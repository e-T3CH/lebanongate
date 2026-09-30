<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Core\ThemeConfig;
use Gate\Core\ThemeDefaults;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Services\AuditLog;
use Gate\Services\MediaLibrary;

/**
 * Appearance: logo (light and dark), favicon, the colour tokens with a live contrast check, the radii and the motion
 * setting. Every value is validated against the same rules the theme uses, and "reset" puts the approved design back.
 */
final class AppearanceController extends AdminController
{
    /** Colour tokens the screen offers, grouped; the rest of the palette follows from these. */
    private const COLOR_GROUPS = [
        'brand' => ['c-brand', 'c-primary', 'c-primary-hover', 'c-accent', 'c-band'],
        'surfaces' => ['c-page', 'c-surface', 'c-tint', 'c-line', 'c-footer'],
        'text' => ['c-text-1', 'c-text-2', 'c-text-3', 'c-footer-text'],
    ];
    /** Pairs checked for contrast: text token on background token. */
    private const CONTRAST_PAIRS = [
        ['c-text-1', 'c-page'],
        ['c-text-2', 'c-page'],
        ['c-text-3', 'c-surface'],
        ['c-primary', 'c-page'],
        ['c-page', 'c-primary'],
        ['c-page', 'c-band'],
        ['c-footer-text', 'c-footer'],
    ];
    private const RADII = ['r-btn', 'r-card', 'r-panel', 'r-pop', 'r-dd'];

    public function index(Request $request): Response
    {
        $s = $this->app->settings();
        $colors = [];
        foreach (self::COLOR_GROUPS as $group => $tokens) {
            foreach ($tokens as $token) {
                $default = ThemeDefaults::COLORS[$token];
                $colors[$group][] = [
                    'token' => $token,
                    'label' => $this->t('admin.appearance.token_' . str_replace('-', '_', $token)),
                    'value' => $s->string('theme.' . $token, $default),
                    'default' => $default,
                ];
            }
        }
        $radii = [];
        foreach (self::RADII as $token) {
            $radii[] = [
                'token' => $token,
                'label' => $this->t('admin.appearance.token_' . str_replace('-', '_', $token)),
                'value' => $s->string('theme.' . $token, ThemeDefaults::RADII[$token]),
            ];
        }
        $library = new MediaLibrary($this->app->db(), $this->app->clock);
        $images = [['value' => '', 'label' => $this->t('admin.appearance.no_image')]];
        foreach ($library->all($this->app->languages()->enabledCodes()) as $item) {
            $images[] = ['value' => $item['url'], 'label' => $item['original_name']];
        }
        return $this->adminView('admin/appearance', 'appearance', $this->t('admin.appearance.title'), $this->t('admin.appearance.subtitle'), [
            'tabs' => (new SettingsController($this->app))->tabs('appearance'),
            'colors' => $colors,
            'radii' => $radii,
            'contrastPairs' => self::CONTRAST_PAIRS,
            'images' => $images,
            'logo' => $s->string('appearance.logo'),
            'logoDark' => $s->string('appearance.logo_dark'),
            'favicon' => $s->string('appearance.favicon'),
            'motionEnabled' => $s->bool('appearance.motion_enabled', true),
            'motionIntensity' => $s->string('appearance.motion_intensity', 'standard'),
            'errors' => $this->pullArray('appearance_errors'),
        ]);
    }

    public function save(Request $request): Response
    {
        $s = $this->app->settings();
        $errors = [];
        foreach (self::COLOR_GROUPS as $tokens) {
            foreach ($tokens as $token) {
                $value = trim($request->input('theme_' . $token));
                if ($value === '') {
                    continue;
                }
                if (!ThemeConfig::isColor($value)) {
                    $errors['theme_' . $token] = $this->t('validation.color');
                    continue;
                }
                $s->set('theme.' . $token, $value);
            }
        }
        foreach (self::RADII as $token) {
            $value = trim($request->input('theme_' . $token));
            if ($value === '') {
                continue;
            }
            $default = ThemeDefaults::RADII[$token];
            if (ThemeConfig::valid($value, 'length', $default) !== $value) {
                $errors['theme_' . $token] = $this->t('validation.length');
                continue;
            }
            $s->set('theme.' . $token, $value);
        }
        foreach (['logo' => 'appearance.logo', 'logo_dark' => 'appearance.logo_dark', 'favicon' => 'appearance.favicon'] as $field => $key) {
            $value = trim($request->input($field));
            if ($value !== '' && preg_match('#^/(uploads|assets)/[A-Za-z0-9._/-]{1,150}$#', $value) !== 1) {
                $errors[$field] = $this->t('validation.choice');
                continue;
            }
            $s->set($key, $value);
        }
        $s->set('appearance.motion_enabled', $request->input('motion_enabled') === '1', 'bool');
        $intensity = $request->input('motion_intensity');
        $s->set('appearance.motion_intensity', in_array($intensity, ['standard', 'subtle'], true) ? $intensity : 'standard');
        if ($errors !== []) {
            $this->app->session()->flash('appearance_errors', $errors);
        } else {
            $this->flashToast('admin.toast.saved');
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['theme.*', 'appearance.*']]);
        return $this->back($this->app->adminPath('appearance'));
    }

    /** Puts the approved design back: every theme token returns to its default. */
    public function reset(Request $request): Response
    {
        $s = $this->app->settings();
        foreach (ThemeDefaults::COLORS as $token => $default) {
            $s->set('theme.' . $token, $default);
        }
        foreach (ThemeDefaults::RADII as $token => $default) {
            $s->set('theme.' . $token, $default);
        }
        foreach (ThemeDefaults::MOTION as $token => [$default]) {
            $s->set('theme.' . $token, $default);
        }
        $s->set('appearance.motion_enabled', true, 'bool');
        $s->set('appearance.motion_intensity', 'standard');
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['theme.*'], 'reset' => true]);
        $this->flashToast('admin.appearance.reset_done');
        return $this->back($this->app->adminPath('appearance'));
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
