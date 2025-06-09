#  ZBounce Email Validator 
Contributors: zbounce.net
Tags: email, validation, verification, zbounce, woocommerce, cf7
Requires at least: 5.0         <!-- WP-vers. can stay, PHP-vers. ниже -->
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate with ZBounce Email Engine Platform to validate email addresses in your forms.

This plugin hooks into Contact Form 7, WooCommerce checkout and WP registration forms to:
* Validate email format & SMTP existence
* Block disposable & catch-all addresses
* Prevent invalid submissions (ideal alongside CAPTCHA)

## Installation ##

1. Upload to `/wp-content/plugins/zbounce-email-validator/`
2. Activate via “Plugins” screen
3. (Optional) Configure API key in Settings → ZBounce Email Validator
4. Forms auto-validate email fields on submit

## Frequently Asked Questions ##

### Do I need an API key? ###
You can run in demo mode (rate-limited) without a key. For production, get a key at https://zbounce.net.

### What forms are supported? ###
Contact Form 7, WooCommerce checkout, WordPress registration—works out of the box.

## Changelog ##

### 1.0.0 ###
* Initial release
* CF7, WooCommerce and WP registration integration
* Shortcode `[zb_email_validator]`

## Upgrade Notice ##

# 1.0.0 #
Initial release.
