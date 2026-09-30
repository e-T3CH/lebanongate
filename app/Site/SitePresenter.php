<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Content\EntryTypes;
use Gate\Core\Clock;
use Gate\Core\Components;
use Gate\I18n\LanguageRules;
use Gate\I18n\Translator;
use Gate\Repositories\ContentRepository;
use Gate\Repositories\EntryRepository;
use Gate\Repositories\LanguageRepository;
use Gate\Services\Settings;

/**
 * Builds the view data of public pages from the settings and the content: the layout (top bar, header with the
 * navigation and the language menu, footer with the "Developed by" credit, cookie banner, toast), the head (meta,
 * canonical, hreflang, Open Graph, JSON-LD, analytics) and the card data shared by lists and home sections.
 *
 * @phpstan-import-type Entry from EntryRepository
 * @phpstan-import-type MediaItem from ContentRepository
 * @phpstan-type Card array{id: int, type: string, title: string, summary: string, href: string, cover: MediaItem|null, date: string, badge: array{0: string, 1: string}, tag: string, status: string, statusLabel: string, region: string, meta: list<array{icon: string, text: string}>}
 */
final class SitePresenter
{
    private const OG_LOCALES = ['en' => 'en_US', 'ar' => 'ar_LB', 'fr' => 'fr_FR'];

    public function __construct(
        private readonly ContentRepository $content,
        private readonly EntryRepository $entries,
        private readonly SiteUrls $urls,
        private readonly Settings $settings,
        private readonly LanguageRepository $languages,
        private readonly Translator $t,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @param array<string, string> $alternates lang => path of the current page
     * @param array{type: string, message: string}|null $toast
     * @param array{action: string, token: string} $newsletter
     * @return array<string, mixed>
     */
    public function layout(string $lang, string $activeKey, array $alternates, string $currentPath, Consent $consent, bool $showCookieSettings, ?array $toast, array $newsletter): array
    {
        $siteName = $this->settings->string('site.name', 'GATE Lebanon');
        $brand = new Brand($this->settings);
        $phone = $this->real('contact.phone');
        $email = $this->real('contact.email');

        $nav = [];
        foreach ($this->content->pages($lang) as $page) {
            if (!$page['in_nav'] || $page['parent_key'] !== '') {
                continue;
            }
            $children = [];
            foreach ($this->content->children($page['key'], $lang) as $child) {
                if ($child['in_nav']) {
                    $children[] = ['label' => $child['nav_label'], 'href' => $this->urls->page($child['key'], $lang), 'current' => $child['key'] === $activeKey];
                }
            }
            $isCurrent = $page['key'] === $activeKey || in_array($activeKey, array_column($this->content->children($page['key'], $lang), 'key'), true);
            $nav[] = ['key' => $page['key'], 'label' => $page['nav_label'], 'href' => $this->urls->page($page['key'], $lang), 'current' => $isCurrent, 'children' => $children];
        }

        $languages = [];
        foreach ($this->languages->enabled() as $l) {
            $languages[] = [
                'code' => $l['code'], 'name' => $l['native_name'],
                'href' => $alternates[$l['code']] ?? $this->urls->page('home', $l['code']),
                'current' => $l['code'] === $lang, 'dir' => LanguageRules::direction($l['code']),
            ];
        }
        $showSelector = count($languages) > 1 && $this->settings->bool('i18n.show_selector', true);
        $current = null;
        foreach ($languages as $l) {
            if ($l['current']) {
                $current = $l;
            }
        }

        $quickLinks = [];
        foreach (['about', 'expertise', 'projects', 'news', 'publications', 'gallery', 'partners'] as $key) {
            $page = $this->content->page($key, $lang);
            if ($page !== null) {
                $quickLinks[] = ['label' => $page['nav_label'], 'href' => $this->urls->page($key, $lang)];
            }
        }
        $legal = [];
        foreach (['privacy', 'cookies', 'terms'] as $key) {
            $page = $this->content->page($key, $lang);
            if ($page !== null) {
                $legal[] = ['label' => $page['nav_label'], 'href' => $this->urls->page($key, $lang)];
            }
        }
        $legal[] = ['label' => $this->t->get('site.footer.cookie_settings'), 'href' => $currentPath . '?cookies=settings#cookie-consent'];
        $contactPage = $this->content->page('contact', $lang);
        $partnerHref = $contactPage !== null ? $this->urls->page('contact', $lang) : $this->urls->page('home', $lang);

        return [
            'lang' => $lang,
            'topbar' => [
                'email' => $email,
                'emailHref' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:' . $email : null,
                'phone' => $phone,
                'phoneHref' => self::telHref($phone),
                'address' => $this->address($lang),
                'socials' => $this->socials('header'),
                'languages' => $showSelector ? $languages : [],
                'currentLanguage' => $current,
            ],
            'header' => [
                'homeHref' => $this->urls->page('home', $lang),
                'logo' => $brand->headerLogo(),
                'siteName' => $siteName,
                'nav' => $nav,
                'cta' => ['label' => $this->t->get('site.header.cta'), 'href' => $partnerHref],
            ],
            'footer' => [
                'logo' => $brand->footerLogo(),
                'siteName' => $siteName,
                'homeHref' => $this->urls->page('home', $lang),
                'about' => $this->t->get('site.footer.about'),
                'socials' => $this->socials('footer'),
                'links' => $quickLinks,
                'contact' => array_values(array_filter([
                    ['icon' => 'pin', 'text' => $this->address($lang), 'href' => self::mapsUrl($this->settings->string('contact.latitude'), $this->settings->string('contact.longitude'))],
                    $phone !== '' ? ['icon' => 'phone', 'text' => $phone, 'href' => self::telHref($phone), 'ltr' => true] : null,
                    $email !== '' ? ['icon' => 'mail', 'text' => $email, 'href' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:' . $email : null, 'ltr' => true] : null,
                    $this->real('contact.hours') !== '' ? ['icon' => 'clock', 'text' => $this->real('contact.hours'), 'href' => null] : null,
                ])),
                'newsletter' => $this->settings->bool('site.newsletter_enabled', true) ? $newsletter + ['return' => $currentPath] : null,
                'legal' => $legal,
                'copyright' => $this->t->get('site.footer.copyright', ['year' => $this->clock->now()->format('Y'), 'site' => $siteName]),
                'credit' => ['label' => $this->t->get('site.footer.developed_by'), 'logo' => Brand::CREDIT_LOGO, 'url' => Brand::CREDIT_URL, 'name' => 'E-5HOP'],
            ],
            // Rendered while a choice is needed (only when analytics is set up: otherwise the site sets necessary
            // cookies only and there is nothing to ask) or when the visitor opened "Cookie settings".
            'cookie' => ($this->settings->string('analytics.provider', 'none') !== 'none' && !$consent->hasChoice()) || $showCookieSettings ? [
                'action' => '/' . $lang . '/consent',
                'returnTo' => $currentPath,
                'policyHref' => $this->content->page('cookies', $lang) !== null ? $this->urls->page('cookies', $lang) : null,
            ] : null,
            'toast' => $toast,
        ];
    }

    /**
     * @param array<string, string> $alternates lang => path
     * @param list<array<string, mixed>> $jsonld extra JSON-LD blocks
     * @param MediaItem|null $image share image of this page (else the default)
     * @return array<string, mixed>
     */
    public function head(string $lang, string $title, string $description, string $path, array $alternates, Consent $consent, SecurityHeadersAllow $allow, array $jsonld = [], bool $noindex = false, ?array $image = null, string $ogType = 'website'): array
    {
        $siteName = $this->settings->string('site.name', 'GATE Lebanon');
        $canonical = $this->urls->absolute($path);
        $brand = new Brand($this->settings);
        $ogImage = $image !== null ? $image['url'] : $this->settings->string('seo.og_image');
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
        $description = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $description)), 0, 300);
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $noindex ? null : $canonical,
            'alternates' => $noindex ? [] : $this->urls->hreflang($alternates),
            'noindex' => $noindex,
            'dir' => LanguageRules::direction($lang),
            'og' => [
                'type' => $ogType, 'site_name' => $siteName, 'title' => $title, 'description' => $description, 'url' => $canonical,
                'image' => $this->urls->absolute($ogImage), 'locale' => self::OG_LOCALES[$lang] ?? 'en_US', 'alternate_locales' => $locales,
            ],
            'jsonld' => array_merge([Seo::organization($this->settings, $this->urls->base(), $lang, $this->urls->absolute($brand->logo()), $this->t->get('site.footer.about'))], $jsonld),
            'analytics' => $analytics,
            'favicon' => $brand->favicon(),
        ];
    }

    /**
     * Card data of an entry for lists and home sections.
     *
     * @param Entry $entry
     * @return Card
     */
    public function card(array $entry, string $lang): array
    {
        $area = $entry['expertise_id'] !== null ? $this->content->expertiseById($entry['expertise_id'], $lang) : null;
        $meta = [];
        if ($entry['type'] === 'project') {
            if ($entry['region'] !== '') {
                $meta[] = ['icon' => 'pin', 'text' => $this->t->get('site.regions.' . $entry['region'])];
            }
            if ($entry['beneficiaries'] !== null && $entry['beneficiaries'] > 0) {
                $meta[] = ['icon' => 'users', 'text' => $this->t->get('site.entries.beneficiaries_short', ['n' => self::number($entry['beneficiaries'], $lang)])];
            }
            if ($entry['donors'] !== '') {
                $meta[] = ['icon' => 'hands', 'text' => $entry['donors']];
            }
        } elseif ($entry['type'] === 'publication') {
            $meta[] = ['icon' => 'calendar', 'text' => Dates::year($entry['published_on'])];
        } elseif ($entry['type'] === 'album') {
            $meta[] = ['icon' => 'image', 'text' => $this->t->get('site.entries.photos', ['n' => $this->entries->galleryCount($entry['id'])])];
        }
        $statusLabel = $entry['status'] !== '' && in_array($entry['status'], EntryTypes::statuses($entry['type']), true)
            ? $this->t->get('site.' . ($entry['type'] === 'project' ? 'status' : 'kinds') . '.' . $entry['status'])
            : '';
        return [
            'id' => $entry['id'],
            'type' => $entry['type'],
            'title' => $entry['title'],
            'summary' => $entry['summary'],
            'href' => $this->urls->entry($entry['type'], $entry['slug'], $lang),
            'cover' => $entry['cover'],
            'date' => Dates::long($entry['published_on'], $lang),
            'badge' => Dates::badge($entry['published_on'], $lang),
            'tag' => $area !== null ? $area['title'] : '',
            'status' => $entry['status'],
            'statusLabel' => $statusLabel,
            'region' => $entry['region'],
            'meta' => $meta,
        ];
    }

    /** @return list<array{network: string, icon: string, url: string, label: string}> */
    private function socials(string $placement): array
    {
        $out = [];
        foreach (Components::SOCIAL_ICONS as $network => $icon) {
            $url = $this->settings->string('social.' . $network . '.url');
            if ($url === '' || str_contains($url, '[') || !$this->settings->bool('social.' . $network . '.' . $placement)) {
                continue;
            }
            $out[] = ['network' => $network, 'icon' => $icon, 'url' => $network === 'whatsapp' ? self::whatsappUrl($url) : $url, 'label' => $this->t->get('site.social.' . $network)];
        }
        return $out;
    }

    /** "Ashrafieh, Beirut, Lebanon" in the page language (the settings hold the English names). */
    public function address(string $lang): string
    {
        $parts = [];
        foreach (['street', 'area', 'city', 'country'] as $field) {
            $value = $this->real('contact.' . $field);
            if ($value === '') {
                continue;
            }
            $key = 'site.places.' . strtolower((string) preg_replace('/[^A-Za-z]+/', '_', $value));
            $parts[] = $this->t->has($key, $lang) ? $this->t->get($key) : $value;
        }
        return implode($lang === 'ar' ? '، ' : ', ', $parts);
    }

    /** A setting without [placeholder] parts. */
    private function real(string $key): string
    {
        $value = trim($this->settings->string($key));
        return str_contains($value, '[') ? '' : $value;
    }

    public static function number(int $n, string $lang): string
    {
        return number_format($n, 0, '.', $lang === 'fr' ? ' ' : ',');
    }

    public static function telHref(string $phone): ?string
    {
        $digits = (string) preg_replace('/[^\d+]/', '', $phone);
        return strlen(ltrim($digits, '+')) >= 7 ? 'tel:' . $digits : null;
    }

    public static function whatsappUrl(string $value): string
    {
        if (str_starts_with($value, 'https://')) {
            return $value;
        }
        $digits = (string) preg_replace('/\D/', '', $value);
        return strlen($digits) >= 8 ? 'https://wa.me/' . $digits : '#';
    }

    public static function mapsUrl(string $lat, string $lng): ?string
    {
        return is_numeric($lat) && is_numeric($lng) ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng) : null;
    }
}
