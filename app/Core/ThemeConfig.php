<?php

declare(strict_types=1);

namespace BMMatic\Core;

use BMMatic\Services\Settings;

/**
 * theme-config: turns the Appearance settings (colors, radii, motion) into CSS custom properties, printed in a
 * nonce'd <style> in <head> (never inline style attributes, so the CSP needs no 'unsafe-inline').
 * The built CSS contains the defaults, so only changed tokens are printed; a missing or invalid setting never breaks
 * the design: invalid values fall back to the default.
 */
final class ThemeConfig
{
    public const MOTION_MODES = ['standard', 'subtle', 'off'];

    /**
     * Only the tokens that differ from the defaults built into the CSS, plus the scaled durations when the motion mode
     * is "subtle". Empty when nothing is customised (no <style> needed).
     */
    public static function css(?Settings $settings, string $motionMode = 'standard'): string
    {
        $get = static fn (string $key, string $default): string => $settings !== null ? $settings->string($key, $default) : $default;

        $root = [];
        $add = static function (string $name, string $value, string $default) use (&$root): void {
            if ($value !== $default) {
                $root[] = '--' . $name . ':' . $value;
            }
        };
        foreach (ThemeDefaults::COLORS as $name => $default) {
            $add($name, self::valid($get('theme.' . $name, $default), 'color', $default), $default);
        }
        foreach (ThemeDefaults::RADII as $name => $default) {
            $add($name, self::valid($get('theme.' . $name, $default), 'length', $default), $default);
        }
        $subtle = [];
        foreach (ThemeDefaults::MOTION as $name => [$default, $subtleDefault]) {
            $type = str_ends_with($default, 'ms') ? 'duration' : 'length';
            $value = self::valid($get('theme.' . $name, $default), $type, $default);
            $add($name, $value, $default);
            if ($subtleDefault !== null) {
                $subtle[] = '--' . $name . ':' . self::scale($value, $default, $subtleDefault);
            }
        }
        foreach (ThemeDefaults::EASINGS as $name => $default) {
            $add($name, self::valid($get('theme.' . $name, $default), 'easing', $default), $default);
        }
        return ($root !== [] ? ':root{' . implode(';', $root) . '}' : '')
            . ($motionMode === 'subtle' ? ':root[data-motion="subtle"]{' . implode(';', $subtle) . '}' : '');
    }

    /** Motion setting → value of <html data-motion>: standard (as approved), subtle, or off (like reduced motion). */
    public static function motionMode(?Settings $settings): string
    {
        if ($settings === null) {
            return 'standard';
        }
        if (!$settings->bool('appearance.motion_enabled', true)) {
            return 'off';
        }
        return $settings->string('appearance.motion_intensity', 'standard') === 'subtle' ? 'subtle' : 'standard';
    }

    public static function isColor(string $value): bool
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1
            || preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d{1,3})\s*)?\)$/', $value) === 1;
    }

    public static function valid(string $value, string $type, string $default): string
    {
        $ok = match ($type) {
            'color' => self::isColor($value),
            'length' => preg_match('/^\d{1,3}(?:\.\d{1,2})?px$/', $value) === 1,
            'duration' => preg_match('/^\d{1,5}ms$/', $value) === 1,
            'easing' => preg_match('/^(?:ease|linear|ease-in|ease-out|ease-in-out|cubic-bezier\(\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*\))$/', $value) === 1,
            default => false,
        };
        return $ok ? $value : $default;
    }

    /** The subtle value keeps the approved ratio to the standard value (e.g. 700ms → 500ms). */
    private static function scale(string $value, string $default, string $subtleDefault): string
    {
        $unit = str_ends_with($default, 'ms') ? 'ms' : 'px';
        $base = (float) $default;
        $result = $base > 0 ? (float) $value * (float) $subtleDefault / $base : (float) $subtleDefault;
        return (string) round($result) . $unit;
    }
}
