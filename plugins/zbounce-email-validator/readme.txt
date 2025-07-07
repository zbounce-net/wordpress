=== ZBounce Email Validator ===
Contributors: zbounce.net
Tags: email, validation, verification, zbounce, woocommerce, cf7
Requires at least: 5.0
Requires PHP: 7.0
Tested up to: 6.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate with ZBounce Email Engine Platform to validate email addresses in your forms.

This plugin hooks into Contact Form 7, WooCommerce checkout and WordPress registration forms to:
* Validate email format & SMTP existence
* Block disposable & catch-all addresses
* Prevent invalid submissions (ideal alongside CAPTCHA)

== Installation ==

1. Upload the folder `zbounce-email-validator` to `/wp-content/plugins/`
2. Activate the plugin through the “Plugins” menu in WordPress
3. (Optional) Configure your API key under **Settings → ZBounce Email Validator**
4. Email fields in supported forms will now validate on submit

== Frequently Asked Questions ==

= Do I need an API key? =
You can run in demo mode (rate-limited) without a key. For production, get a key at https://zbounce.net.

= What forms are supported? =
Out of the box:
* Contact Form 7
* WooCommerce checkout
* WordPress registration

== Changelog ==

= 1.1.0 =
* Added 24-hour caching of validation results
* Added Gravity Forms integration
* Added WPForms integration
* Added Ninja Forms integration

= 1.0.0 =
* Initial release
* Contact Form 7, WooCommerce & WP registration support
* Shortcode `[zb_email_validator]`

== Upgrade Notice ==

= 1.1.0 =
Introduces caching and new form integrations (Gravity, WPForms, Ninja).

