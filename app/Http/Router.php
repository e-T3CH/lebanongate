<?php

declare(strict_types=1);

namespace Gate\Http;

/**
 * Minimal router: exact paths with {param} segments ([^/]+), GET/POST, named routes and flags
 * (e.g. "auth" for admin routes that need a signed-in user).
 *
 * @phpstan-type Handler callable(Request): Response
 * @phpstan-type RouteDef array{method: string, path: string, regex: string, handler: Handler, name: string, flags: list<string>}
 */
final class Router
{
    /** @var list<RouteDef> */
    private array $routes = [];

    /**
     * @param Handler $handler
     * @param list<string> $flags
     */
    public function get(string $path, callable $handler, string $name = '', array $flags = []): void
    {
        $this->add('GET', $path, $handler, $name, $flags);
    }

    /**
     * @param Handler $handler
     * @param list<string> $flags
     */
    public function post(string $path, callable $handler, string $name = '', array $flags = []): void
    {
        $this->add('POST', $path, $handler, $name, $flags);
    }

    /**
     * @param Handler $handler
     * @param list<string> $flags
     */
    public function add(string $method, string $path, callable $handler, string $name = '', array $flags = []): void
    {
        $regex = '#^' . preg_replace_callback('/\\\\\{([a-z_]+)\\\\\}/', static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)', preg_quote($path, '#')) . '$#';
        $this->routes[] = ['method' => $method, 'path' => $path, 'regex' => $regex, 'handler' => $handler, 'name' => $name, 'flags' => $flags];
    }

    /**
     * Every registered route (method, path, name, flags) — for the authorisation matrix test and route listings.
     *
     * @return list<array{method: string, path: string, name: string, flags: list<string>}>
     */
    public function routes(): array
    {
        return array_map(static fn (array $r): array => ['method' => $r['method'], 'path' => $r['path'], 'name' => $r['name'], 'flags' => $r['flags']], $this->routes);
    }

    /**
     * @return array{route: RouteDef, params: array<string, string>}|null
     * @throws HttpException 405 when the path exists for another method
     */
    public function match(string $method, string $path): ?array
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $m) !== 1) {
                continue;
            }
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                $allowed[] = $route['method'];
                continue;
            }
            $params = [];
            foreach ($m as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }
            return ['route' => $route, 'params' => $params];
        }
        if ($allowed !== []) {
            throw new HttpException(405);
        }
        return null;
    }

    public function has(string $name): bool
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, string> $params */
    public function path(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                return (string) preg_replace_callback('/\{([a-z_]+)\}/', static fn (array $m): string => rawurlencode($params[$m[1]] ?? ''), $route['path']);
            }
        }
        throw new \InvalidArgumentException('Unknown route: ' . $name);
    }
}
