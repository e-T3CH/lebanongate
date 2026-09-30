# GATE Lebanon — user guide for the admin panel

This guide is for the team that runs the website: how to answer messages, publish projects, news, reports and photos,
change the texts of the pages, and what to do when something does not work. It is written in plain language; nothing
here needs technical knowledge.

**Contents** — [Signing in](#signing-in) · [The dashboard](#the-dashboard) · [Messages](#messages) ·
[Newsletter](#newsletter) · [Projects, news, publications and the gallery](#projects-news-publications-and-the-gallery) ·
[Pages and the home page](#pages-and-the-home-page) · [Areas of expertise](#areas-of-expertise) ·
[Partners, donors and impact figures](#partners-donors-and-impact-figures) · [Media](#media) ·
[Website texts](#website-texts) ·
[Languages](#languages) · [Appearance](#appearance) · [Settings](#settings) · [Users](#users) ·
[Two-factor authentication](#two-factor-authentication) · [When something goes wrong](#when-something-goes-wrong)

---

## Signing in

Sign in at the secret admin address you received when the site was installed (something like
`https://gatelebanon.org/admin-7f3kq2`). Keep it in your password manager. The panel is available in English and
French; switch under **Settings → Languages → Admin panel language**.

After 30 minutes without activity you are signed out. Your work is safe; just sign in again. After a few wrong
passwords the account is locked for 15 minutes (see [When something goes wrong](#when-something-goes-wrong)).

**Forgot your password?** under the sign-in button sends a link to your email address. It works once, for one hour.

## The dashboard

The first screen shows what needs attention: messages of the last seven days, unread messages, the number of projects
and news items, and newsletter subscribers. Below that are the newest messages.

On the right:

- **Add content** creates a new project, news item, publication or album in one click.
- **Quick controls** switch the newsletter sign-up form and maintenance mode on or off. They save themselves.

## Messages

Everything a visitor sends through the contact form arrives in **Messages**.

- The tabs are **Inbox**, **Unread** and **Archive**. Search by name, email or text, and filter by subject (general,
  partnership, volunteering, media…).
- Opening a message marks it read. **Mark as unread** puts it back, for example when a colleague has to answer.
- **Reply by email** opens your own mail program with the sender's address and a subject already filled in. The phone
  number is a link on a phone.
- **Internal notes** record what was agreed or who follows up. Only your team sees them, with who wrote them and when.
- **Archive** moves a handled message out of the inbox (it can be moved back). **Delete** removes it permanently.
- **Export CSV** downloads the messages matching your filters as a spreadsheet.

Every note, archive, delete and export is recorded in the security log.

## Newsletter

The footer of the website has a newsletter sign-up form. A visitor enters an email address, ticks the consent box and
receives an email with a confirmation link (double opt-in). Only confirmed addresses count as subscribers.

**Newsletter** lists them in three tabs: **Confirmed**, **Waiting for confirmation** and **Unsubscribed**. **Export
confirmed (CSV)** gives you the list to import into your mailing tool (Mailchimp, Brevo…). Every email you send there
must keep an unsubscribe link. People who unsubscribe through the website's link are marked here.

Switch the form off with the quick control on the dashboard or under **Settings → General**.

## Projects, news, publications and the gallery

These four work the same way. Each has its own item in the menu.

| Menu item | What it holds | Extra fields |
| --- | --- | --- |
| **Projects** | Your programmes and interventions | Status (planned, ongoing, completed), area of expertise, region, start and end date, people reached, funded by |
| **News** | Updates and stories | Date, area of expertise, related project |
| **Publications** | Reports, studies, brochures | Type, date, the PDF file |
| **Gallery** | Photo albums | Date, related project, the photos |

### Adding an item

1. Open the list (for example **Projects**) and type a title in **Add a project** on the right.
2. You land on the edit screen. Fill in the texts: title, summary (one or two sentences for the cards), the full text,
   location, and the SEO title and description.
3. Fill in the **Details** on the right: the date, status, region, cover image and so on. These are the same in every
   language.
4. Switch **Shown on the website** on and press **Save changes**. A new item stays hidden until you do this, so you can
   prepare it in peace.

**Featured** puts a project or news item first on the home page. **View on the website** opens the published page.

### Languages

The tabs above the texts (EN · AR · FR) hold one translation each. Write one language, press **Save changes**, then
switch tab. A language that is not translated yet, or whose **Published in this language** switch is off, shows the
default language to visitors instead. The coloured dots in the list show the state of each language (green:
published, amber: draft, grey: missing).

The web address (slug) is made from the title when you leave it empty. Arabic titles get their own Arabic address.

### Publications (PDF)

Upload the PDF under **Media** first, then choose it in **PDF file** on the publication. Visitors see a download
button; the file is served with a readable name, such as `annual-report-2025.pdf`.

### Albums

Upload the photos under **Media**. In the album, tick the photos under **Add photos from the media library** and press
**Save photos**. Drag the photos to change the order; switch one off to take it out of the album (it stays in Media).

## Pages and the home page

**Pages** holds the fixed pages: Home, About us with its sub-pages (Who we are, Mission and vision, Organisational
profile), Areas of expertise, Projects, News, Publications, Gallery, Partners and donors, Contact, and the legal pages.

- Each page has a tab per language with its title, intro, text and SEO fields, and a **Header image** shown behind the
  page title.
- Changing a page's web address keeps the old address working through an automatic redirect.
- **In the menu** and **Menu position** decide where a page appears in the navigation.

### Sections of the home page

Open **Home** to see its sections: Hero, About us, Areas of expertise, Impact figures, Projects, Where we work (map),
News, Partners and donors, Call to action. Drag them into another order, switch the ones you do not need off, and
press **Save order**. **Edit texts** opens one section:

- **Hero**: title, highlighted part, intro and the background image.
- **About us**: the texts, two photos, the badge (for example *2014* / *Working in Lebanon since*) and up to three
  value points with an icon.
- **Where we work**: the main region (highlighted on the map, Akkar by default) and a short note per region that
  appears when a visitor points at it. The project counts per region come from your projects automatically.

The **legal pages** (privacy policy, cookie policy, terms of use) were delivered as templates. Have them checked
against Lebanese law and your donors' requirements, and complete everything in [square brackets] before going live.

## Areas of expertise

Education, protection, social cohesion, livelihoods, emergency response, governance… Each area has texts per
language, an icon, a cover image and switches for **Shown on the website** and **Shown on the home page**. Drag the
list to change the order. Projects and news link to an area, and the area's page lists them.

## Partners, donors and impact figures

- **Partners & donors**: one row per organisation with its name, type (donor or partner), website, logo (from Media)
  and a short description in the language you are editing. Donors and partners appear in separate groups on the
  Partners page and in the logo strip on the home page.
- **Impact figures**: the numbers on the home page (for example *25,000+ / people reached*), per language.

Change the order with the arrows or by dragging, and press **Save changes**.

## Media

**Media** holds every image and PDF document. The tabs filter by **Images** and **PDF documents**.

- **Images**: PNG, JPEG or WebP, at most 6 MB and 25 megapixels (a normal phone photo is fine). Every image is
  re-encoded on arrival, so location data from a phone never reaches the website. Give every image an **alt text** in
  each language: one short sentence describing what is on it, read out by screen readers.
- **PDF documents**: at most 25 MB. Give each one a title per language.
- **Replace file** swaps the file but keeps it wherever it is used. An image can only be replaced by an image, a PDF by
  a PDF.
- Before deleting, the panel shows where a file is used ("Cover (2)", "Gallery (1)"), so nothing disappears by
  accident.

Wherever you pick an image (a cover, a logo, a header image) a small preview shows the chosen picture.

## Website texts

**Website texts** holds the fixed wording of the website: the "Partner with us" button, the footer blurb, the
newsletter box, form labels and messages, "Read more" and similar buttons, the texts of the emails visitors receive,
and the error pages.

- Choose the language with the tabs (EN · AR · FR). Under each text you see the default language's wording as a
  reference; Arabic texts are typed right to left.
- **Part of the website** and **Search** narrow the list. Change what you need and press **Save changes**; only the
  texts you changed are saved.
- A changed text gets a **Changed** mark and a **Reset to original** link. Your wording is kept when the website is
  updated.
- Words that start with a colon, such as `:year` or `:name`, are filled in automatically (the year, a name, a
  number). Keep them in the text; the panel refuses a text without them.

The call-to-action banner at the bottom of the inner pages uses the texts of the home page's **Call to action**
section (Pages → Home → Call to action → Edit), so one change updates it everywhere.

## Languages

The website is in **English**, **Arabic** and **French**. Arabic pages are shown right-to-left automatically. Under
**Settings → Languages** you choose which languages visitors see and which one is the default. The language selector
in the header lists the enabled languages.

## Appearance

The logo (light and dark), the favicon, the colours and the motion setting. The colours start from the GATE brand
blue (#5091CD) with a cedar-green accent. The colour fields show a live contrast check: below 4.5:1 text becomes hard
to read, and the panel warns you. **Reset to the approved design** puts everything back.

## Settings

- **General**: the organisation (name, legal name, registration number, year founded), the address, phone, email,
  office hours and map position, and switches for the newsletter sign-up and maintenance mode.
- **Languages**: see [Languages](#languages).
- **Social media**: one line per network (Facebook, Instagram, LinkedIn, X, YouTube, WhatsApp): the link, and whether
  it appears in the header, the footer or both.
- **Email**: the mail server that sends the contact notifications and the newsletter confirmations, **Contact
  messages go to**, and **Send test email**.
- **Security**: two-factor authentication, the login lockout, the session timeout and the admin address. **Security
  log** shows who did what, and can be filtered and exported.
- **Maintenance** (administrators):
  - **Scheduled tasks**: the secret address that an outside service (for example cron-job.org) opens every 15 minutes
    so emails go out and a backup is made every day. **Run now** does it at once.
  - **Backups**: **Make a backup**, **Download** one to keep a copy off the server (do this regularly), and **Upload**
    a backup you downloaded earlier.
  - **Restore a backup**: puts the whole website back as it was at that backup. A backup of the current state is made
    first. This also works on a new installation.
  - **Uptime monitor**, **Before going live** (every placeholder, missing translation or alt text that is left) and
    **Analytics** (off, Google Analytics or Plausible, only after a visitor's consent).

## Users

Invite a colleague by email: they receive a link, valid for three days, and choose their own password.

- An **Administrator** may do everything.
- An **Editor** works with messages, the newsletter list, content and media, but does not see users, settings,
  security or appearance.

Users are never deleted, because the security log has to keep making sense. Deactivate them instead; that can be
undone at any time. The last active administrator cannot be deactivated. Your own name, email address and password are
under **My profile**.

## Two-factor authentication

Signing in then needs your password **and** a six-digit code from an app on your phone (Google Authenticator,
Microsoft Authenticator, 1Password, Bitwarden…). Someone who learns your password still cannot get in. Switch it on for
every administrator.

1. **Security → Enable two-factor authentication**, and confirm with your password.
2. Scan the QR code with the app and type the six-digit code it shows.
3. You get **ten recovery codes**. Store them in your password manager or print them: each works once, for when your
   phone is lost.

An administrator can require two-factor authentication for everybody under **Security**.

## When something goes wrong

| What you see | What to do |
| --- | --- |
| "Page not found" at the admin address | Check the address in your password manager; your developer can print it again. |
| Your account is locked after wrong passwords | Wait 15 minutes, or ask your developer to unlock it. |
| You lost your password | Click **Forgot your password?** and follow the link in the email. No email? Check the spam folder. |
| You lost your phone with the authenticator app | Sign in with a recovery code and set up two-factor authentication again. No codes left: your developer can reset it. |
| "Something went wrong" with a code (for example 7K2QF9XM) | Send the code to your developer; it points to exactly what happened. |
| A new project or news item does not appear | Check **Shown on the website** and **Published in this language**, and press **Save changes**. |
| Visitors say the contact form does not work | Check that maintenance mode is off, then send yourself a test message. |
| Contact notifications or newsletter confirmations do not arrive | **Settings → Email → Send test email**; check the spam folder; tell your developer if the test fails. |
| You changed or deleted something by mistake | Most changes can simply be made again. For lost content, an administrator can restore last night's backup in **Settings → Maintenance** (everything after that backup is lost, so ask first). |

Your developer's routine for backups, updates and monitoring is in [MAINTENANCE.md](MAINTENANCE.md).
