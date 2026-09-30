<?php

declare(strict_types=1);

namespace Gate\Core;

/** Read-only configuration: config/app.php merged with config/config.local.php (written by the installer). */
final class Config
{
    /** @var array<string, mixed> */
    private array $items;

    /** @param array<string, mixed> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function load(): self
    {
        /** @var array<string, mixed> $base */
        $base = require Paths::config('app.php');
        $localFile = Paths::config('config.local.php');
        if (is_file($localFile)) {
            /** @var array<string, mixed> $local */
            $local = require $localFile;
            $base = self::merge($base, $local);
        }
        // Development override (e.g. `GATE_APP_ENV=local php -S ...` for /design-check before installation).
        $env = getenv('GATE_APP_ENV');
        if (is_string($env) && in_array($env, ['local', 'production'], true) && is_array($base['app'] ?? null)) {
            $base['app']['env'] = $env;
        }
        return new self($base);
    }

    public static function localFileExists(): bool
    {
        return is_file(Paths::config('config.local.php'));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $v = $this->get($key, $default);
        return is_scalar($v) ? (string) $v : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->get($key, $default);
        return is_bool($v) ? $v : filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     * @return array<string, mixed>
     */
    private static function merge(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            if (is_array($v) && isset($a[$k]) && is_array($a[$k]) && !array_is_list($v)) {
                /** @var array<string, mixed> $left */
                $left = $a[$k];
                /** @var array<string, mixed> $v */
                $a[$k] = self::merge($left, $v);
            } else {
                $a[$k] = $v;
            }
        }
        return $a;
    }
}
