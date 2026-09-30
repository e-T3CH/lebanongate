<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * Write access to the website content for the admin panel. Reading for the website stays in ContentRepository
 * (cached, one language, falls back to the default language); this class works per entity with every language.
 *
 * Simple ordered lists (the impact counters) share one set of methods, because they differ only in their table and
 * their translated fields. Projects, news, publications and albums are written by EntryAdminRepository.
 */
final class ContentAdminRepository
{
    /** table => [singular, translated fields] for the ordered lists. */
    public const LISTS = [
        'stats' => ['stat', ['value', 'label']],
    ];
    public const PAGE_FIELDS = ['slug', 'nav_label', 'label', 'title', 'highlight', 'intro', 'body', 'meta_title', 'meta_description'];
    public const EXPERTISE_FIELDS = ['slug', 'title', 'summary', 'body', 'meta_title', 'meta_description'];
    public const PARTNER_KINDS = ['donor', 'partner'];
    public const SECTION_FIELDS = ['label', 'title', 'highlight', 'intro'];

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    // ------------------------------------------------------------------------------------------------- pages

    /** @return list<array<string, mixed>> */
    public function pages(): array
    {
        return $this->db->all('SELECT * FROM {pages} ORDER BY `nav_order`, `id`');
    }

    /** @return array<string, mixed>|null */
    public function page(int $id): ?array
    {
        return $this->db->first('pages', ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public function pageByKey(string $key): ?array
    {
        return $this->db->first('pages', ['key' => $key]);
    }

    /**
     * Translations of one page: language => row (missing languages are absent).
     *
     * @return array<string, array<string, mixed>>
     */
    public function pageTranslations(int $id): array
    {
        return $this->byLang($this->db->select('page_translations', ['page_id' => $id]));
    }

    /** @param array{is_enabled: bool, in_nav: bool, nav_order: int, in_sitemap: bool, hero_media_id: int|null} $values */
    public function savePage(int $id, array $values): void
    {
        $this->db->update('pages', [
            'is_enabled' => $values['is_enabled'] ? 1 : 0,
            'in_nav' => $values['in_nav'] ? 1 : 0,
            'nav_order' => $values['nav_order'],
            'in_sitemap' => $values['in_sitemap'] ? 1 : 0,
            'hero_media_id' => $values['hero_media_id'],
            'updated_at' => $this->now(),
        ], ['id' => $id]);
    }

    /** Is the slug used by another page with the same parent in this language? (Children live under their parent.) */
    public function pageSlugTaken(string $lang, string $slug, int $exceptPageId): bool
    {
        $page = $this->page($exceptPageId);
        $parent = $page !== null && $page['parent_id'] !== null ? (int) $page['parent_id'] : null;
        $row = $this->db->one(
            'SELECT t.`page_id` FROM {page_translations} t JOIN {pages} p ON p.`id` = t.`page_id`
             WHERE t.`lang_code` = :lang AND t.`slug` = :slug AND t.`page_id` <> :id AND ' . ($parent === null ? 'p.`parent_id` IS NULL' : 'p.`parent_id` = :parent'),
            ['lang' => $lang, 'slug' => $slug, 'id' => $exceptPageId] + ($parent === null ? [] : ['parent' => $parent])
        );
        return $row !== null;
    }

    /**
     * Saves one language of a page and returns the previous slug when it changed (the caller then adds a redirect).
     *
     * @param array<string, string> $fields
     */
    public function savePageTranslation(int $id, string $lang, array $fields, bool $published): ?string
    {
        return $this->saveTranslation('page_translations', 'page_id', $id, $lang, $fields, self::PAGE_FIELDS, $published);
    }

    public function slugTaken(string $table, string $lang, string $slug, int $exceptParentId, string $parentColumn): bool
    {
        $row = $this->db->first($table, ['lang_code' => $lang, 'slug' => $slug]);
        return $row !== null && (int) $row[$parentColumn] !== $exceptParentId;
    }

    // ------------------------------------------------------------------------------------------------ sections

    /** @return list<array<string, mixed>> sections of a page in stored order */
    public function sections(int $pageId): array
    {
        return $this->db->all('SELECT * FROM {page_sections} WHERE `page_id` = :p ORDER BY `sort_order`, `id`', ['p' => $pageId]);
    }

    /** @return array<string, array<string, mixed>> */
    public function sectionTranslations(int $sectionId): array
    {
        return $this->byLang($this->db->select('page_section_translations', ['section_id' => $sectionId]));
    }

    /** @return array<string, mixed>|null */
    public function section(int $id): ?array
    {
        return $this->db->first('page_sections', ['id' => $id]);
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, mixed>|null $extra structured texts of the section (about points, map notes); null keeps them
     */
    public function saveSectionTranslation(int $id, string $lang, array $fields, ?array $extra = null): void
    {
        if ($extra !== null) {
            $fields['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        $this->saveTranslation('page_section_translations', 'section_id', $id, $lang, $fields, $extra !== null ? [...self::SECTION_FIELDS, 'extra'] : self::SECTION_FIELDS, null);
    }

    /**
     * Images of a section (shared by all languages): the main image and, in the settings, further ones.
     *
     * @param array<string, mixed> $settings
     */
    public function saveSectionMedia(int $id, ?int $mediaId, array $settings): void
    {
        $this->db->update('page_sections', ['media_id' => $mediaId, 'settings' => $settings === [] ? null : json_encode($settings, JSON_THROW_ON_ERROR)], ['id' => $id]);
    }

    /**
     * New order and enabled state of the sections of one page. Locked sections keep their place and stay enabled.
     *
     * @param list<int> $orderedIds
     * @param list<int> $enabledIds
     */
    public function reorderSections(int $pageId, array $orderedIds, array $enabledIds): void
    {
        $sort = 0;
        foreach ($orderedIds as $id) {
            $section = $this->db->first('page_sections', ['id' => $id, 'page_id' => $pageId]);
            if ($section === null) {
                continue;
            }
            $sort += 10;
            $locked = (int) $section['is_locked'] === 1;
            $this->db->update('page_sections', [
                'sort_order' => $locked ? (int) $section['sort_order'] : $sort,
                'is_enabled' => $locked || in_array($id, $enabledIds, true) ? 1 : 0,
            ], ['id' => $id]);
        }
        $this->touchPage($pageId);
    }

    public function touchPage(int $pageId): void
    {
        $this->db->update('pages', ['updated_at' => $this->now()], ['id' => $pageId]);
    }

    // ---------------------------------------------------------------------------------------------- expertise

    /** @return list<array<string, mixed>> */
    public function expertiseList(): array
    {
        return $this->db->all('SELECT * FROM {expertise} ORDER BY `sort_order`, `id`');
    }

    /** @return array<string, mixed>|null */
    public function expertise(int $id): ?array
    {
        return $this->db->first('expertise', ['id' => $id]);
    }

    /** @return array<string, array<string, mixed>> */
    public function expertiseTranslations(int $id): array
    {
        return $this->byLang($this->db->select('expertise_translations', ['expertise_id' => $id]));
    }

    /** @param array{icon: string, is_enabled: bool, show_on_home: bool, sort_order: int, cover_media_id: int|null} $values */
    public function saveExpertise(int $id, array $values): void
    {
        $this->db->update('expertise', [
            'icon' => $values['icon'],
            'is_enabled' => $values['is_enabled'] ? 1 : 0,
            'show_on_home' => $values['show_on_home'] ? 1 : 0,
            'sort_order' => $values['sort_order'],
            'cover_media_id' => $values['cover_media_id'],
            'updated_at' => $this->now(),
        ], ['id' => $id]);
    }

    public function createExpertise(string $key, string $icon): int
    {
        $max = (int) $this->db->scalar('SELECT COALESCE(MAX(`sort_order`), 0) FROM {expertise}');
        return $this->db->insert('expertise', [
            'key' => $key,
            'icon' => $icon,
            'sort_order' => $max + 10,
            'is_enabled' => 0,
            'show_on_home' => 1,
            'updated_at' => $this->now(),
        ]);
    }

    public function deleteExpertise(int $id): void
    {
        $this->db->delete('expertise', ['id' => $id]);
    }

    public function expertiseKeyTaken(string $key): bool
    {
        return $this->db->first('expertise', ['key' => $key]) !== null;
    }

    /** @param array<string, string> $fields */
    public function saveExpertiseTranslation(int $id, string $lang, array $fields, bool $published): ?string
    {
        return $this->saveTranslation('expertise_translations', 'expertise_id', $id, $lang, $fields, self::EXPERTISE_FIELDS, $published);
    }

    /** @return array<int, string> id => title in the given language (else the key): pickers in the entry editors */
    public function expertiseOptions(string $lang): array
    {
        $out = [];
        foreach ($this->db->all('SELECT e.`id`, e.`key`, t.`title` FROM {expertise} e LEFT JOIN {expertise_translations} t ON t.`expertise_id` = e.`id` AND t.`lang_code` = :l ORDER BY e.`sort_order`, e.`id`', ['l' => $lang]) as $row) {
            $out[(int) $row['id']] = is_string($row['title']) && $row['title'] !== '' ? $row['title'] : (string) $row['key'];
        }
        return $out;
    }

    // -------------------------------------------------------------------------------------------- simple lists

    /** @return list<array<string, mixed>> */
    public function listItems(string $table): array
    {
        $this->assertList($table);
        return $this->db->all('SELECT * FROM {' . $table . '} ORDER BY `sort_order`, `id`');
    }

    /** @return array<int, array<string, array<string, mixed>>> item id => language => translation */
    public function listTranslations(string $table): array
    {
        [$singular] = self::LISTS[$this->assertList($table)];
        $out = [];
        foreach ($this->db->all('SELECT * FROM {' . $singular . '_translations}') as $row) {
            $out[(int) $row[$singular . '_id']][(string) $row['lang_code']] = $row;
        }
        return $out;
    }

    public function createListItem(string $table): int
    {
        $this->assertList($table);
        $max = (int) $this->db->scalar('SELECT COALESCE(MAX(`sort_order`), 0) FROM {' . $table . '}');
        return $this->db->insert($table, ['sort_order' => $max + 10, 'is_enabled' => 1]);
    }

    public function deleteListItem(string $table, int $id): void
    {
        $this->assertList($table);
        $this->db->delete($table, ['id' => $id]);
    }

    /** @param array<string, string> $fields */
    public function saveListTranslation(string $table, int $id, string $lang, array $fields): void
    {
        [$singular, $columns] = self::LISTS[$this->assertList($table)];
        $this->saveTranslation($singular . '_translations', $singular . '_id', $id, $lang, $fields, $columns, null);
    }

    /**
     * @param list<int> $orderedIds
     * @param list<int> $enabledIds
     */
    public function reorderList(string $table, array $orderedIds, array $enabledIds): void
    {
        $this->assertList($table);
        $sort = 0;
        foreach ($orderedIds as $id) {
            if ($this->db->first($table, ['id' => $id]) === null) {
                continue;
            }
            $sort += 10;
            $this->db->update($table, ['sort_order' => $sort, 'is_enabled' => in_array($id, $enabledIds, true) ? 1 : 0], ['id' => $id]);
        }
    }

    // --------------------------------------------------------------------------------------------- partners

    /** @return list<array<string, mixed>> */
    public function partners(): array
    {
        return $this->db->all('SELECT * FROM {partners} ORDER BY `sort_order`, `id`');
    }

    /** @return array<int, array<string, array<string, mixed>>> */
    public function partnerTranslations(): array
    {
        $out = [];
        foreach ($this->db->all('SELECT * FROM {partner_translations}') as $row) {
            $out[(int) $row['partner_id']][(string) $row['lang_code']] = $row;
        }
        return $out;
    }

    public function createPartner(string $name, string $kind = 'partner'): int
    {
        $max = (int) $this->db->scalar('SELECT COALESCE(MAX(`sort_order`), 0) FROM {partners}');
        return $this->db->insert('partners', ['name' => $name, 'kind' => in_array($kind, self::PARTNER_KINDS, true) ? $kind : 'partner', 'url' => '', 'sort_order' => $max + 10, 'is_enabled' => 1]);
    }

    /** @param array{name: string, kind: string, url: string, logo_media_id: int|null, is_enabled: bool, sort_order?: int} $values */
    public function savePartner(int $id, array $values): void
    {
        $data = [
            'name' => $values['name'],
            'kind' => in_array($values['kind'], self::PARTNER_KINDS, true) ? $values['kind'] : 'partner',
            'url' => $values['url'],
            'logo_media_id' => $values['logo_media_id'],
            'is_enabled' => $values['is_enabled'] ? 1 : 0,
        ];
        if (isset($values['sort_order'])) {
            $data['sort_order'] = $values['sort_order'];
        }
        $this->db->update('partners', $data, ['id' => $id]);
    }

    public function deletePartner(int $id): void
    {
        $this->db->delete('partners', ['id' => $id]);
    }

    public function savePartnerTranslation(int $id, string $lang, string $description): void
    {
        $this->saveTranslation('partner_translations', 'partner_id', $id, $lang, ['description' => $description], ['description'], null);
    }

    // ------------------------------------------------------------------------------------------------ helpers

    /**
     * Insert or update one translation row. Returns the previous slug when a slug field changed.
     *
     * @param array<string, string> $fields
     * @param list<string> $allowed
     */
    private function saveTranslation(string $table, string $parentColumn, int $parentId, string $lang, array $fields, array $allowed, ?bool $published): ?string
    {
        $data = [];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $fields)) {
                $data[$column] = $fields[$column];
            }
        }
        if ($published !== null) {
            $data['is_published'] = $published ? 1 : 0;
        }
        $existing = $this->db->first($table, [$parentColumn => $parentId, 'lang_code' => $lang]);
        if ($existing === null) {
            $this->db->insert($table, [$parentColumn => $parentId, 'lang_code' => $lang] + $data);
            return null;
        }
        $this->db->update($table, $data, ['id' => (int) $existing['id']]);
        $oldSlug = is_string($existing['slug'] ?? null) ? $existing['slug'] : null;
        return $oldSlug !== null && isset($data['slug']) && $data['slug'] !== $oldSlug ? $oldSlug : null;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    private function byLang(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['lang_code']] = $row;
        }
        return $out;
    }

    private function assertList(string $table): string
    {
        if (!isset(self::LISTS[$table])) {
            throw new \InvalidArgumentException('Unknown content list: ' . $table);
        }
        return $table;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
