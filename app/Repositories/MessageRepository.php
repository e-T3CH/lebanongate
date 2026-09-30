<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * Messages from the contact form. A new message is unread (`read_at IS NULL`) and in the inbox (`archived_at IS NULL`);
 * opening it marks it read, and archiving moves it out of the inbox without deleting it.
 *
 * @phpstan-type Filters array{box?: string, subject?: string, from?: string, to?: string, q?: string}
 */
final class MessageRepository
{
    public const SUBJECTS = ['general', 'partnership', 'media', 'volunteer', 'other'];
    /** inbox: not archived; unread: not archived and unread; archive: archived. */
    public const BOXES = ['inbox', 'unread', 'archive'];
    private const PER_PAGE = 20;

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * @param array{name: string, email: string, phone: string, organisation: string, subject: string, message: string} $values
     */
    public function create(array $values, string $lang, string $ipHash, string $userAgent): int
    {
        $now = $this->now();
        return $this->db->insert('messages', [
            'name' => $values['name'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'organisation' => $values['organisation'],
            'subject' => in_array($values['subject'], self::SUBJECTS, true) ? $values['subject'] : 'general',
            'message' => $values['message'],
            'lang_code' => $lang,
            'consent_at' => $now,
            'ip_hash' => $ipHash,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'created_at' => $now,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('messages', ['id' => $id]);
    }

    public function countUnread(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {messages} WHERE `read_at` IS NULL AND `archived_at` IS NULL');
    }

    /** Messages received in the last $days days (dashboard). */
    public function countSince(int $days): int
    {
        $from = $this->clock->now()->modify('-' . max(1, $days) . ' days')->format('Y-m-d H:i:s');
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {messages} WHERE `created_at` >= :from', ['from' => $from]);
    }

    /**
     * Filtered page of messages, newest first.
     *
     * @param Filters $filters
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = $this->conditions($filters);
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {messages}' . $where, $params);
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $rows = $this->db->all(
            'SELECT * FROM {messages}' . $where . ' ORDER BY `created_at` DESC, `id` DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * Every matching message for the CSV export (newest first, capped).
     *
     * @param Filters $filters
     * @return list<array<string, mixed>>
     */
    public function export(array $filters, int $limit = 5000): array
    {
        [$where, $params] = $this->conditions($filters);
        return $this->db->all('SELECT * FROM {messages}' . $where . ' ORDER BY `created_at` DESC, `id` DESC LIMIT ' . max(1, $limit), $params);
    }

    /** @return list<array<string, mixed>> the newest inbox messages for the dashboard */
    public function recent(int $limit = 5): array
    {
        return $this->db->all('SELECT * FROM {messages} WHERE `archived_at` IS NULL ORDER BY `created_at` DESC, `id` DESC LIMIT ' . max(1, min(50, $limit)));
    }

    /** Marks a message read when it is opened. Not audit-logged: opening a message is not a change. */
    public function markRead(int $id, ?int $userId): void
    {
        $this->db->run('UPDATE {messages} SET `read_at` = :now, `read_by` = :user WHERE `id` = :id AND `read_at` IS NULL', [
            'now' => $this->now(),
            'user' => $userId,
            'id' => $id,
        ]);
    }

    public function markUnread(int $id): void
    {
        $this->db->update('messages', ['read_at' => null, 'read_by' => null], ['id' => $id]);
    }

    public function setArchived(int $id, bool $archived): void
    {
        $this->db->update('messages', ['archived_at' => $archived ? $this->now() : null], ['id' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete('messages', ['id' => $id]);
    }

    public function addNote(int $id, ?int $userId, string $authorName, string $body): int
    {
        return $this->db->insert('message_notes', [
            'message_id' => $id,
            'user_id' => $userId,
            'author_name' => mb_substr($authorName, 0, 120),
            'body' => $body,
            'created_at' => $this->now(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function notes(int $id): array
    {
        return $this->db->all('SELECT * FROM {message_notes} WHERE `message_id` = :id ORDER BY `created_at`, `id`', ['id' => $id]);
    }

    public function deleteNote(int $messageId, int $noteId): int
    {
        return $this->db->delete('message_notes', ['id' => $noteId, 'message_id' => $messageId]);
    }

    /**
     * @param Filters $filters
     * @return array{0: string, 1: array<string, string>}
     */
    private function conditions(array $filters): array
    {
        $sql = [];
        $params = [];
        $box = $filters['box'] ?? 'inbox';
        if ($box === 'archive') {
            $sql[] = '`archived_at` IS NOT NULL';
        } else {
            $sql[] = '`archived_at` IS NULL';
            if ($box === 'unread') {
                $sql[] = '`read_at` IS NULL';
            }
        }
        if (isset($filters['subject']) && in_array($filters['subject'], self::SUBJECTS, true)) {
            $sql[] = '`subject` = :subject';
            $params['subject'] = $filters['subject'];
        }
        if (isset($filters['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from']) === 1) {
            $sql[] = '`created_at` >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (isset($filters['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to']) === 1) {
            $sql[] = '`created_at` <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }
        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            // One placeholder over all searchable columns: a named parameter may only appear once per statement.
            $sql[] = "CONCAT_WS(' ', `name`, `email`, `phone`, `organisation`, `message`) LIKE :q";
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        }
        return [' WHERE ' . implode(' AND ', $sql), $params];
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
