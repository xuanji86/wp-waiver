# Changelog

All notable changes to WP Waiver are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/); versions follow
[Semantic Versioning](https://semver.org/).

Releases are automated: bump the `Version:` header in `wp-waiver.php`, add a
section below, merge to `main` — CI tags and publishes the release.

## [1.0.1] — 2026-08-17

### Added
- CI (multi-version PHP lint) and version-driven automated releases.
- Captcha setup guide (Google reCAPTCHA v2 and Cloudflare Turnstile) in the README.

### Changed
- README rewritten: badges, quick start, security-model walkthrough, hooks reference.

## [1.0.0] — 2026-08-17

First open-source release.

### Added
- **Editable waiver documents** — agreements written in the block editor
  (Waiver Documents CPT, revisions included), multiple documents,
  `[wp_waiver_form id]` or a settings default.
- **Handwritten signature pad** — vanilla-JS canvas (mouse / touch / stylus)
  plus a typed legal name.
- **Tamper-evident records** — each signed waiver stores a verbatim snapshot
  of the agreement as signed and its SHA-256; editing documents later never
  changes records.
- **Layered anti-bot** — honeypot, HMAC time-trap, per-IP rate limit (all
  silent), plus optional Google reCAPTCHA v2 or Cloudflare Turnstile.
- Notification + optional signer receipt emails.
- Neutral themable styles via `--wpw-*` CSS variables, every rule wrapped in
  `:where()` so theme overrides always win.
