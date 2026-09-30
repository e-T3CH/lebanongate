<?php

declare(strict_types=1);

namespace Gate\Http;

final class HttpException extends \RuntimeException
{
    public function __construct(private readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status, $status);
    }

    public function status(): int
    {
        return $this->status;
    }
}
