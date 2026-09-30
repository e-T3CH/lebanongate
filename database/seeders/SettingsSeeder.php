<?php

declare(strict_types=1);

namespace Gate\Database\Seeders;

use Gate\Core\ThemeDefaults;
use Gate\Services\Settings;

/**
 * Default settings: design colors (Appearance), [bracket] content placeholders, security, i18n, motion and site toggles.
 * Placeholders stay visible on the site until the owner replaces them in the admin panel.
 */
final class SettingsSeeder
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function defaults(): array
    {
        $d = [
            // Site
            'site.name' => ['GATE Lebanon', 'string'],
            'site.url' => ['', 'string'],
            'site.online_booking' => [true, 'bool'],
            'site.maintenance_mode' => [false, 'bool'],

            // Contact and company placeholders
            'contact.street' => ['[Street + number]', 'string'],
            'contact.postcode' => ['9300', 'string'],
            'contact.city' => ['Aalst', 'string'],
            'contact.country' => ['Belgium', 'string'],
            'contact.phone' => ['[+32 XX XX XX XX]', 'string'],
            'contact.whatsapp' => ['+32 [XXX XX XX XX]', 'string'],
            'contact.email' => ['[info@bm-matic.be]', 'string'],
            'contact.hours_weekdays' => ['[08:00–18:00]', 'string'],
            'contact.hours_saturday' => ['[by appointment]', 'string'],
            'contact.latitude' => ['50.9378', 'string'],
            'contact.longitude' => ['4.0403', 'string'],
            'company.vat' => ['[0XXX.XXX.XXX]', 'string'],

            // Opening hours for schema.org (days: Mo Tu We Th Fr Sa Su; times HH:MM)
            'contact.opening_hours' => [[['days' => ['Mo', 'Tu', 'We', 'Th', 'Fr'], 'opens' => '08:00', 'closes' => '18:00']], 'json'],
            'company.name' => ['[BM-Matic BV]', 'string'],
            'site.mobile_dock' => [true, 'bool'],

            // Appointment form (spam protection) and email (SMTP through PHPMailer; the password is stored encrypted)
            'forms.min_seconds' => [3, 'int'],
            'forms.max_per_hour' => [5, 'int'],
            'mail.enabled' => [true, 'bool'],
            'mail.host' => ['', 'string'],
            'mail.port' => [587, 'int'],
            'mail.encryption' => ['tls', 'string'],
            'mail.username' => ['', 'string'],
            'mail.password' => ['', 'string'],
            'mail.from_email' => ['', 'string'],
            'mail.from_name' => ['GATE Lebanon', 'string'],
            'mail.to_email' => ['', 'string'],

            // Cookie consent and analytics (loaded only after consent)
            'consent.version' => [1, 'int'],
            'analytics.provider' => ['none', 'string'],
            'analytics.ga4_id' => ['', 'string'],
            'analytics.plausible_domain' => ['', 'string'],
            'analytics.plausible_src' => ['https://plausible.io/js/script.js', 'string'],

            // SEO
            'seo.og_image' => ['/assets/img/bmmatic-logo.png', 'string'],

            // Google reviews
            'reviews.section_enabled' => [true, 'bool'],
            'reviews.show_rating_badge' => [true, 'bool'],
            'reviews.show_photos' => [false, 'bool'],
            'reviews.link_to_google' => [true, 'bool'],
            'reviews.rating' => ['[4.9]', 'string'],
            'reviews.count' => ['[XXX]', 'string'],
            'reviews.new_visibility' => ['hidden', 'string'],
            'reviews.provider' => ['manual', 'string'],
            'reviews.sync_interval_hours' => [24, 'int'],
            'reviews.display_order' => ['newest', 'string'],
            'reviews.max_on_home' => [6, 'int'],
            'reviews.last_sync_at' => ['', 'string'],
            'reviews.last_error' => ['', 'string'],
            'reviews.retry_after' => ['', 'string'],
            'reviews.failures' => [0, 'int'],
            'google.api_key' => ['', 'string'],
            'google.oauth_client_id' => ['', 'string'],
            'google.oauth_client_secret' => ['', 'string'],
            'google.oauth_refresh_token' => ['', 'string'],
            'google.account_id' => ['', 'string'],
            'google.api_base' => ['', 'string'],
            'google.place_id' => ['[PLACE_ID]', 'string'],
            'google.location_id' => ['accounts/[XXXX]/locations/[XXXX]', 'string'],
            'google.reviews_url' => ['[https://g.page/r/XXXX/review]', 'string'],

            // Social media (URL + shown in header/footer)
            'social.facebook.url' => ['https://facebook.com/[bmmatic]', 'string'],
            'social.facebook.header' => [true, 'bool'],
            'social.facebook.footer' => [true, 'bool'],
            'social.instagram.url' => ['https://instagram.com/[bmmatic]', 'string'],
            'social.instagram.header' => [true, 'bool'],
            'social.instagram.footer' => [true, 'bool'],
            'social.tiktok.url' => ['', 'string'],
            'social.tiktok.header' => [false, 'bool'],
            'social.tiktok.footer' => [false, 'bool'],
            'social.whatsapp.url' => ['+32 [XXX XX XX XX]', 'string'],
            'social.whatsapp.header' => [true, 'bool'],
            'social.whatsapp.footer' => [true, 'bool'],
            'social.youtube.url' => ['', 'string'],
            'social.youtube.header' => [false, 'bool'],
            'social.youtube.footer' => [false, 'bool'],
            'social.google_business.url' => ['https://g.page/[bmmatic]', 'string'],
            'social.google_business.header' => [false, 'bool'],
            'social.google_business.footer' => [true, 'bool'],

            // Security (2FA is off after installation)
            'security.two_factor_required' => [false, 'bool'],
            'security.max_failed_logins' => [5, 'int'],
            'security.ip_max_failed_logins' => [20, 'int'],
            'security.lockout_minutes' => [15, 'int'],
            'security.session_timeout' => [30, 'int'],
            'security.admin_path' => ['admin', 'string'],
            'security.admin_ip_allowlist' => ['', 'string'],
            'security.force_https' => [true, 'bool'],
            'security.form_spam_protection' => [true, 'bool'],

            // Languages and admin panel
            'i18n.detect_browser' => [true, 'bool'],
            'i18n.show_selector' => [true, 'bool'],
            'i18n.url_format' => ['prefix', 'string'],
            'admin.language' => ['en', 'string'],
            'admin.date_format' => ['DD/MM/YYYY', 'string'],
            'admin.timezone' => ['Europe/Brussels', 'string'],

            // Appearance
            'appearance.motion_enabled' => [true, 'bool'],
            'appearance.motion_intensity' => ['standard', 'string'],
        ];
        foreach (ThemeDefaults::COLORS as $name => $value) {
            $d['theme.' . $name] = [$value, 'color'];
        }
        foreach (ThemeDefaults::RADII as $name => $value) {
            $d['theme.' . $name] = [$value, 'string'];
        }
        foreach (ThemeDefaults::MOTION as $name => [$value]) {
            $d['theme.' . $name] = [$value, 'string'];
        }
        foreach (ThemeDefaults::EASINGS as $name => $value) {
            $d['theme.' . $name] = [$value, 'string'];
        }
        return $d;
    }

    /**
     * @param array<string, mixed> $overrides values for settings that do not exist yet
     * @return int settings added
     */
    public function run(array $overrides = []): int
    {
        $defaults = self::defaults();
        foreach ($overrides as $key => $value) {
            $defaults[$key] = [$value, $defaults[$key][1] ?? (is_bool($value) ? 'bool' : (is_int($value) ? 'int' : 'string'))];
        }
        return $this->settings->seedDefaults($defaults);
    }
}
