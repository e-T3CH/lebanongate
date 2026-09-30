<?php

declare(strict_types=1);

namespace BMMatic\Console;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;
use BMMatic\Install\Installer;
use BMMatic\Repositories\UserRepository;
use BMMatic\Security\LoginThrottle;
use BMMatic\Security\PasswordHasher;
use BMMatic\Security\RateLimiter;
use BMMatic\Services\AuditLog;
use BMMatic\Services\Settings;

/**
 * Emergency recovery actions for the site owner (command line only, every action audit-logged with via=cli).
 */
final class Recovery
{
    private readonly UserRepository $users;
    private readonly AuditLog $audit;

    public function __construct(
        private readonly Database $db,
        private readonly Settings $settings,
        private readonly Clock $clock,
        private readonly string $siteUrl,
    ) {
        $this->users = new UserRepository($db, $clock);
        $this->audit = new AuditLog($db, $clock);
        $this->audit->setRequestContext('', 'cli');
    }

    public function adminLoginUrl(): string
    {
        $this->audit->record(AuditLog::ADMIN_PATH_SHOWN, null, ['via' => 'cli']);
        return $this->loginUrl($this->settings->string('security.admin_path', 'admin'));
    }

    /** Replaces the admin path with a new random one and returns the new sign-in URL. */
    public function regenerateAdminPath(): string
    {
        $path = 'admin-' . Installer::randomSlug(8);
        $this->settings->set('security.admin_path', $path);
        $this->audit->record(AuditLog::ADMIN_PATH_REGENERATED, null, ['via' => 'cli']);
        return $this->loginUrl($path);
    }

    /** Disables 2FA and deletes the recovery codes. Returns false when the user does not exist. */
    public function resetTwoFactor(string $email): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return false;
        }
        $this->users->disableTwoFactor($user['id']);
        $this->audit->record(AuditLog::TWO_FACTOR_DISABLED, $user['id'], ['via' => 'cli']);
        return true;
    }

    /**
     * Sets a new password (policy-checked, Argon2id), ends all sessions of that user and clears the account lockout.
     *
     * @return list<string> translation keys of policy violations; ['user_not_found'] when the user does not exist
     */
    public function resetPassword(string $email, #[\SensitiveParameter] string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return ['user_not_found'];
        }
        $hasher = new PasswordHasher();
        $errors = $hasher->policyErrors($password, $user['email']);
        if ($errors !== []) {
            return $errors;
        }
        $this->db->transaction(function () use ($user, $hasher, $password): void {
            $this->users->updatePasswordHash($user['id'], $hasher->hash($password));
            $this->users->endAllSessions($user['id']);
        });
        (new LoginThrottle(new RateLimiter($this->db, $this->clock), 1, 1, 1))->clearAccount($user['email']);
        $this->audit->record(AuditLog::PASSWORD_RESET, $user['id'], ['via' => 'cli', 'sessions_ended' => true]);
        return [];
    }

    public function unlock(string $email, string $ip): void
    {
        (new LoginThrottle(new RateLimiter($this->db, $this->clock), 1, 1, 1))->clearAll($email, $ip);
        $this->audit->record(AuditLog::LOCKOUT_CLEARED, null, ['via' => 'cli', 'email' => mb_substr(UserRepository::normalizeEmail($email), 0, 190)]);
    }

    private function loginUrl(string $path): string
    {
        return rtrim($this->siteUrl, '/') . '/' . trim($path, '/') . '/login';
    }
}
