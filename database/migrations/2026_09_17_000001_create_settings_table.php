<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

return new class implements Migration {
    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {settings} (
            `key` VARCHAR(120) NOT NULL,
            `value` MEDIUMTEXT NULL,
            `type` VARCHAR(16) NOT NULL DEFAULT \'string\',
            `is_secret` TINYINT(1) NOT NULL DEFAULT 0,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {settings}');
    }
};
