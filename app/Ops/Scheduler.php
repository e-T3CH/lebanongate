<?php

declare(strict_types=1);

namespace Gate\Ops;

use Gate\Core\Clock;
use Gate\Core\Config;
use Gate\Core\Database;
use Gate\Http\Request;
use Gate\Mail\MailQueue;
use Gate\Mail\MailWorker;
use Gate\Mail\SmtpTransport;
use Gate\Repositories\ReviewRepository;
use Gate\Reviews\ReviewPhotos;
use Gate\Reviews\ReviewProviders;
use Gate\Reviews\ReviewSync;
use Gate\Security\RateLimiter;
use Gate\Services\AuditLog;
use Gate\Services\Settings;

/**
 * The scheduled work in one place: deliver waiting emails, import the Google reviews when due, and make the daily
 * backup when the last one is 23 hours old.
 *
 * Two ways to start it, both safe to call as often as every few minutes (work that is not due is skipped):
 *  - `php bin/console schedule:run` from a cron job, on hosts that have one;
 *  - the scheduler address `/cron/<token>`, for hosts without cron or SSH (one.com): a free service such as
 *    cron-job.org opens it every 15 minutes. The token is a secret setting; a wrong token is an ordinary 404.
 */
final class Scheduler
{
    public const TOKEN_SETTING = 'ops.cron_token';
    public const LAST_RUN_SETTING = 'ops.cron_last_at';
    public const LAST_REPORT_SETTING = 'ops.cron_last_report';
    /** A daily backup is made once the newest one is this old. */
    public const BACKUP_EVERY_HOURS = 23;
    private const MIN_TOKEN_LENGTH = 32;
    private const LOCK = 'ops:cron';

    public function __construct(
        private readonly Config $config,
        private readonly Database $db,
        private readonly Settings $settings,
        private readonly Clock $clock,
    ) {
    }

    /** The token of the scheduler address; made on first use. */
    public static function token(Settings $settings): string
    {
        $token = $settings->string(self::TOKEN_SETTING);
        if (strlen($token) < self::MIN_TOKEN_LENGTH) {
            $token = self::regenerate($settings);
        }
        return $token;
    }

    /** A new token: the old scheduler address stops working at once. */
    public static function regenerate(Settings $settings): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $settings->set(self::TOKEN_SETTING, $token, 'string', true);
        return $token;
    }

    /** True for /cron/<the configured token>. Never creates a token. */
    public static function authorized(Settings $settings, Request $request): bool
    {
        $path = $request->path();
        if (!str_starts_with($path, '/cron/')) {
            return false;
        }
        $expected = $settings->string(self::TOKEN_SETTING);
        return strlen($expected) >= self::MIN_TOKEN_LENGTH && hash_equals($expected, substr($path, 6));
    }

    /** True while another run holds the lock (a run that died releases it after 15 minutes). */
    public function isRunning(): bool
    {
        return (new RateLimiter($this->db, $this->clock))->attempts(self::LOCK) > 0;
    }

    /**
     * Runs what is due. $forceBackup makes a backup even when the last one is recent ("Run now" does not; the
     * Maintenance screen has its own button for a backup).
     *
     * @return array{status: string, mail: array{sent: int, failed: int}|null, reviews: string, backup: string}
     */
    public function run(?AuditLog $audit = null, bool $forceBackup = false): array
    {
        $limiter = new RateLimiter($this->db, $this->clock);
        if ($limiter->hit(self::LOCK, 900) !== 1) {
            return ['status' => 'busy', 'mail' => null, 'reviews' => 'skipped', 'backup' => 'skipped'];
        }
        @set_time_limit(300);
        try {
            $report = [
                'status' => 'ok',
                'mail' => $this->mail(),
                'reviews' => $this->reviews(),
                'backup' => $this->backup($limiter, $forceBackup),
            ];
            if (str_starts_with($report['reviews'], 'error') || str_starts_with($report['backup'], 'error') || ($report['mail']['failed'] ?? 0) > 0) {
                $report['status'] = 'warn';
            }
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $this->settings->set(self::LAST_RUN_SETTING, $now, 'string');
            $this->settings->set(self::LAST_REPORT_SETTING, $report, 'json');
            $audit?->record(AuditLog::SCHEDULER_RUN, null, ['reviews' => $report['reviews'], 'backup' => $report['backup'], 'mail_sent' => $report['mail']['sent'] ?? 0]);
            return $report;
        } finally {
            $limiter->clear(self::LOCK);
        }
    }

    /** @return array{sent: int, failed: int}|null null when email is not set up */
    private function mail(): ?array
    {
        $transport = SmtpTransport::fromSettings($this->settings);
        if (!$transport->isConfigured()) {
            return null;
        }
        try {
            return (new MailWorker(new MailQueue($this->db, $this->clock), $transport))->run(50, 60.0);
        } catch (\Throwable) {
            return ['sent' => 0, 'failed' => 1];
        }
    }

    private function reviews(): string
    {
        $reviews = new ReviewRepository($this->db, $this->clock);
        $sync = new ReviewSync($reviews, $this->settings, new RateLimiter($this->db, $this->clock), $this->clock);
        if (!$sync->isDue()) {
            return 'not due';
        }
        try {
            $result = $sync->run((new ReviewProviders($this->settings))->active());
            if ($result['status'] === 'ok' && $this->settings->bool('reviews.show_photos')) {
                (new ReviewPhotos())->warm($reviews->visible(24, $this->settings->string('reviews.display_order', 'newest')));
            }
            return $result['status'] === 'error' ? 'error: ' . mb_substr($result['message'], 0, 200) : $result['status'];
        } catch (\Throwable $e) {
            return 'error: ' . mb_substr($e->getMessage(), 0, 200);
        }
    }

    private function backup(RateLimiter $limiter, bool $force): string
    {
        $last = $this->settings->string('backup.last_at');
        $due = $force || $last === '' || (int) strtotime($last . ' UTC') <= $this->clock->now()->getTimestamp() - self::BACKUP_EVERY_HOURS * 3600;
        if (!$due) {
            return 'not due';
        }
        if ($limiter->hit('ops:backup', 3600) !== 1) {
            return 'skipped: another backup is running';
        }
        $backups = new Backups($this->config, $this->db, $this->settings, $this->clock);
        try {
            $result = $backups->run();
            return 'made ' . basename($result['file']);
        } catch (\Throwable $e) {
            $backups->recordFailure($e->getMessage());
            return 'error: ' . mb_substr($e->getMessage(), 0, 200);
        } finally {
            $limiter->clear('ops:backup');
        }
    }
}
