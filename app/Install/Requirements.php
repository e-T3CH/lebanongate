<?php

declare(strict_types=1);

namespace BMMatic\Install;

use BMMatic\Core\Paths;
use BMMatic\Security\PasswordHasher;
use BMMatic\Support\Http;
use BMMatic\Support\HttpClient;

/**
 * Step 1: server requirements.
 *
 * @phpstan-type Check array{key: string, ok: bool, required: bool, detail: string}
 */
final class Requirements
{
    public const MIN_PHP = '8.2.0';

    /**
     * Files that must never be downloadable. On hosts where the whole application lives inside the web root
     * (the "webroot" layout, e.g. one.com), only the .htaccess files keep them private — so the installer checks it.
     */
    public const PRIVATE_PROBES = ['/composer.json', '/vendor/composer/installed.json', '/storage/logs/.gitkeep', '/config/.htaccess'];

    /**
     * @param string|null $baseUrl the site's own address; when given, the installer asks its own server for files
     *                             that must be private and refuses to install when one is served
     * @return list<Check>
     */
    public static function check(bool $https, ?string $baseUrl = null, ?HttpClient $http = null): array
    {
        $checks = [];
        $checks[] = ['key' => 'php', 'ok' => version_compare(PHP_VERSION, self::MIN_PHP, '>='), 'required' => true, 'detail' => PHP_VERSION];
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'sodium', 'json', 'ctype', 'fileinfo', 'dom', 'libxml', 'gd'] as $ext) {
            $checks[] = ['key' => 'ext_' . $ext, 'ok' => extension_loaded($ext), 'required' => true, 'detail' => $ext];
        }
        foreach (['intl', 'curl'] as $ext) {
            $checks[] = ['key' => 'ext_' . $ext, 'ok' => extension_loaded($ext), 'required' => false, 'detail' => $ext];
        }
        $checks[] = ['key' => 'argon2id', 'ok' => PasswordHasher::isSupported(), 'required' => true, 'detail' => 'PASSWORD_ARGON2ID'];
        foreach (['config' => Paths::config(), 'storage' => Paths::storage(), 'storage_sessions' => Paths::storage('sessions'), 'storage_logs' => Paths::storage('logs'), 'storage_cache' => Paths::storage('cache'), 'uploads' => Paths::publicDir('uploads')] as $key => $dir) {
            $checks[] = ['key' => 'writable_' . $key, 'ok' => is_dir($dir) && is_writable($dir), 'required' => true, 'detail' => self::relative($dir)];
        }
        $checks[] = ['key' => 'https', 'ok' => $https, 'required' => false, 'detail' => $https ? 'HTTPS' : 'HTTP'];
        if ($baseUrl !== null) {
            $probe = self::probePrivateFiles($baseUrl, $http ?? new Http(3));
            $checks[] = $probe === null
                // The server could not reach itself (some hosts block that): not a failure, but check by hand.
                ? ['key' => 'private_unverified', 'ok' => false, 'required' => false, 'detail' => rtrim($baseUrl, '/') . '/composer.json']
                : ['key' => 'private_hidden', 'ok' => $probe === [], 'required' => true, 'detail' => implode(', ', $probe)];
        }
        return $checks;
    }

    /**
     * @return list<string>|null the private files the server hands out (empty: all hidden), or null when the server
     *                           could not be reached at all
     */
    public static function probePrivateFiles(string $baseUrl, HttpClient $http): ?array
    {
        $exposed = [];
        $answered = false;
        foreach (self::PRIVATE_PROBES as $path) {
            $response = $http->request('GET', rtrim($baseUrl, '/') . $path);
            if ($response->status === 0) {
                // No answer at all: the server cannot reach itself, and asking again would only add waiting time.
                break;
            }
            $answered = true;
            // A 200 with the file's own content is a leak; the site's HTML 404 page is not.
            if ($response->ok() && !str_contains(strtolower(substr($response->body, 0, 200)), '<!doctype html')) {
                $exposed[] = $path;
            }
        }
        return $answered ? $exposed : null;
    }

    /** @param list<Check> $checks */
    public static function passes(array $checks): bool
    {
        foreach ($checks as $c) {
            if ($c['required'] && !$c['ok']) {
                return false;
            }
        }
        return true;
    }

    private static function relative(string $dir): string
    {
        $root = Paths::root();
        return str_starts_with($dir, $root) ? ltrim(str_replace('\\', '/', substr($dir, strlen($root))), '/') . '/' : $dir;
    }
}
