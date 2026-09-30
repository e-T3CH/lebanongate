<?php

declare(strict_types=1);

namespace BMMatic\Core;

/** Time source; injectable so rate limits, sessions and TOTP are testable. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
