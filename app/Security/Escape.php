<?php

declare(strict_types=1);

namespace Gate\Security;

/**
 * Context-aware output escaping. Views use the global helpers e(), e_attr(), e_js(), e_url(), e_css()
 * (app/helpers.php), which call these methods.
 */
final class Escape
{
    private const FLAGS = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5;

    /** HTML text content. */
    public static function html(mixed $value): string
    {
        return htmlspecialchars(self::stringify($value), self::FLAGS, 'UTF-8');
    }

    /** Quoted HTML attribute value. */
    public static function attr(mixed $value): string
    {
        return htmlspecialchars(self::stringify($value), self::FLAGS, 'UTF-8');
    }

    /** A JavaScript value (JSON literal), safe inside <script> blocks and event-free attributes. */
    public static function js(mixed $value): string
    {
        return (string) json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** One URL path segment or query value. */
    public static function urlComponent(mixed $value): string
    {
        return rawurlencode(self::stringify($value));
    }

    /**
     * A URL for href/src attributes: only http(s), mailto, tel, relative and fragment URLs are allowed;
     * anything else (javascript:, data:, vbscript:) becomes "#".
     */
    public static function url(mixed $value): string
    {
        $url = trim(self::stringify($value));
        $normalized = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $url));
        $scheme = preg_match('/^([a-z][a-z0-9+.-]*):/', $normalized, $m) === 1 ? $m[1] : null;
        if ($scheme !== null && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            $url = '#';
        }
        return self::attr($url);
    }

    /** A CSS custom property value (colors, lengths). Rejects characters that could break out of a declaration. */
    public static function cssValue(mixed $value): string
    {
        $v = self::stringify($value);
        return preg_match('/^[#a-zA-Z0-9 .,%()\-]{1,64}$/', $v) === 1 ? $v : '';
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }
        throw new \InvalidArgumentException('Cannot escape a value of type ' . get_debug_type($value));
    }
}
