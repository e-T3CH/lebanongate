<?php

declare(strict_types=1);

namespace BMMatic\Admin;

use BMMatic\Core\Paths;
use BMMatic\Core\Props;

/**
 * Builds the admin sidebar from config/admin-menu.php for one user: role permissions, modules that exist,
 * badge counters and absolute links below the admin prefix.
 *
 * @phpstan-type MenuItem array{key: string, label: string, icon: string, href: string, badge: int|null}
 * @phpstan-type MenuSection array{section: string, items: list<MenuItem>}
 */
final class AdminMenu
{
    /** @param array<mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfigFile(?string $file = null): self
    {
        $config = require ($file ?? Paths::config('admin-menu.php'));
        if (!is_array($config)) {
            throw new \UnexpectedValueException('config/admin-menu.php must return an array');
        }
        return new self($config);
    }

    /**
     * @param (callable(string): bool)|null $routeExists null shows every item (design check)
     * @param array<string, int> $badges counter per badge key; 0 hides the badge
     * @return list<MenuSection>
     */
    public function build(string $role, string $adminPrefix, ?callable $routeExists = null, array $badges = [], ?Permissions $permissions = null): array
    {
        $permissions ??= Permissions::instance();
        $prefix = '/' . trim($adminPrefix, '/');
        $out = [];
        foreach ($this->config as $section) {
            if (!is_array($section)) {
                continue;
            }
            $items = [];
            foreach (is_array($section['items'] ?? null) ? $section['items'] : [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $key = self::str($item, 'key');
                $icon = self::str($item, 'icon');
                if ($key === '' || !Props::isIcon($icon)) {
                    throw new \UnexpectedValueException('Invalid admin menu item: ' . $key);
                }
                if (!$permissions->allows($role, self::str($item, 'permission'))) {
                    continue;
                }
                if ($routeExists !== null && !$routeExists(self::str($item, 'route'))) {
                    continue;
                }
                $path = trim(self::str($item, 'path'), '/');
                $badgeKey = self::str($item, 'badge');
                $count = $badgeKey !== '' ? ($badges[$badgeKey] ?? 0) : 0;
                $items[] = [
                    'key' => $key,
                    'label' => self::str($item, 'label'),
                    'icon' => $icon,
                    'href' => $path === '' ? $prefix : $prefix . '/' . $path,
                    'badge' => $count > 0 ? $count : null,
                ];
            }
            if ($items !== []) {
                $out[] = ['section' => self::str($section, 'section'), 'items' => $items];
            }
        }
        return $out;
    }

    /** @param array<mixed> $a */
    private static function str(array $a, string $k): string
    {
        return is_string($a[$k] ?? null) ? $a[$k] : '';
    }
}
