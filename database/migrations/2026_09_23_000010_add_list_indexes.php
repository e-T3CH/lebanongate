<?php

declare(strict_types=1);

use BMMatic\Core\Database;
use BMMatic\Core\Migration;

/**
 * Indexes for the list views (Phase 6 performance pass, measured with tools/perf-queries.php on 20,000 requests
 * and 2,000 reviews). Without them the unfiltered lists sorted every row on each page view:
 * - appointments (created_at): the default "newest first" list, the date-range filter and "this week" on the
 *   dashboard; 19 ms with a full sort → under 1 ms.
 * - google_reviews (review_date): the "All" tab and the rating/language filters, newest first.
 */
return new class implements Migration {
    public function up(Database $db): void
    {
        $db->run('ALTER TABLE {appointments} ADD INDEX `appointments_created` (`created_at`)');
        $db->run('ALTER TABLE {google_reviews} ADD INDEX `google_reviews_date` (`review_date`)');
    }

    public function down(Database $db): void
    {
        $db->run('ALTER TABLE {google_reviews} DROP INDEX `google_reviews_date`');
        $db->run('ALTER TABLE {appointments} DROP INDEX `appointments_created`');
    }
};
