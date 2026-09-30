<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Services\Settings;

/**
 * Structured data and crawler files: schema.org AutoRepair (a LocalBusiness type) JSON-LD from the settings,
 * XML sitemaps per language with hreflang alternates, robots.txt.
 */
final class Seo
{
    private const DAYS = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];

    /**
     * schema.org AutoRepair + LocalBusiness for the home page (and every page, as the organisation of the site).
     *
     * @return array<string, mixed>
     */
    public static function localBusiness(Settings $settings, string $baseUrl, string $lang, string $logoUrl, string $description): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => ['AutoRepair', 'LocalBusiness'],
            '@id' => rtrim($baseUrl, '/') . '/#business',
            'name' => $settings->string('site.name', 'GATE Lebanon'),
            'url' => rtrim($baseUrl, '/') . '/' . $lang . '/',
            'image' => $logoUrl,
            'logo' => $logoUrl,
            'description' => $description,
            'inLanguage' => $lang,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->string('contact.street'),
                'postalCode' => $settings->string('contact.postcode'),
                'addressLocality' => $settings->string('contact.city'),
                'addressCountry' => 'BE',
            ],
        ];
        $phone = $settings->string('contact.phone');
        if ($phone !== '' && !str_contains($phone, '[')) {
            $data['telephone'] = $phone;
        }
        $email = $settings->string('contact.email');
        if (filter_var(trim($email, '[]'), FILTER_VALIDATE_EMAIL) !== false) {
            $data['email'] = trim($email, '[]');
        }
        $lat = $settings->string('contact.latitude');
        $lng = $settings->string('contact.longitude');
        if (is_numeric($lat) && is_numeric($lng)) {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng];
        }
        $hours = $settings->get('contact.opening_hours', []);
        $spec = [];
        foreach (is_array($hours) ? $hours : [] as $entry) {
            if (!is_array($entry) || !is_array($entry['days'] ?? null) || !is_string($entry['opens'] ?? null) || !is_string($entry['closes'] ?? null)) {
                continue;
            }
            if (preg_match('/^\d{2}:\d{2}$/', $entry['opens']) !== 1 || preg_match('/^\d{2}:\d{2}$/', $entry['closes']) !== 1) {
                continue;
            }
            $days = array_values(array_filter(array_map(static fn ($d): ?string => is_string($d) && isset(self::DAYS[$d]) ? 'https://schema.org/' . self::DAYS[$d] : null, $entry['days'])));
            if ($days !== []) {
                $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $entry['opens'], 'closes' => $entry['closes']];
            }
        }
        if ($spec !== []) {
            $data['openingHoursSpecification'] = $spec;
        }
        $vat = $settings->string('company.vat');
        if (preg_match('/^(BE)?\s*0?\d{3}[. ]?\d{3}[. ]?\d{3}$/i', $vat) === 1) {
            $digits = (string) preg_replace('/\D/', '', $vat);
            $data['vatID'] = 'BE' . str_pad($digits, 10, '0', STR_PAD_LEFT);
        }
        $sameAs = [];
        foreach (['facebook', 'instagram', 'tiktok', 'youtube', 'google_business'] as $network) {
            $url = $settings->string('social.' . $network . '.url');
            if (preg_match('#^https://[^\s\[\]]+$#', $url) === 1) {
                $sameAs[] = $url;
            }
        }
        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }
        // Google's own totals for the location. No Review markup: that may only describe the reviews as a whole,
        // and a hand-picked selection of visible ones would misrepresent them (EU consumer review rules).
        // Placeholders such as "[4.9]" are not a rating, so nothing is published until Google reported real totals.
        $rating = trim($settings->string('reviews.rating'));
        $countValue = trim($settings->string('reviews.count'));
        $count = ctype_digit($countValue) ? (int) $countValue : 0;
        if (is_numeric($rating) && (float) $rating > 0 && $count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $rating, 1, '.', ''),
                'reviewCount' => $count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }
        $data['priceRange'] = '€€';
        return $data;
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
        return "User-agent: *\nDisallow: /install\nDisallow: /*/consent\n\nSitemap: " . rtrim($baseUrl, '/') . "/sitemap.xml\n";
    }
}
