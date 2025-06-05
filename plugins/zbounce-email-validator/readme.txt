=== ZBounce Email Validator ===
Contributors: zbounce.net
Tags: email, validation, verification, zbounce
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate with ZBounce email verification service to validate email addresses in your forms.

== Description ==

This plugin integrates your WordPress site with the ZBounce email verification API. It allows you to:

* Validate email addresses in real-time
* Block disposable email addresses
* Prevent invalid emails from being submitted
* Works with any form on your site
* Special integration with Contact Form 7

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/zbounce-email-validator` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your API key (optional) in Settings -> ZBounce Email Validator Settings
4. The plugin will automatically validate email fields

== Frequently Asked Questions ==

= Do I need an API key? =

You can use the plugin without an API key in demo mode, but it has rate limits. For production use, we recommend getting an API key from [ZBounce](https://zbounce.net).

= How can I test the plugin? =

After activation, go to any page with an email field and try to submit an invalid or disposable email. You should see an error message.

== Screenshots ==

1. Settings page for the plugin
2. Validation interface on the front end
3. Error message for disposable email

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin