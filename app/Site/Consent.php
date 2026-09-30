<?php

declare(strict_types=1);

namespace BMMatic\Site;

/**
 * Cookie consent: the visitor's choice is stored in the functional cookie `bm_consent` as
 * "v{version}.{a|n}.{unix time}" (a = analytics accepted, n = necessary only). A choice for an older consent version
 * counts as no choice, so the banner is shown again after the policy changes. Analytics scripts are only rendered when
 * the current choice accepts analytics and a provider is configured.
 */
final class Consent
{
    public const COOKIE = 'bm_consent';
    public const LIFETIME = 15552000; // 180 days
    public const PROVIDERS = ['none', 'ga4', 'plausible'];

    private function __construct(public readonly ?bool $analytics, public readonly int $version)
    {
    }

    public static function fromCookie(?string $value, int $currentVersion): self
    {
        if ($value !== null && preg_match('/^v(\d{1,4})\.([an])\.\d{9,11}$/', $value, $m) === 1 && (int) $m[1] === $currentVersion) {
            return new self($m[2] === 'a', $currentVersion);
        }
        return new self(null, $currentVersion);
    }

    public static function cookieValue(bool $analytics, int $version, int $time): string
    {
        return 'v' . $version . '.' . ($analytics ? 'a' : 'n') . '.' . $time;
    }

    public function hasChoice(): bool
    {
        return $this->analytics !== null;
    }

    public function allowsAnalytics(): bool
    {
        return $this->analytics === true;
    }

    /**
     * What to load for the configured provider when analytics is allowed.
     *
     * @return array{script_src: list<string>, connect_src: list<string>, img_src: list<string>, scripts: list<array{src: string, attrs: array<string, string>}>, inline: string}|null
     */
    public static function analyticsTags(string $provider, string $ga4Id, string $plausibleDomain, string $plausibleSrc): ?array
    {
        if ($provider === 'ga4' && preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4Id) === 1) {
            return [
                'script_src' => ['https://www.googletagmanager.com'],
                'connect_src' => ['https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com'],
                'img_src' => ['https://*.google-analytics.com', 'https://www.googletagmanager.com'],
                'scripts' => [['src' => 'https://www.googletagmanager.com/gtag/js?id=' . $ga4Id, 'attrs' => []]],
                'inline' => 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . json_encode($ga4Id, JSON_HEX_TAG) . ',{anonymize_ip:true});',
            ];
        }
        if ($provider === 'plausible' && preg_match('/^[a-z0-9.-]{3,253}$/', $plausibleDomain) === 1 && preg_match('#^https://[a-z0-9.-]+(/[A-Za-z0-9._/-]*)?$#', $plausibleSrc) === 1) {
            $origin = (string) preg_replace('#^(https://[^/]+).*$#', '$1', $plausibleSrc);
            return [
                'script_src' => [$origin],
                'connect_src' => [$origin],
                'img_src' => [],
                'scripts' => [['src' => $plausibleSrc, 'attrs' => ['data-domain' => $plausibleDomain]]],
                'inline' => '',
            ];
        }
        return null;
    }

    /**
     * Names of analytics cookies removed when consent is withdrawn (GA4 sets _ga and _ga_<id>).
     *
     * @param array<mixed> $cookies
     * @return list<string>
     */
    public static function analyticsCookies(array $cookies): array
    {
        return array_values(array_filter(array_keys($cookies), static fn ($name): bool => is_string($name) && preg_match('/^(_ga|_ga_[A-Z0-9]+|_gid|_gat.*)$/', $name) === 1));
    }
}
