<?php

declare(strict_types=1);

namespace BMMatic\Install;

use BMMatic\Core\App;
use BMMatic\Http\Request;
use BMMatic\Http\Response;
use BMMatic\I18n\LanguageRules;
use BMMatic\I18n\LocaleResolver;
use BMMatic\Security\PasswordHasher;

/**
 * /install wizard: 1 requirements · 2 database · 3 site · 4 admin account · 5 languages → install → done.
 * Wizard data lives in the (server-side) session; the admin password is hashed as soon as it is entered.
 */
final class InstallController
{
    public const STEPS = ['requirements', 'database', 'site', 'admin', 'languages'];
    private const KEY = 'install';

    public function __construct(private readonly App $app)
    {
    }

    public static function pickLocale(Request $request): string
    {
        $q = $request->query('lang');
        if (in_array($q, LanguageRules::SUPPORTED, true)) {
            return $q;
        }
        $cookie = $request->cookie('bm_install_lang');
        if ($cookie !== null && in_array($cookie, LanguageRules::SUPPORTED, true)) {
            return $cookie;
        }
        return LocaleResolver::negotiate($request->header('Accept-Language') ?? '', LanguageRules::SUPPORTED) ?? 'en';
    }

    public function dispatch(Request $request): Response
    {
        $session = $this->app->session();
        $now = $this->app->clock->now()->getTimestamp();
        // Submitting a step claims the installer for this session; merely viewing it never does.
        $available = $request->method() === 'POST' ? InstallState::claim($session->id(), $now) : !InstallState::claimedByOther($session->id(), $now);
        if (!$available) {
            return $this->page('install/busy', [], 423);
        }
        $step = trim(substr($request->path(), strlen('/install')), '/');
        if ($step === '') {
            return Response::redirect('/install/requirements' . $this->langQuery($request));
        }
        if (!in_array($step, self::STEPS, true)) {
            return $this->page('install/busy', ['notFound' => true], 404);
        }
        $index = (int) array_search($step, self::STEPS, true);
        $state = $this->state();
        // Every step needs the previous ones.
        for ($i = 0; $i < $index; $i++) {
            if (!isset($state['done'][self::STEPS[$i]])) {
                return Response::redirect('/install/' . self::STEPS[$i]);
            }
        }
        $response = match ($step) {
            'requirements' => $this->requirements($request),
            'database' => $this->database($request),
            'site' => $this->site($request),
            'admin' => $this->admin($request),
            default => $this->languages($request),
        };
        if ($request->query('lang') !== '') {
            $response->withCookie('bm_install_lang', $this->app->translator()->locale(), ['expires' => 0, 'path' => '/install', 'secure' => $request->isSecure(), 'httponly' => true, 'samesite' => 'Strict']);
        }
        return $response;
    }

    private function requirements(Request $request): Response
    {
        $host = (string) ($request->server('HTTP_HOST') ?? '');
        $base = preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host) === 1 ? ($request->isSecure() ? 'https://' : 'http://') . $host : null;
        $checks = Requirements::check($request->isSecure(), $base);
        $passes = Requirements::passes($checks);
        if ($request->method() === 'POST' && $passes) {
            $this->markDone('requirements', []);
            return Response::redirect('/install/database', 303);
        }
        return $this->page('install/requirements', ['checks' => $checks, 'passes' => $passes, 'step' => 'requirements']);
    }

    private function database(Request $request): Response
    {
        $state = $this->state();
        /** @var array{host: string, port: int, name: string, user: string, pass: string} $db */
        $db = $state['data']['database'] ?? ['host' => '127.0.0.1', 'port' => 3306, 'name' => '', 'user' => '', 'pass' => ''];
        $error = null;
        if ($request->method() === 'POST') {
            $db = [
                'host' => mb_substr(trim($request->input('db_host')), 0, 255),
                'port' => (int) ($request->input('db_port') !== '' ? $request->input('db_port') : '3306'),
                'name' => mb_substr(trim($request->input('db_name')), 0, 64),
                'user' => mb_substr(trim($request->input('db_user')), 0, 80),
                'pass' => mb_substr($request->input('db_pass'), 0, 255),
            ];
            if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '' || $db['port'] < 1 || $db['port'] > 65535 || preg_match('/^[A-Za-z0-9_$-]+$/', $db['name']) !== 1) {
                $error = 'install.database.error_fields';
            } else {
                $error = (new Installer($this->app->clock))->testDatabase($db);
            }
            if ($error === null) {
                $this->markDone('database', $db);
                return Response::redirect('/install/site', 303);
            }
        }
        return $this->page('install/database', ['db' => $db, 'error' => $error, 'step' => 'database']);
    }

    private function site(Request $request): Response
    {
        $state = $this->state();
        $guessUrl = ($request->isSecure() ? 'https://' : 'http://') . preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', $request->server('HTTP_HOST') ?? 'localhost');
        /** @var array{site_name: string, site_url: string} $site */
        $site = $state['data']['site'] ?? ['site_name' => 'BM-Matic', 'site_url' => $guessUrl];
        $errors = [];
        if ($request->method() === 'POST') {
            $site = ['site_name' => trim($request->input('site_name')), 'site_url' => rtrim(trim($request->input('site_url')), '/')];
            $errors = Installer::validateSite($site['site_name'], $site['site_url']);
            if ($errors === []) {
                $this->markDone('site', $site);
                return Response::redirect('/install/admin', 303);
            }
        }
        return $this->page('install/site', ['site' => $site, 'errors' => $errors, 'step' => 'site']);
    }

    private function admin(Request $request): Response
    {
        $state = $this->state();
        /** @var array{name: string, email: string, password_hash?: string} $admin */
        $admin = $state['data']['admin'] ?? ['name' => '', 'email' => ''];
        $errors = [];
        if ($request->method() === 'POST') {
            $name = trim($request->input('name'));
            $email = strtolower(trim($request->input('email')));
            $password = $request->input('password');
            $errors = Installer::validateAdmin($name, $email, $password, $request->input('password_confirm'));
            $admin = ['name' => $name, 'email' => $email];
            if ($errors === []) {
                $this->markDone('admin', $admin + ['password_hash' => (new PasswordHasher())->hash($password)]);
                return Response::redirect('/install/languages', 303);
            }
        }
        return $this->page('install/admin', ['admin' => $admin, 'errors' => $errors, 'step' => 'admin']);
    }

    private function languages(Request $request): Response
    {
        $state = $this->state();
        $enabled = LanguageRules::SUPPORTED;
        $default = $this->app->translator()->locale();
        $errors = [];
        if ($request->method() === 'POST') {
            $enabled = array_values(array_intersect(LanguageRules::SUPPORTED, $request->inputList('languages')));
            $default = $request->input('default_language');
            $errors = LanguageRules::validate(LanguageRules::SUPPORTED, $enabled, $default);
            if ($errors === []) {
                /** @var array{host: string, port: int, name: string, user: string, pass: string} $db */
                $db = $state['data']['database'];
                /** @var array{site_name: string, site_url: string} $site */
                $site = $state['data']['site'];
                /** @var array{name: string, email: string, password_hash: string} $admin */
                $admin = $state['data']['admin'];
                try {
                    $result = (new Installer($this->app->clock))->install($db, $site['site_name'], $site['site_url'], $admin['name'], $admin['email'], $admin['password_hash'], $enabled, $default);
                } catch (\Throwable $e) {
                    return $this->page('install/languages', ['enabled' => $enabled, 'default' => $default, 'errors' => ['install.languages.error_install'], 'detail' => $e->getMessage(), 'step' => 'languages']);
                }
                $this->app->session()->invalidate();
                $loginUrl = $site['site_url'] . '/' . $result['admin_path'] . '/login';
                return $this->page('install/done', ['loginUrl' => $loginUrl, 'email' => $admin['email'], 'step' => 'done']);
            }
        }
        return $this->page('install/languages', ['enabled' => $enabled, 'default' => $default, 'errors' => $errors, 'detail' => null, 'step' => 'languages']);
    }

    /** @return array{done: array<string, bool>, data: array<string, mixed>} */
    private function state(): array
    {
        $state = $this->app->session()->get(self::KEY);
        if (!is_array($state)) {
            return ['done' => [], 'data' => []];
        }
        return ['done' => is_array($state['done'] ?? null) ? $state['done'] : [], 'data' => is_array($state['data'] ?? null) ? $state['data'] : []];
    }

    /** @param array<string, mixed> $data */
    private function markDone(string $step, array $data): void
    {
        $state = $this->state();
        $state['done'][$step] = true;
        if ($data !== []) {
            $state['data'][$step] = $data;
        }
        $this->app->session()->set(self::KEY, $state);
    }

    private function langQuery(Request $request): string
    {
        $q = $request->query('lang');
        return in_array($q, LanguageRules::SUPPORTED, true) ? '?lang=' . $q : '';
    }

    /** @param array<string, mixed> $data */
    private function page(string $template, array $data, int $status = 200): Response
    {
        return $this->app->render($template, $data + ['steps' => self::STEPS, 'step' => $data['step'] ?? '', 'done' => $this->state()['done']], 'install', $status);
    }
}
