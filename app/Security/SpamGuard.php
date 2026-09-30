<?php

declare(strict_types=1);

namespace BMMatic\Security;

use BMMatic\Core\Clock;

/**
 * Spam protection for public forms, on top of the CSRF token:
 *  - honeypot: a hidden "website" field that people never fill in
 *  - time trap: a signed timestamp rendered with the form; submissions faster than the minimum (bots) or older than
 *    the maximum (replayed pages) are refused. The signature (HMAC with the app key) prevents forging the time.
 *  - rate limit per IP address (hashed key) through RateLimiter.
 */
final class SpamGuard
{
    public const HONEYPOT = 'website';
    public const TIMESTAMP = 'form_ts';
    public const MAX_AGE_SECONDS = 7200;

    public function __construct(
        private readonly Crypto $crypto,
        private readonly RateLimiter $limiter,
        private readonly Clock $clock,
        private readonly int $minSeconds = 3,
        private readonly int $maxPerHour = 5,
    ) {
    }

    /** Value for the hidden timestamp field. */
    public function token(string $form): string
    {
        $time = (string) $this->clock->now()->getTimestamp();
        return $time . '.' . substr($this->crypto->hmac($form . '|' . $time), 0, 32);
    }

    /**
     * @param array{honeypot: string, token: string} $input
     * @return 'ok'|'honeypot'|'too_fast'|'expired'|'invalid'|'rate_limited'
     */
    public function check(string $form, array $input, string $ip): string
    {
        if (trim($input['honeypot']) !== '') {
            return 'honeypot';
        }
        if (preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $input['token'], $m) !== 1 || !hash_equals(substr($this->crypto->hmac($form . '|' . $m[1]), 0, 32), $m[2])) {
            return 'invalid';
        }
        $age = $this->clock->now()->getTimestamp() - (int) $m[1];
        if ($age < $this->minSeconds) {
            return 'too_fast';
        }
        if ($age > self::MAX_AGE_SECONDS) {
            return 'expired';
        }
        $key = 'form:' . $form . ':' . $ip;
        if ($this->limiter->tooManyAttempts($key, $this->maxPerHour)) {
            return 'rate_limited';
        }
        return 'ok';
    }

    /** Counts an accepted submission against the per-IP hourly limit. */
    public function recordSubmission(string $form, string $ip): void
    {
        $this->limiter->hit('form:' . $form . ':' . $ip, 3600);
    }
}
