<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\ContentAdminRepository;
use Gate\Services\AuditLog;

/**
 * The short content lists: the impact counters and the partners & donors. They share one screen: the rows of the
 * chosen language, with order, visibility and a Save button. Partners have a kind (donor or partner), a link and a
 * logo from the media library.
 */
final class ContentListController extends ContentController
{
    /** URL segment => table (partners are handled separately: they have a logo and a link). */
    private const TYPES = [
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
        return $this->adminView('admin/content-list', $type, $this->t('admin.lists.' . $type . '_title'), $this->t('admin.lists.' . $type . '_subtitle'), [
            'type' => $type,
            'fields' => $fields,
            'options' => [],
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
                $values[$field] = self::line($request->input($field . '_' . $id), 160);
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
            $id = $content->createPartner($name, $request->input('kind'));
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
                    'kind' => (string) $partner['kind'],
                    'url' => (string) $partner['url'],
                    'logo' => $partner['logo_media_id'] !== null ? (string) $partner['logo_media_id'] : '',
                    'description' => (string) ($translations[$id][$lang]['description'] ?? ''),
                ],
            ];
        }
        return $this->adminView('admin/content-list', 'partners', $this->t('admin.lists.partners_title'), $this->t('admin.lists.partners_subtitle'), [
            'type' => 'partners',
            'fields' => ['name', 'kind', 'url', 'logo', 'description'],
            'options' => [
                'kind' => array_map(fn (string $k): array => ['value' => $k, 'label' => $this->t('admin.lists.kind_' . $k)], ContentAdminRepository::PARTNER_KINDS),
                'logo' => $this->mediaOptions('image', $this->t('admin.media.none')),
            ],
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
                'kind' => $request->input('kind_' . $id),
                'url' => preg_match('#^https?://[^\s]{4,300}$#', $url) === 1 ? $url : '',
                'logo_media_id' => $this->mediaId($request->input('logo_' . $id), 'image'),
                'is_enabled' => in_array($id, $enabled, true),
            ]);
            $content->savePartnerTranslation($id, $lang, self::line($request->input('description_' . $id), 400));
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'partners', 'lang' => $lang]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('content/partners?lang=' . $lang));
    }

}
