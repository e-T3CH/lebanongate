<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

return new class implements Migration {
    public function up(Database $db): void
    {
        // Fixed-window counters for login protection and form rate limits. Keys are SHA-256 hashes (no raw IPs/emails).
        $db->run('CREATE TABLE IF NOT EXISTS {rate_limits} (
            `key` CHAR(64) NOT NULL,
            `hits` INT UNSIGNED NOT NULL DEFAULT 0,
            `reset_at` DATETIME NOT NULL,
            PRIMARY KEY (`key`),
            KEY `rate_limits_reset_at_index` (`reset_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin');

        $db->run('CREATE TABLE IF NOT EXISTS {audit_log} (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event` VARCHAR(64) NOT NULL,
            `user_id` INT UNSIGNED NULL,
            `ip` VARBINARY(16) NULL,
            `user_agent` VARCHAR(255) NULL,
            `context` JSON NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `audit_log_event_index` (`event`),
            KEY `audit_log_user_index` (`user_id`),
            KEY `audit_log_created_index` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {audit_log}');
        $db->run('DROP TABLE IF EXISTS {rate_limits}');
    }
};
