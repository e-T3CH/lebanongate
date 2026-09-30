<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Controllers\Controller;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Services\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $auth = $this->app->auth();
        if ($auth->user() !== null) {
            return Response::redirect($this->app->adminPath());
        }
        if ($auth->hasPendingTwoFactor()) {
            return Response::redirect($this->app->adminPath('two-factor'));
        }
        $session = $this->app->session();
        $error = $session->pull('login_error');
        $notice = $session->pull('login_notice');
        $email = $session->pull('login_email');
        return $this->view('admin/login', [
            'error' => is_string($error) ? $this->t($error) : null,
            'notice' => is_string($notice) ? $this->t($notice) : null,
            'email' => is_string($email) ? $email : '',
            'action' => $this->app->adminPath('login'),
            'forgotHref' => $this->app->adminPath('forgot-password'),
        ], 'auth');
    }

    public function login(Request $request): Response
    {
        $email = mb_substr(trim($request->input('email')), 0, 190);
        $password = mb_substr($request->input('password'), 0, 256);
        $result = $this->app->auth()->attempt($email, $password, $request->ip());
        return match ($result) {
            AuthService::RESULT_OK => $this->back($this->app->adminPath()),
            AuthService::RESULT_TWO_FACTOR => $this->back($this->app->adminPath('two-factor')),
            default => $this->failed($email),
        };
    }

    public function logout(Request $request): Response
    {
        $this->app->auth()->logout();
        $this->app->session()->flash('login_notice', 'admin.login.signed_out');
        return $this->back($this->app->adminPath('login'));
    }

    private function failed(string $email): Response
    {
        $session = $this->app->session();
        $session->flash('login_error', 'admin.login.failed');
        $session->flash('login_email', $email);
        return $this->back($this->app->adminPath('login'));
    }
}
