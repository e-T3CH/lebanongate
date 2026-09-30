# Google reviews on the website

This guide is for the person who runs the workshop, not for a developer. It explains how the reviews customers
leave on Google end up on the website, and what to do when something goes wrong. Take your time: nothing here is
urgent, and nothing you do in the panel is visible to visitors until you say so.

The short version: the website never invents a review. It asks Google for the reviews of your location, stores
them, and shows only the ones you approve. The star rating and the number of reviews next to it always come from
Google itself, exactly as Google reports them.

---

## 1. The three ways to get your reviews

Open **Content → Google reviews** in the panel. The Connection card at the top has a **Source** field with three
choices. You pick one; you can always switch later.

| Source | What you get | What you need |
| --- | --- | --- |
| **Google Business Profile API** | Your full review history, with your own replies, kept up to date automatically | A Google Cloud project, a one-time approval by Google, and a connection made once from the panel |
| **Google Places API (New)** | The rating and total review count in full, but **at most five reviews** | An API key and the Place ID of the workshop |
| **Manual import (JSON/CSV)** | Whatever is in the file you upload | A file; nothing is fetched automatically |

**Which one should you use?** Business Profile is the one you want in the end, because it gives every review.
Google has to approve access for your project first, and that takes days to weeks. Places works immediately and
is a good stop-gap, as long as you accept that it only ever hands over five reviews. Manual import is always
available and is also the fallback if a connection ever breaks.

---

## 2. Google Business Profile API (the complete one)

### 2.1 Create a project in Google Cloud

1. Go to <https://console.cloud.google.com/> and sign in with the Google account that owns the Business Profile
   of the workshop. This must be the same account you use to reply to reviews.
2. At the top left, next to the Google Cloud logo, click the project picker and then **New project**.
3. Name it something you will recognise, for example `bm-matic-website`, and click **Create**. Wait a few
   seconds until the picker shows the new project.

### 2.2 Turn on the APIs

1. In the search bar at the top, type **My Business Account Management API** and open it in the results.
2. Click **Enable**. If you see a red "not available" notice instead, that is step 2.4 below — continue anyway.
3. Search for **My Business Business Information API** and enable that one too.
4. Search for **Google My Business API** and enable it as well. (Google splits this into several APIs; the site
   uses the review part of the last one.)

### 2.3 Create the OAuth credentials

1. In the search bar, type **OAuth consent screen** and open it.
2. Choose **External** and click **Create**. Fill in the app name (for example `BM-Matic website`), your own
   email address as the support email, and your email address again at the bottom as the developer contact.
   Save and continue through the next screens; you do not have to add scopes or test users.
3. Back on the consent screen page, click **Publish app** and confirm. As long as it is in "Testing", the
   connection stops working after seven days.
4. Search for **Credentials**, click **+ Create credentials → OAuth client ID**.
5. Application type: **Web application**. Name: anything.
6. Under **Authorised redirect URIs**, click **Add URI** and paste the address of the panel's callback. It is
   your admin address with `/reviews/callback` behind it, for example:

   ```
   https://www.bm-matic.be/beheer/reviews/callback
   ```

   It has to match exactly — https, no trailing slash, the same admin folder you sign in to.
7. Click **Create**. Google shows a **Client ID** and a **Client secret**. Keep that window open.

### 2.4 Ask Google for access

The review part of the API is not open to everyone: Google reviews each project by hand.

1. Open <https://developers.google.com/my-business/content/prereqs> and follow the link to the access request
   form.
2. Fill it in with the workshop's details and the project you just created. Google asks what you will use the
   API for; "showing our own Google reviews on our own website" is the honest and correct answer.
3. You get an email when it is approved. This usually takes a few days, sometimes a few weeks.

Until then, every attempt to sync shows **"Google has not approved API access for this project yet"** on the
Connection card. That is not a fault in the website; see section 5 for what to do meanwhile.

### 2.5 Connect the panel

1. In the panel, **Content → Google reviews**.
2. Source: **Google Business Profile API**.
3. Paste the **OAuth client ID** and the **OAuth client secret** from step 2.3.
4. **Location ID**: the address of your location in Google's own notation, in the form
   `accounts/123456789/locations/987654321`. You find it in the Business Profile API dashboard, or your web
   developer can read it for you once the connection is made.
5. Click **Save changes**. A block appears with **Connect with Google**.
6. Click **Connect**. You go to Google, sign in if needed, and approve the request once. Google sends you
   straight back to the panel and the card says it is connected.

You never have to do this again. The site keeps a token and refreshes its own access. If you change your Google
password or withdraw the app's access in your Google account, the card says the connection expired and offers
**Connect again**.

---

## 3. Google Places API (New) — the quick one

1. In the same Google Cloud project, search for **Places API (New)** and click **Enable**.
2. Go to **Credentials → + Create credentials → API key** and copy the key.
3. Click **Restrict key**, and under **API restrictions** choose **Restrict key** and tick only
   **Places API (New)**. This matters: an unrestricted key can be abused if it ever leaks.
4. Find your **Place ID** at <https://developers.google.com/maps/documentation/places/web-service/place-id> —
   type the workshop's name and address in the finder and copy the code that starts with `ChIJ`.
5. In the panel: Source **Google Places API**, paste the API key and the Place ID, and **Save changes**.
6. Click **Sync now**.

Google hands over **five reviews at most** through this API. The rating (for example 4.9) and the total count
(for example 213) are the real totals for your location; the five reviews are simply the ones Google chooses to
return. The panel says so on the Connection card, so nobody goes looking for the missing ones.

Note that the Places API is billed by Google after a free monthly allowance. With one sync per day the usage is
negligible, but you do need a billing account on the project.

---

## 4. Manual import (JSON or CSV)

Useful while you wait for approval, and as a way to put a few reviews on the website by hand.

1. Make a file with one line per review. A spreadsheet saved as CSV is fine. Example:

   ```csv
   Reviewer,Rating,Review,Date
   An De Smet,5,"Gearbox rebuilt in three days. Clear explanation.",01/09/2026
   Pierre Dubois,5,"Diagnostic clair et rapide.",08/09/2026
   ```

   Dates may be written as `01/09/2026` (day/month/year) or `2026-09-01`.
2. In the panel, scroll to **Import from a file**, choose the file and click **Show preview**.
3. The panel guesses which column is which and shows the first rows. Correct the mapping if a column ended up in
   the wrong place, then click **Import these reviews**.
4. Nothing is stored before you press that button.

Importing the same file twice does not create doubles: a review is recognised by its text, name and date, so the
second import simply updates the first.

---

## 5. What to do while Google has not approved access yet

- Use **Manual import** or the **Places API** so the website is not empty.
- Fill in **Link to your reviews on Google** on the Connection card. That is the link behind
  "Read all reviews on Google" under the reviews on the website, and it works no matter which source you use.
- Type the rating and the number of reviews Google shows on your profile in Settings → General, so the badge is
  right. As soon as a real sync runs, the site replaces them with Google's own numbers.
- When the approval email arrives, switch the source to **Google Business Profile API**, connect, and press
  **Sync now**. Reviews you imported by hand stay where they are; the ones Google returns are added next to them.

---

## 6. Keeping it up to date automatically

The Connection card has **Automatic sync**: every 6, 12 or 24 hours, or manual only. For that to happen without
anyone opening the panel, the hosting has to call the site once in a while. On most shared hosting this is a
"cron job" in the control panel (cPanel, DirectAdmin, Plesk).

Add one job that runs, for example, every hour:

```bash
php /home/YOURACCOUNT/bm-matic/bin/console reviews:sync --cron
```

Replace the path with the folder where the site is installed — your hosting company can tell you, or your
developer put it in the handover note. `--cron` means "only actually sync when it is due", so running it hourly
while the setting says every 12 hours is harmless.

If your control panel only offers a URL to call instead of a command, ask your developer: the site deliberately
does not expose the sync over the web.

You can always press **Sync now** in the panel; that ignores the schedule.

---

## 7. What you decide, and what Google decides

**You decide** which reviews appear on the website. New reviews arrive **hidden** (you can change that in
**New reviews** on the Connection card), and you make one visible with the switch in the **On website** column,
or use **Show all** / **Hide all** above the list to do it for everything that matches your filters. Every one of
those changes is written to the security log, so you can always see who put what on the website.

**Google decides** the star rating and the number of reviews. The website never calculates an average from the
reviews you happen to show — that would be misleading and, under the European rules on consumer reviews, not
allowed. It shows Google's own figure, keeps the label "Reviews from Google", and links to your profile so a
visitor can read all of them.

A sync never changes what you published, and never deletes anything. A review that disappears from Google is
marked "no longer at Google" in the panel and stays there until you remove it from the website yourself.

Reviewer photos are off by default. When you turn them on, the site downloads the photos itself and serves them
from your own domain, so a visitor's browser never contacts Google before the cookie banner was even answered.
A photo that cannot be fetched simply shows the reviewer's initials, exactly like the design.

---

## 8. When something goes wrong

The Connection card always tells you the state in one line, and keeps the last error underneath.

| What it says | What it means | What to do |
| --- | --- | --- |
| *Not configured yet* | The source has no key, ID or connection yet | Finish the fields on the Connection card |
| *Ready · not synced yet* | Everything is filled in, nothing imported yet | Press **Sync now** |
| *Connected · last sync …* | Working normally | Nothing |
| *Needs attention* + "Google has not approved API access for this project yet" | Your request from step 2.4 is still pending | Wait for the email; use Places or a manual import meanwhile |
| *Needs attention* + "The connection with Google expired" | The token was withdrawn, or your Google password changed | Click **Connect again** on the Connection card |
| *Needs attention* + "Google is rate limiting the sync" | Too many calls in a short time | Nothing — the site waits longer before it tries again |
| *Needs attention* + anything else | A network problem or an answer Google's side could not deliver | Try **Sync now** once; if it keeps failing, send the line to your developer |

After a failed sync the site waits before trying again — first 5 minutes, then 15, 45, 2 hours, and at most
6 hours — so a problem at Google never turns into hundreds of failed calls. **Sync now** ignores that wait.

**The website is never affected by any of this.** If the connection breaks, the reviews you approved stay on the
site exactly as they were. A broken connection is a panel problem, not a visitor problem.
