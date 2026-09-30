<?php

declare(strict_types=1);

namespace Gate\Core;

use Gate\I18n\Translator;
use Gate\Security\Csrf;

/**
 * PHP templates in app/Views. Templates receive their data as variables plus $view (this object).
 * Output must be escaped with e(), e_attr(), e_url() ... (helpers.php); the view never escapes implicitly.
 * Components (app/Views/components) are rendered with component(): typed, validated parameters (Components::SPECS).
 */
final class View
{
    /** @var array<string, mixed> shared with every template */
    private array $shared = [];
    /** @var array<string, string>|null content hashes written by tools/assets/build.mjs */
    private static ?array $manifest = null;

    public function __construct(
        private readonly Translator $translator,
        private readonly ?Csrf $csrf,
        private readonly string $nonce,
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function shared(string $key, mixed $default = null): mixed
    {
        return $this->shared[$key] ?? $default;
    }

    /**
     * May the signed-in admin user do this? Same answer as the route check (config/permissions.php), so a screen
     * never offers an action the request would refuse. False when nothing is shared (public pages, design check).
     */
    public function can(string $permission): bool
    {
        $permissions = $this->shared('permissions');
        $role = $this->shared('role');
        return $permissions instanceof \Gate\Admin\Permissions && is_string($role) && $permissions->allows($role, $permission);
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = $this->include($template, $data);
        if ($layout === null) {
            return $content;
        }
        return $this->include('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $name, array $data = []): string
    {
        return $this->include('partials/' . $name, $data);
    }

    /**
     * A component from app/Views/components with validated parameters.
     *
     * @param array<string, mixed> $props
     */
    public function component(string $name, array $props = []): Html
    {
        if (!isset(Components::SPECS[$name])) {
            throw new \InvalidArgumentException('Unknown component: ' . $name);
        }
        $p = Props::check($name, $props, Components::SPECS[$name]);
        // Components are inline fragments: no trailing line break (it would become a text node in inline contexts).
        return Html::trusted(rtrim($this->include('components/' . $name, ['p' => $p]), "\r\n"));
    }

    /**
     * Translated string (not escaped).
     *
     * @param array<string, string|int|float> $params
     */
    public function t(string $key, array $params = []): string
    {
        return $this->translator->get($key, $params);
    }

    public function locale(): string
    {
        return $this->translator->locale();
    }

    public function nonce(): string
    {
        return $this->nonce;
    }

    public function csrfField(): string
    {
        return $this->csrf?->field() ?? '';
    }

    public function csrfToken(): string
    {
        return $this->csrf?->token() ?? '';
    }

    /**
     * Public asset URL with a cache-busting version: the content hash from public/assets/manifest.json,
     * or the file's modification time for files the build does not know.
     */
    public function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $manifest = self::manifest();
        if (isset($manifest[$path])) {
            return '/assets/' . $path . '?v=' . $manifest[$path];
        }
        $file = Paths::publicDir('assets/' . $path);
        return '/assets/' . $path . '?v=' . (is_file($file) ? (string) filemtime($file) : '1');
    }

    /** @return array<string, string> */
    private static function manifest(): array
    {
        if (self::$manifest === null) {
            self::$manifest = [];
            $file = Paths::publicDir('assets/manifest.json');
            $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (is_string($k) && is_string($v)) {
                        self::$manifest[$k] = $v;
                    }
                }
            }
        }
        return self::$manifest;
    }

    /** @internal tests */
    public static function resetManifest(): void
    {
        self::$manifest = null;
    }

    /** @param array<string, mixed> $data */
    private function include(string $template, array $data): string
    {
        if (preg_match('#^[a-z0-9_\-/]+$#', $template) !== 1 || str_contains($template, '..')) {
            throw new \InvalidArgumentException('Invalid template name: ' . $template);
        }
        $file = Paths::app('Views/' . $template . '.php');
        if (!is_file($file)) {
            throw new \RuntimeException('Template not found: ' . $template);
        }
        $view = $this;
        $vars = $data + $this->shared;
        return (static function (string $__file, array $__vars, View $view): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })($file, $vars, $view);
    }
}
