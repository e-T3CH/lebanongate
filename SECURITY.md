# Security

This document describes how the GATE Lebanon CMS defends itself, where each protection lives in the code, and what
the host must provide. The CMS is built on the core of an earlier application (BM-Matic) whose security review
(September 2026) is kept below: the findings and fixes F1–F5 apply to that shared core. The test suites that review
names (`SecurityTest`, `UploadSecurityTest`, `security-check.mjs`, `tools/secret-scan.php`) belong to that project
and are **not** part of this repository; the GATE additions were checked with PHPStan (level 8) and end-to-end runs
of every admin screen and form (see [How the GATE build was checked](#how-the-gate-build-was-checked)). It follows the [OWASP Top 10:2025](https://owasp.org/Top10/2025/).

Report a vulnerability to the developer privately (see the handover note), never in a public issue.

## Summary

| | |
| --- | --- |
| Review scope | Everything in the release zip: public site, admin panel, installer, console, backups |
| Findings | 5 fixed in this phase (below), 0 open |
| `composer audit` | No known vulnerabilities (2026-09-18) — production and development dependencies |
| Automated checks | Core review: `SecurityTest`, `UploadSecurityTest`, `security-check.mjs` (not in this repository). GATE build: PHPStan level 8, end-to-end admin and form runs |

### Findings and fixes in this phase

| # | Finding | Severity | Fix |
| --- | --- | --- | --- |
| F1 | **Decompression bomb.** A 1 KB PNG declaring 7,900 × 7,900 pixels passed the upload check (which only capped each side at 8,000 px) and made GD allocate ~300 MB while re-encoding: the request died with a fatal error. | Medium (an editor could take down PHP workers) | `MediaLibrary::inspect()` refuses more than 25 megapixels and anything that would not fit in `memory_limit` before GD decodes a byte (`MAX_MEGAPIXELS`, `fitsInMemory()`). Tests: `UploadSecurityTest::testADecompressionBombIsRefusedBeforeItIsDecoded`, `security-check.mjs` "decompression bomb". |
| F2 | **Server-side request to any https host** (reviewer photos in the earlier Google reviews module). | Low | The reviews module is not part of the GATE CMS, so the finding no longer applies. The HTTP client still refuses every scheme but http/https and never follows redirects (`Support\Http`). |
| F3 | **Error detail and log hygiene.** Uncaught errors were logged, but without a reference the visitor could quote, the log never rotated, and a logged path could contain an invitation token. | Low | `Support\ErrorLog`: reference code on the error page, full detail in `storage/logs` under that code, daily files with a size cap and retention, token-like path segments masked, query strings never logged. Tests: `SecurityTest::testAnErrorShowsAReferenceAndLogsTheDetailWithoutSecrets`, `testTokensInThePathNeverReachTheLog`, `OpsTest::testTheErrorLogRotatesAndForgets`. |
| F4 | **Spreadsheet formulas in exports.** The CSV exports wrote visitor text as-is; a name such as `=HYPERLINK("https://…")` would run as a formula when someone opened the export in Excel. | Low (needs a user to open and click) | `Support\Csv::cell()` prefixes cells that start with `=`, `+`, `-`, `@`, tab or CR with an apostrophe, in every export (messages, subscribers, security log). Test: `SecurityTest::testExportsNeverHandSpreadsheetsAFormula`. |
| F5 | **Least-privilege database user could not install.** The README granted `CREATE, ALTER, INDEX, DROP` but not `REFERENCES`; MySQL 8 refuses foreign keys without it, so a correctly restricted user failed halfway through the migrations. | Low (install failure, pushes people towards root) | README and GO-LIVE list `REFERENCES`; the installer now tries create / foreign key / alter / index / drop on two throw-away tables before it writes anything and explains which privileges are missing (`Installer::canMigrate()`). Verified: install, backup and restore as a user with exactly these privileges. |

Also fixed in Phase 5 and re-checked here: an emptied secret setting was encrypted into a value that could not be
decrypted (now stored as "nothing"), and a placeholder rating such as `[4.9]` could reach the schema.org data.

---

## A01:2025 Broken Access Control

- **One permission check for every admin route.** `config/permissions.php` is the single source of truth. Every
  route in `app/Http/AdminRoutes.php` carries `auth` and a `perm:<name>` flag; `App::handleAdmin()` checks them before
  any controller runs and logs refusals (`AuditLog::PERMISSION_DENIED`). Views and the sidebar ask the same question
  (`View::can()`, `AdminMenu::build()`), so a screen never offers what the request would refuse.
- **The matrix is tested, not assumed.** `SecurityTest` walks every admin route (86) as administrator, editor and
  signed-out visitor: signed out → redirect to sign-in, editor → 403 exactly where the role has no permission and
  never a 5xx, administrator → never 403. `php bin/console routes:list` prints the same matrix (appendix).
- **Hidden admin.** The admin lives under a random path (`security.admin_path`, set at install, `admin:path
  --regenerate`); `/admin` is a plain 404. An optional IP allowlist (`security.admin_ip_allowlist`) makes the panel a
  404 for every other address.
- **Last administrator.** Users are deactivated, never deleted; the last active administrator cannot be deactivated
  or demoted (`UserRepository::isLastActiveAdmin()`), so nobody can lock the team out by accident.
- **Server-side requests (SSRF).** Only two places make outgoing requests: SMTP (the configured server) and the
  optional off-site backup copy (the configured target). No redirects are followed.
- **Files.** Uploads get random names; public files come only from `public/`; the private folders are outside the
  web root or denied (A02).

## A02:2025 Security Misconfiguration

- **Web root separation.** Standard layout: the document root is `public/`. Split layout: `public_html/` holds only
  `index.php`, `paths.php`, assets and uploads; the application sits next to it. On hosts where everything must live
  inside the web root (one.com: the `webroot` release), the root `.htaccess` routes every request into `public/` and
  denies the private folders, each private folder has its own deny-all `.htaccess` as a second layer, and the
  installer refuses to continue when its own server hands out a private file (`Requirements::probePrivateFiles()`). `security-check.mjs` requests
  33 private paths (config, storage, vendor, .git, composer files, install, resources, tools, tests, source files,
  README, encoded and case-changed variants) on each layout and expects 403/404 with no content.
- **Only the front controller runs.** `public/.htaccess` refuses every `.php` except `index.php`;
  `public/uploads/.htaccess` turns PHP off, removes handlers, denies scripts and HTML/SVG, and sends
  `Content-Security-Policy: sandbox`. Tested by planting `.php`, `.php.png` and `.phtml` files in /uploads.
- **Headers on every response** (`Security\SecurityHeaders`): a CSP with a per-request nonce and no
  `'unsafe-inline'` or `'unsafe-eval'`, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`,
  `form-action 'self'`; `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin`, a restrictive `Permissions-Policy`, COOP/CORP; HSTS
  (`max-age=31536000; includeSubDomains`) over HTTPS once Force HTTPS is on; `no-store` on the admin panel.
  Analytics origins are added to the CSP only after consent. `X-Powered-By` is removed.
- **Force HTTPS** redirects to the configured host (never the `Host` header) and can only be switched on over HTTPS,
  so nobody locks themselves out.
- **Release hygiene.** `build:release` refuses to package tests, tools, design files, `.git`, `config.local.php`,
  `node_modules`, the `/design-check` gallery or dev assets (`ReleaseBuilder::FORBIDDEN`), and checks the staged tree.
- **Debug off.** `app.debug` is false; `display_errors` follows it (`public/index.php`).
- **Host responsibility:** `ServerTokens Prod`, PHP 8.2+ with current security patches, `expose_php = Off`.

## A03:2025 Software Supply Chain Failures

- Four runtime dependencies, all widely used and pinned in `composer.lock`: HTML Purifier, PHPMailer, Google2FA,
  BaconQrCode. Development tools (PHPUnit, PHPStan) are not in the release (`--no-dev`).
- No CDN at runtime: fonts, icons (a subset of Font Awesome) and scripts are served from the site itself, so a
  third party cannot change what runs in the visitor's browser.
- `composer audit` is part of the monthly routine (MAINTENANCE.md). Result of this review: no advisories.
  PHPMailer 7 and Google2FA 9 exist as major updates; they are not needed for security today (MAINTENANCE.md → Updates).

## A04:2025 Cryptographic Failures

- **Passwords:** Argon2id (`PasswordHasher`, 64 MB, 4 iterations), rehashed on sign-in when the parameters change;
  at least 12 characters, common and low-variety passwords refused, not containing the email name.
- **Secrets at rest** (SMTP password, TOTP secrets, the scheduler and newsletter keys): libsodium
  secretbox with a key derived from the app key (`Crypto`). The app key lives only in `config.local.php`.
- **Tokens:** invitation and email-change tokens are stored as HMACs, compared with `hash_equals`, and expire (72 h /
  2 h); newsletter confirm and unsubscribe tokens are stored as HMACs too. Visitor IPs are stored as HMACs (`ip_hash`), never in clear. Recovery codes are hashed.
- **Transport:** HTTPS enforced with HSTS; the session cookie is `__Host-`, `Secure`, `HttpOnly`, `SameSite=Strict`
  over HTTPS. Outgoing API calls verify TLS certificates.
- **Backups** contain the database, including encrypted secrets, but never the app key (`Ops\Backups`); the manifest
  keeps only a fingerprint so a restore can warn when the keys differ.

## A05:2025 Injection

- **SQL:** every query uses prepared statements (`Core\Database`, native prepares, not emulated); table names pass a
  whitelist (`Core\Tables`) and column names a strict pattern; sort columns come from fixed lists
  (for example the message and entry lists). Search uses `LIKE` with `%` and `_` escaped.
- **HTML:** views escape by default (`e()`, `e_attr()`, `e_url()`, `e_js()`, `e_css()` in `app/helpers.php`);
  components validate their props (`Core\Props`). Rich text is cleaned with HTML Purifier on save **and** on render
  (`Site\RichText`). The CSP nonce blocks any script that still slipped in — `security-check.mjs` injects a script,
  an inline handler and an external script and proves none runs; reflected form input is re-rendered escaped.
- **Email headers:** `MailMessage` refuses line breaks in subject, names and reply-to.
- **CSV export:** cells that start with `=`, `+`, `-`, `@`, tab or CR get an apostrophe, so spreadsheets show them as text instead of running them (`Support\Csv`, F4).
- **Files:** uploads are judged by content, never by name. Images (`getimagesize`) are re-encoded with GD; PDFs must
  start with the `%PDF-` signature and be detected as `application/pdf` by `fileinfo`, and are served through a download route with
  `Content-Type: application/pdf` and `X-Content-Type-Options: nosniff`, never executed. Files are stored under a
  random name with the extension of the detected type; the client's file name is only a label (`MediaLibrary`). A file
  can only be replaced by one of the same kind.

## A06:2025 Insecure Design

- **Abuse limits** (`Security\RateLimiter`, stored in MySQL so they work on shared hosting): sign-in per account and
  per IP with lockout (`LoginThrottle`), two-factor attempts, the contact and newsletter forms (honeypot, signed time
  trap, hourly limit per IP — `SpamGuard`), backups and the scheduler (locks).
- **Uploads** have a size, pixel and memory budget (F1); PDFs are capped at 25 MB.
- **Destructive actions** ask for confirmation in a modal; restore from backup asks to type `RESTORE` and makes a
  safety backup first.
- **Secure defaults:** new content hidden until published, newsletter double opt-in, analytics off until consent, admin path random,
  Force HTTPS on (switchable only over HTTPS).

## A07:2025 Authentication Failures

- **Sign-in** returns the same generic answer for every failure and spends the same time on unknown accounts
  (`verifyDummy`), so accounts cannot be enumerated. Lockout after 5 failures per account / 20 per IP (settings).
- **Two-factor** (TOTP, `Security\TwoFactor`) with single-use codes (last time step stored), hashed recovery codes,
  a 5-attempt limit, and an option to require 2FA for every administrator.
- **Sessions** (`Security\NativeSession`, `Services\AuthService`): strict mode (an ID chosen by someone else is
  never accepted), cookies only, new ID at sign-in and on every privilege change, idle timeout (30 min default),
  sign-out destroys the server-side session and rotates the CSRF token, a password change or reset signs out every
  other device (session epoch), a deactivated user is out on the next request. Concurrent sessions of one user are
  allowed (laptop + phone). All of this is tested in `SecurityTest` and, with real cookies, in `security-check.mjs`
  (fixation, replay after sign-out, two devices).
- **Re-authentication** for sensitive changes (password, 2FA, email address — which also needs confirmation by mail).
- **Password reset by email** (`PasswordResetController`, added for hosts without SSH): one generic answer whether
  the address has an account or not (no enumeration), rate-limited per IP (5/h) and per address (3/h), a 256-bit
  token that exists only in the email (the database keeps an HMAC), valid for one hour and once, a new request
  cancels older links; the new password passes the same policy, ends every session of that user and clears the
  login lockout; two-factor authentication stays on, so a stolen mailbox alone does not open an account with 2FA.
  Both steps are in the security log.
- **Other recovery** (2FA reset, unlocking, a new admin address) only from the server's command line
  (`security:2fa-reset`, `security:unlock`, `admin:path --regenerate`), never over the web.

- **Web tools for hosts without SSH** (Settings → Maintenance, `security.manage` — administrators only): a backup
  download is streamed from the backup folder after the name is matched against the folder's own list (never a
  path from the request); an uploaded backup must pass the manifest check or it is deleted, and restoring needs the
  password again plus typing RESTORE, makes a safety backup first and signs everyone out. The scheduler address
  `/cron/<token>` can only start the scheduled work (no output, no data), its token is an encrypted setting compared
  in constant time, and a wrong token is the normal 404.

## A08:2025 Software or Data Integrity Failures

- **CSRF** tokens on every state-changing request (`Security\Csrf`, form field or `X-CSRF-Token` header for AJAX);
  tested for every admin POST route. `SameSite=Strict` session cookies add a second layer.
- **Installer** locks itself after installation (`storage/installed.lock`); `/install` is a 404 afterwards.
- **Backups** carry a manifest with per-table checksums; a restore verifies them and refuses files without a valid
  manifest.
- **Releases** are built from the repository by `build:release`, never edited on the server; assets carry content
  hashes.

## A09:2025 Security Logging and Alerting Failures

- **Security log** (`Services\AuditLog`, Security → Log): sign-ins, failures, lockouts, 2FA, permission refusals,
  CSRF failures, blocked admin IPs, user and settings changes, message archive, delete and export, subscriber export and delete, content and media changes, restores —
  with user, hashed IP and user agent; filterable, exportable, with a retention setting.
- **Error log** with reference codes (F3); optional developer email at most once an hour (`ops.error_email`).
- **Monitoring:** `/health` (token in `config.local.php`) reports database, storage, mail queue, scheduler and
  backup age for an uptime monitor (MAINTENANCE.md).
- **No secrets in logs:** passwords are `#[\SensitiveParameter]`, tokens are masked in logged paths, and
  `tools/secret-scan.php` checks after the full test run that no password, key, token or API secret appears in
  `storage/logs`, the security log or the web server's error log.

## A10:2025 Mishandling of Exceptional Conditions

- Every uncaught exception ends in `App::handle()`: the visitor gets a plain error page with a reference, the
  detail goes to the log; database failures give 503. Stack traces are never shown.
- External failures are expected, not exceptional: email failures stay in the queue and retry with backoff, and
  deferred work runs after the response and cannot break it.
- `/health` still answers (with "fail") when the database is down, because its token is not in the database.

---

## Hosting checklist (what the application cannot do itself)

- PHP 8.2 or newer with `sodium`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (WebP), `curl`; `expose_php = Off`.
- A dedicated MySQL user with only the privileges the app needs on its own database (SELECT, INSERT, UPDATE, DELETE,
  CREATE, ALTER, INDEX, DROP, REFERENCES) — never root. See deploy/GO-LIVE.md.
- HTTPS certificate (Let's Encrypt is fine), then switch on Force HTTPS in Security.
- `config/config.local.php` readable only by the account that runs PHP (`chmod 440`).
- Apache/LiteSpeed with `AllowOverride All` (the `.htaccess` files), or the rules in `deploy/nginx.conf.example`.
- Daily backups (`backup:run`) with an off-site copy, and an uptime monitor on `/health`.

## How the core review was verified (BM-Matic, Phase 6)

| Check | Result |
| --- | --- |
| `SecurityTest` — 86 admin routes × 3 roles, CSRF on every POST, sessions, errors, /health | pass |
| `UploadSecurityTest` — disguised PHP, double extension, SVG, oversized, bomb, zip, null byte, polyglot | pass |
| `security-check.mjs` — standard, split and project-folder document root, plus HTTPS | 37 / 37 |
| Clean room: the release zip in an empty folder, PHP confined to it (`open_basedir`), fresh database, install + e2e | pass |
| `composer audit` (with and without dev) | no advisories |
| `tools/secret-scan.php` after the full end-to-end run | clean |

## How the GATE build was checked

| Check | Result |
| --- | --- |
| PHPStan level 8 on `app/` | no new findings in the GATE code |
| Every admin screen in English and French, signed in as administrator | 200, no untranslated keys, no console errors |
| Contact form, newsletter sign-up (double opt-in), message notes, archive and CSV export | pass |
| Uploads: image, PDF, replacing a PDF with an image (refused), PDF download headers | pass |
| Creating and publishing a project, publication and album; section, partner, figure and settings saves | pass |
| Public pages in English, Arabic and French, 404 page, sitemap, robots.txt | pass |

Before go-live, run the hosting checklist above and an external scan (for example securityheaders.com and Mozilla
Observatory) against the live domain.

## Appendix: authorisation matrix

Generated with `php bin/console routes:list`. "403" means the role is refused with a 403 page and the attempt is
logged; "login" means a signed-out visitor is sent to the sign-in page.

| Method | Path | Permission | Admin | Editor | Signed out |
| --- | --- | --- | --- | --- | --- |
| GET | `/<admin>/login` | public | yes | yes | yes |
| POST | `/<admin>/login` | public | yes | yes | yes |
| POST | `/<admin>/logout` | signed in | yes | yes | login |
| GET | `/<admin>/two-factor` | public | yes | yes | yes |
| POST | `/<admin>/two-factor` | public | yes | yes | yes |
| POST | `/<admin>/two-factor/cancel` | public | yes | yes | yes |
| GET | `/<admin>/forgot-password` | public | yes | yes | yes |
| POST | `/<admin>/forgot-password` | public | yes | yes | yes |
| GET | `/<admin>/reset-password/{token}` | public | yes | yes | yes |
| POST | `/<admin>/reset-password/{token}` | public | yes | yes | yes |
| GET | `/<admin>/invitation/{token}` | public | yes | yes | yes |
| POST | `/<admin>/invitation/{token}` | public | yes | yes | yes |
| GET | `/<admin>/email-change/{token}` | signed in | yes | yes | login |
| GET | `/<admin>` | dashboard.view | yes | yes | login |
| POST | `/<admin>/quick-toggle` | settings.manage | yes | 403 | login |
| GET | `/<admin>/messages` | messages.view | yes | yes | login |
| GET | `/<admin>/messages/export` | messages.view | yes | yes | login |
| GET | `/<admin>/messages/{id}` | messages.view | yes | yes | login |
| POST | `/<admin>/messages/{id}/notes` | messages.manage | yes | yes | login |
| POST | `/<admin>/messages/{id}/notes/{note}/delete` | messages.manage | yes | yes | login |
| POST | `/<admin>/messages/{id}/unread` | messages.manage | yes | yes | login |
| POST | `/<admin>/messages/{id}/archive` | messages.manage | yes | yes | login |
| POST | `/<admin>/messages/{id}/delete` | messages.manage | yes | yes | login |
| GET | `/<admin>/subscribers` | subscribers.manage | yes | yes | login |
| GET | `/<admin>/subscribers/export` | subscribers.manage | yes | yes | login |
| POST | `/<admin>/subscribers/{id}/delete` | subscribers.manage | yes | yes | login |
| GET | `/<admin>/pages` | content.view | yes | yes | login |
| GET | `/<admin>/pages/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/pages/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/pages/{id}/sections` | content.edit | yes | yes | login |
| GET | `/<admin>/pages/{id}/sections/{section}` | content.view | yes | yes | login |
| POST | `/<admin>/pages/{id}/sections/{section}` | content.edit | yes | yes | login |
| GET | `/<admin>/expertise` | content.view | yes | yes | login |
| POST | `/<admin>/expertise/new` | content.edit | yes | yes | login |
| POST | `/<admin>/expertise/order` | content.edit | yes | yes | login |
| GET | `/<admin>/expertise/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/expertise/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/expertise/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/projects` | content.view | yes | yes | login |
| POST | `/<admin>/projects/new` | content.edit | yes | yes | login |
| GET | `/<admin>/projects/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/projects/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/projects/{id}/gallery` | content.edit | yes | yes | login |
| POST | `/<admin>/projects/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/news` | content.view | yes | yes | login |
| POST | `/<admin>/news/new` | content.edit | yes | yes | login |
| GET | `/<admin>/news/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/news/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/news/{id}/gallery` | content.edit | yes | yes | login |
| POST | `/<admin>/news/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/publications` | content.view | yes | yes | login |
| POST | `/<admin>/publications/new` | content.edit | yes | yes | login |
| GET | `/<admin>/publications/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/publications/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/publications/{id}/gallery` | content.edit | yes | yes | login |
| POST | `/<admin>/publications/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/albums` | content.view | yes | yes | login |
| POST | `/<admin>/albums/new` | content.edit | yes | yes | login |
| GET | `/<admin>/albums/{id}` | content.view | yes | yes | login |
| POST | `/<admin>/albums/{id}` | content.edit | yes | yes | login |
| POST | `/<admin>/albums/{id}/gallery` | content.edit | yes | yes | login |
| POST | `/<admin>/albums/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/content/{type}` | content.view | yes | yes | login |
| POST | `/<admin>/content/{type}` | content.edit | yes | yes | login |
| POST | `/<admin>/content/partners/partners` | content.edit | yes | yes | login |
| POST | `/<admin>/content/{type}/new` | content.edit | yes | yes | login |
| POST | `/<admin>/content/{type}/{id}/delete` | content.edit | yes | yes | login |
| GET | `/<admin>/media` | media.view | yes | yes | login |
| POST | `/<admin>/media/upload` | media.manage | yes | yes | login |
| POST | `/<admin>/media/{id}/replace` | media.manage | yes | yes | login |
| POST | `/<admin>/media/{id}/alt` | media.manage | yes | yes | login |
| POST | `/<admin>/media/{id}/delete` | media.manage | yes | yes | login |
| GET | `/<admin>/users` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/invite` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/invitations/{id}/resend` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/invitations/{id}/cancel` | users.manage | yes | 403 | login |
| GET | `/<admin>/users/{id}` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/{id}` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/{id}/active` | users.manage | yes | 403 | login |
| POST | `/<admin>/users/{id}/two-factor-reset` | users.manage | yes | 403 | login |
| GET | `/<admin>/profile` | signed in | yes | yes | login |
| POST | `/<admin>/profile` | signed in | yes | yes | login |
| POST | `/<admin>/profile/password` | signed in | yes | yes | login |
| GET | `/<admin>/security` | security.manage | yes | 403 | login |
| POST | `/<admin>/security` | security.manage | yes | 403 | login |
| POST | `/<admin>/security/two-factor/setup` | signed in | yes | yes | login |
| GET | `/<admin>/security/two-factor/setup` | signed in | yes | yes | login |
| POST | `/<admin>/security/two-factor/confirm` | signed in | yes | yes | login |
| GET | `/<admin>/security/two-factor/recovery-codes` | signed in | yes | yes | login |
| POST | `/<admin>/security/two-factor/recovery-codes` | signed in | yes | yes | login |
| POST | `/<admin>/security/two-factor/disable` | signed in | yes | yes | login |
| GET | `/<admin>/security/log` | security.manage | yes | 403 | login |
| GET | `/<admin>/security/log/export` | security.manage | yes | 403 | login |
| POST | `/<admin>/security/log/retention` | security.manage | yes | 403 | login |
| GET | `/<admin>/appearance` | appearance.manage | yes | 403 | login |
| POST | `/<admin>/appearance` | appearance.manage | yes | 403 | login |
| POST | `/<admin>/appearance/reset` | appearance.manage | yes | 403 | login |
| GET | `/<admin>/settings/general` | settings.manage | yes | 403 | login |
| POST | `/<admin>/settings/general` | settings.manage | yes | 403 | login |
| GET | `/<admin>/settings/languages` | languages.manage | yes | 403 | login |
| POST | `/<admin>/settings/languages` | languages.manage | yes | 403 | login |
| GET | `/<admin>/settings/social` | settings.manage | yes | 403 | login |
| POST | `/<admin>/settings/social` | settings.manage | yes | 403 | login |
| GET | `/<admin>/settings` | settings.manage | yes | 403 | login |
| GET | `/<admin>/settings/email` | settings.manage | yes | 403 | login |
| POST | `/<admin>/settings/email` | settings.manage | yes | 403 | login |
| POST | `/<admin>/settings/email/test` | settings.manage | yes | 403 | login |
| GET | `/<admin>/settings/maintenance` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/scheduler/run` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/scheduler/regenerate` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/backups` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/backups/upload` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/backups/restore` | security.manage | yes | 403 | login |
| GET | `/<admin>/settings/maintenance/backups/{file}` | security.manage | yes | 403 | login |
| POST | `/<admin>/settings/maintenance/analytics` | security.manage | yes | 403 | login |
