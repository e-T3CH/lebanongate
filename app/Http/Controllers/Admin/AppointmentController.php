<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\I18n\Translator;
use BMMatic\Mail\AdminMails;
use BMMatic\Mail\MailQueue;
use BMMatic\Mail\SmtpTransport;
use BMMatic\Repositories\AppointmentRepository;
use BMMatic\Services\AuditLog;
use BMMatic\Services\StatusEmails;
use BMMatic\Support\Csv;

/**
 * Appointment requests: the workflow list (filters, search, sorting, pagination, CSV export) and the detail screen
 * with the status flow New → Confirmed → In diagnosis → Quoted → Done (or Cancelled) and internal notes.
 *
 * Opening a request marks it read for the Messages inbox; that is not audit-logged because it is not a change.
 * Every status change is logged, and sends the customer an email when that status has one switched on.
 */
final class AppointmentController extends AdminController
{
    public function index(Request $request): Response
    {
        $repo = $this->repo();
        $filters = self::filters($request);
        $result = $repo->paginate($filters, max(1, (int) $request->query('page', '1')));
        return $this->adminView('admin/appointments', 'appointments', $this->t('admin.appointments.title'), $this->t('admin.appointments.subtitle'), [
            'result' => $result,
            'rows' => array_map($this->row(...), $result['rows']),
            'filters' => $filters,
            'counts' => $repo->countsByStatus(),
            'query' => self::queryString($filters),
            'statuses' => AppointmentRepository::STATUSES,
        ]);
    }

    public function show(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        $appointment = $repo->find($id);
        if ($appointment === null) {
            return $this->app->errorResponse(404);
        }
        $user = $this->app->auth()->user();
        $repo->markRead($id, $user['id'] ?? null);
        $settings = $this->app->settings();
        $notes = [];
        foreach ($repo->notes($id) as $note) {
            $notes[] = [
                'id' => (int) $note['id'],
                'author' => (string) $note['author_name'],
                'when' => $this->app->formatDate((string) $note['created_at']),
                'body' => (string) $note['body'],
            ];
        }
        return $this->adminView('admin/appointment', 'appointments', $this->t('admin.appointments.detail_title', ['id' => $id]), $this->t('admin.appointments.detail_subtitle', ['name' => (string) $appointment['name']]), [
            'appointment' => [
                'id' => $id,
                'name' => (string) $appointment['name'],
                'email' => (string) $appointment['email'],
                'phone' => (string) $appointment['phone'],
                'car' => (string) $appointment['car'],
                'gearbox' => $this->t('site.form.types.' . (string) $appointment['gearbox_type']),
                'symptoms' => (string) $appointment['symptoms'],
                'lang' => strtoupper((string) $appointment['lang_code']),
                'received' => $this->app->formatDate((string) $appointment['created_at']),
                'consent' => $this->app->formatDate((string) $appointment['consent_at']),
                'status' => (string) $appointment['status'],
                'unread' => $appointment['read_at'] === null,
                'mailto' => self::mailto((string) $appointment['email'], $this->t('admin.messages.reply_subject', ['site' => $settings->string('site.name', 'BM-Matic')])),
                'telHref' => 'tel:' . (string) preg_replace('/[^0-9+]/', '', (string) $appointment['phone']),
            ],
            'notes' => $notes,
            'statuses' => AppointmentRepository::STATUSES,
            'statusEmails' => (new StatusEmails($this->app->db(), $settings))->enabledStatuses(),
            'canManage' => $this->can('appointments.manage'),
            'errors' => $this->pullArray('note_errors'),
        ]);
    }

    public function changeStatus(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        $appointment = $repo->find($id);
        $status = $request->input('status');
        if ($appointment === null) {
            return $this->app->errorResponse(404);
        }
        if (!in_array($status, AppointmentRepository::STATUSES, true) || $status === $appointment['status']) {
            return $this->back($this->app->adminPath('appointments/' . $id));
        }
        $user = $this->app->auth()->user();
        $repo->changeStatus($id, $status, $user['id'] ?? null);
        $this->app->audit()->record(AuditLog::APPOINTMENT_STATUS_CHANGED, $user['id'] ?? null, ['id' => $id, 'from' => $appointment['status'], 'to' => $status]);
        $this->flashToast('admin.appointments.status_changed', 'success', ['status' => $this->t('admin.statuses.' . $status)]);
        $this->sendStatusEmail($appointment, $status);
        return $this->back($this->app->adminPath('appointments/' . $id));
    }

    public function addNote(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        if ($repo->find($id) === null) {
            return $this->app->errorResponse(404);
        }
        $body = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $request->input('body')));
        if ($body === '' || mb_strlen($body) > 2000) {
            $this->app->session()->flash('note_errors', ['body' => $this->t($body === '' ? 'validation.required' : 'validation.max_length', ['max' => 2000])]);
            return $this->back($this->app->adminPath('appointments/' . $id));
        }
        $user = $this->app->auth()->user();
        $noteId = $repo->addNote($id, $user['id'] ?? null, is_string($user['name'] ?? null) ? $user['name'] : '', $body);
        $this->app->audit()->record(AuditLog::APPOINTMENT_NOTE_ADDED, $user['id'] ?? null, ['id' => $id, 'note' => $noteId]);
        $this->flashToast('admin.appointments.note_added');
        return $this->back($this->app->adminPath('appointments/' . $id));
    }

    public function deleteNote(Request $request): Response
    {
        $id = (int) $request->param('id');
        $noteId = (int) $request->param('note');
        if ($this->repo()->deleteNote($id, $noteId) > 0) {
            $this->app->audit()->record(AuditLog::APPOINTMENT_NOTE_DELETED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'note' => $noteId]);
            $this->flashToast('admin.appointments.note_deleted');
        }
        return $this->back($this->app->adminPath('appointments/' . $id));
    }

    public function markUnread(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        if ($repo->find($id) === null) {
            return $this->app->errorResponse(404);
        }
        $repo->markUnread($id);
        $this->app->audit()->record(AuditLog::APPOINTMENT_MARKED_UNREAD, $this->app->auth()->user()['id'] ?? null, ['id' => $id]);
        $this->flashToast('admin.appointments.marked_unread');
        $return = $request->input('return') === 'messages' ? 'messages' : 'appointments';
        return $this->back($this->app->adminPath($return));
    }

    public function export(Request $request): Response
    {
        $filters = self::filters($request);
        $rows = $this->repo()->export($filters);
        $columns = [
            'id' => '#',
            'created_at' => $this->t('admin.appointments.received'),
            'status' => $this->t('admin.appointments.status'),
            'name' => $this->t('admin.appointments.customer'),
            'email' => $this->t('site.form.email'),
            'phone' => $this->t('site.form.phone'),
            'car' => $this->t('admin.appointments.vehicle'),
            'gearbox_type' => $this->t('site.form.type'),
            'symptoms' => $this->t('admin.appointments.symptoms'),
            'lang_code' => $this->t('admin.appointments.language'),
            'read_at' => $this->t('admin.appointments.read'),
        ];
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->app->errorResponse(500);
        }
        fwrite($handle, "\xEF\xBB\xBF"); // BOM: Excel then reads UTF-8 correctly
        fputcsv($handle, array_values($columns), ',', '"', '');
        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($columns) as $column) {
                $value = $row[$column] ?? '';
                $line[] = $column === 'status' && is_string($value) ? $this->t('admin.statuses.' . $value) : (is_scalar($value) ? (string) $value : '');
            }
            fputcsv($handle, Csv::row($line), ',', '"', '');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        $this->app->audit()->record(AuditLog::APPOINTMENT_EXPORTED, $this->app->auth()->user()['id'] ?? null, ['rows' => count($rows), 'filters' => array_filter($filters)]);
        $name = $this->t('admin.appointments.export_name') . '-' . $this->app->clock->now()->format('Y-m-d') . '.csv';
        return (new Response($csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '', $name) . '"');
    }

    /**
     * @param array<string, mixed> $appointment
     */
    private function sendStatusEmail(array $appointment, string $status): void
    {
        $settings = $this->app->settings();
        $templates = new StatusEmails($this->app->db(), $settings);
        if (!$templates->isEnabled($status)) {
            return;
        }
        $lang = is_string($appointment['lang_code'] ?? null) ? $appointment['lang_code'] : $this->app->languages()->defaultCode();
        $usable = static fn (?array $t): bool => $t !== null && trim($t['subject']) !== '' && trim($t['body']) !== '';
        $template = $templates->template($status, $lang);
        if (!$usable($template)) {
            // No text in the customer's language: the default language rather than no email at all.
            $lang = $this->app->languages()->defaultCode();
            $template = $templates->template($status, $lang);
        }
        if ($template === null || !$usable($template)) {
            return;
        }
        if (!SmtpTransport::fromSettings($settings)->isConfigured()) {
            return;
        }
        $t = new Translator($lang, $this->app->languages()->defaultCode(), $this->app->db());
        $site = $settings->string('site.name', 'BM-Matic');
        $message = AdminMails::statusUpdate(
            (string) $appointment['email'],
            (string) $appointment['name'],
            (string) $template['subject'],
            (string) $template['body'],
            [
                'name' => (string) $appointment['name'],
                'car' => (string) $appointment['car'],
                'status' => $t->get('admin.statuses.' . $status),
                'site' => $site,
                'phone' => $settings->string('contact.phone'),
            ],
            $site,
            $settings->string('mail.to_email')
        );
        (new MailQueue($this->app->db(), $this->app->clock))->enqueue($message);
        $this->app->defer(fn () => $this->app->sendQueuedMail());
        $this->flashToast('admin.appointments.status_email_sent', 'info');
    }

    /**
     * @return array{status?: string, from?: string, to?: string, q?: string, unread?: bool, sort?: string, dir?: string}
     */
    public static function filters(Request $request): array
    {
        $filters = [];
        $status = $request->query('status');
        if (in_array($status, AppointmentRepository::STATUSES, true)) {
            $filters['status'] = $status;
        }
        foreach (['from', 'to'] as $field) {
            $value = $request->query($field);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $filters[$field] = $value;
            }
        }
        $q = trim($request->query('q'));
        if ($q !== '') {
            $filters['q'] = mb_substr($q, 0, 120);
        }
        if ($request->query('unread') === '1') {
            $filters['unread'] = true;
        }
        $sort = $request->query('sort');
        if (in_array($sort, AppointmentRepository::SORTABLE, true)) {
            $filters['sort'] = $sort;
            $filters['dir'] = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        }
        return $filters;
    }

    /** @param array<string, mixed> $filters */
    public static function queryString(array $filters): string
    {
        $pairs = [];
        foreach ($filters as $key => $value) {
            $pairs[$key] = $value === true ? '1' : (string) $value;
        }
        return $pairs === [] ? '' : '?' . http_build_query($pairs);
    }

    /**
     * One row of the list screens.
     *
     * @param array<string, mixed> $row
     * @return array{id: int, when: string, who: string, car: string, box: string, label: string, tone: string, unread: bool, excerpt: string, mailto: string}
     */
    public function row(array $row): array
    {
        $status = (string) $row['status'];
        $symptoms = (string) $row['symptoms'];
        return [
            'id' => (int) $row['id'],
            'when' => $this->app->formatDate((string) $row['created_at']),
            'who' => (string) $row['name'],
            'car' => (string) $row['car'],
            'box' => $this->t('site.form.types.' . (string) $row['gearbox_type']),
            'label' => $this->t('admin.statuses.' . $status),
            'tone' => match ($status) {
                'new' => 'new',
                'confirmed' => 'confirmed',
                'diagnosis' => 'diagnosis',
                'quoted' => 'quoted',
                'cancelled' => 'danger',
                default => 'neutral',
            },
            'unread' => $row['read_at'] === null,
            'excerpt' => mb_strimwidth(trim((string) preg_replace('/\s+/u', ' ', $symptoms)), 0, 70, '…'),
            'mailto' => self::mailto((string) $row['email'], $this->t('admin.messages.reply_subject', ['site' => $this->app->settings()->string('site.name', 'BM-Matic')])),
        ];
    }

    /** A mailto: link with a prefilled subject; both parts are encoded, so a name with & or ? cannot break out. */
    public static function mailto(string $email, string $subject): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return '#';
        }
        return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject);
    }

    private function repo(): AppointmentRepository
    {
        return new AppointmentRepository($this->app->db(), $this->app->clock);
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
