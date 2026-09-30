<?php

declare(strict_types=1);

namespace BMMatic\Security;

/** In-memory session for tests and CLI. */
final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];
    private string $id;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(24));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        $this->id = bin2hex(random_bytes(24));
    }

    public function invalidate(): void
    {
        $this->data = [];
        $this->regenerate();
    }

    public function flash(string $key, mixed $value): void
    {
        /** @var array<string, mixed> $flash */
        $flash = is_array($this->data['_flash'] ?? null) ? $this->data['_flash'] : [];
        $flash[$key] = $value;
        $this->data['_flash'] = $flash;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $flash */
        $flash = is_array($this->data['_flash'] ?? null) ? $this->data['_flash'] : [];
        $value = $flash[$key] ?? $default;
        unset($flash[$key]);
        $this->data['_flash'] = $flash;
        return $value;
    }

    public function id(): string
    {
        return $this->id;
    }
}
