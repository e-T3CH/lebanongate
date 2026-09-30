<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Controllers\Controller;
use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\Mail\AdminMails;
use BMMatic\Mail\MailQueue;
use BMMatic\Mail\SmtpTransport;
use BMMatic\Repositories\PasswordResetRepository;
use BMMatic\Repositories\UserRepository;
use BMMatic\Security\LoginThrottle;
use BMMatic\Security\PasswordHasher;
use BMMatic\Services\AuditLog;

/**
 * "Forgot your password?" — the way back in on hosting without a command line (one.com).
 *
 * The answer to a request is always the same, whether the address belongs to an account or not, so the form cannot
 * be used to find out who has an account. Requests are rate-limited per visitor and per address. A new password
 * ends every session of that user and clears the login lockout; two-factor authentication stays as it was.
 */
final class PasswordResetController extends Controller
{
    private const MAX_PER_IP = 5;
    private const MAX_PER_EMAIL = 3;
    private const WINDOW = 3600;

    public function showRequest(Request $request): Response
    {
        if ($this->app->auth()->user() !== null) {
            return Response::redirect($this->app->adminPath());
        }
        $sent = $this->app->session()->pull('reset_sent');
        return $this->view('admin/forgot-password', [
            'sent' => $sent === true,
            'action' => $this->app->adminPath('forgot-password'),
            'loginHref' => $this->app->adminPath('login'),
        ], 'auth');
    }

    public function sendLink(Request $request): Response
    {
        $email = UserRepository::normalizeEmail(mb_substr(trim($request->input('email')), 0, 190));
        $limiter = $this->app->limiter();
        $allowed = $limiter->hit('reset:ip:' . $request->ip(), self::WINDOW) <= self::MAX_PER_IP
            && ($email === '' || $limiter->hit('reset:email:' . $email, self::WINDOW) <= self::MAX_PER_EMAIL);
        $user = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $this->users()->findByEmail($email) : null;
        $send = $allowed && $user !== null && $user['is_active'] && SmtpTransport::fromSettings($this->app->settings())->isConfigured();
        if ($send && $user !== null) {
            $token = $this->resets()->create($user['id']);
            $settings = $this->app->settings();
            $siteName = $settings->string('site.name', 'BM-Matic');
            $message = AdminMails::passwordReset($user['email'], $user['name'], $siteName, $this->app->baseUrl() . $this->app->adminPath('reset-password/' . $token), PasswordResetRepository::TTL_MINUTES, $this->app->translator());
            (new MailQueue($this->app->db(), $this->app->clock))->enqueue($message);
            $this->app->defer(fn () => $this->app->sendQueuedMail());
        }
        $this->app->audit()->record(AuditLog::PASSWORD_RESET_REQUESTED, $user['id'] ?? null, ['email' => mb_substr($email, 0, 190), 'sent' => $send, 'limited' => !$allowed]);
        // The same answer in every case.
        $this->app->session()->flash('reset_sent', true);
        return $this->back($this->app->adminPath('forgot-password'));
    }

    public function showReset(Request $request): Response
    {
        $token = (string) $request->param('token');
        $valid = $this->resets()->find($token) !== null;
        $errors = $this->app->session()->pull('reset_errors');
        return $this->view('admin/reset-password', [
            'valid' => $valid,
            'errors' => is_array($errors) ? $errors : [],
            'action' => $this->app->adminPath('reset-password/' . $token),
            'forgotHref' => $this->app->adminPath('forgot-password'),
            'minLength' => PasswordHasher::MIN_LENGTH,
        ], 'auth', $valid ? 200 : 410);
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->param('token');
        $link = $this->resets()->find($token);
        $user = $link === null ? null : $this->users()->find($link['user_id']);
        if ($link === null || $user === null || !$user['is_active']) {
            return $this->showReset($request);
        }
        $password = mb_substr($request->input('password'), 0, 300);
        $confirm = mb_substr($request->input('password_confirm'), 0, 300);
        $hasher = new PasswordHasher();
        $errors = [];
        $policy = $hasher->policyErrors($password, $user['email']);
        if ($policy !== []) {
            $errors['password'] = $this->t($policy[0], ['min' => PasswordHasher::MIN_LENGTH]);
        } elseif (!hash_equals($password, $confirm)) {
            $errors['password_confirm'] = $this->t('validation.password_confirm');
        }
        if ($errors !== []) {
            $this->app->session()->flash('reset_errors', $errors);
            return $this->back($this->app->adminPath('reset-password/' . $token));
        }
        $users = $this->users();
        $users->updatePasswordHash($user['id'], $hasher->hash($password));
        $users->endAllSessions($user['id']);
        $this->resets()->markUsed($link['id']);
        (new LoginThrottle($this->app->limiter(), 1, 1, 1))->clearAccount($user['email']);
        $this->app->audit()->record(AuditLog::PASSWORD_RESET, $user['id'], ['via' => 'email_link']);
        $this->app->session()->flash('login_notice', 'admin.reset.done');
        return $this->back($this->app->adminPath('login'));
    }

    private function users(): UserRepository
    {
        return new UserRepository($this->app->db(), $this->app->clock);
    }

    private function resets(): PasswordResetRepository
    {
        return new PasswordResetRepository($this->app->db(), $this->app->crypto(), $this->app->clock);
    }
}
