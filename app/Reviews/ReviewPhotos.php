<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

use BMMatic\Core\Paths;
use BMMatic\Support\Http;
use BMMatic\Support\HttpClient;

/**
 * Reviewer photos are served by the site, never by Google.
 *
 * Only the URL is stored. When a visitor loads a review card, the browser asks this site for
 * /review-photo/{id}.jpg; the first request fetches the image server-side and caches it in storage. That way no
 * visitor IP or user agent reaches Google before the cookie banner was even answered, and a photo that cannot be
 * fetched simply falls back to the initials the design already draws.
 */
final class ReviewPhotos
{
    public const MAX_BYTES = 512 * 1024;
    private const CACHE_DAYS = 30;
    /** Longest side of a stored photo: the 42 px avatar at 2× density. */
    public const SIZE = 96;
    private const TYPES = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];

    public function __construct(private readonly ?HttpClient $http = null)
    {
    }

    /** Hosts Google serves profile photos from. Anything else is never fetched (no request to arbitrary servers). */
    public const HOSTS = ['googleusercontent.com', 'ggpht.com'];

    /** True for an https URL on one of Google's photo hosts. */
    public static function allowedUrl(string $url): bool
    {
        if (preg_match('#^https://[^\s<>"]{6,500}$#', $url) !== 1) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }
        return false;
    }

    public static function path(int $reviewId): string
    {
        return '/review-photo/' . $reviewId . '.jpg';
    }

    /**
     * The cached photo of one review, fetching it once when it is not cached yet.
     *
     * @return array{body: string, type: string}|null null when there is no usable photo (the card shows initials)
     */
    public function image(int $reviewId, string $sourceUrl): ?array
    {
        if (!self::allowedUrl($sourceUrl)) {
            return null;
        }
        $file = self::cacheFile($reviewId, $sourceUrl);
        if (is_file($file) && filemtime($file) > time() - self::CACHE_DAYS * 86400) {
            $body = (string) file_get_contents($file);
            return $body === '' ? null : ['body' => $body, 'type' => self::typeOf($file) ?? 'image/jpeg'];
        }
        $response = ($this->http ?? new Http(8))->request('GET', $sourceUrl, ['Accept' => 'image/*']);
        if (!$response->ok() || $response->body === '' || strlen($response->body) > self::MAX_BYTES) {
            return null;
        }
        $info = @getimagesizefromstring($response->body);
        if ($info === false || !isset(self::TYPES[$info[2]]) || $info[0] * $info[1] > 4000000) {
            return null;
        }
        // Stored at the size the card shows (42 px, twice for sharp screens): a few kB instead of Google's original,
        // and re-encoded, so nothing but pixels comes through.
        $body = self::shrink($response->body, $info[0], $info[1]) ?? $response->body;
        $type = $body === $response->body ? self::TYPES[$info[2]] : 'image/jpeg';
        $dir = dirname($file);
        if (is_dir($dir) || @mkdir($dir, 0700, true)) {
            @file_put_contents($file, $body);
        }
        return ['body' => $body, 'type' => $type];
    }

    /** True when this photo is already in the cache, so the website can link to it without a broken image. */
    public static function isCached(int $reviewId, string $sourceUrl): bool
    {
        $file = self::cacheFile($reviewId, $sourceUrl);
        return is_file($file) && filesize($file) > 0 && filemtime($file) > time() - self::CACHE_DAYS * 86400;
    }

    /**
     * Fetches the photos of the reviews the website shows, so the visitor's browser never waits for Google.
     * Called from the panel (after a sync or a visibility change), never while a visitor's page is being built.
     *
     * @param list<array<string, mixed>> $rows reviews as the repository returns them
     * @return int how many photos were fetched
     */
    public function warm(array $rows, int $limit = 24): int
    {
        $fetched = 0;
        foreach (array_slice($rows, 0, $limit) as $row) {
            $url = (string) ($row['reviewer_photo_url'] ?? '');
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || $url === '' || self::isCached($id, $url)) {
                continue;
            }
            $fetched += $this->image($id, $url) === null ? 0 : 1;
        }
        return $fetched;
    }

    /** Removes cached photos that have not been used for a while (called after a sync). */
    public function prune(int $days = 90): int
    {
        $removed = 0;
        foreach (glob(Paths::storage('cache/review-photos/*')) ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - $days * 86400) {
                @unlink($file);
                $removed++;
            }
        }
        return $removed;
    }

    private static function cacheFile(int $reviewId, string $sourceUrl): string
    {
        // The URL is part of the name, so a changed photo is fetched again instead of serving the old one.
        return Paths::storage('cache/review-photos/' . $reviewId . '-' . substr(hash('sha256', $sourceUrl), 0, 16) . '.img');
    }

    /** The photo scaled down to fit SIZE × SIZE, as JPEG (null when GD cannot read it). */
    private static function shrink(string $bytes, int $width, int $height): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }
        $scale = min(1.0, self::SIZE / max($width, $height));
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $out = imagecreatetruecolor($w, $h);
        imagefill($out, 0, 0, (int) imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $image, 0, 0, 0, 0, $w, $h, $width, $height);
        ob_start();
        imagejpeg($out, null, 85);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($out);
        return $jpeg === '' ? null : $jpeg;
    }

    private static function typeOf(string $file): ?string
    {
        $info = @getimagesize($file);
        return $info !== false && isset(self::TYPES[$info[2]]) ? self::TYPES[$info[2]] : null;
    }
}
