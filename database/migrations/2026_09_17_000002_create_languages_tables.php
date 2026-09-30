<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

return new class implements Migration {
    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {languages} (
            `code` CHAR(2) NOT NULL,
            `name` VARCHAR(60) NOT NULL,
            `native_name` VARCHAR(60) NOT NULL,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $db->run('CREATE TABLE IF NOT EXISTS {ui_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `lang_code` CHAR(2) NOT NULL,
            `key` VARCHAR(190) NOT NULL,
            `value` TEXT NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ui_translations_lang_key_unique` (`lang_code`, `key`),
            CONSTRAINT `ui_translations_lang_fk` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {ui_translations}');
        $db->run('DROP TABLE IF EXISTS {languages}');
    }
};
