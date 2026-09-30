<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Content\EntryTypes;
use Gate\Core\Database;

/**
 * Read access to projects, news, publications and albums for the website, in one language with the default language
 * as fallback: an entry appears when it is enabled and has a published translation in the page language or in the
 * default language. Lists are filtered and paginated in SQL.
 *
 * @phpstan-import-type MediaItem from ContentRepository
 * @phpstan-type Entry array{id: int, type: string, expertise_id: int|null, region: string, status: string, start_date: string, end_date: string, published_on: string, beneficiaries: int|null, donors: string, cover_media_id: int|null, file_media_id: int|null, related_id: int|null, is_featured: bool, updated_at: string, lang: string, slug: string, title: string, summary: string, body: string, location: string, meta_title: string, meta_description: string, cover: MediaItem|null}
 * @phpstan-type Filters array{expertise?: int, region?: string, status?: string, year?: int, related?: int, exclude?: int}
 */
final class EntryRepository
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly Database $db, private readonly ContentRepository $content)
    {
    }

    /**
     * One page of entries of a type, newest first (featured projects are not reordered: the date decides).
     *
     * @param Filters $filters
     * @return array{rows: list<Entry>, total: int, page: int, pages: int}
     */
    public function paginate(string $type, string $lang, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->conditions($type, $lang, $filters);
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {entries} e' . $where, $params);
        $perPage = max(1, min(60, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $rows = $this->db->all('SELECT e.* FROM {entries} e' . $where . ' ORDER BY e.`published_on` DESC, e.`id` DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
        return ['rows' => $this->hydrate($rows, $lang), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * The newest entries of a type; featured ones first when $featuredFirst.
     *
     * @param Filters $filters
     * @return list<Entry>
     */
    public function latest(string $type, string $lang, int $limit, bool $featuredFirst = false, array $filters = []): array
    {
        [$where, $params] = $this->conditions($type, $lang, $filters);
        $order = ($featuredFirst ? 'e.`is_featured` DESC, ' : '') . 'e.`published_on` DESC, e.`id` DESC';
        $rows = $this->db->all('SELECT e.* FROM {entries} e' . $where . ' ORDER BY ' . $order . ' LIMIT ' . max(1, min(24, $limit)), $params);
        return $this->hydrate($rows, $lang);
    }

    /**
     * An entry by its slug. An entry translated into this language answers to its own slug only; one without a
     * published translation here answers to the default language's slug (the link the list gives it).
     *
     * @return Entry|null
     */
    public function bySlug(string $type, string $lang, string $slug): ?array
    {
        $row = $this->db->one(
            'SELECT e.* FROM {entries} e JOIN {entry_translations} t ON t.`entry_id` = e.`id`
             WHERE e.`type` = :type AND e.`is_enabled` = 1 AND t.`lang_code` = :lang AND t.`slug` = :slug AND t.`is_published` = 1',
            ['type' => $type, 'lang' => $lang, 'slug' => $slug]
        );
        if ($row === null && $lang !== $this->content->defaultLang()) {
            $row = $this->db->one(
                'SELECT e.* FROM {entries} e JOIN {entry_translations} t ON t.`entry_id` = e.`id`
                 WHERE e.`type` = :type AND e.`is_enabled` = 1 AND t.`lang_code` = :def AND t.`slug` = :slug AND t.`is_published` = 1
                 AND NOT EXISTS (SELECT 1 FROM {entry_translations} o WHERE o.`entry_id` = e.`id` AND o.`lang_code` = :lang AND o.`is_published` = 1)',
                ['type' => $type, 'def' => $this->content->defaultLang(), 'slug' => $slug, 'lang' => $lang]
            );
        }
        return $row === null ? null : ($this->hydrate([$row], $lang)[0] ?? null);
    }

    /** @return Entry|null */
    public function find(int $id, string $lang): ?array
    {
        $row = $this->db->one('SELECT * FROM {entries} WHERE `id` = :id AND `is_enabled` = 1', ['id' => $id]);
        return $row === null ? null : ($this->hydrate([$row], $lang)[0] ?? null);
    }

    /**
     * Slug of an entry per language (only languages with their own published translation).
     *
     * @param list<string> $langs
     * @return array<string, string>
     */
    public function slugs(int $id, array $langs): array
    {
        $out = [];
        foreach ($this->db->all('SELECT `lang_code`, `slug` FROM {entry_translations} WHERE `entry_id` = :id AND `is_published` = 1', ['id' => $id]) as $row) {
            if (in_array($row['lang_code'], $langs, true)) {
                $out[(string) $row['lang_code']] = (string) $row['slug'];
            }
        }
        return $out;
    }

    /**
     * Photos of an entry (gallery), in order, with alt texts.
     *
     * @return list<MediaItem>
     */
    public function gallery(int $entryId, string $lang): array
    {
        $ids = array_map(static fn (array $r): int => (int) $r['media_id'], $this->db->all('SELECT `media_id` FROM {entry_media} WHERE `entry_id` = :id ORDER BY `sort_order`, `id`', ['id' => $entryId]));
        $media = $this->content->media($ids, $lang);
        $out = [];
        foreach ($ids as $id) {
            if (isset($media[$id]) && $media[$id]['kind'] === 'image') {
                $out[] = $media[$id];
            }
        }
        return $out;
    }

    public function galleryCount(int $entryId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {entry_media} WHERE `entry_id` = :id', ['id' => $entryId]);
    }

    /**
     * Values that actually occur, for the filter chips of a list page: regions, statuses, expertise ids and years.
     *
     * @return array{regions: list<string>, statuses: list<string>, expertise: list<int>, years: list<int>}
     */
    public function facets(string $type, string $lang): array
    {
        /** @var array{regions: list<string>, statuses: list<string>, expertise: list<int>, years: list<int>} */
        return $this->remember('facets.' . $type . '.' . $lang, function () use ($type, $lang): array {
            [$where, $params] = $this->conditions($type, $lang, []);
            $rows = $this->db->all('SELECT e.`region`, e.`status`, e.`expertise_id`, YEAR(e.`published_on`) AS y FROM {entries} e' . $where, $params);
            $regions = $statuses = $expertise = $years = [];
            foreach ($rows as $row) {
                if ((string) $row['region'] !== '') {
                    $regions[(string) $row['region']] = true;
                }
                if ((string) $row['status'] !== '') {
                    $statuses[(string) $row['status']] = true;
                }
                if ($row['expertise_id'] !== null) {
                    $expertise[(int) $row['expertise_id']] = true;
                }
                $years[(int) $row['y']] = true;
            }
            krsort($years);
            return [
                'regions' => array_values(array_filter(EntryTypes::REGIONS, static fn (string $r): bool => isset($regions[$r]))),
                'statuses' => array_values(array_filter(EntryTypes::statuses($type), static fn (string $s): bool => isset($statuses[$s]))),
                'expertise' => array_map('intval', array_keys($expertise)),
                'years' => array_map('intval', array_keys($years)),
            ];
        });
    }

    /** @return array<string, int> region => number of listed projects (map on the home page) */
    public function regionCounts(string $lang): array
    {
        [$where, $params] = $this->conditions('project', $lang, []);
        $out = [];
        foreach ($this->db->all('SELECT e.`region`, COUNT(*) AS n FROM {entries} e' . $where . ' AND e.`region` <> \'\' GROUP BY e.`region`', $params) as $row) {
            $out[(string) $row['region']] = (int) $row['n'];
        }
        return $out;
    }

    /**
     * Every listed entry of a language (sitemap): id, type, slug and date.
     *
     * @return list<array{id: int, type: string, slug: string, updated_at: string}>
     */
    public function sitemap(string $lang): array
    {
        $rows = $this->db->all(
            'SELECT e.`id`, e.`type`, e.`updated_at`, t.`slug` FROM {entries} e JOIN {entry_translations} t ON t.`entry_id` = e.`id`
             WHERE e.`is_enabled` = 1 AND t.`lang_code` = :lang AND t.`is_published` = 1 ORDER BY e.`type`, e.`published_on` DESC',
            ['lang' => $lang]
        );
        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'type' => (string) $r['type'], 'slug' => (string) $r['slug'], 'updated_at' => (string) $r['updated_at']], $rows);
    }

    /**
     * WHERE clause for listed entries of a type in a language, plus the filters.
     *
     * @param Filters $filters
     * @return array{0: string, 1: array<string, int|string>}
     */
    private function conditions(string $type, string $lang, array $filters): array
    {
        $sql = [
            'e.`type` = :type',
            'e.`is_enabled` = 1',
            'EXISTS (SELECT 1 FROM {entry_translations} t WHERE t.`entry_id` = e.`id` AND t.`is_published` = 1 AND t.`lang_code` IN (:la, :lb))',
        ];
        $params = ['type' => $type, 'la' => $lang, 'lb' => $this->content->defaultLang()];
        if (isset($filters['expertise']) && $filters['expertise'] > 0) {
            $sql[] = 'e.`expertise_id` = :expertise';
            $params['expertise'] = $filters['expertise'];
        }
        if (isset($filters['region']) && in_array($filters['region'], EntryTypes::REGIONS, true)) {
            $sql[] = 'e.`region` = :region';
            $params['region'] = $filters['region'];
        }
        if (isset($filters['status']) && in_array($filters['status'], EntryTypes::statuses($type), true)) {
            $sql[] = 'e.`status` = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['year']) && $filters['year'] >= 1990 && $filters['year'] <= 2100) {
            $sql[] = 'e.`published_on` BETWEEN :ys AND :ye';
            $params['ys'] = $filters['year'] . '-01-01';
            $params['ye'] = $filters['year'] . '-12-31';
        }
        if (isset($filters['related']) && $filters['related'] > 0) {
            $sql[] = 'e.`related_id` = :related';
            $params['related'] = $filters['related'];
        }
        if (isset($filters['exclude']) && $filters['exclude'] > 0) {
            $sql[] = 'e.`id` <> :exclude';
            $params['exclude'] = $filters['exclude'];
        }
        return [' WHERE ' . implode(' AND ', $sql), $params];
    }

    /**
     * Entry rows + their translation (page language, else default language) + cover image.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<Entry>
     */
    private function hydrate(array $rows, string $lang): array
    {
        if ($rows === []) {
            return [];
        }
        $params = ['la' => $lang, 'lb' => $this->content->defaultLang()];
        $in = [];
        foreach ($rows as $i => $row) {
            $in[] = ':e' . $i;
            $params['e' . $i] = (int) $row['id'];
        }
        $translations = [];
        foreach ($this->db->all('SELECT * FROM {entry_translations} WHERE `is_published` = 1 AND `lang_code` IN (:la, :lb) AND `entry_id` IN (' . implode(',', $in) . ')', $params) as $t) {
            $id = (int) $t['entry_id'];
            if ($t['lang_code'] === $lang || !isset($translations[$id])) {
                $translations[$id] = $t + ['lang' => (string) $t['lang_code']];
            }
        }
        $covers = $this->content->media(array_map(static fn (array $r): ?int => $r['cover_media_id'] !== null ? (int) $r['cover_media_id'] : null, $rows), $lang);
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $t = $translations[$id] ?? null;
            if ($t === null) {
                continue;
            }
            $cover = $row['cover_media_id'] !== null ? ($covers[(int) $row['cover_media_id']] ?? null) : null;
            $out[] = [
                'id' => $id,
                'type' => (string) $row['type'],
                'expertise_id' => $row['expertise_id'] !== null ? (int) $row['expertise_id'] : null,
                'region' => (string) $row['region'],
                'status' => (string) $row['status'],
                'start_date' => (string) ($row['start_date'] ?? ''),
                'end_date' => (string) ($row['end_date'] ?? ''),
                'published_on' => (string) $row['published_on'],
                'beneficiaries' => $row['beneficiaries'] !== null ? (int) $row['beneficiaries'] : null,
                'donors' => (string) $row['donors'],
                'cover_media_id' => $row['cover_media_id'] !== null ? (int) $row['cover_media_id'] : null,
                'file_media_id' => $row['file_media_id'] !== null ? (int) $row['file_media_id'] : null,
                'related_id' => $row['related_id'] !== null ? (int) $row['related_id'] : null,
                'is_featured' => (int) $row['is_featured'] === 1,
                'updated_at' => (string) $row['updated_at'],
                'cover' => $cover !== null && $cover['kind'] === 'image' ? $cover : null,
            ] + ContentRepository::strings($t, ['lang', 'slug', 'title', 'summary', 'body', 'location', 'meta_title', 'meta_description']);
        }
        return $out;
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function remember(string $key, callable $fn): mixed
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $fn();
        }
        /** @var T */
        return $this->cache[$key];
    }
}
