<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

use BMMatic\Services\Settings;
use BMMatic\Support\HttpClient;

/**
 * Google Places API (New): an API key and a Place ID, no OAuth. It returns the location's rating and total review
 * count in full, but **at most five reviews** — that is Google's limit, not a bug, and the panel says so on the
 * Connection card so nobody goes looking for the missing ones.
 */
final class PlacesProvider implements ReviewProvider
{
    public const MAX_REVIEWS = 5;

    public function __construct(
        private readonly Settings $settings,
        private readonly HttpClient $http,
        private readonly string $apiBase = 'https://places.googleapis.com/v1',
    ) {
    }

    public function key(): string
    {
        return 'places';
    }

    public function isConfigured(): bool
    {
        return $this->settings->string('google.api_key') !== '' && $this->placeId() !== '';
    }

    public function limits(): array
    {
        return ['max_reviews' => self::MAX_REVIEWS, 'note' => 'places', 'needs_oauth' => false];
    }

    public function fetch(): ProviderResult
    {
        if (!$this->isConfigured()) {
            return ProviderResult::failure('not_configured', 'Fill in the API key and the Place ID first.');
        }
        $url = $this->apiBase . '/places/' . rawurlencode($this->placeId());
        $response = $this->http->request('GET', $url, [
            'X-Goog-Api-Key' => $this->settings->string('google.api_key'),
            'X-Goog-FieldMask' => 'rating,userRatingCount,reviews',
            'Accept' => 'application/json',
        ]);
        $data = $response->json();
        if (!$response->ok()) {
            $message = is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)
                ? $data['error']['message']
                : ($response->error !== '' ? $response->error : 'Google refused the request.');
            return match (true) {
                $response->status === 403 => ProviderResult::failure('no_access', 'The API key may not use the Places API (New): ' . $message),
                $response->status === 429 => ProviderResult::failure('rate_limited', 'Google is rate limiting the sync. The next run will try again.'),
                $response->status === 404 => ProviderResult::failure('invalid_response', 'Google does not know this Place ID.'),
                $response->status === 0 => ProviderResult::failure('network', $message),
                default => ProviderResult::failure('invalid_response', $message),
            };
        }
        if ($data === null) {
            return ProviderResult::failure('invalid_response', 'Google sent an answer this site could not read.');
        }
        $reviews = [];
        foreach (is_array($data['reviews'] ?? null) ? $data['reviews'] : [] as $review) {
            $mapped = self::map(is_array($review) ? $review : []);
            if ($mapped !== null) {
                $reviews[] = $mapped;
            }
        }
        $count = isset($data['userRatingCount']) ? (int) $data['userRatingCount'] : null;
        return ProviderResult::success(
            $reviews,
            isset($data['rating']) && is_numeric($data['rating']) ? (float) $data['rating'] : null,
            $count,
            // Partial: Google has more reviews than the five this API hands over.
            $count !== null && $count > count($reviews),
        );
    }

    private function placeId(): string
    {
        $id = trim($this->settings->string('google.place_id'));
        return preg_match('/^[A-Za-z0-9_\-]{10,190}$/', $id) === 1 ? $id : '';
    }

    /** @param array<string, mixed> $review */
    private static function map(array $review): ?ReviewData
    {
        $author = is_array($review['authorAttribution'] ?? null) ? $review['authorAttribution'] : [];
        $text = is_array($review['originalText'] ?? null) ? $review['originalText'] : (is_array($review['text'] ?? null) ? $review['text'] : []);
        return ReviewData::fromArray([
            'external_id' => is_string($review['name'] ?? null) ? $review['name'] : '',
            'reviewer_name' => is_string($author['displayName'] ?? null) ? $author['displayName'] : '',
            'photo_url' => is_string($author['photoUri'] ?? null) ? $author['photoUri'] : '',
            'rating' => (int) ($review['rating'] ?? 0),
            'text' => is_string($text['text'] ?? null) ? $text['text'] : '',
            'language' => is_string($text['languageCode'] ?? null) ? $text['languageCode'] : '',
            'review_date' => is_string($review['publishTime'] ?? null) ? $review['publishTime'] : '',
        ], 'places-');
    }
}
