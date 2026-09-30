<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Core\Clock;
use Gate\I18n\Translator;
use Gate\Repositories\ContentRepository;
use Gate\Repositories\LanguageRepository;
use Gate\Repositories\ReviewRepository;
use Gate\Reviews\ReviewPhotos;
use Gate\Services\SectionOrder;
use Gate\Services\Settings;

/**
 * Builds the view data of public pages from the settings and the content tables: layout parts (header with top bar,
 * mobile menu, footer, dock, cookie banner), head data (meta, canonical, hreflang, Open Graph, JSON-LD, analytics) and
 * the shared content of the home sections.
 */
final class SitePresenter
{
    public const LOGO = Brand::LOGO;
    private const OG_LOCALES = ['en' => 'en_GB', 'fr' => 'fr_BE', 'nl' => 'nl_BE'];

    public function __construct(
        private readonly ContentRepository $content,
        private readonly SiteUrls $urls,
        private readonly Settings $settings,
        private readonly LanguageRepository $languages,
        private readonly Translator $t,
        private readonly Clock $clock,
        private readonly ?ReviewRepository $reviews = null,
    ) {
    }

    /**
     * @param array<string, string> $alternates lang => path of the current page
     * @param array{type: string, message: string}|null $toast
     * @return array<string, mixed>
     */
    public function layout(string $lang, string $activeKey, array $alternates, string $currentPath, Consent $consent, bool $showCookieSettings, ?array $toast): array
    {
        $siteName = $this->settings->string('site.name', 'GATE Lebanon');
        $sections = $this->content->sections('home', $lang);
        $topbarOn = false;
        foreach ($sections as $s) {
            if ($s['type'] === 'topbar') {
                $topbarOn = $s['is_enabled'];
            }
        }
        $phone = $this->settings->string('contact.phone');
        $services = array_values(array_filter($this->content->services($lang), static fn (array $s): bool => $s['show_in_menu']));
        $nav = [];
        foreach ($this->content->pages($lang) as $page) {
            if (!$page['in_nav']) {
                continue;
            }
            $item = ['label' => $page['nav_label'], 'href' => $this->urls->page($page['key'], $lang), 'current' => $page['key'] === $activeKey];
            if ($page['key'] === 'services' && $services !== []) {
                $item['children'] = array_map(fn (array $s): array => ['title' => $s['menu_title'] !== '' ? $s['menu_title'] : $s['title'], 'sub' => $s['menu_sub'], 'href' => $this->urls->service($s['id'], $lang)], $services);
            }
            $nav[] = $item;
        }
        $languages = [];
        $default = $this->languages->defaultCode();
        foreach ($this->languages->enabled() as $l) {
            $languages[] = [
                'code' => $l['code'], 'name' => $l['native_name'], 'english' => $l['name'],
                'href' => $alternates[$l['code']] ?? $this->urls->page('home', $l['code']),
                'current' => $l['code'] === $lang, 'default' => $l['code'] === $default,
            ];
        }
        $showSelector = count($languages) > 1 && $this->settings->bool('i18n.show_selector', true);
        $socialsFor = function (string $placement): array {
            $out = [];
            foreach (['facebook' => 'facebook', 'instagram' => 'instagram', 'tiktok' => 'tiktok', 'whatsapp' => 'whatsapp', 'youtube' => 'youtube', 'google_business' => 'google'] as $key => $network) {
                $url = $this->settings->string('social.' . $key . '.url');
                if ($url === '' || !$this->settings->bool('social.' . $key . '.' . $placement)) {
                    continue;
                }
                $out[] = ['network' => $network, 'url' => $network === 'whatsapp' ? self::whatsappUrl($url) : $url];
            }
            return $out;
        };
        $bookHref = $this->bookHref($lang);
        $legalLinks = [];
        foreach (['privacy', 'cookies', 'terms'] as $key) {
            $page = $this->content->page($key, $lang);
            if ($page !== null) {
                $legalLinks[] = ['label' => $page['nav_label'], 'href' => $this->urls->page($key, $lang)];
            }
        }
        $legalLinks[] = ['label' => $this->t->get('site.footer.cookie_settings'), 'href' => $currentPath . '?cookies=settings#cookie-consent'];
        $companyLinks = [];
        foreach (['about', 'reviews', 'contact'] as $key) {
            $page = $this->content->page($key, $lang);
            if ($page !== null) {
                $companyLinks[] = ['label' => $page['nav_label'], 'href' => $this->urls->page($key, $lang)];
            }
        }
        $footerLangs = [];
        foreach ($languages as $l) {
            $footerLangs[] = ['name' => $l['name'], 'href' => $l['href'], 'code' => $l['code']];
        }
        $siteLogo = (new Brand($this->settings))->siteLogo();
        $cookiePolicy = $this->content->page('cookies', $lang) !== null ? $this->urls->page('cookies', $lang) : '/' . $lang . '/';

        return [
            'header' => [
                'topbar' => $topbarOn ? [
                    'address' => $this->addressLine(false),
                    'hours' => $this->t->get('site.hours.weekdays', ['hours' => $this->settings->string('contact.hours_weekdays')]),
                    'coords' => self::coordinates($this->settings->string('contact.latitude'), $this->settings->string('contact.longitude')),
                    'phone' => $phone,
                    'phoneHref' => self::telHref($phone) ?? '#contact',
                ] : null,
                'nav' => $nav,
                'languages' => $showSelector ? $languages : [],
                'homeHref' => $this->urls->page('home', $lang),
                'logoSrc' => $siteLogo,
                'logoAlt' => $siteName,
                'siteName' => $siteName,
                'ctaLabel' => $this->t->get('site.header.cta'),
                'ctaHref' => $bookHref,
                'menuHref' => '#mnav',
            ],
            'drawer' => [
                'nav' => $nav, 'languages' => $showSelector ? $languages : [], 'logoSrc' => $siteLogo, 'logoAlt' => $siteName,
                'closeHref' => $currentPath, 'ctaLabel' => $this->t->get('site.header.cta'), 'ctaHref' => $bookHref,
                'socials' => $socialsFor('header'), 'open' => false, 'hidden' => true,
            ],
            'footer' => [
                'logoSrc' => $siteLogo,
                'logoAlt' => $siteName,
                'about' => $this->t->get('site.footer.about'),
                'socials' => $socialsFor('footer'),
                'columns' => [
                    ['title' => $this->t->get('site.footer.services'), 'links' => array_map(fn (array $s): array => ['label' => $s['short_title'] !== '' ? $s['short_title'] : $s['title'], 'href' => $this->urls->service($s['id'], $lang)], $services)],
                    ['title' => $this->t->get('site.footer.company'), 'links' => $companyLinks],
                    ['title' => $this->t->get('site.footer.legal'), 'links' => $legalLinks],
                ],
                'copyright' => $this->t->get('site.footer.copyright', ['year' => $this->clock->now()->format('Y'), 'site' => $siteName, 'vat' => $this->settings->string('company.vat')]),
                'languages' => count($languages) > 1 ? $footerLangs : [],
            ],
            'dock' => $this->settings->bool('site.mobile_dock', true) ? [
                'label' => $this->t->get('site.dock.label'),
                'items' => array_values(array_filter([
                    self::telHref($phone) !== null ? ['label' => $this->t->get('site.dock.call'), 'icon' => 'fa-solid fa-phone', 'href' => (string) self::telHref($phone)] : null,
                    $this->settings->string('social.whatsapp.url') !== '' ? ['label' => $this->t->get('site.dock.whatsapp'), 'icon' => 'fa-brands fa-whatsapp', 'href' => self::whatsappUrl($this->settings->string('social.whatsapp.url'))] : null,
                    ['label' => $this->t->get('site.dock.book'), 'icon' => 'fa-regular fa-calendar', 'href' => $bookHref, 'primary' => true],
                ])),
            ] : null,
            // Rendered (with its CSS) only while a choice is needed or the visitor opened "Cookie settings".
            'cookie' => !$consent->hasChoice() || $showCookieSettings ? [
                'action' => '/' . $lang . '/consent',
                'returnTo' => $currentPath,
                'policyHref' => $cookiePolicy,
                'open' => true,
            ] : null,
            'toast' => $toast,
        ];
    }

    /**
     * @param array<string, string> $alternates lang => path
     * @param list<string> $bundles
     * @param list<array<string, mixed>> $jsonld extra JSON-LD blocks
     * @return array<string, mixed>
     */
    public function head(string $lang, string $title, string $description, string $path, array $alternates, array $bundles, Consent $consent, SecurityHeadersAllow $allow, array $jsonld = [], bool $noindex = false): array
    {
        $siteName = $this->settings->string('site.name', 'GATE Lebanon');
        $canonical = $this->urls->absolute($path);
        $brand = new Brand($this->settings);
        $ogImage = $this->settings->string('seo.og_image');
        $ogImage = $ogImage !== '' ? $ogImage : $brand->logo();
        $locales = [];
        foreach (array_keys($alternates) as $code) {
            if ($code !== $lang && isset(self::OG_LOCALES[$code])) {
                $locales[] = self::OG_LOCALES[$code];
            }
        }
        $analytics = null;
        if ($consent->allowsAnalytics()) {
            $tags = Consent::analyticsTags($this->settings->string('analytics.provider', 'none'), $this->settings->string('analytics.ga4_id'), $this->settings->string('analytics.plausible_domain'), $this->settings->string('analytics.plausible_src'));
            if ($tags !== null) {
                foreach (['script-src' => $tags['script_src'], 'connect-src' => $tags['connect_src'], 'img-src' => $tags['img_src']] as $directive => $sources) {
                    foreach ($sources as $source) {
                        $allow->allow($directive, $source);
                    }
                }
                $analytics = ['scripts' => $tags['scripts'], 'inline' => $tags['inline']];
            }
        }
        $logoUrl = $this->urls->absolute($brand->logo());
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $noindex ? null : $canonical,
            'alternates' => $noindex ? [] : $this->urls->hreflang(array_map(fn (string $p): string => $p, $alternates)),
            'noindex' => $noindex,
            'bundles' => $bundles,
            'og' => [
                'type' => 'website', 'site_name' => $siteName, 'title' => $title, 'description' => $description, 'url' => $canonical,
                'image' => str_starts_with($ogImage, 'http') ? $ogImage : $this->urls->absolute($ogImage), 'locale' => self::OG_LOCALES[$lang] ?? 'en_GB', 'alternate_locales' => $locales,
            ],
            'jsonld' => array_merge([Seo::localBusiness($this->settings, $this->urls->base(), $lang, $logoUrl, $this->t->get('site.footer.about'))], $jsonld),
            'analytics' => $analytics,
            'favicon' => $brand->favicon(),
        ];
    }

    /**
     * Content shared by the home sections and the content pages.
     *
     * @param array<string, mixed> $form appointment-form props (action, token, values, errors, privacyHref)
     * @return array<string, mixed>
     */
    public function homeData(string $lang, array $form): array
    {
        $phone = $this->settings->string('contact.phone');
        $email = $this->settings->string('contact.email');
        $services = [];
        $n = 0;
        foreach ($this->content->services($lang) as $s) {
            if (!$s['show_on_home']) {
                continue;
            }
            $services[] = ['number' => sprintf('%02d', ++$n), 'icon' => $s['icon'], 'title' => $s['title'], 'text' => $s['summary'], 'href' => $this->urls->service($s['id'], $lang), 'id' => $s['id']];
        }
        $steps = [];
        foreach ($this->content->processSteps($lang) as $i => $step) {
            $steps[] = ['number' => sprintf('%02d', $i + 1), 'title' => $step['title'], 'text' => $step['text']];
        }
        $googleUrl = $this->settings->string('google.reviews_url');
        $hours = $this->t->get('site.hours.weekdays', ['hours' => $this->settings->string('contact.hours_weekdays')]);
        $saturday = $this->settings->string('contact.hours_saturday');
        if ($saturday !== '') {
            $hours .= ' · ' . $this->t->get('site.hours.saturday', ['hours' => $saturday]);
        }
        $statsLabel = '';
        foreach ($this->content->sections('home', $lang) as $section) {
            if ($section['type'] === 'stats') {
                $statsLabel = $section['label'];
            }
        }
        return [
            'bookHref' => $this->bookHref($lang),
            'phoneHref' => self::telHref($phone),
            'rating' => [
                // Exactly what Google reports for the location: never recalculated from the reviews shown here.
                'show' => $this->settings->bool('reviews.show_rating_badge', true) && self::isNumber($this->settings->string('reviews.rating')),
                'value' => $this->settings->string('reviews.rating'),
                'count' => $this->settings->string('reviews.count'),
                'googleUrl' => preg_match('#^https://[^\s\[\]]+$#', $googleUrl) === 1 ? $googleUrl : null,
            ],
            'stats' => array_map(static fn (array $s): array => ['value' => $s['value'], 'label' => $s['label']], $this->content->stats($lang)),
            'statsLabel' => $statsLabel,
            'services' => $services,
            'servicesHref' => $this->content->page('services', $lang) !== null ? $this->urls->page('services', $lang) : null,
            'steps' => $steps,
            'types' => array_map(static fn (array $t): string => $t['label'], $this->content->transmissionTypes($lang)),
            'reviews' => $this->reviewCards($lang),
            'contact' => [
                'items' => array_values(array_filter([
                    ['icon' => 'fa-solid fa-location-dot', 'text' => $this->addressLine(true)],
                    $phone !== '' ? ['icon' => 'fa-solid fa-phone', 'text' => $phone, 'href' => self::telHref($phone)] : null,
                    $email !== '' ? ['icon' => 'fa-regular fa-envelope', 'text' => $email, 'href' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:' . $email : null] : null,
                    ['icon' => 'fa-regular fa-clock', 'text' => $hours],
                ])),
                'mapHref' => self::mapsUrl($this->settings->string('contact.latitude'), $this->settings->string('contact.longitude')),
                'form' => $form,
            ],
        ];
    }

    /** @return array{title: string, text: string, bookHref: string, phoneHref: ?string} */
    public function cta(string $lang): array
    {
        return ['title' => $this->t->get('site.cta.title'), 'text' => $this->t->get('site.cta.text'), 'bookHref' => $this->bookHref($lang), 'phoneHref' => self::telHref($this->settings->string('contact.phone'))];
    }

    /** @return list<array<string, mixed>> SectionOrder::visible() sections of the home page */
    public function homeSections(string $lang): array
    {
        return SectionOrder::visible($this->content->sections('home', $lang));
    }

    /** @return array<string, mixed>|null a home section (for reuse on content pages) */
    public function section(string $type, string $lang): ?array
    {
        foreach ($this->content->sections('home', $lang) as $section) {
            if ($section['type'] === $type) {
                return $section + ['number' => null];
            }
        }
        return null;
    }

    public function bookHref(string $lang): string
    {
        foreach ($this->homeSections($lang) as $section) {
            if ($section['type'] === 'contact') {
                return $this->urls->page('home', $lang) . '#contact';
            }
        }
        return $this->urls->page('contact', $lang, 'contact');
    }

    /**
     * The visible reviews as the cards want them. Photos go through this site (ReviewPhotos), and only when the
     * "Show reviewer profile photos" setting is on; otherwise the design's initials are used.
     *
     * @return list<array{initial: string, name: string, date: string, text: string, rating: float, photo: ?string}>
     */
    private function reviewCards(string $lang): array
    {
        if ($this->reviews === null) {
            return [];
        }
        $showPhotos = $this->settings->bool('reviews.show_photos');
        $order = $this->settings->string('reviews.display_order', 'newest');
        $cards = [];
        foreach ($this->reviews->visible($this->settings->int('reviews.max_on_home', 6), $order) as $review) {
            $name = (string) $review['reviewer_name'];
            $photo = (string) $review['reviewer_photo_url'];
            $cards[] = [
                'initial' => mb_strtoupper(mb_substr(trim($name), 0, 1)),
                'name' => $name,
                'date' => $this->reviewDate((string) $review['review_date'], $lang),
                'text' => (string) ($review['text'] ?? ''),
                'rating' => (float) $review['rating'],
                // Only a photo this site already has: a visitor never waits for Google, and never sees a broken image.
                'photo' => $showPhotos && $photo !== '' && ReviewPhotos::isCached((int) $review['id'], $photo)
                    ? ReviewPhotos::path((int) $review['id'])
                    : null,
            ];
        }
        return $cards;
    }

    /** "12 Sep 2026" in the language of the page. */
    private function reviewDate(string $value, string $lang): string
    {
        $time = strtotime($value);
        if ($time === false) {
            return '';
        }
        $months = [
            'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'fr' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
            'nl' => ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        ];
        return (int) date('j', $time) . ' ' . ($months[$lang] ?? $months['en'])[(int) date('n', $time) - 1] . ' ' . date('Y', $time);
    }

    /** A rating like "4.9" that Google actually reported (placeholders such as "[4.9]" are not shown). */
    private static function isNumber(string $value): bool
    {
        return is_numeric(trim($value)) && (float) $value > 0;
    }

    public static function telHref(string $phone): ?string
    {
        $digits = (string) preg_replace('/[^\d+]/', '', $phone);
        return strlen(ltrim($digits, '+')) >= 8 ? 'tel:' . $digits : null;
    }

    public static function whatsappUrl(string $value): string
    {
        if (str_starts_with($value, 'https://')) {
            return $value;
        }
        $digits = (string) preg_replace('/\D/', '', $value);
        return strlen($digits) >= 8 ? 'https://wa.me/' . $digits : '#';
    }

    public static function coordinates(string $lat, string $lng): string
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return '';
        }
        return sprintf('%.4f° %s / %.4f° %s', abs((float) $lat), (float) $lat >= 0 ? 'N' : 'S', abs((float) $lng), (float) $lng >= 0 ? 'E' : 'W');
    }

    public static function mapsUrl(string $lat, string $lng): ?string
    {
        return is_numeric($lat) && is_numeric($lng) ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng) : null;
    }

    private function addressLine(bool $withCountry): string
    {
        $line = trim($this->settings->string('contact.street') . ', ' . $this->settings->string('contact.postcode') . ' ' . $this->settings->string('contact.city'), ', ');
        return $withCountry ? $line . ', ' . $this->t->get('site.country') : $line;
    }
}
