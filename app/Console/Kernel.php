<?php

declare(strict_types=1);

namespace Gate\Console;

use Gate\Admin\Permissions;
use Gate\Core\App;
use Gate\Core\Clock;
use Gate\Core\Config;
use Gate\Core\Database;
use Gate\Core\Migrator;
use Gate\Core\SystemClock;
use Gate\Database\Seeders\DatabaseSeeder;
use Gate\Http\AdminRoutes;
use Gate\Http\Router;
use Gate\Install\InstallState;
use Gate\Install\Installer;
use Gate\Mail\MailMessage;
use Gate\Mail\MailQueue;
use Gate\Mail\MailWorker;
use Gate\Mail\SmtpTransport;
use Gate\Ops\Backups;
use Gate\Ops\ContentCheck;
use Gate\Ops\Health;
use Gate\Ops\Scheduler;
use Gate\Repositories\LanguageRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Security\ArraySession;
use Gate\Security\Crypto;
use Gate\Security\PasswordHasher;
use Gate\Security\RateLimiter;
use Gate\Services\AuditLog;
use Gate\Services\MediaLibrary;
use Gate\Services\Settings;

/**
 * bin/console. Refuses to run outside the CLI SAPI. Returns an exit code.
 */
final class Kernel
{
    public const HELP = <<<'TXT'
GATE Lebanon console

  migrate                          Run pending migrations
  migrate:status                   List migrations
  migrate:rollback                 Roll back the last batch
  seed                             Add missing settings, languages and UI strings
  install --db-name=... --db-user=... [--db-host=127.0.0.1 --db-port=3306 --db-pass=...]
          --site-name=... --site-url=... --admin-name=... --admin-email=... --admin-password-stdin
          [--languages=en,ar,fr --default-language=en --admin-path=...]
                                   Non-interactive installation (same steps as /install)
  admin:path                       Print the admin sign-in URL
  admin:path --regenerate          Replace the admin path with a new random one (printed once)
  security:2fa-reset <email>       Disable 2FA and delete the recovery codes of a user
  user:password-reset <email> [--password-stdin]
                                   Set a new password (hidden prompt) and end all of that user's sessions
  security:unlock <email> [ip]     Clear the login lockout for an account (and an IP)
  mail:work [--limit=20]           Send queued emails now (for a cron job; the site also sends after each request)
  mail:test <email>                Send a test email with the saved SMTP settings
  redirects:import <file.csv>      Import old URL redirects (CSV: from_path,to_path[,status])
  forms:unlock <ip>                Clear the contact and newsletter form rate limits for one visitor IP address
  backup:run [--no-offsite]        Back up the database and the uploads (storage/backups; for a daily cron job)
  backup:list                      List the backups, newest first
  backup:restore <file> [--no-safety-backup]
                                   Replace the database and the uploads with a backup (asks for confirmation)
  schedule:run                     All scheduled work in one go: emails and the daily backup
                                   (one cron line every 15 minutes; without cron use the scheduler address)
  schedule:url [--regenerate]      Print the scheduler address for a web cron service such as cron-job.org
  content:check                    List unfinished content: [placeholders], missing translations, alt texts, SEO
  health:url                       Print the uptime-monitor URL (/health with its token)
  routes:list                      The admin routes with the permission each needs (authorisation matrix, Markdown)
  analytics:set none | ga4 <G-XXXXXXX> | plausible <domain>
                                   Choose the analytics that load after a visitor consents (off by default)
  build:release --layout=standard|split|webroot [--app-dir=gate-app] [--web-dir=public_html] [--output=build] [--composer=path] [--keep]
                                   Create a deployable zip (see README.md)
TXT;

    /** @var resource */
    private $stdin;
    /** @var resource */
    private $stdout;
    /** @var resource */
    private $stderr;
    private readonly Clock $clock;

    /**
     * @param resource|null $stdin
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function __construct($stdin = null, $stdout = null, $stderr = null, ?Clock $clock = null)
    {
        $this->stdin = $stdin ?? STDIN;
        $this->stdout = $stdout ?? STDOUT;
        $this->stderr = $stderr ?? STDERR;
        $this->clock = $clock ?? new SystemClock();
    }

    /** @param list<string> $argv */
    public function run(array $argv, string $sapi = PHP_SAPI): int
    {
        if ($sapi !== 'cli') {
            // Recovery commands must never be reachable from the web.
            return 64;
        }
        $command = $argv[1] ?? 'help';
        [$options, $args] = self::parse(array_slice($argv, 2));
        try {
            return match ($command) {
                'migrate' => $this->migrate(),
                'migrate:status' => $this->migrateStatus(),
                'migrate:rollback' => $this->rollback(),
                'seed' => $this->seed(),
                'install' => $this->install($options),
                'admin:path' => $this->adminPath(isset($options['regenerate'])),
                'security:2fa-reset' => $this->twoFactorReset($args[0] ?? ''),
                'user:password-reset' => $this->passwordReset($args[0] ?? '', isset($options['password-stdin'])),
                'security:unlock' => $this->unlock($args[0] ?? '', $args[1] ?? ''),
                'build:release' => (new ReleaseBuilder($this->stdout))->build($options),
                'mail:work' => $this->mailWork((int) ($options['limit'] ?? 20)),
                'mail:test' => $this->mailTest($args[0] ?? ''),
                'redirects:import' => $this->redirectsImport($args[0] ?? ''),
                'forms:unlock' => $this->formsUnlock($args[0] ?? ''),
                'backup:run' => $this->backupRun(!isset($options['no-offsite'])),
                'backup:list' => $this->backupList(),
                'backup:restore' => $this->backupRestore($args[0] ?? '', !isset($options['no-safety-backup'])),
                'schedule:run' => $this->scheduleRun(),
                'schedule:url' => $this->scheduleUrl(isset($options['regenerate'])),
                'content:check' => $this->contentCheck(),
                'health:url' => $this->healthUrl(),
                'routes:list' => $this->routesList(),
                'analytics:set' => $this->analyticsSet($args[0] ?? '', $args[1] ?? ''),
                default => $this->line(self::HELP),
            };
        } catch (\Throwable $e) {
            fwrite($this->stderr, get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
            return 1;
        }
    }

    /**
     * @param list<string> $tokens
     * @return array{0: array<string, string>, 1: list<string>}
     */
    public static function parse(array $tokens): array
    {
        $options = [];
        $args = [];
        foreach ($tokens as $token) {
            if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/s', $token, $m) === 1) {
                $options[$m[1]] = $m[2] ?? '';
            } else {
                $args[] = $token;
            }
        }
        return [$options, $args];
    }

    private function migrate(): int
    {
        [, $db] = $this->connect();
        $ran = Migrator::default($db)->migrate();
        return $this->line($ran === [] ? 'Nothing to migrate.' : 'Migrated: ' . implode(', ', $ran));
    }

    private function migrateStatus(): int
    {
        [, $db] = $this->connect();
        foreach (Migrator::default($db)->status() as $name => $done) {
            $this->line(($done ? '[x] ' : '[ ] ') . $name);
        }
        return 0;
    }

    private function rollback(): int
    {
        [, $db] = $this->connect();
        $rolled = Migrator::default($db)->rollback();
        return $this->line($rolled === [] ? 'Nothing to roll back.' : 'Rolled back: ' . implode(', ', $rolled));
    }

    private function seed(): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $result = (new DatabaseSeeder($db, $settings, $this->clock))->run();
        return $this->line(sprintf('Added %d settings, %d languages, %d UI strings.', $result['settings'], $result['languages'], $result['translations']));
    }

    /** @param array<string, string> $o */
    private function install(array $o): int
    {
        if (InstallState::isLocked()) {
            return $this->error('Already installed (storage/installed.lock exists).');
        }
        foreach (['db-name', 'db-user', 'site-name', 'site-url', 'admin-name', 'admin-email'] as $key) {
            if (($o[$key] ?? '') === '') {
                return $this->error('Missing --' . $key);
            }
        }
        $password = isset($o['admin-password-stdin'])
            ? (new PasswordPrompt($this->stdin, $this->stdout))->fromStdin()
            : (new PasswordPrompt($this->stdin, $this->stdout))->ask('Admin password: ');
        $db = ['host' => $o['db-host'] ?? '127.0.0.1', 'port' => (int) ($o['db-port'] ?? 3306), 'name' => $o['db-name'], 'user' => $o['db-user'], 'pass' => $o['db-pass'] ?? ''];
        $installer = new Installer($this->clock);
        $dbError = $installer->testDatabase($db);
        if ($dbError !== null) {
            return $this->error('Database check failed: ' . $dbError);
        }
        $errors = Installer::validateSite($o['site-name'], $o['site-url']) + Installer::validateAdmin($o['admin-name'], $o['admin-email'], $password, $password);
        if ($errors !== []) {
            return $this->error('Invalid input: ' . json_encode($errors));
        }
        $languages = array_values(array_filter(array_map('trim', explode(',', $o['languages'] ?? 'en,fr,nl'))));
        $result = $installer->install($db, $o['site-name'], $o['site-url'], $o['admin-name'], strtolower($o['admin-email']), (new PasswordHasher())->hash($password), $languages, $o['default-language'] ?? ($languages[0] ?? 'en'), ($o['admin-path'] ?? '') !== '' ? $o['admin-path'] : null);
        $this->line('Installed. Admin sign-in: ' . rtrim($o['site-url'], '/') . '/' . $result['admin_path'] . '/login');
        return $this->line('Two-factor authentication is disabled; enable it in Settings → Security.');
    }

    private function adminPath(bool $regenerate): int
    {
        $recovery = $this->recovery();
        if ($regenerate) {
            $this->line('New admin sign-in URL (shown once, store it safely):');
            return $this->line($recovery->regenerateAdminPath());
        }
        return $this->line($recovery->adminLoginUrl());
    }

    private function twoFactorReset(string $email): int
    {
        if ($email === '') {
            return $this->error('Usage: security:2fa-reset <email>');
        }
        if (!$this->recovery()->resetTwoFactor($email)) {
            return $this->error('No user with that email address.');
        }
        return $this->line('Two-factor authentication disabled and recovery codes deleted for ' . $email . '.');
    }

    private function passwordReset(string $email, bool $fromStdin): int
    {
        if ($email === '') {
            return $this->error('Usage: user:password-reset <email> [--password-stdin]');
        }
        $prompt = new PasswordPrompt($this->stdin, $this->stdout);
        if ($fromStdin) {
            $password = $prompt->fromStdin();
            $confirm = $password;
        } else {
            $password = $prompt->ask('New password (at least ' . PasswordHasher::MIN_LENGTH . ' characters): ');
            $confirm = $prompt->ask('Repeat the new password: ');
        }
        if (!hash_equals($password, $confirm)) {
            return $this->error('The passwords do not match.');
        }
        $errors = $this->recovery()->resetPassword($email, $password);
        if ($errors === ['user_not_found']) {
            return $this->error('No user with that email address.');
        }
        if ($errors !== []) {
            $messages = ['validation.password_min' => 'Use at least ' . PasswordHasher::MIN_LENGTH . ' characters.', 'validation.password_max' => 'The password is too long.', 'validation.password_common' => 'This password is too common or too simple.', 'validation.password_contains_email' => 'The password must not contain the email name.'];
            return $this->error(implode(' ', array_map(static fn (string $k): string => $messages[$k] ?? $k, $errors)));
        }
        return $this->line('Password changed. All sessions of ' . $email . ' have been ended.');
    }

    private function unlock(string $email, string $ip): int
    {
        if ($email === '') {
            return $this->error('Usage: security:unlock <email> [ip]');
        }
        $this->recovery()->unlock($email, $ip);
        return $this->line('Login lockout cleared.');
    }

    private function mailWork(int $limit): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $transport = SmtpTransport::fromSettings($settings);
        if (!$transport->isConfigured()) {
            return $this->error('Email is not configured (Settings → Email).');
        }
        $result = (new MailWorker(new MailQueue($db, $this->clock), $transport))->run(max(1, $limit), 120.0);
        return $this->line(sprintf('Sent %d, failed %d.', $result['sent'], $result['failed']));
    }

    private function mailTest(string $to): int
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return $this->error('Usage: mail:test <email>');
        }
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $site = $settings->string('site.name', 'GATE Lebanon');
        SmtpTransport::fromSettings($settings)->send(new MailMessage($to, '', 'Test email from ' . $site, 'This test email confirms that the email settings of ' . $site . ' work.'));
        return $this->line('Test email sent to ' . $to . '.');
    }

    private function redirectsImport(string $file): int
    {
        if ($file === '' || !is_file($file)) {
            return $this->error('Usage: redirects:import <file.csv>  (columns: from_path,to_path[,status])');
        }
        [, $db] = $this->connect();
        $repo = new RedirectRepository($db, $this->clock);
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return $this->error('Cannot read ' . $file);
        }
        $count = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $from = trim((string) ($row[0] ?? ''));
            $to = trim((string) ($row[1] ?? ''));
            $status = (int) ($row[2] ?? 301);
            if ($from === '' || $from === 'from_path' || preg_match('#^(/\S*|https?://\S+)$#', $to) !== 1 || !in_array($status, [301, 302, 307, 308], true)) {
                $skipped++;
                continue;
            }
            $repo->add($from, $to, $status);
            $count++;
        }
        fclose($handle);
        return $this->line(sprintf('Imported %d redirects (%d rows skipped).', $count, $skipped));
    }

    private function backups(): Backups
    {
        [$config, $db] = $this->connect();
        return new Backups($config, $db, new Settings($db, new Crypto($config->string('app.key')), $this->clock), $this->clock);
    }

    private function backupRun(bool $offsite): int
    {
        [, $db] = $this->connect();
        $limiter = new RateLimiter($db, $this->clock);
        if ($limiter->hit('ops:backup', 3600) !== 1) {
            return $this->error('Another backup is running (or one started less than an hour ago and did not finish).');
        }
        $backups = $this->backups();
        try {
            $result = $backups->run($offsite);
        } catch (\Throwable $e) {
            $backups->recordFailure($e->getMessage());
            return $this->error('Backup failed: ' . $e->getMessage());
        } finally {
            $limiter->clear('ops:backup');
        }
        $this->line(sprintf('Backup written: %s (%.1f MB, %d tables, %d rows, %d uploaded files).', $result['file'], $result['size'] / 1048576, $result['tables'], $result['rows'], $result['uploads']));
        if ($result['offsite'] !== '') {
            $this->line('Off-site copy: ' . $result['offsite']);
        }
        return $this->line($result['pruned'] > 0 ? 'Removed ' . $result['pruned'] . ' backup(s) past the retention period.' : 'Nothing past the retention period.');
    }

    private function scheduleRun(): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $report = (new Scheduler($config, $db, $settings, $this->clock))->run();
        if ($report['status'] === 'busy') {
            return $this->line('Another run is busy; nothing done.');
        }
        $this->line('Email: ' . ($report['mail'] === null ? 'not set up' : sprintf('sent %d, failed %d', $report['mail']['sent'], $report['mail']['failed'])));
        $this->line('Backup: ' . $report['backup']);
        return $report['status'] === 'ok' ? 0 : 1;
    }

    private function scheduleUrl(bool $regenerate): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $token = $regenerate ? Scheduler::regenerate($settings) : Scheduler::token($settings);
        $base = rtrim($config->string('app.url'), '/');
        $this->line(($base === '' ? '' : $base) . '/cron/' . $token);
        return $this->line($regenerate ? 'New address: the old one stops working now. Update it at your cron service.' : 'Open it every 15 minutes (cron-job.org or similar). Keep it private.');
    }

    private function backupList(): int
    {
        $list = $this->backups()->list();
        if ($list === []) {
            return $this->line('No backups yet. Run: php bin/console backup:run');
        }
        foreach ($list as $backup) {
            $this->line(sprintf('%s  %8.1f MB  %s', $backup['at'], $backup['size'] / 1048576, $backup['file']));
        }
        return 0;
    }

    private function backupRestore(string $file, bool $safetyBackup): int
    {
        if ($file === '') {
            return $this->error('Usage: backup:restore <file>   (see backup:list)');
        }
        [$config] = $this->connect();
        $backups = $this->backups();
        try {
            $manifest = Backups::manifest($file);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
        $tables = is_array($manifest['tables'] ?? null) ? $manifest['tables'] : [];
        $this->line('Backup from ' . (string) ($manifest['created_at'] ?? '?') . ' UTC of ' . (string) ($manifest['site'] ?? '?'));
        $this->line(sprintf('  %d tables, %d rows, %d uploaded files', count($tables), array_sum(array_map('intval', $tables)), (int) ($manifest['uploads'] ?? 0)));
        if (!$backups->sameKey($file)) {
            $this->line('WARNING: this backup was made with a different app key. Stored secrets (the SMTP password, Google');
            $this->line('tokens, two-factor secrets) will not decrypt: re-enter them afterwards, and users with 2FA need a reset.');
        }
        $this->line('');
        $this->line('This REPLACES everything in database "' . $config->string('db.name') . '" and every uploaded file.');
        fwrite($this->stdout, 'Type RESTORE to continue: ');
        $answer = trim((string) fgets($this->stdin));
        if ($answer !== 'RESTORE') {
            return $this->error('Cancelled. Nothing was changed.');
        }
        if ($safetyBackup) {
            $safety = $backups->run(false, 'before-restore');
            $this->line('Safety backup of the current state: ' . $safety['file']);
        }
        $result = $backups->restore($file);
        (new AuditLog(Database::instance(), $this->clock))->record(AuditLog::BACKUP_RESTORED, null, ['file' => basename($file), 'via' => 'cli']);
        $this->line(sprintf('Restored %d statements and %d uploaded files.', $result['statements'], $result['uploads']));
        if ($result['foreign']) {
            $this->line('Backup of another installation: the admin address and scheduler token of this one are kept.');
            $this->line($result['secrets_cleared'] === [] ? 'Every stored secret is readable.' : 'Enter again (could not be decrypted): ' . implode(', ', $result['secrets_cleared']));
            $this->line(sprintf('Two-factor authentication switched off for %d account(s); they set it up again.', $result['two_factor_reset']));
        }
        return $result['checksums_match']
            ? $this->line('Every table matches the backup (checksums verified).')
            : $this->error('The restored tables do not match the checksums in the backup. Check the database before going on.');
    }

    private function contentCheck(): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        $languages = new LanguageRepository($db);
        $findings = (new ContentCheck($db, $settings, $languages, new MediaLibrary($db, $this->clock)))->run();
        $total = 0;
        foreach ($findings as $group => $items) {
            $title = $group === 'settings' ? 'Site-wide settings' : 'Language ' . strtoupper($group) . ($group === $languages->defaultCode() ? ' (default)' : '');
            $this->line($title . ($items === [] ? ': nothing to finish' : ' (' . count($items) . ')'));
            foreach ($items as $item) {
                $this->line('  - ' . $item);
            }
            $this->line('');
            $total += count($items);
        }
        if ($total === 0) {
            return $this->line('Everything is filled in. Ready to go live.');
        }
        return $this->error($total . ' item(s) to finish before going live.');
    }

    private function healthUrl(): int
    {
        [$config] = $this->connect();
        $token = $config->string('ops.health_token');
        $base = rtrim($config->string('app.url'), '/');
        if (strlen($token) < Health::MIN_TOKEN_LENGTH) {
            $this->line('No health token is configured yet. Add this to config/config.local.php (inside the returned array):');
            $this->line('');
            $this->line("  'ops' => ['health_token' => '" . Health::token() . "'],");
            $this->line('');
            return $this->line('Then run this command again for the URL.');
        }
        $this->line($base . '/health?token=' . $token);
        return $this->line('Give this URL to an uptime monitor; it answers JSON with "status": "ok", "warn" or "fail". Keep it private.');
    }

    /** Analytics provider (loaded only after consent; the CSP is extended for that provider only then). */
    private function analyticsSet(string $provider, string $value): int
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        if ($provider === 'none') {
            $settings->set('analytics.provider', 'none');
            return $this->line('Analytics switched off.');
        }
        if ($provider === 'ga4' && preg_match('/^G-[A-Z0-9]{4,20}$/', $value) === 1) {
            $settings->set('analytics.ga4_id', $value);
        } elseif ($provider === 'plausible' && preg_match('/^[a-z0-9.-]{3,253}$/', $value) === 1) {
            $settings->set('analytics.plausible_domain', $value);
        } else {
            return $this->error('Usage: analytics:set none | ga4 <G-XXXXXXX> | plausible <domain>');
        }
        $settings->set('analytics.provider', $provider);
        (new AuditLog($db, $this->clock))->record(AuditLog::SETTINGS_CHANGED, null, ['keys' => ['analytics.provider'], 'provider' => $provider, 'via' => 'cli']);
        return $this->line('Analytics: ' . $provider . ' (' . $value . '). It loads only for visitors who accept analytics cookies. Update the cookie policy.');
    }

    /** The admin routes with the permission each one needs and who may use it (the authorisation matrix). */
    private function routesList(): int
    {
        $router = new Router();
        AdminRoutes::register(new App(Config::load(), $this->clock, new ArraySession()), $router, '/<admin>');
        $permissions = Permissions::instance();
        $roles = $permissions->roles();
        $this->line('| Method | Path | Permission | ' . implode(' | ', array_map('ucfirst', $roles)) . ' | Signed out |');
        $this->line('| --- | --- | --- | ' . str_repeat('--- | ', count($roles)) . '--- |');
        foreach ($router->routes() as $route) {
            $permission = null;
            foreach ($route['flags'] as $flag) {
                $permission = str_starts_with($flag, 'perm:') ? substr($flag, 5) : $permission;
            }
            $auth = in_array('auth', $route['flags'], true);
            $cells = array_map(static fn (string $role): string => !$auth || $permission === null || $permissions->allows($role, $permission) ? 'yes' : '403', $roles);
            $this->line('| ' . $route['method'] . ' | `' . $route['path'] . '` | ' . ($permission ?? ($auth ? 'signed in' : 'public')) . ' | ' . implode(' | ', $cells) . ' | ' . ($auth ? 'login' : 'yes') . ' |');
        }
        return 0;
    }

    private function formsUnlock(string $ip): int
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->error('Usage: forms:unlock <ip>');
        }
        [, $db] = $this->connect();
        $limiter = new RateLimiter($db, $this->clock);
        foreach (['contact', 'newsletter'] as $form) {
            $limiter->clear('form:' . $form . ':' . $ip);
        }
        return $this->line('Contact and newsletter form rate limits cleared for ' . $ip . '.');
    }

    private function recovery(): Recovery
    {
        [$config, $db] = $this->connect();
        $settings = new Settings($db, new Crypto($config->string('app.key')), $this->clock);
        return new Recovery($db, $settings, $this->clock, $config->string('app.url'));
    }

    /** @return array{0: Config, 1: Database} */
    private function connect(): array
    {
        if (!Config::localFileExists()) {
            throw new \RuntimeException('Not installed: config/config.local.php is missing. Run the installer first.');
        }
        $config = Config::load();
        /** @var array<string, mixed> $db */
        $db = (array) $config->get('db', []);
        return [$config, Database::isConnected() ? Database::instance() : Database::connect($db)];
    }

    private function line(string $text): int
    {
        fwrite($this->stdout, $text . PHP_EOL);
        return 0;
    }

    private function error(string $text): int
    {
        fwrite($this->stderr, $text . PHP_EOL);
        return 1;
    }
}
