<?php

declare(strict_types=1);

namespace Gate\Services;

use Gate\Core\Clock;
use Gate\Core\Database;
use Gate\Core\Paths;

/**
 * The media library: uploads into public/uploads, one row per file with alt text per language.
 *
 * Every upload is checked on its real content (getimagesize / the PDF signature and finfo, never the name or the
 * browser's type) and stored under a random name. Images (PNG, JPEG, WebP) are re-encoded with GD, which drops EXIF,
 * colour profiles and anything hidden in the file. PDF documents (publications) are kept as they are and reach
 * visitors only through the download route, which sends them with safe headers. /uploads itself refuses to execute
 * anything (public/uploads/.htaccess).
 *
 * @phpstan-type MediaRow array{id: int, kind: string, filename: string, original_name: string, mime: string, extension: string, size: int, width: int, height: int, pages: int, url: string, created_at: string, alt: array<string, string>}
 */
final class MediaLibrary
{
    public const MAX_BYTES = 6 * 1024 * 1024;
    public const MAX_DOCUMENT_BYTES = 25 * 1024 * 1024;
    public const KINDS = ['image', 'document'];
    public const MAX_PIXELS = 4000;
    /** Decoding needs ~5 bytes per pixel: a small file that declares huge dimensions (a decompression bomb) is refused. */
    public const MAX_MEGAPIXELS = 25;
    /** image type (getimagesize) => [extension, mime] */
    private const TYPES = [
        IMAGETYPE_PNG => ['png', 'image/png'],
        IMAGETYPE_JPEG => ['jpg', 'image/jpeg'],
        IMAGETYPE_WEBP => ['webp', 'image/webp'],
    ];

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    /**
     * @param list<string> $langs
     * @return list<MediaRow>
     */
    public function all(array $langs, string $search = '', ?string $kind = null): array
    {
        $params = [];
        $conditions = [];
        if (trim($search) !== '') {
            $conditions[] = '`original_name` LIKE :q';
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
        }
        if ($kind !== null && in_array($kind, self::KINDS, true)) {
            $conditions[] = '`kind` = :kind';
            $params['kind'] = $kind;
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $alt = $this->altTexts();
        $out = [];
        foreach ($this->db->all('SELECT * FROM {media}' . $where . ' ORDER BY `created_at` DESC, `id` DESC', $params) as $row) {
            $out[] = $this->map($row, $alt[(int) $row['id']] ?? [], $langs);
        }
        return $out;
    }

    /**
     * @param list<string> $langs
     * @return MediaRow|null
     */
    public function find(int $id, array $langs = []): ?array
    {
        $row = $this->db->first('media', ['id' => $id]);
        if ($row === null) {
            return null;
        }
        $alt = $this->altTexts($id);
        return $this->map($row, $alt[$id] ?? [], $langs);
    }

    /**
     * Stores an uploaded file. Returns the new id, or the reason it was refused.
     *
     * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file one entry of $_FILES
     * @return array{id: int}|array{error: string}
     */
    public function store(array $file, ?int $userId, ?int $replaceId = null): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return ['error' => 'too_large'];
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'upload_failed'];
        }
        $tmp = $file['tmp_name'];
        if (self::isPdf($tmp)) {
            return $this->storeDocument($tmp, is_string($file['name'] ?? null) ? $file['name'] : 'document.pdf', $userId, $replaceId);
        }
        $inspected = self::inspect($tmp);
        if (isset($inspected['error'])) {
            return ['error' => $inspected['error']];
        }
        ['type' => $type, 'extension' => $extension, 'mime' => $mime, 'width' => $width, 'height' => $height] = $inspected;
        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $target = Paths::publicDir('uploads/' . $filename);
        if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
            return ['error' => 'not_writable'];
        }
        // Re-encode: EXIF (including GPS), colour profiles and appended data never reach the web root.
        if (!self::reencode($tmp, $target, $type, $width, $height)) {
            return ['error' => 'not_an_image'];
        }
        $size = @getimagesize($target);
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $data = [
            'kind' => 'image',
            'filename' => $filename,
            'original_name' => mb_substr(self::safeName(is_string($file['name'] ?? null) ? $file['name'] : $filename), 0, 190),
            'mime' => $mime,
            'extension' => $extension,
            'size' => (int) filesize($target),
            'width' => $size !== false ? (int) $size[0] : $width,
            'height' => $size !== false ? (int) $size[1] : $height,
            'pages' => 0,
            'hash' => (string) hash_file('sha256', $target),
            'updated_at' => $now,
        ];
        return $this->persist($data, $target, $userId, $replaceId, $now);
    }

    /**
     * Is this file a PDF? The first bytes must be the PDF signature and finfo must agree.
     */
    public static function isPdf(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $head = (string) fread($handle, 5);
        fclose($handle);
        if ($head !== '%PDF-') {
            return false;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo !== false ? finfo_file($finfo, $path) : false;
            return $mime === 'application/pdf';
        }
        return true;
    }

    /**
     * Number of pages of a PDF, counted from its page objects (0 when it cannot be told, e.g. compressed object
     * streams). Only shown as information on the publication page.
     */
    public static function pdfPages(string $path): int
    {
        $data = @file_get_contents($path, false, null, 0, 20 * 1024 * 1024);
        if (!is_string($data)) {
            return 0;
        }
        $count = preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', $data);
        return is_int($count) ? min(65535, $count) : 0;
    }

    /**
     * @return array{id: int}|array{error: string}
     */
    private function storeDocument(string $tmp, string $originalName, ?int $userId, ?int $replaceId): array
    {
        $size = (int) filesize($tmp);
        if ($size > self::MAX_DOCUMENT_BYTES) {
            return ['error' => 'too_large'];
        }
        $filename = bin2hex(random_bytes(12)) . '.pdf';
        $target = Paths::publicDir('uploads/' . $filename);
        if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
            return ['error' => 'not_writable'];
        }
        if (!@move_uploaded_file($tmp, $target) && !@copy($tmp, $target)) {
            return ['error' => 'not_writable'];
        }
        @chmod($target, 0644);
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $name = self::safeName($originalName);
        return $this->persist([
            'kind' => 'document',
            'filename' => $filename,
            'original_name' => mb_substr(str_ends_with(strtolower($name), '.pdf') ? $name : $name . '.pdf', 0, 190),
            'mime' => 'application/pdf',
            'extension' => 'pdf',
            'size' => $size,
            'width' => 0,
            'height' => 0,
            'pages' => self::pdfPages($target),
            'hash' => (string) hash_file('sha256', $target),
            'updated_at' => $now,
        ], $target, $userId, $replaceId, $now);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id: int}|array{error: string}
     */
    private function persist(array $data, string $target, ?int $userId, ?int $replaceId, string $now): array
    {
        if ($replaceId !== null) {
            $old = $this->db->first('media', ['id' => $replaceId]);
            if ($old === null) {
                @unlink($target);
                return ['error' => 'upload_failed'];
            }
            // A cover must stay an image and a publication file a PDF: replacing keeps the kind.
            if ($old['kind'] !== $data['kind']) {
                @unlink($target);
                return ['error' => 'kind_mismatch'];
            }
            $this->db->update('media', $data, ['id' => $replaceId]);
            $this->deleteFile((string) $old['filename']);
            return ['id' => $replaceId];
        }
        $data['uploaded_by'] = $userId;
        $data['created_at'] = $now;
        return ['id' => $this->db->insert('media', $data)];
    }

    /**
     * What a file really is, read from its content — never from its name or the type the browser claims.
     * A PHP script named "photo.jpg" fails here, and so does anything that is not PNG, JPEG or WebP.
     *
     * @return array{error: string}|array{type: int, extension: string, mime: string, width: int, height: int}
     */
    public static function inspect(string $path): array
    {
        if (!is_file($path)) {
            return ['error' => 'upload_failed'];
        }
        if (filesize($path) > self::MAX_BYTES) {
            return ['error' => 'too_large'];
        }
        $info = @getimagesize($path);
        if ($info === false || !isset(self::TYPES[$info[2]])) {
            return ['error' => 'not_an_image'];
        }
        [$extension, $mime] = self::TYPES[$info[2]];
        [$width, $height] = [(int) $info[0], (int) $info[1]];
        if ($width < 1 || $height < 1 || $width > self::MAX_PIXELS * 2 || $height > self::MAX_PIXELS * 2) {
            return ['error' => 'not_an_image'];
        }
        if ($width * $height > self::MAX_MEGAPIXELS * 1000000 || !self::fitsInMemory($width, $height)) {
            return ['error' => 'too_many_pixels'];
        }
        return ['type' => $info[2], 'extension' => $extension, 'mime' => $mime, 'width' => $width, 'height' => $height];
    }

    /** @param array<string, string> $alt language => alt text (unknown languages are ignored) */
    public function saveAlt(int $id, array $alt): void
    {
        $known = array_map(static fn (array $row): string => (string) $row['code'], $this->db->select('languages', [], ['code']));
        foreach ($alt as $lang => $text) {
            if (!in_array($lang, $known, true)) {
                continue;
            }
            $value = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $text)), 0, 200);
            $existing = $this->db->first('media_translations', ['media_id' => $id, 'lang_code' => $lang]);
            if ($existing === null) {
                $this->db->insert('media_translations', ['media_id' => $id, 'lang_code' => $lang, 'alt' => $value]);
            } else {
                $this->db->update('media_translations', ['alt' => $value], ['id' => (int) $existing['id']]);
            }
        }
    }

    public function delete(int $id): bool
    {
        $row = $this->db->first('media', ['id' => $id]);
        if ($row === null) {
            return false;
        }
        $this->db->delete('media', ['id' => $id]);
        $this->deleteFile((string) $row['filename']);
        return true;
    }

    /**
     * Where a file is used: settings (logo, favicon, share image), covers, galleries, publication files, partner
     * logos, page and section images, and links inside rich text.
     *
     * @return list<string> human-readable places ("covers:2")
     */
    public function usage(int $id, string $url, Settings $settings): array
    {
        $places = [];
        foreach (['appearance.logo' => 'logo', 'appearance.logo_dark' => 'logo_dark', 'appearance.favicon' => 'favicon', 'seo.og_image' => 'og_image'] as $key => $label) {
            if ($settings->string($key) === $url) {
                $places[] = $label;
            }
        }
        $counts = [
            'covers' => 'SELECT COUNT(*) FROM {entries} WHERE `cover_media_id` = :id',
            'files' => 'SELECT COUNT(*) FROM {entries} WHERE `file_media_id` = :id',
            'galleries' => 'SELECT COUNT(DISTINCT `entry_id`) FROM {entry_media} WHERE `media_id` = :id',
            'partners' => 'SELECT COUNT(*) FROM {partners} WHERE `logo_media_id` = :id',
            'expertise' => 'SELECT COUNT(*) FROM {expertise} WHERE `cover_media_id` = :id',
            'page_images' => 'SELECT COUNT(*) FROM {pages} WHERE `hero_media_id` = :id',
            'sections' => 'SELECT COUNT(*) FROM {page_sections} WHERE `media_id` = :id',
        ];
        foreach ($counts as $label => $sql) {
            $n = (int) $this->db->scalar($sql, ['id' => $id]);
            if ($n > 0) {
                $places[] = $label . ':' . $n;
            }
        }
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $url) . '%';
        // One placeholder per statement: a named parameter may only appear once.
        $texts = (int) $this->db->scalar("SELECT COUNT(DISTINCT `page_id`) FROM {page_translations} WHERE CONCAT_WS(' ', `body`, `intro`) LIKE :u", ['u' => $like])
            + (int) $this->db->scalar("SELECT COUNT(DISTINCT `expertise_id`) FROM {expertise_translations} WHERE CONCAT_WS(' ', `body`, `summary`) LIKE :u", ['u' => $like])
            + (int) $this->db->scalar("SELECT COUNT(DISTINCT `entry_id`) FROM {entry_translations} WHERE CONCAT_WS(' ', `body`, `summary`) LIKE :u", ['u' => $like]);
        if ($texts > 0) {
            $places[] = 'texts:' . $texts;
        }
        return $places;
    }

    public static function url(string $filename): string
    {
        return '/uploads/' . $filename;
    }

    private function deleteFile(string $filename): void
    {
        if (preg_match('/^[a-f0-9]{24}\.(png|jpg|webp|pdf)$/', $filename) !== 1) {
            return;
        }
        $file = Paths::publicDir('uploads/' . $filename);
        if (is_file($file)) {
            @unlink($file);
        }
        // Variants generated from this file (media/cache) become orphans; remove them too.
        $base = pathinfo($filename, PATHINFO_FILENAME);
        foreach (glob(Paths::publicDir('media/cache/' . $base . '-*.webp')) ?: [] as $variant) {
            @unlink($variant);
        }
    }

    /** Re-encodes through GD, scaling down anything wider or taller than MAX_PIXELS. */
    private static function reencode(string $source, string $target, int $type, int $width, int $height): bool
    {
        $image = match ($type) {
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };
        if ($image === false) {
            return false;
        }
        $scale = min(1.0, self::MAX_PIXELS / max($width, $height));
        if ($scale < 1.0) {
            $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            imagedestroy($image);
            if ($resized === false) {
                return false;
            }
            $image = $resized;
        }
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
        $ok = match ($type) {
            IMAGETYPE_PNG => imagepng($image, $target, 8),
            IMAGETYPE_JPEG => imagejpeg($image, $target, 86),
            IMAGETYPE_WEBP => imagewebp($image, $target, 86),
            default => false,
        };
        imagedestroy($image);
        return $ok && is_file($target);
    }

    /** True when decoding the image (about 5 bytes per pixel) leaves room under memory_limit. */
    private static function fitsInMemory(int $width, int $height): bool
    {
        $limit = ini_get('memory_limit');
        if ($limit === false || $limit === '' || $limit === '-1') {
            return true;
        }
        $bytes = (int) $limit * match (strtolower(substr($limit, -1))) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
        // The decoded image, a resized copy and some headroom for the rest of the request.
        return $width * $height * 5 * 2 + 16 * 1048576 < $bytes - memory_get_usage();
    }

    private static function safeName(string $name): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', basename($name));
        return trim($name) === '' ? 'image' : $name;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $alt
     * @param list<string> $langs
     * @return MediaRow
     */
    private function map(array $row, array $alt, array $langs): array
    {
        $texts = [];
        foreach ($langs as $lang) {
            $texts[$lang] = $alt[$lang] ?? '';
        }
        return [
            'id' => (int) $row['id'],
            'kind' => (string) $row['kind'],
            'filename' => (string) $row['filename'],
            'original_name' => (string) $row['original_name'],
            'mime' => (string) $row['mime'],
            'extension' => (string) $row['extension'],
            'size' => (int) $row['size'],
            'width' => (int) $row['width'],
            'height' => (int) $row['height'],
            'pages' => (int) $row['pages'],
            'url' => self::url((string) $row['filename']),
            'created_at' => (string) $row['created_at'],
            'alt' => $texts === [] ? $alt : $texts,
        ];
    }

    /** @return array<int, array<string, string>> media id => language => alt */
    private function altTexts(?int $id = null): array
    {
        $rows = $id === null
            ? $this->db->all('SELECT * FROM {media_translations}')
            : $this->db->all('SELECT * FROM {media_translations} WHERE `media_id` = :id', ['id' => $id]);
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['media_id']][(string) $row['lang_code']] = (string) $row['alt'];
        }
        return $out;
    }
}
