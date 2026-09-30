<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Repositories\ContentAdminRepository;
use BMMatic\Services\AuditLog;

/**
 * The short content lists that the home and content pages use: transmission types, process steps, key figures and
 * partners. They share one screen: the rows of the chosen language, with order, visibility and a Save button.
 */
final class ContentListController extends ContentController
{
    /** URL segment => table (partners are handled separately: they have a logo and a link). */
    private const TYPES = [
        'transmissions' => 'transmission_types',
        'steps' => 'process_steps',
        'stats' => 'stats',
    ];

    public function index(Request $request): Response
    {
        $type = (string) $request->param('type');
        if ($type === 'partners') {
            return $this->partners($request);
        }
        $table = self::TYPES[$type] ?? null;
        if ($table === null) {
            return $this->app->errorResponse(404);
        }
        $content = $this->content();
        $lang = $this->editLang($request->query('lang'));
        [, $fields] = ContentAdminRepository::LISTS[$table];
        $translations = $content->listTranslations($table);
        $rows = [];
        foreach ($content->listItems($table) as $item) {
            $id = (int) $item['id'];
            $values = [];
            foreach ($fields as $field) {
                $values[$field] = (string) ($translations[$id][$lang][$field] ?? '');
            }
            $rows[] = ['id' => $id, 'enabled' => (int) $item['is_enabled'] === 1, 'values' => $values];
        }
        return $this->adminView('admin/content-list', 'pages', $this->t('admin.lists.' . $type . '_title'), $this->t('admin.lists.subtitle'), [
            'type' => $type,
            'fields' => $fields,
            'rows' => $rows,
            'lang' => $lang,
            'tabs' => $this->languageTabs($this->app->adminPath('content/' . $type), $lang, [], false),
            'canEdit' => $this->can('content.edit'),
            'partners' => null,
        ]);
    }

    public function save(Request $request): Response
    {
        $type = (string) $request->param('type');
        $table = self::TYPES[$type] ?? null;
        if ($table === null) {
            return $this->app->errorResponse(404);
        }
        $content = $this->content();
        $lang = $this->editLang($request->input('lang'));
        [, $fields] = ContentAdminRepository::LISTS[$table];
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $enabled = array_values(array_filter(array_map('intval', $request->inputList('enabled'))));
        foreach ($order as $id) {
            $values = [];
            foreach ($fields as $field) {
                $values[$field] = self::line($request->input($field . '_' . $id), $field === 'description' || $field === 'text' ? 400 : 160);
            }
            $content->saveListTranslation($table, $id, $lang, $values);
        }
        $content->reorderList($table, $order, $enabled);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $table, 'lang' => $lang, 'order' => $order]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('content/' . $type . '?lang=' . $lang));
    }

    public function add(Request $request): Response
    {
        $type = (string) $request->param('type');
        $content = $this->content();
        if ($type === 'partners') {
            $name = self::line($request->input('name'), 160);
            if ($name === '') {
                return $this->back($this->app->adminPath('content/partners'));
            }
            $id = $content->createPartner($name);
        } else {
            $table = self::TYPES[$type] ?? null;
            if ($table === null) {
                return $this->app->errorResponse(404);
            }
            $id = $content->createListItem($table);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $type . '.created', 'id' => $id]);
        return $this->back($this->app->adminPath('content/' . $type));
    }

    public function delete(Request $request): Response
    {
        $type = (string) $request->param('type');
        $id = (int) $request->param('id');
        $content = $this->content();
        if ($type === 'partners') {
            $content->deletePartner($id);
        } else {
            $table = self::TYPES[$type] ?? null;
            if ($table === null) {
                return $this->app->errorResponse(404);
            }
            $content->deleteListItem($table, $id);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $type . '.deleted', 'id' => $id]);
        $this->flashToast('admin.content.deleted');
        return $this->back($this->app->adminPath('content/' . $type));
    }

    // -------------------------------------------------------------------------------------------- partners

    private function partners(Request $request): Response
    {
        $content = $this->content();
        $lang = $this->editLang($request->query('lang'));
        $translations = $content->partnerTranslations();
        $rows = [];
        foreach ($content->partners() as $partner) {
            $id = (int) $partner['id'];
            $rows[] = [
                'id' => $id,
                'enabled' => (int) $partner['is_enabled'] === 1,
                'values' => [
                    'name' => (string) $partner['name'],
                    'url' => (string) $partner['url'],
                    'logo' => (string) $partner['logo'],
                    'description' => (string) ($translations[$id][$lang]['description'] ?? ''),
                ],
            ];
        }
        return $this->adminView('admin/content-list', 'pages', $this->t('admin.lists.partners_title'), $this->t('admin.lists.subtitle'), [
            'type' => 'partners',
            'fields' => ['name', 'url', 'logo', 'description'],
            'rows' => $rows,
            'lang' => $lang,
            'tabs' => $this->languageTabs($this->app->adminPath('content/partners'), $lang, [], false),
            'canEdit' => $this->can('content.edit'),
            'partners' => true,
        ]);
    }

    public function savePartners(Request $request): Response
    {
        $content = $this->content();
        $lang = $this->editLang($request->input('lang'));
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $enabled = array_values(array_filter(array_map('intval', $request->inputList('enabled'))));
        $sort = 0;
        foreach ($order as $id) {
            $url = trim($request->input('url_' . $id));
            $sort += 10;
            $content->savePartner($id, [
                'sort_order' => $sort,
                'name' => self::line($request->input('name_' . $id), 160),
                'url' => preg_match('#^https?://[^\s]{4,300}$#', $url) === 1 ? $url : '',
                'logo' => self::line($request->input('logo_' . $id), 300),
                'is_enabled' => in_array($id, $enabled, true),
            ]);
            $content->savePartnerTranslation($id, $lang, self::line($request->input('description_' . $id), 400));
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'partners', 'lang' => $lang]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('content/partners?lang=' . $lang));
    }

}
