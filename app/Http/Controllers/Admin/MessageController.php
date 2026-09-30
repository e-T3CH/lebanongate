<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\MessageRepository;
use Gate\Services\AuditLog;
use Gate\Support\Csv;

/**
 * Messages: the contact form inbox. Tabs for the inbox, unread messages and the archive; a search and a subject
 * filter; the message itself with internal notes; mark unread, archive, delete and a CSV export. Replying opens the
 * mail program of the user (reply-to is the sender); the panel never sends a free-text email itself.
 *
 * @phpstan-import-type Filters from MessageRepository
 */
final class MessageController extends AdminController
{
    public function index(Request $request): Response
    {
        $repo = $this->repo();
        $filters = self::filters($request);
        $result = $repo->paginate($filters, max(1, (int) $request->query('page', '1')));
        return $this->adminView('admin/messages', 'messages', $this->t('admin.messages.title'), $this->t('admin.messages.subtitle'), [
            'result' => $result,
            'rows' => array_map($this->row(...), $result['rows']),
            'filters' => $filters,
            'box' => $filters['box'] ?? 'inbox',
            'unreadCount' => $repo->countUnread(),
            'query' => self::queryString($filters),
            'subjects' => MessageRepository::SUBJECTS,
            'canManage' => $this->can('messages.manage'),
        ]);
    }

    public function show(Request $request): Response
    {
        $repo = $this->repo();
        $id = (int) $request->param('id');
        $message = $repo->find($id);
        if ($message === null) {
            return $this->app->errorResponse(404);
        }
        $user = $this->app->auth()->user();
        $repo->markRead($id, $user['id'] ?? null);
        $notes = [];
        foreach ($repo->notes($id) as $note) {
            $notes[] = [
                'id' => (int) $note['id'],
                'author' => (string) $note['author_name'],
                'when' => $this->app->formatDate((string) $note['created_at']),
                'body' => (string) $note['body'],
            ];
        }
        $siteName = $this->app->settings()->string('site.name', 'GATE Lebanon');
        return $this->adminView('admin/message', 'messages', $this->t('admin.messages.detail_title', ['id' => $id]), $this->t('admin.messages.detail_subtitle', ['name' => (string) $message['name']]), [
            'message' => [
                'id' => $id,
                'name' => (string) $message['name'],
                'email' => (string) $message['email'],
                'phone' => (string) $message['phone'],
                'organisation' => (string) $message['organisation'],
                'subject' => $this->t('site.form.subjects.' . (string) $message['subject']),
                'text' => (string) $message['message'],
                'lang' => strtoupper((string) $message['lang_code']),
                'received' => $this->app->formatDate((string) $message['created_at']),
                'consent' => $this->app->formatDate((string) $message['consent_at']),
                'archived' => $message['archived_at'] !== null,
                'mailto' => self::mailto((string) $message['email'], $this->t('admin.messages.reply_subject', ['site' => $siteName])),
                'telHref' => (string) $message['phone'] !== '' ? 'tel:' . (string) preg_replace('/[^0-9+]/', '', (string) $message['phone']) : null,
            ],
            'notes' => $notes,
            'canManage' => $this->can('messages.manage'),
            'errors' => $this->pullArray('note_errors'),
        ]);
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
            return $this->back($this->app->adminPath('messages/' . $id));
        }
        $user = $this->app->auth()->user();
        $noteId = $repo->addNote($id, $user['id'] ?? null, is_string($user['name'] ?? null) ? $user['name'] : '', $body);
        $this->app->audit()->record(AuditLog::MESSAGE_NOTE_ADDED, $user['id'] ?? null, ['id' => $id, 'note' => $noteId]);
        $this->flashToast('admin.messages.note_added');
        return $this->back($this->app->adminPath('messages/' . $id));
    }

    public function deleteNote(Request $request): Response
    {
        $id = (int) $request->param('id');
        $noteId = (int) $request->param('note');
        if ($this->repo()->deleteNote($id, $noteId) > 0) {
            $this->app->audit()->record(AuditLog::MESSAGE_NOTE_DELETED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'note' => $noteId]);
            $this->flashToast('admin.messages.note_deleted');
        }
        return $this->back($this->app->adminPath('messages/' . $id));
    }

    public function markUnread(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($this->repo()->find($id) === null) {
            return $this->app->errorResponse(404);
        }
        $this->repo()->markUnread($id);
        $this->app->audit()->record(AuditLog::MESSAGE_MARKED_UNREAD, $this->app->auth()->user()['id'] ?? null, ['id' => $id]);
        $this->flashToast('admin.messages.marked_unread');
        return $this->back($this->app->adminPath('messages'));
    }

    public function archive(Request $request): Response
    {
        $id = (int) $request->param('id');
        $message = $this->repo()->find($id);
        if ($message === null) {
            return $this->app->errorResponse(404);
        }
        $archive = $message['archived_at'] === null;
        $this->repo()->setArchived($id, $archive);
        $this->app->audit()->record(AuditLog::MESSAGE_ARCHIVED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'archived' => $archive]);
        $this->flashToast($archive ? 'admin.messages.archived' : 'admin.messages.restored');
        return $this->back($this->app->adminPath('messages' . ($archive ? '' : '/' . $id)));
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($this->repo()->delete($id) > 0) {
            $this->app->audit()->record(AuditLog::MESSAGE_DELETED, $this->app->auth()->user()['id'] ?? null, ['id' => $id]);
            $this->flashToast('admin.messages.deleted');
        }
        return $this->back($this->app->adminPath('messages'));
    }

    public function export(Request $request): Response
    {
        $filters = self::filters($request);
        $rows = $this->repo()->export($filters);
        $columns = [
            'id' => '#',
            'created_at' => $this->t('admin.messages.received'),
            'name' => $this->t('site.form.name'),
            'email' => $this->t('site.form.email'),
            'phone' => $this->t('site.form.phone'),
            'organisation' => $this->t('site.form.organisation'),
            'subject' => $this->t('site.form.subject'),
            'message' => $this->t('site.form.message'),
            'lang_code' => $this->t('admin.messages.language'),
            'read_at' => $this->t('admin.messages.read'),
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
                $line[] = $column === 'subject' && is_string($value) ? $this->t('site.form.subjects.' . $value) : (is_scalar($value) ? (string) $value : '');
            }
            fputcsv($handle, Csv::row($line), ',', '"', '');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        $this->app->audit()->record(AuditLog::MESSAGE_EXPORTED, $this->app->auth()->user()['id'] ?? null, ['rows' => count($rows), 'filters' => array_filter($filters)]);
        return (new Response($csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="messages-' . $this->app->clock->now()->format('Y-m-d') . '.csv"');
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, when: string, who: string, subject: string, excerpt: string, unread: bool, archived: bool}
     */
    private function row(array $row): array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $row['message']));
        return [
            'id' => (int) $row['id'],
            'when' => $this->app->formatDate((string) $row['created_at']),
            'who' => (string) $row['name'] . ((string) $row['organisation'] !== '' ? ' · ' . (string) $row['organisation'] : ''),
            'subject' => $this->t('site.form.subjects.' . (string) $row['subject']),
            'excerpt' => mb_strimwidth($text, 0, 90, '…'),
            'unread' => $row['read_at'] === null,
            'archived' => $row['archived_at'] !== null,
        ];
    }

    /** @return Filters */
    public static function filters(Request $request): array
    {
        $filters = [];
        $box = $request->query('box', 'inbox');
        $filters['box'] = in_array($box, MessageRepository::BOXES, true) ? $box : 'inbox';
        foreach (['subject', 'from', 'to', 'q'] as $key) {
            $value = trim($request->query($key));
            if ($value !== '') {
                $filters[$key] = mb_substr($value, 0, 100);
            }
        }
        return $filters;
    }

    /** @param Filters $filters */
    public static function queryString(array $filters): string
    {
        $query = array_filter($filters, static fn ($v, $k): bool => $v !== '' && !($k === 'box' && $v === 'inbox'), ARRAY_FILTER_USE_BOTH);
        return $query === [] ? '' : '?' . http_build_query($query);
    }

    public static function mailto(string $email, string $subject): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return '#';
        }
        return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject);
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }

    private function repo(): MessageRepository
    {
        return new MessageRepository($this->app->db(), $this->app->clock);
    }
}
