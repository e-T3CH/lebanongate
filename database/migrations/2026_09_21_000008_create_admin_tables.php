<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

/**
 * Admin panel (Phase 4).
 * - user_invitations / email_changes: invite a colleague by email, confirm an own email change
 * - appointments: read state (the Messages inbox), who last changed the status, internal notes
 * - appointment_status_emails: optional customer email per status, per language (off by default)
 * - media / media_translations: the media library with alt text per language
 * - published state per language for pages and services
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

        // Messages and appointments are two views of the same request: unread is independent of the status.
        $db->run('ALTER TABLE {appointments}
            ADD COLUMN `read_at` DATETIME NULL AFTER `status`,
            ADD COLUMN `read_by` INT UNSIGNED NULL AFTER `read_at`,
            ADD COLUMN `status_changed_at` DATETIME NULL AFTER `read_by`,
            ADD COLUMN `status_changed_by` INT UNSIGNED NULL AFTER `status_changed_at`,
            ADD KEY `appointments_read_index` (`read_at`, `created_at`),
            ADD CONSTRAINT `appointments_read_by_fk` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            ADD CONSTRAINT `appointments_status_by_fk` FOREIGN KEY (`status_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');

        $db->run('CREATE TABLE IF NOT EXISTS {appointment_notes} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `appointment_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NULL,
            `author_name` VARCHAR(120) NOT NULL,
            `body` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `appointment_notes_appointment_index` (`appointment_id`, `created_at`),
            CONSTRAINT `appointment_notes_appointment_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
            CONSTRAINT `appointment_notes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {appointment_status_emails} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(20) NOT NULL,
            `lang_code` CHAR(2) NOT NULL,
            `subject` VARCHAR(200) NOT NULL DEFAULT \'\',
            `body` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `appointment_status_emails_unique` (`status`, `lang_code`),
            CONSTRAINT `appointment_status_emails_lang_fk` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {media} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `filename` VARCHAR(160) NOT NULL COMMENT \'random name inside public/uploads\',
            `original_name` VARCHAR(190) NOT NULL,
            `mime` VARCHAR(60) NOT NULL,
            `extension` VARCHAR(10) NOT NULL,
            `size` INT UNSIGNED NOT NULL DEFAULT 0,
            `width` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `height` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `hash` CHAR(64) NOT NULL,
            `uploaded_by` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `media_filename_unique` (`filename`),
            KEY `media_hash_index` (`hash`),
            CONSTRAINT `media_user_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {media_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `media_id` INT UNSIGNED NOT NULL,
            `lang_code` CHAR(2) NOT NULL,
            `alt` VARCHAR(200) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `media_translations_unique` (`media_id`, `lang_code`),
            CONSTRAINT `media_translations_media_fk` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
            CONSTRAINT `media_translations_lang_fk` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ' . self::OPTIONS);

        // Publish state per language: an unfinished translation falls back to the default language on the website.
        $db->run('ALTER TABLE {page_translations} ADD COLUMN `is_published` TINYINT(1) NOT NULL DEFAULT 1 AFTER `lang_code`');
        $db->run('ALTER TABLE {service_translations} ADD COLUMN `is_published` TINYINT(1) NOT NULL DEFAULT 1 AFTER `lang_code`');
    }

    public function down(Database $db): void
    {
        $db->run('ALTER TABLE {service_translations} DROP COLUMN `is_published`');
        $db->run('ALTER TABLE {page_translations} DROP COLUMN `is_published`');
        $db->run('DROP TABLE IF EXISTS {media_translations}');
        $db->run('DROP TABLE IF EXISTS {media}');
        $db->run('DROP TABLE IF EXISTS {appointment_status_emails}');
        $db->run('DROP TABLE IF EXISTS {appointment_notes}');
        $db->run('ALTER TABLE {appointments}
            DROP FOREIGN KEY `appointments_status_by_fk`,
            DROP FOREIGN KEY `appointments_read_by_fk`,
            DROP KEY `appointments_read_index`,
            DROP COLUMN `status_changed_by`,
            DROP COLUMN `status_changed_at`,
            DROP COLUMN `read_by`,
            DROP COLUMN `read_at`');
        $db->run('DROP TABLE IF EXISTS {email_changes}');
        $db->run('DROP TABLE IF EXISTS {user_invitations}');
    }
};
