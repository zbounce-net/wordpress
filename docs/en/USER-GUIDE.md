# ZBounce Email Validator 1.2.5

## Installation and setup
Requires WordPress 5.0 or newer and PHP 7.4 or newer. Install `zbounce-email-validator-1.2.5.zip` through Plugins → Add New → Upload Plugin, then activate it. The complete release archive is not an installable plugin.

Open Settings → ZBounce Email. Purchase verification credits at https://zbounce.net/pricing.html, save your API key and accept the service agreement at https://zbounce.net/license.html. The plugin source is GPLv2-or-later; the external service has separate terms. No verification credits or API key are included in the download.

The balance and administrator email tester work independently of Automatic form verification. Enable that switch only when ready to validate form submissions. Saving a key does not prove that it is valid or has credits. Check connection and balance to confirm access. An unavailable balance is not a zero balance.

## Checks, credits and cache
Enter an email in the settings tester. With the cache checkbox enabled, an existing usable result avoids another remote verification. Disable it to request a fresh check, which may consume credits. Balance queries do not verify an address. The displayed balance can be cached for up to 60 seconds.

Email results are cached for 1–168 hours, with a 24-hour default. Unknown results and API failures are not cached. Changing the accepted-status policy applies immediately to cached facts. Verification cannot guarantee delivery or eliminate all abuse.

## Accepted results
| Result | Default | Meaning |
| --- | --- | --- |
| valid | Allow | Positive verification result |
| risky | Allow | Catch-all / accept-all uncertainty |
| unknown | Allow | Undecided mailbox result |
| unavailable | Allow | Verification service request failed |
| invalid | Reject | Invalid email address |
| disposable | Reject | Disposable address |
| no_mx | Reject | Domain cannot receive mail |
| mailbox_not_found | Reject | Mailbox reported missing |

Use the settings checkboxes to choose your policy. Independent form syntax checks still apply. With automatic checks disabled, this plugin neither verifies forms nor rejects them using cached results.

## Integrations and languages
Supports Contact Form 7, WordPress registration, WooCommerce classic checkout and Checkout Block, and selected Gravity Forms, WPForms and Ninja Forms forms. Checkout Block verification runs on the order-submission POST before payment; draft GET/PUT/PATCH calls do not spend credits. Test the actual forms used on your site. The `[zb_email_validator]` shortcode is an administrator-only tester, not a public credit-consuming widget.

English is the source language. Bundled translations: Russian, German, French, Ukrainian and Simplified Chinese. WordPress chooses the locale from its site/user language settings. Each catalog contains all 105 current source messages.

## Troubleshooting and support
Disabled controls: save a nonempty key and accept the agreement. Connection errors: check the key, credits, server HTTPS connectivity and service availability. Unexpected allowed/rejected results: inspect the accepted-status policy and the form's own validation. Avoid bypassing cache repeatedly when testing a real key.

Contact support@zbounce.net with plugin/WordPress/PHP versions, affected integration and reproduction steps. Never send API keys, passwords or customer lists. For developer test instructions see `tests/stand/README.md` in the source archive.
