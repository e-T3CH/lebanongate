# DNS and email

Which DNS records the website needs, and how to make sure the emails the site sends (contact notifications,
newsletter confirmations, invitations) arrive instead of landing in spam. Replace `gatelebanon.org` (an example) with the real domain and the
example values with the ones your hosting and email providers show.

## Website records

| Name | Type | Value | Why |
| --- | --- | --- | --- |
| `@` | A | the IPv4 address of the hosting (hPanel shows it) | `gatelebanon.org` reaches the server |
| `@` | AAAA | the IPv6 address, if the hosting has one | same, over IPv6 |
| `www` | CNAME | `gatelebanon.org.` | `www.gatelebanon.org` works too |
| `@` | CAA (optional) | `0 issue "letsencrypt.org"` | only this certificate authority may issue certificates for the domain |

If you move the nameservers to the hosting company, it creates the A, AAAA and www records itself. Set the site
address in the installer to the one you want people to see (`https://gatelebanon.org`); the site redirects to it.

## Email the website sends

The site sends through SMTP with the account set in **Settings → Email**. Mail servers decide whether to trust a
message from `@gatelebanon.org` with three DNS records. All three are TXT records on the sending domain.

### SPF — which servers may send for the domain

One TXT record on `@`, listing every service that sends mail as `@gatelebanon.org`:

```
v=spf1 include:_spf.mail.hostinger.com ~all
```

- Use the `include:` your mail provider documents (Hostinger, Microsoft 365: `include:spf.protection.outlook.com`,
  Google Workspace: `include:_spf.google.com`, …). If GATE's mailbox and the website use different providers,
  list both in the **same** record: a domain may have only one SPF record.
- Start with `~all` (soft fail); change to `-all` once you are sure every sender is listed.

### DKIM — a signature on every message

The mail provider generates a key pair and shows one or more records to add, for example:

| Name | Type | Value |
| --- | --- | --- |
| `hostingermail1._domainkey` | CNAME or TXT | as shown by the provider |

Add them exactly as shown and switch DKIM on in the provider's panel. Every provider that sends as `@gatelebanon.org`
needs its own DKIM record.

### DMARC — what receivers do when SPF/DKIM fail, and reports to you

A TXT record on `_dmarc`:

```
v=DMARC1; p=none; rua=mailto:dmarc@gatelebanon.org; adkim=r; aspf=r
```

1. Start with `p=none` for two to four weeks and read the reports sent to the `rua` address (or use a free DMARC
   report service) to confirm the website and GATE's own mail both pass.
2. Then tighten to `p=quarantine`, and later to `p=reject`.

### Check it

- Send a test from **Settings → Email** to a Gmail address, open the message → "Show original": SPF, DKIM and DMARC
  should all say **PASS**.
- Or send one to the address shown on <https://www.mail-tester.com> and aim for 10/10.

## Records you might already have

- **MX** records decide where mail *to* `@gatelebanon.org` is delivered. Keep them as they are if GATE's mailbox
  already works; the website only sends.
- The **TXT** record Google Search Console asks for (deploy/GO-LIVE.md step 12) goes on `@` next to SPF; that is fine,
  a domain can have several TXT records — just not two SPF ones.
