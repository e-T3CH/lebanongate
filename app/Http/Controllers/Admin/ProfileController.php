<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Mail\AdminMails;
use Gate\Mail\MailQueue;
use Gate\Mail\SmtpTransport;
use Gate\Repositories\InvitationRepository;
use Gate\Repositories\UserRepository;
use Gate\Security\PasswordHasher;
use Gate\Services\AuditLog;

/**
 * The signed-in user's own account: name, email address (only after confirming from the new address) and password
 * (only after entering the current one; changing it ends the other sessions). Two-factor authentication stays on the
 * Security screen, which is where the approved mock-up puts it.
 */
final class ProfileController extends AdminController
{
    public function index(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return Response::redirect($this->app->adminPath('login'));
        }
        $pending = $this->invitations()->pendingEmailChange($user['id']);
        return $this->adminView('admin/profile', 'profile', $this->t('admin.profile.title'), $this->t('admin.profile.subtitle'), [
            'profile' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
            'pendingEmail' => $pending !== null ? (string) $pending['new_email'] : null,
            'errors' => $this->pullArray('profile_errors'),
            'passwordErrors' => $this->pullArray('password_errors'),
            'old' => $this->pullArray('profile_old'),
        ]);
    }

    public function save(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return Response::redirect($this->app->adminPath('login'));
        }
        $users = new UserRepository($this->app->db(), $this->app->clock);
        $name = trim($request->input('name'));
        $email = UserRepository::normalizeEmail($request->input('email'));
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = $this->t('validation.between', ['min' => 2, 'max' => 120]);
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            $errors['email'] = $this->t('validation.email');
        } elseif ($email !== $user['email'] && $users->findByEmail($email) !== null) {
            $errors['email'] = $this->t('admin.users.email_taken');
        }
        if ($errors !== []) {
            $this->app->session()->flash('profile_errors', $errors);
            $this->app->session()->flash('profile_old', ['name' => $name, 'email' => $email]);
            return $this->back($this->app->adminPath('profile'));
        }
        if ($name !== $user['name']) {
            $users->updateName($user['id'], $name);
            $this->app->audit()->record(AuditLog::USER_CHANGED, $user['id'], ['field' => 'name']);
        }
        if ($email !== $user['email']) {
            $this->requestEmailChange($user, $email);
        } else {
            $this->flashToast('admin.profile.saved');
        }
        $this->app->auth()->refreshUser();
        return $this->back($this->app->adminPath('profile'));
    }

    public function confirmEmail(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return Response::redirect($this->app->adminPath('login'));
        }
        $invitations = $this->invitations();
        $change = $invitations->findEmailChange((string) $request->param('token'), $user['id']);
        if ($change === null) {
            $this->flashToast('admin.profile.email_link_invalid', 'error');
            return Response::redirect($this->app->adminPath('profile'), 303);
        }
        $users = new UserRepository($this->app->db(), $this->app->clock);
        $email = (string) $change['new_email'];
        if ($users->findByEmail($email) !== null) {
            $this->flashToast('admin.users.email_taken', 'error');
            return Response::redirect($this->app->adminPath('profile'), 303);
        }
        $users->updateEmail($user['id'], $email);
        $invitations->markEmailChangeConfirmed((int) $change['id']);
        $this->app->auth()->refreshUser();
        $this->app->audit()->record(AuditLog::EMAIL_CHANGED, $user['id'], []);
        $this->flashToast('admin.profile.email_confirmed', 'success', ['email' => $email]);
        return Response::redirect($this->app->adminPath('profile'), 303);
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return Response::redirect($this->app->adminPath('login'));
        }
        $current = $request->input('current_password');
        $password = $request->input('password');
        $confirm = $request->input('password_confirm');
        $errors = [];
        if (!$this->app->auth()->confirmPassword($current)) {
            $errors['current_password'] = $this->t('admin.profile.wrong_password');
        }
        $hasher = new PasswordHasher();
        $policy = $hasher->policyErrors($password, $user['email']);
        if ($policy !== []) {
            $errors['password'] = $this->t($policy[0], ['min' => PasswordHasher::MIN_LENGTH]);
        } elseif (!hash_equals($password, $confirm)) {
            $errors['password_confirm'] = $this->t('validation.password_confirm');
        }
        if ($errors !== []) {
            $this->app->session()->flash('password_errors', $errors);
            return $this->back($this->app->adminPath('profile'));
        }
        $users = new UserRepository($this->app->db(), $this->app->clock);
        $users->updatePasswordHash($user['id'], $hasher->hash($password));
        $users->endAllSessions($user['id']);
        $this->app->auth()->keepCurrentSession();
        $this->app->audit()->record(AuditLog::PASSWORD_RESET, $user['id'], ['self' => true]);
        $this->flashToast('admin.profile.password_changed');
        return $this->back($this->app->adminPath('profile'));
    }

    /** @param array<string, mixed> $user */
    private function requestEmailChange(array $user, string $email): void
    {
        $settings = $this->app->settings();
        if (!SmtpTransport::fromSettings($settings)->isConfigured()) {
            $this->flashToast('admin.users.mail_not_configured', 'error');
            return;
        }
        $token = $this->invitations()->requestEmailChange((int) $user['id'], $email);
        $link = $this->app->baseUrl() . $this->app->adminPath('email-change/' . $token);
        (new MailQueue($this->app->db(), $this->app->clock))->enqueue(AdminMails::emailChange(
            $email,
            (string) $user['name'],
            $settings->string('site.name', 'GATE Lebanon'),
            $link,
            InvitationRepository::EMAIL_CHANGE_TTL_HOURS,
            $this->app->translator()
        ));
        $this->app->defer(fn () => $this->app->sendQueuedMail());
        $this->app->audit()->record(AuditLog::EMAIL_CHANGE_REQUESTED, (int) $user['id'], []);
        $this->flashToast('admin.profile.email_confirm_sent', 'info', ['email' => $email]);
    }

    private function invitations(): InvitationRepository
    {
        return new InvitationRepository($this->app->db(), $this->app->crypto(), $this->app->clock);
    }

    /** @return array<string, mixed> */
    private function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
