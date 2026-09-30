<?php

declare(strict_types=1);

namespace BMMatic\Admin;

use BMMatic\Core\Paths;

/**
 * Roles and permissions from config/permissions.php, asked in exactly one way:
 * routes (the `perm:` flag), the sidebar menu and the views all call allows().
 * A role holding '*' may do everything.
 */
final class Permissions
{
    private static ?self $instance = null;

    /**
     * @param array<string, string> $permissions permission => translation key
     * @param array<string, list<string>> $roles role => permissions
     */
    public function __construct(private readonly array $permissions, private readonly array $roles)
    {
    }

    public static function fromConfigFile(?string $file = null): self
    {
        $config = require ($file ?? Paths::config('permissions.php'));
        if (!is_array($config) || !is_array($config['permissions'] ?? null) || !is_array($config['roles'] ?? null)) {
            throw new \UnexpectedValueException('config/permissions.php must return permissions and roles');
        }
        $permissions = [];
        foreach ($config['permissions'] as $name => $label) {
            if (is_string($name) && is_string($label)) {
                $permissions[$name] = $label;
            }
        }
        $roles = [];
        foreach ($config['roles'] as $role => $granted) {
            if (!is_string($role) || !is_array($granted)) {
                continue;
            }
            $list = array_values(array_filter($granted, static fn ($p): bool => is_string($p) && ($p === '*' || isset($permissions[$p]))));
            /** @var list<string> $list */
            $roles[$role] = $list;
        }
        return new self($permissions, $roles);
    }

    /** Shared instance for the request (the config file is read once). */
    public static function instance(): self
    {
        return self::$instance ??= self::fromConfigFile();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function allows(?string $role, string $permission): bool
    {
        if ($role === null || !isset($this->roles[$role])) {
            return false;
        }
        return in_array('*', $this->roles[$role], true) || in_array($permission, $this->roles[$role], true);
    }

    /** @return list<string> */
    public function roles(): array
    {
        return array_keys($this->roles);
    }

    /** @return array<string, string> permission => translation key */
    public function all(): array
    {
        return $this->permissions;
    }

    /** @return list<string> the permissions of one role, resolved ('*' becomes every permission) */
    public function forRole(string $role): array
    {
        if (!isset($this->roles[$role])) {
            return [];
        }
        return in_array('*', $this->roles[$role], true) ? array_keys($this->permissions) : $this->roles[$role];
    }

    public function exists(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }
}
