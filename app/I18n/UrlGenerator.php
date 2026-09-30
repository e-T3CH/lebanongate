<?php

declare(strict_types=1);

namespace BMMatic\I18n;

/** Absolute and localized URLs, canonical links and hreflang alternates. */
final class UrlGenerator
{
    /**
     * @param list<string> $enabledCodes in display order
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly array $enabledCodes,
        private readonly string $defaultCode,
    ) {
    }

    public function base(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    /** Absolute URL for a site path ("/admin-x/login"). */
    public function to(string $path): string
    {
        return $this->base() . '/' . ltrim($path, '/');
    }

    /** Localized path "/{lang}{rest}" where rest starts with "/". */
    public static function localizedPath(string $lang, string $rest): string
    {
        $rest = '/' . ltrim($rest, '/');
        return '/' . $lang . $rest;
    }

    public function localized(string $lang, string $rest): string
    {
        return $this->base() . self::localizedPath($lang, $rest);
    }

    /** Canonical URL: the localized page without query string. */
    public function canonical(string $lang, string $rest): string
    {
        return $this->localized($lang, (string) strtok($rest, '?'));
    }

    /**
     * hreflang alternates for every enabled language plus x-default (the default language).
     * Returns an empty list when only one language is enabled.
     *
     * @return list<array{hreflang: string, href: string}>
     */
    public function alternates(string $rest): array
    {
        if (count($this->enabledCodes) < 2) {
            return [];
        }
        $rest = (string) strtok($rest, '?');
        $links = [];
        foreach ($this->enabledCodes as $code) {
            $links[] = ['hreflang' => $code, 'href' => $this->localized($code, $rest)];
        }
        $links[] = ['hreflang' => 'x-default', 'href' => $this->localized($this->defaultCode, $rest)];
        return $links;
    }
}
