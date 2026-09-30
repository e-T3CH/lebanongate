<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\AppointmentRepository;
use Gate\Repositories\ReviewRepository;
use Gate\Services\AuditLog;

/**
 * The dashboard of the approved mock-up with live data: requests of the last seven days, unread messages, the Google
 * rating and the reviews on the website (from the Google reviews module), the newest requests and the quick controls.
 *
 * The quick controls save with a small POST (fetch when JavaScript is on, a normal submit otherwise).
 */
final class DashboardController extends AdminController
{
    /** Quick control => setting key. Only these three can be switched from the dashboard. */
    private const QUICK = [
        'online_booking' => 'site.online_booking',
        'reviews_section' => 'reviews.section_enabled',
        'maintenance_mode' => 'site.maintenance_mode',
    ];

    public function index(Request $request): Response
    {
        $user = $this->app->auth()->user();
        $settings = $this->app->settings();
        $repo = new AppointmentRepository($this->app->db(), $this->app->clock);
        $unread = $repo->countUnread();
        $rating = $settings->string('reviews.rating');
        $count = $settings->string('reviews.count');
        $reviews = new ReviewRepository($this->app->db(), $this->app->clock);
        $reviewCounts = $reviews->counts() + ['visible_now' => $reviews->countVisible()];
        $quick = [];
        if ($this->can('settings.manage')) {
            foreach (self::QUICK as $key => $setting) {
                $quick[] = [
                    'key' => $key,
                    'name' => $this->t('admin.dashboard.quick_' . $key),
                    'description' => $this->t('admin.dashboard.quick_' . $key . '_desc'),
                    'checked' => $settings->bool($setting, $key !== 'maintenance_mode'),
                ];
            }
        }
        return $this->adminView('admin/dashboard', 'dashboard', $this->t('admin.dashboard.title'), $this->t('admin.dashboard.subtitle'), [
            'twoFactorOn' => $user !== null && $user['totp_secret'] !== null,
            'siteName' => $settings->string('site.name', 'GATE Lebanon'),
            'kpi' => [
                'requests' => $repo->countSince(7),
                'requests_delta' => $this->t('admin.dashboard.kpi_requests_delta'),
                'unread' => $unread,
                'unread_delta' => $this->t('admin.dashboard.kpi_unread_delta', ['count' => $repo->countByStatus('new')]),
                // Google's own rating and count (stored by the review sync); a [placeholder] means none yet.
                'rating' => is_numeric(trim($rating)) ? $rating : '—',
                'rating_delta' => ctype_digit(trim($count)) ? $this->t('admin.dashboard.kpi_rating_delta', ['count' => $count]) : $this->t('admin.dashboard.kpi_rating_empty'),
                'reviews_visible' => (string) $reviewCounts['visible_now'],
                'reviews_delta' => match (true) {
                    $reviewCounts['all'] === 0 => $this->t('admin.dashboard.kpi_reviews_empty'),
                    $reviewCounts['hidden'] > 0 => $this->t('admin.dashboard.kpi_reviews_hidden', ['count' => $reviewCounts['hidden']]),
                    default => $this->t('admin.dashboard.kpi_reviews_all'),
                },
            ],
            'recent' => $this->recentRows($repo),
            'quick' => $quick,
        ]);
    }

    public function quickToggle(Request $request): Response
    {
        $key = $request->input('key');
        $setting = self::QUICK[$key] ?? null;
        if ($setting === null) {
            return $this->jsonOrBack(false, 400);
        }
        $value = in_array($request->input('value'), ['1', 'on', 'true'], true);
        $this->app->settings()->set($setting, $value, 'bool');
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => [$setting], 'value' => $value]);
        if (!$request->wantsJson()) {
            $this->flashToast('admin.toast.saved');
        }
        return $this->jsonOrBack(true, 200);
    }

    private function jsonOrBack(bool $ok, int $status): Response
    {
        $request = $this->app->request();
        if ($request->wantsJson()) {
            return Response::json(['ok' => $ok], $status);
        }
        return $this->back($this->app->adminPath());
    }

    /** @return list<array<string, string>> */
    private function recentRows(AppointmentRepository $repo): array
    {
        $rows = [];
        foreach ($repo->recent(5) as $row) {
            $created = (string) $row['created_at'];
            $rows[] = [
                'when' => $this->app->formatDate($created),
                'who' => (string) $row['name'],
                'car' => (string) $row['car'],
                'box' => $this->t('site.form.types.' . (string) $row['gearbox_type']),
                'label' => $this->t('admin.statuses.' . (string) $row['status']),
                'tone' => match ((string) $row['status']) {
                    'new' => 'new',
                    'confirmed' => 'confirmed',
                    'diagnosis' => 'diagnosis',
                    'quoted' => 'quoted',
                    'cancelled' => 'danger',
                    default => 'neutral',
                },
            ];
        }
        return $rows;
    }
}
