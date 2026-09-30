<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Admin\Permissions;
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
 * Users and roles: invite by email (token expires), edit name and role, activate or deactivate, reset 2FA.
 * Users are never deleted — the security log has to keep making sense — and the last active administrator can
 * neither be deactivated nor demoted, so nobody can lock everyone out.
 */
final class UserController extends AdminController
{
    public function index(Request $request): Response
    {
        $currentId = $this->app->auth()->user()['id'] ?? 0;
        $users = [];
        foreach ($this->users()->all() as $row) {
            $users[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
                'active' => (int) $row['is_active'] === 1,
                'two_factor' => $row['totp_enabled_at'] !== null,
                'last_login' => $row['last_login_at'] !== null ? $this->app->formatDate((string) $row['last_login_at']) : $this->t('admin.common.never'),
                'is_you' => (int) $row['id'] === $currentId,
            ];
        }
        $invitations = [];
        foreach ($this->invitations()->open() as $row) {
            $invitations[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
                'expires' => $this->app->formatDate((string) $row['expires_at']),
            ];
        }
        return $this->adminView('admin/users', 'users', $this->t('admin.users.title'), $this->t('admin.users.subtitle'), [
            'users' => $users,
            'invitations' => $invitations,
            'inviteTtlHours' => InvitationRepository::INVITE_TTL_HOURS,
            'old' => $this->pullArray('user_invite_old'),
            'errors' => $this->pullArray('user_invite_errors'),
        ]);
    }

    public function invite(Request $request): Response
    {
        $email = UserRepository::normalizeEmail($request->input('email'));
        $name = trim($request->input('name'));
        $role = $request->input('role');
        $errors = [];
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->t('validation.email');
        } elseif ($this->users()->findByEmail($email) !== null) {
            $errors['email'] = $this->t('admin.users.email_taken');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = $this->t('validation.between', ['min' => 2, 'max' => 120]);
        }
        if (!in_array($role, ['admin', 'editor'], true)) {
            $errors['role'] = $this->t('validation.choice');
        }
        if ($errors !== []) {
            $this->app->session()->flash('user_invite_errors', $errors);
            $this->app->session()->flash('user_invite_old', ['email' => $email, 'name' => $name, 'role' => $role]);
            return $this->back($this->app->adminPath('users'));
        }
        $invitation = $this->invitations()->invite($email, $name, (string) $role, $this->app->auth()->user()['id'] ?? null);
        $this->sendInvitation($email, $name, $invitation['token']);
        $this->app->audit()->record(AuditLog::USER_INVITED, $this->app->auth()->user()['id'] ?? null, ['email' => $email, 'role' => $role]);
        return $this->back($this->app->adminPath('users'));
    }

    public function resendInvitation(Request $request): Response
    {
        $invitations = $this->invitations();
        $id = (int) $request->param('id');
        $invitation = $invitations->find($id);
        $token = $invitation === null ? null : $invitations->refreshToken($id);
        if ($invitation === null || $token === null) {
            return $this->back($this->app->adminPath('users'));
        }
        $this->sendInvitation((string) $invitation['email'], (string) $invitation['name'], $token);
        $this->app->audit()->record(AuditLog::USER_INVITED, $this->app->auth()->user()['id'] ?? null, ['email' => $invitation['email'], 'resent' => true]);
        return $this->back($this->app->adminPath('users'));
    }

    public function cancelInvitation(Request $request): Response
    {
        $id = (int) $request->param('id');
        $invitation = $this->invitations()->find($id);
        if ($invitation !== null && $this->invitations()->cancel($id) > 0) {
            $this->app->audit()->record(AuditLog::USER_INVITE_CANCELLED, $this->app->auth()->user()['id'] ?? null, ['email' => $invitation['email']]);
            $this->flashToast('admin.users.invite_cancelled');
        }
        return $this->back($this->app->adminPath('users'));
    }

    public function edit(Request $request): Response
    {
        $user = $this->users()->find((int) $request->param('id'));
        if ($user === null) {
            return $this->app->errorResponse(404);
        }
        return $this->adminView('admin/user-edit', 'users', $this->t('admin.users.edit_title'), $this->t('admin.users.edit_subtitle'), [
            'editUser' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'active' => $user['is_active'],
                'two_factor' => $user['totp_enabled_at'] !== null,
                'last_login' => $user['last_login_at'] !== null ? $this->app->formatDate($user['last_login_at']) : $this->t('admin.common.never'),
            ],
            'isLastAdmin' => $this->users()->isLastActiveAdmin($user['id']),
            'isYou' => $user['id'] === ($this->app->auth()->user()['id'] ?? 0),
            'errors' => $this->pullArray('user_errors'),
        ]);
    }

    public function save(Request $request): Response
    {
        $users = $this->users();
        $id = (int) $request->param('id');
        $user = $users->find($id);
        if ($user === null) {
            return $this->app->errorResponse(404);
        }
        $name = trim($request->input('name'));
        $role = $request->input('role');
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = $this->t('validation.between', ['min' => 2, 'max' => 120]);
        }
        if (!in_array($role, ['admin', 'editor'], true)) {
            $errors['role'] = $this->t('validation.choice');
        }
        if ($role !== 'admin' && $users->isLastActiveAdmin($id)) {
            $errors['role'] = $this->t('admin.users.last_admin');
        }
        if ($errors !== []) {
            $this->app->session()->flash('user_errors', $errors);
            return $this->back($this->app->adminPath('users/' . $id));
        }
        $users->updateProfile($id, $name, (string) $role, $user['is_active']);
        if ($role !== $user['role']) {
            $users->endAllSessions($id);
        }
        $this->app->audit()->record(AuditLog::USER_CHANGED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'role' => $role]);
        $this->flashToast('admin.users.saved');
        return $this->back($this->app->adminPath('users/' . $id));
    }

    public function setActive(Request $request): Response
    {
        $users = $this->users();
        $id = (int) $request->param('id');
        $user = $users->find($id);
        if ($user === null) {
            return $this->app->errorResponse(404);
        }
        $active = $request->input('active') === '1';
        $currentId = $this->app->auth()->user()['id'] ?? 0;
        if (!$active && $id === $currentId) {
            $this->flashToast('admin.users.self_deactivate', 'error');
            return $this->back($this->app->adminPath('users/' . $id));
        }
        if (!$active && $users->isLastActiveAdmin($id)) {
            $this->flashToast('admin.users.last_admin', 'error');
            return $this->back($this->app->adminPath('users/' . $id));
        }
        $users->updateProfile($id, $user['name'], $user['role'], $active);
        if (!$active) {
            $users->endAllSessions($id);
        }
        $this->app->audit()->record($active ? AuditLog::USER_ACTIVATED : AuditLog::USER_DEACTIVATED, $currentId, ['id' => $id]);
        $this->flashToast($active ? 'admin.users.activated' : 'admin.users.deactivated');
        return $this->back($this->app->adminPath('users/' . $id));
    }

    public function resetTwoFactor(Request $request): Response
    {
        $users = $this->users();
        $id = (int) $request->param('id');
        $user = $users->find($id);
        if ($user === null) {
            return $this->app->errorResponse(404);
        }
        $users->disableTwoFactor($id);
        $users->endAllSessions($id);
        $this->app->audit()->record(AuditLog::TWO_FACTOR_DISABLED, $this->app->auth()->user()['id'] ?? null, ['id' => $id, 'by_admin' => true]);
        $this->flashToast('admin.users.two_factor_reset', 'success', ['name' => $user['name']]);
        return $this->back($this->app->adminPath('users/' . $id));
    }

    // ------------------------------------------------------------------------------------ accepting an invitation

    public function showInvitation(Request $request): Response
    {
        $invitation = $this->invitations()->findByToken((string) $request->param('token'));
        return $this->view('admin/invitation', [
            'invitation' => $invitation,
            'token' => (string) $request->param('token'),
            'siteName' => $this->app->settings()->string('site.name', 'GATE Lebanon'),
            'adminPath' => $this->app->adminPath(),
            'errors' => $this->pullArray('invitation_errors'),
        ], null, $invitation === null ? 410 : 200);
    }

    public function acceptInvitation(Request $request): Response
    {
        $token = (string) $request->param('token');
        $invitations = $this->invitations();
        $invitation = $invitations->findByToken($token);
        if ($invitation === null) {
            return $this->showInvitation($request);
        }
        $password = $request->input('password');
        $confirm = $request->input('password_confirm');
        $errors = [];
        $hasher = new PasswordHasher();
        $policy = $hasher->policyErrors($password, (string) $invitation['email']);
        if ($policy !== []) {
            $errors['password'] = $this->t($policy[0], ['min' => PasswordHasher::MIN_LENGTH]);
        } elseif (!hash_equals($password, $confirm)) {
            $errors['password_confirm'] = $this->t('validation.password_confirm');
        }
        if ($errors !== []) {
            $this->app->session()->flash('invitation_errors', $errors);
            return $this->back($this->app->adminPath('invitation/' . $token));
        }
        $users = $this->users();
        $email = (string) $invitation['email'];
        $existing = $users->findByEmail($email);
        if ($existing !== null) {
            $invitations->markAccepted((int) $invitation['id']);
            return $this->back($this->app->adminPath('login'));
        }
        $id = $users->create($email, (string) $invitation['name'], $hasher->hash($password), (string) $invitation['role']);
        $invitations->markAccepted((int) $invitation['id']);
        $this->app->audit()->record(AuditLog::USER_INVITE_ACCEPTED, $id, ['email' => $email, 'role' => $invitation['role']]);
        $this->app->session()->flash('login_notice', 'admin.invitation.accepted');
        return $this->back($this->app->adminPath('login'));
    }

    // ------------------------------------------------------------------------------------------------- internals

    private function sendInvitation(string $email, string $name, string $token): void
    {
        $settings = $this->app->settings();
        $link = $this->app->baseUrl() . $this->app->adminPath('invitation/' . $token);
        $inviter = $this->app->auth()->user();
        $message = AdminMails::invitation(
            $email,
            $name,
            $settings->string('site.name', 'GATE Lebanon'),
            $link,
            is_string($inviter['name'] ?? null) ? $inviter['name'] : $settings->string('site.name', 'GATE Lebanon'),
            InvitationRepository::INVITE_TTL_HOURS,
            $this->app->translator()
        );
        if (!SmtpTransport::fromSettings($settings)->isConfigured()) {
            $this->flashToast('admin.users.mail_not_configured', 'error');
            return;
        }
        (new MailQueue($this->app->db(), $this->app->clock))->enqueue($message);
        $this->app->defer(fn () => $this->app->sendQueuedMail());
        // The queue is delivered after the response, so a slow mail server never delays the panel.
        $this->flashToast('admin.users.invite_sent', 'success', ['email' => $email]);
    }

    private function users(): UserRepository
    {
        return new UserRepository($this->app->db(), $this->app->clock);
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
