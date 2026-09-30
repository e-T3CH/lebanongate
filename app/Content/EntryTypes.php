<?php

declare(strict_types=1);

namespace Gate\Content;

/**
 * The four kinds of entries (one `entries` table): what each type uses, which page lists it, and the fixed choice
 * lists (project status, publication kind, regions of Lebanon). Labels come from lang/{code}/site.php and admin.php.
 *
 * Columns per type:
 * - project:     expertise, region, status (planned|ongoing|completed), start/end date, beneficiaries, donors,
 *                location, cover, gallery
 * - news:        expertise, date, cover, gallery, related project
 * - publication: expertise, date, kind (in `status`: report|study|brochure|newsletter|policy|other), PDF, cover
 * - album:       date, cover, photos, related project
 */
final class EntryTypes
{
    public const TYPES = ['project', 'news', 'publication', 'album'];

    /** type => key of the page that lists it (and whose slug prefixes the entry URLs). */
    public const LIST_PAGES = [
        'project' => 'projects',
        'news' => 'news',
        'publication' => 'publications',
        'album' => 'gallery',
    ];

    public const PROJECT_STATUSES = ['planned', 'ongoing', 'completed'];
    public const PUBLICATION_KINDS = ['report', 'study', 'brochure', 'newsletter', 'policy', 'other'];

    /** Governorates of Lebanon, plus nationwide work. Stored as these keys; labels in site.regions.*. */
    public const REGIONS = ['akkar', 'north', 'baalbek-hermel', 'bekaa', 'keserwan-jbeil', 'mount-lebanon', 'beirut', 'south', 'nabatieh', 'national'];

    /** Font Awesome icon per type (admin menu and empty states). */
    public const ICONS = [
        'project' => 'fa-solid fa-diagram-project',
        'news' => 'fa-regular fa-newspaper',
        'publication' => 'fa-regular fa-file-pdf',
        'album' => 'fa-regular fa-images',
    ];

    /** Entries per page on the public list pages. */
    public const PER_PAGE = [
        'project' => 9,
        'news' => 9,
        'publication' => 12,
        'album' => 12,
    ];

    public static function isType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /** Does this type use the given feature? */
    public static function uses(string $type, string $feature): bool
    {
        return in_array($feature, match ($type) {
            'project' => ['expertise', 'region', 'status', 'dates', 'beneficiaries', 'donors', 'location', 'cover', 'gallery', 'featured'],
            'news' => ['expertise', 'cover', 'gallery', 'related', 'featured'],
            'publication' => ['expertise', 'kind', 'file', 'cover', 'featured'],
            'album' => ['cover', 'gallery', 'related'],
            default => [],
        }, true);
    }

    /** @return list<string> the values the `status` column may hold for a type ([] when it is not used) */
    public static function statuses(string $type): array
    {
        return match ($type) {
            'project' => self::PROJECT_STATUSES,
            'publication' => self::PUBLICATION_KINDS,
            default => [],
        };
    }

    /** Type of the entries listed by a page, or null for any other page. */
    public static function forListPage(string $pageKey): ?string
    {
        $type = array_search($pageKey, self::LIST_PAGES, true);
        return is_string($type) ? $type : null;
    }
}
