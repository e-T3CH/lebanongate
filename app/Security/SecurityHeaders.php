<?php

declare(strict_types=1);

namespace BMMatic\Security;

use BMMatic\Http\Response;

/**
 * Security headers for every response. The CSP uses a per-request nonce for the few inline <script>/<style>
 * blocks (the motion head script and the theme colors); everything else is loaded from 'self'.
 */
final class SecurityHeaders implements \BMMatic\Site\SecurityHeadersAllow
{
    private readonly string $nonce;

    public function __construct(?string $nonce = null)
    {
        $this->nonce = $nonce ?? rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    public function nonce(): string
    {
        return $this->nonce;
    }

    /** @var array<string, list<string>> extra sources per directive (analytics after consent) */
    private array $extra = ['script-src' => [], 'connect-src' => [], 'img-src' => []];

    /** Allows an extra https origin for script-src, connect-src or img-src on this response. */
    public function allow(string $directive, string $source): void
    {
        if (!isset($this->extra[$directive]) || preg_match('#^https://(\*\.)?[a-z0-9.-]+$#', $source) !== 1) {
            throw new \InvalidArgumentException('CSP source not allowed: ' . $directive . ' ' . $source);
        }
        if (!in_array($source, $this->extra[$directive], true)) {
            $this->extra[$directive][] = $source;
        }
    }

    public function contentSecurityPolicy(bool $https): string
    {
        $add = fn (string $d): string => $this->extra[$d] === [] ? '' : ' ' . implode(' ', $this->extra[$d]);
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-" . $this->nonce . "'" . $add('script-src'),
            "style-src 'self' 'nonce-" . $this->nonce . "'",
            "img-src 'self' data:" . $add('img-src'),
            "font-src 'self'",
            "connect-src 'self'" . $add('connect-src'),
            "media-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "manifest-src 'self'",
        ];
        if ($https) {
            $directives[] = 'upgrade-insecure-requests';
        }
        return implode('; ', $directives);
    }

    /** @param bool $hsts send Strict-Transport-Security (only over HTTPS with "Force HTTPS" enabled) */
    public function apply(Response $response, bool $https, bool $hsts, bool $noStore): Response
    {
        $response
            ->withHeader('Content-Security-Policy', $this->contentSecurityPolicy($https))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none');
        if ($https && $hsts) {
            $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if ($noStore) {
            $response->withHeader('Cache-Control', 'no-store, max-age=0')->withHeader('Pragma', 'no-cache');
        }
        return $response;
    }
}
