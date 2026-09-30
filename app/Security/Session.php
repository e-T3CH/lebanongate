<?php

declare(strict_types=1);

namespace BMMatic\Security;

/** Session storage abstraction (native PHP sessions in production, an array in tests). */
interface Session
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    /** Renews the session ID and keeps the data (call on every privilege change). */
    public function regenerate(): void;

    /** Clears all data and starts a fresh session ID. */
    public function invalidate(): void;

    public function flash(string $key, mixed $value): void;

    /** Returns and removes a flashed value. */
    public function pull(string $key, mixed $default = null): mixed;

    public function id(): string;
}
