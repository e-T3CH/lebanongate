<?php

declare(strict_types=1);

namespace BMMatic\Security;

/**
 * Login protection: failed attempts are counted per account and per IP inside a lockout window.
 * Reaching either limit locks that account/IP until the window ends. Limits come from settings.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly int $maxPerAccount,
        private readonly int $maxPerIp,
        private readonly int $lockoutMinutes,
    ) {
    }

    public function isLocked(string $email, string $ip): bool
    {
        return $this->limiter->tooManyAttempts(self::accountKey($email), max(1, $this->maxPerAccount))
            || $this->limiter->tooManyAttempts(self::ipKey($ip), max(1, $this->maxPerIp));
    }

    public function recordFailure(string $email, string $ip): void
    {
        $window = max(1, $this->lockoutMinutes) * 60;
        $this->limiter->hit(self::accountKey($email), $window);
        $this->limiter->hit(self::ipKey($ip), $window);
    }

    /** Called after a successful password check. The IP counter is kept (it may be shared by an attacker). */
    public function clearAccount(string $email): void
    {
        $this->limiter->clear(self::accountKey($email));
    }

    public function clearAll(string $email, string $ip): void
    {
        $this->limiter->clear(self::accountKey($email));
        $this->limiter->clear(self::ipKey($ip));
    }

    public function secondsUntilUnlocked(string $email, string $ip): int
    {
        return max($this->limiter->availableIn(self::accountKey($email)), $this->limiter->availableIn(self::ipKey($ip)));
    }

    private static function accountKey(string $email): string
    {
        return 'login:account:' . mb_strtolower(trim($email));
    }

    private static function ipKey(string $ip): string
    {
        return 'login:ip:' . $ip;
    }
}
