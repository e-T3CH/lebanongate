<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * Admin panel and visitor input.
 * - user_invitations / email_changes: invite a colleague by email, confirm an own email change
 * - messages / message_notes: the contact form inbox (read state, archive, internal notes)
 * - subscribers: newsletter sign-ups with double opt-in (only an HMAC of the confirmation token is stored)
 */
return new class implements Migration {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {user_invitations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `name` VARCHAR(120) NOT NULL,
            `role` ENUM(\'admin\', \'editor\') NOT NULL DEFAULT \'editor\',
            `token_hash` CHAR(64) NOT NULL,
            `invited_by` INT UNSIGNED NULL,
            `expires_at` DATETIME NOT NULL,
            `accepted_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_invitations_token_unique` (`token_hash`),
            KEY `user_invitations_email_index` (`email`),
            CONSTRAINT `user_invitations_user_fk` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {email_changes} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `new_email` VARCHAR(190) NOT NULL,
            `token_hash` CHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `confirmed_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email_changes_token_unique` (`token_hash`),
            CONSTRAINT `email_changes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {messages} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(120) NOT NULL,
            `email` VARCHAR(190) NOT NULL,
            `phone` VARCHAR(40) NOT NULL DEFAULT \'\',
            `organisation` VARCHAR(160) NOT NULL DEFAULT \'\',
            `subject` VARCHAR(40) NOT NULL DEFAULT \'general\' COMMENT \'general, partnership, media, volunteer, other\',
            `message` TEXT NOT NULL,
            `lang_code` CHAR(2) NOT NULL,
            `consent_at` DATETIME NOT NULL,
            `ip_hash` CHAR(64) NOT NULL,
            `user_agent` VARCHAR(255) NOT NULL DEFAULT \'\',
            `read_at` DATETIME NULL,
            `read_by` INT UNSIGNED NULL,
            `archived_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `messages_created` (`created_at`),
            KEY `messages_inbox` (`archived_at`, `read_at`, `created_at`),
            CONSTRAINT `messages_read_by_fk` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {message_notes} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `message_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NULL,
            `author_name` VARCHAR(120) NOT NULL,
            `body` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `message_notes_message_index` (`message_id`, `created_at`),
            CONSTRAINT `message_notes_message_fk` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
            CONSTRAINT `message_notes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {subscribers} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `lang_code` CHAR(2) NOT NULL,
            `token_hash` CHAR(64) NOT NULL,
            `confirmed_at` DATETIME NULL,
            `unsubscribed_at` DATETIME NULL,
            `ip_hash` CHAR(64) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `subscribers_email_unique` (`email`),
            UNIQUE KEY `subscribers_token_unique` (`token_hash`),
            KEY `subscribers_state` (`confirmed_at`, `unsubscribed_at`)
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['subscribers', 'message_notes', 'messages', 'email_changes', 'user_invitations'] as $table) {
            $db->run('DROP TABLE IF EXISTS {' . $table . '}');
        }
    }
};
