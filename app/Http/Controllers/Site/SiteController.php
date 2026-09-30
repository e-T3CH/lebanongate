<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Site;

use Gate\Core\App;
use Gate\Http\Flash;
use Gate\Http\HttpException;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\I18n\LocaleResolver;
use Gate\I18n\Translator;
use Gate\Mail\AppointmentMails;
use Gate\Mail\MailQueue;
use Gate\Repositories\AppointmentRepository;
use Gate\Repositories\ContentRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Repositories\ReviewRepository;
use Gate\Reviews\ReviewPhotos;
use Gate\Security\Csrf;
use Gate\Security\SpamGuard;
use Gate\Site\AppointmentForm;
use Gate\Site\Consent;
use Gate\Site\Media;
use Gate\Site\RichText;
use Gate\Site\Seo;
use Gate\Site\SitePresenter;
use Gate\Site\SiteUrls;

/**
 * The public website: language routing with translated slugs, pages, service details, the appointment form,
 * cookie consent, sitemaps, robots.txt, redirects for old URLs, maintenance mode and styled error pages.
 */
final class SiteController
{
    private ContentRepository $content;
    private SiteUrls $urls;

    public function __construct(private readonly App $app)
    {
        $languages = $app->languages();
        $this->content = new ContentRepository($app->db(), $languages->defaultCode());
        $this->urls = new SiteUrls($this->content, $languages->enabledCodes(), $languages->defaultCode(), $app->baseUrl());
    }

    public function handle(Request $request): Response
    {
        $path = $request->path();
        $settings = $this->app->settings();
        $languages = $this->app->languages();

        if ($path === '/robots.txt') {
            return Response::text(Seo::robots($this->urls->base()))->withHeader('Cache-Control', 'public, max-age=3600');
        }
        if ($path === '/sitemap.xml') {
            $index = array_map(fn (string $l): string => $this->urls->absolute('/sitemap-' . $l . '.xml'), $this->urls->langs());
            return $this->xml(Seo::sitemapIndex($index, $this->lastModified()));
        }
        if (preg_match('#^/sitemap-([a-z]{2})\.xml$#', $path, $m) === 1 && in_array($m[1], $this->urls->langs(), true)) {
            return $this->xml(Seo::sitemap($this->sitemapEntries($m[1])));
        }
        if (preg_match('#^/review-photo/(\d{1,10})\.jpg$#', $path, $m) === 1) {
            return $this->reviewPhoto((int) $m[1]);
        }

        $resolver = new LocaleResolver($languages->enabledCodes(), $languages->defaultCode(), $settings->bool('i18n.detect_browser', true));
        $match = $resolver->matchPath($path);
        if ($match['status'] === 'root') {
            $lang = $resolver->forRoot($request->cookie(LocaleResolver::COOKIE), $request->header('Accept-Language'));
            return Response::redirect('/' . $lang . '/', 302)->withHeader('Vary', 'Accept-Language, Cookie');
        }
        if ($match['status'] === 'not_found') {
            return $this->redirectOr404($request, $languages->defaultCode());
        }
        if ($match['status'] === 'redirect') {
            return Response::redirect('/' . $match['lang'] . $match['rest'] . $request->queryString(), 301);
        }
        $lang = $match['lang'];
        $this->app->useTranslator(new Translator($lang, $languages->defaultCode(), $this->app->db()));

        if ($settings->bool('site.maintenance_mode') && !$this->app->isAdminSignedIn()) {
            return $this->errorPage(503, $lang, 'maintenance')->withHeader('Retry-After', '3600');
        }
        if (Csrf::requiresCheck($request->method()) && !$this->app->csrf()->validate($request->input(Csrf::FIELD))) {
            return $this->errorPage(419, $lang, 'expired');
        }

        $rest = rtrim($match['rest'], '/');
        if ($request->method() === 'POST' && $rest === '/consent') {
            return $this->saveConsent($request, $lang);
        }
        $route = $this->urls->resolve($lang, $match['rest']);
        if ($route === null) {
            return $this->redirectOr404($request, $lang);
        }
        if ($request->method() === 'POST') {
            if ($route['type'] === 'page' && in_array($route['key'], ['contact', 'home'], true)) {
                return $this->submitAppointment($request, $lang);
            }
            throw new HttpException(405);
        }
        if ($request->method() !== 'GET' && $request->method() !== 'HEAD') {
            throw new HttpException(405);
        }
        // Trailing slash policy: pages without, the home page with.
        if ($route['key'] !== 'home' && str_ends_with($match['rest'], '/')) {
            return Response::redirect('/' . $lang . $rest . $request->queryString(), 301);
        }
        $response = $route['type'] === 'service'
            ? $this->servicePage($request, $lang, (int) $route['service_id'])
            : $this->page($request, $lang, $route['key']);
        return $this->rememberLanguage($request, $response, $lang);
    }

    // --------------------------------------------------------------------------------------------------- pages

    /**
     * @param array{values?: array<string, mixed>, errors?: array<string, string>} $formState
     */
    private function page(Request $request, string $lang, string $key, array $formState = [], int $status = 200): Response
    {
        $page = $this->content->page($key, $lang);
        if ($page === null) {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $presenter = $this->presenter($lang);
        $route = ['type' => 'page', 'key' => $key, 'service_id' => null];
        $alternates = $this->urls->alternates($route);
        $home = $presenter->homeData($lang, $this->formProps($lang, $formState, $key));
        $page['breadcrumbs'] = [['label' => $this->t('site.breadcrumbs.home'), 'href' => $this->urls->page('home', $lang)], ['label' => $page['nav_label']]];
        // Per-page CSS groups (layouts/site adds cookie and toast when those are shown).
        $bundles = match ($page['template']) {
            'home' => ['home', 'cards', 'stats', 'reviews', 'forms'],
            'contact' => ['forms'],
            'reviews' => ['reviews', 'content'],
            'services' => ['cards', 'content'],
            'about' => ['cards', 'stats', 'content'],
            default => ['content'],
        };
        $data = ['page' => $page, 'home' => $home, 'cta' => $presenter->cta($lang)];
        $template = match ($page['template']) {
            'home' => 'site/pages/home',
            'services' => 'site/pages/services',
            'transmissions' => 'site/pages/transmissions',
            'about' => 'site/pages/about',
            'reviews' => 'site/pages/reviews',
            'contact' => 'site/pages/contact',
            default => 'site/pages/legal',
        };
        if ($page['template'] === 'home') {
            $data['sections'] = $presenter->homeSections($lang);
            $types = array_column($data['sections'], 'type');
            $bundles = array_values(array_filter($bundles, static fn (string $b): bool => match ($b) {
                'cards' => array_intersect(['services', 'process'], $types) !== [],
                'stats' => in_array('stats', $types, true),
                'reviews' => in_array('reviews', $types, true),
                'forms' => in_array('contact', $types, true),
                default => true,
            }));
        }
        if (in_array($page['template'], ['services', 'about'], true)) {
            $data['process'] = $presenter->section('process', $lang) ?? ['number' => null, 'label' => '', 'title' => '', 'highlight' => ''];
        }
        if ($page['template'] === 'transmissions') {
            $data['types'] = $this->content->transmissionTypes($lang);
        }
        if ($page['template'] === 'about') {
            $data['partners'] = $this->content->partners($lang);
        }
        if ($page['template'] === 'reviews') {
            $data['reviewsSection'] = ($presenter->section('reviews', $lang) ?? []) + ['number' => null];
        }
        if ($page['template'] === 'contact') {
            $data['contactSection'] = $presenter->section('contact', $lang) ?? ['extra' => []];
        }
        if ($page['template'] === 'legal') {
            $settings = $this->app->settings();
            $data['body'] = RichText::render($page['body'], [
                'company' => $settings->string('company.name'),
                'address' => $settings->string('contact.street') . ', ' . $settings->string('contact.postcode') . ' ' . $settings->string('contact.city'),
                'vat' => $settings->string('company.vat'),
                'email' => $settings->string('contact.email'),
                'phone' => $settings->string('contact.phone'),
                'website' => $this->urls->base(),
            ]);
        }
        $title = $page['meta_title'] !== '' ? $page['meta_title'] : $page['title'] . ' | ' . $this->app->settings()->string('site.name', 'GATE Lebanon');
        $path = $this->urls->page($key, $lang);
        $jsonld = $key === 'home' ? [] : [$this->breadcrumbList($page['breadcrumbs'], $lang)];
        return $this->render($request, $lang, $template, $data, $presenter->head($lang, $title, $page['meta_description'], $path, $alternates, $bundles, $this->consent($request), $this->app->headers, $jsonld), $key, $alternates, $status);
    }

    private function servicePage(Request $request, string $lang, int $serviceId): Response
    {
        $service = null;
        foreach ($this->content->services($lang) as $s) {
            if ($s['id'] === $serviceId) {
                $service = $s;
            }
        }
        $page = $this->content->page('services', $lang);
        if ($service === null || $page === null) {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $presenter = $this->presenter($lang);
        $alternates = $this->urls->alternates(['type' => 'service', 'key' => 'service', 'service_id' => $serviceId]);
        $home = $presenter->homeData($lang, $this->formProps($lang, [], 'service'));
        /** @var list<array{number: string, icon: string, title: string, text: string, href: string, id: int}> $all */
        $all = $home['services'];
        $others = array_values(array_filter($all, static fn (array $s): bool => $s['id'] !== $serviceId));
        $breadcrumbs = [
            ['label' => $this->t('site.breadcrumbs.home'), 'href' => $this->urls->page('home', $lang)],
            ['label' => $page['nav_label'], 'href' => $this->urls->page('services', $lang)],
            ['label' => $service['title']],
        ];
        $title = $service['meta_title'] !== '' ? $service['meta_title'] : $service['title'] . ' | ' . $this->app->settings()->string('site.name', 'GATE Lebanon');
        $path = $this->urls->service($serviceId, $lang);
        $serviceLd = [
            '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $service['title'], 'description' => $service['summary'],
            'serviceType' => $service['title'], 'areaServed' => $this->app->settings()->string('contact.city'),
            'provider' => ['@id' => $this->urls->base() . '/#business'], 'url' => $this->urls->absolute($path),
        ];
        $head = $presenter->head($lang, $title, $service['meta_description'] !== '' ? $service['meta_description'] : $service['summary'], $path, $alternates, ['cards', 'content'], $this->consent($request), $this->app->headers, [$serviceLd, $this->breadcrumbList($breadcrumbs, $lang)]);
        return $this->render($request, $lang, 'site/pages/service', ['page' => $page, 'service' => $service, 'breadcrumbs' => $breadcrumbs, 'others' => $others, 'home' => $home, 'cta' => $presenter->cta($lang)], $head, 'services', $alternates);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $head
     * @param array<string, string> $alternates
     */
    private function render(Request $request, string $lang, string $template, array $data, array $head, string $activeKey, array $alternates, int $status = 200): Response
    {
        $view = $this->app->view();
        $view->share('media', new Media());
        $toast = Flash::pullToast($this->app->session());
        $site = $this->presenter($lang)->layout(
            $lang,
            $activeKey,
            $alternates,
            (string) strtok($request->path(), '?'),
            $this->consent($request),
            $request->query('cookies') === 'settings',
            $toast !== null ? ['type' => $toast['type'], 'message' => $this->t($toast['key'], $toast['params'])] : null,
        );
        $content = $view->render($template, $data);
        $this->scheduleMail();
        return Response::html($view->render('layouts/site', ['content' => $content, 'head' => $head, 'site' => $site]), $status);
    }

    public function errorPage(int $status, string $lang, string $key, ?string $reference = null): Response
    {
        $this->app->noStore();
        try {
            $presenter = $this->presenter($lang);
            $head = $presenter->head($lang, $status . ' — ' . $this->t('site.errors.' . $key . '_title'), $this->t('site.errors.' . $key . '_text'), $this->urls->page('home', $lang), [], ['content'], Consent::fromCookie(null, 1), $this->app->headers, [], true);
            $site = $presenter->layout($lang, '', [], '/' . $lang . '/', Consent::fromCookie('v0.n.1000000000', 0), false, null);
            $site['cookie'] = null;
            $view = $this->app->view();
            $view->share('media', new Media());
            $contact = $key === 'maintenance' ? null : $this->urls->page('contact', $lang);
            $content = $view->render('site/pages/error', ['status' => $status, 'key' => $key, 'homeHref' => $this->urls->page('home', $lang), 'contactHref' => $contact, 'phoneHref' => SitePresenter::telHref($this->app->settings()->string('contact.phone')), 'reference' => $reference]);
            return Response::html($view->render('layouts/site', ['content' => $content, 'head' => $head, 'site' => $site]), $status);
        } catch (\Throwable) {
            return $this->app->errorResponse($status);
        }
    }

    // ------------------------------------------------------------------------------------------ appointment form

    private function submitAppointment(Request $request, string $lang): Response
    {
        $settings = $this->app->settings();
        $origin = $request->input('origin') === 'home' ? 'home' : 'contact';
        $guard = new SpamGuard($this->app->crypto(), $this->app->limiter(), $this->app->clock, $settings->int('forms.min_seconds', 3), $settings->int('forms.max_per_hour', 5));
        $values = AppointmentForm::values(['name' => $request->input('name'), 'phone' => $request->input('phone'), 'email' => $request->input('email'), 'car' => $request->input('car'), 'gearbox_type' => $request->input('gearbox_type'), 'symptoms' => $request->input('symptoms'), 'consent' => $request->input('consent')]);
        $spam = $guard->check('appointment', ['honeypot' => $request->input(SpamGuard::HONEYPOT), 'token' => $request->input(SpamGuard::TIMESTAMP)], $request->ip());
        if ($spam === 'honeypot') {
            // Bots get the normal success response; nothing is stored or sent.
            Flash::toast($this->app->session(), 'success', 'site.form.sent');
            return Response::redirect($this->urls->page($origin, $lang, 'contact'), 303);
        }
        if ($spam !== 'ok') {
            $message = $spam === 'rate_limited' ? 'site.form.rate_limited' : 'site.form.blocked';
            return $this->page($request, $lang, $origin, ['values' => $values, 'errors' => ['form' => $this->t($message)]], $spam === 'rate_limited' ? 429 : 422);
        }
        $errors = [];
        foreach (AppointmentForm::validate($values) as $field => [$key, $params]) {
            $errors[$field] = $this->t($key, $params);
        }
        if ($errors !== []) {
            return $this->page($request, $lang, $origin, ['values' => $values, 'errors' => $errors], 422);
        }

        $guard->recordSubmission('appointment', $request->ip());
        $appointments = new AppointmentRepository($this->app->db(), $this->app->clock);
        $id = $appointments->create($values, $lang, $this->app->crypto()->hmac('ip|' . $request->ip()), $request->userAgent());

        $siteName = $settings->string('site.name', 'GATE Lebanon');
        $queue = new MailQueue($this->app->db(), $this->app->clock);
        $businessTo = $settings->string('mail.to_email');
        if (filter_var($businessTo, FILTER_VALIDATE_EMAIL) === false) {
            $businessTo = $settings->string('contact.email');
        }
        if ($settings->bool('mail.enabled', true)) {
            $adminLang = $settings->string('admin.language', 'en');
            if (filter_var($businessTo, FILTER_VALIDATE_EMAIL) !== false) {
                $queue->enqueue(AppointmentMails::business($values, $id, $businessTo, $siteName, $this->urls->absolute($this->app->adminPath()), new Translator($adminLang, 'en', $this->app->db())));
            }
            $queue->enqueue(AppointmentMails::customer($values, $siteName, $settings->string('contact.phone'), $businessTo, new Translator($lang, 'en', $this->app->db())));
            $this->app->defer(fn () => $this->app->sendQueuedMail());
        }
        $this->app->audit()->record('appointment.created', null, ['id' => $id, 'lang' => $lang]);
        Flash::toast($this->app->session(), 'success', 'site.form.sent');
        return Response::redirect($this->urls->page($origin, $lang, 'contact'), 303);
    }

    /**
     * @param array{values?: array<string, mixed>, errors?: array<string, string>} $state
     * @return array<string, mixed>
     */
    private function formProps(string $lang, array $state, string $origin): array
    {
        $settings = $this->app->settings();
        $guard = new SpamGuard($this->app->crypto(), $this->app->limiter(), $this->app->clock, $settings->int('forms.min_seconds', 3), $settings->int('forms.max_per_hour', 5));
        $errors = $state['errors'] ?? [];
        return [
            'action' => $this->urls->page('contact', $lang),
            'token' => $guard->token('appointment'),
            'values' => $state['values'] ?? [],
            'errors' => $errors,
            'privacyHref' => $this->content->page('privacy', $lang) !== null ? $this->urls->page('privacy', $lang) : null,
            'origin' => $origin === 'home' ? 'home' : 'contact',
        ];
    }

    // ------------------------------------------------------------------------------------------ consent, mail, SEO

    private function saveConsent(Request $request, string $lang): Response
    {
        $version = $this->app->settings()->int('consent.version', 1);
        $analytics = $request->input('analytics') === '1';
        $return = $request->input('return');
        if (preg_match('#^/[a-z]{2}(/[A-Za-z0-9._~%-]*)*$#', $return) !== 1) {
            $return = '/' . $lang . '/';
        }
        $secure = $request->isSecure();
        $response = Response::redirect($return, 303)->withCookie(Consent::COOKIE, Consent::cookieValue($analytics, $version, $this->app->clock->now()->getTimestamp()), [
            'expires' => $this->app->clock->now()->getTimestamp() + Consent::LIFETIME, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
        ]);
        if (!$analytics) {
            $host = (string) parse_url($this->urls->base(), PHP_URL_HOST);
            foreach (Consent::analyticsCookies($_COOKIE) as $name) {
                foreach (['', $host, '.' . preg_replace('/^www\./', '', $host)] as $domain) {
                    $response->withCookie($name, '', ['expires' => 1, 'path' => '/', 'domain' => $domain, 'secure' => $secure, 'samesite' => 'Lax']);
                }
            }
        }
        Flash::toast($this->app->session(), 'info', 'site.consent.saved');
        return $response;
    }

    private function consent(Request $request): Consent
    {
        return Consent::fromCookie($request->cookie(Consent::COOKIE), $this->app->settings()->int('consent.version', 1));
    }

    private function scheduleMail(): void
    {
        try {
            $queue = new MailQueue($this->app->db(), $this->app->clock);
            if ($queue->hasDue()) {
                $this->app->defer(fn () => $this->app->sendQueuedMail());
            }
        } catch (\Throwable) {
            // The queue table may not exist before the Phase 3 migration has run.
        }
    }

    /** @return list<array{loc: string, lastmod: string, alternates: array<string, string>}> */
    private function sitemapEntries(string $lang): array
    {
        $entries = [];
        $abs = fn (array $alternates): array => array_map(fn (string $p): string => $this->urls->absolute($p), $alternates);
        foreach ($this->content->pages($lang) as $page) {
            if (!$page['in_sitemap'] || $page['lang'] !== $lang) {
                continue;
            }
            $alternates = $this->urls->alternates(['type' => 'page', 'key' => $page['key'], 'service_id' => null]);
            $entries[] = ['loc' => $this->urls->absolute($this->urls->page($page['key'], $lang)), 'lastmod' => substr($page['updated_at'], 0, 10), 'alternates' => count($alternates) > 1 ? $abs($alternates) : []];
        }
        foreach ($this->content->services($lang) as $service) {
            if ($service['lang'] !== $lang) {
                continue;
            }
            $alternates = $this->urls->alternates(['type' => 'service', 'key' => 'service', 'service_id' => $service['id']]);
            $entries[] = ['loc' => $this->urls->absolute($this->urls->service($service['id'], $lang)), 'lastmod' => substr($service['updated_at'], 0, 10), 'alternates' => count($alternates) > 1 ? $abs($alternates) : []];
        }
        return $entries;
    }

    private function lastModified(): string
    {
        $dates = [];
        foreach ($this->urls->langs() as $lang) {
            foreach ($this->content->pages($lang) as $p) {
                $dates[] = $p['updated_at'];
            }
        }
        return $dates === [] ? $this->app->clock->now()->format('Y-m-d') : substr((string) max($dates), 0, 10);
    }

    /**
     * A reviewer photo, served by this site. Google never sees the visitor: the image is fetched server-side once
     * and cached. Only visible reviews are served, and a photo that cannot be fetched gives 404 — the card then
     * shows the initials the design draws anyway.
     */
    private function reviewPhoto(int $id): Response
    {
        if (!$this->app->settings()->bool('reviews.show_photos')) {
            throw new HttpException(404);
        }
        $review = (new ReviewRepository($this->app->db(), $this->app->clock))->find($id);
        if ($review === null || (int) $review['is_visible'] !== 1 || $review['deleted_at'] !== null) {
            throw new HttpException(404);
        }
        $image = (new ReviewPhotos())->image($id, (string) $review['reviewer_photo_url']);
        if ($image === null) {
            throw new HttpException(404);
        }
        return (new Response($image['body'], 200))
            ->withHeader('Content-Type', $image['type'])
            ->withHeader('Cache-Control', 'public, max-age=604800, immutable')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }

    private function xml(string $body): Response
    {
        return (new Response($body, 200))->withHeader('Content-Type', 'application/xml; charset=utf-8')->withHeader('Cache-Control', 'public, max-age=3600');
    }

    /**
     * @param list<array{label: string, href?: ?string}> $items
     * @return array<string, mixed>
     */
    private function breadcrumbList(array $items, string $lang): array
    {
        $list = [];
        foreach ($items as $i => $item) {
            $entry = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['label']];
            if (is_string($item['href'] ?? null)) {
                $entry['item'] = $this->urls->absolute($item['href']);
            }
            $list[] = $entry;
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list, 'inLanguage' => $lang];
    }

    private function redirectOr404(Request $request, string $lang): Response
    {
        try {
            $redirects = new RedirectRepository($this->app->db(), $this->app->clock);
            $target = $redirects->find($request->path());
            if ($target !== null) {
                $redirects->hit($request->path());
                return Response::redirect($target['to'], $target['status']);
            }
        } catch (\PDOException) {
            // No redirects table yet.
        }
        return $this->errorPage(404, $lang, 'not_found');
    }

    private function rememberLanguage(Request $request, Response $response, string $lang): Response
    {
        if ($request->cookie(LocaleResolver::COOKIE) !== $lang) {
            $response->withCookie(LocaleResolver::COOKIE, $lang, ['expires' => $this->app->clock->now()->getTimestamp() + 31536000, 'path' => '/', 'secure' => $request->isSecure(), 'httponly' => true, 'samesite' => 'Lax']);
        }
        return $response;
    }

    private function presenter(string $lang): SitePresenter
    {
        return new SitePresenter($this->content, $this->urls, $this->app->settings(), $this->app->languages(), $this->app->translator(), $this->app->clock, new ReviewRepository($this->app->db(), $this->app->clock));
    }

    /** @param array<string, string|int|float> $params */
    private function t(string $key, array $params = []): string
    {
        return $this->app->translator()->get($key, $params);
    }
}
