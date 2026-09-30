<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Repositories\ReviewRepository;
use BMMatic\Reviews\ManualImportProvider;
use BMMatic\Reviews\ReviewPhotos;
use BMMatic\Reviews\ReviewProviders;
use BMMatic\Reviews\ReviewSync;
use BMMatic\Services\AuditLog;

/**
 * Content → Google reviews (the approved screen): the connection with its limits and state, what appears on the
 * website, and the review list with filters, a visibility toggle per review and bulk show/hide.
 *
 * Visibility is the workshop's choice, so every change to it is written to the security log; a sync never touches it.
 */
final class ReviewController extends AdminController
{
    public function index(Request $request): Response
    {
        $settings = $this->app->settings();
        $repo = $this->repo();
        $providers = new ReviewProviders($settings);
        $provider = $providers->active();
        $sync = $this->syncService($repo);
        $filters = self::filters($request);
        $result = $repo->paginate($filters, max(1, (int) $request->query('page', '1')));
        $counts = $repo->counts();
        $lastLog = $repo->lastLog();
        $lastError = $settings->string('reviews.last_error');
        $limits = $provider->limits();
        return $this->adminView('admin/reviews', 'reviews', $this->t('admin.reviews.title'), $this->t('admin.reviews.subtitle'), [
            'rows' => array_map($this->row(...), $result['rows']),
            'result' => $result,
            'counts' => $counts,
            'filters' => $filters,
            'query' => self::queryString($filters),
            'languages' => $repo->languages(),
            'connection' => [
                'provider' => $providers->activeKey(),
                'configured' => $provider->isConfigured(),
                'limit_note' => $this->t('admin.reviews.limit_' . $limits['note'], ['max' => $limits['max_reviews'] ?? 0]),
                'max_reviews' => $limits['max_reviews'],
                'last_sync' => $settings->string('reviews.last_sync_at') !== '' ? $this->app->formatDate($settings->string('reviews.last_sync_at')) : '',
                // A manual import has nothing to fetch, so there is no "next sync" to announce.
                'next_sync' => $providers->activeKey() !== 'manual' && $sync->nextSyncAt() !== '' ? $this->app->formatDate($sync->nextSyncAt()) : '',
                'last_error' => $lastError,
                'last_result' => $lastLog === null ? '' : $this->t('admin.reviews.last_result', [
                    'added' => (int) $lastLog['added'],
                    'updated' => (int) $lastLog['updated'],
                    'removed' => (int) $lastLog['removed'],
                ]),
                'test_endpoint' => $providers->apiBase(),
                'oauth_ready' => $settings->string('google.oauth_client_id') !== '' && $settings->string('google.oauth_client_secret') !== '',
                'connected' => $settings->string('google.oauth_refresh_token') !== '',
            ],
            'values' => [
                'provider' => $providers->activeKey(),
                'location_id' => $settings->string('google.location_id'),
                'place_id' => $settings->string('google.place_id'),
                'reviews_url' => $settings->string('google.reviews_url'),
                'sync_interval_hours' => (string) $settings->int('reviews.sync_interval_hours', 24),
                'new_visibility' => $settings->string('reviews.new_visibility', 'hidden'),
                'display_order' => $settings->string('reviews.display_order', 'newest'),
                'has_api_key' => $settings->string('google.api_key') !== '',
                'has_client_secret' => $settings->string('google.oauth_client_secret') !== '',
                'client_id' => $settings->string('google.oauth_client_id'),
            ],
            'display' => [
                'section_enabled' => $settings->bool('reviews.section_enabled', true),
                'show_rating_badge' => $settings->bool('reviews.show_rating_badge', true),
                'show_photos' => $settings->bool('reviews.show_photos'),
                'link_to_google' => $settings->bool('reviews.link_to_google', true),
            ],
            'rating' => ['value' => $settings->string('reviews.rating'), 'count' => $settings->string('reviews.count')],
            'import' => $this->app->session()->get('review_import'),
            'errors' => $this->pullArray('review_errors'),
            'canManage' => $this->can('reviews.manage'),
        ]);
    }

    public function saveConnection(Request $request): Response
    {
        $settings = $this->app->settings();
        $provider = $request->input('provider');
        if (!in_array($provider, ReviewProviders::KEYS, true)) {
            return $this->back($this->app->adminPath('reviews'));
        }
        $errors = [];
        $settings->set('reviews.provider', $provider);
        $location = trim($request->input('location_id'));
        if ($location !== '' && preg_match('#^accounts/[A-Za-z0-9_\-\[\]]+/locations/[A-Za-z0-9_\-\[\]]+$#', $location) !== 1) {
            $errors['location_id'] = $this->t('admin.reviews.location_invalid');
        } else {
            $settings->set('google.location_id', $location);
        }
        $placeId = trim($request->input('place_id'));
        if ($placeId !== '' && preg_match('/^[A-Za-z0-9_\-\[\]]{6,190}$/', $placeId) !== 1) {
            $errors['place_id'] = $this->t('validation.choice');
        } else {
            $settings->set('google.place_id', $placeId);
        }
        $reviewsUrl = trim($request->input('reviews_url'));
        if ($reviewsUrl !== '' && preg_match('#^https://[^\s]{6,300}$#', $reviewsUrl) !== 1 && !str_contains($reviewsUrl, '[')) {
            $errors['reviews_url'] = $this->t('validation.url');
        } else {
            $settings->set('google.reviews_url', $reviewsUrl);
        }
        $interval = (int) $request->input('sync_interval_hours');
        $settings->set('reviews.sync_interval_hours', in_array($interval, [0, 6, 12, 24], true) ? $interval : 24, 'int');
        $settings->set('reviews.new_visibility', $request->input('new_visibility') === 'visible' ? 'visible' : 'hidden');
        $order = $request->input('display_order');
        $settings->set('reviews.display_order', in_array($order, ReviewRepository::ORDERS, true) ? $order : 'newest');
        $settings->set('google.oauth_client_id', trim($request->input('client_id')));
        foreach (['api_key' => 'google.api_key', 'client_secret' => 'google.oauth_client_secret'] as $field => $key) {
            $value = trim($request->input($field));
            if ($value !== '') {
                $settings->set($key, $value, 'string', true);
            }
        }
        if ($errors !== []) {
            $this->app->session()->flash('review_errors', $errors);
        } else {
            $this->flashToast('admin.toast.saved');
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['reviews.*', 'google.*'], 'provider' => $provider]);
        return $this->back($this->app->adminPath('reviews'));
    }

    public function saveDisplay(Request $request): Response
    {
        $settings = $this->app->settings();
        $keys = ['section_enabled' => 'reviews.section_enabled', 'show_rating_badge' => 'reviews.show_rating_badge', 'show_photos' => 'reviews.show_photos', 'link_to_google' => 'reviews.link_to_google'];
        $field = $request->input('key');
        if (!isset($keys[$field])) {
            return $this->jsonOrBack(false, 400);
        }
        $settings->set($keys[$field], in_array($request->input('value'), ['1', 'on', 'true'], true), 'bool');
        $this->warmPhotos();
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => [$keys[$field]]]);
        if (!$this->app->request()->wantsJson()) {
            $this->flashToast('admin.toast.saved');
        }
        return $this->jsonOrBack(true, 200);
    }

    public function sync(Request $request): Response
    {
        $repo = $this->repo();
        $providers = new ReviewProviders($this->app->settings());
        $result = $this->syncService($repo)->run($providers->active(), false, true);
        $this->app->audit()->record(AuditLog::REVIEWS_SYNCED, $this->app->auth()->user()['id'] ?? null, [
            'provider' => $providers->activeKey(),
            'status' => $result['status'],
            'added' => $result['added'],
            'updated' => $result['updated'],
        ]);
        $this->warmPhotos();
        if ($result['status'] === 'error') {
            $this->flashToast('admin.reviews.sync_failed', 'error');
        } elseif ($result['status'] === 'skipped') {
            $this->flashToast('admin.reviews.sync_skipped', 'info');
        } else {
            $this->flashToast('admin.reviews.sync_done', 'success', ['added' => $result['added'], 'updated' => $result['updated']]);
        }
        return $this->back($this->app->adminPath('reviews'));
    }

    public function setVisibility(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        $review = $repo->find($id);
        if ($review === null) {
            return $this->jsonOrBack(false, 404);
        }
        $visible = in_array($request->input('value'), ['1', 'on', 'true'], true);
        $repo->setVisible($id, $visible);
        $this->app->audit()->record(AuditLog::REVIEW_VISIBILITY_CHANGED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'visible' => $visible]);
        $this->warmPhotos();
        if (!$this->app->request()->wantsJson()) {
            $this->flashToast('admin.reviews.visibility_saved');
        }
        return $this->jsonOrBack(true, 200);
    }

    /** Show or hide everything that matches the filters currently applied. */
    public function bulk(Request $request): Response
    {
        $visible = $request->input('visible') === '1';
        $filters = self::filters($request, true);
        $changed = $this->repo()->setVisibleForFilter($filters, $visible);
        $this->app->audit()->record(AuditLog::REVIEW_VISIBILITY_CHANGED, $this->app->auth()->user()['id'] ?? null, ['bulk' => true, 'visible' => $visible, 'count' => $changed, 'filters' => array_filter($filters)]);
        $this->warmPhotos();
        $this->flashToast($visible ? 'admin.reviews.bulk_shown' : 'admin.reviews.bulk_hidden', 'success', ['count' => $changed]);
        return $this->back($this->app->adminPath('reviews') . self::queryString($filters));
    }

    /** Step 1 of a manual import: read the file and show a preview with the guessed column mapping. */
    public function importPreview(Request $request): Response
    {
        $file = $request->file('file');
        $tmp = is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->flashToast('admin.reviews.import_failed', 'error');
            return $this->back($this->app->adminPath('reviews'));
        }
        $contents = (string) file_get_contents($tmp);
        if (strlen($contents) > 2 * 1024 * 1024) {
            $this->flashToast('admin.reviews.import_too_large', 'error');
            return $this->back($this->app->adminPath('reviews'));
        }
        $parsed = ManualImportProvider::parse($contents, is_string($file['name'] ?? null) ? $file['name'] : '');
        if (isset($parsed['error'])) {
            $this->flashToast('admin.reviews.import_error_' . $parsed['error'], 'error');
            return $this->back($this->app->adminPath('reviews'));
        }
        $mapping = ManualImportProvider::guessMapping($parsed['columns']);
        $preview = ManualImportProvider::map(array_slice($parsed['rows'], 0, 5), $mapping);
        $repo = $this->repo();
        $duplicates = 0;
        foreach (ManualImportProvider::map($parsed['rows'], $mapping) as $review) {
            $duplicates += $repo->existsByExternalId($review->externalId) ? 1 : 0;
        }
        // The file itself is kept in the session until the import is confirmed: nothing is stored before then.
        $this->app->session()->set('review_import', [
            'columns' => $parsed['columns'],
            'mapping' => $mapping,
            'rows' => array_slice($parsed['rows'], 0, 500),
            'total' => count($parsed['rows']),
            'duplicates' => $duplicates,
            'preview' => array_map(static fn ($r): array => [
                'name' => $r->reviewerName,
                'rating' => $r->rating,
                'date' => substr($r->reviewDate, 0, 10),
                'text' => mb_strimwidth($r->text, 0, 80, '…'),
            ], $preview),
        ]);
        return $this->back($this->app->adminPath('reviews') . '#import');
    }

    /** Step 2: store the rows with the mapping the owner confirmed (or corrected). */
    public function importConfirm(Request $request): Response
    {
        $import = $this->app->session()->get('review_import');
        $this->app->session()->remove('review_import');
        if (!is_array($import) || !is_array($import['rows'] ?? null)) {
            $this->flashToast('admin.reviews.import_expired', 'error');
            return $this->back($this->app->adminPath('reviews'));
        }
        $mapping = [];
        foreach (ManualImportProvider::FIELDS as $field) {
            $column = $request->input('map_' . $field);
            if ($column !== '' && in_array($column, is_array($import['columns'] ?? null) ? $import['columns'] : [], true)) {
                $mapping[$field] = $column;
            }
        }
        /** @var list<array<string, string>> $rows */
        $rows = $import['rows'];
        $reviews = ManualImportProvider::map($rows, $mapping);
        $repo = $this->repo();
        $visibleWhenNew = $this->app->settings()->string('reviews.new_visibility', 'hidden') === 'visible';
        $added = 0;
        $updated = 0;
        $skipped = 0;
        foreach ($reviews as $review) {
            $outcome = $repo->upsert($review, 'manual', $visibleWhenNew);
            $added += $outcome === 'added' ? 1 : 0;
            $updated += $outcome === 'updated' ? 1 : 0;
            $skipped += $outcome === 'unchanged' ? 1 : 0;
        }
        $logId = $repo->startLog('manual', false);
        $repo->finishLog($logId, 'ok', ['added' => $added, 'updated' => $updated, 'removed' => 0], 'Manual import');
        $this->app->audit()->record(AuditLog::REVIEWS_IMPORTED, $this->app->auth()->user()['id'] ?? null, ['added' => $added, 'updated' => $updated, 'skipped' => $skipped]);
        $this->flashToast('admin.reviews.import_done', 'success', ['added' => $added, 'updated' => $updated, 'skipped' => $skipped]);
        return $this->back($this->app->adminPath('reviews'));
    }

    // ------------------------------------------------------------------------------------------- OAuth consent

    /** Sends the owner to Google's consent screen; the state value protects the callback against forged returns. */
    public function connect(Request $request): Response
    {
        $providers = new ReviewProviders($this->app->settings());
        $provider = $providers->get('business_profile');
        if (!$provider instanceof \BMMatic\Reviews\BusinessProfileProvider) {
            return $this->back($this->app->adminPath('reviews'));
        }
        $state = bin2hex(random_bytes(16));
        $this->app->session()->set('reviews_oauth_state', $state);
        return Response::redirect($provider->authorizationUrl($this->redirectUri(), $state), 302);
    }

    /** Google returns here with a code: exchange it for the refresh token and store it encrypted. */
    public function callback(Request $request): Response
    {
        $session = $this->app->session();
        $expected = $session->get('reviews_oauth_state');
        $session->remove('reviews_oauth_state');
        $state = $request->query('state');
        if (!is_string($expected) || $expected === '' || !hash_equals($expected, $state)) {
            $this->flashToast('admin.reviews.oauth_state_failed', 'error');
            return Response::redirect($this->app->adminPath('reviews'), 303);
        }
        $error = $request->query('error');
        if ($error !== '') {
            $this->app->settings()->set('reviews.last_error', 'oauth: ' . mb_substr($error, 0, 200));
            $this->flashToast('admin.reviews.oauth_denied', 'error');
            return Response::redirect($this->app->adminPath('reviews'), 303);
        }
        $providers = new ReviewProviders($this->app->settings());
        $provider = $providers->get('business_profile');
        if (!$provider instanceof \BMMatic\Reviews\BusinessProfileProvider) {
            return Response::redirect($this->app->adminPath('reviews'), 303);
        }
        $result = $provider->exchangeCode($request->query('code'), $this->redirectUri());
        if (isset($result['error'])) {
            $this->app->settings()->set('reviews.last_error', 'oauth: ' . $result['error']);
            $this->flashToast('admin.reviews.oauth_failed', 'error');
            return Response::redirect($this->app->adminPath('reviews'), 303);
        }
        $settings = $this->app->settings();
        $settings->set('google.oauth_refresh_token', $result['refresh_token'], 'string', true);
        $settings->set('reviews.last_error', '');
        $settings->set('reviews.retry_after', '');
        $settings->set('reviews.failures', 0, 'int');
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['google.oauth_refresh_token'], 'connected' => true]);
        $this->flashToast('admin.reviews.oauth_connected_toast');
        return Response::redirect($this->app->adminPath('reviews'), 303);
    }

    private function redirectUri(): string
    {
        return $this->app->baseUrl() . $this->app->adminPath('reviews/callback');
    }

    // ------------------------------------------------------------------------------------------------ internals

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function row(array $row): array
    {
        $name = (string) $row['reviewer_name'];
        return [
            'id' => (int) $row['id'],
            'name' => $name,
            'initial' => mb_strtoupper(mb_substr(trim($name), 0, 2)),
            'date' => $this->app->formatDate((string) $row['review_date'], false),
            'rating' => (float) $row['rating'],
            'text' => (string) ($row['text'] ?? ''),
            'language' => strtoupper((string) $row['language']),
            'visible' => (int) $row['is_visible'] === 1,
            'deleted' => $row['deleted_at'] !== null,
            'reply' => (string) ($row['owner_reply'] ?? ''),
        ];
    }

    /** @return array{tab?: string, rating?: int, language?: string, q?: string} */
    public static function filters(Request $request, bool $fromPost = false): array
    {
        $read = static fn (string $key): string => $fromPost ? $request->input($key) : $request->query($key);
        $filters = [];
        $tab = $read('tab');
        if (in_array($tab, ['visible', 'hidden'], true)) {
            $filters['tab'] = $tab;
        }
        $rating = (int) $read('rating');
        if ($rating >= 1 && $rating <= 5) {
            $filters['rating'] = $rating;
        }
        $language = strtolower(trim($read('language')));
        if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $language) === 1) {
            $filters['language'] = $language;
        }
        $q = trim($read('q'));
        if ($q !== '') {
            $filters['q'] = mb_substr($q, 0, 80);
        }
        return $filters;
    }

    /** @param array<string, mixed> $filters */
    public static function queryString(array $filters): string
    {
        return $filters === [] ? '' : '?' . http_build_query($filters);
    }

    private function jsonOrBack(bool $ok, int $status): Response
    {
        if ($this->app->request()->wantsJson()) {
            return Response::json(['ok' => $ok], $status);
        }
        return $this->back($this->app->adminPath('reviews'));
    }

    private function repo(): ReviewRepository
    {
        return new ReviewRepository($this->app->db(), $this->app->clock);
    }

    /**
     * Fetches the photos of the reviews the website shows, after this response was sent. The website only links to
     * a photo this site already has, so a photo Google will not hand over simply stays the reviewer's initials.
     */
    private function warmPhotos(): void
    {
        $settings = $this->app->settings();
        if (!$settings->bool('reviews.show_photos')) {
            return;
        }
        $rows = $this->repo()->visible(24, $settings->string('reviews.display_order', 'newest'));
        if ($rows === []) {
            return;
        }
        $this->app->defer(static function () use ($rows): void {
            (new ReviewPhotos())->warm($rows);
        });
    }

    private function syncService(ReviewRepository $repo): ReviewSync
    {
        return new ReviewSync($repo, $this->app->settings(), $this->app->limiter(), $this->app->clock);
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
