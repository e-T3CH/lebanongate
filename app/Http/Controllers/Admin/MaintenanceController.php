<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Ops\Backups;
use BMMatic\Ops\ContentCheck;
use BMMatic\Ops\Scheduler;
use BMMatic\Repositories\LanguageRepository;
use BMMatic\Repositories\UserRepository;
use BMMatic\Services\AuditLog;
use BMMatic\Services\MediaLibrary;

/**
 * Settings → Maintenance: everything that otherwise needs the command line, for hosts without SSH (one.com).
 *
 *  - the scheduler address for a web cron service, "run now" and a new address;
 *  - backups: make one, download, upload one made elsewhere, restore (password + typing RESTORE; a safety backup of
 *    the current state is made first);
 *  - the uptime-monitor address (/health with its token);
 *  - the before-going-live content check;
 *  - analytics (off until chosen; loads only after a visitor's consent).
 *
 * Administrators only (security.manage): a backup holds every record of the site.
 */
final class MaintenanceController extends AdminController
{
    /** Largest backup accepted through the browser (the host's upload limit may be lower). */
    private const MAX_UPLOAD_BYTES = 512 * 1048576;

    public function __construct(\BMMatic\Core\App $app, private readonly SettingsController $settingsTabs)
    {
        parent::__construct($app);
    }

    public function index(Request $request): Response
    {
        $settings = $this->app->settings();
        $backups = $this->backups();
        $list = [];
        $backupError = null;
        try {
            foreach ($backups->list() as $backup) {
                $list[] = ['name' => basename($backup['file']), 'size' => $backup['size'], 'at' => $this->app->formatDate($backup['at'])];
            }
        } catch (\Throwable $e) {
            $backupError = $e->getMessage();
        }
        $report = $settings->get(Scheduler::LAST_REPORT_SETTING);
        $lastRun = $settings->string(Scheduler::LAST_RUN_SETTING);
        $healthToken = $this->app->config->string('ops.health_token');
        $findings = (new ContentCheck($this->app->db(), $settings, new LanguageRepository($this->app->db()), new MediaLibrary($this->app->db(), $this->app->clock)))->run();
        $session = $this->app->session();
        $restoreError = $session->pull('restore_error');
        $uploadError = $session->pull('backup_upload_error');
        $analyticsErrors = $session->pull('analytics_errors');
        return $this->adminView('admin/settings-maintenance', 'settings', $this->t('admin.settings.title'), $this->t('admin.maintenance.subtitle'), [
            'tabs' => $this->settingsTabs->tabs('maintenance'),
            'schedulerUrl' => $this->app->baseUrl() . '/cron/' . Scheduler::token($settings),
            'lastRun' => $lastRun === '' ? null : $this->app->formatDate($lastRun),
            'lastReport' => is_array($report) ? $report : null,
            'backups' => $list,
            'backupError' => $backupError,
            'lastBackupError' => $settings->string('backup.last_error'),
            'restoreError' => is_string($restoreError) ? $restoreError : null,
            'uploadError' => is_string($uploadError) ? $uploadError : null,
            'uploadLimit' => self::uploadLimit(),
            'healthUrl' => strlen($healthToken) >= 32 ? $this->app->baseUrl() . '/health?token=' . $healthToken : null,
            'findings' => $findings,
            'analytics' => [
                'provider' => $settings->string('analytics.provider', 'none'),
                'ga4_id' => $settings->string('analytics.ga4_id'),
                'plausible_domain' => $settings->string('analytics.plausible_domain'),
            ],
            'analyticsErrors' => is_array($analyticsErrors) ? $analyticsErrors : [],
        ]);
    }

    // ------------------------------------------------------------------------------------------------- scheduler

    public function runScheduler(Request $request): Response
    {
        $report = (new Scheduler($this->app->config, $this->app->db(), $this->app->settings(), $this->app->clock))->run($this->app->audit());
        if ($report['status'] === 'busy') {
            $this->flashToast('admin.maintenance.scheduler_busy', 'error');
        } else {
            $this->flashToast($report['status'] === 'ok' ? 'admin.maintenance.scheduler_done' : 'admin.maintenance.scheduler_warn', $report['status'] === 'ok' ? 'success' : 'error');
        }
        return $this->back($this->app->adminPath('settings/maintenance'));
    }

    public function regenerateScheduler(Request $request): Response
    {
        Scheduler::regenerate($this->app->settings());
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->userId(), ['keys' => [Scheduler::TOKEN_SETTING]]);
        $this->flashToast('admin.maintenance.scheduler_regenerated');
        return $this->back($this->app->adminPath('settings/maintenance'));
    }

    // --------------------------------------------------------------------------------------------------- backups

    public function makeBackup(Request $request): Response
    {
        $limiter = $this->app->limiter();
        if ($limiter->hit('ops:backup', 3600) !== 1) {
            $this->flashToast('admin.maintenance.backup_busy', 'error');
            return $this->back($this->app->adminPath('settings/maintenance'));
        }
        $backups = $this->backups();
        @set_time_limit(300);
        ignore_user_abort(true);
        try {
            $result = $backups->run();
            $this->app->audit()->record(AuditLog::BACKUP_CREATED, $this->userId(), ['file' => basename($result['file']), 'via' => 'panel']);
            $this->flashToast('admin.maintenance.backup_made', 'success', ['file' => basename($result['file'])]);
        } catch (\Throwable $e) {
            $backups->recordFailure($e->getMessage());
            $this->flashToast('admin.maintenance.backup_failed', 'error', ['reference' => $this->app->logException($e)]);
        } finally {
            $limiter->clear('ops:backup');
        }
        return $this->back($this->app->adminPath('settings/maintenance'));
    }

    public function download(Request $request): Response
    {
        $path = $this->find((string) $request->param('file'));
        if ($path === null) {
            return $this->app->errorResponse(404);
        }
        $this->app->audit()->record(AuditLog::BACKUP_DOWNLOADED, $this->userId(), ['file' => basename($path)]);
        return Response::file($path, basename($path), 'application/gzip');
    }

    public function upload(Request $request): Response
    {
        $file = $request->file('backup');
        $tmp = is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $back = $this->back($this->app->adminPath('settings/maintenance') . '#backups');
        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->app->session()->flash('backup_upload_error', $this->t(in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'admin.maintenance.upload_too_big' : 'admin.maintenance.upload_missing', ['limit' => self::formatBytes(self::uploadLimit())]));
            return $back;
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            $this->app->session()->flash('backup_upload_error', $this->t('admin.maintenance.upload_too_big', ['limit' => self::formatBytes(self::uploadLimit())]));
            return $back;
        }
        $backups = $this->backups();
        $target = $backups->dir() . DIRECTORY_SEPARATOR . Backups::PREFIX . $this->app->clock->now()->format('Ymd-His') . '-uploaded.tar.gz';
        if (!move_uploaded_file($tmp, $target)) {
            $this->app->session()->flash('backup_upload_error', $this->t('admin.maintenance.upload_missing', ['limit' => self::formatBytes(self::uploadLimit())]));
            return $back;
        }
        try {
            Backups::manifest($target);
        } catch (\Throwable) {
            @unlink($target);
            $this->app->session()->flash('backup_upload_error', $this->t('admin.maintenance.upload_not_backup'));
            return $back;
        }
        @chmod($target, 0600);
        $this->app->audit()->record(AuditLog::BACKUP_CREATED, $this->userId(), ['file' => basename($target), 'via' => 'upload']);
        $this->flashToast('admin.maintenance.upload_done', 'success', ['file' => basename($target)]);
        return $back;
    }

    public function restore(Request $request): Response
    {
        $back = $this->back($this->app->adminPath('settings/maintenance') . '#restore');
        $path = $this->find($request->input('file'));
        if ($path === null) {
            $this->app->session()->flash('restore_error', $this->t('admin.maintenance.restore_choose'));
            return $back;
        }
        if (!$this->app->auth()->confirmPassword($request->input('password'))) {
            $this->app->session()->flash('restore_error', $this->t('admin.maintenance.restore_password'));
            return $back;
        }
        if ($request->input('confirm') !== 'RESTORE') {
            $this->app->session()->flash('restore_error', $this->t('admin.maintenance.restore_type'));
            return $back;
        }
        $limiter = $this->app->limiter();
        if ($limiter->hit('ops:backup', 3600) !== 1) {
            $this->flashToast('admin.maintenance.backup_busy', 'error');
            return $back;
        }
        // Whoever restores keeps signing in with the account and password they use now, also when the backup comes
        // from another installation where that account did not exist.
        $users = new UserRepository($this->app->db(), $this->app->clock);
        $me = $users->find($this->userId() ?? 0);
        $backups = $this->backups();
        @set_time_limit(600);
        ignore_user_abort(true);
        try {
            $safety = $backups->run(false, 'before-restore');
            $result = $backups->restore($path);
        } catch (\Throwable $e) {
            $this->app->session()->flash('restore_error', $this->t('admin.maintenance.restore_failed', ['reference' => $this->app->logException($e)]));
            return $back;
        } finally {
            $limiter->clear('ops:backup');
        }
        $this->app->settings()->refresh();
        $account = $me === null ? 'none' : $this->keepAccount($users, $me['email'], $me['name'], $me['password_hash']);
        $this->app->audit()->record(AuditLog::BACKUP_RESTORED, null, [
            'file' => basename($path),
            'safety' => basename($safety['file']),
            'checksums_match' => $result['checksums_match'],
            'other_installation' => $result['foreign'],
            'secrets_cleared' => $result['secrets_cleared'],
            'two_factor_reset' => $result['two_factor_reset'],
            'account' => $account,
            'via' => 'panel',
        ]);
        // The accounts are now those of the backup: sign in again.
        $this->app->auth()->logout();
        $this->app->session()->flash('login_notice', match (true) {
            !$result['checksums_match'] => 'admin.maintenance.restored_mismatch',
            $result['foreign'] => 'admin.maintenance.restored_foreign',
            default => 'admin.maintenance.restored',
        });
        return $this->back($this->app->adminPath('login'));
    }

    // ------------------------------------------------------------------------------------------------- analytics

    public function saveAnalytics(Request $request): Response
    {
        $settings = $this->app->settings();
        $provider = $request->input('provider');
        $ga4 = strtoupper(trim($request->input('ga4_id')));
        $domain = strtolower(trim($request->input('plausible_domain')));
        $errors = [];
        if (!in_array($provider, ['none', 'ga4', 'plausible'], true)) {
            $errors['provider'] = $this->t('validation.required');
        } elseif ($provider === 'ga4' && preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4) !== 1) {
            $errors['ga4_id'] = $this->t('admin.maintenance.analytics_ga4_invalid');
        } elseif ($provider === 'plausible' && preg_match('/^[a-z0-9.-]{3,253}$/', $domain) !== 1) {
            $errors['plausible_domain'] = $this->t('admin.maintenance.analytics_domain_invalid');
        }
        if ($errors !== []) {
            $this->app->session()->flash('analytics_errors', $errors);
            return $this->back($this->app->adminPath('settings/maintenance') . '#analytics');
        }
        if ($provider === 'ga4') {
            $settings->set('analytics.ga4_id', $ga4);
        } elseif ($provider === 'plausible') {
            $settings->set('analytics.plausible_domain', $domain);
        }
        $settings->set('analytics.provider', $provider);
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->userId(), ['keys' => ['analytics.provider'], 'provider' => $provider]);
        $this->flashToast('admin.maintenance.analytics_saved');
        return $this->back($this->app->adminPath('settings/maintenance') . '#analytics');
    }

    // ------------------------------------------------------------------------------------------------- internals

    /** After a restore: the restoring administrator's account exists, is active, is an administrator and has the current password. */
    private function keepAccount(UserRepository $users, string $email, string $name, string $passwordHash): string
    {
        $existing = $users->findByEmail($email);
        if ($existing === null) {
            $users->create($email, $name, $passwordHash, 'admin');
            return 'added';
        }
        $users->updatePasswordHash($existing['id'], $passwordHash);
        $users->updateProfile($existing['id'], $existing['name'], 'admin', true);
        return 'kept';
    }

    private function backups(): Backups
    {
        return new Backups($this->app->config, $this->app->db(), $this->app->settings(), $this->app->clock);
    }

    /** A backup in the backup folder by its file name (never a path from the request). */
    private function find(string $name): ?string
    {
        if (preg_match('/^' . preg_quote(Backups::PREFIX, '/') . '[A-Za-z0-9-]+\.tar\.gz$/', $name) !== 1) {
            return null;
        }
        foreach ($this->backups()->list() as $backup) {
            if (basename($backup['file']) === $name) {
                return $backup['file'];
            }
        }
        return null;
    }

    private function userId(): ?int
    {
        $id = $this->app->auth()->user()['id'] ?? null;
        return is_int($id) ? $id : null;
    }

    /** The smaller of PHP's upload_max_filesize and post_max_size, in bytes. */
    public static function uploadLimit(): int
    {
        $limits = [];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes((string) ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }
        return $limits === [] ? self::MAX_UPLOAD_BYTES : min(self::MAX_UPLOAD_BYTES, ...$limits);
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^(\d+)\s*([KMG]?)/i', $value, $m) !== 1) {
            return 0;
        }
        return (int) $m[1] * match (strtoupper($m[2])) {
            'G' => 1073741824,
            'M' => 1048576,
            'K' => 1024,
            default => 1,
        };
    }

    public static function formatBytes(int $bytes): string
    {
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format(max(1, $bytes) / 1024, 0) . ' KB';
    }
}
