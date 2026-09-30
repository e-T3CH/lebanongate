<?php

declare(strict_types=1);

namespace Gate\Http;

final class Response
{
    /** @var array<string, string> */
    private array $headers = [];
    /** @var list<array{name: string, value: string, options: array<string, mixed>}> */
    private array $cookies = [];
    /** A file sent from disk in chunks after the headers (large downloads never pass through memory). */
    private ?string $file = null;

    public function __construct(private string $body = '', private int $status = 200)
    {
    }

    public static function html(string $body, int $status = 200): self
    {
        return (new self($body, $status))->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return (new self('', $status))->withHeader('Location', $location);
    }

    public static function text(string $body, int $status = 200): self
    {
        return (new self($body, $status))->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
        return (new self($body, $status))->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /** A download streamed from disk. $name is the file name the browser saves it as. */
    public static function file(string $path, string $name, string $type = 'application/octet-stream'): self
    {
        $response = (new self('', 200))
            ->withHeader('Content-Type', $type)
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Content-Disposition', 'attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"');
        $response->file = $path;
        return $response;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = str_replace(["\r", "\n"], '', $value);
        return $this;
    }

    public function withoutHeader(string $name): self
    {
        unset($this->headers[$name]);
        return $this;
    }

    /** @param array<string, mixed> $options expires, path, secure, httponly, samesite */
    public function withCookie(string $name, string $value, array $options): self
    {
        $this->cookies[] = ['name' => $name, 'value' => $value, 'options' => $options];
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** @return list<array{name: string, value: string, options: array<string, mixed>}> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            // 419 has no standard reason phrase; send one so proxies and logs show it clearly.
            if ($this->status === 419) {
                header('HTTP/1.1 419 Page Expired', true, 419);
            } else {
                http_response_code($this->status);
            }
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
            foreach ($this->cookies as $cookie) {
                /** @var array{expires?: int, path?: string, domain?: string, secure?: bool, httponly?: bool, samesite?: 'None'|'Lax'|'Strict'} $options */
                $options = $cookie['options'];
                setcookie($cookie['name'], $cookie['value'], $options);
            }
        }
        if ($this->file === null) {
            echo $this->body;
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $handle = fopen($this->file, 'rb');
        if ($handle === false) {
            return;
        }
        while (!feof($handle)) {
            echo (string) fread($handle, 1048576);
            flush();
        }
        fclose($handle);
    }

    /** The streamed file, if this is a download. */
    public function filePath(): ?string
    {
        return $this->file;
    }
}
