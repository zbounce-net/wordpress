<?php
namespace ZbEmailValidator;

class Assets {
    public static function init() {
        add_action( 'wp_enqueue_scripts',   [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    public static function enqueue_frontend_assets() {
        wp_register_style(
            'zb-email-validator',
            ZB_EVAL_URL . 'assets/css/validator.css',
            [],
            ZB_EVAL_VERSION
        );
        wp_enqueue_style( 'zb-email-validator' );

        wp_register_script(
            'zb-email-validator',
            ZB_EVAL_URL . 'assets/js/validator.js',
            [ 'jquery' ],
            ZB_EVAL_VERSION,
            true
        );
        wp_enqueue_script( 'zb-email-validator' );

        wp_localize_script( 'zb-email-validator', 'zbEmailValidator', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'zb_email_validator' ),
            'is_pro'   => Settings::is_pro(),
            'strings'  => [
                'invalid_email' => __( 'Invalid email format – powered by ZBounce Email API', 'zb-email-validator' ),
                'error'         => __( 'Validation error – powered by ZBounce Email API', 'zb-email-validator' ),
            ],
        ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_zb-email-validator' === $hook ) {
            wp_enqueue_style(
                'zb-email-validator-admin',
                ZB_EVAL_URL . 'assets/css/admin.css',
                [],
                ZB_EVAL_VERSION
            );
        }
    }
}
