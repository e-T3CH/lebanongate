<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Services\Settings;

/**
 * Structured data and crawler files: schema.org NGO JSON-LD from the settings, XML sitemaps per language with hreflang
 * alternates, robots.txt.
 */
final class Seo
{
    /**
     * schema.org NGO (an Organization type) for every page: who publishes the site.
     *
     * @return array<string, mixed>
     */
    public static function organization(Settings $settings, string $baseUrl, string $lang, string $logoUrl, string $description): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            '@id' => rtrim($baseUrl, '/') . '/#organization',
            'name' => $settings->string('site.name', 'GATE Lebanon'),
            'url' => rtrim($baseUrl, '/') . '/' . $lang . '/',
            'logo' => $logoUrl,
            'image' => $logoUrl,
            'description' => $description,
            'inLanguage' => $lang,
            'areaServed' => ['@type' => 'Country', 'name' => 'Lebanon'],
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => self::real($settings->string('contact.street')),
                'addressLocality' => trim($settings->string('contact.area') . ', ' . $settings->string('contact.city'), ', '),
                'addressCountry' => 'LB',
            ]),
        ];
        $legal = self::real($settings->string('org.legal_name'));
        if ($legal !== '') {
            $data['legalName'] = $legal;
        }
        $founded = $settings->string('org.founded');
        if (preg_match('/^\d{4}$/', $founded) === 1) {
            $data['foundingDate'] = $founded;
        }
        $phone = self::real($settings->string('contact.phone'));
        if ($phone !== '') {
            $data['telephone'] = $phone;
        }
        $email = self::real($settings->string('contact.email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $data['email'] = $email;
        }
        $lat = $settings->string('contact.latitude');
        $lng = $settings->string('contact.longitude');
        if (is_numeric($lat) && is_numeric($lng)) {
            $data['location'] = ['@type' => 'Place', 'geo' => ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng]];
        }
        $sameAs = [];
        foreach (['facebook', 'instagram', 'linkedin', 'x', 'youtube'] as $network) {
            $url = $settings->string('social.' . $network . '.url');
            if (preg_match('#^https://[^\s\[\]]+$#', $url) === 1) {
                $sameAs[] = $url;
            }
        }
        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }
        return $data;
    }

    /** A setting value without placeholders: "[+961 …]" is not published. */
    private static function real(string $value): string
    {
        return str_contains($value, '[') ? '' : trim($value);
    }

    /**
     * JSON for a <script type="application/ld+json"> block (safe against "</script>").
     *
     * @param array<string, mixed> $data
     */
    public static function jsonLd(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<array{loc: string, lastmod: string, alternates: array<string, string>}> $entries absolute URLs
     */
    public static function sitemap(array $entries): string
    {
        $x = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($entries as $entry) {
            $out .= '  <url><loc>' . $x($entry['loc']) . '</loc>';
            if ($entry['lastmod'] !== '') {
                $out .= '<lastmod>' . $x($entry['lastmod']) . '</lastmod>';
            }
            foreach ($entry['alternates'] as $lang => $href) {
                $out .= '<xhtml:link rel="alternate" hreflang="' . $x($lang) . '" href="' . $x($href) . '"/>';
            }
            $out .= "</url>\n";
        }
        return $out . "</urlset>\n";
    }

    /** @param list<string> $sitemapUrls */
    public static function sitemapIndex(array $sitemapUrls, string $lastmod): string
    {
        $x = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($sitemapUrls as $url) {
            $out .= '  <sitemap><loc>' . $x($url) . '</loc><lastmod>' . $x($lastmod) . "</lastmod></sitemap>\n";
        }
        return $out . "</sitemapindex>\n";
    }

    /** robots.txt: everything public is crawlable; the admin path is never revealed. */
    public static function robots(string $baseUrl): string
    {
        return "User-agent: *\nDisallow: /install\nDisallow: /*/consent\nDisallow: /*/newsletter/\n\nSitemap: " . rtrim($baseUrl, '/') . "/sitemap.xml\n";
    }
}
