<?php

declare(strict_types=1);

use Gate\Core\Database;
use Gate\Core\Migration;

/**
 * Accessibility corrections to the approved palette (owner decision after Phase 2, WCAG AA 4.5:1):
 * existing installations still holding the original values get the corrected ones. Colors changed in the
 * Appearance settings are left alone.
 */
return new class implements Migration {
    private const CHANGES = [
        'theme.c-text-6' => ['#5E7893', '#6F86A0'],
        'theme.c-text-8' => ['#4F6780', '#6F86A0'],
        'theme.c-primary-hover' => ['#1580C4', '#147BBD'],
    ];

    public function up(Database $db): void
    {
        foreach (self::CHANGES as $key => [$old, $new]) {
            $db->run('UPDATE {settings} SET `value` = :new WHERE `key` = :k AND UPPER(`value`) = :old', ['new' => $new, 'k' => $key, 'old' => $old]);
        }
    }

    public function down(Database $db): void
    {
        foreach (self::CHANGES as $key => [$old, $new]) {
            $db->run('UPDATE {settings} SET `value` = :old WHERE `key` = :k AND UPPER(`value`) = :new', ['old' => $old, 'k' => $key, 'new' => $new]);
        }
    }
};
