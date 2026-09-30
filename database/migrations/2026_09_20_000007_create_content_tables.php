<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * Public website content. Every translatable table has a *_translations table (one row per language) with a publish
 * state, so an unfinished translation falls back to the default language on the website.
 *
 * - media / media_translations: the media library (images, and PDF documents for publications) with alt text
 * - pages / page_translations: system pages with translated slugs; About has child pages (parent_id)
 * - page_sections / page_section_translations: ordered, toggleable sections of the home page
 * - expertise / expertise_translations: the areas of expertise (sectors), also used to classify entries
 * - entries / entry_translations / entry_media: projects, news, publications and gallery albums
 * - stats / stat_translations: the impact counters
 * - partners / partner_translations: partners and donors with their logo
 * - redirects, mail_queue: old URLs, outgoing email
 */
return new class implements Migration {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $lang = static fn (string $name): string => "`lang_code` CHAR(2) NOT NULL,
            CONSTRAINT `{$name}_lang_fk` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE ON UPDATE CASCADE";

        $db->run('CREATE TABLE IF NOT EXISTS {media} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `kind` VARCHAR(10) NOT NULL DEFAULT \'image\' COMMENT \'image or document\',
            `filename` VARCHAR(160) NOT NULL COMMENT \'random name inside public/uploads\',
            `original_name` VARCHAR(190) NOT NULL,
            `mime` VARCHAR(60) NOT NULL,
            `extension` VARCHAR(10) NOT NULL,
            `size` INT UNSIGNED NOT NULL DEFAULT 0,
            `width` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `height` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `pages` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'PDF page count when known\',
            `hash` CHAR(64) NOT NULL,
            `uploaded_by` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `media_filename_unique` (`filename`),
            KEY `media_hash_index` (`hash`),
            KEY `media_kind_created` (`kind`, `created_at`),
            CONSTRAINT `media_user_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {media_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `media_id` INT UNSIGNED NOT NULL,
            ' . $lang('media_translations') . ',
            `alt` VARCHAR(200) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `media_translations_unique` (`media_id`, `lang_code`),
            CONSTRAINT `media_translations_media_fk` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {pages} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(40) NOT NULL,
            `template` VARCHAR(40) NOT NULL,
            `parent_id` INT UNSIGNED NULL,
            `hero_media_id` INT UNSIGNED NULL,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `in_nav` TINYINT(1) NOT NULL DEFAULT 0,
            `nav_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `in_sitemap` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `pages_key_unique` (`key`),
            CONSTRAINT `pages_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
            CONSTRAINT `pages_hero_fk` FOREIGN KEY (`hero_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {page_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            ' . $lang('page_translations') . ',
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `slug` VARCHAR(120) NOT NULL,
            `nav_label` VARCHAR(80) NOT NULL DEFAULT \'\',
            `label` VARCHAR(120) NOT NULL DEFAULT \'\',
            `title` VARCHAR(200) NOT NULL,
            `highlight` VARCHAR(200) NOT NULL DEFAULT \'\',
            `intro` TEXT NULL,
            `body` MEDIUMTEXT NULL,
            `meta_title` VARCHAR(200) NOT NULL DEFAULT \'\',
            `meta_description` VARCHAR(320) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `page_translations_page_lang_unique` (`page_id`, `lang_code`),
            KEY `page_translations_lang_slug` (`lang_code`, `slug`),
            CONSTRAINT `page_translations_page_fk` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {page_sections} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            `type` VARCHAR(40) NOT NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
            `media_id` INT UNSIGNED NULL,
            `settings` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `page_sections_page_type_unique` (`page_id`, `type`),
            KEY `page_sections_order` (`page_id`, `sort_order`),
            CONSTRAINT `page_sections_page_fk` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
            CONSTRAINT `page_sections_media_fk` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {page_section_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `section_id` INT UNSIGNED NOT NULL,
            ' . $lang('page_section_translations') . ',
            `label` VARCHAR(120) NOT NULL DEFAULT \'\',
            `title` VARCHAR(200) NOT NULL DEFAULT \'\',
            `highlight` VARCHAR(200) NOT NULL DEFAULT \'\',
            `intro` TEXT NULL,
            `extra` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `page_section_translations_unique` (`section_id`, `lang_code`),
            CONSTRAINT `page_section_translations_section_fk` FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {expertise} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(60) NOT NULL,
            `icon` VARCHAR(60) NOT NULL,
            `cover_media_id` INT UNSIGNED NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `show_on_home` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `expertise_key_unique` (`key`),
            CONSTRAINT `expertise_cover_fk` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {expertise_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `expertise_id` INT UNSIGNED NOT NULL,
            ' . $lang('expertise_translations') . ',
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `slug` VARCHAR(120) NOT NULL,
            `title` VARCHAR(160) NOT NULL,
            `summary` TEXT NULL,
            `body` MEDIUMTEXT NULL,
            `meta_title` VARCHAR(200) NOT NULL DEFAULT \'\',
            `meta_description` VARCHAR(320) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `expertise_translations_unique` (`expertise_id`, `lang_code`),
            UNIQUE KEY `expertise_translations_slug_unique` (`lang_code`, `slug`),
            CONSTRAINT `expertise_translations_parent_fk` FOREIGN KEY (`expertise_id`) REFERENCES `expertise` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        // Projects, news, publications and gallery albums share one table: the same listing, filtering, publishing
        // and translation logic, with the type-specific columns left empty where they do not apply.
        $db->run('CREATE TABLE IF NOT EXISTS {entries} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(20) NOT NULL COMMENT \'project, news, publication, album\',
            `expertise_id` INT UNSIGNED NULL,
            `region` VARCHAR(40) NOT NULL DEFAULT \'\',
            `status` VARCHAR(20) NOT NULL DEFAULT \'\' COMMENT \'projects: planned, ongoing, completed\',
            `start_date` DATE NULL,
            `end_date` DATE NULL,
            `published_on` DATE NOT NULL,
            `beneficiaries` INT UNSIGNED NULL,
            `donors` VARCHAR(300) NOT NULL DEFAULT \'\',
            `cover_media_id` INT UNSIGNED NULL,
            `file_media_id` INT UNSIGNED NULL COMMENT \'publications: the PDF\',
            `related_id` INT UNSIGNED NULL COMMENT \'news and albums: the project they belong to\',
            `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `entries_list` (`type`, `is_enabled`, `published_on`),
            KEY `entries_expertise` (`type`, `expertise_id`),
            KEY `entries_region` (`type`, `region`),
            CONSTRAINT `entries_expertise_fk` FOREIGN KEY (`expertise_id`) REFERENCES `expertise` (`id`) ON DELETE SET NULL,
            CONSTRAINT `entries_cover_fk` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
            CONSTRAINT `entries_file_fk` FOREIGN KEY (`file_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
            CONSTRAINT `entries_related_fk` FOREIGN KEY (`related_id`) REFERENCES `entries` (`id`) ON DELETE SET NULL,
            CONSTRAINT `entries_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {entry_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT UNSIGNED NOT NULL,
            `entry_type` VARCHAR(20) NOT NULL,
            ' . $lang('entry_translations') . ',
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `slug` VARCHAR(160) NOT NULL,
            `title` VARCHAR(200) NOT NULL,
            `summary` TEXT NULL,
            `body` MEDIUMTEXT NULL,
            `location` VARCHAR(160) NOT NULL DEFAULT \'\',
            `meta_title` VARCHAR(200) NOT NULL DEFAULT \'\',
            `meta_description` VARCHAR(320) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `entry_translations_unique` (`entry_id`, `lang_code`),
            UNIQUE KEY `entry_translations_slug_unique` (`entry_type`, `lang_code`, `slug`),
            CONSTRAINT `entry_translations_entry_fk` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {entry_media} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT UNSIGNED NOT NULL,
            `media_id` INT UNSIGNED NOT NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `entry_media_unique` (`entry_id`, `media_id`),
            KEY `entry_media_order` (`entry_id`, `sort_order`),
            CONSTRAINT `entry_media_entry_fk` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `entry_media_media_fk` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {stats} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {stat_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `stat_id` INT UNSIGNED NOT NULL,
            ' . $lang('stat_translations') . ',
            `value` VARCHAR(60) NOT NULL,
            `label` VARCHAR(160) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `stat_translations_unique` (`stat_id`, `lang_code`),
            CONSTRAINT `stat_translations_parent_fk` FOREIGN KEY (`stat_id`) REFERENCES `stats` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {partners} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(160) NOT NULL,
            `kind` VARCHAR(20) NOT NULL DEFAULT \'partner\' COMMENT \'donor or partner\',
            `url` VARCHAR(300) NOT NULL DEFAULT \'\',
            `logo_media_id` INT UNSIGNED NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            CONSTRAINT `partners_logo_fk` FOREIGN KEY (`logo_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {partner_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `partner_id` INT UNSIGNED NOT NULL,
            ' . $lang('partner_translations') . ',
            `description` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `partner_translations_unique` (`partner_id`, `lang_code`),
            CONSTRAINT `partner_translations_partner_fk` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {mail_queue} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `to_email` VARCHAR(190) NOT NULL,
            `to_name` VARCHAR(160) NOT NULL DEFAULT \'\',
            `reply_to` VARCHAR(190) NOT NULL DEFAULT \'\',
            `subject` VARCHAR(255) NOT NULL,
            `body_text` MEDIUMTEXT NOT NULL,
            `body_html` MEDIUMTEXT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT \'pending\',
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `available_at` DATETIME NOT NULL,
            `locked_until` DATETIME NULL,
            `last_error` VARCHAR(500) NOT NULL DEFAULT \'\',
            `created_at` DATETIME NOT NULL,
            `sent_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `mail_queue_pending` (`status`, `available_at`)
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {redirects} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `from_path` VARCHAR(255) NOT NULL,
            `to_path` VARCHAR(500) NOT NULL,
            `status` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
            `hits` INT UNSIGNED NOT NULL DEFAULT 0,
            `last_hit_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `redirects_from_unique` (`from_path`)
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['redirects', 'mail_queue', 'partner_translations', 'partners', 'stat_translations', 'stats', 'entry_media', 'entry_translations', 'entries', 'expertise_translations', 'expertise', 'page_section_translations', 'page_sections', 'page_translations', 'pages', 'media_translations', 'media'] as $table) {
            $db->run('DROP TABLE IF EXISTS {' . $table . '}');
        }
    }
};
