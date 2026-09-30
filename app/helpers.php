<?php

declare(strict_types=1);

use BMMatic\Security\Escape;

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
        return Escape::url($value);
    }
}

if (!function_exists('e_css')) {
    /** Validate a CSS custom property value (empty string when unsafe). */
    function e_css(mixed $value): string
    {
        return Escape::cssValue($value);
    }
}
