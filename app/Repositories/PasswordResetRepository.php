<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Security\Crypto;

/**
 * Password reset links. The token only exists in the email; the database keeps an HMAC of it. A link is valid for
 * TTL_MINUTES, works once, and asking for a new one cancels the older ones of that user.
 */
final class PasswordResetRepository
{
    public const TTL_MINUTES = 60;

    public function __construct(private readonly Database $db, private readonly Crypto $crypto, private readonly Clock $clock)
    {
    }

    /** Creates a link for the user and returns the token to put in the email. */
    public function create(int $userId): string
    {
        $now = $this->clock->now();
        // Only the newest link works.
        $this->db->run('UPDATE {password_resets} SET `used_at` = :now WHERE `user_id` = :id AND `used_at` IS NULL', ['now' => $now->format('Y-m-d H:i:s'), 'id' => $userId]);
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->db->insert('password_resets', [
            'user_id' => $userId,
            'token_hash' => $this->hash($token),
            'expires_at' => $now->modify('+' . self::TTL_MINUTES . ' minutes')->format('Y-m-d H:i:s'),
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    /** @return array{id: int, user_id: int}|null a link that is still valid */
    public function find(string $token): ?array
    {
        if (preg_match('/^[A-Za-z0-9_-]{40,64}$/', $token) !== 1) {
            return null;
        }
        $row = $this->db->one(
            'SELECT `id`, `user_id` FROM {password_resets} WHERE `token_hash` = :h AND `used_at` IS NULL AND `expires_at` > :now',
            ['h' => $this->hash($token), 'now' => $this->clock->now()->format('Y-m-d H:i:s')]
        );
        return $row === null ? null : ['id' => (int) $row['id'], 'user_id' => (int) $row['user_id']];
    }

    public function markUsed(int $id): void
    {
        $this->db->update('password_resets', ['used_at' => $this->clock->now()->format('Y-m-d H:i:s')], ['id' => $id]);
    }

    private function hash(string $token): string
    {
        return $this->crypto->hmac('password-reset|' . $token);
    }
}
