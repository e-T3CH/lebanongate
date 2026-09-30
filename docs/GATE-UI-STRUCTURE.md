# GATE Lebanon website: structure and transitions

Blueprint for turning the BM-Matic codebase (`bm-matic-v3-final.zip`) into the GATE Lebanon website described in
`Website.docx` (the RFQ). The page structure and motion follow the pattern of the Careox non-profit template
(bracketweb), rebuilt in BM-Matic's own components. **No Careox code, CSS or images are copied**, so no template
license is needed. The colour palette is still to be chosen and is left as tokens here.

> Status: draft. The Careox demo could not be inspected from the build environment (the host is blocked), so the
> Careox column describes the usual layout of this template family; check it against the live demo.

## 1. Sitemap

Languages: English, Arabic (RTL), French. Every page lives under a language prefix, as BM-Matic already does
(`/en/…`, `/ar/…`, `/fr/…`).

```
Home
About Us
├── Who We Are
├── Mission & Vision
└── Organizational Profile        (downloadable profile PDF)
Our Expertise                       (list → detail per area)
Projects / Programs                 (list with filters → detail)
News & Updates                      (list with categories → article)
Publications                        (documents and reports, PDF downloads)
Gallery                             (albums → lightbox)
Partners / Donors
Contact Us
Privacy · Cookies · Terms           (footer only; already in BM-Matic)
```

Main menu: Home · About (dropdown) · Expertise · Projects · News · Publications · Contact.
Partners, Gallery and the legal pages are reached from the footer and from the home page sections.

## 2. Page mapping

| GATE page | Careox equivalent | BM-Matic starting point | Work |
| --- | --- | --- | --- |
| Home | Home (boxed) | `site/pages/home.php` + `page_sections` | New section set (§3) |
| About + 3 sub-pages | About, Team | `site/pages/about.php` | Add parent/child pages and dropdown navigation |
| Our Expertise | Services / What we do | `services` module, `service-card.php` | Rename, new icons |
| Projects / Programs | Causes list + details | none | **New module** (§5) |
| News & Updates | Blog list, sidebar, details | none | **New module** |
| Publications | none (document list) | `media` library (images only today) | **New module** + PDF upload support |
| Gallery | Gallery | `media` library | **New module** (albums, lightbox) |
| Partners / Donors | Sponsors strip | `partners` table | Full page + home strip |
| Contact Us | Contact | `site/pages/contact.php`, `appointment-form.php` | Turn the appointment form into a plain contact form |
| (removed) | Donate, Shop, Cart | `transmissions`, `process`, Google reviews | Remove or switch off |

## 3. Home page, section order

Every section is a row in `page_sections`, so the administrator can reorder or hide it (BM-Matic feature).

| # | Section | Content | Motion |
| --- | --- | --- | --- |
| 0 | Top bar | Email, phone, social links, language switcher | none |
| 1 | Header | Logo, menu with About dropdown, "Contact us" button | Sticky; shrinks and gains a background after 80px of scroll |
| 2 | Hero | One large field photo, headline ("Empowering Communities in Lebanon"), two buttons (Our projects, About us) | Headline words rise in sequence; image zooms slowly from 1.06 to 1 on load. No auto-playing slider |
| 3 | About intro | Two-column: photo collage + short text, 3 value points, "Read more" | Image slides in from the side, text reveals |
| 4 | Areas of Expertise | 4–6 cards: Education, Protection, Social Cohesion, Livelihoods, Youth & Women, Emergency Response | Staggered card reveal; hover lifts the card and fills the icon |
| 5 | Impact counters | 4 numbers (years active, people reached, projects, partners) on a colour band | Count up once when visible |
| 6 | Featured projects | 3 project cards (image, sector tag, region, status) + "All projects" | Staggered reveal; image zoom on hover |
| 7 | Where we work | Map of Lebanon with regions highlighted (Akkar first) | Regions fade in one after another |
| 8 | Latest news | 3 newest articles | Staggered reveal |
| 9 | Partners / Donors | Logo strip, greyscale → colour on hover | Slow, pausable marquee (still when motion is off) |
| 10 | Call to action | "Partner with us / Get in touch" band | Reveal |
| 11 | Footer | About blurb, quick links, contact, newsletter, social, legal links | none |

## 4. Inner page layouts

- **Page banner** (`page-hero.php`): title, breadcrumb (`breadcrumbs.php`), background photo with a colour overlay.
- **List pages** (Projects, News, Publications, Gallery): filter chips (`chips.php`) above a 3-column card grid
  (2 on tablet, 1 on phone), pagination (`pagination.php`).
- **Detail pages** (Project, Article): content column plus a sidebar (key facts or categories, recent items,
  a contact call to action). The sidebar drops below the content on phones.
- **About sub-pages**: text page with a side menu of the About pages (`prose.php`).

## 5. New content modules

Each one follows the existing pattern: a table plus a `*_translations` table, a repository, an admin list and edit
screen, publish state, slug with automatic redirects, a permission in `config/permissions.php`, and SEO fields.

| Module | Fields |
| --- | --- |
| Projects | Title, slug, summary, body, cover image, gallery, sector(s), region(s), donor(s), partner(s), start/end date, status (ongoing, completed), beneficiaries count, featured flag |
| News | Title, slug, excerpt, body, cover image, category, publish date, related project |
| Publications | Title, type (report, study, brochure…), year, language, PDF file, cover thumbnail, description |
| Gallery | Album title, date, related project, images with captions and alt text |
| Partners | Name, logo, website, type (donor, partner), order |

Media library change: accept `application/pdf` (checked on content, size limit, stored outside the image variant
pipeline, served with `Content-Disposition`).

## 6. Motion system

BM-Matic already has the engine; only the choreography changes.

| Existing piece | Where | Used for |
| --- | --- | --- |
| Scroll reveal with stagger | `app.js` (`IntersectionObserver`, threshold .14) | Section and card reveals |
| Motion tokens | `app/Core/ThemeDefaults.php` `MOTION`, `EASINGS` | Durations and easing; editable under Appearance |
| Motion off / reduced motion | `motion-off.css`, `prefers-reduced-motion` | Every animation below must switch off here |
| Page transitions | View Transitions (`::view-transition-*`) | Cross-fade between pages |
| Counters | `stats.php`, `stats.css` | Impact counters |

Rules:

- Reveal: fade + rise (`--rise`, 22px), `--dur-m` 700ms, `--ease-reveal`, stagger `--stagger` 70ms, capped at 330ms.
- Hover: `--dur-hover` 200ms; cards lift 4px and the shadow deepens; images scale to 1.05 inside a clipped frame.
- Header: background and height change over `--dur-s` 300ms.
- Counters run once, 1.8s (`--dur-draw`), eased out.
- Everything reveals only once; nothing loops except the partner marquee, which pauses on hover and focus.
- No preloader and no auto-playing hero slider (they slow the first view and cause accessibility problems).
- RTL: slide-in directions mirror (`inset-inline-start`, logical properties); marquee runs the other way.

## 7. Colour palette tokens (to be decided)

The palette will replace `ThemeDefaults::COLORS`. BM-Matic's design is dark; an NGO site reads better light, so the
site tokens change from "dark page, light text" to "light page, dark text", with one strong colour band for the
counters and the call to action.

| Token | Role | Value |
| --- | --- | --- |
| `c-page` | Page background | TBD |
| `c-surface` / `c-card` | Section and card backgrounds | TBD |
| `c-text-1` … `c-text-3` | Headings, body, muted text | TBD |
| `c-primary` / `c-primary-hover` | Buttons, links, active menu | TBD (from the GATE logo) |
| `c-accent` | Tags, icons, highlights | TBD |
| `c-band` | Counter band and CTA band | TBD |
| `c-footer` | Footer | TBD |

The Appearance screen's live contrast check keeps every pair at WCAG AA or better.

## 8. What BM-Matic loses

Remove or switch off: transmissions pages and section, repair process steps, appointment status workflow (keep the
messages inbox), Google reviews (optional; can stay off), the `AutoRepair` / `LocalBusiness` schema (replace with
`NGO` / `Organization`), and the Dutch language (replace with Arabic).

## 9. Open points

1. GATE logo and brand colours (the RFQ requires the existing visual identity).
2. The official expansion of "GATE", if there is one.
3. Photos from the field (the design depends on real imagery).
4. Final list of sectors, regions and donors for the project filters.
5. The BM-Matic source repository (`resources/`, `tools/`, `tests/`, `design/`): the zip holds only minified CSS/JS.
