<?php

declare(strict_types=1);

namespace BMMatic\I18n;

use BMMatic\Core\Database;
use BMMatic\Core\Paths;

/**
 * UI strings. Lookup order: ui_translations (editable in admin) for the language, the lang/{code}/*.php file,
 * then the same two sources for the fallback language, then the key itself.
 * Keys are "group.path" where group is the file name (admin, install, site, validation).
 * Parameters are replaced as :name (escaped by the view, not here).
 */
final class Translator
{
    /** @var array<string, array<string, string>> */
    private array $db = [];
    /** @var array<string, array<string, string>> */
    private array $files = [];

    public function __construct(private string $locale, private readonly string $fallback, private readonly ?Database $database)
    {
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /** @param array<string, string|int|float> $params */
    public function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;
        $value = $this->lookup($key, $locale) ?? ($locale !== $this->fallback ? $this->lookup($key, $this->fallback) : null) ?? $key;
        if ($params !== []) {
            uksort($params, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
            foreach ($params as $name => $replacement) {
                $value = str_replace(':' . $name, (string) $replacement, $value);
            }
        }
        return $value;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        return $this->lookup($key, $locale ?? $this->locale) !== null;
    }

    /**
     * All keys defined in the language files for a locale (used by the seeder and by translation progress).
     *
     * @return array<string, string>
     */
    public function fileStrings(string $locale): array
    {
        if (isset($this->files[$locale])) {
            return $this->files[$locale];
        }
        $strings = [];
        if (preg_match('/^[a-z]{2}$/', $locale) === 1) {
            foreach (glob(Paths::lang($locale . '/*.php')) ?: [] as $file) {
                $data = require $file;
                if (is_array($data)) {
                    self::flatten(basename($file, '.php'), $data, $strings);
                }
            }
        }
        return $this->files[$locale] = $strings;
    }

    private function lookup(string $key, string $locale): ?string
    {
        if ($this->database !== null) {
            if (!isset($this->db[$locale])) {
                $this->db[$locale] = [];
                try {
                    foreach ($this->database->select('ui_translations', ['lang_code' => $locale], ['key', 'value']) as $row) {
                        $this->db[$locale][(string) $row['key']] = (string) $row['value'];
                    }
                } catch (\PDOException) {
                    // Before installation the table does not exist; the language files are used.
                }
            }
            if (isset($this->db[$locale][$key])) {
                return $this->db[$locale][$key];
            }
        }
        return $this->fileStrings($locale)[$key] ?? null;
    }

    /**
     * @param array<mixed> $data
     * @param array<string, string> $out
     */
    private static function flatten(string $prefix, array $data, array &$out): void
    {
        foreach ($data as $k => $v) {
            $key = $prefix . '.' . $k;
            if (is_array($v)) {
                self::flatten($key, $v, $out);
            } elseif (is_scalar($v)) {
                $out[$key] = (string) $v;
            }
        }
    }
}
