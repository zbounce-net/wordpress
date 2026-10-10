<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

/** Register bundled catalogs without loading translations before init. */
class Translations {
    public static function register(): void {
        global $wp_textdomain_registry;

        // Let WordPress lazily choose language packs or the bundled fallback.
        if ( isset( $wp_textdomain_registry ) ) {
            $wp_textdomain_registry->set_custom_path( 'zbounce-email-validator', ZB_EVAL_PATH . 'languages' );
            return;
        }

        // WordPress 5.0–6.0 predates the text-domain registry.
        $locale = determine_locale();
        $filename = 'zbounce-email-validator-' . $locale . '.mo';
        $language_pack = WP_LANG_DIR . '/plugins/' . $filename;
        $bundled = ZB_EVAL_PATH . 'languages/' . $filename;
        if ( is_readable( $language_pack ) ) {
            load_textdomain( 'zbounce-email-validator', $language_pack );
        } elseif ( is_readable( $bundled ) ) {
            load_textdomain( 'zbounce-email-validator', $bundled );
        }
    }
}
