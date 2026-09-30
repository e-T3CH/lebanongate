<?php

declare(strict_types=1);

namespace Gate\Database\Seeders;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Services\Settings;

/** Runs all seeders. Safe to run again: existing rows and owner edits are never overwritten. */
final class DatabaseSeeder
{
    public function __construct(private readonly Database $db, private readonly Settings $settings, private readonly Clock $clock)
    {
    }

    /**
     * @param array<string, mixed> $overrides installer values that replace defaults for new settings
     * @return array{settings: int, languages: int, translations: int, content: int}
     */
    public function run(array $overrides = []): array
    {
        $languages = (new LanguagesSeeder($this->db))->run();
        $settings = (new SettingsSeeder($this->settings))->run($overrides);
        $translations = (new TranslationsSeeder($this->db, $this->clock))->run();
        $content = (new ContentSeeder($this->db, $this->clock))->run();
        return ['settings' => $settings, 'languages' => $languages, 'translations' => $translations, 'content' => $content];
    }
}
