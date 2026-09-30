<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

/**
 * Public website content. Every content table has a *_translations table (one row per language).
 * - pages / page_translations: system pages with translated slugs, navigation labels and SEO fields
 * - page_sections / page_section_translations: ordered, toggleable sections (home page)
 * - services, transmission_types, process_steps, stats, partners (+ translations)
 * Admin editing screens arrive in Phase 4.
 */
return new class implements Migration {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $lang = static fn (string $name): string => "`lang_code` CHAR(2) NOT NULL,
            CONSTRAINT `{$name}_lang_fk` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE ON UPDATE CASCADE";

        $db->run('CREATE TABLE IF NOT EXISTS {pages} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(40) NOT NULL,
            `template` VARCHAR(40) NOT NULL,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `in_nav` TINYINT(1) NOT NULL DEFAULT 0,
            `nav_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `in_sitemap` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `pages_key_unique` (`key`)
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {page_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            ' . $lang('page_translations') . ',
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
            UNIQUE KEY `page_translations_lang_slug_unique` (`lang_code`, `slug`),
            CONSTRAINT `page_translations_page_fk` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {page_sections} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            `type` VARCHAR(40) NOT NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
            `settings` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `page_sections_page_type_unique` (`page_id`, `type`),
            KEY `page_sections_order` (`page_id`, `sort_order`),
            CONSTRAINT `page_sections_page_fk` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
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

        $db->run('CREATE TABLE IF NOT EXISTS {services} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(60) NOT NULL,
            `icon` VARCHAR(60) NOT NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `show_in_menu` TINYINT(1) NOT NULL DEFAULT 0,
            `show_on_home` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `services_key_unique` (`key`)
        ) ' . self::OPTIONS);
        $db->run('CREATE TABLE IF NOT EXISTS {service_translations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `service_id` INT UNSIGNED NOT NULL,
            ' . $lang('service_translations') . ',
            `slug` VARCHAR(120) NOT NULL,
            `title` VARCHAR(160) NOT NULL,
            `menu_title` VARCHAR(120) NOT NULL DEFAULT \'\',
            `menu_sub` VARCHAR(160) NOT NULL DEFAULT \'\',
            `short_title` VARCHAR(120) NOT NULL DEFAULT \'\',
            `summary` TEXT NULL,
            `body` MEDIUMTEXT NULL,
            `meta_title` VARCHAR(200) NOT NULL DEFAULT \'\',
            `meta_description` VARCHAR(320) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `service_translations_unique` (`service_id`, `lang_code`),
            UNIQUE KEY `service_translations_slug_unique` (`lang_code`, `slug`),
            CONSTRAINT `service_translations_service_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        foreach (['transmission_types' => ['label VARCHAR(160) NOT NULL', 'description TEXT NULL'], 'process_steps' => ['title VARCHAR(160) NOT NULL', 'text TEXT NULL'], 'stats' => ['value VARCHAR(60) NOT NULL', 'label VARCHAR(160) NOT NULL']] as $table => $fields) {
            $fk = rtrim($table, 's');
            $db->run('CREATE TABLE IF NOT EXISTS {' . $table . '} (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ' . self::OPTIONS);
            $columns = implode(",\n", array_map(static fn (string $f): string => '`' . strtok($f, ' ') . '` ' . substr($f, strpos($f, ' ') + 1), $fields));
            $db->run('CREATE TABLE IF NOT EXISTS {' . $fk . '_translations} (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `' . $fk . '_id` INT UNSIGNED NOT NULL,
                ' . $lang($fk . '_translations') . ',
                ' . $columns . ',
                PRIMARY KEY (`id`),
                UNIQUE KEY `' . $fk . '_translations_unique` (`' . $fk . '_id`, `lang_code`),
                CONSTRAINT `' . $fk . '_translations_parent_fk` FOREIGN KEY (`' . $fk . '_id`) REFERENCES `' . $table . '` (`id`) ON DELETE CASCADE
            ) ' . self::OPTIONS);
        }

        $db->run('CREATE TABLE IF NOT EXISTS {partners} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(160) NOT NULL,
            `url` VARCHAR(300) NOT NULL DEFAULT \'\',
            `logo` VARCHAR(300) NOT NULL DEFAULT \'\',
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
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

        $db->run('CREATE TABLE IF NOT EXISTS {appointments} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(20) NOT NULL DEFAULT \'new\',
            `name` VARCHAR(120) NOT NULL,
            `phone` VARCHAR(40) NOT NULL,
            `email` VARCHAR(190) NOT NULL,
            `car` VARCHAR(160) NOT NULL,
            `gearbox_type` VARCHAR(40) NOT NULL,
            `symptoms` TEXT NOT NULL,
            `lang_code` CHAR(2) NOT NULL,
            `consent_at` DATETIME NOT NULL,
            `ip_hash` CHAR(64) NOT NULL,
            `user_agent` VARCHAR(255) NOT NULL DEFAULT \'\',
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `appointments_status_created` (`status`, `created_at`)
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
        foreach (['redirects', 'mail_queue', 'appointments', 'partner_translations', 'partners', 'stat_translations', 'stats', 'process_step_translations', 'process_steps', 'transmission_type_translations', 'transmission_types', 'service_translations', 'services', 'page_section_translations', 'page_sections', 'page_translations', 'pages'] as $table) {
            $db->run('DROP TABLE IF EXISTS {' . $table . '}');
        }
    }
};
