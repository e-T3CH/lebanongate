<?php

declare(strict_types=1);

namespace BMMatic\Database\Seeders;

use BMMatic\Core\Database;

final class LanguagesSeeder
{
    /** code => [name, native name, sort order] */
    private const LANGUAGES = [
        'en' => ['English', 'English', 1],
        'fr' => ['French', 'Français', 2],
        'nl' => ['Dutch', 'Nederlands', 3],
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return int languages added */
    public function run(): int
    {
        $added = 0;
        $hasDefault = (int) $this->db->scalar('SELECT COUNT(*) FROM {languages} WHERE `is_default` = 1') > 0;
        foreach (self::LANGUAGES as $code => [$name, $native, $order]) {
            if ($this->db->first('languages', ['code' => $code]) !== null) {
                continue;
            }
            $this->db->insert('languages', [
                'code' => $code,
                'name' => $name,
                'native_name' => $native,
                'is_enabled' => 1,
                'is_default' => !$hasDefault && $code === 'en' ? 1 : 0,
                'sort_order' => $order,
            ]);
            $added++;
        }
        return $added;
    }
}
