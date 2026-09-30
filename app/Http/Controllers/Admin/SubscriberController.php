<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\SubscriberRepository;
use Gate\Services\AuditLog;
use Gate\Support\Csv;

/**
 * Newsletter subscribers: counts per state, the list with search and state tabs, a CSV export of the confirmed
 * addresses (for the mailing tool GATE Lebanon uses) and delete (for "forget me" requests).
 */
final class SubscriberController extends AdminController
{
    public function index(Request $request): Response
    {
        $repo = $this->repo();
        $state = in_array($request->query('state'), SubscriberRepository::STATES, true) ? $request->query('state') : 'confirmed';
        $search = mb_substr(trim($request->query('q')), 0, 100);
        $result = $repo->paginate($state, $search, max(1, (int) $request->query('page', '1')));
        $rows = [];
        foreach ($result['rows'] as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'email' => (string) $row['email'],
                'lang' => strtoupper((string) $row['lang_code']),
                'since' => $this->app->formatDate((string) ($row['confirmed_at'] ?? $row['created_at']), false),
                'state' => SubscriberRepository::state($row),
            ];
        }
        return $this->adminView('admin/subscribers', 'subscribers', $this->t('admin.subscribers.title'), $this->t('admin.subscribers.subtitle'), [
            'rows' => $rows,
            'result' => $result,
            'state' => $state,
            'search' => $search,
            'counts' => $repo->counts(),
            'enabled' => $this->app->settings()->bool('site.newsletter_enabled', true),
        ]);
    }

    public function export(Request $request): Response
    {
        $rows = $this->repo()->confirmed();
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->app->errorResponse(500);
        }
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['email', 'language', 'confirmed_at'], ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, Csv::row([(string) $row['email'], (string) $row['lang_code'], (string) $row['confirmed_at']]), ',', '"', '');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        $this->app->audit()->record(AuditLog::SUBSCRIBERS_EXPORTED, $this->app->auth()->user()['id'] ?? null, ['rows' => count($rows)]);
        return (new Response($csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="subscribers-' . $this->app->clock->now()->format('Y-m-d') . '.csv"');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $row = $this->repo()->find($id);
        if ($row !== null && $this->repo()->delete($id) > 0) {
            // The address itself is not logged: deleting is how a "forget me" request is honoured.
            $this->app->audit()->record(AuditLog::SUBSCRIBER_DELETED, $this->app->auth()->user()['id'] ?? null, ['id' => $id]);
            $this->flashToast('admin.subscribers.deleted');
        }
        return $this->back($this->app->adminPath('subscribers'));
    }

    private function repo(): SubscriberRepository
    {
        return new SubscriberRepository($this->app->db(), $this->app->crypto(), $this->app->clock);
    }
}
