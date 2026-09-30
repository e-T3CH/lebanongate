<?php

declare(strict_types=1);

namespace Gate\Database\Seeders;

use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * Starting content of the website: pages (About with its child pages), home sections, areas of expertise, impact
 * counters, partners and the first projects and news, in every language that has a file in database/seeders/content.
 * Structure (keys, templates, order, flags) is defined here; texts come from the language files. [Bracketed] values
 * are placeholders for GATE Lebanon to confirm (Settings → Maintenance → Content check lists them).
 *
 * Never overwrites: only missing rows and missing translations are added, so the owner's edits are safe.
 */
final class ContentSeeder
{
    /** key => [template, parent key, in_nav, nav_order] */
    public const PAGES = [
        'home' => ['home', null, 0, 0],
        'about' => ['about', null, 1, 10],
        'who' => ['text', 'about', 1, 11],
        'mission' => ['text', 'about', 1, 12],
        'profile' => ['text', 'about', 1, 13],
        'expertise' => ['expertise', null, 1, 20],
        'projects' => ['projects', null, 1, 30],
        'news' => ['news', null, 1, 40],
        'publications' => ['publications', null, 1, 50],
        'gallery' => ['gallery', null, 0, 60],
        'partners' => ['partners', null, 0, 70],
        'contact' => ['contact', null, 1, 80],
        'privacy' => ['text', null, 0, 100],
        'cookies' => ['text', null, 0, 101],
        'terms' => ['text', null, 0, 102],
    ];

    /** Home sections in the approved order (docs/GATE-UI-STRUCTURE.md §3): type => sort_order. */
    public const HOME_SECTIONS = [
        'hero' => 10,
        'about' => 20,
        'expertise' => 30,
        'stats' => 40,
        'projects' => 50,
        'map' => 60,
        'news' => 70,
        'partners' => 80,
        'cta' => 90,
    ];

    /** key => icon (a symbol of the site's SVG sprite, see config/expertise-icons.php) */
    public const EXPERTISE = [
        'education' => 'book',
        'protection' => 'shield',
        'social-cohesion' => 'hands',
        'livelihoods' => 'briefcase',
        'emergency' => 'flame',
        'governance' => 'landmark',
    ];

    /** name => [kind, url] */
    public const PARTNERS = [
        'RET Germany' => ['partner', 'https://retgermany.org'],
        'BMZ' => ['donor', 'https://www.bmz.de'],
        'National Education Scouts' => ['partner', ''],
        'Lebanese Civil Defense' => ['partner', ''],
    ];

    /**
     * The first entries: key => [type, expertise key, region, status, start, end, date, beneficiaries, donors, featured].
     * Texts per language in the content files under 'entries'. Detected by their English slug.
     */
    public const ENTRIES = [
        'civil-defense' => ['project', 'emergency', 'akkar', 'ongoing', '2026-06-01', null, '2026-06-01', null, 'RET Germany · BMZ', 1],
        'sport' => ['project', 'social-cohesion', 'akkar', 'ongoing', '2026-07-01', null, '2026-07-01', 2600, 'RET Germany · BMZ', 1],
        'mosaic' => ['project', 'livelihoods', 'akkar', 'ongoing', '2026-09-01', null, '2026-09-01', null, 'RET Germany · BMZ', 1],
        'scouts' => ['project', 'protection', 'akkar', 'completed', '2026-08-01', '2026-08-31', '2026-08-01', 46, 'RET Germany · BMZ', 0],
        'news-mosaic' => ['news', 'livelihoods', 'akkar', '', null, null, '2026-09-01', null, '', 0],
        'news-camps' => ['news', 'protection', 'akkar', '', null, null, '2026-08-01', null, '', 0],
        'news-sport' => ['news', 'social-cohesion', 'akkar', '', null, null, '2026-07-01', null, '', 0],
        'news-civil-defense' => ['news', 'emergency', 'akkar', '', null, null, '2026-06-01', null, '', 0],
    ];

    /** News that belongs to a project: news key => project key. */
    private const RELATED = [
        'news-mosaic' => 'mosaic',
        'news-camps' => 'scouts',
        'news-sport' => 'sport',
        'news-civil-defense' => 'civil-defense',
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

        // Pages (parents first: the order of PAGES guarantees it)
        foreach (self::PAGES as $key => [$template, $parent, $inNav, $navOrder]) {
            $parentId = $parent !== null ? $this->idFor('pages', ['key' => $parent]) : null;
            $id = $this->idFor('pages', ['key' => $key]) ?? $this->db->insert('pages', ['key' => $key, 'template' => $template, 'parent_id' => $parentId, 'is_enabled' => 1, 'in_nav' => $inNav, 'nav_order' => $navOrder, 'in_sitemap' => 1, 'updated_at' => $now]);
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
        foreach (self::HOME_SECTIONS as $type => $sort) {
            $id = $this->idFor('page_sections', ['page_id' => $homeId, 'type' => $type]) ?? $this->db->insert('page_sections', ['page_id' => $homeId, 'type' => $type, 'sort_order' => $sort, 'is_enabled' => 1, 'is_locked' => 0]);
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

        // Areas of expertise
        $sort = 0;
        $expertiseIds = [];
        foreach (self::EXPERTISE as $key => $icon) {
            $sort += 10;
            $id = $this->idFor('expertise', ['key' => $key]) ?? $this->db->insert('expertise', ['key' => $key, 'icon' => $icon, 'sort_order' => $sort, 'is_enabled' => 1, 'show_on_home' => 1, 'updated_at' => $now]);
            $expertiseIds[$key] = $id;
            foreach ($content as $lang => $c) {
                $t = $c['expertise'][$key] ?? null;
                if (!is_array($t) || $this->idFor('expertise_translations', ['expertise_id' => $id, 'lang_code' => $lang]) !== null) {
                    continue;
                }
                $this->db->insert('expertise_translations', ['expertise_id' => $id, 'lang_code' => $lang] + self::pick($t, ['slug', 'title', 'summary', 'body', 'meta_title', 'meta_description']));
                $added++;
            }
        }

        // Impact counters: seeded once, translations by position
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $this->db->select('stats', [], ['id'], ['sort_order' => 'ASC', 'id' => 'ASC']));
        if ($ids === []) {
            $count = max(array_map(static fn (array $c): int => count($c['stats'] ?? []), $content ?: [[]]));
            for ($i = 0; $i < $count; $i++) {
                $ids[] = $this->db->insert('stats', ['sort_order' => ($i + 1) * 10, 'is_enabled' => 1]);
                $added++;
            }
        }
        foreach ($content as $lang => $c) {
            foreach ($ids as $i => $id) {
                $t = $c['stats'][$i] ?? null;
                if (!is_array($t) || $this->idFor('stat_translations', ['stat_id' => $id, 'lang_code' => $lang]) !== null) {
                    continue;
                }
                $this->db->insert('stat_translations', ['stat_id' => $id, 'lang_code' => $lang] + self::pick($t, ['value', 'label']));
                $added++;
            }
        }

        // Partners and donors (seeded once)
        if ((int) $this->db->scalar('SELECT COUNT(*) FROM {partners}') === 0) {
            $sort = 0;
            foreach (self::PARTNERS as $name => [$kind, $url]) {
                $sort += 10;
                $id = $this->db->insert('partners', ['name' => $name, 'kind' => $kind, 'url' => $url, 'sort_order' => $sort, 'is_enabled' => 1]);
                foreach ($content as $lang => $c) {
                    $description = $c['partners'][$name] ?? null;
                    if (is_string($description)) {
                        $this->db->insert('partner_translations', ['partner_id' => $id, 'lang_code' => $lang, 'description' => $description]);
                    }
                }
                $added++;
            }
        }

        // First projects and news (only when the English slug is not there yet)
        $entryIds = [];
        foreach (self::ENTRIES as $key => [$type, $area, $region, $status, $start, $end, $date, $beneficiaries, $donors, $featured]) {
            $slug = is_string($content['en']['entries'][$key]['slug'] ?? null) ? $content['en']['entries'][$key]['slug'] : null;
            $existing = $slug !== null ? $this->db->first('entry_translations', ['entry_type' => $type, 'lang_code' => 'en', 'slug' => $slug], ['entry_id']) : null;
            if ($existing !== null) {
                $entryIds[$key] = (int) $existing['entry_id'];
                continue;
            }
            $id = $this->db->insert('entries', [
                'type' => $type, 'expertise_id' => $expertiseIds[$area], 'region' => $region, 'status' => $status,
                'start_date' => $start, 'end_date' => $end, 'published_on' => $date, 'beneficiaries' => $beneficiaries,
                'donors' => $donors, 'is_featured' => $featured, 'is_enabled' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $entryIds[$key] = $id;
            foreach ($content as $lang => $c) {
                $t = $c['entries'][$key] ?? null;
                if (is_array($t)) {
                    $this->db->insert('entry_translations', ['entry_id' => $id, 'entry_type' => $type, 'lang_code' => $lang, 'is_published' => 1] + self::pick($t, ['slug', 'title', 'summary', 'body', 'location', 'meta_title', 'meta_description']));
                }
            }
            $added++;
        }
        foreach (self::RELATED as $news => $project) {
            $row = isset($entryIds[$news], $entryIds[$project]) ? $this->db->first('entries', ['id' => $entryIds[$news]], ['related_id']) : null;
            if ($row !== null && $row['related_id'] === null) {
                $this->db->update('entries', ['related_id' => $entryIds[$project]], ['id' => $entryIds[$news]]);
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
