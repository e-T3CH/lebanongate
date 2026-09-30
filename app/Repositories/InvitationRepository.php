<?php

declare(strict_types=1);

namespace BMMatic\Repositories;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;
use BMMatic\Security\Crypto;

/**
 * Invitations to the admin panel and confirmations of an own email change. Both work the same way: a random token
 * goes out by email, only its HMAC is stored, and it expires. The token is shown once and never logged.
 */
final class InvitationRepository
{
    public const INVITE_TTL_HOURS = 72;
    public const EMAIL_CHANGE_TTL_HOURS = 2;

    public function __construct(private readonly Database $db, private readonly Crypto $crypto, private readonly Clock $clock)
    {
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function hash(string $token): string
    {
        return $this->crypto->hmac('token|' . $token);
    }

    /** @return array{id: int, token: string} */
    public function invite(string $email, string $name, string $role, ?int $invitedBy): array
    {
        $token = self::newToken();
        $this->db->run('DELETE FROM {user_invitations} WHERE `email` = :email AND `accepted_at` IS NULL', ['email' => UserRepository::normalizeEmail($email)]);
        $id = $this->db->insert('user_invitations', [
            'email' => UserRepository::normalizeEmail($email),
            'name' => $name,
            'role' => $role,
            'token_hash' => $this->hash($token),
            'invited_by' => $invitedBy,
            'expires_at' => $this->clock->now()->modify('+' . self::INVITE_TTL_HOURS . ' hours')->format('Y-m-d H:i:s'),
            'created_at' => $this->now(),
        ]);
        return ['id' => $id, 'token' => $token];
    }

    /** A new token for an existing invitation (the "resend" button). */
    public function refreshToken(int $id): ?string
    {
        $row = $this->db->first('user_invitations', ['id' => $id]);
        if ($row === null || $row['accepted_at'] !== null) {
            return null;
        }
        $token = self::newToken();
        $this->db->update('user_invitations', [
            'token_hash' => $this->hash($token),
            'expires_at' => $this->clock->now()->modify('+' . self::INVITE_TTL_HOURS . ' hours')->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        return $token;
    }

    /** @return array<string, mixed>|null the open, unexpired invitation for this token */
    public function findByToken(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return null;
        }
        $row = $this->db->first('user_invitations', ['token_hash' => $this->hash($token)]);
        if ($row === null || $row['accepted_at'] !== null || (string) $row['expires_at'] < $this->now()) {
            return null;
        }
        return $row;
    }

    public function markAccepted(int $id): void
    {
        $this->db->update('user_invitations', ['accepted_at' => $this->now()], ['id' => $id]);
    }

    public function cancel(int $id): int
    {
        return $this->db->delete('user_invitations', ['id' => $id, 'accepted_at' => null]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('user_invitations', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> open invitations, newest first */
    public function open(): array
    {
        return $this->db->all('SELECT * FROM {user_invitations} WHERE `accepted_at` IS NULL ORDER BY `created_at` DESC');
    }

    // ---------------------------------------------------------------------------------- email change confirmation

    public function requestEmailChange(int $userId, string $newEmail): string
    {
        $token = self::newToken();
        $this->db->run('DELETE FROM {email_changes} WHERE `user_id` = :id AND `confirmed_at` IS NULL', ['id' => $userId]);
        $this->db->insert('email_changes', [
            'user_id' => $userId,
            'new_email' => UserRepository::normalizeEmail($newEmail),
            'token_hash' => $this->hash($token),
            'expires_at' => $this->clock->now()->modify('+' . self::EMAIL_CHANGE_TTL_HOURS . ' hours')->format('Y-m-d H:i:s'),
            'created_at' => $this->now(),
        ]);
        return $token;
    }

    /** @return array<string, mixed>|null */
    public function findEmailChange(string $token, int $userId): ?array
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return null;
        }
        $row = $this->db->first('email_changes', ['token_hash' => $this->hash($token), 'user_id' => $userId]);
        if ($row === null || $row['confirmed_at'] !== null || (string) $row['expires_at'] < $this->now()) {
            return null;
        }
        return $row;
    }

    public function markEmailChangeConfirmed(int $id): void
    {
        $this->db->update('email_changes', ['confirmed_at' => $this->now()], ['id' => $id]);
    }

    /** @return array<string, mixed>|null the pending email change of a user (shown on the profile screen) */
    public function pendingEmailChange(int $userId): ?array
    {
        $row = $this->db->first('email_changes', ['user_id' => $userId, 'confirmed_at' => null]);
        return $row !== null && (string) $row['expires_at'] >= $this->now() ? $row : null;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
