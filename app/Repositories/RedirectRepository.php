<?php

declare(strict_types=1);

namespace BMMatic\Repositories;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;

/**
 * 301/302 redirect map for old URLs (e.g. the previous website). Checked before a public 404 is shown; hits are
 * counted so unused entries can be removed later. Paths are stored normalised: leading slash, no trailing slash
 * (except "/"), lower case, without query string.
 */
final class RedirectRepository
{
    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    public static function normalise(string $path): string
    {
        $path = strtolower((string) strtok(rawurldecode($path), '?'));
        $path = '/' . trim($path, '/');
        return $path;
    }

    /** @return array{to: string, status: int}|null */
    public function find(string $path): ?array
    {
        $row = $this->db->first('redirects', ['from_path' => self::normalise($path)]);
        if ($row === null) {
            return null;
        }
        $to = (string) $row['to_path'];
        // Only local paths or absolute http(s) URLs.
        if (preg_match('#^(/[^\s]*|https?://[^\s]+)$#', $to) !== 1 || str_starts_with($to, '//')) {
            return null;
        }
        return ['to' => $to, 'status' => in_array((int) $row['status'], [301, 302, 307, 308], true) ? (int) $row['status'] : 301];
    }

    public function hit(string $path): void
    {
        $this->db->run('UPDATE {redirects} SET `hits` = `hits` + 1, `last_hit_at` = :now WHERE `from_path` = :p', ['now' => $this->clock->now()->format('Y-m-d H:i:s'), 'p' => self::normalise($path)]);
    }

    public function add(string $from, string $to, int $status = 301): void
    {
        $this->db->upsert('redirects', ['from_path' => self::normalise($from), 'to_path' => $to, 'status' => $status], ['to_path', 'status']);
    }
}
