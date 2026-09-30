<?php

declare(strict_types=1);

namespace Gate\Support;

/** Minimal HTTP contract, so providers can be tested and pointed at a mock without touching the network. */
interface HttpClient
{
    /** @param array<string, string> $headers */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResponse;
}
