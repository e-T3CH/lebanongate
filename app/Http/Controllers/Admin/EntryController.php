<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Content\EntryTypes;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\ContentAdminRepository;
use Gate\Repositories\EntryAdminRepository;
use Gate\Repositories\RedirectRepository;
use Gate\Services\AuditLog;
use Gate\Services\MediaLibrary;

/**
 * Projects, news, publications and albums: one screen per type with the list (search, paging, language states) and a
 * card to add one, and the editor with the language tabs, the texts, the settings of the type (area, region, status,
 * dates, people reached, donors, cover, PDF, related project), publishing and the photo gallery.
 *
 * A new entry starts disabled, so nothing half-finished appears on the website. When a slug changes, the old address
 * gets a redirect to the new one.
 */
final class EntryController extends ContentController
{
    /** type => URL segment below the admin path */
    public const SEGMENTS = [
        'project' => 'projects',
        'news' => 'news',
        'publication' => 'publications',
        'album' => 'albums',
    ];

    public function index(Request $request, string $type): Response
    {
        $repo = $this->entries();
        $lang = $this->app->languages()->defaultCode();
        $search = mb_substr(trim($request->query('q')), 0, 100);
        $result = $repo->paginate($type, $lang, $search, max(1, (int) $request->query('page', '1')));
        $rows = [];
        foreach ($result['rows'] as $row) {
            $rows[] = $row + [
                'date' => $this->app->formatDate($row['published_on'] . ' 00:00:00', false),
                'statusLabel' => $row['status'] !== '' && in_array($row['status'], EntryTypes::statuses($type), true)
                    ? $this->t('site.' . ($type === 'project' ? 'status' : 'kinds') . '.' . $row['status']) : '',
            ];
        }
        return $this->adminView('admin/entries', $type, $this->t('admin.entries.' . $type . '.title'), $this->t('admin.entries.' . $type . '.subtitle'), [
            'type' => $type,
            'base' => $this->app->adminPath(self::SEGMENTS[$type]),
            'rows' => $rows,
            'result' => $result,
            'search' => $search,
            'languages' => $this->app->languages()->enabledCodes(),
            'canEdit' => $this->can('content.edit'),
            'errors' => $this->pullArray('entry_errors'),
            'icon' => EntryTypes::ICONS[$type],
        ]);
    }

    public function create(Request $request, string $type): Response
    {
        $title = self::line($request->input('title'), 200);
        $base = $this->app->adminPath(self::SEGMENTS[$type]);
        if ($title === '') {
            $this->app->session()->flash('entry_errors', ['title' => $this->t('validation.required')]);
            return $this->back($base);
        }
        $repo = $this->entries();
        $user = $this->app->auth()->user();
        $id = $repo->create($type, $user['id'] ?? null);
        $lang = $this->app->languages()->defaultCode();
        $repo->saveTranslation($id, $type, $lang, ['title' => $title, 'slug' => $this->uniqueSlug($type, $lang, self::slug($title), $id)], true);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $user['id'] ?? null, ['type' => $type . '.created', 'id' => $id]);
        $this->flashToast('admin.entries.created');
        return $this->back($base . '/' . $id);
    }

    public function edit(Request $request, string $type): Response
    {
        $repo = $this->entries();
        $entry = $repo->find((int) $request->param('id'), $type);
        if ($entry === null) {
            return $this->app->errorResponse(404);
        }
        $id = (int) $entry['id'];
        $lang = $this->editLang($request->query('lang'));
        $default = $this->app->languages()->defaultCode();
        $translations = $repo->translations($id);
        $translation = $translations[$lang] ?? [];
        $old = $this->pullArray('entry_old');
        $values = [];
        foreach (EntryAdminRepository::FIELDS as $field) {
            $values[$field] = is_scalar($old[$field] ?? null) ? (string) $old[$field] : (is_scalar($translation[$field] ?? null) ? (string) $translation[$field] : '');
        }
        $settings = [];
        foreach (['expertise_id', 'region', 'status', 'start_date', 'end_date', 'published_on', 'beneficiaries', 'donors', 'cover_media_id', 'file_media_id', 'related_id'] as $field) {
            $settings[$field] = is_scalar($old[$field] ?? null) ? (string) $old[$field] : (is_scalar($entry[$field] ?? null) ? (string) $entry[$field] : '');
        }
        $settings['is_enabled'] = $old !== [] ? ($old['is_enabled'] ?? '') === '1' : (int) $entry['is_enabled'] === 1;
        $settings['is_featured'] = $old !== [] ? ($old['is_featured'] ?? '') === '1' : (int) $entry['is_featured'] === 1;
        $base = $this->app->adminPath(self::SEGMENTS[$type]);
        $library = new MediaLibrary($this->app->db(), $this->app->clock);
        $galleryIds = EntryTypes::uses($type, 'gallery') ? $repo->gallery($id) : [];
        $images = EntryTypes::uses($type, 'gallery') ? $library->all([$default], '', 'image') : [];
        $listPage = (new ContentAdminRepository($this->app->db(), $this->app->clock))->pageByKey(EntryTypes::LIST_PAGES[$type]);
        $publicUrl = null;
        if ($listPage !== null && (int) $entry['is_enabled'] === 1 && isset($translations[$lang]) && (int) $translations[$lang]['is_published'] === 1) {
            $pageTranslations = (new ContentAdminRepository($this->app->db(), $this->app->clock))->pageTranslations((int) $listPage['id']);
            $listSlug = (string) ($pageTranslations[$lang]['slug'] ?? $pageTranslations[$default]['slug'] ?? '');
            $publicUrl = '/' . $lang . '/' . $listSlug . '/' . (string) $translations[$lang]['slug'];
        }
        $title = (string) ($translations[$lang]['title'] ?? $translations[$default]['title'] ?? ('#' . $id));
        return $this->adminView('admin/entry-edit', $type, $this->t('admin.entries.edit_title', ['title' => $title]), $this->t('admin.entries.' . $type . '.subtitle'), [
            'type' => $type,
            'entry' => ['id' => $id] + $settings,
            'lang' => $lang,
            'values' => $values,
            'published' => $old !== [] ? ($old['is_published'] ?? '') === '1' : (int) ($translation['is_published'] ?? 1) === 1,
            'isNewTranslation' => !isset($translations[$lang]),
            'tabs' => $this->languageTabs($base . '/' . $id, $lang, $translations),
            'base' => $base,
            'publicUrl' => $publicUrl,
            'options' => $this->options($type, $id, $default),
            'gallery' => array_values(array_filter(array_map(static fn (int $mid): ?array => $library->find($mid, [$default]), $galleryIds))),
            'images' => $images,
            'galleryIds' => $galleryIds,
            'errors' => $this->pullArray('entry_errors'),
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function save(Request $request, string $type): Response
    {
        $repo = $this->entries();
        $id = (int) $request->param('id');
        $entry = $repo->find($id, $type);
        if ($entry === null) {
            return $this->app->errorResponse(404);
        }
        $lang = $this->editLang($request->input('lang'));
        $default = $this->app->languages()->defaultCode();
        $base = $this->app->adminPath(self::SEGMENTS[$type]);
        $fields = [
            'title' => self::line($request->input('title'), 200),
            'slug' => self::slug($request->input('slug')),
            'summary' => self::text($request->input('summary'), 600),
            'body' => self::richText($request->input('body')),
            'location' => self::line($request->input('location'), 160),
            'meta_title' => self::line($request->input('meta_title')),
            'meta_description' => self::line($request->input('meta_description'), 320),
        ];
        // An empty slug takes the default language's slug (Arabic titles have no Latin letters), else the title.
        if ($fields['slug'] === '' && $fields['title'] !== '') {
            $translations = $repo->translations($id);
            $fields['slug'] = is_string($translations[$default]['slug'] ?? null) && $lang !== $default ? $translations[$default]['slug'] : self::slug($fields['title']);
            if ($fields['slug'] === '') {
                $fields['slug'] = self::SEGMENTS[$type] . '-' . $id;
            }
        }
        $settings = [
            'expertise_id' => $request->input('expertise_id'),
            'region' => $request->input('region'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'published_on' => $request->input('published_on'),
            'beneficiaries' => trim($request->input('beneficiaries')),
            'donors' => self::line($request->input('donors'), 300),
            'cover_media_id' => $request->input('cover_media_id'),
            'file_media_id' => $request->input('file_media_id'),
            'related_id' => $request->input('related_id'),
        ];
        $errors = [];
        if ($fields['title'] === '') {
            $errors['title'] = $this->t('validation.required');
        }
        if ($fields['slug'] !== '' && $repo->slugTaken($type, $lang, $fields['slug'], $id)) {
            $errors['slug'] = $this->t('admin.pages.slug_taken');
        }
        foreach (['published_on', 'start_date', 'end_date'] as $field) {
            if ($settings[$field] !== '' && !self::isDate($settings[$field])) {
                $errors[$field] = $this->t('admin.entries.date_invalid');
            }
        }
        if ($settings['published_on'] === '') {
            $errors['published_on'] = $this->t('validation.required');
        }
        if ($settings['start_date'] !== '' && $settings['end_date'] !== '' && $settings['end_date'] < $settings['start_date']) {
            $errors['end_date'] = $this->t('admin.entries.end_before_start');
        }
        if ($settings['beneficiaries'] !== '' && (!ctype_digit(str_replace([',', ' ', '.'], '', $settings['beneficiaries'])) || strlen($settings['beneficiaries']) > 12)) {
            $errors['beneficiaries'] = $this->t('validation.numeric');
        }
        if ($type === 'publication' && $request->input('is_enabled') === '1' && $this->mediaId($settings['file_media_id'], 'document') === null) {
            $errors['file_media_id'] = $this->t('admin.entries.file_required');
        }
        if ($errors !== []) {
            $this->app->session()->flash('entry_errors', $errors);
            $this->app->session()->flash('entry_old', $fields + $settings + ['is_published' => $request->input('is_published'), 'is_enabled' => $request->input('is_enabled'), 'is_featured' => $request->input('is_featured')]);
            $this->flashToast('admin.entries.check_fields', 'error');
            return $this->back($base . '/' . $id . '?lang=' . $lang);
        }

        $published = $request->input('is_published') === '1';
        $oldSlug = $repo->saveTranslation($id, $type, $lang, $fields, $published);
        $expertiseId = ctype_digit($settings['expertise_id']) ? (int) $settings['expertise_id'] : null;
        if ($expertiseId !== null && $this->app->db()->first('expertise', ['id' => $expertiseId], ['id']) === null) {
            $expertiseId = null;
        }
        $relatedId = ctype_digit($settings['related_id']) ? (int) $settings['related_id'] : null;
        if ($relatedId !== null && $repo->find($relatedId, 'project') === null) {
            $relatedId = null;
        }
        $repo->save($id, [
            'expertise_id' => EntryTypes::uses($type, 'expertise') ? $expertiseId : null,
            'region' => EntryTypes::uses($type, 'region') && in_array($settings['region'], EntryTypes::REGIONS, true) ? $settings['region'] : '',
            'status' => in_array($settings['status'], EntryTypes::statuses($type), true) ? $settings['status'] : ((string) (EntryTypes::statuses($type)[0] ?? '')),
            'start_date' => EntryTypes::uses($type, 'dates') && $settings['start_date'] !== '' ? $settings['start_date'] : null,
            'end_date' => EntryTypes::uses($type, 'dates') && $settings['end_date'] !== '' ? $settings['end_date'] : null,
            'published_on' => $settings['published_on'],
            'beneficiaries' => EntryTypes::uses($type, 'beneficiaries') && $settings['beneficiaries'] !== '' ? (int) str_replace([',', ' ', '.'], '', $settings['beneficiaries']) : null,
            'donors' => EntryTypes::uses($type, 'donors') ? $settings['donors'] : '',
            'cover_media_id' => $this->mediaId($settings['cover_media_id'], 'image'),
            'file_media_id' => EntryTypes::uses($type, 'file') ? $this->mediaId($settings['file_media_id'], 'document') : null,
            'related_id' => EntryTypes::uses($type, 'related') ? $relatedId : null,
            'is_featured' => EntryTypes::uses($type, 'featured') && $request->input('is_featured') === '1',
            'is_enabled' => $request->input('is_enabled') === '1',
        ]);
        if ($oldSlug !== null && $oldSlug !== '') {
            $this->addRedirect($type, $lang, $oldSlug, $fields['slug']);
        }
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $type, 'id' => $id, 'lang' => $lang, 'published' => $published]);
        $this->flashToast('admin.content.saved');
        return $this->back($base . '/' . $id . '?lang=' . $lang);
    }

    /** The photo gallery: the chosen images in the order given (the sortable list), newly ticked ones at the end. */
    public function saveGallery(Request $request, string $type): Response
    {
        $repo = $this->entries();
        $id = (int) $request->param('id');
        if ($repo->find($id, $type) === null || !EntryTypes::uses($type, 'gallery')) {
            return $this->app->errorResponse(404);
        }
        $order = array_values(array_filter(array_map('intval', $request->inputList('order'))));
        $keep = array_values(array_filter(array_map('intval', $request->inputList('keep'))));
        $add = array_values(array_filter(array_map('intval', $request->inputList('add'))));
        $ids = array_values(array_unique(array_merge(array_values(array_filter($order, static fn (int $m): bool => in_array($m, $keep, true))), $add)));
        $repo->setGallery($id, $ids);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $type . '.gallery', 'id' => $id, 'photos' => count($ids)]);
        $this->flashToast('admin.entries.gallery_saved');
        return $this->back($this->app->adminPath(self::SEGMENTS[$type] . '/' . $id) . '#gallery');
    }

    public function delete(Request $request, string $type): Response
    {
        $repo = $this->entries();
        $id = (int) $request->param('id');
        if ($repo->find($id, $type) === null) {
            return $this->app->errorResponse(404);
        }
        // The entry disappears from the website, so its old addresses get a redirect to the list page.
        foreach ($repo->translations($id) as $lang => $translation) {
            $slug = is_string($translation['slug'] ?? null) ? $translation['slug'] : '';
            if ($slug !== '') {
                $this->addRedirect($type, (string) $lang, $slug, null);
            }
        }
        $repo->delete($id);
        $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $this->app->auth()->user()['id'] ?? null, ['type' => $type . '.deleted', 'id' => $id]);
        $this->flashToast('admin.entries.deleted');
        return $this->back($this->app->adminPath(self::SEGMENTS[$type]));
    }

    /**
     * The choice lists of the editor.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function options(string $type, int $id, string $lang): array
    {
        $content = new ContentAdminRepository($this->app->db(), $this->app->clock);
        $none = ['value' => '', 'label' => $this->t('admin.entries.none')];
        $expertise = [$none];
        foreach ($content->expertiseOptions($lang) as $value => $label) {
            $expertise[] = ['value' => (string) $value, 'label' => $label];
        }
        $regions = [$none];
        foreach (EntryTypes::REGIONS as $region) {
            $regions[] = ['value' => $region, 'label' => $this->t('site.regions.' . $region)];
        }
        $statuses = [];
        foreach (EntryTypes::statuses($type) as $status) {
            $statuses[] = ['value' => $status, 'label' => $this->t('site.' . ($type === 'project' ? 'status' : 'kinds') . '.' . $status)];
        }
        $projects = [$none];
        foreach ($this->entries()->projectOptions($lang) as $value => $label) {
            if ($value !== $id) {
                $projects[] = ['value' => (string) $value, 'label' => $label];
            }
        }
        return [
            'expertise' => $expertise,
            'regions' => $regions,
            'statuses' => $statuses,
            'projects' => $projects,
            'images' => $this->mediaOptions('image', $this->t('admin.media.none')),
            'documents' => $this->mediaOptions('document', $this->t('admin.media.none')),
        ];
    }

    private function uniqueSlug(string $type, string $lang, string $slug, int $id): string
    {
        $slug = $slug !== '' ? $slug : self::SEGMENTS[$type] . '-' . $id;
        $candidate = $slug;
        for ($n = 2; $this->entries()->slugTaken($type, $lang, $candidate, $id); $n++) {
            $candidate = $slug . '-' . $n;
        }
        return $candidate;
    }

    private function addRedirect(string $type, string $lang, string $oldSlug, ?string $newSlug): void
    {
        $content = new ContentAdminRepository($this->app->db(), $this->app->clock);
        $listPage = $content->pageByKey(EntryTypes::LIST_PAGES[$type]);
        $listSlug = '';
        if ($listPage !== null) {
            $translations = $content->pageTranslations((int) $listPage['id']);
            $listSlug = (string) ($translations[$lang]['slug'] ?? $translations[$this->app->languages()->defaultCode()]['slug'] ?? '');
        }
        $base = '/' . $lang . ($listSlug === '' ? '' : '/' . $listSlug);
        (new RedirectRepository($this->app->db(), $this->app->clock))->add($base . '/' . $oldSlug, $newSlug === null ? $base : $base . '/' . $newSlug, 301);
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private function entries(): EntryAdminRepository
    {
        return new EntryAdminRepository($this->app->db(), $this->app->clock);
    }
}
