<?php

declare(strict_types=1);

namespace Gate\Support;

/** One HTTP answer: the status, the body, and the transport error when there was no answer at all. */
final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly string $error = '',
    ) {
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** @return array<string, mixed>|null the body as JSON, or null when it is not valid JSON */
    public function json(): ?array
    {
        if ($this->body === '') {
            return null;
        }
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }
}
