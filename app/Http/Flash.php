<?php

declare(strict_types=1);

namespace Gate\Http;

use Gate\Security\Session;

/**
 * One-time toast after a redirect (Post/Redirect/Get). The layouts render it with the toast component;
 * AJAX responses use the JavaScript API BM.toast(type, message) instead.
 */
final class Flash
{
    private const KEY = 'toast';
    public const TYPES = ['success', 'error', 'info', 'warning'];

    /** @param array<string, string|int|float> $params translation parameters */
    public static function toast(Session $session, string $type, string $messageKey, array $params = []): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Unknown toast type: ' . $type);
        }
        $session->flash(self::KEY, ['type' => $type, 'key' => $messageKey, 'params' => $params]);
    }

    /** @return array{type: string, key: string, params: array<string, string|int|float>}|null */
    public static function pullToast(Session $session): ?array
    {
        $value = $session->pull(self::KEY);
        if (!is_array($value) || !is_string($value['key'] ?? null)) {
            return null;
        }
        $type = is_string($value['type'] ?? null) && in_array($value['type'], self::TYPES, true) ? $value['type'] : 'success';
        $params = [];
        foreach (is_array($value['params'] ?? null) ? $value['params'] : [] as $k => $v) {
            if (is_string($k) && (is_string($v) || is_int($v) || is_float($v))) {
                $params[$k] = $v;
            }
        }
        return ['type' => $type, 'key' => $value['key'], 'params' => $params];
    }
}
