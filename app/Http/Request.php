<?php

declare(strict_types=1);

namespace Gate\Http;

use Gate\Core\Url;
use Gate\Security\IpAddress;

final class Request
{
    /** @var array<string, string> route parameters */
    private array $params = [];
    private string $ip;
    private bool $secure;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     * @param list<string> $trustedProxies
     * @param array<string, mixed> $files
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $server = [],
        private readonly array $cookies = [],
        array $trustedProxies = [],
        private readonly array $files = [],
        private readonly string $basePath = '',
    ) {
        $this->ip = IpAddress::client($server, $trustedProxies);
        $this->secure = self::detectSecure($server, $trustedProxies);
    }

    /** @param list<string> $trustedProxies */
    public static function fromGlobals(array $trustedProxies): self
    {
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $path = self::normalizePath(rawurldecode((string) parse_url($uri, PHP_URL_PATH)));
        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        // Installed in a folder (https://example.org/gate/): the application sees '/en/…', never '/gate/en/…'.
        $base = Url::detect($path, is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '');
        if ($base !== '') {
            $path = self::normalizePath(substr($path, strlen($base)));
        }
        return new self(
            strtoupper(is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET'),
            $path,
            $_GET,
            $_POST,
            $server,
            $_COOKIE,
            $trustedProxies,
            $_FILES,
            $base,
        );
    }

    public static function normalizePath(string $path): string
    {
        $path = '/' . ltrim(str_replace("\0", '', $path), '/');
        return (string) preg_replace('#/{2,}#', '/', $path);
    }

    public function method(): string
    {
        return $this->method;
    }

    /** The folder the site is installed in ('' at the domain root, '/gate' in a folder). */
    public function basePath(): string
    {
        return $this->basePath;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, string $default = ''): string
    {
        $v = $this->query[$key] ?? $default;
        return is_scalar($v) ? (string) $v : $default;
    }

    public function input(string $key, string $default = ''): string
    {
        $v = $this->post[$key] ?? $default;
        return is_scalar($v) ? (string) $v : $default;
    }

    /** @return list<string> */
    public function inputList(string $key): array
    {
        $v = $this->post[$key] ?? [];
        return is_array($v) ? array_values(array_map('strval', array_filter($v, 'is_scalar'))) : [];
    }

    /** @return array<string, string> a keyed array field such as text[site.footer.about] (non-scalar values dropped) */
    public function inputMap(string $key): array
    {
        $v = $this->post[$key] ?? [];
        $out = [];
        if (is_array($v)) {
            foreach ($v as $k => $item) {
                if (is_scalar($item)) {
                    $out[(string) $k] = (string) $item;
                }
            }
        }
        return $out;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $v = $this->server[$key] ?? null;
        return is_string($v) ? $v : null;
    }

    /**
     * One uploaded file, as PHP hands it over. Never trust its name or type: the media library reads the content.
     *
     * @return array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int}
     */
    public function file(string $key): array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || is_array($file['name'] ?? null)) {
            return ['error' => UPLOAD_ERR_NO_FILE];
        }
        /** @var array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file */
        return $file;
    }

    /** A fetch() call from the panel (quick toggles): answer with JSON instead of a redirect. */
    public function wantsJson(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'fetch'
            || str_contains(strtolower($this->header('Accept') ?? ''), 'application/json');
    }

    public function cookie(string $name): ?string
    {
        $v = $this->cookies[$name] ?? null;
        return is_string($v) ? $v : null;
    }

    public function server(string $key): ?string
    {
        $v = $this->server[$key] ?? null;
        return is_scalar($v) ? (string) $v : null;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function userAgent(): string
    {
        return mb_substr($this->header('User-Agent') ?? '', 0, 255);
    }

    public function queryString(): string
    {
        $qs = $this->server('QUERY_STRING') ?? '';
        return $qs === '' ? '' : '?' . $qs;
    }

    public function param(string $name, string $default = ''): string
    {
        return $this->params[$name] ?? $default;
    }

    /** @param array<string, string> $params */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    /**
     * @param array<string, mixed> $server
     * @param list<string> $trustedProxies
     */
    private static function detectSecure(array $server, array $trustedProxies): bool
    {
        $https = $server['HTTPS'] ?? '';
        if ((is_string($https) && $https !== '' && strtolower($https) !== 'off') || (string) ($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        $remote = is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : '';
        if ($trustedProxies !== [] && IpAddress::matchesAny($remote, $trustedProxies)) {
            $proto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';
            return is_string($proto) && strtolower(trim(explode(',', $proto)[0])) === 'https';
        }
        return false;
    }
}
