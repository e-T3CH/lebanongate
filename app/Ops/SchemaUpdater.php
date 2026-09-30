<?php

declare(strict_types=1);

namespace Gate\Ops;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Core\Migrator;
use Gate\Core\Paths;
use Gate\Database\Seeders\SettingsSeeder;
use Gate\Database\Seeders\StatusEmailSeeder;
use Gate\Database\Seeders\TranslationsSeeder;
use Gate\Security\RateLimiter;
use Gate\Services\AuditLog;
use Gate\Services\Settings;

/**
 * Automatic database updates: after new files are uploaded, the first request runs the new migrations and adds
 * missing settings, interface strings and status-email texts — what `php bin/console migrate` and `seed` do, for
 * hosts without a command line (one.com). Changed interface wording in lang/ is brought up to date as well.
 *
 * Every request compares a signature of the migration and seeder files (names, sizes, dates: a few file stats, no
 * reading) with the one stored after the last update; only a difference does any work. One request updates while
 * the others get a short "back in a moment" (HTTP 503). Page content and languages are never seeded here: those
 * belong to the owner once the site is installed.
 */
final class SchemaUpdater
{
    public const SIGNATURE_SETTING = 'app.schema';
    private const LOCK = 'ops:migrate';

    public function __construct(private readonly Database $db, private readonly Settings $settings, private readonly Clock $clock)
    {
    }

    public static function signature(): string
    {
        $files = array_merge(
            glob(Paths::root('database/migrations') . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(Paths::root('database/seeders') . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(Paths::root('lang') . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php') ?: [],
        );
        sort($files, SORT_STRING);
        $parts = [];
        foreach ($files as $file) {
            $parts[] = basename(dirname($file)) . '/' . basename($file) . ':' . (int) @filesize($file) . ':' . (int) @filemtime($file);
        }
        return hash('sha256', implode("\n", $parts));
    }

    public function isCurrent(): bool
    {
        return hash_equals($this->settings->string(self::SIGNATURE_SETTING), self::signature());
    }

    /**
     * Brings the database up to date when the files changed.
     *
     * @return list<string>|null the migrations that ran, or null when another request is updating right now
     */
    public function update(?AuditLog $audit = null): ?array
    {
        $signature = self::signature();
        if (hash_equals($this->settings->string(self::SIGNATURE_SETTING), $signature)) {
            return [];
        }
        $limiter = new RateLimiter($this->db, $this->clock);
        if ($limiter->hit(self::LOCK, 300) !== 1) {
            return null;
        }
        try {
            @set_time_limit(300);
            $ran = Migrator::default($this->db)->migrate();
            $this->settings->refresh();
            (new SettingsSeeder($this->settings))->run();
            (new TranslationsSeeder($this->db, $this->clock))->run();
            (new StatusEmailSeeder($this->db))->run();
            $this->settings->set(self::SIGNATURE_SETTING, $signature, 'string');
            if ($ran !== []) {
                $audit?->record(AuditLog::SYSTEM_UPDATED, null, ['migrations' => $ran]);
            }
            return $ran;
        } finally {
            $limiter->clear(self::LOCK);
        }
    }
}
