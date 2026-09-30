<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Services\AuditLog;
use Gate\Services\MediaLibrary;

/**
 * The media library: a grid of uploaded images and PDF documents with search and a kind filter, alt text (images) or
 * a title (documents) per language, replace-file and delete with a usage check ("used on 3 pages").
 */
final class MediaController extends ContentController
{
    public function index(Request $request): Response
    {
        $library = $this->library();
        $langs = $this->app->languages()->enabledCodes();
        $search = mb_substr(trim($request->query('q')), 0, 80);
        $kind = $request->query('kind');
        $kind = in_array($kind, MediaLibrary::KINDS, true) ? $kind : null;
        $settings = $this->app->settings();
        $items = [];
        foreach ($library->all($langs, $search, $kind) as $item) {
            $usage = $library->usage($item['id'], $item['url'], $settings);
            $items[] = $item + [
                'usage' => $this->usageLabels($usage),
                'used' => $usage !== [],
                'uploaded' => $this->app->formatDate($item['created_at'], false),
                'size_kb' => $item['size'] >= 1024 * 1024 ? number_format($item['size'] / 1024 / 1024, 1, '.', ' ') . ' MB' : number_format($item['size'] / 1024, 0, '.', ' ') . ' KB',
                'alt_missing' => array_values(array_filter($langs, static fn (string $l): bool => trim($item['alt'][$l] ?? '') === '')),
            ];
        }
        return $this->adminView('admin/media', 'media', $this->t('admin.media.title'), $this->t('admin.media.subtitle'), [
            'items' => $items,
            'languages' => $langs,
            'search' => $search,
            'kind' => $kind ?? '',
            'kinds' => [
                ['label' => $this->t('admin.media.kind_all'), 'href' => $this->app->adminPath('media') . ($search !== '' ? '?q=' . rawurlencode($search) : ''), 'active' => $kind === null],
                ['label' => $this->t('admin.media.kind_image'), 'href' => $this->app->adminPath('media?kind=image') . ($search !== '' ? '&q=' . rawurlencode($search) : ''), 'active' => $kind === 'image'],
                ['label' => $this->t('admin.media.kind_document'), 'href' => $this->app->adminPath('media?kind=document') . ($search !== '' ? '&q=' . rawurlencode($search) : ''), 'active' => $kind === 'document'],
            ],
            'canManage' => $this->can('media.manage'),
            'maxMb' => (int) round(MediaLibrary::MAX_BYTES / 1024 / 1024),
            'maxPdfMb' => (int) round(MediaLibrary::MAX_DOCUMENT_BYTES / 1024 / 1024),
            'error' => $this->app->session()->pull('media_error'),
        ]);
    }

    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        $library = $this->library();
        $result = $library->store($file, $this->app->auth()->user()['id'] ?? null);
        if (isset($result['error'])) {
            $this->app->session()->flash('media_error', $this->t('admin.media.error_' . $result['error']));
            $this->app->audit()->record(AuditLog::MEDIA_REJECTED, $this->app->auth()->user()['id'] ?? null, ['reason' => $result['error'], 'name' => mb_substr(is_string($file['name'] ?? null) ? $file['name'] : '', 0, 120)]);
            return $this->back($this->app->adminPath('media'));
        }
        $this->app->audit()->record(AuditLog::MEDIA_UPLOADED, $this->app->auth()->user()['id'] ?? null, ['id' => $result['id']]);
        $this->flashToast('admin.media.uploaded');
        return $this->back($this->app->adminPath('media'));
    }

    public function replace(Request $request): Response
    {
        $id = (int) $request->param('id');
        $library = $this->library();
        if ($library->find($id) === null) {
            return $this->app->errorResponse(404);
        }
        $result = $library->store($request->file('file'), $this->app->auth()->user()['id'] ?? null, $id);
        if (isset($result['error'])) {
            $this->app->session()->flash('media_error', $this->t('admin.media.error_' . $result['error']));
            return $this->back($this->app->adminPath('media'));
        }
        $this->app->audit()->record(AuditLog::MEDIA_REPLACED, $this->app->auth()->user()['id'] ?? null, ['id' => $id]);
        $this->flashToast('admin.media.replaced');
        return $this->back($this->app->adminPath('media'));
    }

    public function saveAlt(Request $request): Response
    {
        $id = (int) $request->param('id');
        $library = $this->library();
        if ($library->find($id) === null) {
            return $this->app->errorResponse(404);
        }
        $alt = [];
        foreach ($this->app->languages()->enabledCodes() as $lang) {
            $alt[$lang] = $request->input('alt_' . $lang);
        }
        $library->saveAlt($id, $alt);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => 'media.alt', 'id' => $id]);
        $this->flashToast('admin.content.saved');
        return $this->back($this->app->adminPath('media'));
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $library = $this->library();
        $item = $library->find($id);
        if ($item === null) {
            return $this->app->errorResponse(404);
        }
        $library->delete($id);
        $this->app->audit()->record(AuditLog::MEDIA_DELETED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'file' => $item['filename']]);
        $this->flashToast('admin.media.deleted');
        return $this->back($this->app->adminPath('media'));
    }

    /**
     * @param list<string> $usage
     * @return list<string> translated "used on …" labels
     */
    private function usageLabels(array $usage): array
    {
        $labels = [];
        foreach ($usage as $place) {
            [$key, $count] = array_pad(explode(':', $place, 2), 2, null);
            $labels[] = $count === null
                ? $this->t('admin.media.used_' . $key)
                : $this->t('admin.media.used_' . $key, ['count' => (int) $count]);
        }
        return $labels;
    }

    private function library(): MediaLibrary
    {
        return new MediaLibrary($this->app->db(), $this->app->clock);
    }
}
