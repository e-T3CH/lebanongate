<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Security\Crypto;

/**
 * Newsletter sign-ups with double opt-in. A sign-up only counts once the address confirmed it through the link in the
 * confirmation email. Only an HMAC of the link token is stored; the same token later serves the unsubscribe link, so
 * every email can carry a one-click way out.
 */
final class SubscriberRepository
{
    public const STATES = ['pending', 'confirmed', 'unsubscribed'];
    private const PER_PAGE = 30;

    public function __construct(private readonly Database $db, private readonly Crypto $crypto, private readonly Clock $clock)
    {
    }

    /**
     * Records a sign-up and returns the token for the confirmation link, or null when the address is already
     * confirmed (the visitor sees the same answer either way, so the form never reveals who is subscribed).
     */
    public function subscribe(string $email, string $lang, string $ipHash): ?string
    {
        $email = mb_strtolower(trim($email));
        $token = bin2hex(random_bytes(24));
        $hash = $this->crypto->hmac('subscriber|' . $token);
        $now = $this->now();
        $existing = $this->db->first('subscribers', ['email' => $email]);
        if ($existing !== null) {
            if ($existing['confirmed_at'] !== null && $existing['unsubscribed_at'] === null) {
                return null;
            }
            // Pending or unsubscribed: a new confirmation link, the old one stops working.
            $this->db->update('subscribers', ['lang_code' => $lang, 'token_hash' => $hash, 'confirmed_at' => null, 'unsubscribed_at' => null, 'ip_hash' => $ipHash, 'updated_at' => $now], ['id' => (int) $existing['id']]);
            return $token;
        }
        $this->db->insert('subscribers', [
            'email' => $email,
            'lang_code' => $lang,
            'token_hash' => $hash,
            'ip_hash' => $ipHash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $token;
    }

    /** Confirms the sign-up of a link token. Returns false for an unknown token. */
    public function confirm(string $token): bool
    {
        $row = $this->byToken($token);
        if ($row === null || $row['unsubscribed_at'] !== null) {
            return false;
        }
        if ($row['confirmed_at'] === null) {
            $this->db->update('subscribers', ['confirmed_at' => $this->now(), 'updated_at' => $this->now()], ['id' => (int) $row['id']]);
        }
        return true;
    }

    public function unsubscribe(string $token): bool
    {
        $row = $this->byToken($token);
        if ($row === null) {
            return false;
        }
        if ($row['unsubscribed_at'] === null) {
            $this->db->update('subscribers', ['unsubscribed_at' => $this->now(), 'updated_at' => $this->now()], ['id' => (int) $row['id']]);
        }
        return true;
    }

    /** @return array{pending: int, confirmed: int, unsubscribed: int} */
    public function counts(): array
    {
        return [
            'pending' => (int) $this->db->scalar('SELECT COUNT(*) FROM {subscribers} WHERE `confirmed_at` IS NULL AND `unsubscribed_at` IS NULL'),
            'confirmed' => (int) $this->db->scalar('SELECT COUNT(*) FROM {subscribers} WHERE `confirmed_at` IS NOT NULL AND `unsubscribed_at` IS NULL'),
            'unsubscribed' => (int) $this->db->scalar('SELECT COUNT(*) FROM {subscribers} WHERE `unsubscribed_at` IS NOT NULL'),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} */
    public function paginate(string $state, string $search, int $page): array
    {
        [$where, $params] = $this->conditions($state, $search);
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {subscribers}' . $where, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($page, $pages));
        $rows = $this->db->all('SELECT * FROM {subscribers}' . $where . ' ORDER BY `created_at` DESC, `id` DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** @return list<array<string, mixed>> confirmed subscribers for the CSV export */
    public function confirmed(): array
    {
        return $this->db->all('SELECT * FROM {subscribers} WHERE `confirmed_at` IS NOT NULL AND `unsubscribed_at` IS NULL ORDER BY `confirmed_at`, `id`');
    }

    public function delete(int $id): int
    {
        return $this->db->delete('subscribers', ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('subscribers', ['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    public static function state(array $row): string
    {
        if ($row['unsubscribed_at'] !== null) {
            return 'unsubscribed';
        }
        return $row['confirmed_at'] !== null ? 'confirmed' : 'pending';
    }

    /** @return array<string, mixed>|null */
    private function byToken(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return null;
        }
        return $this->db->first('subscribers', ['token_hash' => $this->crypto->hmac('subscriber|' . $token)]);
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function conditions(string $state, string $search): array
    {
        $sql = [];
        $params = [];
        if ($state === 'pending') {
            $sql[] = '`confirmed_at` IS NULL AND `unsubscribed_at` IS NULL';
        } elseif ($state === 'confirmed') {
            $sql[] = '`confirmed_at` IS NOT NULL AND `unsubscribed_at` IS NULL';
        } elseif ($state === 'unsubscribed') {
            $sql[] = '`unsubscribed_at` IS NOT NULL';
        }
        if (trim($search) !== '') {
            $sql[] = '`email` LIKE :q';
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
        }
        return [$sql === [] ? '' : ' WHERE ' . implode(' AND ', $sql), $params];
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
