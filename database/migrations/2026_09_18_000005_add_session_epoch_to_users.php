<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * users.session_epoch: incremented to end every session of a user at once (password reset, account lock).
 * A session stores the epoch it was created with; a mismatch signs the session out on its next request.
 */
return new class implements Migration {
    public function up(Database $db): void
    {
        $exists = (int) $db->scalar("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'session_epoch'");
        if ($exists === 0) {
            $db->run('ALTER TABLE {users} ADD COLUMN `session_epoch` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `password_changed_at`');
        }
    }

    public function down(Database $db): void
    {
        $exists = (int) $db->scalar("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'session_epoch'");
        if ($exists === 1) {
            $db->run('ALTER TABLE {users} DROP COLUMN `session_epoch`');
        }
    }
};
