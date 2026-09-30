<?php

declare(strict_types=1);

namespace BMMatic\I18n;

/** Rules for the enabled/default language configuration. */
final class LanguageRules
{
    public const SUPPORTED = ['en', 'fr', 'nl'];

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
