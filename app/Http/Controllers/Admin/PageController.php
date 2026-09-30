<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Content\EntryTypes;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\ContentAdminRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Services\AuditLog;
use Gate\Services\SectionOrder;

/**
 * Pages: the system pages (About with its child pages) with their texts, SEO fields, page image and slug per language,
 * a publish state per language and — on the home page — the order, visibility, texts and images of its sections.
 *
 * Changing a slug keeps the old address working: a 301 redirect to the new one is added automatically.
 */
final class PageController extends ContentController
{
    public function index(Request $request): Response
    {
        $content = $this->content();
        $languages = $this->app->languages()->enabledCodes();
        $rows = [];
        $pages = $content->pages();
        $keys = array_column($pages, 'key', 'id');
        // Children right after their parent (the list mirrors the menu).
        usort($pages, static function (array $a, array $b): int {
            $ka = [$a['parent_id'] !== null ? (int) $a['parent_id'] : (int) $a['id'], $a['parent_id'] !== null ? 1 : 0, (int) $a['nav_order']];
            $kb = [$b['parent_id'] !== null ? (int) $b['parent_id'] : (int) $b['id'], $b['parent_id'] !== null ? 1 : 0, (int) $b['nav_order']];
            return $ka <=> $kb;
        });
        foreach ($pages as $page) {
            $translations = $content->pageTranslations((int) $page['id']);
            $states = [];
            foreach ($languages as $lang) {
                $row = $translations[$lang] ?? null;
                $states[$lang] = $row === null ? 'missing' : ((int) ($row['is_published'] ?? 1) === 1 ? 'published' : 'draft');
            }
            $rows[] = [
                'id' => (int) $page['id'],
                'key' => (string) $page['key'],
                'child' => $page['parent_id'] !== null,
                'parent' => $page['parent_id'] !== null ? (string) ($keys[(int) $page['parent_id']] ?? '') : '',
                // The menu label reads better in a list than the page heading ("Automatic transmissions,").
                'title' => (string) ($translations[$this->app->languages()->defaultCode()]['nav_label'] ?? '') !== ''
                    ? (string) $translations[$this->app->languages()->defaultCode()]['nav_label']
                    : (string) ($translations[$this->app->languages()->defaultCode()]['title'] ?? $page['key']),
                'enabled' => (int) $page['is_enabled'] === 1,
                'in_nav' => (int) $page['in_nav'] === 1,
                'updated' => $this->app->formatDate((string) $page['updated_at'], false),
                'states' => $states,
            ];
        }
        return $this->adminView('admin/pages', 'pages', $this->t('admin.pages.title'), $this->t('admin.pages.subtitle'), [
            'pages' => $rows,
            'languages' => $languages,
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function edit(Request $request): Response
    {
        $content = $this->content();
        $page = $content->page((int) $request->param('id'));
        if ($page === null) {
            return $this->app->errorResponse(404);
        }
        $id = (int) $page['id'];
        $lang = $this->editLang($request->query('lang'));
        $translations = $content->pageTranslations($id);
        $translation = $translations[$lang] ?? [];
        $sections = [];
        if ((string) $page['template'] === 'home') {
            foreach (SectionOrder::sort($content->sections($id)) as $section) {
                $sectionTranslations = $content->sectionTranslations((int) $section['id']);
                $sections[] = [
                    'id' => (int) $section['id'],
                    'type' => (string) $section['type'],
                    'name' => $this->t('admin.sections.' . $section['type']),
                    'enabled' => (int) $section['is_enabled'] === 1,
                    'locked' => (int) $section['is_locked'] === 1,
                    'title' => trim((string) ($sectionTranslations[$lang]['title'] ?? $sectionTranslations[$this->app->languages()->defaultCode()]['title'] ?? '')),
                ];
            }
        }
        $old = $this->pullArray('page_old');
        return $this->adminView('admin/page-edit', 'pages', $this->t('admin.pages.edit_title', ['title' => (string) ($translation['title'] ?? $page['key'])]), $this->t('admin.pages.subtitle'), [
            'page' => [
                'id' => $id,
                'key' => (string) $page['key'],
                'template' => (string) $page['template'],
                'is_enabled' => (int) $page['is_enabled'] === 1,
                'in_nav' => (int) $page['in_nav'] === 1,
                'nav_order' => (int) $page['nav_order'],
                'in_sitemap' => (int) $page['in_sitemap'] === 1,
                'hero' => $page['hero_media_id'] !== null ? (string) $page['hero_media_id'] : '',
                'child' => $page['parent_id'] !== null,
            ],
            'images' => $this->mediaOptions('image', $this->t('admin.media.none')),
            'lang' => $lang,
            'values' => $old !== [] ? $old : $this->translationValues($translation),
            'published' => $old !== [] ? ($old['is_published'] ?? '1') === '1' : (int) ($translation['is_published'] ?? 1) === 1,
            'tabs' => $this->languageTabs($this->app->adminPath('pages/' . $id), $lang, $translations),
            'errors' => $this->pullArray('page_errors'),
            'sections' => $sections,
            'canEdit' => $this->can('content.edit'),
            'previewUrl' => $this->pagePath($lang, $this->parentSlug($page, $lang) . (string) ($translation['slug'] ?? '')),
        ]);
    }

    public function save(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        $page = $content->page($id);
        if ($page === null) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->input('lang'));
        $isHome = (string) $page['key'] === 'home';
        $values = [
            'slug' => $isHome ? '' : self::slug($request->input('slug')),
            'nav_label' => self::line($request->input('nav_label'), 80),
            'label' => self::line($request->input('label'), 120),
            'title' => self::line($request->input('title')),
            'highlight' => self::line($request->input('highlight')),
            'intro' => self::text($request->input('intro')),
            'body' => self::richText($request->input('body')),
            'meta_title' => self::line($request->input('meta_title')),
            'meta_description' => self::line($request->input('meta_description'), 320),
        ];
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = $this->t('validation.required');
        }
        // An empty slug in another language takes the default language's slug (Arabic titles have no Latin letters).
        if (!$isHome && $values['slug'] === '' && $lang !== $this->app->languages()->defaultCode()) {
            $defaultSlug = $content->pageTranslations($id)[$this->app->languages()->defaultCode()]['slug'] ?? '';
            $values['slug'] = is_string($defaultSlug) ? $defaultSlug : '';
        }
        if (!$isHome && $values['slug'] === '') {
            $errors['slug'] = $this->t('validation.required');
        } elseif (!$isHome && ($content->pageSlugTaken($lang, $values['slug'], $id) || $this->reservedSlug($page, $values['slug']))) {
            $errors['slug'] = $this->t('admin.pages.slug_taken');
        }
        if ($errors !== []) {
            $this->app->session()->flash('page_errors', $errors);
            $this->app->session()->flash('page_old', $values + ['is_published' => $request->input('is_published')]);
            return $this->back($this->app->adminPath('pages/' . $id . '?lang=' . $lang));
        }
        $published = $request->input('is_published') === '1';
        $oldSlug = $content->savePageTranslation($id, $lang, $values, $published);
        $content->savePage($id, [
            'is_enabled' => $request->input('is_enabled') === '1',
            'in_nav' => $request->input('in_nav') === '1',
            'nav_order' => max(0, min(999, (int) $request->input('nav_order'))),
            'in_sitemap' => $request->input('in_sitemap') === '1',
            'hero_media_id' => $this->mediaId($request->input('hero'), 'image'),
        ]);
        if ($oldSlug !== null && $oldSlug !== '') {
            $parent = $this->parentSlug($page, $lang);
            $this->addRedirect($lang, $parent . $oldSlug, $parent . $values['slug']);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'page', 'id' => $id, 'lang' => $lang, 'published' => $published]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('pages/' . $id . '?lang=' . $lang));
    }

    /** Order and visibility of the home page sections (one Save button, never auto-save). */
    public function saveSections(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        if ($content->page($id) === null) {
            return $this->app->errorResponse(404);
        }
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $enabled = array_values(array_filter(array_map('intval', $request->inputList('enabled'))));
        if ($order !== []) {
            $content->reorderSections($id, $order, $enabled);
            $this->app->audit()->record(AuditLog::CONTENT_REORDERED, $this->app->auth()->user()['id'] ?? null, ['type' => 'sections', 'page' => $id, 'order' => $order]);
            $this->flashToast('admin.content.order_saved');
        }
        return $this->back($this->app->adminPath('pages/' . $id));
    }

    public function editSection(Request $request): Response
    {
        $content = $this->content();
        $section = $content->section((int) $request->param('section'));
        $page = $content->page((int) $request->param('id'));
        if ($section === null || $page === null || (int) $section['page_id'] !== (int) $page['id']) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->query('lang'));
        $translations = $content->sectionTranslations((int) $section['id']);
        $translation = $translations[$lang] ?? [];
        $extra = is_string($translation['extra'] ?? null) ? json_decode($translation['extra'], true) : null;
        $settings = is_string($section['settings'] ?? null) ? json_decode($section['settings'], true) : null;
        $regions = [['value' => '', 'label' => $this->t('admin.entries.none')]];
        foreach (EntryTypes::REGIONS as $region) {
            $regions[] = ['value' => $region, 'label' => $this->t('site.regions.' . $region)];
        }
        return $this->adminView('admin/section-edit', 'pages', $this->t('admin.sections.' . $section['type']), $this->t('admin.pages.sections_subtitle'), [
            'section' => [
                'id' => (int) $section['id'], 'type' => (string) $section['type'], 'page_id' => (int) $page['id'],
                'locked' => (int) $section['is_locked'] === 1, 'enabled' => (int) $section['is_enabled'] === 1,
                'media' => $section['media_id'] !== null ? (string) $section['media_id'] : '',
                'media2' => is_array($settings) && is_int($settings['media2'] ?? null) ? (string) $settings['media2'] : '',
            ],
            'extra' => is_array($extra) ? $extra : [],
            'images' => $this->mediaOptions('image', $this->t('admin.media.none')),
            'regions' => $regions,
            'icons' => ExpertiseController::icons(),
            'lang' => $lang,
            'values' => [
                'label' => (string) ($translation['label'] ?? ''),
                'title' => (string) ($translation['title'] ?? ''),
                'highlight' => (string) ($translation['highlight'] ?? ''),
                'intro' => (string) ($translation['intro'] ?? ''),
            ],
            'tabs' => $this->languageTabs($this->app->adminPath('pages/' . $page['id'] . '/sections/' . $section['id']), $lang, $translations, false),
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function saveSection(Request $request): Response
    {
        $content = $this->content();
        $section = $content->section((int) $request->param('section'));
        $pageId = (int) $request->param('id');
        if ($section === null || (int) $section['page_id'] !== $pageId) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->input('lang'));
        $type = (string) $section['type'];
        $content->saveSectionTranslation((int) $section['id'], $lang, [
            'label' => self::line($request->input('label'), 120),
            'title' => self::line($request->input('title')),
            'highlight' => self::line($request->input('highlight')),
            'intro' => self::text($request->input('intro')),
        ], $this->sectionExtra($type, $request));
        if (in_array($type, ['hero', 'about'], true)) {
            $media2 = $type === 'about' ? $this->mediaId($request->input('media2'), 'image') : null;
            $content->saveSectionMedia((int) $section['id'], $this->mediaId($request->input('media'), 'image'), $media2 !== null ? ['media2' => $media2] : []);
        }
        $content->touchPage($pageId);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'section', 'id' => (int) $section['id'], 'lang' => $lang]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('pages/' . $pageId . '/sections/' . $section['id'] . '?lang=' . $lang));
    }

    /**
     * The structured texts of a section from the form: the About points and badge, the map's main region and notes.
     *
     * @return array<string, mixed>|null null when the section has none (its stored extra stays as it is)
     */
    private function sectionExtra(string $type, Request $request): ?array
    {
        if ($type === 'about') {
            $points = [];
            for ($i = 0; $i < 3; $i++) {
                $title = self::line($request->input('point_title_' . $i), 80);
                if ($title === '') {
                    continue;
                }
                $icon = $request->input('point_icon_' . $i);
                $points[] = [
                    'icon' => in_array($icon, ExpertiseController::icons(), true) || in_array($icon, ['target', 'users', 'check'], true) ? $icon : 'check',
                    'title' => $title,
                    'text' => self::line($request->input('point_text_' . $i), 200),
                ];
            }
            return ['badge_value' => self::line($request->input('badge_value'), 20), 'badge_label' => self::line($request->input('badge_label'), 60), 'points' => $points];
        }
        if ($type === 'map') {
            $notes = [];
            foreach (EntryTypes::REGIONS as $region) {
                $note = self::line($request->input('note_' . $region), 80);
                if ($note !== '') {
                    $notes[$region] = $note;
                }
            }
            $main = $request->input('main');
            return ['main' => in_array($main, EntryTypes::REGIONS, true) ? $main : '', 'notes' => $notes];
        }
        return null;
    }

    /**
     * "about/" for a child of About (in this language), '' for a top-level page.
     *
     * @param array<string, mixed> $page
     */
    private function parentSlug(array $page, string $lang): string
    {
        if ($page['parent_id'] === null) {
            return '';
        }
        $translations = $this->content()->pageTranslations((int) $page['parent_id']);
        $slug = $translations[$lang]['slug'] ?? $translations[$this->app->languages()->defaultCode()]['slug'] ?? '';
        return is_string($slug) && $slug !== '' ? $slug . '/' : '';
    }

    /**
     * A top-level page may not take a slug the site uses for its own addresses.
     *
     * @param array<string, mixed> $page
     */
    private function reservedSlug(array $page, string $slug): bool
    {
        return $page['parent_id'] === null && in_array($slug, ['consent', 'newsletter'], true);
    }

    /**
     * @param array<string, mixed> $translation
     * @return array<string, string>
     */
    private function translationValues(array $translation): array
    {
        $values = [];
        foreach (ContentAdminRepository::PAGE_FIELDS as $field) {
            $values[$field] = is_scalar($translation[$field] ?? null) ? (string) $translation[$field] : '';
        }
        return $values;
    }

    private function pagePath(string $lang, string $slug): string
    {
        return '/' . $lang . '/' . ($slug === '' ? '' : $slug);
    }

    private function addRedirect(string $lang, string $oldSlug, string $newSlug): void
    {
        $redirects = new RedirectRepository($this->app->db(), $this->app->clock);
        $redirects->add($this->pagePath($lang, $oldSlug), $this->pagePath($lang, $newSlug), 301);
    }
}
