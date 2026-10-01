<?php

declare(strict_types=1);

namespace Gate\Core;

/** Absolute filesystem locations. The root can be overridden (tests use a temporary copy of storage/config). */
final class Paths
{
    private static ?string $root = null;
    private static ?string $storage = null;
    private static ?string $config = null;
    private static ?string $public = null;

    public static function root(string $path = ''): string
    {
        self::$root ??= dirname(__DIR__, 2);
        return self::join(self::$root, $path);
    }

    /** storage/ (override with the GATE_STORAGE_DIR environment variable, e.g. for tests or a staging copy) */
    public static function storage(string $path = ''): string
    {
        return self::join(self::$storage ?? self::env('GATE_STORAGE_DIR') ?? self::root('storage'), $path);
    }

    /**
     * config/ (override with GATE_CONFIG_DIR). Only the installer-written config.local.php lives in the override;
     * shipped files (app.php, admin-menu.php) are always read from the project config/ directory.
     */
    public static function config(string $path = ''): string
    {
        if ($path !== '' && $path !== 'config.local.php') {
            return self::join(self::root('config'), $path);
        }
        return self::join(self::$config ?? self::env('GATE_CONFIG_DIR') ?? self::root('config'), $path);
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);
        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function app(string $path = ''): string
    {
        return self::join(self::root('app'), $path);
    }

    public static function lang(string $path = ''): string
    {
        return self::join(self::root('lang'), $path);
    }

    /** The web root: /public (standard layout) or public_html (split layout), set by public/index.php. */
    public static function publicDir(string $path = ''): string
    {
        self::$public ??= self::discoverPublic();
        return self::join(self::$public, $path);
    }

    /**
     * The web root when public/index.php did not set it (the console): <app>/public in the standard layout; in the
     * split layout the sibling folder whose paths.php names this application folder (public_html, www, htdocs…).
     */
    private static function discoverPublic(): string
    {
        $standard = self::root('public');
        if (is_file($standard . DIRECTORY_SEPARATOR . 'index.php')) {
            return $standard;
        }
        $appDir = basename(self::root());
        foreach (glob(dirname(self::root()) . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'paths.php') ?: [] as $file) {
            $deploy = @include $file;
            if (is_array($deploy) && ($deploy['layout'] ?? '') === 'split' && ($deploy['app_dir'] ?? '') === $appDir) {
                return dirname($file);
            }
        }
        return $standard;
    }

    public static function useRoot(?string $dir): void
    {
        self::$root = $dir === null ? null : rtrim($dir, '/\\');
    }

    public static function usePublic(?string $dir): void
    {
        self::$public = $dir === null ? null : rtrim($dir, '/\\');
    }

    /**
     * Resolves the application root from the web root and the deploy layout (public/paths.php):
     *  - standard: document root = <app>/public          → app root = parent of the web root
     *  - split:    document root = public_html, app next to it in a sibling folder named $appDir
     */
    public static function appRootFor(string $webRoot, string $layout, string $appDir): string
    {
        $parent = dirname(rtrim($webRoot, '/\\'));
        return match ($layout) {
            'standard' => $parent,
            'split' => self::normalize($parent . DIRECTORY_SEPARATOR . self::safeDirName($appDir)),
            default => throw new \InvalidArgumentException('Unknown deploy layout: ' . $layout),
        };
    }

    /** Resolves '..' segments ('/x/public_html/../gate-app' becomes '/x/gate-app'), also for a folder not created yet. */
    private static function normalize(string $path): string
    {
        $real = realpath($path);
        if ($real !== false) {
            return $real;
        }
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '..') {
                array_pop($parts);
            } elseif ($part !== '.' && ($part !== '' || $parts === [])) {
                $parts[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private static function safeDirName(string $name): string
    {
        // A folder name ('gate-app'), optionally a level or more up ('../gate-app') for a web folder that sits inside
        // public_html (https://example.org/gate/) while the application stays outside the web root.
        $last = basename(str_replace('\\', '/', $name));
        if (preg_match('#^(\.\./){0,5}[A-Za-z0-9._-]{1,64}$#', $name) !== 1 || $last === '.' || $last === '..') {
            throw new \InvalidArgumentException('Invalid application folder name: ' . $name);
        }
        return $name;
    }

    public static function useStorage(?string $dir): void
    {
        self::$storage = $dir;
    }

    public static function useConfig(?string $dir): void
    {
        self::$config = $dir;
    }

    private static function join(string $base, string $path): string
    {
        return $path === '' ? $base : rtrim($base, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), '/\\');
    }
}
