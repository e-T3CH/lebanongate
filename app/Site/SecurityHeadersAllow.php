<?php

declare(strict_types=1);

namespace Gate\Site;

/** Narrow view of SecurityHeaders::allow() for code that may add CSP sources (analytics after consent). */
interface SecurityHeadersAllow
{
    public function allow(string $directive, string $source): void;
}
