<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Site;

use Gate\Content\EntryTypes;
use Gate\Core\App;
use Gate\Core\Paths;
use Gate\Http\Flash;
use Gate\Http\HttpException;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\I18n\LocaleResolver;
use Gate\I18n\Translator;
use Gate\Mail\ContactMails;
use Gate\Mail\MailQueue;
use Gate\Repositories\ContentRepository;
use Gate\Repositories\EntryRepository;
use Gate\Repositories\MessageRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Repositories\SubscriberRepository;
use Gate\Security\Csrf;
use Gate\Security\SpamGuard;
use Gate\Site\Consent;
use Gate\Site\ContactForm;
use Gate\Site\Dates;
use Gate\Site\RichText;
use Gate\Site\Seo;
use Gate\Site\SitePresenter;
use Gate\Site\SiteUrls;

/**
 * The public website: language routing with translated slugs, pages (home, about and its child pages, lists of
 * expertise, projects, news, publications and albums, partners, contact, legal), detail pages, publication downloads,
 * the contact and newsletter forms, cookie consent, sitemaps, robots.txt, redirects for old URLs, maintenance mode
 * and styled error pages.
 *
 * @phpstan-import-type Page from ContentRepository
 * @phpstan-import-type Entry from EntryRepository
 */
final class SiteController
{
    private ContentRepository $content;
    private EntryRepository $entries;
    private SiteUrls $urls;

    public function __construct(private readonly App $app)
    {
        $languages = $app->languages();
        $this->content = new ContentRepository($app->db(), $languages->defaultCode());
        $this->entries = new EntryRepository($app->db(), $this->content);
        $this->urls = new SiteUrls($this->content, $this->entries, $languages->enabledCodes(), $languages->defaultCode(), $app->baseUrl());
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
        if ($rest === '/newsletter' && $request->method() === 'POST') {
            return $this->subscribe($request, $lang);
        }
        if (preg_match('#^/newsletter/(confirm|unsubscribe)/([a-f0-9]{48})$#', $rest, $nm) === 1 && $request->method() === 'GET') {
            return $this->newsletterLink($request, $lang, $nm[1], $nm[2]);
        }

        $route = $this->urls->resolve($lang, $match['rest']);
        if ($route === null) {
            return $this->redirectOr404($request, $lang);
        }
        if ($request->method() === 'POST') {
            if ($route['type'] === 'page' && $route['key'] === 'contact') {
                return $this->submitContact($request, $lang);
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
        $response = match ($route['type']) {
            'expertise' => $this->expertisePage($request, $lang, (int) $route['id']),
            'entry' => $this->entryPage($request, $lang, $route['key'], (int) $route['id']),
            'download' => $this->download((int) $route['id'], $lang),
            default => $this->page($request, $lang, $route['key']),
        };
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
        $alternates = $this->urls->alternates(['type' => 'page', 'key' => $key, 'id' => null]);
        $breadcrumbs = $this->breadcrumbs($page, $lang);
        $hero = $this->content->mediaItem($page['hero_media_id'], $lang);
        $data = ['page' => $page, 'breadcrumbs' => $breadcrumbs, 'hero' => $hero, 'cta' => $this->cta($lang)];
        $type = EntryTypes::forListPage($page['template']);
        $template = 'site/pages/text';
        $noindexList = false;

        if ($page['template'] === 'home') {
            $template = 'site/pages/home';
            $data['sections'] = $this->homeSections($lang);
        } elseif ($page['template'] === 'about') {
            $template = 'site/pages/about';
            $data['body'] = RichText::render($page['body']);
            $data['children'] = array_map(fn (array $c): array => ['title' => $c['nav_label'] !== '' ? $c['nav_label'] : $c['title'], 'intro' => $c['intro'], 'href' => $this->urls->page($c['key'], $lang)], $this->content->children($key, $lang));
            $data['stats'] = $this->content->stats($lang);
            $data['values'] = $this->content->section('home', 'about', $lang)['extra']['points'] ?? [];
        } elseif ($page['template'] === 'expertise') {
            $template = 'site/pages/expertise';
            $data['areas'] = $this->expertiseCards($lang, false);
        } elseif ($type !== null) {
            $template = $type === 'album' ? 'site/pages/gallery' : 'site/pages/list';
            $filters = $this->listFilters($request, $type);
            $result = $this->entries->paginate($type, $lang, $filters, max(1, (int) $request->query('page', '1')), EntryTypes::PER_PAGE[$type]);
            $data['type'] = $type;
            $data['cards'] = array_map(fn (array $e): array => $presenter->card($e, $lang), $result['rows']);
            $data['result'] = ['total' => $result['total'], 'page' => $result['page'], 'pages' => $result['pages']];
            $data['filters'] = $this->filterGroups($type, $lang, $key, $filters);
            $data['active'] = $filters;
            $data['pageUrl'] = fn (int $p): string => $this->urls->listPage($key, $lang, $this->filterQuery($filters) + ['page' => $p > 1 ? $p : null]);
            $data['clearHref'] = $this->urls->page($key, $lang);
            // Filtered and paged views are the same content in another order: search engines index the plain list.
            $noindexList = $filters !== [] || $result['page'] > 1;
        } elseif ($page['template'] === 'partners') {
            $template = 'site/pages/partners';
            $data['body'] = RichText::render($page['body']);
            $partners = $this->content->partners($lang);
            $data['donors'] = array_values(array_filter($partners, static fn (array $p): bool => $p['kind'] === 'donor'));
            $data['partners'] = array_values(array_filter($partners, static fn (array $p): bool => $p['kind'] !== 'donor'));
        } elseif ($page['template'] === 'contact') {
            $template = 'site/pages/contact';
            $data['form'] = $this->contactFormProps($lang, $formState);
            $data['details'] = $this->contactDetails($lang);
        } else {
            $data['body'] = RichText::render($page['body'], $this->replacements());
            $data['siblings'] = $page['parent_key'] !== ''
                ? array_map(fn (array $c): array => ['label' => $c['nav_label'], 'href' => $this->urls->page($c['key'], $lang), 'current' => $c['key'] === $key], $this->content->children($page['parent_key'], $lang))
                : [];
        }

        $title = $page['meta_title'] !== '' ? $page['meta_title'] : ($key === 'home' ? $this->siteName() . ' — ' . $page['title'] : $page['title'] . ' | ' . $this->siteName());
        $description = $page['meta_description'] !== '' ? $page['meta_description'] : $page['intro'];
        $path = $this->urls->page($key, $lang);
        $jsonld = $key === 'home' ? [] : [$this->breadcrumbList($breadcrumbs, $lang)];
        $head = $presenter->head($lang, $title, $description, $path, $alternates, $this->consent($request), $this->app->headers, $jsonld, $noindexList, $hero);
        if ($noindexList) {
            // Keep the canonical on the plain list so ranking signals collect there.
            $head['canonical'] = $this->urls->absolute($path);
            $head['noindex'] = false;
            $head['robots'] = 'noindex, follow';
        }
        return $this->render($request, $lang, $template, $data, $head, $page['parent_key'] !== '' ? $page['parent_key'] : $key, $alternates, $status);
    }

    private function expertisePage(Request $request, string $lang, int $id): Response
    {
        $area = $this->content->expertiseById($id, $lang);
        $page = $this->content->page('expertise', $lang);
        if ($area === null || $page === null) {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $presenter = $this->presenter($lang);
        $alternates = $this->urls->alternates(['type' => 'expertise', 'key' => 'expertise', 'id' => $id]);
        $breadcrumbs = [
            ['label' => $this->t('site.breadcrumbs.home'), 'href' => $this->urls->page('home', $lang)],
            ['label' => $page['nav_label'], 'href' => $this->urls->page('expertise', $lang)],
            ['label' => $area['title']],
        ];
        $cover = $this->content->mediaItem($area['cover_media_id'], $lang);
        $projects = array_map(fn (array $e): array => $presenter->card($e, $lang), $this->entries->latest('project', $lang, 3, true, ['expertise' => $id]));
        $news = array_map(fn (array $e): array => $presenter->card($e, $lang), $this->entries->latest('news', $lang, 3, false, ['expertise' => $id]));
        $others = array_values(array_filter($this->expertiseCards($lang, false), static fn (array $a): bool => $a['id'] !== $id));
        $title = $area['meta_title'] !== '' ? $area['meta_title'] : $area['title'] . ' | ' . $this->siteName();
        $path = $this->urls->expertise($id, $lang);
        $head = $presenter->head($lang, $title, $area['meta_description'] !== '' ? $area['meta_description'] : $area['summary'], $path, $alternates, $this->consent($request), $this->app->headers, [$this->breadcrumbList($breadcrumbs, $lang)], false, $cover);
        $projectsPage = $this->content->page('projects', $lang);
        return $this->render($request, $lang, 'site/pages/expertise-area', [
            'page' => $page,
            'area' => $area,
            'cover' => $cover,
            'body' => RichText::render($area['body']),
            'breadcrumbs' => $breadcrumbs,
            'projects' => $projects,
            'projectsHref' => $projectsPage !== null ? $this->urls->listPage('projects', $lang, ['sector' => $area['key']]) : null,
            'news' => $news,
            'others' => $others,
            'cta' => $this->cta($lang),
        ], $head, 'expertise', $alternates);
    }

    private function entryPage(Request $request, string $lang, string $type, int $id): Response
    {
        $entry = $this->entries->find($id, $lang);
        $listKey = EntryTypes::LIST_PAGES[$type] ?? '';
        $listPage = $this->content->page($listKey, $lang);
        if ($entry === null || $listPage === null || $entry['type'] !== $type) {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $presenter = $this->presenter($lang);
        $alternates = $this->urls->alternates(['type' => 'entry', 'key' => $type, 'id' => $id]);
        $breadcrumbs = [
            ['label' => $this->t('site.breadcrumbs.home'), 'href' => $this->urls->page('home', $lang)],
            ['label' => $listPage['nav_label'], 'href' => $this->urls->page($listKey, $lang)],
            ['label' => $entry['title']],
        ];
        $area = $entry['expertise_id'] !== null ? $this->content->expertiseById($entry['expertise_id'], $lang) : null;
        $gallery = EntryTypes::uses($type, 'gallery') ? $this->entries->gallery($id, $lang) : [];
        $file = $type === 'publication' ? $this->content->mediaItem($entry['file_media_id'], $lang) : null;
        $related = $entry['related_id'] !== null ? $this->entries->find($entry['related_id'], $lang) : null;
        $more = array_map(fn (array $e): array => $presenter->card($e, $lang), $this->entries->latest($type, $lang, 3, false, ['exclude' => $id] + ($area !== null ? ['expertise' => $area['id']] : [])));
        if ($more === []) {
            $more = array_map(fn (array $e): array => $presenter->card($e, $lang), $this->entries->latest($type, $lang, 3, false, ['exclude' => $id]));
        }
        $updates = $type === 'project'
            ? array_map(fn (array $e): array => $presenter->card($e, $lang), array_merge($this->entries->latest('news', $lang, 3, false, ['related' => $id]), $this->entries->latest('album', $lang, 2, false, ['related' => $id])))
            : [];
        $facts = $this->facts($entry, $lang, $area, $file);
        $title = $entry['meta_title'] !== '' ? $entry['meta_title'] : $entry['title'] . ' | ' . $this->siteName();
        $path = $this->urls->entry($type, $entry['slug'], $lang);
        $jsonld = [$this->breadcrumbList($breadcrumbs, $lang), $this->entryLd($entry, $path, $lang)];
        $head = $presenter->head($lang, $title, $entry['meta_description'] !== '' ? $entry['meta_description'] : $entry['summary'], $path, $alternates, $this->consent($request), $this->app->headers, $jsonld, false, $entry['cover'], $type === 'news' ? 'article' : 'website');
        return $this->render($request, $lang, 'site/pages/entry', [
            'page' => $listPage,
            'entry' => $entry,
            'type' => $type,
            'body' => RichText::render($entry['body']),
            'breadcrumbs' => $breadcrumbs,
            'area' => $area !== null ? ['title' => $area['title'], 'href' => $this->urls->expertise($area['id'], $lang)] : null,
            'gallery' => $gallery,
            'file' => $file !== null ? ['href' => $this->urls->download($entry['slug'], $lang), 'size' => self::bytes($file['size'], $lang), 'pages' => $file['pages']] : null,
            'related' => $related !== null ? ['title' => $related['title'], 'href' => $this->urls->entry($related['type'], $related['slug'], $lang)] : null,
            'facts' => $facts,
            'date' => Dates::long($entry['published_on'], $lang),
            'more' => $more,
            'updates' => $updates,
            'backHref' => $this->urls->page($listKey, $lang),
            'cta' => $this->cta($lang),
        ], $head, $listKey, $alternates);
    }

    /** The PDF of a publication, with a readable file name and headers that keep the browser from sniffing it. */
    private function download(int $id, string $lang): Response
    {
        $entry = $this->entries->find($id, $lang);
        $file = $entry !== null ? $this->content->mediaItem($entry['file_media_id'], $lang) : null;
        if ($entry === null || $file === null || $file['kind'] !== 'document') {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $path = Paths::publicDir(ltrim($file['url'], '/'));
        if (!is_file($path)) {
            return $this->errorPage(404, $lang, 'not_found');
        }
        $name = ($entry['slug'] !== '' ? $entry['slug'] : 'publication') . '.pdf';
        return Response::file($path, $name, 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withHeader('X-Robots-Tag', 'noindex');
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $head
     * @param array<string, string> $alternates
     */
    private function render(Request $request, string $lang, string $template, array $data, array $head, string $activeKey, array $alternates, int $status = 200): Response
    {
        $view = $this->app->view();
        $toast = Flash::pullToast($this->app->session());
        $site = $this->presenter($lang)->layout(
            $lang,
            $activeKey,
            $alternates,
            (string) strtok($request->path(), '?'),
            $this->consent($request),
            $request->query('cookies') === 'settings',
            $toast !== null ? ['type' => $toast['type'], 'message' => $this->t($toast['key'], $toast['params'])] : null,
            $this->newsletterProps($lang),
        );
        $content = $view->render($template, $data + ['lang' => $lang]);
        $this->scheduleMail();
        return Response::html($view->render('layouts/site', ['content' => $content, 'head' => $head, 'site' => $site]), $status);
    }

    public function errorPage(int $status, string $lang, string $key, ?string $reference = null): Response
    {
        $this->app->noStore();
        try {
            $presenter = $this->presenter($lang);
            $head = $presenter->head($lang, $status . ' — ' . $this->t('site.errors.' . $key . '_title'), $this->t('site.errors.' . $key . '_text'), $this->urls->page('home', $lang), [], Consent::fromCookie(null, 1), $this->app->headers, [], true);
            $site = $presenter->layout($lang, '', [], '/' . $lang . '/', Consent::fromCookie('v0.n.1000000000', 0), false, null, $this->newsletterProps($lang));
            $site['cookie'] = null;
            $view = $this->app->view();
            $contact = $key === 'maintenance' || $this->content->page('contact', $lang) === null ? null : $this->urls->page('contact', $lang);
            $content = $view->render('site/pages/error', ['status' => $status, 'key' => $key, 'homeHref' => $this->urls->page('home', $lang), 'contactHref' => $contact, 'reference' => $reference, 'lang' => $lang]);
            return Response::html($view->render('layouts/site', ['content' => $content, 'head' => $head, 'site' => $site]), $status);
        } catch (\Throwable) {
            return $this->app->errorResponse($status);
        }
    }

    // ---------------------------------------------------------------------------------------------- home page

    /** @return list<array<string, mixed>> the visible home sections with their data */
    private function homeSections(string $lang): array
    {
        $presenter = $this->presenter($lang);
        $out = [];
        foreach ($this->content->sections('home', $lang) as $section) {
            if (!$section['is_enabled']) {
                continue;
            }
            $data = $section + ['media' => $this->content->mediaItem($section['media_id'], $lang)];
            switch ($section['type']) {
                case 'about':
                    $about = $this->content->page('about', $lang);
                    $data['href'] = $about !== null ? $this->urls->page('about', $lang) : null;
                    $data['media2'] = $this->content->mediaItem(isset($section['settings']['media2']) && is_int($section['settings']['media2']) ? $section['settings']['media2'] : null, $lang);
                    break;
                case 'expertise':
                    $data['areas'] = $this->expertiseCards($lang, true);
                    if ($data['areas'] === []) {
                        continue 2;
                    }
                    break;
                case 'stats':
                    $data['stats'] = $this->content->stats($lang);
                    if ($data['stats'] === []) {
                        continue 2;
                    }
                    break;
                case 'projects':
                case 'news':
                    $type = $section['type'] === 'projects' ? 'project' : 'news';
                    $data['cards'] = array_map(fn (array $e): array => $presenter->card($e, $lang), $this->entries->latest($type, $lang, 3, $type === 'project'));
                    $data['href'] = $this->content->page($section['type'], $lang) !== null ? $this->urls->page($section['type'], $lang) : null;
                    if ($data['cards'] === []) {
                        continue 2;
                    }
                    break;
                case 'map':
                    $counts = $this->entries->regionCounts($lang);
                    $data['regions'] = $counts;
                    $data['projectsHref'] = $this->content->page('projects', $lang) !== null ? $this->urls->page('projects', $lang) : null;
                    $data['regionHref'] = fn (string $r): string => $this->urls->listPage('projects', $lang, ['region' => $r]);
                    break;
                case 'partners':
                    $data['partners'] = $this->content->partners($lang);
                    $data['href'] = $this->content->page('partners', $lang) !== null ? $this->urls->page('partners', $lang) : null;
                    if ($data['partners'] === []) {
                        continue 2;
                    }
                    break;
                case 'cta':
                    $data['cta'] = $this->cta($lang);
                    break;
                case 'hero':
                    $projects = $this->content->page('projects', $lang);
                    $about = $this->content->page('about', $lang);
                    $data['primary'] = $projects !== null ? ['label' => $this->t('site.hero.projects'), 'href' => $this->urls->page('projects', $lang)] : null;
                    $data['secondary'] = $about !== null ? ['label' => $this->t('site.hero.about'), 'href' => $this->urls->page('about', $lang)] : null;
                    break;
            }
            $out[] = $data;
        }
        return $out;
    }

    /** @return list<array{id: int, key: string, icon: string, title: string, summary: string, href: string}> */
    private function expertiseCards(string $lang, bool $homeOnly): array
    {
        $out = [];
        foreach ($this->content->expertise($lang) as $area) {
            if ($homeOnly && !$area['show_on_home']) {
                continue;
            }
            $out[] = ['id' => $area['id'], 'key' => $area['key'], 'icon' => $area['icon'], 'title' => $area['title'], 'summary' => $area['summary'], 'href' => $this->urls->expertise($area['id'], $lang)];
        }
        return $out;
    }

    // ------------------------------------------------------------------------------------------- list filters

    /** @return array{expertise?: int, region?: string, status?: string, year?: int} */
    private function listFilters(Request $request, string $type): array
    {
        $filters = [];
        $sector = $request->query('sector');
        if ($sector !== '' && EntryTypes::uses($type, 'expertise')) {
            $area = $this->content->expertiseByKey($sector, $this->app->translator()->locale());
            if ($area !== null) {
                $filters['expertise'] = $area['id'];
            }
        }
        $region = $request->query('region');
        if (EntryTypes::uses($type, 'region') && in_array($region, EntryTypes::REGIONS, true)) {
            $filters['region'] = $region;
        }
        $status = $request->query('status');
        if (in_array($status, EntryTypes::statuses($type), true)) {
            $filters['status'] = $status;
        }
        $year = $request->query('year');
        if (preg_match('/^(19|20)\d{2}$/', $year) === 1 && in_array($type, ['news', 'publication', 'album'], true)) {
            $filters['year'] = (int) $year;
        }
        return $filters;
    }

    /**
     * @param array{expertise?: int, region?: string, status?: string, year?: int} $filters
     * @return array<string, string|int|null>
     */
    private function filterQuery(array $filters): array
    {
        $lang = $this->app->translator()->locale();
        $area = isset($filters['expertise']) ? $this->content->expertiseById($filters['expertise'], $lang) : null;
        return [
            'sector' => $area['key'] ?? null,
            'region' => $filters['region'] ?? null,
            'status' => $filters['status'] ?? null,
            'year' => $filters['year'] ?? null,
        ];
    }

    /**
     * Filter chips of a list page: only values that occur, each a link that toggles it.
     *
     * @param array{expertise?: int, region?: string, status?: string, year?: int} $active
     * @return list<array{name: string, label: string, options: list<array{label: string, href: string, active: bool}>}>
     */
    private function filterGroups(string $type, string $lang, string $pageKey, array $active): array
    {
        $facets = $this->entries->facets($type, $lang);
        $query = $this->filterQuery($active);
        $link = function (string $param, string|int|null $value) use ($query, $pageKey, $lang): string {
            $q = $query;
            $q[$param] = $q[$param] === $value ? null : $value;
            return $this->urls->listPage($pageKey, $lang, $q);
        };
        $groups = [];
        if ($facets['statuses'] !== []) {
            $prefix = $type === 'project' ? 'site.status.' : 'site.kinds.';
            $groups[] = ['name' => 'status', 'label' => $this->t($type === 'project' ? 'site.filters.status' : 'site.filters.kind'), 'options' => array_map(fn (string $s): array => ['label' => $this->t($prefix . $s), 'href' => $link('status', $s), 'active' => ($active['status'] ?? null) === $s], $facets['statuses'])];
        }
        if ($facets['expertise'] !== [] && EntryTypes::uses($type, 'expertise')) {
            $options = [];
            foreach ($this->content->expertise($lang) as $area) {
                if (in_array($area['id'], $facets['expertise'], true)) {
                    $options[] = ['label' => $area['title'], 'href' => $link('sector', $area['key']), 'active' => ($active['expertise'] ?? null) === $area['id']];
                }
            }
            if (count($options) > 1 || isset($active['expertise'])) {
                $groups[] = ['name' => 'sector', 'label' => $this->t('site.filters.sector'), 'options' => $options];
            }
        }
        if ($facets['regions'] !== [] && EntryTypes::uses($type, 'region') && (count($facets['regions']) > 1 || isset($active['region']))) {
            $groups[] = ['name' => 'region', 'label' => $this->t('site.filters.region'), 'options' => array_map(fn (string $r): array => ['label' => $this->t('site.regions.' . $r), 'href' => $link('region', $r), 'active' => ($active['region'] ?? null) === $r], $facets['regions'])];
        }
        if (in_array($type, ['news', 'publication', 'album'], true) && (count($facets['years']) > 1 || isset($active['year']))) {
            $groups[] = ['name' => 'year', 'label' => $this->t('site.filters.year'), 'options' => array_map(fn (int $y): array => ['label' => (string) $y, 'href' => $link('year', $y), 'active' => ($active['year'] ?? null) === $y], $facets['years'])];
        }
        return $groups;
    }

    /**
     * Key facts of an entry (the side panel of the detail page).
     *
     * @param Entry $entry
     * @param array<string, mixed>|null $area
     * @param array<string, mixed>|null $file
     * @return list<array{label: string, value: string, href?: string}>
     */
    private function facts(array $entry, string $lang, ?array $area, ?array $file): array
    {
        $facts = [];
        if ($entry['type'] === 'project') {
            if ($entry['status'] !== '') {
                $facts[] = ['label' => $this->t('site.entries.status'), 'value' => $this->t('site.status.' . $entry['status'])];
            }
            if ($area !== null) {
                $facts[] = ['label' => $this->t('site.entries.sector'), 'value' => (string) $area['title'], 'href' => $this->urls->expertise((int) $area['id'], $lang)];
            }
            if ($entry['region'] !== '') {
                $facts[] = ['label' => $this->t('site.entries.region'), 'value' => $this->t('site.regions.' . $entry['region'])];
            }
            if ($entry['location'] !== '') {
                $facts[] = ['label' => $this->t('site.entries.location'), 'value' => $entry['location']];
            }
            $period = trim(Dates::month($entry['start_date'], $lang) . ($entry['end_date'] !== '' ? ' – ' . Dates::month($entry['end_date'], $lang) : ''), ' –');
            if ($period !== '') {
                $facts[] = ['label' => $this->t('site.entries.period'), 'value' => $period];
            }
            if ($entry['beneficiaries'] !== null && $entry['beneficiaries'] > 0) {
                $facts[] = ['label' => $this->t('site.entries.beneficiaries'), 'value' => SitePresenter::number($entry['beneficiaries'], $lang)];
            }
            if ($entry['donors'] !== '') {
                $facts[] = ['label' => $this->t('site.entries.donors'), 'value' => $entry['donors']];
            }
        } elseif ($entry['type'] === 'publication') {
            if ($entry['status'] !== '') {
                $facts[] = ['label' => $this->t('site.entries.kind'), 'value' => $this->t('site.kinds.' . $entry['status'])];
            }
            $facts[] = ['label' => $this->t('site.entries.published'), 'value' => Dates::long($entry['published_on'], $lang)];
            if ($area !== null) {
                $facts[] = ['label' => $this->t('site.entries.sector'), 'value' => (string) $area['title'], 'href' => $this->urls->expertise((int) $area['id'], $lang)];
            }
            if ($file !== null && (int) $file['pages'] > 0) {
                $facts[] = ['label' => $this->t('site.entries.pages'), 'value' => (string) $file['pages']];
            }
        }
        return $facts;
    }

    // ------------------------------------------------------------------------------------------- contact form

    private function submitContact(Request $request, string $lang): Response
    {
        $settings = $this->app->settings();
        $guard = $this->guard();
        $values = ContactForm::values(['name' => $request->input('name'), 'email' => $request->input('email'), 'phone' => $request->input('phone'), 'organisation' => $request->input('organisation'), 'subject' => $request->input('subject'), 'message' => $request->input('message'), 'consent' => $request->input('consent')]);
        $spam = $guard->check('contact', ['honeypot' => $request->input(SpamGuard::HONEYPOT), 'token' => $request->input(SpamGuard::TIMESTAMP)], $request->ip());
        if ($spam === 'honeypot') {
            // Bots get the normal success response; nothing is stored or sent.
            Flash::toast($this->app->session(), 'success', 'site.form.sent');
            return Response::redirect($this->urls->page('contact', $lang, 'contact-form'), 303);
        }
        if ($spam !== 'ok') {
            $message = $spam === 'rate_limited' ? 'site.form.rate_limited' : 'site.form.blocked';
            return $this->page($request, $lang, 'contact', ['values' => $values, 'errors' => ['form' => $this->t($message)]], $spam === 'rate_limited' ? 429 : 422);
        }
        $errors = [];
        foreach (ContactForm::validate($values) as $field => [$key, $params]) {
            $errors[$field] = $this->t($key, $params);
        }
        if ($errors !== []) {
            return $this->page($request, $lang, 'contact', ['values' => $values, 'errors' => $errors], 422);
        }

        $guard->recordSubmission('contact', $request->ip());
        $messages = new MessageRepository($this->app->db(), $this->app->clock);
        $id = $messages->create($values, $lang, $this->app->crypto()->hmac('ip|' . $request->ip()), $request->userAgent());

        if ($settings->bool('mail.enabled', true)) {
            $siteName = $this->siteName();
            $queue = new MailQueue($this->app->db(), $this->app->clock);
            $to = $settings->string('mail.to_email');
            if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                $to = trim($settings->string('contact.email'), '[]');
            }
            if (filter_var($to, FILTER_VALIDATE_EMAIL) !== false) {
                $adminLang = $settings->string('admin.language', 'en');
                $queue->enqueue(ContactMails::organisation($values, $id, $to, $siteName, $this->urls->absolute($this->app->adminPath('messages/' . $id)), new Translator($adminLang, 'en', $this->app->db())));
            }
            $queue->enqueue(ContactMails::sender($values, $siteName, $to, new Translator($lang, 'en', $this->app->db())));
            $this->app->defer(fn () => $this->app->sendQueuedMail());
        }
        $this->app->audit()->record('message.received', null, ['id' => $id, 'lang' => $lang]);
        Flash::toast($this->app->session(), 'success', 'site.form.sent');
        return Response::redirect($this->urls->page('contact', $lang, 'contact-form'), 303);
    }

    /**
     * @param array{values?: array<string, mixed>, errors?: array<string, string>} $state
     * @return array<string, mixed>
     */
    private function contactFormProps(string $lang, array $state): array
    {
        return [
            'action' => $this->urls->page('contact', $lang),
            'token' => $this->guard()->token('contact'),
            'values' => $state['values'] ?? [],
            'errors' => $state['errors'] ?? [],
            'subjects' => MessageRepository::SUBJECTS,
            'privacyHref' => $this->content->page('privacy', $lang) !== null ? $this->urls->page('privacy', $lang) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function contactDetails(string $lang): array
    {
        $s = $this->app->settings();
        $real = static fn (string $v): string => str_contains($v, '[') ? '' : trim($v);
        $phone = $real($s->string('contact.phone'));
        $email = $real($s->string('contact.email'));
        return [
            'address' => $this->presenter($lang)->address($lang),
            'mapHref' => SitePresenter::mapsUrl($s->string('contact.latitude'), $s->string('contact.longitude')),
            'phone' => $phone,
            'phoneHref' => SitePresenter::telHref($phone),
            'email' => $email,
            'emailHref' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:' . $email : null,
            'hours' => $real($s->string('contact.hours')),
        ];
    }

    // ---------------------------------------------------------------------------------------------- newsletter

    /** @return array{action: string, token: string} */
    private function newsletterProps(string $lang): array
    {
        return ['action' => '/' . $lang . '/newsletter', 'token' => $this->guard()->token('newsletter')];
    }

    private function subscribe(Request $request, string $lang): Response
    {
        $back = $this->safeReturn($request->input('return'), $lang);
        if (!$this->app->settings()->bool('site.newsletter_enabled', true)) {
            throw new HttpException(404);
        }
        $guard = $this->guard();
        $spam = $guard->check('newsletter', ['honeypot' => $request->input(SpamGuard::HONEYPOT), 'token' => $request->input(SpamGuard::TIMESTAMP)], $request->ip());
        $email = mb_strtolower(trim($request->input('email')));
        if ($spam === 'honeypot') {
            Flash::toast($this->app->session(), 'success', 'site.newsletter.check_inbox');
            return Response::redirect($back, 303);
        }
        if ($spam !== 'ok') {
            Flash::toast($this->app->session(), 'error', $spam === 'rate_limited' ? 'site.form.rate_limited' : 'site.form.blocked');
            return Response::redirect($back, 303);
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190 || $request->input('consent') !== '1') {
            Flash::toast($this->app->session(), 'error', filter_var($email, FILTER_VALIDATE_EMAIL) === false ? 'site.newsletter.invalid' : 'site.newsletter.consent');
            return Response::redirect($back, 303);
        }
        $guard->recordSubmission('newsletter', $request->ip());
        $token = (new SubscriberRepository($this->app->db(), $this->app->crypto(), $this->app->clock))->subscribe($email, $lang, $this->app->crypto()->hmac('ip|' . $request->ip()));
        if ($token !== null && $this->app->settings()->bool('mail.enabled', true)) {
            (new MailQueue($this->app->db(), $this->app->clock))->enqueue(ContactMails::newsletterConfirm($email, $this->urls->absolute('/' . $lang . '/newsletter/confirm/' . $token), $this->siteName(), new Translator($lang, 'en', $this->app->db())));
            $this->app->defer(fn () => $this->app->sendQueuedMail());
        }
        // The same answer whether the address was new, pending or already confirmed.
        Flash::toast($this->app->session(), 'success', 'site.newsletter.check_inbox');
        return Response::redirect($back, 303);
    }

    private function newsletterLink(Request $request, string $lang, string $action, string $token): Response
    {
        $repo = new SubscriberRepository($this->app->db(), $this->app->crypto(), $this->app->clock);
        $ok = $action === 'confirm' ? $repo->confirm($token) : $repo->unsubscribe($token);
        $this->app->noStore();
        $key = $ok ? ($action === 'confirm' ? 'confirmed' : 'unsubscribed') : 'invalid_link';
        $presenter = $this->presenter($lang);
        $head = $presenter->head($lang, $this->t('site.newsletter.' . $key . '_title') . ' | ' . $this->siteName(), '', '/' . $lang . '/', [], $this->consent($request), $this->app->headers, [], true);
        $site = $presenter->layout($lang, '', [], '/' . $lang . '/', $this->consent($request), false, null, $this->newsletterProps($lang));
        $site['cookie'] = null;
        $view = $this->app->view();
        $content = $view->render('site/pages/notice', ['title' => $this->t('site.newsletter.' . $key . '_title'), 'text' => $this->t('site.newsletter.' . $key . '_text'), 'homeHref' => $this->urls->page('home', $lang), 'lang' => $lang, 'ok' => $ok]);
        return Response::html($view->render('layouts/site', ['content' => $content, 'head' => $head, 'site' => $site]), $ok ? 200 : 404)->withHeader('X-Robots-Tag', 'noindex');
    }

    // ------------------------------------------------------------------------------------------ consent, mail, SEO

    private function saveConsent(Request $request, string $lang): Response
    {
        $version = $this->app->settings()->int('consent.version', 1);
        $analytics = $request->input('analytics') === '1';
        $return = $this->safeReturn($request->input('return'), $lang);
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

    /** A local return path ("/en/projects?region=akkar"), else the home page of the language. */
    private function safeReturn(string $return, string $lang): string
    {
        return preg_match('#^/[a-z]{2}(/[A-Za-z0-9._~%-]*)*(\?[A-Za-z0-9=&%._~-]*)?(\#[A-Za-z0-9_-]*)?$#', $return) === 1 ? $return : '/' . $lang . '/';
    }

    private function consent(Request $request): Consent
    {
        return Consent::fromCookie($request->cookie(Consent::COOKIE), $this->app->settings()->int('consent.version', 1));
    }

    private function guard(): SpamGuard
    {
        $settings = $this->app->settings();
        return new SpamGuard($this->app->crypto(), $this->app->limiter(), $this->app->clock, $settings->int('forms.min_seconds', 3), $settings->int('forms.max_per_hour', 5));
    }

    private function scheduleMail(): void
    {
        try {
            if ((new MailQueue($this->app->db(), $this->app->clock))->hasDue()) {
                $this->app->defer(fn () => $this->app->sendQueuedMail());
            }
        } catch (\Throwable) {
            // The site keeps working when the queue cannot be read.
        }
    }

    /** @return array{title: string, text: string, primary: array{label: string, href: string}|null, secondary: array{label: string, href: string}|null} */
    private function cta(string $lang): array
    {
        $contact = $this->content->page('contact', $lang);
        $profile = $this->content->page('profile', $lang);
        return [
            'title' => $this->t('site.cta.title'),
            'text' => $this->t('site.cta.text'),
            'primary' => $contact !== null ? ['label' => $this->t('site.cta.contact'), 'href' => $this->urls->page('contact', $lang)] : null,
            'secondary' => $profile !== null ? ['label' => $this->t('site.cta.profile'), 'href' => $this->urls->page('profile', $lang)] : null,
        ];
    }

    /** @return array<string, string> [placeholders] the legal texts may use */
    private function replacements(): array
    {
        $s = $this->app->settings();
        return [
            'organisation' => $s->string('org.legal_name', $this->siteName()),
            'registration' => $s->string('org.registration'),
            'address' => trim($s->string('contact.street') . ', ' . $s->string('contact.area') . ', ' . $s->string('contact.city') . ', ' . $s->string('contact.country'), ', '),
            'email' => $s->string('contact.email'),
            'phone' => $s->string('contact.phone'),
            'website' => $this->urls->base(),
        ];
    }

    /**
     * @param Page $page
     * @return list<array{label: string, href?: string}>
     */
    private function breadcrumbs(array $page, string $lang): array
    {
        if ($page['key'] === 'home') {
            return [];
        }
        $crumbs = [['label' => $this->t('site.breadcrumbs.home'), 'href' => $this->urls->page('home', $lang)]];
        if ($page['parent_key'] !== '') {
            $parent = $this->content->page($page['parent_key'], $lang);
            if ($parent !== null) {
                $crumbs[] = ['label' => $parent['nav_label'], 'href' => $this->urls->page($parent['key'], $lang)];
            }
        }
        $crumbs[] = ['label' => $page['nav_label'] !== '' ? $page['nav_label'] : $page['title']];
        return $crumbs;
    }

    /**
     * @param Entry $entry
     * @return array<string, mixed>
     */
    private function entryLd(array $entry, string $path, string $lang): array
    {
        $base = [
            '@context' => 'https://schema.org',
            'name' => $entry['title'],
            'headline' => $entry['title'],
            'description' => $entry['summary'],
            'url' => $this->urls->absolute($path),
            'inLanguage' => $entry['lang'],
            'datePublished' => $entry['published_on'],
            'dateModified' => substr($entry['updated_at'], 0, 10),
            'publisher' => ['@id' => $this->urls->base() . '/#organization'],
        ];
        if ($entry['cover'] !== null) {
            $base['image'] = $this->urls->absolute($entry['cover']['url']);
        }
        return match ($entry['type']) {
            'news' => ['@type' => 'NewsArticle', 'author' => ['@id' => $this->urls->base() . '/#organization']] + $base,
            'publication' => ['@type' => 'Report'] + $base,
            'album' => ['@type' => 'ImageGallery'] + $base,
            default => ['@type' => 'Project', 'sponsor' => $entry['donors'] !== '' ? $entry['donors'] : null, 'areaServed' => $entry['region'] !== '' ? $this->t('site.regions.' . $entry['region']) : 'Lebanon'] + $base,
        };
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
            $alternates = $this->urls->alternates(['type' => 'page', 'key' => $page['key'], 'id' => null]);
            $entries[] = ['loc' => $this->urls->absolute($this->urls->page($page['key'], $lang)), 'lastmod' => substr($page['updated_at'], 0, 10), 'alternates' => count($alternates) > 1 ? $abs($alternates) : []];
        }
        foreach ($this->content->expertise($lang) as $area) {
            if ($area['lang'] !== $lang) {
                continue;
            }
            $alternates = $this->urls->alternates(['type' => 'expertise', 'key' => 'expertise', 'id' => $area['id']]);
            $entries[] = ['loc' => $this->urls->absolute($this->urls->expertise($area['id'], $lang)), 'lastmod' => substr($area['updated_at'], 0, 10), 'alternates' => count($alternates) > 1 ? $abs($alternates) : []];
        }
        foreach ($this->entries->sitemap($lang) as $entry) {
            $alternates = $this->urls->alternates(['type' => 'entry', 'key' => $entry['type'], 'id' => $entry['id']]);
            $entries[] = ['loc' => $this->urls->absolute($this->urls->entry($entry['type'], $entry['slug'], $lang)), 'lastmod' => substr($entry['updated_at'], 0, 10), 'alternates' => count($alternates) > 1 ? $abs($alternates) : []];
        }
        return $entries;
    }

    private function lastModified(): string
    {
        $latest = (string) $this->app->db()->scalar('SELECT GREATEST(COALESCE((SELECT MAX(`updated_at`) FROM {pages}), \'2000-01-01\'), COALESCE((SELECT MAX(`updated_at`) FROM {entries}), \'2000-01-01\'), COALESCE((SELECT MAX(`updated_at`) FROM {expertise}), \'2000-01-01\'))');
        return $latest === '' || str_starts_with($latest, '2000') ? $this->app->clock->now()->format('Y-m-d') : substr($latest, 0, 10);
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
        return new SitePresenter($this->content, $this->entries, $this->urls, $this->app->settings(), $this->app->languages(), $this->app->translator(), $this->app->clock);
    }

    private function siteName(): string
    {
        return $this->app->settings()->string('site.name', 'GATE Lebanon');
    }

    private static function bytes(int $bytes, string $lang): string
    {
        $mb = $bytes / 1048576;
        $value = $mb >= 1 ? number_format($mb, 1, $lang === 'fr' ? ',' : '.', '') . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
        return $lang === 'ar' ? str_replace(['MB', 'KB'], ['م.ب', 'ك.ب'], $value) : ($lang === 'fr' ? str_replace(['MB', 'KB'], ['Mo', 'Ko'], $value) : $value);
    }

    /** @param array<string, string|int|float> $params */
    private function t(string $key, array $params = []): string
    {
        return $this->app->translator()->get($key, $params);
    }
}
