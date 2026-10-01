<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Admin\AdminMenu;
use Gate\Admin\Permissions;
use Gate\Http\Controllers\Controller;
use Gate\Http\Flash;
use Gate\Http\Response;
use Gate\Repositories\MessageRepository;
use Gate\Services\MediaLibrary;

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
            'siteName' => $this->app->settings()->string('site.name', 'GATE Lebanon'),
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
     * Sidebar badges: unread messages in the inbox.
     *
     * @return array<string, int>
     */
    private function badges(): array
    {
        return ['messages.unread' => (new MessageRepository($this->app->db(), $this->app->clock))->countUnread()];
    }

    /**
     * Options for a media picker (a select of the library): images or PDF documents, newest first, with an empty
     * choice first.
     *
     * @return list<array{value: string, label: string}>
     */
    protected function mediaOptions(string $kind, string $emptyLabel): array
    {
        $options = [['value' => '', 'label' => $emptyLabel]];
        $urls = [];
        foreach ((new MediaLibrary($this->app->db(), $this->app->clock))->all([], '', $kind) as $item) {
            $size = $kind === 'image' && $item['width'] > 0 ? ' · ' . $item['width'] . '×' . $item['height'] : '';
            $options[] = ['value' => (string) $item['id'], 'label' => $item['original_name'] . $size];
            if ($kind === 'image') {
                $urls[(string) $item['id']] = \Gate\Core\Url::to($item['url']);
            }
        }
        // The layout prints these for admin-gate.js, which shows a thumbnail next to image pickers.
        $shared = $this->app->view()->shared('mediaUrls', []);
        $this->app->view()->share('mediaUrls', (is_array($shared) ? $shared : []) + $urls);
        return $options;
    }

    /** A media id from a picker, when it exists and is of the expected kind; else null. */
    protected function mediaId(string $value, string $kind): ?int
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            return null;
        }
        $row = $this->app->db()->first('media', ['id' => (int) $value], ['id', 'kind']);
        return $row !== null && $row['kind'] === $kind ? (int) $row['id'] : null;
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
