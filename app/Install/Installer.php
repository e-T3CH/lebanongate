<?php

declare(strict_types=1);

namespace Gate\Install;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Core\Migrator;
use Gate\Core\Paths;
use Gate\Database\Seeders\DatabaseSeeder;
use Gate\I18n\LanguageRules;
use Gate\Repositories\LanguageRepository;
use Gate\Repositories\UserRepository;
use Gate\Security\Crypto;
use Gate\Security\PasswordHasher;
use Gate\Services\AuditLog;
use Gate\Services\Settings;

/**
 * Performs the installation (used by the /install wizard and `php bin/console install`):
 * config/config.local.php → migrations → seeders → admin account → languages → audit entry → lock.
 * Two-factor authentication stays disabled; it is enabled later in Settings → Security.
 */
final class Installer
{
    public const VERSION = '1.0.0';

    public function __construct(private readonly Clock $clock)
    {
    }

    /**
     * @param array{host: string, port: int, name: string, user: string, pass: string} $db
     * @return string|null translation key of the problem, or null when the database is usable
     */
    public function testDatabase(array $db): ?string
    {
        try {
            $pdo = Database::createPdo($db);
        } catch (\PDOException) {
            return 'install.database.error_connect';
        }
        $version = (string) $pdo->query('SELECT VERSION()')?->fetchColumn();
        $isMaria = stripos($version, 'mariadb') !== false;
        $numeric = (string) preg_replace('/[^0-9.].*$/', '', $version);
        if (($isMaria && version_compare($numeric, '10.4', '<')) || (!$isMaria && version_compare($numeric, '5.7', '<'))) {
            return 'install.database.error_version';
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (\'settings\', \'users\')');
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return 'install.database.error_not_empty';
        }
        return self::canMigrate($pdo) ? null : 'install.database.error_privileges';
    }

    /**
     * Tries what the migrations do (create, index, foreign key, alter, drop) on two throw-away tables, so a database
     * user without enough privileges is caught before anything is installed rather than halfway through.
     */
    private static function canMigrate(\PDO $pdo): bool
    {
        $a = '`gate_install_probe_a`';
        $b = '`gate_install_probe_b`';
        try {
            $pdo->exec("CREATE TABLE {$a} (`id` INT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB");
            $pdo->exec("CREATE TABLE {$b} (`id` INT UNSIGNED NOT NULL PRIMARY KEY, `a_id` INT UNSIGNED NULL, CONSTRAINT `gate_install_probe_fk` FOREIGN KEY (`a_id`) REFERENCES {$a} (`id`)) ENGINE=InnoDB");
            $pdo->exec("ALTER TABLE {$b} ADD COLUMN `note` VARCHAR(10) NULL, ADD INDEX `gate_install_probe_note` (`note`)");
            $pdo->exec("INSERT INTO {$a} (`id`) VALUES (1)");
            $pdo->exec("DELETE FROM {$a}");
            $ok = true;
        } catch (\PDOException) {
            $ok = false;
        }
        try {
            $pdo->exec("DROP TABLE IF EXISTS {$b}");
            $pdo->exec("DROP TABLE IF EXISTS {$a}");
        } catch (\PDOException) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * @param array{host: string, port: int, name: string, user: string, pass: string} $db
     * @param list<string> $languages enabled language codes
     * @return array{admin_path: string}
     */
    public function install(
        array $db,
        string $siteName,
        string $siteUrl,
        string $adminName,
        string $adminEmail,
        string $adminPasswordHash,
        array $languages,
        string $defaultLanguage,
        ?string $adminPath = null,
    ): array {
        if (InstallState::isLocked()) {
            throw new \RuntimeException('Already installed.');
        }
        $errors = LanguageRules::validate(LanguageRules::SUPPORTED, $languages, $defaultLanguage);
        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        $appKey = Crypto::generateKey();
        $siteUrl = rtrim($siteUrl, '/');
        $this->writeConfig($db, $siteUrl, $appKey);

        $database = Database::connect($db);
        (new Migrator($database, Paths::root('database/migrations')))->migrate();

        $crypto = new Crypto($appKey);
        $settings = new Settings($database, $crypto, $this->clock);
        $adminPath ??= 'admin-' . self::randomSlug(8);
        (new DatabaseSeeder($database, $settings, $this->clock))->run([
            'site.name' => $siteName,
            'site.url' => $siteUrl,
            'security.admin_path' => $adminPath,
            'security.force_https' => str_starts_with($siteUrl, 'https://'),
            'admin.language' => $defaultLanguage,
        ]);

        (new LanguageRepository($database))->saveState($languages, $defaultLanguage);

        $users = new UserRepository($database, $this->clock);
        if ($users->findByEmail($adminEmail) !== null) {
            throw new \RuntimeException('An account with this email already exists.');
        }
        $userId = $users->create($adminEmail, $adminName, $adminPasswordHash, 'admin');

        $audit = new AuditLog($database, $this->clock);
        $audit->record(AuditLog::USER_CREATED, $userId, ['role' => 'admin', 'via' => 'installer']);
        $audit->record(AuditLog::INSTALLED, $userId, ['version' => self::VERSION, 'languages' => $languages, 'default' => $defaultLanguage]);

        InstallState::lock(self::VERSION);
        return ['admin_path' => $adminPath];
    }

    /**
     * Validates wizard/CLI input. Returns field => translation key.
     *
     * @return array<string, string>
     */
    public static function validateAdmin(string $name, string $email, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $confirm): array
    {
        $errors = [];
        if (trim($name) === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'validation.required';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            $errors['email'] = 'validation.email';
        }
        $policy = (new PasswordHasher())->policyErrors($password, $email);
        if ($policy !== []) {
            $errors['password'] = $policy[0];
        } elseif (!hash_equals($password, $confirm)) {
            $errors['password_confirm'] = 'validation.password_confirm';
        }
        return $errors;
    }

    /** @return array<string, string> */
    public static function validateSite(string $name, string $url): array
    {
        $errors = [];
        if (trim($name) === '' || mb_strlen($name) > 120) {
            $errors['site_name'] = 'validation.required';
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true) || parse_url($url, PHP_URL_QUERY) !== null) {
            $errors['site_url'] = 'validation.url';
        }
        return $errors;
    }

    /** @param array{host: string, port: int, name: string, user: string, pass: string} $db */
    private function writeConfig(array $db, string $siteUrl, string $appKey): void
    {
        $config = [
            'app' => ['env' => 'production', 'debug' => false, 'url' => $siteUrl, 'key' => $appKey],
            'db' => ['host' => $db['host'], 'port' => $db['port'], 'name' => $db['name'], 'user' => $db['user'], 'pass' => $db['pass'], 'charset' => 'utf8mb4'],
            // The uptime-monitor URL is /health?token=… (bin/console health:url prints it).
            'ops' => ['health_token' => \Gate\Ops\Health::token()],
        ];
        $php = "<?php\n\ndeclare(strict_types=1);\n\n// Written by the GATE Lebanon installer on " . gmdate('Y-m-d H:i') . " UTC. Keep this file private (chmod 440).\nreturn " . var_export($config, true) . ";\n";
        $file = Paths::config('config.local.php');
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, $php, LOCK_EX) === false || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException('Could not write ' . $file);
        }
        @chmod($file, 0640);
    }

    public static function randomSlug(int $length): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
