<?php

declare(strict_types=1);

namespace BMMatic\Support;

use BMMatic\Core\Paths;

/**
 * The application error log: storage/logs/app-YYYY-MM-DD.log, one file per day, a new part when a day's file grows
 * past MAX_BYTES, and files older than the retention removed when a new day starts.
 *
 * Every entry gets a short reference code. The visitor sees only that code on the error page; the log holds the
 * full detail under the same code, so a phone call ("I got error 7K2QF9XM") finds the right entry at once.
 *
 * Nothing secret is written: request paths are logged without their query string, and long token-like path
 * segments (invitation links, the health token) are masked.
 */
final class ErrorLog
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(private readonly int $retentionDays = 30, private readonly ?string $dir = null)
    {
    }

    /** A new reference code: 8 characters without look-alikes (no 0/O, 1/I). */
    public static function reference(): string
    {
        $out = '';
        foreach (str_split(random_bytes(8)) as $byte) {
            $out .= self::ALPHABET[ord($byte) % 32];
        }
        return $out;
    }

    /**
     * Writes one exception and returns its reference code.
     *
     * @param array<string, string> $context e.g. ['method' => 'POST', 'path' => '/nl/contact']
     */
    public function write(\Throwable $e, array $context = [], ?string $reference = null): string
    {
        $reference ??= self::reference();
        $lines = [sprintf('[%s] ref=%s %s: %s', gmdate('c'), $reference, $e::class, self::oneLine($e->getMessage()))];
        foreach ($context as $key => $value) {
            $lines[] = '  ' . $key . ': ' . ($key === 'path' ? self::maskPath($value) : self::oneLine($value));
        }
        $lines[] = '  at ' . $e->getFile() . ':' . $e->getLine();
        $lines[] = $e->getTraceAsString();
        $this->append(implode("\n", $lines) . "\n\n");
        return $reference;
    }

    /** Removes log files older than the retention period. Returns how many were removed. */
    public function prune(?int $days = null): int
    {
        $days ??= $this->retentionDays;
        if ($days <= 0) {
            return 0;
        }
        $cutoff = gmdate('Y-m-d', time() - $days * 86400);
        $removed = 0;
        foreach (glob($this->dir() . DIRECTORY_SEPARATOR . 'app-*.log') ?: [] as $file) {
            if (preg_match('/^app-(\d{4}-\d{2}-\d{2})(-\d+)?\.log$/', basename($file), $m) === 1 && $m[1] < $cutoff) {
                $removed += @unlink($file) ? 1 : 0;
            }
        }
        return $removed;
    }

    /** "/admin/invitation/Zx9…" → "/admin/invitation/…": tokens never reach the log. */
    public static function maskPath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        return mb_substr((string) preg_replace('#/[A-Za-z0-9_\-]{20,}#', '/…', $path), 0, 200);
    }

    /** The file an entry written now goes to (a new part once the day's file is full). */
    public function currentFile(): string
    {
        $base = $this->dir() . DIRECTORY_SEPARATOR . 'app-' . gmdate('Y-m-d');
        $file = $base . '.log';
        for ($part = 1; is_file($file) && filesize($file) >= self::MAX_BYTES && $part < 100; $part++) {
            $file = $base . '-' . $part . '.log';
        }
        return $file;
    }

    private function append(string $entry): void
    {
        $dir = $this->dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $file = $this->currentFile();
        $newDay = !is_file($this->dir() . DIRECTORY_SEPARATOR . 'app-' . gmdate('Y-m-d') . '.log');
        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
        if ($newDay) {
            // Rotation happens by date; the first entry of a new day clears out what is past the retention.
            $this->prune();
        }
    }

    private function dir(): string
    {
        return $this->dir ?? Paths::storage('logs');
    }

    private static function oneLine(string $value): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', $value)), 0, 1000);
    }
}
