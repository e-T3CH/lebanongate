# Going live — from zero to a published website

A step-by-step guide for someone who is not a developer. Take it in order; each step says what you should see
before you move on. Tick the boxes as you go. Plan an afternoon, plus up to a day of waiting for the
domain (step 9).

What you need before you start: the release zip from your developer (`gate-lebanon-webroot-<date>.zip`),
access to the domain name (the registrar where `gatelebanon.org` was bought), a one.com hosting account, and a password
manager. No SSH, FTP program or command line is needed: everything below works with one.com's **File Manager** and
the website's own admin panel.

> This guide follows **one.com** on a plan without SSH and without scheduled tasks. The File Manager only shows the
> website folder, so the whole application goes into that folder; `.htaccess` files keep its private parts (code,
> settings, backups) out of reach of browsers. The names of one.com's screens change now and then; the "you should
> see" lines tell you whether you got the right result. Hosting somewhere else (Hostinger, a cPanel host)? See the
> appendix at the end.

---

## 1. The hosting account

- [ ] A one.com web hosting plan for `gatelebanon.org` with **PHP 8.2 or newer** and a **MySQL database**. SSL
      (https) is included.
- [ ] **Scheduled tasks (cron):** the daily backup and the email retries need something that runs
      a task on a schedule. We could not confirm that one.com's plans offer cron jobs. Ask one.com support
      ("Can my plan run a PHP command on a schedule?") and tell your developer the answer — see step 8.

## 2. Set PHP

- [ ] In one.com's control panel, open the PHP and database settings and choose **PHP 8.3** (8.2 or newer works).
- [ ] The installer lists anything that is missing in step 6 (for example `sodium` or `gd`); if it shows a red line,
      one.com support can switch that extension on.

## 3. Create the database

- [ ] In the same PHP and database settings, create a **MySQL database**. Save in your password manager: the
      database name, the user name, the password and the **host name** one.com shows.

The database user one.com gives you can only reach that one database, which is what we want. The installer checks
that it may create tables with foreign keys (`CREATE, ALTER, INDEX, DROP, REFERENCES`) before it writes anything.

**You should see:** the database in the list, with its user and host.

## 4. Upload the release

Open the one.com **File Manager**. You arrive in the website folder of `gatelebanon.org` (the one that shows
`.htaccess` and `index.php`).

- [ ] **First keep what is there.** If the folder already contains files (for example `maintenance-data/`,
      `.htaccess`, `index.php` from one.com's "coming soon" page), select them, download them to your computer and
      then delete them from the website folder. Keep the downloaded copy until the new site is live.
- [ ] Upload the zip `gate-lebanon-webroot-<date>.zip` into the (now empty) website folder.
- [ ] Right-click it → **Extract** (or **Unzip**) into the current folder. Then delete the zip itself.

The zip holds the application without an extra folder level:

```
(website folder)
├── .htaccess          sends every visitor to public/, refuses the private folders
├── public/            what visitors get (index.php, assets, uploads)
├── app/ bin/ config/ database/ lang/ storage/ vendor/ …   private — each has its own "deny all" .htaccess
└── README.md, MAINTENANCE.md, USER-GUIDE*.md             refused to browsers as well
```

**You should see:** in the website folder `.htaccess`, `public/`, `app/`, `config/`, `storage/`, `vendor/` … — not a
single folder called `gate-lebanon` with everything inside it. If you do see one, move its contents one level up.

Check the protection once the domain answers (after step 6): `https://gatelebanon.org/config/config.php`,
`https://gatelebanon.org/storage/` and `https://gatelebanon.org/README.md` must all show "Page not found" or "Forbidden".
The installer also checks this for you and refuses to continue when private files can be read.

(one.com also offers a separate `httpd.private` folder next to the website folder. It is not reachable from the
File Manager on every plan; when it is — for example over SFTP — your developer has a `split` zip that puts the
private parts there instead. Both work.)

## 5. HTTPS

- [ ] one.com switches on the free SSL certificate for the domain by itself once the domain points to your hosting.
      If `https://gatelebanon.org` does not work yet, finish step 9 first and come back.

## 6. Run the installer

- [ ] Open `https://gatelebanon.org/` in your browser. You are sent to the installer.
- [ ] **Requirements** — every line is green. Pay attention to **"Private files are not downloadable (.htaccess
      works)"**: if that line is red, the `.htaccess` files are missing or ignored — upload them again (step 4) or ask
      one.com. If it says it **could not check**, open `https://gatelebanon.org/composer.json` yourself: you must get
      "Forbidden" (403) or "Page not found", never a page of text.
- [ ] **Database** — enter the host, name, user and password from step 3.
- [ ] **Website** — name `GATE Lebanon`, address `https://gatelebanon.org` (with https, without a slash at the end).
- [ ] **Admin account** — your name, email and a long password (at least 12 characters; a sentence works well).
- [ ] **Languages** — Dutch, French and English, default Dutch.
- [ ] The last screen shows your **secret admin address** (like `https://gatelebanon.org/admin-7f3kq2`) once.
      Save it in the password manager right away.

**You should see:** the admin panel after signing in. `https://gatelebanon.org/install` now gives "Page not found".

If the installer says **"Installation in progress"**, another browser (or you, in another window) started it less
than an hour ago. Wait, or delete the file `storage/install.claim` with the File Manager.

## 7. Secure the panel

- [ ] **Security** → set up **two-factor authentication** with an authenticator app, and store the recovery codes in
      the password manager.
- [ ] **Security** → switch on **Force HTTPS** (it can only be switched on while you are on https — that is on
      purpose).
- [ ] **Settings → Email** → fill in the SMTP details of the mailbox that sends the site's emails. For a one.com
      mailbox these are shown in one.com's email settings (usually server `send.one.com`, port 465 with SSL or 587
      with TLS, the full email address as user name). Then **Send test email**.
- [ ] Sign out and try **Forgot your password?** on the sign-in screen once: the email with the link arrives
      within a minute. This is your way back in if a password is ever lost — there is no command line on this plan.

## 8. Scheduled tasks (backup, emails)

Two things have to happen on a schedule: a **daily backup** and retrying emails that could not be sent at once. Your one.com plan has no scheduled tasks of its own, so a free outside
service opens a secret address of your website every 15 minutes, and the website then does whatever is due.

- [ ] In the admin panel go to **Settings → Maintenance**. Copy the **Scheduler address** (it looks like
      `https://gatelebanon.org/cron/Xy7…`). Keep it private, like a password.
- [ ] Create a free account at <https://cron-job.org> → **Create cronjob**:
      - Title: `GATE Lebanon`
      - URL: paste the scheduler address
      - Execution schedule: **Every 15 minutes**
      - Under **Notifications**, tick "the execution of the cronjob fails" so you hear about problems.
      - Save.
- [ ] Back in **Settings → Maintenance**, press **Run now** once.

**You should see:** after at most 15 minutes, the Scheduled tasks card shows **Last run** with the current time,
and the Backups list has a backup of today. cron-job.org shows the calls as successful (status 202).

Good to know:

- The address only starts the tasks; it cannot read or change anything else. If it ever leaks, press **New address**
  and paste the new one at cron-job.org — the old one stops working at once.
- Calling it more often does no harm: work that is not due is skipped. The backup runs once every 24 hours.
- The newest backups are kept on the server (at least three, older ones are removed after the retention period).
  **Download one now and then** (the **Download** link in the Backups list) and keep it somewhere else — a copy on
  the same server does not help if the account itself is lost.

## 9. Point the domain to the hosting

If the domain is registered with one.com, nothing to do. If it is registered elsewhere:

- [ ] At that registrar, either change the **nameservers** to the ones one.com gives you, or keep your nameservers and
      set the records from deploy/DNS-AND-EMAIL.md (A/AAAA for `@` and `www`).
- [ ] Do **not** remove existing MX records if GATE's email already works elsewhere.

**You should see:** within a few hours (sometimes up to a day) `https://gatelebanon.org` shows the site with a padlock,
and `http://gatelebanon.org` ends up there too (Force HTTPS). For `www.gatelebanon.org`, add a redirect to
`https://gatelebanon.org` in one.com's domain settings (or ask support); every page already tells search engines that
the address without `www` is the real one (canonical links).

## 10. Fill in the content — and check nothing is left

Everything in [square brackets] on the website is a placeholder: the phone number, address, registration number,
office hours, and the notes in the legal pages. The starting texts about GATE's projects and news come from public
information; have the GATE team check every one of them.

- [ ] **Settings → General**: organisation name, legal name, registration number, address, phone, email, office hours.
- [ ] **Pages**, **Areas of expertise**, **Projects**, **News**, **Partners & donors** and **Impact figures**, in each
      of the three languages (English, Arabic, French: the tabs at the top of each screen).
- [ ] **Media**: upload real photos and the partner logos; replace the placeholder images.
- [ ] **Privacy policy, cookie policy, terms**: these are templates. Complete every [bracket] and have them checked
      against Lebanese law and your donors' data-protection requirements **before** launch; then delete the
      "TEMPLATE" line at the top of each.
- [ ] **Media**: every image in use has an alt text in each language.
- [ ] **Settings → Maintenance → Before going live** lists every placeholder, missing translation and missing alt
      text that is left, per language. Work through it until it is empty.

**You should see:** the card says **Nothing left to do**.
## 11. Backups and monitoring

- [ ] **Settings → Maintenance → Backups**: press **Make a backup**, then **Download** it. Store it outside the
      server (your computer, a cloud drive). Repeat this after larger changes and at least once a month
      (MAINTENANCE.md).
- [ ] Store `config/config.local.php` in the password manager too (download it with the File Manager): without its
      app key, a backup restored on a fresh installation cannot decrypt the stored passwords.
- [ ] **Uptime monitor**: copy the **Monitor address** from Settings → Maintenance into a free monitor such as
      UptimeRobot, as a *keyword* check on `"status":"ok"` every 5 minutes. It warns you when the site is down, the
      database is unreachable, the daily backup is missing or the scheduled tasks stopped.
- [ ] Your developer can also send each backup to another server automatically (an `offsite` entry in
      `config/config.local.php`; see MAINTENANCE.md → Backups).

**Restoring**, should it ever be needed: Settings → Maintenance → **Restore a backup** (choose it, your password,
type RESTORE). A backup of the current state is made first, so a restore can itself be undone. A backup downloaded
earlier can be put back with **Upload** in the Backups card first — also on a completely new installation (for
example after the hosting was reset): install, sign in, upload, restore. You keep the new admin address and your
current sign-in; re-check Settings → Email and switch two-factor authentication on again.
## 12. Search engines and analytics

- [ ] **Search Console** — go to <https://search.google.com/search-console>, add a **Domain** property for
      `gatelebanon.org`, and verify it with the TXT record Google shows (add it at the DNS provider from step 9). Then
      **Sitemaps** → submit `https://gatelebanon.org/sitemap.xml`.
- [ ] **Analytics (optional)** — the site loads analytics only for visitors who accept analytics cookies. For Google
      Analytics 4, create a property and a web stream at <https://analytics.google.com> and copy the Measurement ID
      (`G-…`). Enter it in **Settings → Maintenance → Analytics** (or choose Plausible and enter `gatelebanon.org`). Then
      name the provider, its country and its cookies in the cookie policy and the privacy policy.
## 13. Last checks on the live site

- [ ] Open the site on a phone and a computer, in the three languages.
- [ ] Send a test message with the contact form: it arrives in **Messages** and in the GATE team mailbox (not in
      spam). Sign up for the newsletter with a test address: the confirmation email arrives and the link works.
- [ ] If analytics is on: the cookie banner appears once; "Cookie settings" at the bottom reopens it.
- [ ] `http://gatelebanon.org` goes to `https://gatelebanon.org`.
- [ ] [securityheaders.com](https://securityheaders.com) and [SSL Labs](https://www.ssllabs.com/ssltest/) give a
      good grade (A or better).
- [ ] Tell the GATE team the admin address and hand over USER-GUIDE.md (it doubles as the
      training handout).

Done. From here on, MAINTENANCE.md describes the monthly routine.

---

## Appendix: other hosts

**Hostinger (hPanel) and cPanel hosts** usually let you put files next to the web folder. Use the `split` zip
there: its `public_html/` contents go into the existing `public_html`, and `gate-app/` goes **next to** it (never
inside). Only the web files are then reachable at all, which is the safest layout.

Hostinger's cron jobs (hPanel → **Advanced → Cron Jobs**) take the full path, for example:

| Schedule | Command |
| --- | --- |
| Every 15 minutes | `/usr/bin/php /home/u123456789/domains/gatelebanon.org/gate-app/bin/console schedule:run` |

`schedule:run` does the same as the scheduler address: emails and the daily backup.
(The separate commands `backup:run` and `mail:work` still work for hosts that prefer them.)

If hPanel's **PHP** type only accepts a file path, choose the **Custom** type and paste the whole line. Hostinger's
SMTP server is usually `smtp.hostinger.com` (port 465 SSL or 587 TLS).

**Installing in a folder** (for example `https://yourdomain.org/gate/` instead of the domain root): it works the same
way, there is nothing to configure.

- Simplest: unzip the full project zip into the folder (`public_html/gate/`) and open `https://yourdomain.org/gate/`.
- cPanel / hPanel zip: copy the contents of its `public_html/` into `public_html/gate/`, put `gate-app/` next to
  `public_html` (outside the web root), then edit `public_html/gate/paths.php` and set `'app_dir' => '../gate-app'`.

The installer suggests the address with the folder; keep it. The scheduler and monitor addresses shown in
Settings → Maintenance already include the folder.

**A host where you can choose the document root** (VPS, nginx): use the `standard` zip and point the document root
at `gate-lebanon/public` (nginx: `deploy/nginx.conf.example`).
