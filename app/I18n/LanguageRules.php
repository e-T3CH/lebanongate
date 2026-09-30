<?php

declare(strict_types=1);

namespace Gate\I18n;

/** Rules for the enabled/default language configuration. */
final class LanguageRules
{
    /** Website languages (content and public interface), in display order. */
    public const SUPPORTED = ['en', 'ar', 'fr'];

    /** Languages of the admin panel and the installer (the ones with a full admin translation). */
    public const ADMIN = ['en', 'fr'];

    /** Languages written right to left: the page gets dir="rtl" and the mirrored layout. */
    public const RTL = ['ar'];

    public static function isRtl(string $code): bool
    {
        return in_array($code, self::RTL, true);
    }

    public static function direction(string $code): string
    {
        return self::isRtl($code) ? 'rtl' : 'ltr';
    }

    /**
     * @param list<string> $knownCodes
     * @param list<string> $enabledCodes
     * @return list<string> translation keys of the violated rules
     */
    public static function validate(array $knownCodes, array $enabledCodes, string $defaultCode): array
    {
        $errors = [];
        foreach ($enabledCodes as $code) {
            if (!in_array($code, $knownCodes, true)) {
                $errors[] = 'validation.language_unknown';
                break;
            }
        }
        if ($enabledCodes === []) {
            $errors[] = 'validation.language_none_enabled';
        }
        if (!in_array($defaultCode, $knownCodes, true)) {
            $errors[] = 'validation.language_default_unknown';
        } elseif (!in_array($defaultCode, $enabledCodes, true)) {
            $errors[] = 'validation.language_default_disabled';
        }
        return $errors;
    }

    /** The language selector is hidden when fewer than two languages are enabled. */
    public static function showSelector(int $enabledCount, bool $settingOn): bool
    {
        return $settingOn && $enabledCount > 1;
    }
}
