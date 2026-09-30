<?php

declare(strict_types=1);

/*
 * Router for PHP's built-in server (development only):
 *   php -S 127.0.0.1:8080 -t public bin/dev-router.php
 * Existing files under public/ are served as they are; everything else goes to the front controller.
 */
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = realpath(__DIR__ . '/../public' . $path);
$public = realpath(__DIR__ . '/../public');
if ($path !== '/' && $file !== false && $public !== false && str_starts_with($file, $public) && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require __DIR__ . '/../public/index.php';
