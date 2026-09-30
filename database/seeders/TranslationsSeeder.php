<?php

declare(strict_types=1);

namespace Gate\Database\Seeders;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\I18n\Translator;

/**
 * Copies the lang/{code}/*.php strings into ui_translations: new keys are added, and a key whose text changed in the
 * files gets the new text, so wording fixed in a release reaches installed sites. A row the team changed under
 * Content → Website texts is marked is_custom and is never overwritten.
 */
final class TranslationsSeeder
{
    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /** @return int strings added or updated */
    public function run(): int
    {
        $translator = new Translator('en', 'en', null);
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $changed = 0;
        foreach ($this->db->select('languages', [], ['code']) as $lang) {
            $code = (string) $lang['code'];
            $existing = [];
            $custom = [];
            foreach ($this->db->select('ui_translations', ['lang_code' => $code], ['key', 'value', 'is_custom']) as $row) {
                $existing[(string) $row['key']] = (string) $row['value'];
                if ((int) $row['is_custom'] === 1) {
                    $custom[(string) $row['key']] = true;
                }
            }
            foreach ($translator->fileStrings($code) as $key => $value) {
                if (!array_key_exists($key, $existing)) {
                    $this->db->insert('ui_translations', ['lang_code' => $code, 'key' => $key, 'value' => $value, 'updated_at' => $now]);
                    $changed++;
                } elseif ($existing[$key] !== $value && !isset($custom[$key])) {
                    $this->db->update('ui_translations', ['value' => $value, 'updated_at' => $now], ['lang_code' => $code, 'key' => $key]);
                    $changed++;
                }
            }
        }
        return $changed;
    }
}
