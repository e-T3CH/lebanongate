<?php

declare(strict_types=1);

use Gate\Security\Escape;

if (!function_exists('e')) {
    /** Escape for HTML text. */
    function e(mixed $value): string
    {
        return Escape::html($value);
    }
}

if (!function_exists('e_attr')) {
    /** Escape for a quoted HTML attribute. */
    function e_attr(mixed $value): string
    {
        return Escape::attr($value);
    }
}

if (!function_exists('e_js')) {
    /** Encode as a JavaScript literal. */
    function e_js(mixed $value): string
    {
        return Escape::js($value);
    }
}

if (!function_exists('e_url')) {
    /** Escape a URL for href/src (blocks javascript:, data:, ...). */
    function e_url(mixed $value): string
    {
        // Root-relative paths get the installation folder (Url::to); absolute URLs pass unchanged.
        return Escape::url(is_string($value) ? \Gate\Core\Url::to($value) : $value);
    }
}

if (!function_exists('e_css')) {
    /** Validate a CSS custom property value (empty string when unsafe). */
    function e_css(mixed $value): string
    {
        return Escape::cssValue($value);
    }
}

if (!function_exists('svg_icon')) {
    /**
     * An icon of the public site's SVG sprite (app/Views/site/parts/sprite.php), decorative (aria-hidden).
     * Names are fixed identifiers from the templates, never user input.
     */
    function svg_icon(string $name, string $class = 'ic'): string
    {
        $name = preg_replace('/[^a-z0-9-]/', '', $name) ?? '';
        return '<svg class="' . Escape::attr($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"/></svg>';
    }
}
