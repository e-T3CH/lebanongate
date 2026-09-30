<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

/**
 * "Forgot your password?" (hosts without a command line, such as one.com, have no other way back in).
 * One row per link: only an HMAC of the token is stored, a link works once and for one hour.
 */
return new class implements Migration {
    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {password_resets} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `token_hash` CHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `used_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `password_resets_token_unique` (`token_hash`),
            KEY `password_resets_user_index` (`user_id`, `created_at`),
            CONSTRAINT `password_resets_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {password_resets}');
    }
};
