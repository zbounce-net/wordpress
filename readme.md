# ZBounce WordPress plugin

Plugin source: `plugins/zbounce-email-validator/`. Its `readme.txt` is the authoritative product description.

Version 1.2.1 requires purchased ZBounce verification credits and an API key, plus explicit administrator enablement and acceptance of the ZBounce service license agreement. Settings include the live key balance and an email tester with optional cache bypass. No anonymous demo fallback.

Purchase: https://zbounce.net/pricing.html

Build a clean submission ZIP with `python3 tools/package_plugin.py`. Validation evidence and remaining limitations are recorded in `RELEASE-CHECKLIST.md`.

The existing docker-compose.yml is an older local development environment. Do not point tests at a production WordPress installation.
