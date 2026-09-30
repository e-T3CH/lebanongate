<?php

declare(strict_types=1);

namespace BMMatic\Site;

use BMMatic\Repositories\ContentRepository;

/**
 * Public URLs with translated slugs: /{lang}/ (home), /{lang}/{page-slug}, /{lang}/{services-slug}/{service-slug}.
 * Also resolves an incoming path back to a page or service, and builds the per-language alternates of a route
 * (hreflang links and the language selector).
 *
 * @phpstan-type Route array{type: 'page'|'service', key: string, service_id: int|null}
 */
final class SiteUrls
{
    /**
     * @param list<string> $enabledLangs display order
     */
    public function __construct(
        private readonly ContentRepository $content,
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
        return $this->base() . '/' . ltrim($path, '/');
    }

    public function page(string $key, string $lang, string $fragment = ''): string
    {
        $page = $this->content->page($key, $lang);
        $path = $page === null || $page['slug'] === '' ? '/' . $lang . '/' : '/' . $lang . '/' . $page['slug'];
        return $path . ($fragment !== '' ? '#' . $fragment : '');
    }

    public function service(int $serviceId, string $lang): string
    {
        foreach ($this->content->services($lang) as $service) {
            if ($service['id'] === $serviceId) {
                return rtrim($this->page('services', $lang), '/') . '/' . $service['slug'];
            }
        }
        return $this->page('services', $lang);
    }

    /**
     * Resolves the part after the language prefix ("/", "/contact", "/services/diagnostics").
     *
     * @return Route|null
     */
    public function resolve(string $lang, string $rest): ?array
    {
        $rest = trim($rest, '/');
        if ($rest === '') {
            return $this->content->page('home', $lang) !== null ? ['type' => 'page', 'key' => 'home', 'service_id' => null] : null;
        }
        $segments = explode('/', $rest);
        if (count($segments) === 1) {
            $page = $this->content->pageBySlug($segments[0], $lang);
            return $page !== null && $page['key'] !== 'home' ? ['type' => 'page', 'key' => $page['key'], 'service_id' => null] : null;
        }
        if (count($segments) === 2) {
            $services = $this->content->page('services', $lang);
            if ($services !== null && $services['slug'] === $segments[0] && $services['lang'] === $lang) {
                $service = $this->content->serviceBySlug($segments[1], $lang);
                if ($service !== null) {
                    return ['type' => 'service', 'key' => 'service', 'service_id' => $service['id']];
                }
            }
        }
        return null;
    }

    /**
     * The same route in every enabled language that has its own translation: lang => path.
     *
     * @param Route $route
     * @return array<string, string>
     */
    public function alternates(array $route): array
    {
        $out = [];
        foreach ($this->enabledLangs as $lang) {
            if ($route['type'] === 'service') {
                $slugs = $this->content->serviceSlugs((int) $route['service_id'], [$lang]);
                if (isset($slugs[$lang]) && $this->content->pageSlugs('services', [$lang]) !== []) {
                    $out[$lang] = $this->service((int) $route['service_id'], $lang);
                }
            } elseif ($route['key'] === 'home' || $this->content->pageSlugs($route['key'], [$lang]) !== []) {
                $out[$lang] = $this->page($route['key'], $lang);
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
}
