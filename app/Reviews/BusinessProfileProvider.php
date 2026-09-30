<?php

declare(strict_types=1);

namespace Gate\Reviews;

use Gate\Services\Settings;
use Gate\Support\HttpClient;

/**
 * Google Business Profile API: the full review history of one location.
 *
 * Google gives a refresh token once, at the end of the OAuth consent; it is stored encrypted and exchanged for a
 * short-lived access token on every sync. Two answers are expected rather than exceptional and get their own state
 * in the panel:
 *  - the API is enabled but the project has no approved access yet (Google reviews every request for this API):
 *    reason "no_access";
 *  - the refresh token was withdrawn or expired (password change, revoked app): reason "token_expired", which the
 *    panel offers to fix with "Connect again".
 */
final class BusinessProfileProvider implements ReviewProvider
{
    public const OAUTH_SCOPE = 'https://www.googleapis.com/auth/business.manage';
    public const OAUTH_BASE = 'https://oauth2.googleapis.com';
    private const CONSENT_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const PAGE_SIZE = 50;
    private const MAX_PAGES = 40;

    public function __construct(
        private readonly Settings $settings,
        private readonly HttpClient $http,
        private readonly string $oauthBase = self::OAUTH_BASE,
        private readonly string $apiBase = 'https://mybusiness.googleapis.com/v4',
    ) {
    }

    public function key(): string
    {
        return 'business_profile';
    }

    public function isConfigured(): bool
    {
        return $this->settings->string('google.oauth_client_id') !== ''
            && $this->settings->string('google.oauth_client_secret') !== ''
            && $this->settings->string('google.oauth_refresh_token') !== ''
            && $this->locationPath() !== '';
    }

    public function limits(): array
    {
        return ['max_reviews' => null, 'note' => 'business_profile', 'needs_oauth' => true];
    }

    public function fetch(): ProviderResult
    {
        if (!$this->isConfigured()) {
            return ProviderResult::failure('not_configured', 'Connect the Google Business Profile first.');
        }
        $token = $this->accessToken();
        if (!is_string($token)) {
            return $token;
        }
        $reviews = [];
        $rating = null;
        $count = null;
        $pageToken = '';
        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $url = $this->apiBase . '/' . trim($this->locationPath(), '/') . '/reviews?pageSize=' . self::PAGE_SIZE
                . ($pageToken !== '' ? '&pageToken=' . rawurlencode($pageToken) : '');
            $response = $this->http->request('GET', $url, ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']);
            if (!$response->ok()) {
                return self::errorFor($response->status, $response->body, $response->error);
            }
            $data = $response->json();
            if ($data === null) {
                return ProviderResult::failure('invalid_response', 'Google sent an answer this site could not read.');
            }
            $rating ??= isset($data['averageRating']) && is_numeric($data['averageRating']) ? (float) $data['averageRating'] : null;
            $count ??= isset($data['totalReviewCount']) ? (int) $data['totalReviewCount'] : null;
            foreach (is_array($data['reviews'] ?? null) ? $data['reviews'] : [] as $review) {
                $mapped = self::map(is_array($review) ? $review : []);
                if ($mapped !== null) {
                    $reviews[] = $mapped;
                }
            }
            $pageToken = is_string($data['nextPageToken'] ?? null) ? $data['nextPageToken'] : '';
            if ($pageToken === '') {
                break;
            }
        }
        return ProviderResult::success($reviews, $rating, $count);
    }

    /** The OAuth consent URL the admin screen sends the owner to. */
    public function authorizationUrl(string $redirectUri, string $state): string
    {
        // With a test endpoint configured, the consent screen is part of the mock as well.
        $consent = $this->oauthBase === self::OAUTH_BASE ? self::CONSENT_URL : $this->oauthBase . '/o/oauth2/v2/auth';
        return $consent . '?' . http_build_query([
            'client_id' => $this->settings->string('google.oauth_client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::OAUTH_SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /**
     * Exchanges the code from the consent screen for a refresh token.
     *
     * @return array{refresh_token: string}|array{error: string}
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = $this->http->request('POST', $this->oauthBase . '/token', ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query([
            'code' => $code,
            'client_id' => $this->settings->string('google.oauth_client_id'),
            'client_secret' => $this->settings->string('google.oauth_client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]));
        $data = $response->json();
        $refresh = is_string($data['refresh_token'] ?? null) ? $data['refresh_token'] : '';
        if (!$response->ok() || $refresh === '') {
            return ['error' => self::messageFrom($data, $response->error !== '' ? $response->error : 'Google did not return a refresh token.')];
        }
        return ['refresh_token' => $refresh];
    }

    /**
     * The accounts and locations this connection can read, for the picker on the Connection card.
     *
     * @return array{accounts: list<array{name: string, label: string}>, error?: string}
     */
    public function accounts(): array
    {
        $token = $this->accessToken();
        if (!is_string($token)) {
            return ['accounts' => [], 'error' => $token->errorMessage];
        }
        $response = $this->http->request('GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', ['Authorization' => 'Bearer ' . $token]);
        $data = $response->json();
        if (!$response->ok() || $data === null) {
            return ['accounts' => [], 'error' => self::messageFrom($data, 'Could not read the accounts.')];
        }
        $accounts = [];
        foreach (is_array($data['accounts'] ?? null) ? $data['accounts'] : [] as $account) {
            if (is_array($account) && is_string($account['name'] ?? null)) {
                $accounts[] = ['name' => $account['name'], 'label' => is_string($account['accountName'] ?? null) ? $account['accountName'] : $account['name']];
            }
        }
        return ['accounts' => $accounts];
    }

    /** A fresh access token, or the ProviderResult explaining why there is none. */
    private function accessToken(): string|ProviderResult
    {
        $response = $this->http->request('POST', $this->oauthBase . '/token', ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query([
            'client_id' => $this->settings->string('google.oauth_client_id'),
            'client_secret' => $this->settings->string('google.oauth_client_secret'),
            'refresh_token' => $this->settings->string('google.oauth_refresh_token'),
            'grant_type' => 'refresh_token',
        ]));
        $data = $response->json();
        $token = is_string($data['access_token'] ?? null) ? $data['access_token'] : '';
        if ($response->ok() && $token !== '') {
            return $token;
        }
        $error = is_string($data['error'] ?? null) ? $data['error'] : '';
        if ($error === 'invalid_grant' || $response->status === 400 || $response->status === 401) {
            return ProviderResult::failure('token_expired', 'The connection with Google expired. Connect again to restore it.');
        }
        return ProviderResult::failure('network', self::messageFrom($data, $response->error !== '' ? $response->error : 'Could not reach Google.'));
    }

    private function locationPath(): string
    {
        $location = trim($this->settings->string('google.location_id'));
        return preg_match('#^accounts/[^/\s\[\]]+/locations/[^/\s\[\]]+$#', $location) === 1 ? $location : '';
    }

    /** @param array<string, mixed> $review */
    private static function map(array $review): ?ReviewData
    {
        $stars = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
        $reviewer = is_array($review['reviewer'] ?? null) ? $review['reviewer'] : [];
        $reply = is_array($review['reviewReply'] ?? null) ? $review['reviewReply'] : [];
        return ReviewData::fromArray([
            'external_id' => is_string($review['reviewId'] ?? null) ? $review['reviewId'] : (is_string($review['name'] ?? null) ? $review['name'] : ''),
            'reviewer_name' => is_string($reviewer['displayName'] ?? null) ? $reviewer['displayName'] : '',
            'photo_url' => is_string($reviewer['profilePhotoUrl'] ?? null) ? $reviewer['profilePhotoUrl'] : '',
            'rating' => $stars[(string) ($review['starRating'] ?? '')] ?? 0,
            'text' => is_string($review['comment'] ?? null) ? $review['comment'] : '',
            'review_date' => is_string($review['createTime'] ?? null) ? $review['createTime'] : '',
            'owner_reply' => is_string($reply['comment'] ?? null) ? $reply['comment'] : '',
            'owner_reply_at' => is_string($reply['updateTime'] ?? null) ? $reply['updateTime'] : '',
        ]);
    }

    private static function errorFor(int $status, string $body, string $transportError): ProviderResult
    {
        $data = json_decode($body, true);
        $message = self::messageFrom(is_array($data) ? $data : null, $transportError !== '' ? $transportError : 'Google refused the request.');
        if ($status === 401) {
            return ProviderResult::failure('token_expired', 'The connection with Google expired. Connect again to restore it.');
        }
        if ($status === 403) {
            // Access to this API is granted per project; until Google approves it, every call comes back 403.
            return ProviderResult::failure('no_access', 'Google has not approved API access for this project yet: ' . $message);
        }
        if ($status === 429) {
            return ProviderResult::failure('rate_limited', 'Google is rate limiting the sync. The next run will try again.');
        }
        return ProviderResult::failure($status === 0 ? 'network' : 'invalid_response', $message);
    }

    /** @param array<string, mixed>|null $data */
    private static function messageFrom(?array $data, string $fallback): string
    {
        $error = is_array($data['error'] ?? null) ? $data['error'] : null;
        if ($error !== null && is_string($error['message'] ?? null)) {
            return $error['message'];
        }
        if (is_string($data['error_description'] ?? null)) {
            return $data['error_description'];
        }
        return $fallback;
    }
}
