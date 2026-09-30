<?php

declare(strict_types=1);

namespace BMMatic\Repositories;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;
use BMMatic\Reviews\ReviewData;

/**
 * The imported Google reviews. `is_visible` belongs to the workshop: a sync never changes it, and a review that
 * Google stops returning is marked with `deleted_at` instead of disappearing from the panel.
 *
 * @phpstan-type Filters array{tab?: string, rating?: int, language?: string, q?: string}
 */
final class ReviewRepository
{
    public const ORDERS = ['newest', 'oldest', 'highest'];
    private const PER_PAGE = 20;

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * Stores one review from a provider. Returns what happened, so the sync can report added/updated counts.
     *
     * @return 'added'|'updated'|'unchanged'
     */
    public function upsert(ReviewData $review, string $source, bool $visibleWhenNew): string
    {
        $now = $this->now();
        $existing = $this->db->first('google_reviews', ['google_review_id' => $review->externalId]);
        if ($existing === null) {
            $this->db->insert('google_reviews', $review->toRow($source, $now) + [
                'is_visible' => $visibleWhenNew ? 1 : 0,
                'imported_at' => $now,
            ]);
            return 'added';
        }
        if (!$review->differsFrom($existing)) {
            return 'unchanged';
        }
        // Everything except is_visible: the choice about what appears on the website stays with the workshop.
        $this->db->update('google_reviews', $review->toRow($source, $now), ['id' => (int) $existing['id']]);
        return 'updated';
    }

    /**
     * Marks reviews that the provider no longer returns. They keep their data and stay visible in the panel.
     *
     * @param list<string> $keepExternalIds
     */
    public function markMissingAsDeleted(array $keepExternalIds, string $source): int
    {
        if ($keepExternalIds === []) {
            return 0;
        }
        $placeholders = [];
        $params = ['source' => $source, 'now' => $this->now()];
        foreach (array_values($keepExternalIds) as $i => $id) {
            $placeholders[] = ':id' . $i;
            $params['id' . $i] = $id;
        }
        return $this->db->run(
            'UPDATE {google_reviews} SET `deleted_at` = :now WHERE `source` = :source AND `deleted_at` IS NULL AND `google_review_id` NOT IN (' . implode(', ', $placeholders) . ')',
            $params
        )->rowCount();
    }

    public function existsByExternalId(string $externalId): bool
    {
        return $this->db->first('google_reviews', ['google_review_id' => $externalId]) !== null;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('google_reviews', ['id' => $id]);
    }

    public function setVisible(int $id, bool $visible): bool
    {
        return $this->db->update('google_reviews', ['is_visible' => $visible ? 1 : 0, 'updated_at' => $this->now()], ['id' => $id]) > 0;
    }

    /**
     * Shows or hides everything that matches the current filter (the bulk buttons above the table).
     *
     * @param Filters $filters
     */
    public function setVisibleForFilter(array $filters, bool $visible): int
    {
        [$where, $params] = $this->conditions($filters);
        $params['now'] = $this->now();
        $params['visible'] = $visible ? 1 : 0;
        return $this->db->run('UPDATE {google_reviews} SET `is_visible` = :visible, `updated_at` = :now' . $where, $params)->rowCount();
    }

    /**
     * @param Filters $filters
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = $this->conditions($filters);
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {google_reviews}' . $where, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $rows = $this->db->all(
            'SELECT * FROM {google_reviews}' . $where . ' ORDER BY `review_date` DESC, `id` DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** @return array{all: int, visible: int, hidden: int, deleted: int} */
    public function counts(): array
    {
        $row = $this->db->all('SELECT COUNT(*) AS `all`, SUM(`is_visible` = 1) AS `visible`, SUM(`is_visible` = 0) AS `hidden`, SUM(`deleted_at` IS NOT NULL) AS `deleted` FROM {google_reviews}')[0] ?? [];
        return [
            'all' => (int) ($row['all'] ?? 0),
            'visible' => (int) ($row['visible'] ?? 0),
            'hidden' => (int) ($row['hidden'] ?? 0),
            'deleted' => (int) ($row['deleted'] ?? 0),
        ];
    }

    /** @return list<string> the languages that occur, for the language filter */
    public function languages(): array
    {
        $out = [];
        foreach ($this->db->all("SELECT DISTINCT `language` FROM {google_reviews} WHERE `language` <> '' ORDER BY `language`") as $row) {
            $out[] = (string) $row['language'];
        }
        return $out;
    }

    /**
     * What the website shows: visible reviews that still exist at Google, in the chosen display order.
     *
     * @return list<array<string, mixed>>
     */
    public function visible(int $limit = 12, string $order = 'newest'): array
    {
        $sql = match (in_array($order, self::ORDERS, true) ? $order : 'newest') {
            'oldest' => '`review_date` ASC, `id` ASC',
            'highest' => '`rating` DESC, `review_date` DESC',
            default => '`review_date` DESC, `id` DESC',
        };
        return $this->db->all(
            'SELECT * FROM {google_reviews} WHERE `is_visible` = 1 AND `deleted_at` IS NULL ORDER BY ' . $sql . ' LIMIT ' . max(1, min(100, $limit))
        );
    }

    public function countVisible(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {google_reviews} WHERE `is_visible` = 1 AND `deleted_at` IS NULL');
    }

    // ------------------------------------------------------------------------------------------------ sync log

    public function startLog(string $provider, bool $dryRun): int
    {
        return $this->db->insert('review_sync_log', [
            'provider' => $provider,
            'status' => 'running',
            'dry_run' => $dryRun ? 1 : 0,
            'started_at' => $this->now(),
        ]);
    }

    /** @param array{added: int, updated: int, removed: int} $counts */
    public function finishLog(int $id, string $status, array $counts, string $message = ''): void
    {
        $this->db->update('review_sync_log', [
            'status' => $status,
            'added' => $counts['added'],
            'updated' => $counts['updated'],
            'removed' => $counts['removed'],
            'message' => mb_substr($message, 0, 500),
            'finished_at' => $this->now(),
        ], ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function recentLogs(int $limit = 5): array
    {
        return $this->db->all('SELECT * FROM {review_sync_log} ORDER BY `started_at` DESC, `id` DESC LIMIT ' . max(1, min(50, $limit)));
    }

    /** @return array<string, mixed>|null */
    public function lastLog(): ?array
    {
        return $this->recentLogs(1)[0] ?? null;
    }

    /**
     * @param Filters $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function conditions(array $filters): array
    {
        $sql = [];
        $params = [];
        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'visible') {
            $sql[] = '`is_visible` = 1';
        } elseif ($tab === 'hidden') {
            $sql[] = '`is_visible` = 0';
        }
        if (isset($filters['rating']) && $filters['rating'] >= 1 && $filters['rating'] <= 5) {
            $sql[] = '`rating` >= :rating';
            $params['rating'] = $filters['rating'];
        }
        if (isset($filters['language']) && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $filters['language']) === 1) {
            $sql[] = '`language` = :language';
            $params['language'] = $filters['language'];
        }
        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            $sql[] = "CONCAT_WS(' ', `reviewer_name`, `text`) LIKE :q";
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], mb_substr($q, 0, 80)) . '%';
        }
        return [$sql === [] ? '' : ' WHERE ' . implode(' AND ', $sql), $params];
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
