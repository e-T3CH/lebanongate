<?php

declare(strict_types=1);

namespace Gate\Ops;

use Gate\Core\Database;
use Gate\Repositories\LanguageRepository;
use Gate\Services\MediaLibrary;
use Gate\Services\Settings;

/**
 * `bin/console content:check`: everything that is not finished yet, so nothing half-done goes live.
 *
 *  - [bracket] placeholders left in settings and content (the mock-up's sample values, the legal templates' notes);
 *  - translations that are missing or not published, per language (visitors would see the default language);
 *  - images in use without alt text in a language;
 *  - pages, areas of expertise and entries (projects, news, publications, albums) without an SEO title or description.
 *
 * Findings are grouped: 'settings' for the site-wide ones, then one group per enabled language.
 */
final class ContentCheck
{
    /** A [placeholder]: brackets around short text that is not JSON (no quotes or braces inside). */
    public const PLACEHOLDER = '/\[([^\[\]\n"{}<>]{1,120})\]/u';

    public function __construct(
        private readonly Database $db,
        private readonly Settings $settings,
        private readonly LanguageRepository $languages,
        private readonly MediaLibrary $media,
    ) {
    }

    /** @return array<string, list<string>> group => findings */
    public function run(): array
    {
        $codes = $this->languages->enabledCodes();
        $out = ['settings' => $this->settingsFindings()];
        foreach ($codes as $lang) {
            $out[$lang] = [];
        }
        $this->pages($codes, $out);
        $this->expertise($codes, $out);
        $this->entries($codes, $out);
        $this->sections($codes, $out);
        $this->lists($codes, $out);
        $this->altTexts($codes, $out);
        return $out;
    }

    /** @return list<string> */
    public static function placeholders(string $text): array
    {
        preg_match_all(self::PLACEHOLDER, strip_tags($text), $m);
        return array_values(array_unique($m[0]));
    }

    /** @return list<string> */
    private function settingsFindings(): array
    {
        $out = [];
        foreach ($this->db->all("SELECT `key`, `value`, `type` FROM {settings} WHERE `is_secret` = 0 AND `value` LIKE '%[%' ORDER BY `key`") as $row) {
            $key = (string) $row['key'];
            if ($row['type'] === 'json') {
                continue;
            }
            $found = self::placeholders((string) $row['value']);
            if ($found !== []) {
                $out[] = $key . ': placeholder ' . implode(' ', $found);
            }
        }
        return $out;
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function pages(array $codes, array &$out): void
    {
        $pages = $this->db->all('SELECT `id`, `key` FROM {pages} WHERE `is_enabled` = 1 ORDER BY `nav_order`, `id`');
        foreach ($pages as $page) {
            $rows = $this->byLang($this->db->all('SELECT * FROM {page_translations} WHERE `page_id` = :id', ['id' => (int) $page['id']]));
            foreach ($codes as $lang) {
                $this->translation('page "' . $page['key'] . '"', $rows[$lang] ?? null, ['title', 'intro', 'body', 'meta_title', 'meta_description', 'label', 'highlight'], $out[$lang]);
            }
        }
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function expertise(array $codes, array &$out): void
    {
        foreach ($this->db->all('SELECT `id`, `key` FROM {expertise} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $area) {
            $rows = $this->byLang($this->db->all('SELECT * FROM {expertise_translations} WHERE `expertise_id` = :id', ['id' => (int) $area['id']]));
            foreach ($codes as $lang) {
                $this->translation('expertise "' . $area['key'] . '"', $rows[$lang] ?? null, ['title', 'summary', 'body', 'meta_title', 'meta_description'], $out[$lang]);
            }
        }
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function entries(array $codes, array &$out): void
    {
        foreach ($this->db->all('SELECT `id`, `type` FROM {entries} WHERE `is_enabled` = 1 ORDER BY `type`, `published_on` DESC, `id` DESC') as $entry) {
            $rows = $this->byLang($this->db->all('SELECT * FROM {entry_translations} WHERE `entry_id` = :id', ['id' => (int) $entry['id']]));
            foreach ($codes as $lang) {
                $this->translation($entry['type'] . ' #' . $entry['id'], $rows[$lang] ?? null, ['title', 'summary', 'body', 'location', 'meta_title', 'meta_description'], $out[$lang]);
            }
        }
    }

    /**
     * @param array<string, mixed>|null $row
     * @param list<string> $fields
     * @param list<string> $findings
     */
    private function translation(string $what, ?array $row, array $fields, array &$findings): void
    {
        if ($row === null) {
            $findings[] = $what . ': not translated (visitors see the default language)';
            return;
        }
        if ((int) ($row['is_published'] ?? 1) !== 1) {
            $findings[] = $what . ': not published in this language';
        }
        if (trim((string) ($row['title'] ?? '')) === '') {
            $findings[] = $what . ': title is empty';
        }
        foreach (['meta_title' => 'SEO title', 'meta_description' => 'SEO description'] as $field => $label) {
            if (array_key_exists($field, $row) && trim((string) $row[$field]) === '') {
                $findings[] = $what . ': ' . $label . ' is empty';
            }
        }
        $placeholders = [];
        foreach ($fields as $field) {
            $placeholders = array_merge($placeholders, self::placeholders((string) ($row[$field] ?? '')));
        }
        if ($placeholders !== []) {
            $findings[] = $what . ': placeholder ' . implode(' ', array_unique($placeholders));
        }
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function sections(array $codes, array &$out): void
    {
        $sql = 'SELECT s.`id`, s.`type`, p.`key` AS `page` FROM {page_sections} s JOIN {pages} p ON p.`id` = s.`page_id` WHERE s.`is_enabled` = 1 AND p.`is_enabled` = 1 ORDER BY p.`id`, s.`sort_order`';
        foreach ($this->db->all($sql) as $section) {
            $rows = $this->byLang($this->db->all('SELECT * FROM {page_section_translations} WHERE `section_id` = :id', ['id' => (int) $section['id']]));
            $what = 'section "' . $section['type'] . '" on ' . $section['page'];
            foreach ($codes as $lang) {
                $row = $rows[$lang] ?? null;
                if ($row === null) {
                    $out[$lang][] = $what . ': not translated';
                    continue;
                }
                $found = [];
                foreach (['label', 'title', 'highlight', 'intro', 'extra'] as $field) {
                    $found = array_merge($found, self::placeholders((string) ($row[$field] ?? '')));
                }
                if ($found !== []) {
                    $out[$lang][] = $what . ': placeholder ' . implode(' ', array_unique($found));
                }
            }
        }
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function lists(array $codes, array &$out): void
    {
        $lists = [
            'key figure' => ['stats', 'stat_translations', 'stat_id', ['value', 'label'], 'label'],
        ];
        foreach ($lists as $label => [$table, $translations, $fk, $fields, $required]) {
            foreach ($this->db->all('SELECT `id` FROM {' . $table . '} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $i => $item) {
                $rows = $this->byLang($this->db->all('SELECT * FROM {' . $translations . '} WHERE `' . $fk . '` = :id', ['id' => (int) $item['id']]));
                foreach ($codes as $lang) {
                    $row = $rows[$lang] ?? null;
                    $what = $label . ' ' . ($i + 1);
                    if ($row === null || trim((string) ($row[$required] ?? '')) === '') {
                        $out[$lang][] = $what . ': not translated';
                        continue;
                    }
                    $found = [];
                    foreach ($fields as $field) {
                        $found = array_merge($found, self::placeholders((string) ($row[$field] ?? '')));
                    }
                    if ($found !== []) {
                        $out[$lang][] = $what . ': placeholder ' . implode(' ', array_unique($found));
                    }
                }
            }
        }
        foreach ($this->db->all('SELECT `id`, `name`, `url` FROM {partners} WHERE `is_enabled` = 1 ORDER BY `sort_order`, `id`') as $partner) {
            $found = array_merge(self::placeholders((string) $partner['name']), self::placeholders((string) $partner['url']));
            if ($found !== []) {
                $out['settings'][] = 'partner "' . $partner['name'] . '": placeholder ' . implode(' ', array_unique($found));
            }
        }
    }

    /**
     * @param list<string> $codes
     * @param array<string, list<string>> $out
     */
    private function altTexts(array $codes, array &$out): void
    {
        foreach ($this->db->all("SELECT `id`, `filename`, `original_name` FROM {media} WHERE `kind` = 'image' ORDER BY `id`") as $media) {
            $url = MediaLibrary::url((string) $media['filename']);
            $used = $this->media->usage((int) $media['id'], $url, $this->settings);
            // The favicon is decoration for the browser tab; everything else is seen on the page.
            if (array_diff($used, ['favicon']) === []) {
                continue;
            }
            $alts = [];
            foreach ($this->db->all('SELECT `lang_code`, `alt` FROM {media_translations} WHERE `media_id` = :id', ['id' => (int) $media['id']]) as $row) {
                $alts[(string) $row['lang_code']] = trim((string) $row['alt']);
            }
            foreach ($codes as $lang) {
                if (($alts[$lang] ?? '') === '') {
                    $out[$lang][] = 'image "' . $media['original_name'] . '" (used: ' . implode(', ', $used) . '): alt text is missing';
                }
            }
        }
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
}
