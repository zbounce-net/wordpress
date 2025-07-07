# ZBounce Email Validator 
Contributors: zbounce.net
Tags: email, validation, verification, zbounce, woocommerce, cf7, gravityforms, wpforms, ninja-forms
- Requires WordPress at least: 5.0
- Requires PHP: 7.0
- Tested up to: 6.8
- Stable tag: 1.1.0
- License: GPLv2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates with ZBounce Email Engine Platform to validate email addresses in your forms.

## Description
This plugin hooks into your forms to:

* Validate syntax and SMTP existence
* Block disposable & catch-all addresses
* Prevent invalid submissions (ideal alongside CAPTCHA)

Use the [zb_email_validator] shortcode anywhere to provide a standalone validation widget.

## Installation
1. Unzip and upload the folder to `/wp-content/plugins/zbounce-email-validator/`
2. Activate via the “Plugins” menu in WordPress
3. (Optional) Enter your API key under **Settings → ZBounce Email Validator**
4. All supported forms will now automatically validate email fields on submit

## Supported Forms
* Contact Form 7
* WooCommerce checkout
* WordPress registration
* Gravity Forms (Advanced Integrations)
* WPForms (Advanced Integrations)
* Ninja Forms (Advanced Integrations)

## Frequently Asked Questions
### Do I need an API key?
No. In demo mode (rate-limited) no key is required. For production use, get your key at https://zbounce.net.

### How do I enable or disable specific forms? ###
Head to **Settings → ZBounce Email Validator**, open “Advanced Integrations” and check or uncheck form IDs or integrations to control where validation applies.

## Changelog
#### 1.1.0 
* Added 24-hour caching of results
* Added Gravity Forms integration
* Added WPForms integration
* Added Ninja Forms integration

#### 1.0.0
* Initial release
* Contact Form 7, WooCommerce & WP registration support
* [zb_email_validator] shortcode

### Upgrade Notice
#### 1.1.0
Introduces caching and new form integrations, configurable via Advanced Integrations section. 
