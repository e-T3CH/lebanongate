<?php

declare(strict_types=1);

namespace Gate\Reviews;

use Gate\Services\Settings;
use Gate\Support\Http;
use Gate\Support\HttpClient;

/**
 * Builds the provider that is currently selected (and the others, for the picker on the Connection card).
 *
 * `google.api_base` is a development hook: when it is set, every Google call goes to that address instead. Only the
 * console can write it (`bin/console reviews:api-base`), and the Connection card shows a warning while it is in use,
 * so a test endpoint can never be forgotten on a live site.
 */
final class ReviewProviders
{
    public const KEYS = ['business_profile', 'places', 'manual'];

    public function __construct(private readonly Settings $settings, private readonly ?HttpClient $http = null)
    {
    }

    public function active(): ReviewProvider
    {
        return $this->get($this->settings->string('reviews.provider', 'manual'));
    }

    public function get(string $key): ReviewProvider
    {
        $http = $this->http ?? new Http();
        $base = $this->apiBase();
        return match ($key) {
            'business_profile' => $base === ''
                ? new BusinessProfileProvider($this->settings, $http)
                : new BusinessProfileProvider($this->settings, $http, $base, $base . '/v4'),
            'places' => $base === ''
                ? new PlacesProvider($this->settings, $http)
                : new PlacesProvider($this->settings, $http, $base . '/v1'),
            default => new ManualImportProvider($this->settings),
        };
    }

    /** The test endpoint, when one is configured. */
    public function apiBase(): string
    {
        $base = trim($this->settings->string('google.api_base'));
        return preg_match('#^https?://[^\s]{6,200}$#', $base) === 1 ? rtrim($base, '/') : '';
    }

    public function activeKey(): string
    {
        $key = $this->settings->string('reviews.provider', 'manual');
        return in_array($key, self::KEYS, true) ? $key : 'manual';
    }
}
