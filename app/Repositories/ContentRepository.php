<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Database;

/**
 * Read access to the public website content (pages, sections, services, transmission types, process steps, stats,
 * partners) in one language, falling back to the default language for missing translations. Results are cached for
 * the request.
 *
 * @phpstan-type Page array{id: int, key: string, template: string, in_nav: bool, nav_order: int, in_sitemap: bool, updated_at: string, lang: string, slug: string, nav_label: string, label: string, title: string, highlight: string, intro: string, body: string, meta_title: string, meta_description: string}
 * @phpstan-type Section array{id: int, type: string, sort_order: int, is_enabled: bool, is_locked: bool, settings: array<string, mixed>, label: string, title: string, highlight: string, intro: string, extra: array<string, mixed>}
 * @phpstan-type Service array{id: int, key: string, icon: string, sort_order: int, show_in_menu: bool, show_on_home: bool, updated_at: string, lang: string, slug: string, title: string, menu_title: string, menu_sub: string, short_title: string, summary: string, body: string, meta_title: string, meta_description: string}
 */
final class ContentRepository
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly Database $db, private readonly string $defaultLang)
    {
    }

    /** @return list<Page> enabled pages, navigation order */
    public function pages(string $lang): array
    {
        return $this->remember('pages.' . $lang, function () use ($lang): array {
            $rows = $this->db->all('SELECT * FROM {pages} WHERE `is_enabled` = 1 ORDER BY `nav_order`, `id`');
            $translations = $this->translations('page_translations', 'page_id', $lang, true);
            $out = [];
            foreach ($rows as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t === null) {
                    continue;
                }
                $out[] = [
                    'id' => (int) $row['id'], 'key' => (string) $row['key'], 'template' => (string) $row['template'],
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

    /** @return Page|null */
    public function pageBySlug(string $slug, string $lang): ?array
    {
        foreach ($this->pages($lang) as $page) {
            if ($page['slug'] === $slug && $page['lang'] === $lang) {
                return $page;
            }
        }
        return null;
    }

    /**
     * Slug of a page per language (only languages that have their own translation).
     *
     * @param list<string> $langs
     * @return array<string, string>
     */
    public function pageSlugs(string $key, array $langs): array
    {
        $out = [];
        foreach ($langs as $lang) {
            $page = $this->page($key, $lang);
            if ($page !== null && $page['lang'] === $lang) {
                $out[$lang] = $page['slug'];
            }
        }
        return $out;
    }

    /**
     * Sections of a page in display order (see SectionOrder), including disabled ones (callers filter).
     *
     * @return list<array<string, mixed>> Section rows
     */
    public function sections(string $pageKey, string $lang): array
    {
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
                    'settings' => is_array($settings) ? $settings : [],
                ] + self::strings($t, ['label', 'title', 'highlight', 'intro']) + ['extra' => is_array($extra) ? $extra : []];
            }
            return \Gate\Services\SectionOrder::sort($out);
        });
    }

    /** @return list<Service> */
    public function services(string $lang): array
    {
        return $this->remember('services.' . $lang, function () use ($lang): array {
            $rows = $this->db->all('SELECT * FROM {services} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`');
            $translations = $this->translations('service_translations', 'service_id', $lang, true);
            $out = [];
            foreach ($rows as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t === null) {
                    continue;
                }
                $out[] = [
                    'id' => (int) $row['id'], 'key' => (string) $row['key'], 'icon' => (string) $row['icon'], 'sort_order' => (int) $row['sort_order'],
                    'show_in_menu' => (int) $row['show_in_menu'] === 1, 'show_on_home' => (int) $row['show_on_home'] === 1, 'updated_at' => (string) $row['updated_at'],
                ] + self::strings($t, ['lang', 'slug', 'title', 'menu_title', 'menu_sub', 'short_title', 'summary', 'body', 'meta_title', 'meta_description']);
            }
            return $out;
        });
    }

    /** @return Service|null */
    public function serviceBySlug(string $slug, string $lang): ?array
    {
        foreach ($this->services($lang) as $service) {
            if ($service['slug'] === $slug && $service['lang'] === $lang) {
                return $service;
            }
        }
        return null;
    }

    /**
     * @param list<string> $langs
     * @return array<string, string> lang => slug
     */
    public function serviceSlugs(int $serviceId, array $langs): array
    {
        $out = [];
        foreach ($langs as $lang) {
            foreach ($this->services($lang) as $service) {
                if ($service['id'] === $serviceId && $service['lang'] === $lang) {
                    $out[$lang] = $service['slug'];
                }
            }
        }
        return $out;
    }

    /** @return list<array{id: int, label: string, description: string}> */
    public function transmissionTypes(string $lang): array
    {
        return $this->simpleList('transmission_types', 'transmission_type', ['label', 'description'], $lang);
    }

    /** @return list<array{id: int, title: string, text: string}> */
    public function processSteps(string $lang): array
    {
        return $this->simpleList('process_steps', 'process_step', ['title', 'text'], $lang);
    }

    /** @return list<array{id: int, value: string, label: string}> */
    public function stats(string $lang): array
    {
        return $this->simpleList('stats', 'stat', ['value', 'label'], $lang);
    }

    /** @return list<array{id: int, name: string, url: string, logo: string, description: string}> */
    public function partners(string $lang): array
    {
        return $this->remember('partners.' . $lang, function () use ($lang): array {
            $translations = $this->translations('partner_translations', 'partner_id', $lang);
            $out = [];
            foreach ($this->db->all('SELECT * FROM {partners} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $row) {
                $out[] = ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'url' => (string) $row['url'], 'logo' => (string) $row['logo'], 'description' => (string) ($translations[(int) $row['id']]['description'] ?? '')];
            }
            return $out;
        });
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function simpleList(string $table, string $singular, array $fields, string $lang): array
    {
        /** @var list<array<string, mixed>> */
        return $this->remember($table . '.' . $lang, function () use ($table, $singular, $fields, $lang): array {
            $translations = $this->translations($singular . '_translations', $singular . '_id', $lang);
            $out = [];
            foreach ($this->db->all('SELECT `id` FROM {' . $table . '} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $row) {
                $t = $translations[(int) $row['id']] ?? null;
                if ($t !== null) {
                    $out[] = ['id' => (int) $row['id']] + self::strings($t, $fields);
                }
            }
            return $out;
        });
    }

    /**
     * Translations keyed by parent id: the requested language, else the default language (marked with its lang).
     *
     * @return array<int, array<string, mixed>>
     */
    private function translations(string $table, string $parentColumn, string $lang, bool $publishedOnly = false): array
    {
        // Pages and services have a publish state per language: a draft translation falls back to the default language.
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
    private static function strings(array $row, array $fields): array
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
