<?php

declare(strict_types=1);

namespace Gate\Core;

/**
 * Typed component parameters. Every component declares its parameters in Components::SPECS; View::component()
 * validates the given props against that spec before the template runs. Unknown props, missing required props
 * and wrong types throw InvalidArgumentException (a programming error, never user input).
 *
 * Spec entry: 'name' => 'type' (required) or 'name' => ['type', default] (optional). Types:
 *   string, int, float, bool, list (list<mixed>), map (array<string, mixed>)
 *   html     Html (printed as is) or string (escaped)
 *   enum:a|b one of the listed strings
 *   icon     Font Awesome class pair, e.g. "fa-solid fa-check" (the subsetting build scans these literals)
 *   url      string; unsafe schemes are neutralised at output by e_url()
 *   id       HTML id / name token
 *   classes  extra CSS class names
 *   attrs    extra attributes: data-*, aria-* and a small allowlist (never event handlers or style)
 * A leading "?" makes null acceptable.
 */
final class Props
{
    private const ATTR_ALLOW = '/^(data-[a-z0-9-]+|aria-[a-z]+|role|tabindex|title|lang|dir|hreflang|rel|target|autocomplete|inputmode|form|download)$/';

    /**
     * @param array<string, mixed> $props
     * @param array<string, string|array{0: string, 1: mixed}> $spec
     * @return array<string, mixed>
     */
    public static function check(string $component, array $props, array $spec): array
    {
        foreach (array_keys($props) as $name) {
            if (!array_key_exists($name, $spec)) {
                throw new \InvalidArgumentException(sprintf('Component "%s" has no parameter "%s".', $component, $name));
            }
        }
        $out = [];
        foreach ($spec as $name => $definition) {
            [$type, $required, $default] = is_array($definition) ? [$definition[0], false, $definition[1]] : [$definition, true, null];
            if (!array_key_exists($name, $props)) {
                if ($required) {
                    throw new \InvalidArgumentException(sprintf('Component "%s" requires parameter "%s".', $component, $name));
                }
                $out[$name] = $default;
                continue;
            }
            $value = $props[$name];
            if (!self::matches($type, $value)) {
                throw new \InvalidArgumentException(sprintf('Component "%s" parameter "%s" must be %s, %s given.', $component, $name, $type, get_debug_type($value)));
            }
            $out[$name] = $value;
        }
        return $out;
    }

    public static function matches(string $type, mixed $value): bool
    {
        if (str_starts_with($type, '?')) {
            return $value === null || self::matches(substr($type, 1), $value);
        }
        if (str_starts_with($type, 'enum:')) {
            return is_string($value) && in_array($value, explode('|', substr($type, 5)), true);
        }
        return match ($type) {
            'string', 'url' => is_string($value),
            'int' => is_int($value),
            'float' => is_int($value) || is_float($value),
            'bool' => is_bool($value),
            'list' => is_array($value) && array_is_list($value),
            'map' => is_array($value),
            'html' => is_string($value) || $value instanceof Html,
            'icon' => is_string($value) && self::isIcon($value),
            'id' => is_string($value) && preg_match('/^[A-Za-z][A-Za-z0-9_:.\-\[\]]*$/', $value) === 1,
            'classes' => is_string($value) && preg_match('/^[A-Za-z0-9_\- ]*$/', $value) === 1,
            'attrs' => is_array($value) && self::validAttrs($value),
            default => throw new \LogicException('Unknown prop type ' . $type),
        };
    }

    public static function isIcon(string $value): bool
    {
        return preg_match('/^fa-(solid|regular|brands) fa-[a-z0-9-]+$/', $value) === 1;
    }

    /**
     * Renders extra attributes (leading space). true → bare attribute, false/null → omitted.
     *
     * @param array<string, string|int|bool|null> $attrs
     */
    public static function attrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if (!self::validAttrName((string) $name)) {
                throw new \InvalidArgumentException('Attribute not allowed: ' . $name);
            }
            if ($value === null || $value === false) {
                continue;
            }
            $out .= ' ' . $name . ($value === true ? '' : '="' . e_attr((string) $value) . '"');
        }
        return $out;
    }

    /** Joins class names, skipping empty ones. */
    public static function classes(?string ...$names): string
    {
        return trim(implode(' ', array_filter(array_map(static fn (?string $c): string => trim((string) $c), $names), static fn (string $c): bool => $c !== '')));
    }

    /** @param array<mixed> $attrs */
    private static function validAttrs(array $attrs): bool
    {
        foreach ($attrs as $name => $value) {
            if (!is_string($name) || !self::validAttrName($name) || !(is_scalar($value) || $value === null)) {
                return false;
            }
        }
        return true;
    }

    private static function validAttrName(string $name): bool
    {
        return preg_match(self::ATTR_ALLOW, $name) === 1;
    }
}
