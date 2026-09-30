<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Content\EntryTypes;
use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * Write access to projects, news, publications and albums for the admin panel: the list per type with search and
 * paging, one entry with every translation, the photo gallery. The website reads through EntryRepository.
 *
 * @phpstan-type EntryValues array{expertise_id: int|null, region: string, status: string, start_date: string|null, end_date: string|null, published_on: string, beneficiaries: int|null, donors: string, cover_media_id: int|null, file_media_id: int|null, related_id: int|null, is_featured: bool, is_enabled: bool}
 */
final class EntryAdminRepository
{
    public const FIELDS = ['slug', 'title', 'summary', 'body', 'location', 'meta_title', 'meta_description'];
    private const PER_PAGE = 25;

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * Entries of a type, newest first, with the title in $lang (else any language) and the languages they exist in.
     *
     * @return array{rows: list<array{id: int, title: string, published_on: string, status: string, region: string, is_enabled: bool, is_featured: bool, states: array<string, string>, cover_media_id: int|null}>, total: int, page: int, pages: int}
     */
    public function paginate(string $type, string $lang, string $search, int $page): array
    {
        $where = ' WHERE e.`type` = :type';
        $params = ['type' => $type];
        if (trim($search) !== '') {
            $where .= ' AND EXISTS (SELECT 1 FROM {entry_translations} s WHERE s.`entry_id` = e.`id` AND s.`title` LIKE :q)';
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
        }
        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM {entries} e' . $where, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($page, $pages));
        $rows = $this->db->all('SELECT e.* FROM {entries} e' . $where . ' ORDER BY e.`published_on` DESC, e.`id` DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $params);
        $translations = $this->translationsFor(array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $byLang = $translations[$id] ?? [];
            $title = (string) ($byLang[$lang]['title'] ?? (reset($byLang) !== false ? reset($byLang)['title'] : ''));
            $states = [];
            foreach ($byLang as $code => $t) {
                $states[$code] = (int) $t['is_published'] === 1 ? 'published' : 'draft';
            }
            $out[] = [
                'id' => $id,
                'title' => $title !== '' ? $title : '#' . $id,
                'published_on' => (string) $row['published_on'],
                'status' => (string) $row['status'],
                'region' => (string) $row['region'],
                'is_enabled' => (int) $row['is_enabled'] === 1,
                'is_featured' => (int) $row['is_featured'] === 1,
                'states' => $states,
                'cover_media_id' => $row['cover_media_id'] !== null ? (int) $row['cover_media_id'] : null,
            ];
        }
        return ['rows' => $out, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function count(string $type): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM {entries} WHERE `type` = :t', ['t' => $type]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, ?string $type = null): ?array
    {
        $row = $this->db->first('entries', ['id' => $id]);
        return $row !== null && ($type === null || $row['type'] === $type) ? $row : null;
    }

    /** @return array<string, array<string, mixed>> language => translation row */
    public function translations(int $id): array
    {
        $out = [];
        foreach ($this->db->select('entry_translations', ['entry_id' => $id]) as $row) {
            $out[(string) $row['lang_code']] = $row;
        }
        return $out;
    }

    /** A new entry starts disabled, so nothing half-finished appears on the website. */
    public function create(string $type, ?int $userId): int
    {
        if (!EntryTypes::isType($type)) {
            throw new \InvalidArgumentException('Unknown entry type: ' . $type);
        }
        $now = $this->now();
        return $this->db->insert('entries', [
            'type' => $type,
            'status' => $type === 'project' ? 'ongoing' : ($type === 'publication' ? 'report' : ''),
            'published_on' => $this->clock->now()->format('Y-m-d'),
            'is_enabled' => 0,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @param EntryValues $values */
    public function save(int $id, array $values): void
    {
        $this->db->update('entries', [
            'expertise_id' => $values['expertise_id'],
            'region' => $values['region'],
            'status' => $values['status'],
            'start_date' => $values['start_date'],
            'end_date' => $values['end_date'],
            'published_on' => $values['published_on'],
            'beneficiaries' => $values['beneficiaries'],
            'donors' => $values['donors'],
            'cover_media_id' => $values['cover_media_id'],
            'file_media_id' => $values['file_media_id'],
            'related_id' => $values['related_id'] !== $id ? $values['related_id'] : null,
            'is_featured' => $values['is_featured'] ? 1 : 0,
            'is_enabled' => $values['is_enabled'] ? 1 : 0,
            'updated_at' => $this->now(),
        ], ['id' => $id]);
    }

    public function setEnabled(int $id, bool $enabled): void
    {
        $this->db->update('entries', ['is_enabled' => $enabled ? 1 : 0, 'updated_at' => $this->now()], ['id' => $id]);
    }

    /**
     * Saves one language and returns the previous slug when it changed (the caller then adds a redirect).
     *
     * @param array<string, string> $fields
     */
    public function saveTranslation(int $id, string $type, string $lang, array $fields, bool $published): ?string
    {
        $data = ['is_published' => $published ? 1 : 0];
        foreach (self::FIELDS as $column) {
            if (array_key_exists($column, $fields)) {
                $data[$column] = $fields[$column];
            }
        }
        $existing = $this->db->first('entry_translations', ['entry_id' => $id, 'lang_code' => $lang]);
        $this->db->update('entries', ['updated_at' => $this->now()], ['id' => $id]);
        if ($existing === null) {
            $this->db->insert('entry_translations', ['entry_id' => $id, 'entry_type' => $type, 'lang_code' => $lang] + $data);
            return null;
        }
        $this->db->update('entry_translations', $data, ['id' => (int) $existing['id']]);
        $old = (string) $existing['slug'];
        return isset($data['slug']) && $data['slug'] !== $old ? $old : null;
    }

    public function slugTaken(string $type, string $lang, string $slug, int $exceptId): bool
    {
        $row = $this->db->first('entry_translations', ['entry_type' => $type, 'lang_code' => $lang, 'slug' => $slug]);
        return $row !== null && (int) $row['entry_id'] !== $exceptId;
    }

    public function delete(int $id): void
    {
        $this->db->delete('entries', ['id' => $id]);
    }

    /** @return list<int> media ids of the gallery, in order */
    public function gallery(int $id): array
    {
        return array_map(static fn (array $r): int => (int) $r['media_id'], $this->db->all('SELECT `media_id` FROM {entry_media} WHERE `entry_id` = :id ORDER BY `sort_order`, `id`', ['id' => $id]));
    }

    /**
     * Replaces the gallery with these media ids, in this order (unknown or non-image ids are skipped).
     *
     * @param list<int> $mediaIds
     */
    public function setGallery(int $id, array $mediaIds): void
    {
        $this->db->transaction(function () use ($id, $mediaIds): void {
            $this->db->delete('entry_media', ['entry_id' => $id]);
            $sort = 0;
            foreach (array_values(array_unique($mediaIds)) as $mediaId) {
                $media = $this->db->first('media', ['id' => $mediaId]);
                if ($media === null || $media['kind'] !== 'image') {
                    continue;
                }
                $sort += 10;
                $this->db->insert('entry_media', ['entry_id' => $id, 'media_id' => $mediaId, 'sort_order' => $sort]);
            }
        });
    }

    /** @return array<int, string> project id => title (the "related project" picker) */
    public function projectOptions(string $lang): array
    {
        $out = [];
        $rows = $this->db->all("SELECT e.`id`, t.`title`, t.`lang_code` FROM {entries} e LEFT JOIN {entry_translations} t ON t.`entry_id` = e.`id` WHERE e.`type` = 'project' ORDER BY e.`published_on` DESC, e.`id` DESC");
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (!isset($out[$id]) || $row['lang_code'] === $lang) {
                $out[$id] = is_string($row['title']) && $row['title'] !== '' ? $row['title'] : '#' . $id;
            }
        }
        return $out;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, array<string, mixed>>> entry id => language => translation
     */
    private function translationsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $in = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $in[] = ':i' . $i;
            $params['i' . $i] = $id;
        }
        $out = [];
        foreach ($this->db->all('SELECT * FROM {entry_translations} WHERE `entry_id` IN (' . implode(',', $in) . ')', $params) as $row) {
            $out[(int) $row['entry_id']][(string) $row['lang_code']] = $row;
        }
        return $out;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
