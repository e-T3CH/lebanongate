<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\EntryAdminRepository;
use Gate\Repositories\MessageRepository;
use Gate\Repositories\SubscriberRepository;
use Gate\Services\AuditLog;

/**
 * The dashboard: messages of the last seven days, unread messages, published projects and confirmed newsletter
 * subscribers; the newest messages, shortcuts to add content and the quick controls.
 *
 * The quick controls save with a small POST (fetch when JavaScript is on, a normal submit otherwise).
 */
final class DashboardController extends AdminController
{
    /** Quick control => setting key. Only these can be switched from the dashboard. */
    private const QUICK = [
        'newsletter_enabled' => 'site.newsletter_enabled',
        'maintenance_mode' => 'site.maintenance_mode',
    ];

    private const SHORTCUT_ICONS = [
        'project' => 'fa-solid fa-diagram-project',
        'news' => 'fa-regular fa-newspaper',
        'publication' => 'fa-regular fa-file-pdf',
        'album' => 'fa-regular fa-images',
    ];

    public function index(Request $request): Response
    {
        $user = $this->app->auth()->user();
        $settings = $this->app->settings();
        $messages = new MessageRepository($this->app->db(), $this->app->clock);
        $entries = new EntryAdminRepository($this->app->db(), $this->app->clock);
        $subscribers = (new SubscriberRepository($this->app->db(), $this->app->crypto(), $this->app->clock))->counts();
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
        $shortcuts = [];
        if ($this->can('content.edit')) {
            foreach (EntryController::SEGMENTS as $type => $segment) {
                $shortcuts[] = ['label' => $this->t('admin.entries.' . $type . '.add'), 'action' => $this->app->adminPath($segment . '/new'), 'icon' => self::SHORTCUT_ICONS[$type]];
            }
        }
        $unread = $messages->countUnread();
        return $this->adminView('admin/dashboard', 'dashboard', $this->t('admin.dashboard.title'), $this->t('admin.dashboard.subtitle'), [
            'twoFactorOn' => $user !== null && $user['totp_secret'] !== null,
            'siteName' => $settings->string('site.name', 'GATE Lebanon'),
            'kpi' => [
                'messages' => $messages->countSince(7),
                'unread' => $unread,
                'projects' => $entries->count('project'),
                'news' => $entries->count('news'),
                'subscribers' => $subscribers['confirmed'],
                'pending' => $subscribers['pending'],
            ],
            'recent' => $this->recentRows($messages),
            'quick' => $quick,
            'shortcuts' => $shortcuts,
            'canMessages' => $this->can('messages.view'),
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

    /** @return list<array{id: int, when: string, who: string, subject: string, unread: bool}> */
    private function recentRows(MessageRepository $repo): array
    {
        $rows = [];
        foreach ($repo->recent(6) as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'when' => $this->app->formatDate((string) $row['created_at']),
                'who' => (string) $row['name'] . ((string) $row['organisation'] !== '' ? ' · ' . (string) $row['organisation'] : ''),
                'subject' => $this->t('site.form.subjects.' . (string) $row['subject']),
                'unread' => $row['read_at'] === null,
            ];
        }
        return $rows;
    }
}
