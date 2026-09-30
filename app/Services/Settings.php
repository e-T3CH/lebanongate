<?php

declare(strict_types=1);

namespace BMMatic\Services;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;
use BMMatic\Security\Crypto;

/**
 * Key/value settings stored in the `settings` table, loaded once per request.
 * Types: string, int, bool, json, color. Secrets (API keys, SMTP passwords) are encrypted at rest.
 */
final class Settings
{
    /** @var array<string, array{value: ?string, type: string, is_secret: bool}>|null */
    private ?array $cache = null;

    public function __construct(private readonly Database $db, private readonly ?Crypto $crypto, private readonly Clock $clock)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->load()[$key] ?? null;
        if ($row === null || $row['value'] === null) {
            return $default;
        }
        if ($row['is_secret']) {
            try {
                return $this->crypto?->decrypt($row['value']) ?? $default;
            } catch (\RuntimeException) {
                // Encrypted with another app key (a database copied from another installation): as if not set.
                return $default;
            }
        }
        return match ($row['type']) {
            'int' => (int) $row['value'],
            'bool' => $row['value'] === '1',
            'json' => json_decode($row['value'], true),
            default => $row['value'],
        };
    }

    public function string(string $key, string $default = ''): string
    {
        $v = $this->get($key, $default);
        return is_scalar($v) ? (string) $v : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->get($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->get($key, $default);
        return is_bool($v) ? $v : ($v === 1 || $v === '1');
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->load());
    }

    public function set(string $key, mixed $value, ?string $type = null, bool $secret = false): void
    {
        if (preg_match('/^[a-z0-9_]+(\.[a-z0-9_-]+)+$/', $key) !== 1) {
            throw new \InvalidArgumentException('Invalid setting key: ' . $key);
        }
        $type ??= $this->load()[$key]['type'] ?? match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_array($value) => 'json',
            default => 'string',
        };
        $stored = match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '0',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            is_scalar($value) => (string) $value,
            default => throw new \InvalidArgumentException('Unsupported setting value for ' . $key),
        };
        if ($type === 'bool' && $stored !== null) {
            $stored = in_array($stored, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
        }
        if ($secret && $stored === '') {
            // An emptied secret is "nothing stored", not an encrypted empty string (which cannot be read back).
            $stored = null;
        }
        if ($secret && $stored !== null) {
            if ($this->crypto === null) {
                throw new \RuntimeException('Cannot store a secret setting without an app key.');
            }
            $stored = $this->crypto->encrypt($stored);
        }
        $this->db->upsert('settings', [
            'key' => $key,
            'value' => $stored,
            'type' => $type,
            'is_secret' => $secret ? 1 : 0,
            'updated_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ], ['value', 'type', 'is_secret', 'updated_at']);
        $this->cache = null;
    }

    /**
     * Inserts settings that do not exist yet (used by seeders; never overwrites owner edits).
     *
     * @param array<string, array{0: mixed, 1: string}> $defaults key => [value, type]
     */
    public function seedDefaults(array $defaults): int
    {
        $existing = $this->load();
        $added = 0;
        foreach ($defaults as $key => [$value, $type]) {
            if (!array_key_exists($key, $existing)) {
                $this->set($key, $value, $type);
                $added++;
            }
        }
        return $added;
    }

    /** @return array<string, mixed> all settings with the given prefix (secrets excluded) */
    public function group(string $prefix): array
    {
        $out = [];
        foreach ($this->load() as $key => $row) {
            if (str_starts_with($key, $prefix . '.') && !$row['is_secret']) {
                $out[$key] = $this->get($key);
            }
        }
        return $out;
    }

    public function refresh(): void
    {
        $this->cache = null;
    }

    /** @return array<string, array{value: ?string, type: string, is_secret: bool}> */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $this->cache = [];
        foreach ($this->db->select('settings') as $row) {
            $this->cache[(string) $row['key']] = [
                'value' => $row['value'] === null ? null : (string) $row['value'],
                'type' => (string) $row['type'],
                'is_secret' => (int) $row['is_secret'] === 1,
            ];
        }
        return $this->cache;
    }
}
