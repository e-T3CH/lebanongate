<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Core\Paths;

/**
 * Responsive images: WebP variants of a public image at the requested widths, generated once with GD into
 * public/media/cache (content-hashed names, so changed originals get new files) and served as static files.
 * Falls back to the original when GD/WebP is unavailable or the file cannot be written.
 *
 * @phpstan-type Picture array{src: string, width: int, height: int, webp: string}
 */
final class Media
{
    public const CACHE_DIR = 'media/cache';

    /** @var array<string, array{0: int, 1: int}|null> */
    private array $sizes = [];

    /**
     * @param list<int> $widths pixel widths of the variants (for 1x and 2x of the displayed size)
     * @return Picture
     */
    public function picture(string $publicPath, array $widths): array
    {
        $publicPath = '/' . ltrim((string) strtok($publicPath, '?'), '/');
        $file = self::resolve($publicPath);
        $size = $file !== null ? $this->fileSize($file) : null;
        $result = ['src' => $publicPath, 'width' => $size[0] ?? 0, 'height' => $size[1] ?? 0, 'webp' => ''];
        if ($file === null || $size === null || !self::supported()) {
            return $result;
        }
        $hash = substr((string) hash_file('sha256', $file), 0, 12);
        $base = pathinfo($file, PATHINFO_FILENAME);
        $set = [];
        foreach (array_unique($widths) as $w) {
            $w = min((int) $w, $size[0]);
            if ($w < 16) {
                continue;
            }
            $name = self::CACHE_DIR . '/' . preg_replace('/[^a-z0-9-]/i', '-', $base) . '-' . $hash . '-' . $w . '.webp';
            $target = Paths::publicDir($name);
            if (!is_file($target) && !$this->generate($file, $target, $w, $size)) {
                return $result;
            }
            $set[$w] = \Gate\Core\Url::to('/' . $name) . ' ' . $w . 'w';
        }
        ksort($set);
        $result['webp'] = implode(', ', $set);
        return $result;
    }

    /**
     * Pixel size of a public image (/assets/… or /uploads/…), or null when it is not a local image.
     *
     * @return array{0: int, 1: int}|null
     */
    public function size(string $publicPath): ?array
    {
        $file = self::resolve('/' . ltrim((string) strtok($publicPath, '?'), '/'));
        $size = $file === null ? null : $this->fileSize($file);
        return $size !== null && $size[0] > 0 && $size[1] > 0 ? $size : null;
    }

    public static function supported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefrompng');
    }

    private static function resolve(string $publicPath): ?string
    {
        if (preg_match('#^/(assets|uploads)/[A-Za-z0-9._/-]+\.(png|jpe?g|webp)$#i', $publicPath) !== 1 || str_contains($publicPath, '..')) {
            return null;
        }
        $file = Paths::publicDir(ltrim($publicPath, '/'));
        return is_file($file) ? $file : null;
    }

    /** @return array{0: int, 1: int}|null */
    private function fileSize(string $file): ?array
    {
        if (!array_key_exists($file, $this->sizes)) {
            $info = @getimagesize($file);
            $this->sizes[$file] = is_array($info) ? [(int) $info[0], (int) $info[1]] : null;
        }
        return $this->sizes[$file];
    }

    /** @param array{0: int, 1: int} $size */
    private function generate(string $source, string $target, int $width, array $size): bool
    {
        $dir = dirname($target);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $image = match ($ext) {
            'png' => @imagecreatefrompng($source),
            'jpg', 'jpeg' => @imagecreatefromjpeg($source),
            'webp' => @imagecreatefromwebp($source),
            default => false,
        };
        if ($image === false) {
            return false;
        }
        $height = max(1, (int) round($size[1] * $width / $size[0]));
        $scaled = imagecreatetruecolor($width, $height);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, (int) imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $ok = imagewebp($scaled, $tmp, 88);
        imagedestroy($image);
        imagedestroy($scaled);
        if ($ok && @rename($tmp, $target)) {
            return true;
        }
        @unlink($tmp);
        return false;
    }
}
