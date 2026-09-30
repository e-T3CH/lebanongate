# Maintenance

What keeps the GATE Lebanon website healthy after launch. The automatic parts run from cron jobs (below); the monthly
routine takes about half an hour and is best done by the developer, with the GATE team checking the panel.

Commands are run from the application folder (`gate-lebanon/` or `gate-app/`) over SSH. **Without SSH** (one.com):
every task below also has a place in the admin panel, under **Settings → Maintenance** — the scheduler address,
backups (make, download, upload, restore), the monitor address, the content check and analytics. Database updates
after an upload run by themselves (see Updates).

## Automatic (scheduled tasks)

One job, every 15 minutes, does everything that is due: sends waiting emails and makes the daily backup (database + uploads into
`storage/backups`, 30 days kept with at least the 3 newest, copied off-site if configured) once the last one is 23
hours old. Two ways to start it:

| Host | Set up |
| --- | --- |
| No cron, no SSH (one.com) | A free web cron service (cron-job.org) opens the **scheduler address** from Settings → Maintenance every 15 minutes. The site answers at once (HTTP 202) and does the work afterwards. |
| Cron available (Hostinger, cPanel) | `php bin/console schedule:run` every 15 minutes (or the separate `backup:run` and `mail:work`). |

The scheduler address is `/cron/<token>`; the token is a secret setting (encrypted in the database). A wrong token
is an ordinary 404. If it leaks: Settings → Maintenance → **New address** (or `schedule:url --regenerate`), then
update the cron service. A run that is already busy is not started twice. The last run and its result show on the
Maintenance screen; the monitor warns when the scheduler has been silent for more than a day.

deploy/GO-LIVE.md step 8 walks through cron-job.org; its appendix shows Hostinger's exact format. Logs rotate by
themselves (daily files, removed after `ops.log_retention_days`, 30 by default); the security log follows its own
retention setting in the panel.

## Updates

Build a release on the development machine (README → Deploy layouts and releases) and upload it over the existing files —
with the File Manager on one.com. Never overwrite `config/config.local.php`, `storage/` or `public/uploads/`. **No
command is needed afterwards:** the first request after the upload notices the new files, runs the new database
migrations, adds missing settings and brings the interface wording up to date (never touching settings or texts
changed in the panel). While that
runs — normally a second or two — other visitors get a short "back in a moment" (503). The security log records it
as `system.updated`. Make a backup (Settings → Maintenance) before every update.

## Monitoring

Settings → Maintenance shows the monitor address (or `php bin/console health:url`): `https://<site>/health?token=…`. Give it to an uptime
monitor (UptimeRobot, Better Stack, Uptime Kuma…) as a **keyword** check on `"status":"ok"`, every 5 minutes, with
alerts to the developer. The answer:

```json
{"status":"ok","time":"2026-09-18T03:20:00Z","checks":{"database":{"status":"ok","ms":1},"storage":{"status":"ok"},
 "mail_queue":{"status":"ok","pending":0,"oldest_pending_minutes":0,"failed_last_24h":0},
 "backup":{"status":"ok","last_backup":"2026-09-18 03:15:02","age_hours":0},
 "scheduler":{"status":"ok","last_run":"2026-09-18 03:15:00","age_hours":0}}}
```

`"warn"` (HTTP 200) means the site works but something needs a look: a backup older than 26 hours, emails stuck for more than an hour or failed in the last day, a scheduler that has not
run for a day (only once it has run at least once). `"fail"` (HTTP 503) means
the database or the storage folders are broken. Without the right token the address is an ordinary 404. The token
is `ops.health_token` in `config/config.local.php`; change it there if it ever leaks.

Optional error mail: set `'ops' => ['error_email' => 'dev@example.com']` in `config.local.php`. At most one email per
hour, with the reference code; every error stays in `storage/logs` under its code.

## Monthly routine

1. **Updates.**
   - PHP: check the version in the hosting panel; stay on a supported branch (8.3 or 8.4 in 2026).
   - Dependencies, on the development machine:
     ```bash
     composer audit
     ```
     ```bash
     composer outdated --direct
     ```
     Security advisory → update, run the full checks (README → Checks), build a release and deploy it (README →
     Updating). Minor versions: same, when convenient. Major versions (PHPMailer 7, Google2FA 9 are available in
     September 2026): plan them; they are not urgent while `composer audit` is clean.
2. **Backups — prove they restore.** Settings → Maintenance (or `php bin/console backup:list`) shows a backup from
   last night; **download** one and keep it off the server (or check the off-site copy). Every three months, restore the newest backup into a scratch installation (never the live site):
   install the same release in a test folder with an empty database, copy `config.local.php` with the live app key
   but the test database, then `php bin/console backup:restore <file>` — it reports "Every table matches the
   backup". Without SSH: upload the backup in the scratch installation's Settings → Maintenance and restore it
   there; the sign-in screen then says whether every table matched. Keep `config/config.local.php` (app key, database password) in the password manager, not only on the
   server.
3. **Logs.** Skim `storage/logs/app-*.log` for new reference codes and Security → Log in the panel for failed
   sign-ins, lockouts, permission refusals or blocked IPs that do not belong to the team.
4. **Messages and newsletter.** No old unread messages in the inbox; newsletter confirmations still arrive (sign up
   with a test address).
5. **Email.** Settings → Email → send a test; check that a test contact message reaches the team inbox and not the
   spam folder.
6. **Content.** Settings → Maintenance → Before going live (or `php bin/console content:check`) should still be
   empty. Check the office hours, the
   phone number, and that the newest projects, news and reports are published in all three languages.
7. **Users.** Deactivate accounts of people who left; every administrator has two-factor on.

## Once a year

- Renew the domain (and check the TLS certificate renews automatically).
- Re-read the privacy policy, cookie policy and terms against how the organisation works now and your donors' requirements; update the date.
- Change the database password (hosting panel, then `config.local.php`) and rotate the health token.
- Review who has access to the hosting account, the domain registrar and the mail account.

## If something breaks

| Symptom | First step |
| --- | --- |
| Visitors see "Something went wrong" with a code | Find the code in `storage/logs/app-<date>.log` |
| The whole site is down, `/health` says `fail` for the database | Hosting panel: is MySQL up, did the database password change? |
| "Temporarily unavailable" (503) everywhere | Same as above: the database cannot be reached |
| Nobody can sign in | Lost password: **Forgot your password?** on the sign-in screen (needs working email), or `php bin/console user:password-reset <email>`; lockout: wait 15 minutes or `security:unlock <email> <ip>`; lost 2FA phone: a recovery code, or `security:2fa-reset <email>` |
| Admin address forgotten | `php bin/console admin:path` |
| Emails stop arriving | Settings → Email → test; `php bin/console mail:test <address>`; SPF/DKIM (deploy/DNS-AND-EMAIL.md) |
| Content or uploads lost or damaged | Settings → Maintenance → **Restore a backup** (or `backup:restore <file>`); a safety backup of the current state is made first |
| The hosting was wiped or the site reinstalled | Install the release, sign in, Settings → Maintenance → **Upload** a downloaded backup → **Restore**. A backup from another installation (other app key) is handled: the new admin address, scheduler token and whoever restores keep working; secrets it cannot decrypt take this installation's value or are emptied (re-enter the email password); two-factor authentication is switched off for the restored accounts. Keeping `config/config.local.php` avoids all of that. |
| Every page shows an error with a code right after an upload | The automatic database update failed (for example a file was not uploaded completely): find the code in `storage/logs`, upload the missing files again; every request retries the update |
