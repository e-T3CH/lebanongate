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
            'site.maintenance_mode' => [false, 'bool'],
            'site.newsletter_enabled' => [true, 'bool'],

            // Organisation and contact ([bracketed] values are placeholders until GATE Lebanon supplies them)
            'org.legal_name' => ['GATE Lebanon', 'string'],
            'org.registration' => ['[NGO registration no.]', 'string'],
            'org.founded' => ['2014', 'string'],
            'contact.street' => ['[Street, building]', 'string'],
            'contact.area' => ['Ashrafieh', 'string'],
            'contact.city' => ['Beirut', 'string'],
            'contact.country' => ['Lebanon', 'string'],
            'contact.phone' => ['[+961 X XXX XXX]', 'string'],
            'contact.email' => ['[info@gatelebanon.org]', 'string'],
            'contact.hours' => ['[Monday–Friday, 08:30–16:30]', 'string'],
            'contact.latitude' => ['33.8886', 'string'],
            'contact.longitude' => ['35.5196', 'string'],

            // Contact and newsletter forms (spam protection) and email (SMTP through PHPMailer; the password is stored encrypted)
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
            'seo.og_image' => ['', 'string'],

            // Social media (URL + shown in the top bar / footer)
            'social.facebook.url' => ['https://www.facebook.com/p/GATE-Lebanon-100083091830790/', 'string'],
            'social.facebook.header' => [true, 'bool'],
            'social.facebook.footer' => [true, 'bool'],
            'social.instagram.url' => ['', 'string'],
            'social.instagram.header' => [true, 'bool'],
            'social.instagram.footer' => [true, 'bool'],
            'social.linkedin.url' => ['', 'string'],
            'social.linkedin.header' => [true, 'bool'],
            'social.linkedin.footer' => [true, 'bool'],
            'social.x.url' => ['', 'string'],
            'social.x.header' => [false, 'bool'],
            'social.x.footer' => [true, 'bool'],
            'social.youtube.url' => ['', 'string'],
            'social.youtube.header' => [false, 'bool'],
            'social.youtube.footer' => [true, 'bool'],
            'social.whatsapp.url' => ['', 'string'],
            'social.whatsapp.header' => [false, 'bool'],
            'social.whatsapp.footer' => [true, 'bool'],

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
            'admin.timezone' => ['Asia/Beirut', 'string'],

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
