=== ZBounce Email Validator ===
Tags: email validation, email verification, contact form 7, woocommerce, spam prevention
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Check form email addresses with the ZBounce service. Requires purchased verification credits and an API key from zbounce.net.

== Description ==

ZBounce Email Validator checks email addresses submitted through selected forms using the ZBounce email verification service.

The plugin is free and open source. External verification requires a paid ZBounce credit package. Buy credits at https://zbounce.net/pricing.html and enter the API key received after checkout in Settings > ZBounce Email. There is no separate paid plugin edition or local license activation.

External checks are disabled until you enter a key and explicitly enable them. The plugin does not use the public demo endpoint or fall back to a free service when a key fails.

The plugin rejects addresses reported by the API as invalid, disposable, or having no mailbox. Catch-all and undecided mailbox results are not automatically rejected. Verification cannot guarantee delivery or prevent every spam submission; use it alongside your existing form protections.

Integrations:
* Contact Form 7.
* WooCommerce classic checkout and Checkout Block.
* WordPress registration.
* Selected Gravity Forms, WPForms and Ninja Forms forms.

Results are cached locally for a configurable 1–168 hours (24 hours by default). Unknown mailbox results and API failures are not cached or presented as confirmed mailboxes. Your accepted-results policy determines whether those submissions pass; the default policy allows them.

The [zb_email_validator] shortcode provides a standalone tester for logged-in administrators only. It is not a public widget that can consume your credits anonymously.

== External services ==

This plugin relies on ZBounce (https://zbounce.net/) for remote email verification.

When the site administrator has entered an API key and enabled external verification, an uncached form check sends the email address being checked as JSON to https://api.zbounce.net/v1/fast-verify. The API key is sent in the X-API-Key HTTP header. These requests happen on form validation and when an administrator uses the standalone tester.

The administrator's Check connection and balance button sends the API key to https://api.zbounce.net/v1/me. That request does not include an email address and does not run a verification.

Requests are made from the WordPress server over HTTPS. The service receives the server's network address and normal HTTP request metadata. The plugin does not include other form fields, names, passwords, payment details or the visitor's IP address in its API payload. API keys are not exposed to frontend JavaScript.

The API key is stored in WordPress options. Cached results, including checked email addresses, are stored in the site's WordPress database. Expired cache entries are ignored on reads and removed during subsequent writes; expiration is not a promise of immediate physical deletion. Optional debug logging records request outcomes without email addresses, keys or response bodies. WordPress personal-data export and erasure tools include this local verification cache. These tools do not request new checks or erase data held by the external service. Deactivation preserves settings and cache; deletion of the plugin removes them.

Site owners are responsible for informing their visitors about this external processing. Review the service policies before enabling checks:
* Terms of service: https://zbounce.net/terms.html
* Privacy policy: https://zbounce.net/privacy.html

== Installation ==

1. Upload the zbounce-email-validator folder to /wp-content/plugins/ or install its ZIP from Plugins > Add New > Upload Plugin.
2. Activate ZBounce Email Validator.
3. Buy a verification package at https://zbounce.net/pricing.html and copy the API key shown after payment.
4. Open Settings > ZBounce Email, paste the key, enable external verification and save.
5. Use Check connection and balance, then select the forms to validate.
6. Test your form before relying on the integration in production.

== Frequently Asked Questions ==

= Is this a paid plugin? =
No. The plugin is GPL software. The remote ZBounce verification service requires a purchased credit package and API key. Current packages are listed at https://zbounce.net/pricing.html.

= Does it send data before I configure it? =
No. You must enter an API key and accept the service license agreement at https://zbounce.net/license.html and explicitly enable external verification first. There is no automatic demo mode.

= Does it block catch-all addresses? =
By default they pass. Uncheck Risky in Accepted verification results to reject accept-all domains, or Unknown to reject unconfirmed mailboxes. These outcomes are kept separate from missing mailboxes.

= What happens if I run out of credits or the API is unavailable? =
The result is API unavailable. It passes by default; uncheck API unavailable to reject submissions until verification works again. No successful remote result is fabricated or cached. Check your balance using the settings page.

= How do I disable all remote checks? =
Uncheck External verification and save. An empty Contact Form 7 selection means all Contact Form 7 forms; other optional form integrations require selecting form IDs.

= Is the source code included? =
Yes. PHP, JavaScript and CSS are supplied as readable source. Compiled translation MO files have the corresponding PO sources in languages/.

== Changelog ==

= 1.2.3 =
* Keep the connection/balance refresh button visible beside the balance; disable it until setup is complete.

= 1.2.2 =
* Always show the balance and email tester at the top of settings, with disabled controls and setup guidance until verification is enabled.

= 1.2.1 =
* Require explicit acceptance of the ZBounce service license agreement before any API use.
* Add an administrator email tester to settings, with an optional cache bypass and policy/source results.
* Display the key balance in settings and refresh it after verification.

= 1.2.0 =
* Require a purchased ZBounce API key and administrator opt-in before external requests.
* Remove automatic demo fallback and public anonymous tester access.
* Document external requests, privacy, local storage and paid service requirements.
* Sanitize settings, protect direct PHP access and remove personal data from debug logs.
* Keep service failures distinct from verification results; clean expired cache entries on writes.
* Fix repeated admin tester results and translate its status labels.
* Treat transient SMTP failures as undecided, honor shorter API retry TTLs and validate legacy cached results.
* Add WordPress personal-data export and erasure for the local cache.
* Align plugin metadata and provide a connection/balance check.

= 1.1.1 =
* Previous API-key fallback behaviour.

= 1.1.0 =
* Added caching and additional form integrations.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.0 =
External checks now require your purchased API key and explicit enablement in Settings. Demo fallback is removed. Review the external service disclosure before enabling checks.

== Verification policy ==
Choose accepted results in Settings: valid, risky (accept-all), disposable, invalid format, no-mail domain, missing mailbox, unknown, and API unavailable. Checked results pass. Native form format checks still apply. Defaults allow valid, risky, unknown and API unavailable; other results are rejected. SMTP uncertainty is distinct from API connection/key/balance failure. Rules re-evaluate cached results immediately. Unknown and failed checks are not cached.

WooCommerce Checkout Block validates the billing email server-side when an order is submitted, before payment. Intermediate checkout draft updates do not trigger verification. Classic checkout remains supported.
