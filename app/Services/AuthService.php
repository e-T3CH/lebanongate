<?php

declare(strict_types=1);

namespace BMMatic\Services;

use BMMatic\Core\Clock;
use BMMatic\Repositories\UserRepository;
use BMMatic\Security\Csrf;
use BMMatic\Security\IpAddress;
use BMMatic\Security\LoginThrottle;
use BMMatic\Security\PasswordHasher;
use BMMatic\Security\RateLimiter;
use BMMatic\Security\Session;
use BMMatic\Security\TwoFactor;

/**
 * Admin authentication: password check with login protection, optional TOTP second step, session lifecycle.
 * Every failure returns the same generic result so accounts cannot be enumerated.
 *
 * @phpstan-import-type UserRow from UserRepository
 */
final class AuthService
{
    public const RESULT_OK = 'ok';
    public const RESULT_TWO_FACTOR = 'two_factor';
    public const RESULT_FAILED = 'failed';

    private const SESSION_USER = 'auth_user_id';
    private const SESSION_AUTH_AT = 'auth_at';
    private const SESSION_ACTIVITY = 'auth_last_activity';
    private const SESSION_PENDING = 'auth_pending_2fa';
    private const SESSION_EPOCH = 'auth_epoch';
    private const PENDING_TTL = 300;
    private const TWO_FACTOR_MAX = 5;
    private const TWO_FACTOR_WINDOW = 900;

    /** @var UserRow|null */
    private ?array $user = null;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly LoginThrottle $throttle,
        private readonly RateLimiter $limiter,
        private readonly TwoFactor $twoFactor,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly AuditLog $audit,
        private readonly Clock $clock,
    ) {
    }

    public function attempt(string $email, #[\SensitiveParameter] string $password, string $ip): string
    {
        $email = UserRepository::normalizeEmail($email);
        if ($email === '' || $password === '') {
            return self::RESULT_FAILED;
        }
        if ($this->throttle->isLocked($email, $ip)) {
            $this->hasher->verifyDummy($password);
            $this->audit->record(AuditLog::LOGIN_LOCKED, null, ['email' => mb_substr($email, 0, 190)]);
            return self::RESULT_FAILED;
        }
        $user = $this->users->findByEmail($email);
        $valid = false;
        if ($user !== null) {
            $valid = $this->hasher->verify($password, $user['password_hash']) && $user['is_active'];
        } else {
            $this->hasher->verifyDummy($password);
        }
        if (!$valid || $user === null) {
            $this->throttle->recordFailure($email, $ip);
            $this->audit->record(AuditLog::LOGIN_FAILED, $user['id'] ?? null, ['email' => mb_substr($email, 0, 190)]);
            return self::RESULT_FAILED;
        }
        $this->throttle->clearAccount($email);
        if ($this->hasher->needsRehash($user['password_hash'])) {
            $this->users->updatePasswordHash($user['id'], $this->hasher->hash($password));
        }
        if ($user['totp_secret'] !== null) {
            $this->session->regenerate();
            $this->session->set(self::SESSION_PENDING, ['user_id' => $user['id'], 'at' => $this->clock->now()->getTimestamp()]);
            return self::RESULT_TWO_FACTOR;
        }
        $this->completeLogin($user, $ip, false);
        return self::RESULT_OK;
    }

    /** @phpstan-impure reads the session and the clock */
    public function hasPendingTwoFactor(): bool
    {
        return $this->pendingUserId() !== null;
    }

    /** Verifies a TOTP code or a recovery code for the pending login. */
    public function verifyTwoFactor(#[\SensitiveParameter] string $input, string $ip): bool
    {
        $userId = $this->pendingUserId();
        $user = $userId === null ? null : $this->users->find($userId);
        if ($user === null || $user['totp_secret'] === null || !$user['is_active']) {
            $this->session->remove(self::SESSION_PENDING);
            return false;
        }
        $key = '2fa:user:' . $user['id'];
        if ($this->limiter->tooManyAttempts($key, self::TWO_FACTOR_MAX)) {
            $this->audit->record(AuditLog::TWO_FACTOR_LOCKED, $user['id']);
            return false;
        }
        $input = trim($input);
        if (TwoFactor::looksLikeRecoveryCode($input)) {
            $remaining = $this->twoFactor->consumeRecoveryCode($user['recovery_codes'], $input);
            if ($remaining !== null) {
                $this->users->setRecoveryCodes($user['id'], $remaining);
                $this->limiter->clear($key);
                $this->audit->record(AuditLog::RECOVERY_CODE_USED, $user['id'], ['remaining' => count($remaining)]);
                $this->completeLogin($user, $ip, true);
                return true;
            }
        } else {
            $step = $this->twoFactor->verify($this->twoFactor->decryptSecret($user['totp_secret']), $input, $user['totp_last_timestep']);
            if ($step !== null) {
                $this->users->setTotpTimestep($user['id'], $step);
                $this->limiter->clear($key);
                $this->audit->record(AuditLog::TWO_FACTOR_PASSED, $user['id']);
                $this->completeLogin($user, $ip, true);
                return true;
            }
        }
        $this->limiter->hit($key, self::TWO_FACTOR_WINDOW);
        $this->audit->record(AuditLog::TWO_FACTOR_FAILED, $user['id']);
        return false;
    }

    public function cancelTwoFactor(): void
    {
        $this->session->remove(self::SESSION_PENDING);
    }

    /** @return UserRow|null */
    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }
        $id = $this->session->get(self::SESSION_USER);
        if (!is_int($id)) {
            return null;
        }
        $user = $this->users->find($id);
        // A password reset (or any "end all sessions") increments the epoch and signs this session out.
        if ($user === null || !$user['is_active'] || $this->session->get(self::SESSION_EPOCH) !== $user['session_epoch']) {
            $this->session->invalidate();
            return null;
        }
        return $this->user = $user;
    }

    public function refreshUser(): void
    {
        $this->user = null;
    }

    /**
     * Idle timeout: true when the signed-in session has been inactive for longer than $minutes
     * (the session is then ended). Otherwise the activity timestamp is refreshed.
     */
    public function expireIfIdle(int $minutes): bool
    {
        $userId = $this->session->get(self::SESSION_USER);
        if (!is_int($userId)) {
            return false;
        }
        $last = $this->session->get(self::SESSION_ACTIVITY);
        $now = $this->clock->now()->getTimestamp();
        if (is_int($last) && $now - $last > max(1, $minutes) * 60) {
            $this->audit->record(AuditLog::SESSION_EXPIRED, $userId);
            $this->session->invalidate();
            $this->csrf->rotate();
            $this->user = null;
            return true;
        }
        $this->session->set(self::SESSION_ACTIVITY, $now);
        return false;
    }

    public function logout(): void
    {
        $user = $this->user();
        if ($user !== null) {
            $this->audit->record(AuditLog::LOGOUT, $user['id']);
        }
        $this->session->invalidate();
        $this->csrf->rotate();
        $this->user = null;
    }

    /** Re-checks the password of the signed-in user (sensitive actions such as 2FA changes). */
    public function confirmPassword(#[\SensitiveParameter] string $password): bool
    {
        $user = $this->user();
        return $user !== null && $password !== '' && $this->hasher->verify($password, $user['password_hash']);
    }

    /**
     * Keeps this session valid after "end all sessions" (changing your own password signs out the other devices,
     * not the one you are working on). Call it right after the epoch was raised.
     */
    public function keepCurrentSession(): void
    {
        $id = $this->session->get(self::SESSION_USER);
        $this->user = null;
        if (!is_int($id)) {
            return;
        }
        $user = $this->users->find($id);
        if ($user !== null) {
            $this->session->set(self::SESSION_EPOCH, $user['session_epoch']);
            $this->session->regenerate();
        }
    }

    /** @param UserRow $user */
    private function completeLogin(array $user, string $ip, bool $viaTwoFactor): void
    {
        $this->session->remove(self::SESSION_PENDING);
        $this->session->regenerate();
        $now = $this->clock->now()->getTimestamp();
        $this->session->set(self::SESSION_USER, $user['id']);
        $this->session->set(self::SESSION_AUTH_AT, $now);
        $this->session->set(self::SESSION_ACTIVITY, $now);
        $this->session->set(self::SESSION_EPOCH, $user['session_epoch']);
        $this->csrf->rotate();
        $this->users->recordLogin($user['id'], IpAddress::toBinary($ip));
        $this->audit->record(AuditLog::LOGIN_SUCCESS, $user['id'], ['two_factor' => $viaTwoFactor]);
        $this->user = null;
    }

    private function pendingUserId(): ?int
    {
        $pending = $this->session->get(self::SESSION_PENDING);
        if (!is_array($pending) || !is_int($pending['user_id'] ?? null) || !is_int($pending['at'] ?? null)) {
            return null;
        }
        if ($this->clock->now()->getTimestamp() - $pending['at'] > self::PENDING_TTL) {
            $this->session->remove(self::SESSION_PENDING);
            return null;
        }
        return $pending['user_id'];
    }
}
