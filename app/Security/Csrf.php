<?php

declare(strict_types=1);

namespace Gate\Security;

/**
 * Synchronizer-token CSRF protection. One token per session, rotated on login/logout.
 * Every state-changing request (POST, PUT, PATCH, DELETE) must send it as the `_token` field or `X-CSRF-Token` header.
 */
final class Csrf
{
    public const FIELD = '_token';
    public const HEADER = 'X-CSRF-Token';
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }
        return $token;
    }

    public function rotate(): string
    {
        $this->session->remove(self::KEY);
        return $this->token();
    }

    public function validate(?string $submitted): bool
    {
        $token = $this->session->get(self::KEY);
        return is_string($token) && strlen($token) === 64 && is_string($submitted) && hash_equals($token, $submitted);
    }

    public static function requiresCheck(string $method): bool
    {
        return !in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /** Hidden input for forms. */
    public function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . Escape::attr($this->token()) . '">';
    }
}
