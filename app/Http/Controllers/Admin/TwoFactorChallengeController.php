<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers\Admin;

use BMMatic\Http\Controllers\Controller;
use BMMatic\Http\Request;
use BMMatic\Http\Response;

/** Second login step: authenticator code or one recovery code. */
final class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): Response
    {
        $auth = $this->app->auth();
        if ($auth->user() !== null) {
            return Response::redirect($this->app->adminPath());
        }
        if (!$auth->hasPendingTwoFactor()) {
            return Response::redirect($this->app->adminPath('login'));
        }
        $error = $this->app->session()->pull('challenge_error');
        return $this->view('admin/two-factor', [
            'error' => is_string($error) ? $this->t($error) : null,
            'action' => $this->app->adminPath('two-factor'),
            'cancelAction' => $this->app->adminPath('two-factor/cancel'),
        ], 'auth');
    }

    public function verify(Request $request): Response
    {
        $auth = $this->app->auth();
        if (!$auth->hasPendingTwoFactor()) {
            $this->app->session()->flash('login_notice', 'admin.two_factor.expired');
            return $this->back($this->app->adminPath('login'));
        }
        if ($auth->verifyTwoFactor(mb_substr($request->input('code'), 0, 32), $request->ip())) {
            return $this->back($this->app->adminPath());
        }
        if (!$auth->hasPendingTwoFactor()) {
            return $this->back($this->app->adminPath('login'));
        }
        $this->app->session()->flash('challenge_error', 'admin.two_factor.failed');
        return $this->back($this->app->adminPath('two-factor'));
    }

    public function cancel(Request $request): Response
    {
        $this->app->auth()->cancelTwoFactor();
        return $this->back($this->app->adminPath('login'));
    }
}
