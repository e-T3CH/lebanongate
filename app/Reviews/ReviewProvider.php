<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

/**
 * Where reviews come from. Three implementations sit behind this contract — the Google Business Profile API (the
 * full history), the Google Places API (at most five reviews) and a manual JSON/CSV import — so the sync, the admin
 * screen and the website never need to know which one is active.
 */
interface ReviewProvider
{
    /** Stored in settings and in the sync log: business_profile | places | manual. */
    public function key(): string;

    /** Everything it needs is filled in (keys, ids, tokens). */
    public function isConfigured(): bool;

    /**
     * What this provider can and cannot do, shown on the Connection card.
     *
     * @return array{max_reviews: ?int, note: string, needs_oauth: bool}
     */
    public function limits(): array;

    /**
     * Fetches the reviews. Never throws for an expected problem (no access yet, expired token, rate limit):
     * those come back as a ProviderResult with an error, so the panel can explain them and the site keeps working.
     */
    public function fetch(): ProviderResult;
}
