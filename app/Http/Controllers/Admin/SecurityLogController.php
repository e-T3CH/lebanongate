<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Security\IpAddress;
use BMMatic\Services\AuditLog;
use BMMatic\Support\Csv;

/**
 * Settings → Security log: what happened in the panel (sign-ins, changes, refusals), filtered by event, user and
 * date, with a CSV export and a retention setting that removes entries older than the chosen number of days.
 */
final class SecurityLogController extends AdminController
{
    private const PER_PAGE = 30;
    private const RETENTION_CHOICES = [90, 180, 365, 730, 0];

    public function index(Request $request): Response
    {
        [$where, $params] = $this->conditions($request);
        $db = $this->app->db();
        $total = (int) $db->scalar('SELECT COUNT(*) FROM {audit_log}' . $where, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min((int) $request->query('page', '1'), $pages));
        $rows = $db->all(
            'SELECT l.*, u.`name` AS user_name, u.`email` AS user_email FROM {audit_log} l LEFT JOIN {users} u ON u.`id` = l.`user_id`'
            . $where . ' ORDER BY l.`created_at` DESC, l.`id` DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        );
        $events = array_map(static fn (array $row): string => (string) $row['event'], $db->all('SELECT DISTINCT `event` FROM {audit_log} ORDER BY `event`'));
        return $this->adminView('admin/security-log', 'security', $this->t('admin.settings.title'), $this->t('admin.log.subtitle'), [
            'tabs' => (new SettingsController($this->app))->tabs('log'),
            'rows' => array_map($this->row(...), $rows),
            'events' => $events,
            'filters' => ['event' => $request->query('event'), 'q' => $request->query('q'), 'from' => $request->query('from'), 'to' => $request->query('to')],
            'query' => $this->queryString($request),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'retention' => $this->app->settings()->int('security.log_retention_days', 365),
            'retentionChoices' => self::RETENTION_CHOICES,
        ]);
    }

    public function export(Request $request): Response
    {
        [$where, $params] = $this->conditions($request);
        $rows = $this->app->db()->all(
            'SELECT l.*, u.`name` AS user_name, u.`email` AS user_email FROM {audit_log} l LEFT JOIN {users} u ON u.`id` = l.`user_id`'
            . $where . ' ORDER BY l.`created_at` DESC, l.`id` DESC LIMIT 20000',
            $params
        );
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->app->errorResponse(500);
        }
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [$this->t('admin.log.when'), $this->t('admin.log.event'), $this->t('admin.log.user'), $this->t('admin.log.ip'), $this->t('admin.log.details')], ',', '"', '');
        foreach ($rows as $row) {
            $item = $this->row($row);
            fputcsv($handle, Csv::row([(string) $row['created_at'], (string) $row['event'], (string) $item['user'], (string) $item['ip'], (string) $item['context']]), ',', '"', '');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['export' => 'security_log', 'rows' => count($rows)]);
        return (new Response($csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="security-log-' . $this->app->clock->now()->format('Y-m-d') . '.csv"');
    }

    /** Retention: entries older than the chosen number of days are removed (0 = keep everything). */
    public function saveRetention(Request $request): Response
    {
        $days = (int) $request->input('retention_days');
        if (!in_array($days, self::RETENTION_CHOICES, true)) {
            return $this->back($this->app->adminPath('security/log'));
        }
        $this->app->settings()->set('security.log_retention_days', $days, 'int');
        $removed = 0;
        if ($days > 0) {
            $before = $this->app->clock->now()->modify('-' . $days . ' days')->format('Y-m-d H:i:s');
            $removed = $this->app->db()->run('DELETE FROM {audit_log} WHERE `created_at` < :before', ['before' => $before])->rowCount();
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['security.log_retention_days'], 'days' => $days, 'removed' => $removed]);
        $this->flashToast('admin.log.retention_saved', 'success', ['count' => $removed]);
        return $this->back($this->app->adminPath('security/log'));
    }

    /**
     * @param array<string, mixed> $row
     * @return array{when: string, event: string, label: string, user: string, ip: string, context: string}
     */
    private function row(array $row): array
    {
        $context = is_string($row['context'] ?? null) ? json_decode($row['context'], true) : null;
        $parts = [];
        foreach (is_array($context) ? $context : [] as $key => $value) {
            $parts[] = $key . '=' . (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES));
        }
        $name = is_string($row['user_name'] ?? null) ? $row['user_name'] : '';
        return [
            'when' => $this->app->formatDate((string) $row['created_at']),
            'event' => (string) $row['event'],
            'label' => $this->eventLabel((string) $row['event']),
            'user' => $name !== '' ? $name : ($row['user_id'] === null ? $this->t('admin.log.system') : '#' . (string) $row['user_id']),
            'ip' => $row['ip'] === null ? '' : IpAddress::fromBinary((string) $row['ip']),
            'context' => mb_substr(implode(' · ', $parts), 0, 160),
        ];
    }

    /** Event key → readable label; unknown events fall back to the key itself. */
    private function eventLabel(string $event): string
    {
        $key = 'admin.log.events.' . str_replace('.', '_', $event);
        $label = $this->t($key);
        return $label === $key ? $event : $label;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function conditions(Request $request): array
    {
        $sql = [];
        $params = [];
        $event = $request->query('event');
        if ($event !== '' && preg_match('/^[a-z0-9._]{1,64}$/', $event) === 1) {
            $sql[] = 'l.`event` = :event';
            $params['event'] = $event;
        }
        foreach (['from' => '>=', 'to' => '<='] as $field => $operator) {
            $value = $request->query($field);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $sql[] = 'l.`created_at` ' . $operator . ' :' . $field;
                $params[$field] = $value . ($field === 'from' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        $q = trim($request->query('q'));
        if ($q !== '') {
            $sql[] = "CONCAT_WS(' ', u.`name`, u.`email`, l.`context`) LIKE :q";
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], mb_substr($q, 0, 80)) . '%';
        }
        return [$sql === [] ? '' : ' WHERE ' . implode(' AND ', $sql), $params];
    }

    private function queryString(Request $request): string
    {
        $pairs = [];
        foreach (['event', 'q', 'from', 'to'] as $key) {
            $value = $request->query($key);
            if ($value !== '') {
                $pairs[$key] = $value;
            }
        }
        return $pairs === [] ? '' : '?' . http_build_query($pairs);
    }
}
