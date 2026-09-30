<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Core\Paths;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\ContentAdminRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Services\AuditLog;

/**
 * Areas of expertise: the list with order and visibility, and the detail screen with the icon (from the picker: the
 * symbols of the site's SVG sprite listed in config/expertise-icons.php), the cover image, "show on the home page"
 * and the texts per language. Projects, news and publications are classified by these areas.
 *
 * A new area starts disabled, so nothing half-finished ever appears on the website.
 */
final class ExpertiseController extends ContentController
{
    public function index(Request $request): Response
    {
        $content = $this->content();
        $languages = $this->app->languages()->enabledCodes();
        $default = $this->app->languages()->defaultCode();
        $rows = [];
        foreach ($content->expertiseList() as $area) {
            $translations = $content->expertiseTranslations((int) $area['id']);
            $states = [];
            foreach ($languages as $lang) {
                $row = $translations[$lang] ?? null;
                $states[$lang] = $row === null ? 'missing' : ((int) ($row['is_published'] ?? 1) === 1 ? 'published' : 'draft');
            }
            $rows[] = [
                'id' => (int) $area['id'],
                'key' => (string) $area['key'],
                'icon' => (string) $area['icon'],
                'title' => (string) ($translations[$default]['title'] ?? $area['key']),
                'enabled' => (int) $area['is_enabled'] === 1,
                'states' => $states,
            ];
        }
        return $this->adminView('admin/expertise', 'expertise', $this->t('admin.expertise.title'), $this->t('admin.expertise.subtitle'), [
            'areas' => $rows,
            'languages' => $languages,
            'canEdit' => $this->can('content.edit'),
            'errors' => $this->pullArray('expertise_errors'),
        ]);
    }

    public function create(Request $request): Response
    {
        $content = $this->content();
        $key = self::slug($request->input('key'));
        if ($key === '' || mb_strlen($key) > 60) {
            $this->app->session()->flash('expertise_errors', ['key' => $this->t('validation.required')]);
            return $this->back($this->app->adminPath('expertise'));
        }
        if ($content->expertiseKeyTaken($key)) {
            $this->app->session()->flash('expertise_errors', ['key' => $this->t('admin.expertise.key_taken')]);
            return $this->back($this->app->adminPath('expertise'));
        }
        $id = $content->createExpertise($key, self::icons()[0]);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'expertise.created', 'id' => $id, 'key' => $key]);
        $this->flashToast('admin.expertise.created');
        return $this->back($this->app->adminPath('expertise/' . $id));
    }

    public function edit(Request $request): Response
    {
        $content = $this->content();
        $area = $content->expertise((int) $request->param('id'));
        if ($area === null) {
            return $this->app->errorResponse(404);
        }
        $id = (int) $area['id'];
        $lang = $this->editLang($request->query('lang'));
        $translations = $content->expertiseTranslations($id);
        $translation = $translations[$lang] ?? [];
        $old = $this->pullArray('expertise_old');
        $values = [];
        foreach (ContentAdminRepository::EXPERTISE_FIELDS as $field) {
            $values[$field] = is_scalar($old[$field] ?? null) ? (string) $old[$field] : (is_scalar($translation[$field] ?? null) ? (string) $translation[$field] : '');
        }
        return $this->adminView('admin/expertise-edit', 'expertise', $this->t('admin.expertise.edit_title', ['title' => (string) ($translation['title'] ?? $area['key'])]), $this->t('admin.expertise.subtitle'), [
            'area' => [
                'id' => $id,
                'key' => (string) $area['key'],
                'icon' => (string) $area['icon'],
                'is_enabled' => (int) $area['is_enabled'] === 1,
                'show_on_home' => (int) $area['show_on_home'] === 1,
                'sort_order' => (int) $area['sort_order'],
                'cover' => $area['cover_media_id'] !== null ? (string) $area['cover_media_id'] : '',
            ],
            'images' => $this->mediaOptions('image', $this->t('admin.media.none')),
            'lang' => $lang,
            'values' => $values,
            'published' => $old !== [] ? ($old['is_published'] ?? '1') === '1' : (int) ($translation['is_published'] ?? 1) === 1,
            'tabs' => $this->languageTabs($this->app->adminPath('expertise/' . $id), $lang, $translations),
            'icons' => self::icons(),
            'errors' => $this->pullArray('expertise_errors'),
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function save(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        $area = $content->expertise($id);
        if ($area === null) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->input('lang'));
        $values = [
            'slug' => self::slug($request->input('slug')),
            'title' => self::line($request->input('title'), 160),
            'summary' => self::text($request->input('summary'), 400),
            'body' => self::richText($request->input('body')),
            'meta_title' => self::line($request->input('meta_title')),
            'meta_description' => self::line($request->input('meta_description'), 320),
        ];
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = $this->t('validation.required');
        }
        if ($values['slug'] === '') {
            $errors['slug'] = $this->t('validation.required');
        } elseif ($content->slugTaken('expertise_translations', $lang, $values['slug'], $id, 'expertise_id')) {
            $errors['slug'] = $this->t('admin.pages.slug_taken');
        }
        $icon = $request->input('icon');
        if (!in_array($icon, self::icons(), true)) {
            $errors['icon'] = $this->t('validation.choice');
        }
        if ($errors !== []) {
            $this->app->session()->flash('expertise_errors', $errors);
            $this->app->session()->flash('expertise_old', $values + ['is_published' => $request->input('is_published')]);
            return $this->back($this->app->adminPath('expertise/' . $id . '?lang=' . $lang));
        }
        $published = $request->input('is_published') === '1';
        $oldSlug = $content->saveExpertiseTranslation($id, $lang, $values, $published);
        $content->saveExpertise($id, [
            'icon' => $icon,
            'is_enabled' => $request->input('is_enabled') === '1',
            'show_on_home' => $request->input('show_on_home') === '1',
            'sort_order' => max(0, min(999, (int) $request->input('sort_order'))),
            'cover_media_id' => $this->mediaId($request->input('cover'), 'image'),
        ]);
        if ($oldSlug !== null && $oldSlug !== '') {
            $this->addRedirect($lang, $oldSlug, $values['slug']);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'expertise', 'id' => $id, 'lang' => $lang, 'published' => $published]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('expertise/' . $id . '?lang=' . $lang));
    }

    public function reorder(Request $request): Response
    {
        $content = $this->content();
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $enabled = array_values(array_filter(array_map('intval', $request->inputList('enabled'))));
        $sort = 0;
        foreach ($order as $id) {
            $area = $content->expertise($id);
            if ($area === null) {
                continue;
            }
            $sort += 10;
            $content->saveExpertise($id, [
                'icon' => (string) $area['icon'],
                'is_enabled' => in_array($id, $enabled, true),
                'show_on_home' => (int) $area['show_on_home'] === 1,
                'sort_order' => $sort,
                'cover_media_id' => $area['cover_media_id'] !== null ? (int) $area['cover_media_id'] : null,
            ]);
        }
        $this->app->audit()->record(AuditLog::CONTENT_REORDERED, $this->app->auth()->user()['id'] ?? null, ['type' => 'expertise', 'order' => $order]);
        $this->flashToast('admin.content.order_saved');
        return $this->back($this->app->adminPath('expertise'));
    }

    public function delete(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        $area = $content->expertise($id);
        if ($area === null) {
            return $this->app->errorResponse(404);
        }
        // The area disappears from the website, so its old addresses get a redirect to the expertise page.
        foreach ($content->expertiseTranslations($id) as $lang => $translation) {
            $slug = is_string($translation['slug'] ?? null) ? $translation['slug'] : '';
            if ($slug !== '') {
                $this->addRedirect((string) $lang, $slug, null);
            }
        }
        $content->deleteExpertise($id);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'expertise.deleted', 'id' => $id, 'key' => $area['key']]);
        $this->flashToast('admin.expertise.deleted');
        return $this->back($this->app->adminPath('expertise'));
    }

    /** @return list<string> the icons the picker offers (config/expertise-icons.php, symbols of the site sprite) */
    public static function icons(): array
    {
        $icons = require Paths::config('expertise-icons.php');
        return is_array($icons) ? array_values(array_filter($icons, 'is_string')) : ['cedar'];
    }

    private function addRedirect(string $lang, string $oldSlug, ?string $newSlug): void
    {
        $content = $this->content();
        $listPage = $content->pageByKey('expertise');
        $listSlug = '';
        if ($listPage !== null) {
            $translations = $content->pageTranslations((int) $listPage['id']);
            $listSlug = is_string($translations[$lang]['slug'] ?? null) ? $translations[$lang]['slug'] : '';
        }
        $base = '/' . $lang . ($listSlug === '' ? '' : '/' . $listSlug);
        (new RedirectRepository($this->app->db(), $this->app->clock))->add(
            $base . '/' . $oldSlug,
            $newSlug === null ? $base : $base . '/' . $newSlug,
            301
        );
    }
}
