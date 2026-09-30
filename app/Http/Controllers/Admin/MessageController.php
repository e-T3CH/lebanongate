<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Repositories\AppointmentRepository;

/**
 * Messages: the same requests as the Appointments screen, seen as an inbox. Unread (`read_at IS NULL`) is
 * independent of the status, so moving a request back to "New" never makes it unread again. Replying opens the
 * mail program of the user; the panel never sends a free-text email itself.
 */
final class MessageController extends AdminController
{
    public function index(Request $request): Response
    {
        $repo = new AppointmentRepository($this->app->db(), $this->app->clock);
        // The inbox defaults to unread; ?unread=0 shows everything.
        $filters = AppointmentController::filters($request);
        if ($request->query('unread', '1') !== '0') {
            $filters['unread'] = true;
        } else {
            unset($filters['unread']);
        }
        $result = $repo->paginate($filters, max(1, (int) $request->query('page', '1')));
        $appointments = new AppointmentController($this->app);
        return $this->adminView('admin/messages', 'messages', $this->t('admin.messages.title'), $this->t('admin.messages.subtitle'), [
            'result' => $result,
            'rows' => array_map($appointments->row(...), $result['rows']),
            'canManage' => $this->can('appointments.manage'),
            'filters' => $filters,
            'unreadOnly' => ($filters['unread'] ?? false) === true,
            'unreadCount' => $repo->countUnread(),
            'query' => AppointmentController::queryString($filters),
            'siteName' => $this->app->settings()->string('site.name', 'BM-Matic'),
        ]);
    }
}
