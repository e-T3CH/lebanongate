<?php

declare(strict_types=1);

namespace Gate\Core;

use Gate\Admin\Permissions;
use Gate\DesignCheck\DesignCheckController;
use Gate\Http\AdminRoutes;
use Gate\Http\Controllers\Site\SiteController;
use Gate\Http\Flash;
use Gate\Http\HttpException;
use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Http\Router;
use Gate\I18n\LanguageRules;
use Gate\Mail\MailMessage;
use Gate\Mail\MailQueue;
use Gate\Ops\Health;
use Gate\Ops\Scheduler;
use Gate\Ops\SchemaUpdater;
use Gate\Mail\MailWorker;
use Gate\Mail\SmtpTransport;
use Gate\I18n\LocaleResolver;
use Gate\I18n\Translator;
use Gate\I18n\UrlGenerator;
use Gate\Install\InstallController;
use Gate\Install\InstallState;
use Gate\Repositories\LanguageRepository;
use Gate\Repositories\UserRepository;
use Gate\Security\Crypto;
use Gate\Security\Csrf;
use Gate\Security\IpAddress;
use Gate\Security\LoginThrottle;
use Gate\Security\NativeSession;
use Gate\Security\PasswordHasher;
use Gate\Security\RateLimiter;
use Gate\Security\SecurityHeaders;
use Gate\Security\Session;
use Gate\Security\TwoFactor;
use Gate\Services\AuditLog;
use Gate\Services\AuthService;
use Gate\Services\Settings;
use Gate\Support\ErrorLog;

/**
 * Application kernel and service container. One instance per request.
 * Order: HTTPS redirect → session → CSRF → routing (installer | admin | public i18n) → security headers.
 */
final class App
{
    public readonly Clock $clock;
    public readonly SecurityHeaders $headers;
    public Request $request;
    private ?Database $db = null;
    private ?Settings $settings = null;
    private ?Crypto $crypto = null;
    private ?Session $session = null;
    private ?Csrf $csrf = null;
    private ?Translator $translator = null;
    private ?View $view = null;
    private ?AuditLog $audit = null;
    private ?AuthService $auth = null;
    private ?UserRepository $users = null;
    private ?LanguageRepository $languages = null;
    private ?TwoFactor $twoFactor = null;
    private ?RateLimiter $limiter = null;
    private ?Router $router = null;
    private bool $noStore = false;
    /** True when this request runs against an installed site (settings, sessions and theme come from the database). */
    private bool $installedFlow = false;
    /** True for public website requests (errors use the site layout). */
    private bool $publicFlow = false;
    /** @var list<callable(): void> work to run after the response has been sent */
    private array $deferred = [];

    public function __construct(public readonly Config $config, ?Clock $clock = null, ?Session $session = null)
    {
        $this->clock = $clock ?? new SystemClock();
        $this->headers = new SecurityHeaders();
        $this->session = $session;
        $this->request = new Request('GET', '/');
    }

    public static function isInstalled(): bool
    {
        return Config::localFileExists() && InstallState::isLocked();
    }

    public function handle(Request $request): Response
    {
        $this->request = $request;
        try {
            $path = $request->path();
            if ($path === '/design-check' || str_starts_with($path, '/design-check/')) {
                // Component and screen gallery: local development only, a plain 404 everywhere else.
                // Release zips do not contain app/DesignCheck (class_exists is false there).
                if ($this->config->string('app.env') !== 'local' || !class_exists(DesignCheckController::class)) {
                    throw new HttpException(404);
                }
                $this->noStore();
                $response = (new DesignCheckController($this))->dispatch($request);
            } elseif (self::isInstalled() && Health::authorized($this, $request)) {
                // Before the database is touched: the health check must answer even when the database is down.
                $response = Health::handle($this);
            } else {
                $response = self::isInstalled() ? $this->handleInstalled($request) : $this->handleInstaller($request);
            }
        } catch (HttpException $e) {
            $response = $this->errorResponse($e->status());
        } catch (\PDOException $e) {
            $response = $this->errorResponse(503, $this->logException($e));
        } catch (\Throwable $e) {
            $response = $this->errorResponse(500, $this->logException($e));
        }
        $https = $request->isSecure();
        $hsts = false;
        if ($this->installedFlow && $this->db !== null) {
            try {
                $hsts = $this->settings()->bool('security.force_https');
            } catch (\Throwable) {
                $hsts = false;
            }
        }
        return $this->headers->apply($response, $https, $hsts, $this->noStore);
    }

    // ------------------------------------------------------------------------------------------------ services

    public function db(): Database
    {
        if ($this->db === null) {
            /** @var array<string, mixed> $dbConfig */
            $dbConfig = (array) $this->config->get('db', []);
            $this->db = Database::isConnected() ? Database::instance() : Database::connect($dbConfig);
        }
        return $this->db;
    }

    public function crypto(): Crypto
    {
        return $this->crypto ??= new Crypto($this->config->string('app.key'));
    }

    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->db(), $this->crypto(), $this->clock);
    }

    public function session(): Session
    {
        if ($this->session === null) {
            $idle = $this->installedFlow ? $this->settings()->int('security.session_timeout', 30) : 30;
            $this->session = new NativeSession($this->config->string('session.name', 'gate_session'), Paths::storage('sessions'), $this->request->isSecure(), $idle);
        }
        return $this->session;
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf($this->session());
    }

    public function audit(): AuditLog
    {
        if ($this->audit === null) {
            $this->audit = new AuditLog($this->db(), $this->clock);
            $this->audit->setRequestContext($this->request->ip(), $this->request->userAgent());
        }
        return $this->audit;
    }

    public function users(): UserRepository
    {
        return $this->users ??= new UserRepository($this->db(), $this->clock);
    }

    public function languages(): LanguageRepository
    {
        return $this->languages ??= new LanguageRepository($this->db());
    }

    public function limiter(): RateLimiter
    {
        return $this->limiter ??= new RateLimiter($this->db(), $this->clock);
    }

    public function twoFactor(): TwoFactor
    {
        return $this->twoFactor ??= new TwoFactor($this->crypto(), $this->clock);
    }

    public function auth(): AuthService
    {
        if ($this->auth === null) {
            $s = $this->settings();
            $throttle = new LoginThrottle($this->limiter(), $s->int('security.max_failed_logins', 5), $s->int('security.ip_max_failed_logins', 20), $s->int('security.lockout_minutes', 15));
            $this->auth = new AuthService($this->users(), new PasswordHasher(), $throttle, $this->limiter(), $this->twoFactor(), $this->session(), $this->csrf(), $this->audit(), $this->clock);
        }
        return $this->auth;
    }

    public function translator(): Translator
    {
        return $this->translator ??= new Translator('en', 'en', $this->db);
    }

    public function view(): View
    {
        if ($this->view === null) {
            $this->view = new View($this->translator(), $this->csrf(), $this->headers->nonce());
            $this->view->share('app', $this);
            if ($this->installedFlow) {
                // Appearance settings: <html data-motion> and the theme tokens in <head>.
                $motion = ThemeConfig::motionMode($this->settings());
                $this->view->share('motion', $motion);
                $this->view->share('themeCss', ThemeConfig::css($this->settings(), $motion));
            }
        }
        return $this->view;
    }

    public function router(): Router
    {
        return $this->router ??= new Router();
    }

    public function urls(string $lang = ''): UrlGenerator
    {
        $base = $this->config->string('app.url');
        if ($base === '') {
            $host = $this->request->server('HTTP_HOST') ?? 'localhost';
            $base = ($this->request->isSecure() ? 'https://' : 'http://') . $host;
        }
        return new UrlGenerator($base, $this->languages()->enabledCodes(), $this->languages()->defaultCode());
    }

    public function request(): Request
    {
        return $this->request;
    }

    /**
     * A stored date ("2026-09-17 09:12:00") in the admin language, e.g. "17 Sep, 09:12".
     * Dates are stored and compared in UTC; only the display is localised.
     */
    public function formatDate(string $value, bool $withTime = true): string
    {
        $time = strtotime($value);
        if ($time === false) {
            return $value;
        }
        $lang = $this->translator()->locale();
        $months = [
            'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'fr' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
            'nl' => ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        ];
        $month = ($months[$lang] ?? $months['en'])[(int) date('n', $time) - 1];
        $date = (int) date('j', $time) . ' ' . $month;
        return $withTime ? $date . ', ' . date('H:i', $time) : $date . ' ' . date('Y', $time);
    }

    /** Admin URL prefix from settings, e.g. "/beheer-7f3kq2". */
    public function adminPath(string $suffix = ''): string
    {
        $path = '/' . trim($this->settings()->string('security.admin_path', 'admin'), '/');
        return $suffix === '' ? $path : $path . '/' . ltrim($suffix, '/');
    }

    /** Absolute base URL: the configured site URL, else the request host. */
    public function baseUrl(): string
    {
        $base = $this->config->string('app.url');
        if ($base === '') {
            $host = $this->request->server('HTTP_HOST') ?? 'localhost';
            $base = ($this->request->isSecure() ? 'https://' : 'http://') . $host;
        }
        return rtrim($base, '/');
    }

    /** Switches the request language (before any view is rendered). */
    public function useTranslator(Translator $translator): void
    {
        $this->translator = $translator;
        $this->view = null;
    }

    public function isAdminSignedIn(): bool
    {
        try {
            return $this->auth()->user() !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Runs $work after the response has been sent to the browser (email delivery, housekeeping). */
    public function defer(callable $work): void
    {
        $this->deferred[] = $work;
    }

    /** Delivers what is waiting in the mail queue. Called from deferred work, never during page rendering. */
    public function sendQueuedMail(int $limit = 10, float $seconds = 20.0): void
    {
        try {
            $transport = SmtpTransport::fromSettings($this->settings());
            if ($transport->isConfigured()) {
                (new MailWorker(new MailQueue($this->db(), $this->clock), $transport))->run($limit, $seconds);
            }
        } catch (\Throwable) {
            // Email must never break a request; failures stay in the queue with their error.
        }
    }

    /** Sends the response, closes the connection where the server allows it, then runs deferred work. */
    public function finish(Response $response): void
    {
        if ($this->deferred === []) {
            $response->send();
            return;
        }
        $fastcgi = function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
        if (!$fastcgi) {
            // mod_php and similar: tell the client the response is complete so it does not wait for the deferred work.
            $response->withHeader('Connection', 'close')->withHeader('Content-Length', (string) strlen($response->body()))->withHeader('Content-Encoding', 'none');
        }
        ignore_user_abort(true);
        $response->send();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } else {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }
        foreach ($this->deferred as $work) {
            try {
                $work();
            } catch (\Throwable $e) {
                $this->logException($e);
            }
        }
        $this->deferred = [];
    }

    public function usesDatabase(): bool
    {
        return $this->installedFlow && $this->db !== null;
    }

    public function noStore(): void
    {
        $this->noStore = true;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = null, int $status = 200): Response
    {
        return Response::html($this->view()->render($template, $data, $layout), $status);
    }

    // ------------------------------------------------------------------------------------------------ flows

    private function handleInstaller(Request $request): Response
    {
        $this->noStore();
        if (!str_starts_with($request->path(), '/install')) {
            return Response::redirect('/install');
        }
        $this->translator = new Translator(InstallController::pickLocale($request), 'en', null);
        if (Csrf::requiresCheck($request->method()) && !$this->csrf()->validate($request->input(Csrf::FIELD))) {
            return $this->errorResponse(419);
        }
        return (new InstallController($this))->dispatch($request);
    }

    private function handleInstalled(Request $request): Response
    {
        $this->installedFlow = true;
        $this->db();
        $settings = $this->settings();

        // New files were uploaded: bring the database up to date before anything reads it (no command line needed).
        if ((new SchemaUpdater($this->db(), $settings, $this->clock))->update($this->audit()) === null) {
            return $this->errorResponse(503)->withHeader('Retry-After', '30');
        }

        // Force HTTPS: redirect to the configured URL's scheme and host (never trust the Host header here).
        if ($settings->bool('security.force_https') && !$request->isSecure()) {
            $base = $this->config->string('app.url');
            $host = $base !== '' ? (string) parse_url($base, PHP_URL_HOST) : ($request->server('HTTP_HOST') ?? '');
            if ($host !== '') {
                return Response::redirect('https://' . $host . $request->path() . $request->queryString(), 301);
            }
        }

        $path = $request->path();
        if (str_starts_with($path, '/install')) {
            throw new HttpException(404);
        }

        if (Scheduler::authorized($settings, $request)) {
            return $this->startScheduler();
        }

        $adminPrefix = $this->adminPath();
        $isAdmin = $path === $adminPrefix || str_starts_with($path, $adminPrefix . '/');

        if ($isAdmin) {
            return $this->handleAdmin($request, $adminPrefix);
        }
        return $this->handlePublic($request);
    }

    /**
     * The scheduler address (/cron/<token>): answers at once and does the work after the response, so the calling
     * service (cron-job.org) never waits for a backup. No session, no cookies.
     */
    private function startScheduler(): Response
    {
        $this->noStore();
        $scheduler = new Scheduler($this->config, $this->db(), $this->settings(), $this->clock);
        if ($scheduler->isRunning()) {
            return Response::json(['status' => 'busy'], 200)->withHeader('X-Robots-Tag', 'noindex');
        }
        $this->defer(function () use ($scheduler): void {
            $scheduler->run($this->audit());
        });
        return Response::json(['status' => 'started', 'time' => $this->clock->now()->format('Y-m-d\TH:i:s\Z')], 202)->withHeader('X-Robots-Tag', 'noindex');
    }

    private function handleAdmin(Request $request, string $prefix): Response
    {
        $this->noStore();
        $settings = $this->settings();
        $allow = IpAddress::parseList($settings->string('security.admin_ip_allowlist'));
        if ($allow !== [] && !IpAddress::matchesAny($request->ip(), $allow)) {
            $this->audit()->record(AuditLog::ADMIN_IP_BLOCKED, null, ['path' => mb_substr($request->path(), 0, 120)]);
            throw new HttpException(404);
        }

        $adminLang = $settings->string('admin.language', 'en');
        $this->translator = new Translator(in_array($adminLang, LanguageRules::SUPPORTED, true) ? $adminLang : 'en', 'en', $this->db);

        if (Csrf::requiresCheck($request->method()) && !$this->csrf()->validate($request->input(Csrf::FIELD, $request->header(Csrf::HEADER) ?? ''))) {
            $this->audit()->record(AuditLog::CSRF_FAILED, null, ['path' => mb_substr($request->path(), 0, 120)]);
            return $this->errorResponse(419);
        }

        $router = $this->router();
        AdminRoutes::register($this, $router, $prefix);

        $match = $router->match($request->method(), $request->path());
        if ($match === null) {
            throw new HttpException(404);
        }
        $flags = $match['route']['flags'];
        if (in_array('auth', $flags, true)) {
            if ($this->auth()->expireIfIdle($settings->int('security.session_timeout', 30))) {
                $this->session()->flash('login_notice', 'admin.login.session_expired');
                return Response::redirect($prefix . '/login');
            }
            $user = $this->auth()->user();
            if ($user === null) {
                return Response::redirect($prefix . '/login');
            }
            // "Require 2FA for all admin users": admins without 2FA may only reach the 2FA setup screens.
            if ($settings->bool('security.two_factor_required') && $user['totp_secret'] === null
                && !in_array('2fa-exempt', $flags, true) && $match['route']['name'] !== 'admin.security' && $match['route']['name'] !== 'admin.logout') {
                Flash::toast($this->session(), 'info', 'admin.security.two_factor_required_notice');
                return Response::redirect($prefix . '/security');
            }
            // One permission check for every admin route (config/permissions.php).
            $role = is_string($user['role'] ?? null) ? $user['role'] : '';
            foreach ($flags as $flag) {
                if (str_starts_with($flag, 'perm:') && !Permissions::instance()->allows($role, substr($flag, 5))) {
                    $this->audit()->record(AuditLog::PERMISSION_DENIED, $user['id'], ['permission' => substr($flag, 5), 'path' => mb_substr($request->path(), 0, 120)]);
                    return $this->errorResponse(403);
                }
            }
        }
        return ($match['route']['handler'])($request->withParams($match['params']));
    }

    private function handlePublic(Request $request): Response
    {
        $this->publicFlow = true;
        return (new SiteController($this))->handle($request);
    }

    // ------------------------------------------------------------------------------------------------ errors

    /** @param string|null $reference the error log reference shown to the visitor (500/503 only) */
    public function errorResponse(int $status, ?string $reference = null): Response
    {
        $this->noStore();
        if ($this->publicFlow && $this->db !== null && !in_array($status, [500], true)) {
            try {
                $lang = preg_match('#^/([a-z]{2})(/|$)#', $this->request->path(), $m) === 1 && in_array($m[1], $this->languages()->enabledCodes(), true) ? $m[1] : $this->languages()->defaultCode();
                $this->useTranslator(new Translator($lang, $this->languages()->defaultCode(), $this->db));
                $keys = [403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed', 419 => 'expired', 503 => 'unavailable'];
                return (new SiteController($this))->errorPage($status, $lang, $keys[$status] ?? 'server_error', $reference);
            } catch (\Throwable) {
                // fall through to the plain error page
            }
        }
        $keys = [403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed', 419 => 'expired', 500 => 'server_error', 503 => 'unavailable'];
        $key = $keys[$status] ?? 'server_error';
        try {
            $translator = $this->translator ?? new Translator('en', 'en', null);
            $view = new View($translator, null, $this->headers->nonce());
            $body = $view->render('errors/page', ['status' => $status, 'key' => $key, 'reference' => $reference]);
        } catch (\Throwable) {
            $body = '<!doctype html><title>' . $status . '</title><p>Error ' . $status . ($reference !== null ? ' · ' . $reference : '') . '</p>';
        }
        return Response::html($body, $status);
    }

    /**
     * Writes the exception to storage/logs (full detail, never shown to visitors) and returns its reference code.
     * With `ops.error_email` set, the developer gets one email per hour at most — never one per error.
     */
    public function logException(\Throwable $e): string
    {
        $log = new ErrorLog(max(1, (int) $this->config->get('ops.log_retention_days', 30)));
        $reference = $log->write($e, ['method' => $this->request->method(), 'path' => $this->request->path()]);
        $this->notifyError($e, $reference);
        return $reference;
    }

    private function notifyError(\Throwable $e, string $reference): void
    {
        $to = $this->config->string('ops.error_email');
        // Without a database there is no queue to put the message in (and no rate limit to respect).
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false || !$this->installedFlow || $e instanceof \PDOException) {
            return;
        }
        try {
            // One message per hour: the first error opens the window, the rest are only logged.
            if ($this->limiter()->hit('ops:error-mail', 3600) !== 1) {
                return;
            }
            $site = $this->baseUrl();
            $text = 'An error occurred on ' . $site . ".\n\n"
                . 'Reference: ' . $reference . "\n"
                . 'Time (UTC): ' . gmdate('Y-m-d H:i:s') . "\n"
                . 'Request: ' . $this->request->method() . ' ' . ErrorLog::maskPath($this->request->path()) . "\n"
                . 'Error: ' . $e::class . ': ' . mb_substr($e->getMessage(), 0, 300) . "\n\n"
                . "The full detail is in storage/logs under this reference.\n"
                . "Further errors in the next hour are logged but not emailed.\n";
            $host = (string) (parse_url($site, PHP_URL_HOST) ?? $site);
            (new MailQueue($this->db(), $this->clock))->enqueue(new MailMessage($to, '', 'Error on ' . $host . ' — ' . $reference, $text));
            $this->defer(fn () => $this->sendQueuedMail(3, 10.0));
        } catch (\Throwable) {
            // Reporting an error must never cause another one.
        }
    }
}
