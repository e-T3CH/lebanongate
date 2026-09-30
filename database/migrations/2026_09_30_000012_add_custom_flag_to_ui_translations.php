<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * ui_translations.is_custom: 1 when the team changed the text under Content → Website texts. Updates copy the
 * language files into this table (TranslationsSeeder) and never overwrite a row marked custom.
 */
return new class implements Migration {
    public function up(Database $db): void
    {
        $exists = (int) $db->scalar("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'ui_translations' AND column_name = 'is_custom'");
        if ($exists === 0) {
            $db->run('ALTER TABLE {ui_translations} ADD COLUMN `is_custom` TINYINT(1) NOT NULL DEFAULT 0 AFTER `value`');
        }
    }

    public function down(Database $db): void
    {
        $exists = (int) $db->scalar("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'ui_translations' AND column_name = 'is_custom'");
        if ($exists === 1) {
            $db->run('ALTER TABLE {ui_translations} DROP COLUMN `is_custom`');
        }
    }
};
