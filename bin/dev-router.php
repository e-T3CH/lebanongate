<?php

declare(strict_types=1);

/*
 * Router for PHP's built-in server (development only):
 *   php -S 127.0.0.1:8080 -t public bin/dev-router.php
 * Existing files in the web root (-t) are served as they are; everything else goes to the front controller.
 * The web root is public/ in the standard layout and the separate web folder (public_html) in the split layout.
 */
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$public = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: realpath(__DIR__ . '/../public');
$file = $public !== false ? realpath($public . $path) : false;
if ($path !== '/' && $file !== false && $public !== false && str_starts_with($file, $public) && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require $public . '/index.php';
