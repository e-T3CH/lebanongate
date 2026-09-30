<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\I18n\LanguageRules;
use Gate\Repositories\ContentAdminRepository;
use Gate\Services\AuditLog;

/**
 * Settings → General, Languages and Social media (Security and Email have their own controllers, and every tab is
 * listed by tabs(), which hides what the signed-in user may not open).
 */
final class SettingsController extends AdminController
{
    /** Social networks in the order of the approved screen. */
    private const SOCIAL = ['facebook', 'instagram', 'linkedin', 'x', 'youtube', 'whatsapp'];

    /** General settings fields => maximum length. */
    private const GENERAL_FIELDS = [
        'site_name' => 120, 'legal_name' => 160, 'registration' => 80, 'founded' => 10,
        'street' => 160, 'area' => 80, 'city' => 80, 'country' => 80,
        'phone' => 40, 'email' => 190, 'hours' => 120, 'latitude' => 20, 'longitude' => 20,
    ];

    /** Setting key of each general field. */
    private const GENERAL_KEYS = [
        'site_name' => 'site.name', 'legal_name' => 'org.legal_name', 'registration' => 'org.registration', 'founded' => 'org.founded',
        'street' => 'contact.street', 'area' => 'contact.area', 'city' => 'contact.city', 'country' => 'contact.country',
        'phone' => 'contact.phone', 'email' => 'contact.email', 'hours' => 'contact.hours', 'latitude' => 'contact.latitude', 'longitude' => 'contact.longitude',
    ];

    public function general(Request $request): Response
    {
        $s = $this->app->settings();
        $old = $this->pullArray('settings_old');
        $values = $old;
        if ($values === []) {
            foreach (self::GENERAL_KEYS as $field => $key) {
                $values[$field] = $s->string($key, $field === 'site_name' ? 'GATE Lebanon' : '');
            }
        }
        return $this->adminView('admin/settings-general', 'settings', $this->t('admin.settings.title'), $this->t('admin.settings.general_subtitle'), [
            'tabs' => $this->tabs('general'),
            'values' => $values,
            'errors' => $this->pullArray('settings_errors'),
            'toggles' => [
                'newsletter_enabled' => $s->bool('site.newsletter_enabled', true),
                'maintenance_mode' => $s->bool('site.maintenance_mode'),
            ],
        ]);
    }

    public function saveGeneral(Request $request): Response
    {
        $values = [];
        foreach (self::GENERAL_FIELDS as $field => $max) {
            $values[$field] = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $request->input($field))), 0, $max);
        }
        $errors = [];
        if ($values['site_name'] === '') {
            $errors['site_name'] = $this->t('validation.required');
        }
        // Placeholders such as [info@gatelebanon.org] stay allowed until the owner fills in the real details.
        if ($values['email'] !== '' && !str_contains($values['email'], '[') && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->t('validation.email');
        }
        foreach (['latitude', 'longitude'] as $field) {
            if ($values[$field] !== '' && !is_numeric($values[$field])) {
                $errors[$field] = $this->t('validation.numeric');
            }
        }
        if ($errors !== []) {
            $this->app->session()->flash('settings_errors', $errors);
            $this->app->session()->flash('settings_old', $values);
            return $this->back($this->app->adminPath('settings/general'));
        }
        $s = $this->app->settings();
        foreach (self::GENERAL_KEYS as $field => $key) {
            $s->set($key, $values[$field]);
        }
        $s->set('site.newsletter_enabled', $request->input('newsletter_enabled') === '1', 'bool');
        $s->set('site.maintenance_mode', $request->input('maintenance_mode') === '1', 'bool');
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['site.*', 'contact.*', 'org.*']]);
        $this->flashToast('admin.toast.saved');
        return $this->back($this->app->adminPath('settings/general'));
    }

    public function languages(Request $request): Response
    {
        $languages = $this->app->languages();
        $settings = $this->app->settings();
        $rows = [];
        $progress = $this->translationProgress();
        foreach ($languages->all() as $language) {
            $rows[] = [
                'code' => $language['code'],
                'name' => $language['name'],
                'native' => $language['native_name'],
                'enabled' => $language['is_enabled'],
                'default' => $language['is_default'],
                'progress' => $progress[$language['code']] ?? 0,
            ];
        }
        return $this->adminView('admin/settings-languages', 'languages', $this->t('admin.settings.title'), $this->t('admin.settings.languages_subtitle'), [
            'tabs' => $this->tabs('languages'),
            'languages' => $rows,
            'detectBrowser' => $settings->bool('i18n.detect_browser', true),
            'selectorInHeader' => $settings->bool('i18n.selector_in_header', true),
            'adminLanguage' => $settings->string('admin.language', 'en'),
            'error' => $this->app->session()->pull('languages_error'),
        ]);
    }

    public function saveLanguages(Request $request): Response
    {
        $languages = $this->app->languages();
        $enabled = array_values(array_filter($request->inputList('enabled'), static fn (string $c): bool => preg_match('/^[a-z]{2}$/', $c) === 1));
        $default = $request->input('default_language');
        // The default language is always on: its switch is locked in the form, and browsers do not send locked fields.
        if (preg_match('/^[a-z]{2}$/', $default) === 1 && !in_array($default, $enabled, true)) {
            $enabled[] = $default;
        }
        $known = array_column($languages->all(), 'code');
        $errors = LanguageRules::validate($known, $enabled, $default);
        if ($errors !== []) {
            $this->app->session()->flash('languages_error', $this->t($errors[0]));
            return $this->back($this->app->adminPath('settings/languages'));
        }
        $languages->saveState($enabled, $default);
        $settings = $this->app->settings();
        $settings->set('i18n.detect_browser', $request->input('detect_browser') === '1', 'bool');
        $settings->set('i18n.selector_in_header', $request->input('selector_in_header') === '1', 'bool');
        $adminLanguage = $request->input('admin_language');
        // The panel is translated into every supported language, whichever languages the website offers.
        if (in_array($adminLanguage, LanguageRules::ADMIN, true)) {
            $settings->set('admin.language', $adminLanguage);
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['languages', 'i18n.*'], 'enabled' => $enabled, 'default' => $default]);
        $this->flashToast('admin.toast.saved');
        return $this->back($this->app->adminPath('settings/languages'));
    }

    public function social(Request $request): Response
    {
        $s = $this->app->settings();
        $rows = [];
        foreach (self::SOCIAL as $network) {
            $rows[] = [
                'network' => $network,
                'label' => $this->t('admin.social.' . $network),
                'url' => $s->string('social.' . $network . '.url'),
                'header' => $s->bool('social.' . $network . '.header'),
                'footer' => $s->bool('social.' . $network . '.footer'),
            ];
        }
        return $this->adminView('admin/settings-social', 'settings', $this->t('admin.settings.title'), $this->t('admin.settings.social_subtitle'), [
            'tabs' => $this->tabs('social'),
            'networks' => $rows,
            'errors' => $this->pullArray('social_errors'),
        ]);
    }

    public function saveSocial(Request $request): Response
    {
        $s = $this->app->settings();
        $errors = [];
        foreach (self::SOCIAL as $network) {
            $url = trim($request->input('url_' . $network));
            // WhatsApp may hold a phone number; the others must be an https link (or empty).
            $valid = $url === ''
                || ($network === 'whatsapp' && preg_match('/^[+0-9 ()\[\].\/-]{6,40}$/', $url) === 1)
                || preg_match('#^https://[^\s]{4,300}$#', $url) === 1;
            if (!$valid) {
                $errors['url_' . $network] = $this->t('validation.url');
                continue;
            }
            $s->set('social.' . $network . '.url', mb_substr($url, 0, 300));
            $s->set('social.' . $network . '.header', $request->input('header_' . $network) === '1', 'bool');
            $s->set('social.' . $network . '.footer', $request->input('footer_' . $network) === '1', 'bool');
        }
        if ($errors !== []) {
            $this->app->session()->flash('social_errors', $errors);
        } else {
            $this->flashToast('admin.toast.saved');
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['social.*']]);
        return $this->back($this->app->adminPath('settings/social'));
    }

    /**
     * Share of the website's interface strings (site.*: menus, buttons, forms, messages) that exist in each language.
     * Admin panel strings are left out: the panel has its own languages (LanguageRules::ADMIN), so counting them
     * would make a fully translated website language look unfinished.
     *
     * @return array<string, int>
     */
    private function translationProgress(): array
    {
        $total = (int) $this->app->db()->scalar("SELECT COUNT(DISTINCT `key`) FROM {ui_translations} WHERE `key` LIKE 'site.%'");
        $out = [];
        foreach ($this->app->languages()->all() as $language) {
            $code = $language['code'];
            $done = (int) $this->app->db()->scalar("SELECT COUNT(*) FROM {ui_translations} WHERE `lang_code` = :c AND `key` LIKE 'site.%' AND `value` <> ''", ['c' => $code]);
            $out[$code] = $total > 0 ? (int) round($done / $total * 100) : 0;
        }
        return $out;
    }

    /**
     * The settings tabs of the approved screens, without the ones this user may not open.
     *
     * @return list<array{label: string, href: string, active: bool}>
     */
    public function tabs(string $active): array
    {
        $path = $this->app->adminPath();
        $tabs = [
            ['key' => 'general', 'label' => $this->t('admin.settings.tab_general'), 'href' => $path . '/settings/general', 'permission' => 'settings.manage'],
            ['key' => 'languages', 'label' => $this->t('admin.settings.tab_languages'), 'href' => $path . '/settings/languages', 'permission' => 'languages.manage'],
            ['key' => 'security', 'label' => $this->t('admin.settings.tab_security'), 'href' => $path . '/security', 'permission' => 'security.manage'],
            ['key' => 'social', 'label' => $this->t('admin.settings.tab_social'), 'href' => $path . '/settings/social', 'permission' => 'settings.manage'],
            ['key' => 'appearance', 'label' => $this->t('admin.settings.tab_appearance'), 'href' => $path . '/appearance', 'permission' => 'appearance.manage'],
            ['key' => 'email', 'label' => $this->t('admin.settings.tab_email'), 'href' => $path . '/settings/email', 'permission' => 'settings.manage'],
            ['key' => 'log', 'label' => $this->t('admin.settings.tab_log'), 'href' => $path . '/security/log', 'permission' => 'security.manage'],
            ['key' => 'maintenance', 'label' => $this->t('admin.settings.tab_maintenance'), 'href' => $path . '/settings/maintenance', 'permission' => 'security.manage'],
        ];
        $out = [];
        foreach ($tabs as $tab) {
            if ($this->can($tab['permission'])) {
                $out[] = ['label' => $tab['label'], 'href' => $tab['href'], 'active' => $tab['key'] === $active];
            }
        }
        return $out;
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
