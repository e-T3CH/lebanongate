<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * Google reviews (Phase 5).
 * - google_reviews: one row per review, keyed by the id the provider gives it, so a sync can update instead of
 *   duplicating. `is_visible` belongs to the workshop and is never touched by a sync; a review that disappears at
 *   Google is marked (`deleted_at`) and stays readable in the panel.
 * - review_sync_log: one row per sync run (manual, cron or dry run) with its counts and any error.
 */
return new class implements Migration {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->run('CREATE TABLE IF NOT EXISTS {google_reviews} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `google_review_id` VARCHAR(190) NOT NULL,
            `reviewer_name` VARCHAR(160) NOT NULL,
            `reviewer_photo_url` VARCHAR(500) NOT NULL DEFAULT \'\',
            `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `text` TEXT NULL,
            `language` VARCHAR(5) NOT NULL DEFAULT \'\',
            `review_date` DATETIME NOT NULL,
            `owner_reply` TEXT NULL,
            `owner_reply_at` DATETIME NULL,
            `is_visible` TINYINT(1) NOT NULL DEFAULT 0,
            `source` VARCHAR(20) NOT NULL DEFAULT \'business_profile\',
            `deleted_at` DATETIME NULL COMMENT \'no longer returned by the provider; never removed silently\',
            `imported_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `google_reviews_external_unique` (`google_review_id`),
            KEY `google_reviews_visible_date` (`is_visible`, `review_date`),
            KEY `google_reviews_rating` (`rating`)
        ) ' . self::OPTIONS);

        $db->run('CREATE TABLE IF NOT EXISTS {review_sync_log} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `provider` VARCHAR(30) NOT NULL,
            `status` VARCHAR(20) NOT NULL COMMENT \'ok | error | skipped\',
            `added` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `updated` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `removed` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `dry_run` TINYINT(1) NOT NULL DEFAULT 0,
            `message` VARCHAR(500) NOT NULL DEFAULT \'\',
            `started_at` DATETIME NOT NULL,
            `finished_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `review_sync_log_started` (`started_at`)
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        $db->run('DROP TABLE IF EXISTS {review_sync_log}');
        $db->run('DROP TABLE IF EXISTS {google_reviews}');
    }
};
