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
 * Services: the list with order and visibility, and the detail screen with the icon (from the picker, which only
 * offers icons that are in the built icon subset), the menu options and the texts per language.
 *
 * A new service starts disabled and unpublished, so nothing half-finished ever appears on the website.
 */
final class ServiceController extends ContentController
{
    public function index(Request $request): Response
    {
        $content = $this->content();
        $languages = $this->app->languages()->enabledCodes();
        $default = $this->app->languages()->defaultCode();
        $rows = [];
        foreach ($content->services() as $service) {
            $translations = $content->serviceTranslations((int) $service['id']);
            $states = [];
            foreach ($languages as $lang) {
                $row = $translations[$lang] ?? null;
                $states[$lang] = $row === null ? 'missing' : ((int) ($row['is_published'] ?? 1) === 1 ? 'published' : 'draft');
            }
            $rows[] = [
                'id' => (int) $service['id'],
                'key' => (string) $service['key'],
                'icon' => (string) $service['icon'],
                'title' => (string) ($translations[$default]['title'] ?? $service['key']),
                'enabled' => (int) $service['is_enabled'] === 1,
                'in_menu' => (int) $service['show_in_menu'] === 1,
                'states' => $states,
            ];
        }
        return $this->adminView('admin/services', 'services', $this->t('admin.services.title'), $this->t('admin.services.subtitle'), [
            'services' => $rows,
            'languages' => $languages,
            'canEdit' => $this->can('content.edit'),
            'errors' => $this->pullArray('service_errors'),
        ]);
    }

    public function create(Request $request): Response
    {
        $content = $this->content();
        $key = self::slug($request->input('key'));
        if ($key === '' || mb_strlen($key) > 60) {
            $this->app->session()->flash('service_errors', ['key' => $this->t('validation.required')]);
            return $this->back($this->app->adminPath('services'));
        }
        if ($content->serviceKeyTaken($key)) {
            $this->app->session()->flash('service_errors', ['key' => $this->t('admin.services.key_taken')]);
            return $this->back($this->app->adminPath('services'));
        }
        $id = $content->createService($key, self::icons()[0]);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'service.created', 'id' => $id, 'key' => $key]);
        $this->flashToast('admin.services.created');
        return $this->back($this->app->adminPath('services/' . $id));
    }

    public function edit(Request $request): Response
    {
        $content = $this->content();
        $service = $content->service((int) $request->param('id'));
        if ($service === null) {
            return $this->app->errorResponse(404);
        }
        $id = (int) $service['id'];
        $lang = $this->editLang($request->query('lang'));
        $translations = $content->serviceTranslations($id);
        $translation = $translations[$lang] ?? [];
        $old = $this->pullArray('service_old');
        $values = [];
        foreach (ContentAdminRepository::SERVICE_FIELDS as $field) {
            $values[$field] = is_scalar($old[$field] ?? null) ? (string) $old[$field] : (is_scalar($translation[$field] ?? null) ? (string) $translation[$field] : '');
        }
        return $this->adminView('admin/service-edit', 'services', $this->t('admin.services.edit_title', ['title' => (string) ($translation['title'] ?? $service['key'])]), $this->t('admin.services.subtitle'), [
            'service' => [
                'id' => $id,
                'key' => (string) $service['key'],
                'icon' => (string) $service['icon'],
                'is_enabled' => (int) $service['is_enabled'] === 1,
                'show_in_menu' => (int) $service['show_in_menu'] === 1,
                'show_on_home' => (int) $service['show_on_home'] === 1,
                'sort_order' => (int) $service['sort_order'],
            ],
            'lang' => $lang,
            'values' => $values,
            'published' => $old !== [] ? ($old['is_published'] ?? '1') === '1' : (int) ($translation['is_published'] ?? 1) === 1,
            'tabs' => $this->languageTabs($this->app->adminPath('services/' . $id), $lang, $translations),
            'icons' => self::icons(),
            'errors' => $this->pullArray('service_errors'),
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function save(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        $service = $content->service($id);
        if ($service === null) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->input('lang'));
        $values = [
            'slug' => self::slug($request->input('slug')),
            'title' => self::line($request->input('title'), 160),
            'menu_title' => self::line($request->input('menu_title'), 120),
            'menu_sub' => self::line($request->input('menu_sub'), 160),
            'short_title' => self::line($request->input('short_title'), 120),
            'summary' => self::text($request->input('summary')),
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
        } elseif ($content->slugTaken('service_translations', $lang, $values['slug'], $id, 'service_id')) {
            $errors['slug'] = $this->t('admin.pages.slug_taken');
        }
        $icon = $request->input('icon');
        if (!in_array($icon, self::icons(), true)) {
            $errors['icon'] = $this->t('validation.choice');
        }
        if ($errors !== []) {
            $this->app->session()->flash('service_errors', $errors);
            $this->app->session()->flash('service_old', $values + ['is_published' => $request->input('is_published')]);
            return $this->back($this->app->adminPath('services/' . $id . '?lang=' . $lang));
        }
        $published = $request->input('is_published') === '1';
        $oldSlug = $content->saveServiceTranslation($id, $lang, $values, $published);
        $content->saveService($id, [
            'icon' => $icon,
            'is_enabled' => $request->input('is_enabled') === '1',
            'show_in_menu' => $request->input('show_in_menu') === '1',
            'show_on_home' => $request->input('show_on_home') === '1',
            'sort_order' => max(0, min(999, (int) $request->input('sort_order'))),
        ]);
        if ($oldSlug !== null && $oldSlug !== '') {
            $this->addRedirect($lang, $oldSlug, $values['slug']);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'service', 'id' => $id, 'lang' => $lang, 'published' => $published]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('services/' . $id . '?lang=' . $lang));
    }

    public function reorder(Request $request): Response
    {
        $content = $this->content();
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $enabled = array_values(array_filter(array_map('intval', $request->inputList('enabled'))));
        $sort = 0;
        foreach ($order as $id) {
            $service = $content->service($id);
            if ($service === null) {
                continue;
            }
            $sort += 10;
            $content->saveService($id, [
                'icon' => (string) $service['icon'],
                'is_enabled' => in_array($id, $enabled, true),
                'show_in_menu' => (int) $service['show_in_menu'] === 1,
                'show_on_home' => (int) $service['show_on_home'] === 1,
                'sort_order' => $sort,
            ]);
        }
        $this->app->audit()->record(AuditLog::CONTENT_REORDERED, $this->app->auth()->user()['id'] ?? null, ['type' => 'services', 'order' => $order]);
        $this->flashToast('admin.content.order_saved');
        return $this->back($this->app->adminPath('services'));
    }

    public function delete(Request $request): Response
    {
        $content = $this->content();
        $id = (int) $request->param('id');
        $service = $content->service($id);
        if ($service === null) {
            return $this->app->errorResponse(404);
        }
        // The service disappears from the website, so its old addresses get a redirect to the services page.
        foreach ($content->serviceTranslations($id) as $lang => $translation) {
            $slug = is_string($translation['slug'] ?? null) ? $translation['slug'] : '';
            if ($slug !== '') {
                $this->addRedirect((string) $lang, $slug, null);
            }
        }
        $content->deleteService($id);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'service.deleted', 'id' => $id, 'key' => $service['key']]);
        $this->flashToast('admin.services.deleted');
        return $this->back($this->app->adminPath('services'));
    }

    /** @return list<string> the icons the picker offers (config/service-icons.php, all in the built subset) */
    public static function icons(): array
    {
        $icons = require Paths::config('service-icons.php');
        return is_array($icons) ? array_values(array_filter($icons, 'is_string')) : ['fa-solid fa-gear'];
    }

    private function addRedirect(string $lang, string $oldSlug, ?string $newSlug): void
    {
        $content = $this->content();
        $servicesPage = $content->pageByKey('services');
        $servicesSlug = '';
        if ($servicesPage !== null) {
            $translations = $content->pageTranslations((int) $servicesPage['id']);
            $servicesSlug = is_string($translations[$lang]['slug'] ?? null) ? $translations[$lang]['slug'] : '';
        }
        $base = '/' . $lang . ($servicesSlug === '' ? '' : '/' . $servicesSlug);
        (new RedirectRepository($this->app->db(), $this->app->clock))->add(
            $base . '/' . $oldSlug,
            $newSlug === null ? $base : $base . '/' . $newSlug,
            301
        );
    }
}
