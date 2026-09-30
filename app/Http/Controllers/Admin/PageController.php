<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\ContentAdminRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Services\AuditLog;
use Gate\Services\SectionOrder;

/**
 * Pages: the nine system pages with their texts, SEO fields and slug per language, a publish state per language and
 * — on the home page — the order and visibility of its sections.
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
        foreach ($content->pages() as $page) {
            $translations = $content->pageTranslations((int) $page['id']);
            $states = [];
            foreach ($languages as $lang) {
                $row = $translations[$lang] ?? null;
                $states[$lang] = $row === null ? 'missing' : ((int) ($row['is_published'] ?? 1) === 1 ? 'published' : 'draft');
            }
            $rows[] = [
                'id' => (int) $page['id'],
                'key' => (string) $page['key'],
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
            ],
            'lang' => $lang,
            'values' => $old !== [] ? $old : $this->translationValues($translation),
            'published' => $old !== [] ? ($old['is_published'] ?? '1') === '1' : (int) ($translation['is_published'] ?? 1) === 1,
            'tabs' => $this->languageTabs($this->app->adminPath('pages/' . $id), $lang, $translations),
            'errors' => $this->pullArray('page_errors'),
            'sections' => $sections,
            'canEdit' => $this->can('content.edit'),
            'previewUrl' => $this->pagePath($lang, (string) ($translation['slug'] ?? '')),
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
        if (!$isHome && $values['slug'] === '') {
            $errors['slug'] = $this->t('validation.required');
        } elseif (!$isHome && $content->slugTaken('page_translations', $lang, $values['slug'], $id, 'page_id')) {
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
        ]);
        if ($oldSlug !== null && $oldSlug !== '') {
            $this->addRedirect($lang, $oldSlug, $values['slug']);
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
        return $this->adminView('admin/section-edit', 'pages', $this->t('admin.sections.' . $section['type']), $this->t('admin.pages.sections_subtitle'), [
            'section' => ['id' => (int) $section['id'], 'type' => (string) $section['type'], 'page_id' => (int) $page['id'], 'locked' => (int) $section['is_locked'] === 1, 'enabled' => (int) $section['is_enabled'] === 1],
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
        $content->saveSectionTranslation((int) $section['id'], $lang, [
            'label' => self::line($request->input('label'), 120),
            'title' => self::line($request->input('title')),
            'highlight' => self::line($request->input('highlight')),
            'intro' => self::text($request->input('intro')),
        ]);
        $content->touchPage($pageId);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'section', 'id' => (int) $section['id'], 'lang' => $lang]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('pages/' . $pageId . '/sections/' . $section['id'] . '?lang=' . $lang));
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
