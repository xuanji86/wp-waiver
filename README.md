# WP Waiver

Online waiver signing for WordPress: admin-editable waiver documents, a handwritten
signature pad, tamper-evident signed records, and layered anti-bot protection —
with **no third-party form plugins** and (by default) **no third-party requests**.

Built for a real Texas shooting range ([Triple A Sportsman Club](https://aaarange.com)),
generalized for any business that needs visitors to sign a release online: ranges,
gyms, climbing walls, rentals, tours, events.

## Features

- **Editable waiver documents** — a `Waiver Documents` post type: write your
  agreement in the block editor, keep several documents, revisions included.
- **Handwritten signature** — a vanilla-JS canvas pad (mouse / touch / stylus)
  plus a typed legal name. Submission is blocked until both exist.
- **Tamper-evident records** — every signed waiver stores a private record with
  the participant's details, IP, user agent, the signature PNG (random-suffix
  filename in uploads), **a verbatim snapshot of the agreement as signed, and its
  SHA-256 hash**. Editing the document later never changes what a record proves.
- **Layered anti-bot** — always on, zero config, invisible to humans:
  honeypot, an HMAC-signed time-trap (submissions faster than 8s are bots — a
  human has to read the agreement and draw a signature), and a per-IP hourly
  rate limit. Bots receive a fake success and nothing is stored.
- **Optional captcha** — Google reCAPTCHA v2 (checkbox) or Cloudflare Turnstile;
  paste your keys in settings to enable. Nothing third-party loads otherwise.
- **Email** — new-waiver notification (signature attached) to an address you
  choose, plus an optional receipt to the signer containing the agreement text.
- **Themeable** — all markup uses `wpw-` classes; the bundled stylesheet is
  driven entirely by `--wpw-*` CSS custom properties, so a theme restyles the
  whole form by overriding variables on `.wpw`.

## Install

1. Download (or clone) this repository into `wp-content/plugins/wp-waiver` and
   activate **WP Waiver**.
2. Go to **Waivers → Waiver Documents**, write your agreement, publish it.
   (A draft sample is created on activation as a starting point.)
3. Go to **Waivers → Settings**: pick the default document, set the
   notification email, optionally enable the signer receipt and a captcha.
4. Put `[wp_waiver_form]` on any page — or `[wp_waiver_form id="123"]` to pin a
   specific document.

Signed waivers appear under **Waivers → Signed Records**.

## Shortcode

```
[wp_waiver_form]           // uses the default document from settings
[wp_waiver_form id="123"]  // uses waiver document #123
```

## Theming

Override the CSS variables (all of them optional):

```css
.wpw {
  --wpw-text: #eae7db;   --wpw-muted: #9b9683;
  --wpw-panel: #1b1a13;  --wpw-field-bg: #100f0a;
  --wpw-border: #2b2a1f; --wpw-accent: #c99f56; --wpw-accent-text: #14130e;
  --wpw-paper: #e8e6de;  --wpw-ink: #14130e;    /* signature pad colors */
  --wpw-radius: 0;       --wpw-font: 'Barlow', sans-serif;
}
```

Replace the confirmation panel:

```php
add_filter( 'wpw_confirmation_html', function ( $html, $doc ) {
    return '<div class="wpw-confirm">…your markup…</div>';
}, 10, 2 );
```

## Filters

| Filter | Default | Purpose |
|---|---|---|
| `wpw_min_fill_seconds` | `8` | Time-trap: minimum seconds between form render and submit |
| `wpw_max_submissions_per_hour` | `5` | Per-IP rate limit |
| `wpw_confirmation_html` | built-in panel | Confirmation markup (`$html, $doc`) |

## Data & privacy notes

- Records are a private post type visible to admins/editors only; the signature
  PNG lives in `uploads/` under an unguessable random filename.
- Uninstalling removes plugin settings only — **signed records and documents are
  legal records and are never auto-deleted**.
- The plugin makes no external requests unless you enable a captcha provider
  (then only to that provider's verify endpoint).

## Disclaimer

This plugin captures signatures and stores records; it is not legal advice.
Whether an electronically signed waiver is enforceable depends on your
jurisdiction and your agreement text — have counsel review both.

## Roadmap

Configurable form fields, PDF export, reCAPTCHA v3, GDPR export/erase
integration, WordPress.org directory submission.

## License

GPL v2 or later — see [LICENSE](LICENSE).
