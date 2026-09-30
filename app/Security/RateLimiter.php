<?php

declare(strict_types=1);

namespace BMMatic\Security;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;

/**
 * Fixed-window counters stored in MySQL (works on shared hosting without Redis/APCu).
 * Keys are hashed, so no raw IP addresses or email addresses are stored.
 */
final class RateLimiter
{
    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /** Records one hit and returns the number of hits in the current window. */
    public function hit(string $key, int $windowSeconds): int
    {
        $now = $this->clock->now();
        $reset = $now->modify('+' . max(1, $windowSeconds) . ' seconds')->format('Y-m-d H:i:s');
        $nowText = $now->format('Y-m-d H:i:s');
        $this->db->run(
            'INSERT INTO {rate_limits} (`key`, `hits`, `reset_at`) VALUES (:k, 1, :reset)
             ON DUPLICATE KEY UPDATE
               `hits` = IF(`reset_at` <= :now1, 1, `hits` + 1),
               `reset_at` = IF(`reset_at` <= :now2, :reset2, `reset_at`)',
            ['k' => self::hashKey($key), 'reset' => $reset, 'now1' => $nowText, 'now2' => $nowText, 'reset2' => $reset]
        );
        return $this->attempts($key);
    }

    /** Hits in the current (unexpired) window. */
    public function attempts(string $key): int
    {
        $row = $this->db->one('SELECT `hits`, `reset_at` FROM {rate_limits} WHERE `key` = :k', ['k' => self::hashKey($key)]);
        if ($row === null || (string) $row['reset_at'] <= $this->clock->now()->format('Y-m-d H:i:s')) {
            return 0;
        }
        return (int) $row['hits'];
    }

    public function tooManyAttempts(string $key, int $max): bool
    {
        return $this->attempts($key) >= $max;
    }

    /** Seconds until the window resets (0 when there is no active window). */
    public function availableIn(string $key): int
    {
        $row = $this->db->one('SELECT `reset_at` FROM {rate_limits} WHERE `key` = :k', ['k' => self::hashKey($key)]);
        if ($row === null) {
            return 0;
        }
        $reset = new \DateTimeImmutable((string) $row['reset_at'], new \DateTimeZone('UTC'));
        return max(0, $reset->getTimestamp() - $this->clock->now()->getTimestamp());
    }

    public function clear(string $key): void
    {
        $this->db->delete('rate_limits', ['key' => self::hashKey($key)]);
    }

    /** Removes expired windows (called opportunistically). */
    public function prune(): int
    {
        return $this->db->delete('rate_limits', ['reset_at <' => $this->clock->now()->modify('-1 day')->format('Y-m-d H:i:s')]);
    }

    private static function hashKey(string $key): string
    {
        return hash('sha256', 'bm-rate:' . $key);
    }
}
