<?php

declare(strict_types=1);

/*
 * Front controller. The web server's document root is this directory (standard layout: <app>/public;
 * split layout: public_html). Where the application lives is resolved from paths.php — no relative paths.
 */

/** @var array{layout: string, app_dir: string} $deploy */
$deploy = require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';
// Same rule as \Gate\Core\Paths::appRootFor(), which cannot be autoloaded before the root is known.
$root = $deploy['layout'] === 'split'
    ? dirname(__DIR__) . DIRECTORY_SEPARATOR . $deploy['app_dir']
    : dirname(__DIR__);

if (!is_file($root . '/vendor/autoload.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "The application could not be loaded: check paths.php (layout) and that vendor/ was uploaded.\n";
    exit;
}

require $root . '/vendor/autoload.php';

\Gate\Core\Paths::useRoot(\Gate\Core\Paths::appRootFor(__DIR__, $deploy['layout'], $deploy['app_dir']));
\Gate\Core\Paths::usePublic(__DIR__);

header_remove('X-Powered-By');
$config = \Gate\Core\Config::load();
ini_set('display_errors', $config->bool('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('UTC');

/** @var list<string> $trusted */
$trusted = array_values(array_filter((array) $config->get('trusted_proxies', []), 'is_string'));

$app = new \Gate\Core\App($config);
// finish(): sends the response, then runs deferred work such as email delivery without keeping the visitor waiting.
$app->finish($app->handle(\Gate\Http\Request::fromGlobals($trusted)));
