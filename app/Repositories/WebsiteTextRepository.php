<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\I18n\Translator;

/**
 * Website texts: the fixed wording of the public site (site.* strings: buttons, labels, the footer blurb, the
 * newsletter box, form messages, visitor emails) as the team edits it under Content → Website texts.
 *
 * The strings live in ui_translations, filled from lang/{code}/site.php. A text changed here is marked is_custom, so
 * updates (TranslationsSeeder) keep it; Reset puts the file text back and clears the mark.
 *
 * @phpstan-type TextRow array{key: string, category: string, value: string, original: string, reference: string, custom: bool, long: bool}
 */
final class WebsiteTextRepository
{
    private const PREFIX = 'site.';

    /** Category => the site.* groups it holds, in the order the screen lists them. Unknown groups go to "other". */
    public const CATEGORIES = [
        'header' => ['header', 'nav', 'language', 'social', 'skip', 'to_top', 'close', 'home_link', 'breadcrumbs'],
        'home' => ['hero', 'about', 'stats', 'projects', 'news', 'map', 'partners', 'cta', 'expertise'],
        'lists' => ['read_more', 'list', 'filters', 'pagination', 'entries', 'status', 'kinds', 'regions', 'places', 'lightbox'],
        'contact' => ['contact', 'form'],
        'footer' => ['footer', 'newsletter', 'consent'],
        'emails' => ['mail'],
        'errors' => ['errors'],
        'other' => [],
    ];

    /** Texts longer than this get a text area instead of a single line. */
    private const LONG = 90;

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * The texts of one language, with the file text (for Reset) and the default language's text as a reference.
     *
     * @return list<TextRow>
     */
    public function rows(string $lang, string $referenceLang, string $category = '', string $search = ''): array
    {
        $files = new Translator($referenceLang, $referenceLang, null);
        $master = $this->siteStrings($files->fileStrings($referenceLang));
        $original = $this->siteStrings($files->fileStrings($lang));
        $stored = $this->stored($lang);
        $reference = $this->stored($referenceLang);
        $search = mb_strtolower(trim($search));
        $out = [];
        foreach (array_keys($master) as $key) {
            $cat = self::category($key);
            if ($category !== '' && $cat !== $category) {
                continue;
            }
            $value = $stored[$key]['value'] ?? $original[$key] ?? '';
            $ref = $reference[$key]['value'] ?? $master[$key];
            if ($search !== '' && !str_contains(mb_strtolower($key . ' ' . $value . ' ' . $ref), $search)) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'category' => $cat,
                'value' => $value,
                'original' => $original[$key] ?? '',
                'reference' => $ref,
                'custom' => ($stored[$key]['custom'] ?? false) === true,
                'long' => mb_strlen($value) > self::LONG || mb_strlen($ref) > self::LONG,
            ];
        }
        usort($out, static fn (array $a, array $b): int => array_search($a['category'], array_keys(self::CATEGORIES), true) <=> array_search($b['category'], array_keys(self::CATEGORIES), true));
        return $out;
    }

    /**
     * Saves the changed texts of one language. A text equal to the file text is stored unmarked, so it follows
     * future updates again.
     *
     * @param array<string, string> $values key => text
     * @return array{saved: int, errors: array<string, string>} errors: key => reason (required, placeholders, markup)
     */
    public function save(string $lang, string $referenceLang, array $values): array
    {
        $files = new Translator($referenceLang, $referenceLang, null);
        $master = $this->siteStrings($files->fileStrings($referenceLang));
        $original = $this->siteStrings($files->fileStrings($lang));
        $stored = $this->stored($lang);
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $saved = 0;
        $errors = [];
        foreach ($values as $key => $value) {
            if (!isset($master[$key])) {
                continue;
            }
            $value = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value));
            $current = $stored[$key]['value'] ?? $original[$key] ?? '';
            if ($value === $current) {
                continue;
            }
            $error = self::check($value, $original[$key] ?? $master[$key]);
            if ($error !== null) {
                $errors[$key] = $error;
                continue;
            }
            $custom = $value !== ($original[$key] ?? null) ? 1 : 0;
            if (isset($stored[$key])) {
                $this->db->update('ui_translations', ['value' => $value, 'is_custom' => $custom, 'updated_at' => $now], ['lang_code' => $lang, 'key' => $key]);
            } else {
                $this->db->insert('ui_translations', ['lang_code' => $lang, 'key' => $key, 'value' => $value, 'is_custom' => $custom, 'updated_at' => $now]);
            }
            $saved++;
        }
        return ['saved' => $saved, 'errors' => $errors];
    }

    /** Puts the file text back for one key and language. False when the key is not a website text. */
    public function reset(string $lang, string $referenceLang, string $key): bool
    {
        $files = new Translator($referenceLang, $referenceLang, null);
        if (!isset($this->siteStrings($files->fileStrings($referenceLang))[$key])) {
            return false;
        }
        $original = $this->siteStrings($files->fileStrings($lang))[$key] ?? null;
        if ($original === null) {
            $this->db->delete('ui_translations', ['lang_code' => $lang, 'key' => $key]);
            return true;
        }
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        if ($this->db->first('ui_translations', ['lang_code' => $lang, 'key' => $key], ['id']) !== null) {
            $this->db->update('ui_translations', ['value' => $original, 'is_custom' => 0, 'updated_at' => $now], ['lang_code' => $lang, 'key' => $key]);
        } else {
            $this->db->insert('ui_translations', ['lang_code' => $lang, 'key' => $key, 'value' => $original, 'is_custom' => 0, 'updated_at' => $now]);
        }
        return true;
    }

    /** @return array<string, int> category => number of texts the team changed in this language */
    public function customCounts(string $lang): array
    {
        $out = array_fill_keys(array_keys(self::CATEGORIES), 0);
        foreach ($this->stored($lang) as $key => $row) {
            if ($row['custom']) {
                $out[self::category($key)]++;
            }
        }
        return $out;
    }

    /** Why a text cannot be saved: 'required', 'placeholders' (a :name of the original is missing) or 'markup'. */
    public static function check(string $value, string $original): ?string
    {
        if ($value === '') {
            return 'required';
        }
        if (preg_match('/[<>]/', $value) === 1) {
            return 'markup';
        }
        foreach (self::placeholders($original) as $placeholder) {
            if (preg_match('/' . preg_quote($placeholder, '/') . '(?![a-z_])/', $value) !== 1) {
                return 'placeholders';
            }
        }
        return null;
    }

    /** @return list<string> the :name placeholders of a text, such as :year */
    public static function placeholders(string $text): array
    {
        preg_match_all('/:[a-z_]+/', $text, $m);
        return array_values(array_unique($m[0]));
    }

    public static function category(string $key): string
    {
        $group = explode('.', substr($key, strlen(self::PREFIX)))[0];
        foreach (self::CATEGORIES as $category => $groups) {
            if (in_array($group, $groups, true)) {
                return $category;
            }
        }
        return 'other';
    }

    /**
     * @param array<string, string> $strings
     * @return array<string, string>
     */
    private function siteStrings(array $strings): array
    {
        return array_filter($strings, static fn (string $key): bool => str_starts_with($key, self::PREFIX), ARRAY_FILTER_USE_KEY);
    }

    /** @return array<string, array{value: string, custom: bool}> */
    private function stored(string $lang): array
    {
        $out = [];
        foreach ($this->db->all("SELECT `key`, `value`, `is_custom` FROM {ui_translations} WHERE `lang_code` = :l AND `key` LIKE 'site.%'", ['l' => $lang]) as $row) {
            $out[(string) $row['key']] = ['value' => (string) $row['value'], 'custom' => (int) $row['is_custom'] === 1];
        }
        return $out;
    }
}
