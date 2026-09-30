<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Content\EntryTypes;
use Gate\Repositories\ContentRepository;
use Gate\Repositories\EntryRepository;

/**
 * Public URLs with translated slugs, and the way back from a path to what it shows:
 *
 *   /{lang}/                                  home
 *   /{lang}/{page}                            a page (about, expertise, projects, news, contact …)
 *   /{lang}/{about}/{child}                   a child page (who we are, mission & vision, profile)
 *   /{lang}/{expertise}/{area}                one area of expertise
 *   /{lang}/{projects|news|…}/{entry}         one project, article, publication or album
 *   /{lang}/{publications}/{entry}/download   the PDF of a publication
 *
 * Also builds the per-language alternates of a route (hreflang links and the language selector).
 *
 * @phpstan-type Route array{type: 'page'|'expertise'|'entry'|'download', key: string, id: int|null}
 */
final class SiteUrls
{
    /**
     * @param list<string> $enabledLangs display order
     */
    public function __construct(
        private readonly ContentRepository $content,
        private readonly EntryRepository $entries,
        private readonly array $enabledLangs,
        private readonly string $defaultLang,
        private readonly string $baseUrl,
    ) {
    }

    public function base(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    /** @return list<string> */
    public function langs(): array
    {
        return $this->enabledLangs;
    }

    public function defaultLang(): string
    {
        return $this->defaultLang;
    }

    public function absolute(string $path): string
    {
        return str_starts_with($path, 'http') ? $path : $this->base() . '/' . ltrim($path, '/');
    }

    public function page(string $key, string $lang, string $fragment = ''): string
    {
        $page = $this->content->page($key, $lang);
        if ($page === null || $page['key'] === 'home' || $page['slug'] === '') {
            $path = '/' . $lang . '/';
        } else {
            $parent = $page['parent_key'] !== '' ? $this->content->page($page['parent_key'], $lang) : null;
            $path = '/' . $lang . '/' . ($parent !== null ? $parent['slug'] . '/' : '') . $page['slug'];
        }
        return $path . ($fragment !== '' ? '#' . $fragment : '');
    }

    public function expertise(int $id, string $lang): string
    {
        $area = $this->content->expertiseById($id, $lang);
        return $area === null ? $this->page('expertise', $lang) : rtrim($this->page('expertise', $lang), '/') . '/' . $area['slug'];
    }

    /** URL of an entry from its type and slug (the slug of the page language, as the list gave it). */
    public function entry(string $type, string $slug, string $lang): string
    {
        return rtrim($this->page(EntryTypes::LIST_PAGES[$type] ?? 'home', $lang), '/') . '/' . $slug;
    }

    public function entryById(int $id, string $lang): ?string
    {
        $entry = $this->entries->find($id, $lang);
        return $entry === null ? null : $this->entry($entry['type'], $entry['slug'], $lang);
    }

    public function download(string $slug, string $lang): string
    {
        return $this->entry('publication', $slug, $lang) . '/download';
    }

    /**
     * A list page with filters as query string ("?region=akkar&page=2"); empty values are left out.
     *
     * @param array<string, string|int|null> $query
     */
    public function listPage(string $key, string $lang, array $query = []): string
    {
        $query = array_filter($query, static fn ($v): bool => $v !== null && $v !== '' && $v !== 0);
        return $this->page($key, $lang) . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * Resolves the part after the language prefix ("/", "/contact", "/projects/sport-in-akkar").
     *
     * @return Route|null
     */
    public function resolve(string $lang, string $rest): ?array
    {
        $rest = trim($rest, '/');
        if ($rest === '') {
            return $this->content->page('home', $lang) !== null ? ['type' => 'page', 'key' => 'home', 'id' => null] : null;
        }
        $segments = explode('/', $rest);
        $count = count($segments);
        if ($count > 3) {
            return null;
        }
        $top = $this->content->pageBySlug($segments[0], $lang);
        if ($top === null || $top['key'] === 'home') {
            return null;
        }
        if ($count === 1) {
            return ['type' => 'page', 'key' => $top['key'], 'id' => null];
        }
        $type = EntryTypes::forListPage($top['key']);
        if ($count === 3) {
            if ($type === 'publication' && $segments[2] === 'download') {
                $entry = $this->entries->bySlug('publication', $lang, $segments[1]);
                return $entry !== null && $entry['file_media_id'] !== null ? ['type' => 'download', 'key' => 'publication', 'id' => $entry['id']] : null;
            }
            return null;
        }
        $child = $this->content->pageBySlug($segments[1], $lang, $top['key']);
        if ($child !== null) {
            return ['type' => 'page', 'key' => $child['key'], 'id' => null];
        }
        if ($top['key'] === 'expertise') {
            $area = $this->content->expertiseBySlug($segments[1], $lang);
            return $area !== null ? ['type' => 'expertise', 'key' => 'expertise', 'id' => $area['id']] : null;
        }
        if ($type !== null) {
            $entry = $this->entries->bySlug($type, $lang, $segments[1]);
            return $entry !== null ? ['type' => 'entry', 'key' => $type, 'id' => $entry['id']] : null;
        }
        return null;
    }

    /**
     * The same route in every enabled language that has its own published translation: lang => path.
     *
     * @param Route $route
     * @return array<string, string>
     */
    public function alternates(array $route): array
    {
        $out = [];
        foreach ($this->enabledLangs as $lang) {
            if ($route['type'] === 'page') {
                if ($route['key'] === 'home' || $this->ownPage($route['key'], $lang)) {
                    $out[$lang] = $this->page($route['key'], $lang);
                }
            } elseif ($route['type'] === 'expertise') {
                $area = $this->content->expertiseById((int) $route['id'], $lang);
                if ($area !== null && $area['lang'] === $lang && $this->ownPage('expertise', $lang)) {
                    $out[$lang] = $this->expertise((int) $route['id'], $lang);
                }
            } elseif ($route['type'] === 'entry') {
                $slugs = $this->entries->slugs((int) $route['id'], [$lang]);
                if (isset($slugs[$lang]) && $this->ownPage(EntryTypes::LIST_PAGES[$route['key']] ?? '', $lang)) {
                    $out[$lang] = $this->entry($route['key'], $slugs[$lang], $lang);
                }
            }
        }
        return $out;
    }

    /**
     * hreflang links (absolute) including x-default; empty with fewer than two languages.
     *
     * @param array<string, string> $alternates lang => path
     * @return list<array{hreflang: string, href: string}>
     */
    public function hreflang(array $alternates): array
    {
        if (count($alternates) < 2) {
            return [];
        }
        $links = [];
        foreach ($alternates as $lang => $path) {
            $links[] = ['hreflang' => $lang, 'href' => $this->absolute($path)];
        }
        $default = $alternates[$this->defaultLang] ?? reset($alternates);
        $links[] = ['hreflang' => 'x-default', 'href' => $this->absolute($default)];
        return $links;
    }

    /** Does the page exist in this very language (and its parent too)? A fallback translation has no URL of its own. */
    private function ownPage(string $key, string $lang): bool
    {
        $page = $this->content->page($key, $lang);
        if ($page === null || $page['lang'] !== $lang) {
            return false;
        }
        return $page['parent_key'] === '' || $this->ownPage($page['parent_key'], $lang);
    }
}
