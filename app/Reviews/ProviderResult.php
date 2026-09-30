<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

/**
 * What a provider returns: the reviews it could fetch, the totals Google reports for the location (never
 * recalculated from the reviews we happen to have), and — when something went wrong — a machine-readable reason
 * with a message for the panel.
 *
 * Reasons: not_configured, no_access (API not approved yet), token_expired, rate_limited, network, invalid_response.
 */
final class ProviderResult
{
    /**
     * @param list<ReviewData> $reviews
     */
    private function __construct(
        public readonly array $reviews,
        public readonly ?float $aggregateRating,
        public readonly ?int $aggregateCount,
        public readonly ?string $errorReason = null,
        public readonly string $errorMessage = '',
        public readonly bool $partial = false,
    ) {
    }

    /** @param list<ReviewData> $reviews */
    public static function success(array $reviews, ?float $rating = null, ?int $count = null, bool $partial = false): self
    {
        return new self($reviews, $rating, $count, null, '', $partial);
    }

    public static function failure(string $reason, string $message): self
    {
        return new self([], null, null, $reason, mb_substr($message, 0, 500));
    }

    public function failed(): bool
    {
        return $this->errorReason !== null;
    }
}
