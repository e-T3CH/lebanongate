# GATE Lebanon website

Public website and content management system for **GATE Lebanon** (غايت لبنان, *For Humanitarian Aid, Development &
Peace*), an independent Lebanese NGO working mainly in Akkar since 2014. It is a PHP 8.2+ / MySQL application built
for ordinary shared hosting (Apache or LiteSpeed with `.htaccess`, or nginx). The server needs no Node and no build
step.

The website is trilingual (English, Arabic right-to-left, French) and covers the sections of the RFQ: Home, About us
(Who we are, Mission and vision, Organisational profile), Areas of expertise, Projects, News, Publications, Gallery,
Partners and donors, and Contact, with a newsletter sign-up, SEO, SSL-only cookies, backups and an admin panel for the
whole content.

## Documentation

| Document | For | What is in it |
| --- | --- | --- |
| [README.md](README.md) (this file) | Developer | Architecture, folders, releases, console, conventions |
| [USER-GUIDE.md](USER-GUIDE.md) | The GATE team | The admin panel in plain language (also the training handout) |
| [deploy/GO-LIVE.md](deploy/GO-LIVE.md) | Owner + developer | From zero to a live site: hosting, database, upload, installer, cron, domain, content |
| [deploy/DNS-AND-EMAIL.md](deploy/DNS-AND-EMAIL.md) | Owner + developer | DNS records, SPF / DKIM / DMARC for the sending domain |
| [MAINTENANCE.md](MAINTENANCE.md) | Developer | Cron, monitoring, the monthly routine, what to do when something breaks |
| [SECURITY.md](SECURITY.md) | Developer | Security measures, hosting checklist, authorisation matrix |
| [docs/GATE-UI-STRUCTURE.md](docs/GATE-UI-STRUCTURE.md) | Developer | Site structure, sections, motion and palette of the approved design |
| [mockups/home.html](mockups/home.html) | Everyone | The approved home page mock-up (repository only) |

## Architecture

A plain PHP application without a framework: one front controller, a small kernel, and code organised by
responsibility.

```
request → public/index.php → Core\App::handle()
            ├─ /health (token)          → Ops\Health                       (before the database is touched)
            ├─ not installed            → Install\InstallController       (/install wizard)
            └─ installed
                 ├─ session, CSRF, Force HTTPS
                 ├─ /<admin path>/…     → Http\AdminRoutes → one permission check (config/permissions.php)
                 │                        → Http\Controllers\Admin\* → Repositories / Services → Views/admin
                 └─ everything else     → Http\Controllers\Site\SiteController (i18n routing, pages, forms, SEO)
          ← Security\SecurityHeaders (CSP nonce, HSTS…) ← App::finish() sends, then runs deferred work (mail)
```

- **Views** are PHP templates (`app/Views`). The admin is built from components with checked props
  (`Core\Components`); the public site from the parts in `app/Views/site/parts` and `sections`. Output is escaped by
  default, and the Content Security Policy allows no inline styles or scripts without a nonce.
- **Data** goes through `Core\Database` (PDO, prepared statements, a table whitelist in `Core\Tables`) and
  repositories. Schema changes are numbered migrations in `database/migrations`; defaults and translations come from
  idempotent seeders.
- **Settings** live in the `settings` table (`Services\Settings`, secrets encrypted). Only the database credentials,
  the app key and operations options live in `config/config.local.php`.
- **Background work** without a queue server: email is queued in MySQL and sent after the response or by cron;
  backups run from cron or the scheduler address.

## Folder map

| Folder | What is in it |
| --- | --- |
| `app/Core` | Kernel (`App`), config, paths, database, migrator, views, components, theme tokens |
| `app/Http` | Request/response, router, admin routes, controllers (`Admin/*`, `Site/*`) |
| `app/Content` | `EntryTypes`: the four entry types, statuses, publication kinds, regions |
| `app/Repositories` | One class per group of tables (content, entries, messages, subscribers, users…) |
| `app/Services` | Settings, auth, audit log, media library (images and PDF) |
| `app/Site` | Public-site presentation: URLs and hreflang, SEO and JSON-LD, dates, brand, the contact form |
| `app/Security` | Sessions, CSRF, headers, crypto, passwords, 2FA, rate limits, spam guard, IP handling |
| `app/Mail` | Message, queue, worker, SMTP transport, mail templates |
| `app/Ops` | Backups, database dump, scheduler, `/health`, `content:check` |
| `app/Console`, `app/Install` | `bin/console` commands and release builder; the installation wizard |
| `config` | `app.php`, `permissions.php`, `admin-menu.php`, `expertise-icons.php`; `config.local.php` is written by the installer |
| `database` | Migrations and seeders (starting content in `seeders/content/{en,ar,fr}.php`) |
| `lang` | Interface strings per language (`en`, `fr`: admin and site; `ar`: site) |
| `public` | The web root: `index.php`, `paths.php`, assets, uploads |
| `storage` | Sessions, logs, cache, backups; never web-reachable |
| `tools` | `assets/manifest.py`, `icons/build.py`, `map/build.py` (see below); not in releases |
| `deploy`, `docs`, `mockups` | Server examples, handover documents, the approved mock-up |

## Content model

| Table | Holds |
| --- | --- |
| `pages`, `page_translations` | The fixed pages, with `parent_id` for the About sub-pages, a header image, slug, menu label, texts and SEO fields per language |
| `page_sections`, `page_section_translations` | The home page sections (hero, about, expertise, stats, projects, map, news, partners, cta), their order, photos and structured extras (About points and badge, map notes) |
| `expertise`, `expertise_translations` | Areas of expertise with an icon from the site's SVG sprite and a cover image |
| `entries`, `entry_translations`, `entry_media` | Projects, news, publications and albums in one table (`type`), with status, region, dates, beneficiaries, donors, cover, PDF file, related project and the album photos |
| `stats`, `partners` (+ translations) | Impact figures; partners and donors with logo and link |
| `media`, `media_translations` | Images (re-encoded) and PDF documents, with alt text or title per language |
| `messages`, `message_notes` | The contact form inbox and internal notes |
| `subscribers` | Newsletter sign-ups (double opt-in; only an HMAC of the tokens is stored) |
| `ui_translations` | Interface strings per language, copied from `lang/` on every update; rows the team edits under Content → Website texts (`site.*` only) are marked `is_custom` and kept |
| `mail_queue`, `redirects` | Outgoing email; old URL → new URL with a hit counter |

A translation that is missing or not published falls back to the default language, including its slug, so a page is
never empty. Changing a slug adds a redirect automatically.

## The public website

- **URLs**: every page lives under a language prefix with a translated slug (`/en/projects/…`, `/ar/…`, `/fr/…`).
  `/` redirects to the visitor's language (cookie, then `Accept-Language`, then the default). Arabic pages are served
  with `dir="rtl"` and IBM Plex Sans Arabic; Latin pages use IBM Plex Sans. Both fonts are self-hosted.
- **SEO**: canonical URLs, `hreflang` including `x-default`, Open Graph, schema.org JSON-LD (`NGO` organisation,
  `BreadcrumbList`, `NewsArticle` / `Report` where they apply), one sitemap per language behind `sitemap.xml`.
- **Contact form and newsletter**: validated on the server, protected by the CSRF token, a honeypot, a signed time
  trap (`forms.min_seconds`) and a per-IP hourly limit (`forms.max_per_hour`). The newsletter uses double opt-in.
- **Map**: the governorate map on the home page is generated from geoBoundaries (CC BY 4.0) by `tools/map/build.py`
  into `app/Views/site/parts/map-paths.php`.
- **Cookies and analytics**: no banner unless an analytics provider is set; analytics (GA4 or Plausible) only loads
  after consent.
- **Footer credit**: "Developed by" with the E-5HOP logo linking to https://www.e-5hop.com (`Site\Brand`).

## The admin panel

Day-to-day use is described in [USER-GUIDE.md](USER-GUIDE.md). `config/permissions.php` is the single source of
truth for the roles (`admin` may do everything; `editor` works with messages, subscribers, content and media). Every
admin route carries a `perm:<name>` flag checked by `App` before the controller runs; the sidebar
(`config/admin-menu.php`) and the views ask the same question, so a screen never offers what the request would refuse.
`php bin/console routes:list` prints the matrix.

Rich text is sanitised with HTML Purifier when it is saved and again when it is rendered.

## Requirements

- PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `sodium`, `json`, `ctype`, `fileinfo`, and `gd` with
  WebP (image uploads, responsive images); `curl` for off-site backups
- MySQL 8 or MariaDB 10.6+ (utf8mb4)
- Apache 2.4 or LiteSpeed with `.htaccess` (mod_rewrite), or nginx using `deploy/nginx.conf.example`
- HTTPS in production (session cookies are `Secure` and `__Host-` prefixed)
- `upload_max_filesize` and `post_max_size` of at least 25M for PDF reports

The `/install` wizard checks these on the server before anything is written.

## Deploy layouts and releases

The web root must contain only the contents of `public/`. `public/paths.php` (written by `build:release`) selects the
layout:

| Layout | Folder structure on the server | Use when |
| --- | --- | --- |
| `standard` | `gate-lebanon/` (app) with `gate-lebanon/public/` as the document root | You can choose the document root |
| `split` | `public_html/` holds the contents of `public/`, and `gate-app/` holds everything else next to it | The document root is fixed (cPanel) |
| `webroot` | Everything in the web root; private folders get a deny-all `.htaccess` | Fallback when there is no private folder |

```bash
php bin/console build:release --layout=standard
php bin/console build:release --layout=split --app-dir=gate-app
```

This creates `build/gate-lebanon-<layout>-<timestamp>.zip` with `vendor/` (no dev packages), the assets, `app`,
`config/app.php`, `database`, `lang`, `bin/console`, `deploy` and an empty `storage/`. It refuses to pack tools,
mock-ups, `.git`, `config/config.local.php`, the RFQ document or other zips. Before a release, after changing any file
under `public/assets`, run `python3 tools/assets/manifest.py` (cache-busting hashes).

### Installing

1. Create a database and a user limited to it (`SELECT, INSERT, UPDATE, DELETE`, plus `CREATE, ALTER, INDEX, DROP,
   REFERENCES` for installation, updates and restores).
2. Upload and extract the zip for your layout; make `storage/` and `config/` writable by PHP.
3. Open `https://your-domain/`: the `/install` wizard asks for the database, the website, the admin account and the
   languages (English, Arabic, French). The last step shows the secret admin address once; store it in a password
   manager.
4. Sign in and enable two-factor authentication under Security.

After installation `/install` returns 404 for good.

### Updating

Upload a new release except `config/config.local.php`, `storage/` and the uploads folder. The first request applies
new migrations and the idempotent seeders (`Ops\SchemaUpdater`); content changed in the admin is never overwritten.
With SSH: `php bin/console migrate` and `php bin/console seed`.

## Console

These commands only run from the PHP CLI and are written to the audit log with `via=cli`.

| Command | What it does |
| --- | --- |
| `admin:path [--regenerate]` | Print the admin sign-in URL, or replace it with a new random one |
| `security:2fa-reset <email>` | Disable 2FA and delete the recovery codes of that user |
| `user:password-reset <email>` | Set a new password (hidden prompt) and end that user's sessions |
| `security:unlock <email> [ip]` | Clear a login lockout |
| `forms:unlock <ip>` | Clear the contact and newsletter form rate limits for one visitor |
| `mail:work [--limit=20]`, `mail:test <email>` | Send queued emails now; send a test email |
| `redirects:import <file.csv>` | Import old URL redirects (`from_path,to_path[,status]`) |
| `schedule:run`, `schedule:url [--regenerate]` | All scheduled work (emails, daily backup); the address for a web cron service |
| `backup:run`, `backup:list`, `backup:restore <file>` | Back up, list and restore the database and the uploads |
| `content:check` | Unfinished content per language: [placeholders], missing translations, alt texts, SEO fields |
| `health:url` | The uptime-monitor URL |
| `analytics:set none \| ga4 <G-ID> \| plausible <domain>` | Choose the analytics that load after consent |
| `routes:list` | The admin routes with their permission per role |

`php bin/console help` lists every command, including `migrate`, `seed` and a non-interactive `install`.

## Operations

The routine is in [MAINTENANCE.md](MAINTENANCE.md).

- **Scheduled work** (`Ops\Scheduler`): emails and the daily backup, from `schedule:run` (cron) or the scheduler
  address `/cron/<token>` for hosts without cron. A lock prevents overlapping runs; the last result is shown in
  Settings → Maintenance, which also offers backups, restore, the monitor address and the content check without a
  command line.
- **Backups**: one `gate-backup-YYYYMMDD-HHMMSS.tar.gz` per run with `database.sql`, the uploads and a
  `manifest.json` with checksums. Default folder `storage/backups`, retention 30 days, optional off-site copy
  (`ops.backup` in `config.local.php`). `config.local.php` itself is not in the backup: keep it in the password
  manager.
- **Errors**: visitors see a reference code; `storage/logs/app-YYYY-MM-DD.log` holds the detail under that code.
- **Monitoring**: `/health?token=…` returns `ok` / `warn` / `fail` for the database, storage, mail queue and backup
  age.

## Development

```bash
composer install
php -S 127.0.0.1:8080 -t public bin/dev-router.php
```

Open `http://127.0.0.1:8080/` to run the installer against a local database. Setting `app.env` to `local` in
`config/config.local.php` relaxes the `Secure` cookie flag for plain HTTP.

Static analysis: `vendor/bin/phpstan analyse` (level 8, `phpstan.neon.dist`).

Asset tools (Python 3, run from the repository root):

| Script | When |
| --- | --- |
| `python3 tools/assets/manifest.py` | After changing any CSS, JS, font or image under `public/assets` |
| `python3 tools/icons/build.py <fontawesome-free package>` | After using a new Font Awesome icon in the admin (needs `fonttools`, `brotli`, `pyyaml`) |
| `python3 tools/map/build.py` | To regenerate the governorate map |

### Conventions

- Strict types everywhere; PHPStan level 8 clean for `app/`.
- No inline `style` attributes (CSP): JavaScript sets styles through the CSSOM.
- A named SQL placeholder may be used once per statement; use `{table}` placeholders so the whitelist applies.
- Interface strings go in `lang/{en,fr}/admin.php` or `lang/{en,ar,fr}/site.php`, never in templates.
- Keep the three languages in step: a new site string needs its Arabic and French text in the same change.
