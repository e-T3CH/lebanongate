<?php

declare(strict_types=1);

namespace Gate\Ops;

use Gate\Core\Clock;
use Gate\Core\Config;
use Gate\Core\Database;
use Gate\Core\Paths;
use Gate\Security\Crypto;
use Gate\Services\Settings;

/**
 * Backups: one file per run, gate-backup-YYYYMMDD-HHMMSS.tar.gz, holding
 *   manifest.json   what is inside, row counts, checksums and a fingerprint of the app key
 *   database.sql    the dump (DatabaseDump)
 *   uploads/…       every uploaded file (the media library)
 *
 * They are written to storage/backups by default (never web-reachable) or to ops.backup.dir, which is refused
 * when it lies inside the web root. Old backups are removed after ops.backup.retention_days (at least the three
 * newest are always kept). ops.backup.offsite copies each new backup to a second place: a folder (a mounted or
 * synced drive) or an ftp://, ftps:// or sftp:// URL.
 *
 * The configuration file itself (config.local.php: database password, app key) is not in the backup — it is a
 * secret and belongs in the password manager. Without the same app key, stored secrets (SMTP password, Google
 * tokens) cannot be decrypted after a restore; the manifest records a fingerprint so a restore can warn about it.
 */
final class Backups
{
    public const PREFIX = 'gate-backup-';
    public const FORMAT = 1;
    private const KEEP_AT_LEAST = 3;

    public function __construct(
        private readonly Config $config,
        private readonly Database $db,
        private readonly Settings $settings,
        private readonly Clock $clock,
    ) {
    }

    public function dir(): string
    {
        $configured = trim($this->config->string('ops.backup.dir'));
        $dir = $configured === '' ? Paths::storage('backups') : rtrim($configured, '/\\');
        if (self::isInside($dir, Paths::publicDir())) {
            throw new \RuntimeException('The backup folder may not be inside the web root: ' . $dir);
        }
        if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
            throw new \RuntimeException('Cannot create the backup folder ' . $dir);
        }
        return $dir;
    }

    /**
     * Makes a backup. Returns the file and what went into it.
     *
     * @return array{file: string, size: int, tables: int, rows: int, uploads: int, offsite: string, pruned: int}
     */
    public function run(bool $offsite = true, string $label = ''): array
    {
        $dir = $this->dir();
        $stamp = $this->clock->now()->format('Ymd-His');
        $name = self::PREFIX . $stamp . ($label !== '' ? '-' . preg_replace('/[^a-z0-9-]/', '', $label) : '');
        $work = $dir . DIRECTORY_SEPARATOR . '.work-' . bin2hex(random_bytes(4));
        mkdir($work, 0700, true);
        try {
            $dump = new DatabaseDump($this->db);
            $rows = $dump->write($work . DIRECTORY_SEPARATOR . 'database.sql');
            $tar = new \PharData($work . DIRECTORY_SEPARATOR . $name . '.tar');
            $tar->addFile($work . DIRECTORY_SEPARATOR . 'database.sql', 'database.sql');
            $uploads = 0;
            foreach (self::uploadFiles() as $relative => $absolute) {
                $tar->addFile($absolute, 'uploads/' . $relative);
                $uploads++;
            }
            $tar->addFromString('manifest.json', (string) json_encode([
                'format' => self::FORMAT,
                'created_at' => $this->clock->now()->format('Y-m-d H:i:s'),
                'site' => $this->config->string('app.url'),
                'tables' => $rows,
                'checksums' => $dump->checksums(),
                'uploads' => $uploads,
                'key_fingerprint' => $this->keyFingerprint(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $tar->compress(\Phar::GZ);
            unset($tar);
            $file = $dir . DIRECTORY_SEPARATOR . $name . '.tar.gz';
            if (!rename($work . DIRECTORY_SEPARATOR . $name . '.tar.gz', $file)) {
                throw new \RuntimeException('Cannot move the backup into ' . $dir);
            }
            @chmod($file, 0600);
        } finally {
            self::removeTree($work);
        }
        $copied = $offsite ? $this->copyOffsite($file) : '';
        $pruned = $this->prune();
        $this->settings->set('backup.last_at', $this->clock->now()->format('Y-m-d H:i:s'));
        $this->settings->set('backup.last_file', basename($file));
        $this->settings->set('backup.last_size', (int) filesize($file), 'int');
        $this->settings->set('backup.last_error', '');
        return ['file' => $file, 'size' => (int) filesize($file), 'tables' => count($rows), 'rows' => array_sum($rows), 'uploads' => $uploads, 'offsite' => $copied, 'pruned' => $pruned];
    }

    public function recordFailure(string $message): void
    {
        try {
            $this->settings->set('backup.last_error', $this->clock->now()->format('Y-m-d H:i:s') . ' ' . mb_substr($message, 0, 300));
        } catch (\Throwable) {
            // the database itself may be the problem
        }
    }

    /**
     * Reads the manifest of a backup without extracting it.
     *
     * @return array<string, mixed>
     */
    public static function manifest(string $file): array
    {
        try {
            $tar = new \PharData(self::realFile($file));
            $data = json_decode((string) file_get_contents($tar['manifest.json']->getPathname()), true);
        } catch (\BadMethodCallException|\UnexpectedValueException) {
            $data = null;
        }
        if (!is_array($data) || ($data['format'] ?? null) !== self::FORMAT) {
            throw new \RuntimeException('This file is not a GATE Lebanon backup (no readable manifest).');
        }
        return $data;
    }

    /** True when the backup was made with the same app key as this installation. */
    public function sameKey(string $file): bool
    {
        return hash_equals((string) (self::manifest($file)['key_fingerprint'] ?? ''), $this->keyFingerprint());
    }

    /**
     * Restores the database and the uploads from a backup. The caller asks for confirmation first.
     *
     * A backup of another installation (made with another app key) works too: see reconcile().
     *
     * @return array{statements: int, uploads: int, checksums_match: bool, foreign: bool, secrets_cleared: list<string>, two_factor_reset: int}
     */
    public function restore(string $file): array
    {
        $file = self::realFile($file);
        $manifest = self::manifest($file);
        $foreign = !$this->sameKey($file);
        $snapshot = $this->installationSettings();
        $work = $this->dir() . DIRECTORY_SEPARATOR . '.restore-' . bin2hex(random_bytes(4));
        mkdir($work, 0700, true);
        try {
            $tar = new \PharData($file);
            $tar->extractTo($work, null, true);
            unset($tar);
            $dump = new DatabaseDump($this->db);
            $statements = $dump->restore($work . DIRECTORY_SEPARATOR . 'database.sql');
            $checksums = $dump->checksums();
            $uploads = $this->restoreUploads($work . DIRECTORY_SEPARATOR . 'uploads');
        } finally {
            self::removeTree($work);
        }
        // Counters and locks in the backup belong to the moment it was made (a backup made by the scheduler holds its
        // lock): start from a clean slate.
        $this->db->run('DELETE FROM {rate_limits}');
        $this->settings->refresh();
        // The restored settings describe the moment of the backup; the backups on disk are what exists now.
        $newest = $this->list()[0] ?? null;
        if ($newest !== null) {
            $this->settings->set('backup.last_at', $newest['at']);
            $this->settings->set('backup.last_file', basename($newest['file']));
        }
        $expected = is_array($manifest['checksums'] ?? null) ? $manifest['checksums'] : [];
        ksort($expected);
        $reconciled = $this->reconcile($snapshot, $foreign);
        $this->settings->refresh();
        return ['statements' => $statements, 'uploads' => $uploads, 'checksums_match' => $expected == $checksums, 'foreign' => $foreign] + $reconciled;
    }

    /**
     * The settings that belong to this installation rather than to its content: the admin address, the scheduler
     * token and every encrypted value (readable with this installation's key). Raw rows, taken before a restore.
     *
     * @return array<string, array<string, mixed>>
     */
    private function installationSettings(): array
    {
        $rows = [];
        $stmt = $this->db->run("SELECT `key`, `value`, `type`, `is_secret`, `updated_at` FROM {settings} WHERE `is_secret` = 1 OR `key` IN ('security.admin_path', 'ops.cron_token')");
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rows[(string) $row['key']] = $row;
        }
        return $rows;
    }

    /**
     * After a restore: keep this installation's admin address and scheduler token (the addresses in use now), and
     * deal with values the backup encrypted with another app key — they cannot be read here. Such a secret gets this
     * installation's value when it has one (for example the email password entered after installing), otherwise it
     * is emptied. Two-factor secrets and recovery codes cannot be carried over at all: two-factor authentication is
     * switched off for those accounts (they switch it on again), and unusable invitation and reset links are removed.
     *
     * @param array<string, array<string, mixed>> $snapshot
     * @return array{secrets_cleared: list<string>, two_factor_reset: int}
     */
    private function reconcile(array $snapshot, bool $foreign): array
    {
        $columns = ['value', 'type', 'is_secret', 'updated_at'];
        foreach (['security.admin_path', 'ops.cron_token'] as $key) {
            if (isset($snapshot[$key])) {
                $this->db->upsert('settings', $snapshot[$key], $columns);
            }
        }
        $crypto = new Crypto($this->config->string('app.key'));
        $readable = static function (string $value) use ($crypto): bool {
            try {
                $crypto->decrypt($value);
                return true;
            } catch (\RuntimeException) {
                return false;
            }
        };
        $cleared = [];
        foreach ($this->db->run('SELECT `key`, `value` FROM {settings} WHERE `is_secret` = 1 AND `value` IS NOT NULL')->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['key'];
            if ($readable((string) $row['value'])) {
                continue;
            }
            if (isset($snapshot[$key]) && $snapshot[$key]['value'] !== null) {
                $this->db->upsert('settings', $snapshot[$key], $columns);
            } else {
                $this->db->update('settings', ['value' => null], ['key' => $key]);
                $cleared[] = $key;
            }
        }
        $reset = 0;
        foreach ($this->db->run('SELECT `id`, `totp_secret` FROM {users} WHERE `totp_secret` IS NOT NULL')->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if (!$foreign && $readable((string) $row['totp_secret'])) {
                continue;
            }
            $this->db->update('users', ['totp_secret' => null, 'totp_enabled_at' => null, 'totp_last_timestep' => null, 'recovery_codes' => null], ['id' => (int) $row['id']]);
            $reset++;
        }
        if ($foreign) {
            // Their tokens are stored as HMACs of the other installation's key: no link can match any more.
            $tables = (new DatabaseDump($this->db))->tables();
            foreach (['user_invitations', 'password_resets'] as $table) {
                if (in_array($table, $tables, true)) {
                    $this->db->run('DELETE FROM {' . $table . '}');
                }
            }
        }
        sort($cleared);
        return ['secrets_cleared' => $cleared, 'two_factor_reset' => $reset];
    }

    /** @return list<array{file: string, size: int, at: string}> newest first */
    public function list(): array
    {
        $out = [];
        foreach (glob($this->dir() . DIRECTORY_SEPARATOR . self::PREFIX . '*.tar.gz') ?: [] as $file) {
            $out[] = ['file' => $file, 'size' => (int) filesize($file), 'at' => gmdate('Y-m-d H:i:s', (int) filemtime($file))];
        }
        usort($out, static fn (array $a, array $b): int => strcmp(basename($b['file']), basename($a['file'])));
        return $out;
    }

    /** Removes backups older than the retention, always keeping the newest few. */
    public function prune(): int
    {
        $days = max(1, (int) $this->config->get('ops.backup.retention_days', 30));
        return self::pruneDir($this->dir(), $days, $this->clock->now()->getTimestamp());
    }

    public static function pruneDir(string $dir, int $days, int $now): int
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . self::PREFIX . '*.tar.gz') ?: [];
        rsort($files);
        $removed = 0;
        foreach (array_slice($files, self::KEEP_AT_LEAST) as $file) {
            if (preg_match('/' . self::PREFIX . '(\d{8})-(\d{6})/', basename($file), $m) !== 1) {
                continue;
            }
            $time = \DateTimeImmutable::createFromFormat('Ymd His', $m[1] . ' ' . $m[2], new \DateTimeZone('UTC'));
            if ($time !== false && $time->getTimestamp() < $now - $days * 86400) {
                $removed += @unlink($file) ? 1 : 0;
            }
        }
        return $removed;
    }

    /** Copies a backup to ops.backup.offsite. Returns where it went ('' when no target is configured). */
    public function copyOffsite(string $file): string
    {
        $target = trim($this->config->string('ops.backup.offsite'));
        if ($target === '') {
            return '';
        }
        if (preg_match('#^(ftp|ftps|sftp)://#i', $target) === 1 || str_starts_with($target, 'file://')) {
            return $this->upload($file, rtrim($target, '/') . '/' . basename($file));
        }
        $dir = rtrim($target, '/\\');
        if (self::isInside($dir, Paths::publicDir())) {
            throw new \RuntimeException('The off-site folder may not be inside the web root.');
        }
        if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
            throw new \RuntimeException('Cannot create the off-site folder ' . $dir);
        }
        if (!copy($file, $dir . DIRECTORY_SEPARATOR . basename($file))) {
            throw new \RuntimeException('Copying the backup to ' . $dir . ' failed.');
        }
        self::pruneDir($dir, max(1, (int) $this->config->get('ops.backup.retention_days', 30)), $this->clock->now()->getTimestamp());
        return $dir;
    }

    /** A fingerprint of the app key (never the key itself). */
    public function keyFingerprint(): string
    {
        return substr((new Crypto($this->config->string('app.key')))->hmac('backup-key-fingerprint'), 0, 16);
    }

    private function upload(string $file, string $url): string
    {
        $handle = fopen($file, 'rb');
        if ($handle === false || !function_exists('curl_init')) {
            throw new \RuntimeException('Uploading the backup needs the PHP curl extension.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => (int) filesize($file),
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
            CURLOPT_USE_SSL => str_starts_with(strtolower($url), 'ftps://') ? CURLUSESSL_ALL : CURLUSESSL_NONE,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $ok = curl_exec($curl) !== false;
        $error = curl_error($curl);
        curl_close($curl);
        fclose($handle);
        // The URL may carry a password: report the host only.
        $where = (string) preg_replace('#//[^@/]*@#', '//', $url);
        if (!$ok) {
            throw new \RuntimeException('Uploading the backup to ' . $where . ' failed: ' . $error);
        }
        return $where;
    }

    private function restoreUploads(string $from): int
    {
        $target = Paths::publicDir('uploads');
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }
        // Clear the current uploads (never the .htaccess that stops code from running there).
        foreach (glob($target . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $count = 0;
        foreach (is_dir($from) ? (glob($from . DIRECTORY_SEPARATOR . '*') ?: []) : [] as $file) {
            $name = basename($file);
            // Only the file names the media library itself produces.
            if (is_file($file) && preg_match('/^[a-f0-9]{16,64}\.(png|jpe?g|webp)$/', $name) === 1) {
                copy($file, $target . DIRECTORY_SEPARATOR . $name);
                $count++;
            }
        }
        // Resized variants are regenerated on demand.
        foreach (glob(Paths::publicDir('media/cache/*.webp')) ?: [] as $variant) {
            @unlink($variant);
        }
        return $count;
    }

    /** @return array<string, string> relative name => absolute path of every uploaded file */
    private static function uploadFiles(): array
    {
        $out = [];
        foreach (glob(Paths::publicDir('uploads') . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file) && basename($file) !== '.htaccess') {
                $out[basename($file)] = $file;
            }
        }
        ksort($out);
        return $out;
    }

    private static function realFile(string $file): string
    {
        $real = realpath($file);
        if ($real === false || !is_file($real) || !str_ends_with($real, '.tar.gz')) {
            throw new \RuntimeException('Backup not found: ' . $file);
        }
        return $real;
    }

    private static function isInside(string $dir, string $parent): bool
    {
        $norm = static fn (string $p): string => rtrim(str_replace('\\', '/', strtolower((string) (realpath($p) ?: $p))), '/') . '/';
        return str_starts_with($norm($dir), $norm($parent));
    }

    private static function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
