<?php

declare(strict_types=1);

namespace Gate\Services;

/**
 * Display order of page sections. Unlocked sections follow sort_order; locked sections keep their place:
 * the top bar and header always come first, the footer always last. Header and footer are always shown.
 */
final class SectionOrder
{
    private const TOP = ['topbar' => 0, 'header' => 1];
    private const BOTTOM = ['footer' => 0];
    public const REQUIRED = ['header', 'footer'];
    /** Sections that carry a "01 —" style number in their label, in display order. */
    public const NUMBERED = ['hero', 'services', 'process', 'reviews', 'contact'];

    /**
     * @param list<array<string, mixed>> $sections each with type (string) and sort_order (int)
     * @return list<array<string, mixed>>
     */
    public static function sort(array $sections): array
    {
        $rank = static function (array $s): array {
            $type = is_string($s['type'] ?? null) ? $s['type'] : '';
            if (isset(self::TOP[$type])) {
                return [0, self::TOP[$type], $type];
            }
            if (isset(self::BOTTOM[$type])) {
                return [2, self::BOTTOM[$type], $type];
            }
            return [1, is_int($s['sort_order'] ?? null) ? $s['sort_order'] : 0, $type];
        };
        usort($sections, static fn (array $a, array $b): int => $rank($a) <=> $rank($b));
        return $sections;
    }

    /**
     * Sections to render: enabled (or required) ones, each with the label number ("01") of numbered sections.
     *
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    public static function visible(array $sections): array
    {
        $out = [];
        $n = 0;
        foreach (self::sort($sections) as $section) {
            $type = is_string($section['type'] ?? null) ? $section['type'] : '';
            if (($section['is_enabled'] ?? false) !== true && !in_array($type, self::REQUIRED, true)) {
                continue;
            }
            $section['number'] = in_array($type, self::NUMBERED, true) ? sprintf('%02d', ++$n) : null;
            $out[] = $section;
        }
        return $out;
    }
}
