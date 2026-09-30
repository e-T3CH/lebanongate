<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Admin\AdminMenu;
use BMMatic\Admin\Permissions;
use BMMatic\Http\Controllers\Controller;
use BMMatic\Http\Flash;
use BMMatic\Http\Response;
use BMMatic\Repositories\AppointmentRepository;

/** Shared data for admin screens (signed-in user, sidebar menu, admin paths, one-time toast). */
abstract class AdminController extends Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function adminView(string $template, string $active, string $title, string $subtitle, array $data = []): Response
    {
        $user = $this->app->auth()->user();
        $router = $this->app->router();
        $role = is_string($user['role'] ?? null) ? $user['role'] : 'admin';
        $view = $this->app->view();
        $view->share('permissions', Permissions::instance());
        $view->share('role', $role);
        $menu = AdminMenu::fromConfigFile()->build($role, $this->app->adminPath(), $router->has(...), $this->badges());
        return $this->view($template, $data + [
            'user' => $user,
            'active' => $active,
            'menu' => $menu,
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'adminPath' => $this->app->adminPath(),
            'siteName' => $this->app->settings()->string('site.name', 'BM-Matic'),
            'toast' => $this->pullToast(),
        ], 'admin');
    }

    /** May the signed-in user do this? Same source as the route flags and the views ($view->can()). */
    protected function can(string $permission): bool
    {
        $role = $this->app->auth()->user()['role'] ?? null;
        return is_string($role) && Permissions::instance()->allows($role, $permission);
    }

    /**
     * Sidebar badges: new appointment requests and unread messages (the same records, counted two ways).
     *
     * @return array<string, int>
     */
    private function badges(): array
    {
        $appointments = new AppointmentRepository($this->app->db(), $this->app->clock);
        return ['appointments.new' => $appointments->countByStatus('new'), 'messages.unread' => $appointments->countUnread()];
    }

    /** @param array<string, string|int|float> $params */
    protected function flashToast(string $key, string $type = 'success', array $params = []): void
    {
        Flash::toast($this->app->session(), $type, $key, $params);
    }

    /** @return array{type: string, message: string}|null */
    private function pullToast(): ?array
    {
        $toast = Flash::pullToast($this->app->session());
        return $toast !== null ? ['type' => $toast['type'], 'message' => $this->t($toast['key'], $toast['params'])] : null;
    }
}
