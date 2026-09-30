<?php

declare(strict_types=1);

namespace Gate\Core;

/** Time source; injectable so rate limits, sessions and TOTP are testable. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
