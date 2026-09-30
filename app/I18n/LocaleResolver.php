<?php

declare(strict_types=1);

namespace BMMatic\I18n;

/**
 * Chooses the visitor's language:
 *  - URL prefix /en/ /fr/ /nl/ decides for every public page;
 *  - on "/" the remembered choice (functional cookie) wins, then browser detection on the first visit only
 *    (when enabled in settings), then the default language.
 */
final class LocaleResolver
{
    public const COOKIE = 'bm_lang';

    /**
     * @param list<string> $enabledCodes
     */
    public function __construct(
        private readonly array $enabledCodes,
        private readonly string $defaultCode,
        private readonly bool $detectBrowser,
    ) {
    }

    /** Language for the site root redirect. */
    public function forRoot(?string $cookieValue, ?string $acceptLanguage): string
    {
        if ($cookieValue !== null && $cookieValue !== '') {
            // A remembered choice exists, so this is not a first visit: never auto-detect again.
            return $this->isEnabled($cookieValue) ? $cookieValue : $this->defaultCode;
        }
        if ($this->detectBrowser && $acceptLanguage !== null && $acceptLanguage !== '') {
            return self::negotiate($acceptLanguage, $this->enabledCodes) ?? $this->defaultCode;
        }
        return $this->defaultCode;
    }

    public function isEnabled(string $code): bool
    {
        return in_array($code, $this->enabledCodes, true);
    }

    /**
     * Result of matching a request path against the language prefix.
     *
     * @return array{status: 'ok'|'redirect'|'not_found'|'root', lang: string, rest: string}
     *   ok: served in "lang"; redirect: go to /{lang}{rest}; not_found: unknown prefix; root: "/" (use forRoot)
     */
    public function matchPath(string $path): array
    {
        if ($path === '' || $path === '/') {
            return ['status' => 'root', 'lang' => $this->defaultCode, 'rest' => '/'];
        }
        if (preg_match('#^/([a-z]{2})(/.*)?$#', $path, $m) !== 1) {
            return ['status' => 'not_found', 'lang' => $this->defaultCode, 'rest' => $path];
        }
        $code = $m[1];
        $rest = $m[2] ?? '';
        if (!in_array($code, LanguageRules::SUPPORTED, true)) {
            return ['status' => 'not_found', 'lang' => $this->defaultCode, 'rest' => $path];
        }
        if (!$this->isEnabled($code)) {
            // A disabled language: send visitors to the same page in the default language.
            return ['status' => 'redirect', 'lang' => $this->defaultCode, 'rest' => $rest === '' ? '/' : $rest];
        }
        if ($rest === '') {
            return ['status' => 'redirect', 'lang' => $code, 'rest' => '/'];
        }
        return ['status' => 'ok', 'lang' => $code, 'rest' => $rest];
    }

    /**
     * Picks the best enabled language from an Accept-Language header (RFC 9110 q-values, primary subtags).
     *
     * @param list<string> $enabledCodes
     */
    public static function negotiate(string $header, array $enabledCodes): ?string
    {
        $candidates = [];
        foreach (explode(',', $header) as $index => $part) {
            $pieces = explode(';', trim($part));
            $tag = strtolower(trim($pieces[0]));
            if ($tag === '' || preg_match('/^([a-z]{1,8})(-[a-z0-9]{1,8})*$|^\*$/', $tag) !== 1) {
                continue;
            }
            $q = 1.0;
            foreach (array_slice($pieces, 1) as $param) {
                if (preg_match('/^\s*q\s*=\s*([01](?:\.\d{0,3})?)\s*$/i', $param, $m) === 1) {
                    $q = (float) $m[1];
                }
            }
            if ($q <= 0.0) {
                continue;
            }
            $primary = explode('-', $tag)[0];
            $candidates[] = ['code' => $primary, 'q' => $q, 'index' => $index];
        }
        usort($candidates, static fn (array $a, array $b): int => [$b['q'], $a['index']] <=> [$a['q'], $b['index']]);
        foreach ($candidates as $c) {
            if (in_array($c['code'], $enabledCodes, true)) {
                return $c['code'];
            }
        }
        return null;
    }
}
