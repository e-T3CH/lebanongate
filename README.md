# BM-Matic website

Public website and admin panel for BM-Matic (automatic transmission repair, Aalst). It is a PHP 8.2+ / MySQL product built for ordinary shared hosting (Hostinger and similar Apache/LiteSpeed hosts). The server needs no Node and no build step; cron jobs are needed for backups and the review sync, and SSH is handy for the console.

## Documentation

| Document | For | What is in it |
| --- | --- | --- |
| [README.md](README.md) (this file) | Developer | Architecture, folders, running and checking the code, releases, console, conventions |
| [deploy/GO-LIVE.md](deploy/GO-LIVE.md) | Owner + developer | From zero to a live site: hosting, database, upload, installer, cron, domain, content, Google |
| [deploy/DNS-AND-EMAIL.md](deploy/DNS-AND-EMAIL.md) | Owner + developer | DNS records, SPF / DKIM / DMARC for the sending domain |
| [MAINTENANCE.md](MAINTENANCE.md) | Developer | Cron jobs, monitoring, the monthly routine, what to do when something breaks |
| [SECURITY.md](SECURITY.md) | Developer | The security review (OWASP Top 10:2025), findings, tests, hosting checklist, authorisation matrix |
| [USER-GUIDE.md](USER-GUIDE.md) / [USER-GUIDE.nl.md](USER-GUIDE.nl.md) | The workshop | The admin panel in plain language, with screenshots |
| [docs/GOOGLE-REVIEWS.md](docs/GOOGLE-REVIEWS.md) | Owner | Connecting the Google reviews, step by step |
| [design/DESIGN-SPEC.md](design/DESIGN-SPEC.md) | Developer | The approved design, baselines, motion rules (repository only) |

## Architecture

A plain PHP application without a framework, so it runs on any shared host: one front controller, a small kernel, and
code organised by responsibility.

```
request → public/index.php → Core\App::handle()
            ├─ /health (token)          → Ops\Health                       (before the database is touched)
            ├─ not installed            → Install\InstallController       (/install wizard)
            └─ installed
                 ├─ Force HTTPS redirect, session, CSRF
                 ├─ /<admin path>/…     → Http\AdminRoutes → one permission check (config/permissions.php)
                 │                        → Http\Controllers\Admin\* → Repositories / Services → Views/admin
                 └─ everything else     → Http\Controllers\Site\SiteController (i18n routing, pages, form, SEO)
          ← Security\SecurityHeaders (CSP nonce, HSTS…) ← App::finish() sends, then runs deferred work (mail, photos)
```

- **Views** are PHP templates built from components (`app/Views/components`, props checked by `Core\Components`);
  output is escaped by default. **CSS/JS** are built once on the development machine (`tools/assets/build.mjs`) into
  per-page bundles with content hashes, and committed — the server never needs Node.
- **Data** goes through `Core\Database` (PDO, prepared statements, a table whitelist) and repositories; schema changes
  are numbered migrations in `database/migrations`; defaults and translations come from idempotent seeders.
- **Settings** live in the `settings` table (`Services\Settings`, secrets encrypted); only the database credentials,
  the app key and operations options live in `config/config.local.php`.
- **Background work** without a queue server: email is queued in MySQL and sent after the response or by cron; review
  syncs and backups run from cron through `bin/console`.

## Folder map

| Folder | What is in it |
| --- | --- |
| `app/Core` | Kernel (`App`), config, paths, database, migrator, views, components, theme |
| `app/Http` | Request/response, router, admin routes, controllers (`Admin/*`, `Site/*`) |
| `app/Security` | Sessions, CSRF, headers, crypto, passwords, 2FA, rate limits, spam guard, IP handling |
| `app/Services` | Settings, auth, audit log, media library, status emails, section order |
| `app/Repositories` | One class per group of tables |
| `app/Site` | Public-site presentation: SEO, consent, rich text, media variants, URL building |
| `app/Reviews` | Google review providers, sync, photos |
| `app/Mail` | Message, queue, worker, SMTP transport, mail templates |
| `app/Ops` | Backups, database dump, `/health`, `content:check` |
| `app/Console` | `bin/console` commands, release builder |
| `app/Install` | The installation wizard (locked after installation) |
| `app/Views` | Templates: `admin/`, `site/`, `components/`, `layouts/`, `errors/` |
| `config` | `app.php` (defaults), `permissions.php`, `admin-menu.php`, `service-icons.php`; `config.local.php` is written by the installer |
| `database` | Migrations and seeders (content texts in `seeders/content/{lang}.php`) |
| `lang` | Interface strings per language (`admin`, `site`, `ui`, `install`, `validation`) |
| `public` | The web root: `index.php`, `paths.php`, built assets, uploads |
| `resources` | Source CSS/JS and icons (built into `public/assets`) — not in releases |
| `storage` | Sessions, logs, cache, backups — never web-reachable |
| `tests` | PHPUnit (`Unit`, `Integration`) and Playwright end-to-end tests (`e2e`) |
| `tools` | Asset build, visual/accessibility/performance checks, deploy and guide scripts — not in releases |
| `design`, `mockups` | The approved design and its baselines — not in releases |
| `deploy`, `docs` | Server examples and handover documents |

## Requirements

- PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `sodium`, `json`, `ctype` and `fileinfo`; `gd` with WebP (image uploads, responsive images) and `curl` (Google reviews, off-site backups)
- Cron jobs for backups and the review sync (deploy/GO-LIVE.md)
- MySQL 8 or MariaDB 10.6+ (utf8mb4)
- Apache 2.4 or LiteSpeed with `.htaccess` (mod_rewrite), or nginx using `deploy/nginx.conf.example`
- HTTPS for production. Session cookies are `Secure` and `__Host-` prefixed on HTTPS.

The `/install` wizard checks these requirements on the server before anything is written.

## Deploy layouts

The web root must contain only the contents of `public/`. Everything else (app code, config, storage, vendor, installer, language files) sits outside it. One value selects the layout: `public/paths.php`, which `build:release` writes for you.

```php
return ['layout' => 'standard', 'app_dir' => 'bmmatic-app'];
```

| Layout | Folder structure on the server | Use when |
| --- | --- | --- |
| `standard` | `bm-matic/` (app) with `bm-matic/public/` as the document root | You can choose the document root (VPS, Hostinger "custom document root", nginx) |
| `split` | `public_html/` holds the contents of `public/`, and `bmmatic-app/` holds everything else as a sibling folder | The document root is fixed and there is a private folder next to it: cPanel (`public_html`), **one.com** (`httpd.www` + `httpd.private`: build with `--web-dir=httpd.www --app-dir=httpd.private`) |
| `webroot` | Everything is unpacked into the web root itself; the root `.htaccess` sends every request into `public/`, and every private folder carries its own deny-all `.htaccess` | Everything has to live inside the web root (fallback when there is no private folder) |

The front controller resolves every path from this value: `standard` means the parent of the web root, `split` means `<parent of the web root>/<app_dir>`. There are no hard-coded `../` paths. `app_dir` may only be a plain folder name.

`webroot` is the `standard` layout unpacked without its wrapper folder (its `paths.php` says `standard`). Because the private folders are inside the web root there, the release adds a deny-all `.htaccess` to `app`, `bin`, `config`, `database`, `deploy`, `docs`, `lang`, `storage` and `vendor`, and the installer refuses to continue when its own server hands out `composer.json`, `vendor/composer/installed.json` or a file from `storage/` (step 1, "Private files are not downloadable"). The same protection covers a standard install whose document root could not be set to `public/`. Tested on Apache with the unpacked release as document root: every end-to-end suite, the HTTP security check, backup/restore and Lighthouse.

## Building a release

Run this on the development machine. It needs PHP with `zip` or `phar` support, plus Composer.

```bash
php bin/console build:release --layout=standard
```

```bash
php bin/console build:release --layout=split --app-dir=bmmatic-app --composer=C:/laragon/bin/composer/composer.phar
```

```bash
php bin/console build:release --layout=split --web-dir=httpd.www --app-dir=httpd.private
```

```bash
php bin/console build:release --layout=webroot
```

This creates `build/bm-matic-<layout>-<timestamp>.zip`. The zip contains:

- `vendor/` from `composer install --no-dev --optimize-autoloader` (classmap-authoritative)
- the built assets from `public/assets` (they are committed, so the server never needs Node)
- `app`, `config/app.php`, `database`, `lang`, `bin/console`, `deploy`, `composer.json`/`.lock` and the web root with `paths.php` for the chosen layout
- an empty `storage/` skeleton (`sessions`, `logs`, `cache`) with its deny `.htaccess`

The build refuses to create the zip if the staged tree contains tests, tools, `node_modules`, `.git`, `config/config.local.php`, mock-ups, design files or PHPUnit/PHPStan config. It never packs storage contents or uploaded files. Pass `--keep` to keep the staging folder so you can inspect it.

## Installing on shared hosting

1. **Create the database.** Create a dedicated database user with only the privileges this app needs (never root in production). Use one user per site, limited to this database:
   - Running the site: `SELECT, INSERT, UPDATE, DELETE`
   - Installer, migrations and `backup:restore`: also `CREATE, ALTER, INDEX, DROP, REFERENCES` (the tables use foreign keys; the installer tests this before it writes anything). You can revoke these after installation and grant them again before an update or a restore.

   ```sql
   CREATE USER 'bmmatic'@'localhost' IDENTIFIED BY '<long random password>';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON `bmmatic`.* TO 'bmmatic'@'localhost';
   ```

   On hosts without SQL access, the control panel usually creates a user that is already limited to one database.
2. **Upload** the zip and extract it:
   - **standard:** upload `bm-matic/` and set the document root to `bm-matic/public`.
   - **split:** extract `public_html/` into the existing `public_html/`, and `bmmatic-app/` next to it (not inside it).
3. **Check that `storage/` and `config/` are writable** by PHP. The installer writes `config/config.local.php` and `storage/installed.lock`.
4. **Open `https://your-domain/`.** You are redirected to `/install`. Go through requirements → database → website → admin account → languages. The last step shows the secret admin sign-in address once, so store it in a password manager.
5. **Sign in** and enable two-factor authentication under Security.

After installation `/install` returns 404 for good. Nothing under `app/`, `config/`, `storage/`, `vendor/`, `.git/` or `composer.json` can be reached over HTTP in either layout: requests get 403 or 404.

### Updating

Build a new release. Upload everything except `config/config.local.php`, `storage/` and `public/uploads/` (or
`public_html/uploads/`). The database is brought up to date by the first request afterwards (`Ops\SchemaUpdater`:
new migrations, then the idempotent settings, interface-string and status-email seeders; page content and languages
are never re-seeded). It compares a signature of the migration, seeder and language files (names, sizes, dates) with
the one stored in the `app.schema` setting, so a normal request costs a few file stats; one request updates under a
lock while others get a 503 with `Retry-After: 30`. With SSH you can still run it yourself:

```bash
php bin/console migrate
```

```bash
php bin/console seed
```

The seeder adds missing settings and brings the interface strings (the `lang/` files) up to date. It never overwrites settings, texts or content changed in the admin panel.

## The public website

### Languages and URLs

Every public page lives under a language prefix with a translated slug: `/nl/`, `/nl/diensten`, `/nl/diensten/diagnose`,
`/fr/a-propos`, `/en/contact`. `/` redirects to the visitor's language (the `bm_lang` cookie, then `Accept-Language`,
then the default language). Pages carry a canonical URL, `hreflang` links including `x-default`, Open Graph tags and
schema.org JSON-LD (`AutoRepair` + `LocalBusiness` from the contact settings, `Service` and `BreadcrumbList` where they
apply). `sitemap.xml` is an index of one sitemap per language; `robots.txt` points at it. Old URLs are redirected
through the `redirects` table (`bin/console redirects:import`).

### Content model

Content lives in the database, with one `*_translations` table per language:

| Table | Holds |
| --- | --- |
| `pages`, `page_translations` | The nine pages (home, services, transmissions, about, reviews, contact, privacy, cookies, terms): slug, navigation label, headings, body, meta title and description |
| `page_sections`, `page_section_translations` | The home page sections (hero, stats, services, process, transmissions, reviews, contact and the locked top bar, header and footer): type, order, enabled, and the texts of each section |
| `services`, `service_translations` | The services, their icon, order, and whether they appear in the menu and on the home page |
| `transmission_types`, `process_steps`, `stats`, `partners` (+ translations) | The lists used by the home and content pages |
| `appointments` | Requests from the contact form (status `new`) |
| `mail_queue` | Outgoing emails (see below) |
| `redirects` | Old URL → new URL, with a hit counter |

The seeder fills these with the approved mock-up copy in all three languages, keeping the `[bracketed]` placeholders
for the details the owner still has to supply (address, phone, VAT number, social profiles, opening hours). The
sections of the home page can be reordered and switched off; the header and footer are always shown. The admin screens
for editing all of this come in Phase 4.

### Appointment form and email

The contact form (on the contact page and in the home page's contact section) validates on the server, keeps what was
typed when something is wrong and shows one message per field. It is protected by the CSRF token, a honeypot field, a
signed time trap (`forms.min_seconds`, default 3 seconds) and a per-IP hourly limit (`forms.max_per_hour`, default 5).

Requests are stored and two emails are queued: one to the workshop and a confirmation to the customer in the language
of the page. Emails are sent **after** the response has been delivered, so a slow mail server never delays a page; the
queue retries with backoff. Settings → Email holds the SMTP settings (password stored encrypted) and a test button. On
hosts where sending after the response is not possible, add a cron job:

```bash
php bin/console mail:work
```

### Cookies and analytics

The banner asks once, stores the choice in the `bm_consent` cookie with a version, and is shown again when the version
changes. Analytics (GA4 or Plausible, `analytics.provider`) is only rendered after consent, and the Content Security
Policy is extended for that provider only then. The footer has a "Cookie settings" link that reopens the banner, which
is also how consent is withdrawn. Analytics is off by default; switch it on with `php bin/console analytics:set ga4 G-…`
(or `plausible <domain>`), and name the provider in the cookie and privacy policies.

## The admin panel

Day-to-day use is described in [USER-GUIDE.md](USER-GUIDE.md); this section is about how it is built.

### Roles and permissions

`config/permissions.php` is the single source of truth: it lists the permissions and gives them to the roles
(`admin` may everything, `editor` works with requests, content and media). The same answer is used in three places,
so a screen can never offer what the request would refuse:

- every admin route carries a `perm:<name>` flag (`app/Http/AdminRoutes.php`) that `App` checks before the
  controller runs — a refused request is logged and gets the 403 page;
- the sidebar (`config/admin-menu.php`) hides items whose permission the role lacks, and items whose module has no
  route yet;
- views ask `$view->can('<name>')` (controllers: `$this->can(...)`).

Users are deactivated, never deleted, so the security log keeps making sense, and the last active administrator can
neither be deactivated nor demoted.

### Modules

| Screen | What it does |
| --- | --- |
| Dashboard | Live counts, the newest requests and quick controls that save with a small POST (fetch when JavaScript is on, a normal submit otherwise) |
| Appointments | Status workflow, internal notes, filters, search, pagination, CSV export, optional customer email per status |
| Messages | The same requests as an inbox: unread is `read_at IS NULL`, independent of the status |
| Pages, Services, lists | Content per language with a publish state, drag-and-drop ordering (arrow buttons for the keyboard) and automatic redirects when a slug changes |
| Media | Uploads checked on their content, re-encoded, random file names, alt text per language, usage check before deleting |
| Appearance | Logo, favicon, colour tokens with a live contrast check, radii, motion setting, reset to the approved design |
| Settings | General, Languages (with real translation progress), Security, Social media, Email |
| Google reviews | One screen for the connection, the imported reviews and what appears on the website (see below) |
| Security log | Filterable audit trail with CSV export and a retention setting |

Rich text (page and service bodies) is sanitised with HTML Purifier when it is saved **and** again when it is
rendered, so content that was edited straight in the database can never inject markup.

### Google reviews

Three sources behind one `ReviewProvider` contract (`app/Reviews/`): the Business Profile API (OAuth, the full
history), the Places API (New) (an API key, five reviews at most) and a manual JSON/CSV import with a column mapper.
`ReviewSync` runs them the same way: it adds, updates and marks what Google no longer returns, never deletes, and
**never writes `is_visible`** — what appears on the website is the workshop's choice, and every change to it is
audit-logged. The aggregate rating and count are stored exactly as Google reports them and passed to
`schema.org/AggregateRating`; no `Review` markup is emitted, because a hand-picked selection would misrepresent the
whole (EU consumer review rules).

A sync is safe to run twice: it takes a lock through the rate-limiter table, and an API error backs the next attempt
off (5, 15, 45 minutes, 2 and 6 hours) instead of hammering Google. For cron:

```bash
php bin/console reviews:sync --cron
```

Reviewer photos are stored as a URL only. The site fetches them itself after a sync or a visibility change and serves
them from `/review-photo/{id}.jpg`, so no visitor request reaches Google; a photo that cannot be fetched falls back to
the initials the design draws. [docs/GOOGLE-REVIEWS.md](docs/GOOGLE-REVIEWS.md) explains the whole setup for the owner.

`php bin/console reviews:api-base <url>` points every Google call at a test endpoint (`tests/e2e/google-mock.mjs`) and
the Connection card shows a warning while it is set, so it can never be forgotten on a live site. Clear it with
`reviews:api-base --clear`.

## Recovery from the command line

These commands only run from the PHP CLI (they exit with code 64 under any web SAPI, and `bin/console` returns 404 over HTTP). Every action is written to the audit log with `via=cli`. Run them from the application folder (`bm-matic/` or `bmmatic-app/`).

| Command | What it does |
| --- | --- |
| `php bin/console admin:path` | Print the full admin sign-in URL |
| `php bin/console admin:path --regenerate` | Replace the admin path with a new random one; printed once and never written to the audit log |
| `php bin/console security:2fa-reset <email>` | Disable 2FA and delete the recovery codes of that user |
| `php bin/console user:password-reset <email>` | Prompt twice for a new password (hidden input), check its strength, store it with Argon2id, end all of that user's sessions and clear the login lockout |
| `php bin/console security:unlock <email> [ip]` | Clear a login lockout without changing the password |
| `php bin/console forms:unlock <ip>` | Clear the appointment form rate limit for one visitor |
| `php bin/console mail:work [--limit=20]` | Send the queued emails now (for a cron job; the site also sends them after each request) |
| `php bin/console mail:test <email>` | Send a test email with the saved SMTP settings |
| `php bin/console redirects:import <file.csv>` | Import old URL redirects (`from_path,to_path[,status]`) |
| `php bin/console reviews:sync [--dry-run] [--force] [--cron]` | Import the Google reviews (for a cron job; `--cron` only runs when it is due) |
| `php bin/console reviews:api-base <url> \| --clear` | Point the Google calls at a test endpoint (development only) |
| `php bin/console schedule:run` | All scheduled work in one go (emails, review sync when due, daily backup); one cron line every 15 minutes |
| `php bin/console schedule:url [--regenerate]` | Print the scheduler address for a web cron service (cron-job.org), or replace it |
| `php bin/console backup:run [--no-offsite]` | Back up the database and the uploads (daily cron job; see Operations) |
| `php bin/console backup:list` | List the backups, newest first |
| `php bin/console backup:restore <file> [--no-safety-backup]` | Replace the database and the uploads with a backup; asks you to type `RESTORE` and makes a safety backup first |
| `php bin/console content:check` | List unfinished content per language: [placeholders], missing translations, alt texts, SEO fields. Exit code 1 while anything is left |
| `php bin/console health:url` | Print the uptime-monitor URL (`/health` with its token) |
| `php bin/console analytics:set none \| ga4 <G-ID> \| plausible <domain>` | Choose the analytics that load after consent |
| `php bin/console routes:list` | The admin routes with their permission per role (the authorisation matrix, as Markdown) |

For scripts, `user:password-reset` also accepts `--password-stdin`. `php bin/console help` lists every command, including `migrate`, `seed` and a non-interactive `install`.

## Operations

The day-to-day routine is in [MAINTENANCE.md](MAINTENANCE.md); this is how the pieces work.

- **Scheduled work** (`Ops\Scheduler`) — emails, the review sync when due and the daily backup, from
  `schedule:run` (cron) or the scheduler address `/cron/<token>` (hosts without cron: a web cron service such as
  cron-job.org calls it; the token is an encrypted setting, a wrong one is a 404; the answer is sent before the work
  starts). A lock prevents overlapping runs; the last run and its result are shown in Settings → Maintenance.
  cron-job.org: deploy/GO-LIVE.md step 8; Hostinger's format: its appendix.
- **Without a command line** — Settings → Maintenance (administrators only) does what the console does: backups
  (make, download as a stream, upload one made elsewhere, restore after the password and typing RESTORE, with a safety
  backup first), the monitor address, the content check and analytics. Database updates run by themselves (Updating).
  A lost password is reset with **Forgot your password?** on the sign-in screen: a one-hour, single-use link by email
  (only an HMAC of the token is stored), the same answer whether the address exists or not, 5 requests per hour per
  IP and 3 per address; the new password ends every session of that user.
- **Backups** (`app/Ops`) — one `bmmatic-backup-YYYYMMDD-HHMMSS.tar.gz` per run with `database.sql` (a PHP dump, one
  statement per line, no `mysqldump` needed), the uploads and `manifest.json` (row counts, per-table checksums, an
  app-key fingerprint). Default folder `storage/backups`, retention 30 days (the 3 newest always stay), optional
  off-site copy. `backup:restore` verifies the checksums afterwards. Options in `config.local.php`:

  ```php
  'ops' => [
      'health_token' => '…',                 // written by the installer
      'error_email' => 'dev@example.com',    // at most one error email per hour
      'log_retention_days' => 30,
      'backup' => ['dir' => '', 'retention_days' => 30, 'offsite' => 'sftp://user:pass@host/path'],
  ],
  ```

  `config.local.php` itself is not in the backup: it holds the app key and the database password — keep it in the
  password manager. Restoring on another installation with another app key works, but stored secrets (SMTP password,
  Google tokens, 2FA secrets) must then be entered again; the restore warns about it.
- **Errors** — visitors see a reference code; `storage/logs/app-YYYY-MM-DD.log` holds the detail under that code
  (daily files, a new part after 5 MB, pruned after `ops.log_retention_days`).
- **Monitoring** — `/health?token=…` returns JSON (`ok` / `warn` / `fail`) for database, storage, mail queue, review
  sync and backup age; HTTP 503 on `fail`. Without the token it is the normal 404 page.
- **Performance** — list views are indexed for years of data (`tools/perf-queries.php` fills a scratch database with
  20,000 requests and times every list query). No page cache: every public page carries a fresh CSP nonce, and the
  pages that matter most (home, contact) carry a CSRF token in the form; with 55–95 ms to the first byte (measured on
  the local Apache test host) a cache would add
  risk for little gain.

## Development

Laragon on Windows works well. Any PHP 8.2+ with MySQL does too.

```bash
composer install
```

```bash
php -S 127.0.0.1:8080 -t public bin/dev-router.php
```

Open `http://127.0.0.1:8080/` to run the installer against a local database. Setting `app.env` to `local` in `config/config.local.php` relaxes the `Secure` cookie flag for plain HTTP.

### Tests and static analysis

Integration tests need a MySQL server. They drop and re-create databases named `bmmatic_test*` only. Database credentials are never committed: copy `phpunit.xml.dist` to `phpunit.xml` (git-ignored) and add

```xml
<env name="BM_TEST_DB_USER" value="..." force="false"/>
<env name="BM_TEST_DB_PASS" value="..." force="false"/>
```

or export `BM_TEST_DB_USER` / `BM_TEST_DB_PASS`. Without them the database tests are skipped.

```bash
vendor/bin/phpunit
```

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

Browser tests (Playwright) live in `tests/e2e` and run from `tools/visual-check` against a running instance:

```bash
node ../../tests/e2e/install-wizard.mjs http://127.0.0.1:8080 bmmatic_e2e
```

```bash
node ../../tests/e2e/auth-flow.mjs http://127.0.0.1:8080 <admin-path> <email> <password>
```

```bash
node ../../tests/e2e/public-site.mjs http://127.0.0.1:8080 http://127.0.0.1:8025
```

```bash
node ../../tests/e2e/admin-flow.mjs http://127.0.0.1:8080 <admin-path> <email> <password> http://127.0.0.1:8025
```

`admin-flow.mjs` walks the panel the way a user does: a request arriving from the website, the status flow, notes
with the confirm modal, the CSV export, content in three languages with a slug redirect, reordering sections with the
keyboard, a media upload (and a refused PHP file), inviting a colleague and checking what that editor may not open,
and the dashboard quick toggle.

```bash
node ../../tests/e2e/reviews-flow.mjs http://127.0.0.1:8080 <admin-path> <email> <password> "<php> <path>/bin/console"
```

`reviews-flow.mjs` runs the Google reviews module against `tests/e2e/google-mock.mjs`, a stand-in for Google: it
connects a Business Profile (consent and refresh token), syncs twice, shows one review and then all of them, checks
what the website does with that, switches to Places (five reviews, nothing marked as removed), imports a CSV twice
without duplicating it, and makes a sync fail to see that the website carries on. The console command it is given is
used for `reviews:api-base`, which is cleared again at the end. The mock also runs on its own
(`node tests/e2e/google-mock.mjs`) for trying the providers by hand.

`public-site.mjs` covers the languages, the cookie banner, the appointment form (validation, honeypot, time trap,
rate limit, no double submit) and the two emails. It needs an SMTP server; [Mailpit](https://mailpit.axllent.org/)
on `127.0.0.1:1025` with its API on `:8025` works well. Its last test uses up the hourly form limit of the test
machine's IP address, so run `php bin/console forms:unlock 127.0.0.1` before repeating it.

### Coding conventions

- PHP 8.2, `declare(strict_types=1)` everywhere, PSR-4 under `BMMatic\`, PHPStan level 6 clean, no suppressions.
- Final classes, constructor injection, readonly properties; no static state except the database connection and paths.
- Names and comments say *why*, in plain English; docblocks only where types need it (array shapes).
- Every write: CSRF (automatic), permission (route flag), server-side validation, and an audit log entry when it
  changes what the site shows or who may do what.
- No inline `style=""`, no inline event handlers, no `'unsafe-inline'`; the few inline `<script>`/`<style>` blocks
  carry the CSP nonce. Animate only `transform`, `opacity` and `stroke-dashoffset`; everything readable without JS.
- Every visible string is a translation key in `lang/{en,fr,nl}` with the same keys in all three; editable content
  lives in the database, seeded from `database/seeders`.
- New tables: a numbered migration **and** a line in `Core\Tables`. Seeders only add, never overwrite.
- A change to an approved screen must keep the static check under 1 %; new screens get a self-rendered baseline
  (`admin-static.mjs --write`).
- Commits: one subject line that says what changed for the user, a body that says why.

### The full check run

Before a release, from the repository root and `tools/visual-check`:

| Check | Command |
| --- | --- |
| Unit + integration tests | `vendor/bin/phpunit` |
| Static analysis | `vendor/bin/phpstan analyse --memory-limit=1G` |
| Dependencies | `composer audit` |
| Approved screens (< 1 %) | `node static-check.mjs php`, `node responsive-check.mjs php`, `node admin-static.mjs <url> <admin> <email> <pw>` |
| Motion, interaction | `node motion-check.mjs php`, `node ui-check.mjs php` |
| Accessibility | `BM_SITE_URL=<url> node a11y-check.mjs`, `node a11y-admin.mjs <url> <admin> <email> <pw>` |
| Layout and weight | `BM_SITE_URL=<url> node overflow-check.mjs`, `BM_SITE_URL=<url> node site-weight.mjs`, `node page-weight.mjs` |
| Lighthouse (deployed release) | `node lighthouse-check.mjs <url>` |
| End-to-end | `tests/e2e/install-wizard.mjs`, `auth-flow.mjs`, `public-site.mjs`, `admin-flow.mjs`, `reviews-flow.mjs` |
| Tools without a command line (forgot password, scheduler address, backups and restore in the panel; mail to Mailpit) | `node ../../tests/e2e/web-tools.mjs <url> <admin> <email> <pw>` |
| Security over HTTP (both layouts) | `node ../../tests/e2e/security-check.mjs --standard=… --split=… [--fallback=…] [--tls=…] --admin=… --email=… --password=… [--uploads=…]` |
| No secrets in logs (after the e2e run) | `php tools/secret-scan.php --root=<app folder> --secret=<admin password> --extra=<web server error log>` |
| Backup → empty database → restore | `node ../../tests/e2e/restore-proof.mjs …` (see the script header) |
| Query plans | `php tools/perf-queries.php` |

`tools/deploy-local.ps1 [-Fresh]` deploys the newest release zips to the local Apache test hosts (standard on :8181,
split on :8182, project folder as document root on :8183, HTTPS on :8443). The user-guide screenshots are made by
`tools/guide/demo-data.php` and `tools/guide/screens.mjs` on a scratch installation.

### Components and front-end assets

The interface is built from the PHP components in `app/Views/components`. Their parameters are declared and type-checked in `app/Core/Components.php`, and a component renders with:

```php
<?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit']) ?>
```

- Text parameters are always escaped. Slots accept output from other components (`Html`) or plain text (escaped).
- Component texts come from `lang/{code}/ui.php`.
- There are no inline `style` attributes or event handlers anywhere, so the CSP needs no `'unsafe-inline'`.
- Theme colors, radii and motion tokens come from the Appearance settings (`theme.*`). They are printed in a nonce'd `<style>` in `<head>`.
- The motion setting (`appearance.motion_enabled`, `appearance.motion_intensity`) becomes `<html data-motion="standard|subtle|off">`.
- The admin sidebar menu (sections, icons, badges, role permissions) is defined in `config/admin-menu.php`.

CSS and JavaScript sources live in `resources/`. Built files are committed to `public/assets`, so the server never needs Node. Rebuild them after changing `resources/`, a component's icons or the design fonts:

```bash
cd tools/assets && npm install && node build.mjs
```

The build does the following:
- minifies the CSS into a shared `css/core.css` for every public page plus the per-page groups `home`, `cards`,
  `reviews`, `forms` and `content` (and `cookie`, `toast` and `motion-off`, which load only when the banner, a
  toast or the "motion off" setting needs them), `css/admin.css` (admin, sign-in, installer) and `js/app.js`
- subsets Font Awesome to the icons that the application actually uses (it scans `app/`, `config/`, `database/` and `resources/js` for `fa-solid|fa-regular|fa-brands fa-*`); the public bundle only contains the icons used outside the admin panel
- writes `public/assets/manifest.json`, whose content hashes become the `?v=` cache-busting query on every asset URL

### Design check

`/design-check` shows every component in every state, the toast and confirm modal, and live motion demos with the motion setting switchable on the page. It also renders the seven approved screens from the components at `/design-check/screens/{name}`: `home-desktop`, `mobile-home`, `mobile-menu`, `admin-dashboard`, `admin-reviews`, `admin-languages` and `admin-security`.

It is available only when `app.env` is `local`, and returns 404 everywhere else. It is not included in release zips. Start a local server without installing:

```bash
BM_APP_ENV=local php -S 127.0.0.1:8080 -t public bin/dev-router.php
```

(PowerShell: `$env:BM_APP_ENV = "local"` first.) Then open `http://127.0.0.1:8080/design-check`.

The screens accept the visual-check harness and the motion setting as query parameters:
- `?state=language`, `services`, `rating-filter`, `admin-lang` or `toast`
- `?motion=standard`, `subtle` or `off`

For example: `/design-check/screens/admin-reviews?state=rating-filter&motion=subtle`.

### Visual, motion, interaction and accessibility checks

These run in `tools/visual-check` (`npm install` once). The `php` target starts `BM_APP_ENV=local php -S` itself, or uses `BM_PHP_URL` if it is set.

```bash
node static-check.mjs php
```

```bash
node motion-check.mjs php
```

```bash
node ui-check.mjs php
```

```bash
node a11y-check.mjs
```

```bash
node page-weight.mjs
```

Against a running site (the dev instance, a deployed release), these also check the public pages:

```bash
BM_SITE_URL=http://127.0.0.1:8184 node a11y-check.mjs
```

```bash
BM_SITE_URL=http://127.0.0.1:8184 node overflow-check.mjs
```

```bash
BM_SITE_URL=http://127.0.0.1:8184 node site-weight.mjs
```

```bash
node responsive-check.mjs php
```

```bash
node lighthouse-check.mjs http://127.0.0.1:8181
```

- `overflow-check.mjs`: every public page in three languages at 360, 390, 414, 768, 1024, 1280 and 1440 px, with no
  horizontal overflow (scroll containers and screen-reader-only text are ignored).
- `responsive-check.mjs`: the widths the mock-ups do not draw (1280, 1024, 768 and the admin at 1024) against
  self-rendered baselines in `design/screenshots-local/responsive`; `--write` regenerates them.
- `site-weight.mjs`: the CSS, JS, font and image bytes each public page loads (budget: under 30 KB of CSS per page).
- `lighthouse-check.mjs`: Lighthouse mobile on the home page, a service page and contact; every category must reach 90.
- `css-coverage.mjs`: which CSS rules never match on any public page, to keep the bundles small.
- `admin-static.mjs`: the admin screens Phase 4 adds, at 1440 and 1024 px, against baselines in
  `design/screenshots-local/admin` (`--write` regenerates them). Screens with live timestamps — appointments,
  messages, media, users, pages, the log — are deliberately not in it; the end-to-end test and axe cover those.
- `a11y-admin.mjs`: axe on every admin screen, in all three interface languages, at 1440 and 1024 px.

The targets `rebuild` (Phase 0 HTML) and `design` (approved mock-ups) are still available for comparison. Reports are written to `tools/visual-check/out/`. `design/DESIGN-SPEC.md` describes the baselines and the motion rules.
