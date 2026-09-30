<?php

declare(strict_types=1);

namespace BMMatic\Repositories;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;

/**
 * Appointment requests from the public form. New requests start with status "new" and unread.
 *
 * The admin panel shows the same records in two ways: the Appointments screen is the status workflow, the Messages
 * screen is an inbox (unread = `read_at IS NULL`, independent of the status, so moving a request back to "new" never
 * makes it unread again).
 *
 * @phpstan-type Filters array{status?: string, from?: string, to?: string, q?: string, unread?: bool, sort?: string, dir?: string}
 */
final class AppointmentRepository
{
    public const STATUSES = ['new', 'confirmed', 'diagnosis', 'quoted', 'done', 'cancelled'];
    /** Status changes offered in the panel: everything except going back to "new" is allowed from any status. */
    public const SORTABLE = ['created_at', 'name', 'status'];
    private const PER_PAGE = 20;

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * @param array{name: string, phone: string, email: string, car: string, gearbox_type: string, symptoms: string} $values
     */
    public function create(array $values, string $lang, string $ipHash, string $userAgent): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        return $this->db->insert('appointments', [
            'status' => 'new',
            'name' => $values['name'],
            'phone' => $values['phone'],
            'email' => $values['email'],
            'car' => $values['car'],
            'gearbox_type' => $values['gearbox_type'],
            'symptoms' => $values['symptoms'],
            'lang_code' => $lang,
            'consent_at' => $now,
            'ip_hash' => $ipHash,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('appointments', ['id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {appointments} WHERE `status` = :s', ['s' => $status]);
    }

    public function countUnread(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {appointments} WHERE `read_at` IS NULL');
    }

    /** Requests received in the last $days days (dashboard: "this week"). */
    public function countSince(int $days): int
    {
        $from = $this->clock->now()->modify('-' . max(1, $days) . ' days')->format('Y-m-d H:i:s');
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {appointments} WHERE `created_at` >= :from', ['from' => $from]);
    }

    /** @return array<string, int> status => count (every status present, zero included) */
    public function countsByStatus(): array
    {
        $out = array_fill_keys(self::STATUSES, 0);
        foreach ($this->db->all('SELECT `status`, COUNT(*) AS n FROM {appointments} GROUP BY `status`') as $row) {
            $status = (string) $row['status'];
            if (isset($out[$status])) {
                $out[$status] = (int) $row['n'];
            }
        }
        return $out;
    }

    /**
     * Filtered, sorted page of requests.
     *
     * @param Filters $filters
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = $this->conditions($filters);
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {appointments}' . $where, $params);
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $sort = in_array($filters['sort'] ?? '', self::SORTABLE, true) ? (string) $filters['sort'] : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $rows = $this->db->all(
            'SELECT * FROM {appointments}' . $where . ' ORDER BY `' . $sort . '` ' . $dir . ', `id` ' . $dir
            . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * Every matching request for the CSV export (newest first, capped).
     *
     * @param Filters $filters
     * @return list<array<string, mixed>>
     */
    public function export(array $filters, int $limit = 5000): array
    {
        [$where, $params] = $this->conditions($filters);
        return $this->db->all('SELECT * FROM {appointments}' . $where . ' ORDER BY `created_at` DESC, `id` DESC LIMIT ' . max(1, $limit), $params);
    }

    /**
     * @param Filters $filters
     * @return array{0: string, 1: array<string, string>}
     */
    private function conditions(array $filters): array
    {
        $sql = [];
        $params = [];
        if (isset($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql[] = '`status` = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['unread'] ?? false) === true) {
            $sql[] = '`read_at` IS NULL';
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
            $sql[] = "CONCAT_WS(' ', `name`, `email`, `phone`, `car`, `symptoms`) LIKE :q";
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        }
        return [$sql === [] ? '' : ' WHERE ' . implode(' AND ', $sql), $params];
    }

    /** Marks a request read when it is opened. Not audit-logged: opening a request is not a change. */
    public function markRead(int $id, ?int $userId): void
    {
        $this->db->run('UPDATE {appointments} SET `read_at` = :now, `read_by` = :user WHERE `id` = :id AND `read_at` IS NULL', [
            'now' => $this->now(),
            'user' => $userId,
            'id' => $id,
        ]);
    }

    public function markUnread(int $id): void
    {
        $this->db->update('appointments', ['read_at' => null, 'read_by' => null], ['id' => $id]);
    }

    public function changeStatus(int $id, string $status, ?int $userId): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $now = $this->now();
        return $this->db->update('appointments', [
            'status' => $status,
            'status_changed_at' => $now,
            'status_changed_by' => $userId,
            'updated_at' => $now,
        ], ['id' => $id]) > 0;
    }

    public function addNote(int $id, ?int $userId, string $authorName, string $body): int
    {
        return $this->db->insert('appointment_notes', [
            'appointment_id' => $id,
            'user_id' => $userId,
            'author_name' => mb_substr($authorName, 0, 120),
            'body' => $body,
            'created_at' => $this->now(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function notes(int $id): array
    {
        return $this->db->all('SELECT * FROM {appointment_notes} WHERE `appointment_id` = :id ORDER BY `created_at`, `id`', ['id' => $id]);
    }

    public function deleteNote(int $appointmentId, int $noteId): int
    {
        return $this->db->delete('appointment_notes', ['id' => $noteId, 'appointment_id' => $appointmentId]);
    }

    /** @return list<array<string, mixed>> the newest requests for the dashboard table */
    public function recent(int $limit = 5): array
    {
        return $this->db->all('SELECT * FROM {appointments} ORDER BY `created_at` DESC, `id` DESC LIMIT ' . max(1, min(50, $limit)));
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
