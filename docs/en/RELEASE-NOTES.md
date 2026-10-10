# Release 1.2.5

Manual administrator checks and key balance now require a saved key and service agreement acceptance, independently of the automatic-form switch. Pausing automatic checks also skips cached rejections. Settings keep balance and tester visible; cache bypass requests a fresh check. All five bundled translations cover 105 messages.

Earlier 1.2.x work introduced configurable accepted results, WooCommerce Checkout Block support, privacy export/erasure, explicit service consent and balance/tester controls. Detailed version history is in the plugin's `readme.txt`.

Validation already completed on isolated Docker projects on pi: WordPress 7.1.3, WooCommerce 11.2.1, Contact Form 7 6.2.1, WPForms Lite 2.0.2.2 and Ninja Forms 3.15.5. 105 PHP assertions passed. Chrome covered manual fresh/cache/bypass checks with automatic forms disabled, balance, invalid nonce and anonymous rejection. Store API/browser checkout checks passed during 1.2.x validation. PHP 7.4 syntax checks passed; a complete legacy runtime matrix was not run. Gravity Forms was exercised through callbacks, without a licensed browser installation.

Release 1.2.5 removes developer error logging and its settings, keeps temporary variables out of the global scope, and registers bundled translations on init using the WordPress text-domain registry. WordPress 5.0–6.0 has a direct catalog fallback; that legacy runtime branch was not exercised on a complete old WordPress installation.

The final 1.2.5 installer passed Plugin Check 2.1.0 with zero errors and zero warnings on the isolated stand. All 105 PHP assertions and the Chrome settings tests passed again. Five bundled locale loading checks passed, and all plugin PHP files passed PHP 7.4 syntax checks. Translation extraction/compilation also completed without warnings. Approval by WordPress.org is not implied. Release tooling checks catalog completeness and reproducible archives. No live customer addresses, keys or WordPress database are included.
