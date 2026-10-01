<?php

declare(strict_types=1);

namespace Gate\Core;

/**
 * The folder the website is installed in, so the same code runs at https://example.org/ and at
 * https://example.org/any/folder/.
 *
 * The application works with root-relative paths ('/en/projects', '/assets/css/site.css', '/admin-x/login').
 * Request::fromGlobals() removes the folder from incoming paths; Url::to() adds it back to outgoing ones (e_url(),
 * View::asset(), redirects, image srcsets, rich text). Each path goes through Url::to() exactly once.
 */
final class Url
{
    private static string $base = '';

    /** Set from the request at the start of every request ('' at the domain root, '/gate' in a folder). */
    public static function setBase(string $base): void
    {
        $base = '/' . trim(str_replace('\\', '/', $base), '/');
        self::$base = $base === '/' ? '' : $base;
    }

    public static function base(): string
    {
        return self::$base;
    }

    /**
     * A root-relative path as the browser must request it: with the installation folder in front. Absolute URLs,
     * protocol-relative URLs, fragments, queries, mailto: and other values are returned unchanged.
     */
    public static function to(string $path): string
    {
        if (self::$base === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $path;
        }
        return self::$base . $path;
    }

    /**
     * The installation folder seen from a request: the folder of the front controller (SCRIPT_NAME), or the folder
     * above it when everything is unpacked in one folder and the root .htaccess routes requests into public/.
     */
    public static function detect(string $requestPath, string $scriptName): string
    {
        $dir = rtrim(str_replace('\\', '/', dirname(str_replace('\\', '/', $scriptName))), '/');
        if ($dir === '' || $dir === '.') {
            return '';
        }
        if ($requestPath === $dir || str_starts_with($requestPath, $dir . '/')) {
            return $dir;
        }
        if (str_ends_with($dir, '/public')) {
            $parent = substr($dir, 0, -strlen('/public'));
            if ($parent !== '' && ($requestPath === $parent || str_starts_with($requestPath, $parent . '/'))) {
                return $parent;
            }
        }
        return '';
    }
}
