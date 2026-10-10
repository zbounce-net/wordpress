# ZBounce WordPress plugin

Plugin source: `plugins/zbounce-email-validator/`. Its `readme.txt` is the authoritative product description.

Version 1.2.5 requires purchased ZBounce verification credits and an API key, plus acceptance of the ZBounce service license agreement. Automatic form verification is separately opt-in. Settings include the live key balance and an email tester with optional cache bypass. No anonymous demo fallback.

Purchase: https://zbounce.net/pricing.html

Build a clean submission ZIP with `python3 tools/package_plugin.py`. Validation evidence and remaining limitations are recorded in `RELEASE-CHECKLIST.md`.

The existing docker-compose.yml is an older local development environment. Do not point tests at a production WordPress installation.

Release documentation: [English](docs/en/USER-GUIDE.md) · [Русский](docs/ru/USER-GUIDE.md).
Upgrade, data handling, security, validation and WordPress.org submission instructions are in `docs/en/` and `docs/ru/`.

For the complete release, install GNU gettext (`xgettext`, `msgcmp`, `msgattrib`, `msgfmt`) and use Python 3.9+. Run `python3 tools/package_release.py` from a checkout where release sources are tracked by Git. This audits source/catalog parity and creates the installer, tracked source archive, bilingual release bundle and SHA-256 checksums in `releases/`. ZIP contents have fixed timestamps and permissions. Only the installer ZIP should be uploaded to WordPress.
