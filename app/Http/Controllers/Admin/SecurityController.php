<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Security\IpAddress;
use BMMatic\Services\AuditLog;

/**
 * Settings → Security: login protection, session timeout, admin path, IP allowlist, HTTPS, and the 2FA lifecycle
 * (enable with QR + confirmation code + recovery codes shown once; disable with password + current code).
 */
final class SecurityController extends AdminController
{
    private const SETUP_KEY = 'two_factor_setup';
    private const CODES_KEY = 'two_factor_new_codes';
    private const RESERVED_PATHS = ['en', 'fr', 'nl', 'install', 'assets', 'uploads', 'api', 'well-known'];

    public function index(Request $request): Response
    {
        $s = $this->app->settings();
        $user = $this->app->auth()->user();
        $session = $this->app->session();
        $errors = $session->pull('security_errors');
        $old = $session->pull('security_old');
        return $this->adminView('admin/security', 'security', $this->t('admin.security.title'), $this->t('admin.security.subtitle'), [
            'values' => is_array($old) ? $old : [
                'max_failed_logins' => (string) $s->int('security.max_failed_logins', 5),
                'ip_max_failed_logins' => (string) $s->int('security.ip_max_failed_logins', 20),
                'lockout_minutes' => (string) $s->int('security.lockout_minutes', 15),
                'session_timeout' => (string) $s->int('security.session_timeout', 30),
                'admin_path' => trim($s->string('security.admin_path', 'admin'), '/'),
                'admin_ip_allowlist' => $s->string('security.admin_ip_allowlist'),
                'force_https' => $s->bool('security.force_https'),
                'two_factor_required' => $s->bool('security.two_factor_required'),
            ],
            'errors' => is_array($errors) ? $errors : [],
            'twoFactorOn' => $user !== null && $user['totp_secret'] !== null,
            'recoveryCount' => $user === null ? 0 : count($user['recovery_codes']),
            'twoFactorError' => $session->pull('two_factor_error'),
            'currentIp' => $request->ip(),
            'isHttps' => $request->isSecure(),
        ]);
    }

    public function save(Request $request): Response
    {
        $s = $this->app->settings();
        $user = $this->app->auth()->user();
        $errors = [];
        $int = static function (string $value, int $min, int $max): ?int {
            if (preg_match('/^\d{1,5}$/', trim($value)) !== 1 || (int) $value < $min || (int) $value > $max) {
                return null;
            }
            return (int) $value;
        };
        $values = [
            'max_failed_logins' => $request->input('max_failed_logins'),
            'ip_max_failed_logins' => $request->input('ip_max_failed_logins'),
            'lockout_minutes' => $request->input('lockout_minutes'),
            'session_timeout' => $request->input('session_timeout'),
            'admin_path' => strtolower(trim($request->input('admin_path'), " /\t")),
            'admin_ip_allowlist' => trim($request->input('admin_ip_allowlist')),
            'force_https' => $request->input('force_https') === '1',
            'two_factor_required' => $request->input('two_factor_required') === '1',
        ];
        $limits = ['max_failed_logins' => [1, 50], 'ip_max_failed_logins' => [1, 1000], 'lockout_minutes' => [1, 1440], 'session_timeout' => [5, 1440]];
        $numbers = [];
        foreach ($limits as $field => [$min, $max]) {
            $n = $int((string) $values[$field], $min, $max);
            if ($n === null) {
                $errors[$field] = $this->t('validation.between', ['min' => $min, 'max' => $max]);
            } else {
                $numbers[$field] = $n;
            }
        }
        $path = (string) $values['admin_path'];
        if (preg_match('/^[a-z0-9][a-z0-9-]{3,40}$/', $path) !== 1 || in_array($path, self::RESERVED_PATHS, true)) {
            $errors['admin_path'] = $this->t('validation.admin_path');
        }
        $allowText = (string) $values['admin_ip_allowlist'];
        if ($allowText !== '') {
            $entries = preg_split('/[\s,;]+/', $allowText) ?: [];
            $invalid = array_filter($entries, static fn (string $e): bool => $e !== '' && !IpAddress::validEntry($e));
            if ($invalid !== []) {
                $errors['admin_ip_allowlist'] = $this->t('validation.ip_list');
            } elseif (!IpAddress::matchesAny($request->ip(), IpAddress::parseList($allowText))) {
                $errors['admin_ip_allowlist'] = $this->t('validation.ip_list_self', ['ip' => $request->ip()]);
            }
        }
        if ($values['force_https'] && !$s->bool('security.force_https') && !$request->isSecure()) {
            $errors['force_https'] = $this->t('validation.https_required');
        }
        if ($values['two_factor_required'] && ($user === null || $user['totp_secret'] === null)) {
            $errors['two_factor_required'] = $this->t('validation.two_factor_required_self');
        }
        if ($errors !== []) {
            $this->app->session()->flash('security_errors', $errors);
            $this->app->session()->flash('security_old', $values);
            return $this->back($this->app->adminPath('security'));
        }

        $new = [
            'security.max_failed_logins' => $numbers['max_failed_logins'],
            'security.ip_max_failed_logins' => $numbers['ip_max_failed_logins'],
            'security.lockout_minutes' => $numbers['lockout_minutes'],
            'security.session_timeout' => $numbers['session_timeout'],
            'security.admin_path' => $path,
            'security.admin_ip_allowlist' => implode("\n", IpAddress::parseList($allowText)),
            'security.force_https' => (bool) $values['force_https'],
            'security.two_factor_required' => (bool) $values['two_factor_required'],
        ];
        $changed = [];
        foreach ($new as $key => $value) {
            if ($s->get($key) !== $value) {
                $s->set($key, $value);
                $changed[] = $key;
            }
        }
        if ($changed !== []) {
            $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $user['id'] ?? null, ['keys' => $changed]);
        }
        $this->flashToast('admin.toast.saved');
        return $this->back('/' . $path . '/security');
    }

    public function startSetup(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null || $user['totp_secret'] !== null) {
            return $this->back($this->app->adminPath('security'));
        }
        if (!$this->app->auth()->confirmPassword($request->input('password'))) {
            $this->app->session()->flash('two_factor_error', $this->t('admin.two_factor.password_wrong'));
            return $this->back($this->app->adminPath('security'));
        }
        $secret = $this->app->twoFactor()->generateSecret();
        $this->app->session()->set(self::SETUP_KEY, ['secret' => $this->app->crypto()->encrypt($secret), 'at' => $this->app->clock->now()->getTimestamp()]);
        return $this->back($this->app->adminPath('security/two-factor/setup'));
    }

    public function showSetup(Request $request): Response
    {
        $user = $this->app->auth()->user();
        $secret = $this->pendingSecret();
        if ($user === null || $secret === null || $user['totp_secret'] !== null) {
            return Response::redirect($this->app->adminPath('security'));
        }
        $issuer = $this->app->settings()->string('site.name', 'BM-Matic');
        $error = $this->app->session()->pull('two_factor_error');
        return $this->adminView('admin/two-factor-setup', 'security', $this->t('admin.two_factor.setup_title'), $this->t('admin.security.subtitle'), [
            'qrSvg' => $this->app->twoFactor()->qrSvg($issuer, $user['email'], $secret),
            'secretGroups' => str_split($secret, 4),
            'error' => is_string($error) ? $error : null,
        ]);
    }

    public function confirmSetup(Request $request): Response
    {
        $user = $this->app->auth()->user();
        $secret = $this->pendingSecret();
        if ($user === null || $secret === null) {
            return $this->back($this->app->adminPath('security'));
        }
        $twoFactor = $this->app->twoFactor();
        $step = $twoFactor->verify($secret, $request->input('code'), null);
        if ($step === null) {
            $this->app->session()->flash('two_factor_error', $this->t('admin.two_factor.code_wrong'));
            return $this->back($this->app->adminPath('security/two-factor/setup'));
        }
        $codes = $twoFactor->generateRecoveryCodes();
        $this->app->users()->enableTwoFactor($user['id'], $twoFactor->encryptSecret($secret), $step, $twoFactor->hashRecoveryCodes($codes));
        $this->app->session()->remove(self::SETUP_KEY);
        $this->app->session()->regenerate();
        $this->app->audit()->record(AuditLog::TWO_FACTOR_ENABLED, $user['id']);
        $this->app->session()->flash(self::CODES_KEY, $codes);
        $this->app->auth()->refreshUser();
        return $this->back($this->app->adminPath('security/two-factor/recovery-codes'));
    }

    /** Recovery codes are shown exactly once, right after they were generated. */
    public function showRecoveryCodes(Request $request): Response
    {
        $codes = $this->app->session()->pull(self::CODES_KEY);
        if (!is_array($codes) || $codes === []) {
            return Response::redirect($this->app->adminPath('security'));
        }
        return $this->adminView('admin/recovery-codes', 'security', $this->t('admin.two_factor.codes_title'), $this->t('admin.security.subtitle'), [
            'codes' => array_values(array_filter($codes, 'is_string')),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null || $user['totp_secret'] === null || !$this->confirmPasswordAndCode($request, $user['totp_secret'], $user['totp_last_timestep'], $user['id'])) {
            return $this->back($this->app->adminPath('security'));
        }
        $twoFactor = $this->app->twoFactor();
        $codes = $twoFactor->generateRecoveryCodes();
        $this->app->users()->setRecoveryCodes($user['id'], $twoFactor->hashRecoveryCodes($codes));
        $this->app->audit()->record(AuditLog::RECOVERY_CODES_REGENERATED, $user['id']);
        $this->app->session()->flash(self::CODES_KEY, $codes);
        return $this->back($this->app->adminPath('security/two-factor/recovery-codes'));
    }

    public function disable(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null || $user['totp_secret'] === null) {
            return $this->back($this->app->adminPath('security'));
        }
        if (!$this->confirmPasswordAndCode($request, $user['totp_secret'], $user['totp_last_timestep'], $user['id'])) {
            $this->app->audit()->record(AuditLog::TWO_FACTOR_DISABLE_FAILED, $user['id']);
            return $this->back($this->app->adminPath('security'));
        }
        $this->app->users()->disableTwoFactor($user['id']);
        $this->app->audit()->record(AuditLog::TWO_FACTOR_DISABLED, $user['id']);
        $settings = $this->app->settings();
        if ($settings->bool('security.two_factor_required')) {
            // Requiring 2FA only makes sense while the signed-in administrator uses it.
            $settings->set('security.two_factor_required', false);
            $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $user['id'], ['keys' => ['security.two_factor_required']]);
        }
        $this->app->auth()->refreshUser();
        $this->flashToast('admin.two_factor.disabled_toast');
        return $this->back($this->app->adminPath('security'));
    }

    private function confirmPasswordAndCode(Request $request, string $encryptedSecret, ?int $lastStep, int $userId): bool
    {
        $twoFactor = $this->app->twoFactor();
        $key = '2fa:manage:' . $userId;
        $limiter = $this->app->limiter();
        if ($limiter->tooManyAttempts($key, 5)) {
            $this->app->session()->flash('two_factor_error', $this->t('admin.two_factor.too_many'));
            return false;
        }
        $passwordOk = $this->app->auth()->confirmPassword($request->input('password'));
        $step = $passwordOk ? $twoFactor->verify($twoFactor->decryptSecret($encryptedSecret), $request->input('code'), $lastStep) : null;
        if (!$passwordOk || $step === null) {
            $limiter->hit($key, 900);
            $this->app->session()->flash('two_factor_error', $this->t('admin.two_factor.password_or_code_wrong'));
            return false;
        }
        $limiter->clear($key);
        $this->app->users()->setTotpTimestep($userId, $step);
        return true;
    }

    private function pendingSecret(): ?string
    {
        $pending = $this->app->session()->get(self::SETUP_KEY);
        if (!is_array($pending) || !is_string($pending['secret'] ?? null) || !is_int($pending['at'] ?? null)) {
            return null;
        }
        if ($this->app->clock->now()->getTimestamp() - $pending['at'] > 900) {
            $this->app->session()->remove(self::SETUP_KEY);
            return null;
        }
        try {
            return $this->app->crypto()->decrypt($pending['secret']);
        } catch (\RuntimeException) {
            return null;
        }
    }
}
