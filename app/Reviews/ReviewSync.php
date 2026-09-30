<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

use BMMatic\Core\Clock;
use BMMatic\Repositories\ReviewRepository;
use BMMatic\Security\RateLimiter;
use BMMatic\Services\Settings;

/**
 * One sync run: ask the active provider, store what changed, and write a line in the sync log.
 *
 * Rules that make a sync safe to run from cron, by hand and twice at once:
 *  - idempotent: an unchanged review is left alone, a changed one is updated, and nothing is duplicated;
 *  - `is_visible` is never written by a sync — that choice belongs to the workshop;
 *  - a review Google no longer returns is marked, never deleted;
 *  - a lock (through the rate limiter table) keeps two runs from overlapping;
 *  - an API error backs the next attempt off (5 min, 15, 45, … up to 6 hours) instead of hammering Google;
 *  - the aggregate rating and count are stored exactly as Google reports them, never recalculated.
 */
final class ReviewSync
{
    public const LOCK_KEY = 'reviews:sync';
    private const LOCK_SECONDS = 600;
    private const BACKOFF_MINUTES = [5, 15, 45, 120, 360];

    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly Settings $settings,
        private readonly RateLimiter $limiter,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @return array{status: string, added: int, updated: int, removed: int, message: string, reason: ?string}
     */
    public function run(ReviewProvider $provider, bool $dryRun = false, bool $force = false): array
    {
        $now = $this->clock->now();
        if (!$force && !$dryRun) {
            $waitUntil = $this->settings->string('reviews.retry_after');
            if ($waitUntil !== '' && $waitUntil > $now->format('Y-m-d H:i:s')) {
                return self::result('skipped', 0, 0, 0, 'Waiting until ' . $waitUntil . ' after an earlier error.');
            }
        }
        if (!$dryRun && !$this->lock()) {
            return self::result('skipped', 0, 0, 0, 'Another sync is already running.');
        }
        $logId = $this->reviews->startLog($provider->key(), $dryRun);
        try {
            $result = $provider->fetch();
            if ($result->failed()) {
                $this->afterFailure($result->errorReason ?? 'error', $result->errorMessage);
                $this->reviews->finishLog($logId, 'error', ['added' => 0, 'updated' => 0, 'removed' => 0], $result->errorMessage);
                return self::result('error', 0, 0, 0, $result->errorMessage, $result->errorReason);
            }
            $visibleWhenNew = $this->settings->string('reviews.new_visibility', 'hidden') === 'visible';
            $added = 0;
            $updated = 0;
            $ids = [];
            foreach ($result->reviews as $review) {
                $ids[] = $review->externalId;
                if ($dryRun) {
                    $added += $this->reviews->existsByExternalId($review->externalId) ? 0 : 1;
                    continue;
                }
                $outcome = $this->reviews->upsert($review, $provider->key(), $visibleWhenNew);
                $added += $outcome === 'added' ? 1 : 0;
                $updated += $outcome === 'updated' ? 1 : 0;
            }
            // Places hands over five reviews at a time: the ones it leaves out are not gone, so nothing is marked.
            $removed = !$dryRun && !$result->partial ? $this->reviews->markMissingAsDeleted($ids, $provider->key()) : 0;
            if (!$dryRun) {
                $this->storeAggregate($result);
                $this->settings->set('reviews.last_sync_at', $now->format('Y-m-d H:i:s'));
                $this->settings->set('reviews.last_error', '');
                $this->settings->set('reviews.retry_after', '');
                $this->settings->set('reviews.failures', 0, 'int');
            }
            $this->reviews->finishLog($logId, 'ok', ['added' => $added, 'updated' => $updated, 'removed' => $removed], $dryRun ? 'Dry run: nothing was stored.' : '');
            return self::result('ok', $added, $updated, $removed, $dryRun ? 'Dry run: nothing was stored.' : '');
        } finally {
            if (!$dryRun) {
                $this->limiter->clear(self::LOCK_KEY);
            }
        }
    }

    /** When the next automatic sync is due (empty when automatic syncing is off). */
    public function nextSyncAt(): string
    {
        $hours = $this->settings->int('reviews.sync_interval_hours', 24);
        if ($hours <= 0) {
            return '';
        }
        $retry = $this->settings->string('reviews.retry_after');
        if ($retry !== '') {
            return $retry;
        }
        $last = $this->settings->string('reviews.last_sync_at');
        $from = $last === '' ? $this->clock->now() : new \DateTimeImmutable($last, new \DateTimeZone('UTC'));
        return $from->modify('+' . $hours . ' hours')->format('Y-m-d H:i:s');
    }

    /** True when the cron job should run a sync now. */
    public function isDue(): bool
    {
        $hours = $this->settings->int('reviews.sync_interval_hours', 24);
        if ($hours <= 0) {
            return false;
        }
        $next = $this->nextSyncAt();
        return $next === '' || $next <= $this->clock->now()->format('Y-m-d H:i:s');
    }

    private function storeAggregate(ProviderResult $result): void
    {
        // Exactly what Google reports for the location — never the average of the reviews we happen to show.
        if ($result->aggregateRating !== null && $result->aggregateRating > 0) {
            $this->settings->set('reviews.rating', number_format($result->aggregateRating, 1, '.', ''));
        }
        if ($result->aggregateCount !== null && $result->aggregateCount >= 0) {
            $this->settings->set('reviews.count', (string) $result->aggregateCount);
        }
    }

    private function afterFailure(string $reason, string $message): void
    {
        $failures = $this->settings->int('reviews.failures', 0) + 1;
        $minutes = self::BACKOFF_MINUTES[min($failures, count(self::BACKOFF_MINUTES)) - 1];
        $this->settings->set('reviews.failures', $failures, 'int');
        $this->settings->set('reviews.last_error', $reason . ': ' . mb_substr($message, 0, 300));
        $this->settings->set('reviews.retry_after', $this->clock->now()->modify('+' . $minutes . ' minutes')->format('Y-m-d H:i:s'));
    }

    private function lock(): bool
    {
        return $this->limiter->hit(self::LOCK_KEY, self::LOCK_SECONDS) === 1;
    }

    /**
     * @return array{status: string, added: int, updated: int, removed: int, message: string, reason: ?string}
     */
    private static function result(string $status, int $added, int $updated, int $removed, string $message, ?string $reason = null): array
    {
        return ['status' => $status, 'added' => $added, 'updated' => $updated, 'removed' => $removed, 'message' => $message, 'reason' => $reason];
    }
}
