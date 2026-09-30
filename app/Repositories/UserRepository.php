<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * @phpstan-type UserRow array{id: int, email: string, name: string, password_hash: string, role: string, is_active: bool,
 *   totp_secret: ?string, totp_enabled_at: ?string, totp_last_timestep: ?int, recovery_codes: list<string>,
 *   last_login_at: ?string, session_epoch: int}
 */
final class UserRepository
{
    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /** @return UserRow|null */
    public function find(int $id): ?array
    {
        $row = $this->db->first('users', ['id' => $id]);
        return $row === null ? null : self::map($row);
    }

    /** @return UserRow|null */
    public function findByEmail(string $email): ?array
    {
        $row = $this->db->first('users', ['email' => self::normalizeEmail($email)]);
        return $row === null ? null : self::map($row);
    }

    public function count(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {users}');
    }

    public function create(string $email, string $name, string $passwordHash, string $role): int
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            throw new \InvalidArgumentException('Unknown role: ' . $role);
        }
        $now = $this->now();
        return $this->db->insert('users', [
            'email' => self::normalizeEmail($email),
            'name' => $name,
            'password_hash' => $passwordHash,
            'role' => $role,
            'is_active' => 1,
            'password_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->db->update('users', ['password_hash' => $hash, 'password_changed_at' => $this->now(), 'updated_at' => $this->now()], ['id' => $id]);
    }

    /** Ends every existing session of the user (they are signed out on their next request). */
    public function endAllSessions(int $id): void
    {
        $this->db->run('UPDATE {users} SET `session_epoch` = `session_epoch` + 1, `updated_at` = :now WHERE `id` = :id', ['now' => $this->now(), 'id' => $id]);
    }

    public function recordLogin(int $id, ?string $ipBinary): void
    {
        $this->db->update('users', ['last_login_at' => $this->now(), 'last_login_ip' => $ipBinary], ['id' => $id]);
    }

    /** @param list<string> $recoveryHashes */
    public function enableTwoFactor(int $id, string $encryptedSecret, int $timestep, array $recoveryHashes): void
    {
        $this->db->update('users', [
            'totp_secret' => $encryptedSecret,
            'totp_enabled_at' => $this->now(),
            'totp_last_timestep' => $timestep,
            'recovery_codes' => json_encode($recoveryHashes, JSON_THROW_ON_ERROR),
            'updated_at' => $this->now(),
        ], ['id' => $id]);
    }

    public function disableTwoFactor(int $id): void
    {
        $this->db->update('users', [
            'totp_secret' => null,
            'totp_enabled_at' => null,
            'totp_last_timestep' => null,
            'recovery_codes' => null,
            'updated_at' => $this->now(),
        ], ['id' => $id]);
    }

    public function setTotpTimestep(int $id, int $timestep): void
    {
        $this->db->update('users', ['totp_last_timestep' => $timestep], ['id' => $id]);
    }

    /** @param list<string> $hashes */
    public function setRecoveryCodes(int $id, array $hashes): void
    {
        $this->db->update('users', ['recovery_codes' => json_encode(array_values($hashes), JSON_THROW_ON_ERROR), 'updated_at' => $this->now()], ['id' => $id]);
    }

    /**
     * Every user for the Users screen, newest sign-in first.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->db->all('SELECT `id`, `email`, `name`, `role`, `is_active`, `totp_enabled_at`, `last_login_at`, `created_at` FROM {users} ORDER BY `is_active` DESC, `name`');
    }

    public function updateProfile(int $id, string $name, string $role, bool $active): void
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            throw new \InvalidArgumentException('Unknown role: ' . $role);
        }
        $this->db->update('users', ['name' => $name, 'role' => $role, 'is_active' => $active ? 1 : 0, 'updated_at' => $this->now()], ['id' => $id]);
    }

    public function updateName(int $id, string $name): void
    {
        $this->db->update('users', ['name' => $name, 'updated_at' => $this->now()], ['id' => $id]);
    }

    public function updateEmail(int $id, string $email): void
    {
        $this->db->update('users', ['email' => self::normalizeEmail($email), 'updated_at' => $this->now()], ['id' => $id]);
    }

    /** Active administrators; used to refuse deactivating or demoting the last one. */
    public function countActiveAdmins(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM {users} WHERE `role` = 'admin' AND `is_active` = 1");
    }

    /**
     * True when this user is the only active administrator left, so removing their access would lock everyone out.
     */
    public function isLastActiveAdmin(int $id): bool
    {
        $user = $this->find($id);
        if ($user === null || $user['role'] !== 'admin' || !$user['is_active']) {
            return false;
        }
        return $this->countActiveAdmins() <= 1;
    }

    /** A user who appears in the audit log is never deleted, only deactivated. */
    public function appearsInAuditLog(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM {audit_log} WHERE `user_id` = :id LIMIT 1', ['id' => $id]) !== null;
    }

    public function countAdminsWithoutTwoFactor(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM {users} WHERE `role` = 'admin' AND `is_active` = 1 AND `totp_enabled_at` IS NULL");
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * @param array<string, mixed> $row
     * @return UserRow
     */
    private static function map(array $row): array
    {
        $codes = is_string($row['recovery_codes'] ?? null) ? json_decode($row['recovery_codes'], true) : [];
        return [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'name' => (string) $row['name'],
            'password_hash' => (string) $row['password_hash'],
            'role' => (string) $row['role'],
            'is_active' => (int) $row['is_active'] === 1,
            'totp_secret' => is_string($row['totp_secret'] ?? null) ? $row['totp_secret'] : null,
            'totp_enabled_at' => is_string($row['totp_enabled_at'] ?? null) ? $row['totp_enabled_at'] : null,
            'totp_last_timestep' => isset($row['totp_last_timestep']) ? (int) $row['totp_last_timestep'] : null,
            'recovery_codes' => is_array($codes) ? array_values(array_filter($codes, 'is_string')) : [],
            'last_login_at' => is_string($row['last_login_at'] ?? null) ? $row['last_login_at'] : null,
            'session_epoch' => (int) ($row['session_epoch'] ?? 0),
        ];
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
