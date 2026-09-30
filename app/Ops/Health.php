<?php

declare(strict_types=1);

namespace BMMatic\Ops;

use BMMatic\Core\App;
use BMMatic\Core\Paths;
use BMMatic\Http\Request;
use BMMatic\Http\Response;

/**
 * GET /health?token=… — a JSON status for an uptime monitor.
 *
 * The token lives in config.local.php (`ops.health_token`), not in the database, so the endpoint can still answer
 * — with "fail" and HTTP 503 — when the database is down. Without the right token the URL is a plain 404, the same
 * answer as any page that does not exist.
 *
 * status "ok": everything fine (HTTP 200). "warn": the site works but something needs a look — an overdue
 * backup or review sync, stuck or failed emails, a scheduler that stopped running (HTTP 200). "fail": the
 * database or storage is broken (HTTP 503).
 */
final class Health
{
    public const MIN_TOKEN_LENGTH = 32;
    /** A daily backup that is more than this many hours old counts as overdue. */
    private const BACKUP_MAX_AGE_HOURS = 26;

    public static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * True for /health with the configured token (?token=… or "Authorization: Bearer …"). Anything else is handled
     * like any other address — a missing token and a wrong one both end on the site's normal 404 page.
     */
    public static function authorized(App $app, Request $request): bool
    {
        if ($request->path() !== '/health') {
            return false;
        }
        $expected = $app->config->string('ops.health_token');
        $given = $request->query('token');
        if ($given === '') {
            $auth = (string) $request->header('Authorization');
            $given = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : '';
        }
        return strlen($expected) >= self::MIN_TOKEN_LENGTH && hash_equals($expected, $given);
    }

    public static function handle(App $app): Response
    {
        $app->noStore();
        $report = self::report($app);
        return Response::json($report, $report['status'] === 'fail' ? 503 : 200)->withHeader('X-Robots-Tag', 'noindex');
    }

    /** @return array{status: string, time: string, checks: array<string, array<string, mixed>>} */
    public static function report(App $app): array
    {
        $checks = [];
        $now = $app->clock->now();
        try {
            $start = microtime(true);
            $app->db()->scalar('SELECT 1');
            $checks['database'] = ['status' => 'ok', 'ms' => (int) round((microtime(true) - $start) * 1000)];
        } catch (\Throwable) {
            $checks['database'] = ['status' => 'fail', 'message' => 'The database cannot be reached.'];
        }

        $unwritable = [];
        foreach (['sessions', 'logs', 'cache'] as $dir) {
            if (!is_dir(Paths::storage($dir)) || !is_writable(Paths::storage($dir))) {
                $unwritable[] = 'storage/' . $dir;
            }
        }
        if (!is_writable(Paths::publicDir('uploads'))) {
            $unwritable[] = 'uploads';
        }
        $checks['storage'] = $unwritable === [] ? ['status' => 'ok'] : ['status' => 'fail', 'message' => 'Not writable: ' . implode(', ', $unwritable)];

        if ($checks['database']['status'] === 'ok') {
            try {
                $db = $app->db();
                $pending = (int) $db->scalar("SELECT COUNT(*) FROM {mail_queue} WHERE `status` = 'pending'");
                $oldest = $db->scalar("SELECT MIN(`created_at`) FROM {mail_queue} WHERE `status` = 'pending'");
                $failed = (int) $db->scalar("SELECT COUNT(*) FROM {mail_queue} WHERE `status` = 'failed' AND `created_at` >= :since", ['since' => $now->modify('-1 day')->format('Y-m-d H:i:s')]);
                $ageMinutes = is_string($oldest) ? (int) floor(($now->getTimestamp() - (int) strtotime($oldest . ' UTC')) / 60) : 0;
                $checks['mail_queue'] = [
                    'status' => $failed > 0 || $ageMinutes > 60 ? 'warn' : 'ok',
                    'pending' => $pending,
                    'oldest_pending_minutes' => $ageMinutes,
                    'failed_last_24h' => $failed,
                ];

                $settings = $app->settings();
                $provider = $settings->string('reviews.provider', 'manual');
                $interval = $settings->int('reviews.sync_interval_hours', 24);
                $lastSync = $settings->string('reviews.last_sync_at');
                $syncOverdue = $provider !== 'manual' && $interval > 0
                    && ($lastSync === '' || strtotime($lastSync . ' UTC') < $now->getTimestamp() - 2 * $interval * 3600);
                $checks['review_sync'] = [
                    'status' => $settings->string('reviews.last_error') !== '' || $syncOverdue ? 'warn' : 'ok',
                    'provider' => $provider,
                    'last_sync' => $lastSync === '' ? null : $lastSync,
                    'last_error' => $settings->string('reviews.last_error') === '' ? null : 'see the reviews screen',
                ];

                $lastBackup = $settings->string('backup.last_at');
                $backupAge = $lastBackup === '' ? null : (int) floor(($now->getTimestamp() - (int) strtotime($lastBackup . ' UTC')) / 3600);
                $checks['backup'] = [
                    'status' => $backupAge === null || $backupAge > self::BACKUP_MAX_AGE_HOURS || $settings->string('backup.last_error') !== '' ? 'warn' : 'ok',
                    'last_backup' => $lastBackup === '' ? null : $lastBackup,
                    'age_hours' => $backupAge,
                ];

                // Only once the scheduler address has been used: then a silent day means the cron service stopped.
                $lastRun = $settings->string(Scheduler::LAST_RUN_SETTING);
                if ($lastRun !== '') {
                    $runAge = (int) floor(($now->getTimestamp() - (int) strtotime($lastRun . ' UTC')) / 3600);
                    $checks['scheduler'] = ['status' => $runAge > 24 ? 'warn' : 'ok', 'last_run' => $lastRun, 'age_hours' => $runAge];
                }
            } catch (\Throwable) {
                $checks['database'] = ['status' => 'fail', 'message' => 'The database answers but a table could not be read.'];
            }
        }

        $states = array_column($checks, 'status');
        $status = in_array('fail', $states, true) ? 'fail' : (in_array('warn', $states, true) ? 'warn' : 'ok');
        return ['status' => $status, 'time' => $now->format('Y-m-d\TH:i:s\Z'), 'checks' => $checks];
    }
}
