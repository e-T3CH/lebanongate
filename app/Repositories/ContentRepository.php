<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Database;
use Gate\Services\MediaLibrary;
use Gate\Services\SectionOrder;

/**
 * Read access to the public website content in one language, falling back to the default language for missing or
 * unpublished translations: pages (About has child pages), home sections, areas of expertise, impact counters,
 * partners and media. Entries (projects, news, publications, albums) are read by EntryRepository. Results are cached
 * for the request.
 *
 * @phpstan-type Page array{id: int, key: string, template: string, parent_id: int|null, parent_key: string, hero_media_id: int|null, in_nav: bool, nav_order: int, in_sitemap: bool, updated_at: string, lang: string, slug: string, nav_label: string, label: string, title: string, highlight: string, intro: string, body: string, meta_title: string, meta_description: string}
 * @phpstan-type Section array{id: int, type: string, sort_order: int, is_enabled: bool, is_locked: bool, media_id: int|null, settings: array<string, mixed>, label: string, title: string, highlight: string, intro: string, extra: array<string, mixed>}
 * @phpstan-type Expertise array{id: int, key: string, icon: string, cover_media_id: int|null, sort_order: int, show_on_home: bool, updated_at: string, lang: string, slug: string, title: string, summary: string, body: string, meta_title: string, meta_description: string}
 * @phpstan-type MediaItem array{id: int, kind: string, url: string, width: int, height: int, alt: string, original_name: string, size: int, extension: string, pages: int}
 */
final class ContentRepository
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly Database $db, private readonly string $defaultLang)
    {
    }

    public function defaultLang(): string
    {
        return $this->defaultLang;
    }

    /** @return list<Page> enabled pages, navigation order */
    public function pages(string $lang): array
    {
        /** @var list<Page> */
        return $this->remember('pages.' . $lang, function () use ($lang): array {
            $rows = $this->db->all('SELECT * FROM {pages} WHERE `is_enabled` = 1 ORDER BY `nav_order`, `id`');
            $keys = [];
            foreach ($rows as $row) {
                $keys[(int) $row['id']] = (string) $row['key'];
            }
            $translations = $this->translations('page_translations', 'page_id', $lang, true);
            $out = [];
            foreach ($rows as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t === null) {
                    continue;
                }
                $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
                // A child of a disabled parent is not reachable either.
                if ($parentId !== null && !isset($keys[$parentId])) {
                    continue;
                }
                $out[] = [
                    'id' => (int) $row['id'], 'key' => (string) $row['key'], 'template' => (string) $row['template'],
                    'parent_id' => $parentId, 'parent_key' => $parentId !== null ? $keys[$parentId] : '',
                    'hero_media_id' => $row['hero_media_id'] !== null ? (int) $row['hero_media_id'] : null,
                    'in_nav' => (int) $row['in_nav'] === 1, 'nav_order' => (int) $row['nav_order'], 'in_sitemap' => (int) $row['in_sitemap'] === 1,
                    'updated_at' => (string) $row['updated_at'],
                ] + self::strings($t, ['lang', 'slug', 'nav_label', 'label', 'title', 'highlight', 'intro', 'body', 'meta_title', 'meta_description']);
            }
            return $out;
        });
    }

    /** @return Page|null */
    public function page(string $key, string $lang): ?array
    {
        foreach ($this->pages($lang) as $page) {
            if ($page['key'] === $key) {
                return $page;
            }
        }
        return null;
    }

    /**
     * A page by its slug under a parent ('' = top level). A page translated into this language answers to its own
     * slug only; a page without a translation here answers to the default language's slug (the link it gets).
     *
     * @return Page|null
     */
    public function pageBySlug(string $slug, string $lang, string $parentKey = ''): ?array
    {
        foreach ($this->pages($lang) as $page) {
            if ($page['slug'] === $slug && $page['parent_key'] === $parentKey) {
                return $page;
            }
        }
        return null;
    }

    /** @return list<Page> the enabled child pages of a page, in navigation order */
    public function children(string $parentKey, string $lang): array
    {
        return array_values(array_filter($this->pages($lang), static fn (array $p): bool => $p['parent_key'] === $parentKey));
    }

    /**
     * Sections of a page in display order (see SectionOrder), including disabled ones (callers filter).
     *
     * @return list<Section>
     */
    public function sections(string $pageKey, string $lang): array
    {
        /** @var list<Section> */
        return $this->remember('sections.' . $pageKey . '.' . $lang, function () use ($pageKey, $lang): array {
            $rows = $this->db->all('SELECT s.* FROM {page_sections} s JOIN {pages} p ON p.`id` = s.`page_id` WHERE p.`key` = :k', ['k' => $pageKey]);
            $translations = $this->translations('page_section_translations', 'section_id', $lang);
            $out = [];
            foreach ($rows as $row) {
                $t = $translations[(int) $row['id']] ?? [];
                $settings = is_string($row['settings'] ?? null) ? json_decode($row['settings'], true) : null;
                $extra = is_string($t['extra'] ?? null) ? json_decode($t['extra'], true) : null;
                $out[] = [
                    'id' => (int) $row['id'], 'type' => (string) $row['type'], 'sort_order' => (int) $row['sort_order'],
                    'is_enabled' => (int) $row['is_enabled'] === 1, 'is_locked' => (int) $row['is_locked'] === 1,
                    'media_id' => $row['media_id'] !== null ? (int) $row['media_id'] : null,
                    'settings' => is_array($settings) ? $settings : [],
                ] + self::strings($t, ['label', 'title', 'highlight', 'intro']) + ['extra' => is_array($extra) ? $extra : []];
            }
            return SectionOrder::sort($out);
        });
    }

    /** @return Section|null */
    public function section(string $pageKey, string $type, string $lang): ?array
    {
        foreach ($this->sections($pageKey, $lang) as $section) {
            if ($section['type'] === $type) {
                return $section;
            }
        }
        return null;
    }

    /** @return list<Expertise> */
    public function expertise(string $lang): array
    {
        /** @var list<Expertise> */
        return $this->remember('expertise.' . $lang, function () use ($lang): array {
            $rows = $this->db->all('SELECT * FROM {expertise} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`');
            $translations = $this->translations('expertise_translations', 'expertise_id', $lang, true);
            $out = [];
            foreach ($rows as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t === null) {
                    continue;
                }
                $out[] = [
                    'id' => (int) $row['id'], 'key' => (string) $row['key'], 'icon' => (string) $row['icon'],
                    'cover_media_id' => $row['cover_media_id'] !== null ? (int) $row['cover_media_id'] : null,
                    'sort_order' => (int) $row['sort_order'], 'show_on_home' => (int) $row['show_on_home'] === 1, 'updated_at' => (string) $row['updated_at'],
                ] + self::strings($t, ['lang', 'slug', 'title', 'summary', 'body', 'meta_title', 'meta_description']);
            }
            return $out;
        });
    }

    /** @return Expertise|null */
    public function expertiseById(int $id, string $lang): ?array
    {
        foreach ($this->expertise($lang) as $area) {
            if ($area['id'] === $id) {
                return $area;
            }
        }
        return null;
    }

    /** @return Expertise|null */
    public function expertiseBySlug(string $slug, string $lang): ?array
    {
        foreach ($this->expertise($lang) as $area) {
            if ($area['slug'] === $slug) {
                return $area;
            }
        }
        return null;
    }

    /** @return Expertise|null */
    public function expertiseByKey(string $key, string $lang): ?array
    {
        foreach ($this->expertise($lang) as $area) {
            if ($area['key'] === $key) {
                return $area;
            }
        }
        return null;
    }

    /** @return list<array{id: int, value: string, label: string}> */
    public function stats(string $lang): array
    {
        /** @var list<array{id: int, value: string, label: string}> */
        return $this->remember('stats.' . $lang, function () use ($lang): array {
            $translations = $this->translations('stat_translations', 'stat_id', $lang);
            $out = [];
            foreach ($this->db->all('SELECT `id` FROM {stats} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t !== null) {
                    $out[] = ['id' => (int) $row['id']] + self::strings($t, ['value', 'label']);
                }
            }
            return $out;
        });
    }

    /** @return list<array{id: int, name: string, kind: string, url: string, logo: MediaItem|null, description: string}> */
    public function partners(string $lang): array
    {
        /** @var list<array{id: int, name: string, kind: string, url: string, logo: MediaItem|null, description: string}> */
        return $this->remember('partners.' . $lang, function () use ($lang): array {
            $translations = $this->translations('partner_translations', 'partner_id', $lang);
            $rows = $this->db->all('SELECT * FROM {partners} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`');
            $media = $this->media(array_map(static fn (array $r): ?int => $r['logo_media_id'] !== null ? (int) $r['logo_media_id'] : null, $rows), $lang);
            $out = [];
            foreach ($rows as $row) {
                $logoId = $row['logo_media_id'] !== null ? (int) $row['logo_media_id'] : null;
                $out[] = [
                    'id' => (int) $row['id'], 'name' => (string) $row['name'], 'kind' => (string) $row['kind'], 'url' => (string) $row['url'],
                    'logo' => $logoId !== null ? ($media[$logoId] ?? null) : null,
                    'description' => is_string($translations[(int) $row['id']]['description'] ?? null) ? (string) $translations[(int) $row['id']]['description'] : '',
                ];
            }
            return $out;
        });
    }

    /**
     * Media rows by id with the alt text of a language (default language as fallback). Unknown ids are absent.
     *
     * @param list<int|null> $ids
     * @return array<int, MediaItem>
     */
    public function media(array $ids, string $lang): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (?int $id): bool => $id !== null && $id > 0)));
        if ($ids === []) {
            return [];
        }
        $params = ['a' => $lang, 'b' => $this->defaultLang];
        $in = [];
        foreach ($ids as $i => $id) {
            $in[] = ':m' . $i;
            $params['m' . $i] = $id;
        }
        $rows = $this->db->all('SELECT * FROM {media} WHERE `id` IN (' . implode(',', $in) . ')', array_diff_key($params, ['a' => 1, 'b' => 1]));
        $alts = [];
        foreach ($this->db->all('SELECT * FROM {media_translations} WHERE `lang_code` IN (:a, :b) AND `media_id` IN (' . implode(',', $in) . ')', $params) as $row) {
            $id = (int) $row['media_id'];
            if ($row['lang_code'] === $lang || !isset($alts[$id]) || $alts[$id] === '') {
                $alts[$id] = (string) $row['alt'];
            }
        }
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $out[$id] = [
                'id' => $id,
                'kind' => (string) $row['kind'],
                'url' => MediaLibrary::url((string) $row['filename']),
                'width' => (int) $row['width'],
                'height' => (int) $row['height'],
                'alt' => $alts[$id] ?? '',
                'original_name' => (string) $row['original_name'],
                'size' => (int) $row['size'],
                'extension' => (string) $row['extension'],
                'pages' => (int) $row['pages'],
            ];
        }
        return $out;
    }

    /** @return MediaItem|null */
    public function mediaItem(?int $id, string $lang): ?array
    {
        return $id === null ? null : ($this->media([$id], $lang)[$id] ?? null);
    }

    /**
     * Translations keyed by parent id: the requested language, else the default language (marked with its lang).
     *
     * @return array<int, array<string, mixed>>
     */
    private function translations(string $table, string $parentColumn, string $lang, bool $publishedOnly = false): array
    {
        // Pages and expertise have a publish state per language: a draft translation falls back to the default language.
        $where = $publishedOnly ? ' AND `is_published` = 1' : '';
        $rows = $this->db->all('SELECT * FROM {' . $table . '} WHERE `lang_code` IN (:a, :b)' . $where, ['a' => $lang, 'b' => $this->defaultLang]);
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row[$parentColumn];
            $row['lang'] = (string) $row['lang_code'];
            if ($row['lang_code'] === $lang || !isset($out[$id])) {
                $out[$id] = $row;
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $fields
     * @return array<string, string>
     */
    public static function strings(array $row, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[$f] = is_scalar($row[$f] ?? null) ? (string) $row[$f] : '';
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
