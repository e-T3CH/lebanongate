<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

return new class implements Migration {
    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {users} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `name` VARCHAR(120) NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM(\'admin\', \'editor\') NOT NULL DEFAULT \'editor\',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `totp_secret` TEXT NULL COMMENT \'encrypted at rest\',
            `totp_enabled_at` DATETIME NULL,
            `totp_last_timestep` BIGINT UNSIGNED NULL,
            `recovery_codes` TEXT NULL COMMENT \'JSON list of HMAC-SHA256 hashes\',
            `last_login_at` DATETIME NULL,
            `last_login_ip` VARBINARY(16) NULL,
            `password_changed_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {users}');
    }
};
