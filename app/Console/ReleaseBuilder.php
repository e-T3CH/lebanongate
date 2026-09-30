<?php

declare(strict_types=1);

namespace Gate\Console;

use Gate\Core\Paths;

/**
 * build:release — a deployable zip for the standard or split layout:
 * production vendor/ (composer install --no-dev --optimize-autoloader), built assets, no tests, tools,
 * node_modules, .git, config.local.php or storage contents.
 */
final class ReleaseBuilder
{
    /** Copied from the project root into the application folder. */
    private const APP_ITEMS = ['app', 'config/app.php', 'config/admin-menu.php', 'config/permissions.php', 'config/service-icons.php', 'config/.htaccess', 'database', 'lang', 'bin/console', 'deploy', 'composer.json', 'composer.lock', 'README.md', 'SECURITY.md', 'MAINTENANCE.md', 'USER-GUIDE.md', 'USER-GUIDE.nl.md', 'docs'];
    /** Folders that get their own deny-all .htaccess when the application folder can be web-reachable. */
    private const PRIVATE_DIRS = ['app', 'bin', 'config', 'database', 'deploy', 'docs', 'lang', 'storage', 'vendor'];
    private const DENY_ALL = "# Private: never web-accessible.\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
    /** Never allowed in a release (checked after staging). */
    private const FORBIDDEN = ['tests', 'tools', 'node_modules', '.git', 'config/config.local.php', 'mockups', 'design', 'phpunit.xml', 'phpunit.xml.dist', 'phpstan.neon.dist', 'bin/dev-router.php', '.env', 'resources', 'app/DesignCheck', 'app/Views/design-check'];
    /** Development-only parts of app/ and public/ (the /design-check gallery and the visual-check harness). */
    private const DEV_ONLY_APP = ['app/DesignCheck', 'app/Views/design-check'];
    private const DEV_ONLY_WEB = ['assets/js/state.js', 'assets/js/design-check.js', 'assets/css/design-check.css'];

    /** @param resource $out */
    public function __construct(private $out)
    {
    }

    /**
     * @param array<string, string> $options layout, app-dir, web-dir, output, composer, keep
     */
    public function build(array $options): int
    {
        $layout = $options['layout'] ?? '';
        if (!in_array($layout, ['standard', 'split', 'webroot'], true)) {
            throw new \InvalidArgumentException('Use --layout=standard, --layout=split or --layout=webroot');
        }
        $appDirName = $options['app-dir'] ?? 'gate-app';
        Paths::appRootFor(Paths::root('public'), 'split', $appDirName); // validates the folder name
        $output = rtrim($options['output'] ?? Paths::root('build'), '/\\');
        $composer = $this->findComposer($options['composer'] ?? '');
        $root = Paths::root();

        foreach (['public/assets/css/core.css', 'public/assets/css/admin.css', 'public/assets/js/app.js', 'public/assets/manifest.json'] as $asset) {
            if (!is_file($root . DIRECTORY_SEPARATOR . $asset)) {
                throw new \RuntimeException('Built asset missing: ' . $asset . ' (run node build.mjs in tools/assets first).');
            }
        }

        $stamp = gmdate('Ymd-His');
        $stage = $output . DIRECTORY_SEPARATOR . 'stage-' . $layout . '-' . $stamp;
        // webroot: the zip holds the application itself (no wrapper folder), to be unpacked straight into the web root.
        $top = $layout === 'standard' ? $stage . DIRECTORY_SEPARATOR . 'gate-lebanon' : $stage;
        $appDir = $layout === 'split' ? $stage . DIRECTORY_SEPARATOR . $appDirName : $top;
        // split: the web folder is named like the host names it (public_html on cPanel, httpd.www on one.com).
        $webDirName = $options['web-dir'] ?? 'public_html';
        Paths::appRootFor(Paths::root('public'), 'split', $webDirName); // same rules as the application folder name
        $webDir = $layout === 'split' ? $stage . DIRECTORY_SEPARATOR . $webDirName : $appDir . DIRECTORY_SEPARATOR . 'public';
        $this->say('Staging ' . $layout . ' layout in ' . $stage);

        foreach (self::APP_ITEMS as $item) {
            $source = $root . DIRECTORY_SEPARATOR . $item;
            if (file_exists($source)) {
                $this->copy($source, $appDir . DIRECTORY_SEPARATOR . $item);
            }
        }
        if ($layout !== 'split') {
            $this->copy($root . DIRECTORY_SEPARATOR . '.htaccess', $appDir . DIRECTORY_SEPARATOR . '.htaccess');
        }
        // storage skeleton: protection file and empty folders only
        $this->copy($root . '/storage/.htaccess', $appDir . '/storage/.htaccess');
        foreach (['sessions', 'logs', 'cache'] as $dir) {
            $this->mkdir($appDir . '/storage/' . $dir);
            file_put_contents($appDir . '/storage/' . $dir . '/.gitkeep', '');
        }
        // web root: public/ without uploaded files, plus the layout file
        foreach (scandir($root . '/public') ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'uploads') {
                continue;
            }
            $this->copy($root . '/public/' . $entry, $webDir . DIRECTORY_SEPARATOR . $entry);
        }
        $this->copy($root . '/public/uploads/.htaccess', $webDir . '/uploads/.htaccess');
        foreach (self::DEV_ONLY_APP as $item) {
            $this->remove($appDir . DIRECTORY_SEPARATOR . $item);
        }
        foreach (self::DEV_ONLY_WEB as $item) {
            $this->remove($webDir . DIRECTORY_SEPARATOR . $item);
        }
        // webroot works like standard (the app is the parent of public/); only where it is unpacked differs.
        file_put_contents($webDir . '/paths.php', self::pathsFile($layout === 'webroot' ? 'standard' : $layout, $appDirName));

        $this->say('Installing production dependencies (composer install --no-dev --optimize-autoloader)');
        $this->composerInstall($composer, $appDir);
        if ($layout !== 'split') {
            // The application folder may be web-reachable (webroot layout, or a standard install whose document root
            // could not be set to public/): every private folder refuses requests on its own, not only through the
            // rewrite rules in the root .htaccess.
            foreach (self::PRIVATE_DIRS as $dir) {
                if (is_dir($appDir . DIRECTORY_SEPARATOR . $dir) && !is_file($appDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . '.htaccess')) {
                    file_put_contents($appDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . '.htaccess', self::DENY_ALL);
                }
            }
        }

        $this->assertClean($appDir);
        foreach (self::DEV_ONLY_WEB as $item) {
            if (file_exists($webDir . DIRECTORY_SEPARATOR . $item)) {
                throw new \RuntimeException('Release contains a development-only asset: ' . $item);
            }
        }
        if (!is_file($webDir . '/assets/css/core.css') || !is_file($appDir . '/vendor/autoload.php')) {
            throw new \RuntimeException('Release incomplete (assets or vendor missing).');
        }

        $zip = $output . DIRECTORY_SEPARATOR . 'gate-lebanon-' . $layout . '-' . $stamp . '.zip';
        $count = $this->zip($stage, $zip);
        if (!isset($options['keep'])) {
            $this->remove($stage);
        }
        $this->say(sprintf('Created %s (%d files, %.1f MB)', $zip, $count, filesize($zip) / 1048576));
        return 0;
    }

    public static function pathsFile(string $layout, string $appDir): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\n// Deploy layout written by build:release. See README.md.\nreturn [\n    'layout' => " . var_export($layout, true) . ",\n    'app_dir' => " . var_export($appDir, true) . ",\n];\n";
    }

    /** @return list<string> forbidden paths found under $appDir */
    public static function forbiddenIn(string $appDir): array
    {
        $found = [];
        foreach (self::FORBIDDEN as $item) {
            if (file_exists($appDir . DIRECTORY_SEPARATOR . $item)) {
                $found[] = $item;
            }
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $name = $file->getFilename();
            if ($name === 'node_modules' || $name === '.git' || $name === 'config.local.php' || $name === 'installed.lock' || ($file->isFile() && str_ends_with($name, '.zip'))) {
                $found[] = substr($file->getPathname(), strlen($appDir) + 1);
            }
        }
        return $found;
    }

    private function assertClean(string $appDir): void
    {
        $found = self::forbiddenIn($appDir);
        if ($found !== []) {
            throw new \RuntimeException('Release contains forbidden paths: ' . implode(', ', array_slice($found, 0, 10)));
        }
    }

    private function findComposer(string $option): string
    {
        $candidates = array_filter([$option, (string) getenv('COMPOSER_PHAR')]);
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        $finder = DIRECTORY_SEPARATOR === '\\' ? 'where composer.phar composer 2>NUL' : 'command -v composer 2>/dev/null';
        $found = trim((string) shell_exec($finder));
        if ($found !== '') {
            return strtok($found, "\r\n") ?: $found;
        }
        throw new \RuntimeException('Composer not found: pass --composer=path/to/composer.phar or set COMPOSER_PHAR.');
    }

    private function composerInstall(string $composer, string $cwd): void
    {
        $command = str_ends_with(strtolower($composer), '.phar')
            ? [PHP_BINARY, $composer]
            : [$composer];
        $command = array_merge($command, ['install', '--no-dev', '--optimize-autoloader', '--classmap-authoritative', '--no-interaction', '--no-progress', '--no-scripts']);
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if (!is_resource($process)) {
            throw new \RuntimeException('Could not start composer.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new \RuntimeException("composer install failed:\n" . $stdout . $stderr);
        }
    }

    private function zip(string $sourceDir, string $zipFile): int
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS));
        $count = 0;
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create ' . $zipFile);
            }
            foreach ($files as $file) {
                /** @var \SplFileInfo $file */
                $zip->addFile($file->getPathname(), str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1)));
                $count++;
            }
            $zip->close();
            return $count;
        }
        // ext-zip missing: the phar extension writes zip archives too.
        $archive = new \PharData($zipFile, 0, null, \Phar::ZIP);
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $archive->addFile($file->getPathname(), str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1)));
            $count++;
        }
        return $count;
    }

    private function copy(string $source, string $target): void
    {
        if (is_dir($source)) {
            $this->mkdir($target);
            foreach (scandir($source) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->copy($source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry);
                }
            }
            return;
        }
        $this->mkdir(dirname($target));
        if (!copy($source, $target)) {
            throw new \RuntimeException('Copy failed: ' . $source);
        }
    }

    private function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create ' . $dir);
        }
    }

    private function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->remove($path . DIRECTORY_SEPARATOR . $entry);
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    private function say(string $line): void
    {
        fwrite($this->out, $line . PHP_EOL);
    }
}
