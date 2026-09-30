<?php

declare(strict_types=1);

namespace Gate\Support;

/**
 * CSV cells that are safe to open in a spreadsheet. Text typed by a visitor ("=HYPERLINK(…)", "+cmd|…") would
 * otherwise run as a formula when the workshop opens an export in Excel or LibreOffice; such cells get a leading
 * apostrophe, which spreadsheets show as plain text (OWASP "CSV injection").
 */
final class Csv
{
    public static function cell(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }

    /**
     * @param list<string> $cells
     * @return list<string>
     */
    public static function row(array $cells): array
    {
        return array_map(self::cell(...), $cells);
    }
}
