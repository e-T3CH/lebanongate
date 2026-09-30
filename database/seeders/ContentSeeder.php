<?php

declare(strict_types=1);

namespace BMMatic\Database\Seeders;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;

/**
 * Public website content: pages, home sections, services, transmission types, process steps and stats, in every
 * language that has a file in database/seeders/content. Structure (keys, templates, order, flags) is defined here,
 * texts come from the language files. Never overwrites: missing rows and missing translations are added only.
 */
final class ContentSeeder
{
    /** key => [template, in_nav, nav_order] */
    public const PAGES = [
        'home' => ['home', 0, 0],
        'services' => ['services', 1, 1],
        'transmissions' => ['transmissions', 1, 2],
        'about' => ['about', 1, 3],
        'reviews' => ['reviews', 1, 4],
        'contact' => ['contact', 1, 5],
        'privacy' => ['legal', 0, 10],
        'cookies' => ['legal', 0, 11],
        'terms' => ['legal', 0, 12],
    ];

    /** Home sections in the approved order: type => [sort_order, is_locked]. Locked sections keep their position. */
    public const HOME_SECTIONS = [
        'topbar' => [0, 1],
        'header' => [1, 1],
        'hero' => [10, 0],
        'stats' => [20, 0],
        'services' => [30, 0],
        'process' => [40, 0],
        'transmissions' => [50, 0],
        'reviews' => [60, 0],
        'contact' => [70, 0],
        'footer' => [100, 1],
    ];

    /** key => [icon, show_in_menu] */
    public const SERVICES = [
        'diagnostics' => ['fa-solid fa-wave-square', 1],
        'overhaul' => ['fa-solid fa-gear', 1],
        'flush' => ['fa-solid fa-droplet', 1],
        'converter' => ['fa-solid fa-layer-group', 0],
        'mechatronic' => ['fa-solid fa-microchip', 0],
        'dsg' => ['fa-solid fa-wrench', 1],
    ];

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /** @return int rows added */
    public function run(): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $content = [];
        foreach ($this->db->select('languages', [], ['code']) as $row) {
            $file = __DIR__ . '/content/' . $row['code'] . '.php';
            if (is_file($file)) {
                $content[(string) $row['code']] = require $file;
            }
        }
        $added = 0;

        // Pages
        foreach (self::PAGES as $key => [$template, $inNav, $navOrder]) {
            $id = $this->idFor('pages', ['key' => $key]) ?? $this->db->insert('pages', ['key' => $key, 'template' => $template, 'is_enabled' => 1, 'in_nav' => $inNav, 'nav_order' => $navOrder, 'in_sitemap' => 1, 'updated_at' => $now]);
            foreach ($content as $lang => $c) {
                $t = $c['pages'][$key] ?? null;
                if (!is_array($t) || $this->idFor('page_translations', ['page_id' => $id, 'lang_code' => $lang]) !== null) {
                    continue;
                }
                $this->db->insert('page_translations', ['page_id' => $id, 'lang_code' => $lang] + self::pick($t, ['slug', 'nav_label', 'label', 'title', 'highlight', 'intro', 'body', 'meta_title', 'meta_description']));
                $added++;
            }
        }

        // Home sections
        $homeId = (int) $this->idFor('pages', ['key' => 'home']);
        foreach (self::HOME_SECTIONS as $type => [$sort, $locked]) {
            $id = $this->idFor('page_sections', ['page_id' => $homeId, 'type' => $type]) ?? $this->db->insert('page_sections', ['page_id' => $homeId, 'type' => $type, 'sort_order' => $sort, 'is_enabled' => 1, 'is_locked' => $locked]);
            foreach ($content as $lang => $c) {
                $t = $c['sections'][$type] ?? null;
                if (!is_array($t) || $this->idFor('page_section_translations', ['section_id' => $id, 'lang_code' => $lang]) !== null) {
                    continue;
                }
                $row = self::pick($t, ['label', 'title', 'highlight', 'intro']);
                $row['extra'] = isset($t['extra']) ? json_encode($t['extra'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;
                $this->db->insert('page_section_translations', ['section_id' => $id, 'lang_code' => $lang] + $row);
                $added++;
            }
        }

        // Services
        $sort = 0;
        foreach (self::SERVICES as $key => [$icon, $inMenu]) {
            $sort += 10;
            $id = $this->idFor('services', ['key' => $key]) ?? $this->db->insert('services', ['key' => $key, 'icon' => $icon, 'sort_order' => $sort, 'is_enabled' => 1, 'show_in_menu' => $inMenu, 'show_on_home' => 1, 'updated_at' => $now]);
            foreach ($content as $lang => $c) {
                $t = $c['services'][$key] ?? null;
                if (!is_array($t) || $this->idFor('service_translations', ['service_id' => $id, 'lang_code' => $lang]) !== null) {
                    continue;
                }
                $this->db->insert('service_translations', ['service_id' => $id, 'lang_code' => $lang] + self::pick($t, ['slug', 'title', 'menu_title', 'menu_sub', 'short_title', 'summary', 'body', 'meta_title', 'meta_description']));
                $added++;
            }
        }

        // Ordered lists without keys: seeded once, translations by position
        foreach (['transmission_types' => ['transmission_type', ['label', 'description']], 'process_steps' => ['process_step', ['title', 'text']], 'stats' => ['stat', ['value', 'label']]] as $table => [$singular, $fields]) {
            $ids = array_map(static fn (array $r): int => (int) $r['id'], $this->db->select($table, [], ['id'], ['sort_order' => 'ASC', 'id' => 'ASC']));
            if ($ids === []) {
                $count = max(array_map(static fn (array $c): int => count($c[$table] ?? []), $content ?: [[]]));
                for ($i = 0; $i < $count; $i++) {
                    $ids[] = $this->db->insert($table, ['sort_order' => ($i + 1) * 10, 'is_enabled' => 1]);
                    $added++;
                }
            }
            foreach ($content as $lang => $c) {
                foreach ($ids as $i => $id) {
                    $t = $c[$table][$i] ?? null;
                    if (!is_array($t) || $this->idFor($singular . '_translations', [$singular . '_id' => $id, 'lang_code' => $lang]) !== null) {
                        continue;
                    }
                    $this->db->insert($singular . '_translations', [$singular . '_id' => $id, 'lang_code' => $lang] + self::pick($t, $fields));
                    $added++;
                }
            }
        }
        return $added;
    }

    /** @param array<string, mixed> $where */
    private function idFor(string $table, array $where): ?int
    {
        $row = $this->db->first($table, $where, ['id']);
        return $row === null ? null : (int) $row['id'];
    }

    /**
     * @param array<string, mixed> $source
     * @param list<string> $fields
     * @return array<string, string>
     */
    private static function pick(array $source, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $v = $source[$f] ?? '';
            $out[$f] = is_scalar($v) ? (string) $v : '';
        }
        return $out;
    }
}
