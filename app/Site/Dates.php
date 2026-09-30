<?php

declare(strict_types=1);

namespace Gate\Site;

/**
 * Dates on the public website in the page language, without depending on the intl extension (not every shared host
 * has it). Arabic uses the month names used in Lebanon (كانون الثاني, شباط …) and Western digits, as Lebanese media do.
 */
final class Dates
{
    private const MONTHS = [
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'ar' => ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'],
        'fr' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
    ];

    private const SHORT = [
        'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'ar' => ['ك٢', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'ت١', 'ت٢', 'ك١'],
        'fr' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
    ];

    /** "15 September 2026" / "15 أيلول 2026" / "15 septembre 2026"; '' for an empty or invalid date. */
    public static function long(string $date, string $lang): string
    {
        $parts = self::parts($date);
        if ($parts === null) {
            return '';
        }
        [$y, $m, $d] = $parts;
        return $d . ' ' . (self::MONTHS[$lang] ?? self::MONTHS['en'])[$m - 1] . ' ' . $y;
    }

    /** "September 2026" (project periods). */
    public static function month(string $date, string $lang): string
    {
        $parts = self::parts($date);
        if ($parts === null) {
            return '';
        }
        return (self::MONTHS[$lang] ?? self::MONTHS['en'])[$parts[1] - 1] . ' ' . $parts[0];
    }

    /** Day and short month for the date badge on cards: ['15', 'Sep']. */
    public static function badge(string $date, string $lang): array
    {
        $parts = self::parts($date);
        if ($parts === null) {
            return ['', ''];
        }
        return [(string) $parts[2], (self::SHORT[$lang] ?? self::SHORT['en'])[$parts[1] - 1]];
    }

    public static function year(string $date): string
    {
        $parts = self::parts($date);
        return $parts === null ? '' : (string) $parts[0];
    }

    /** @return array{0: int, 1: int, 2: int}|null year, month, day */
    private static function parts(string $date): ?array
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $m) !== 1) {
            return null;
        }
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];
        return checkdate($mo, $d, $y) ? [$y, $mo, $d] : null;
    }
}
