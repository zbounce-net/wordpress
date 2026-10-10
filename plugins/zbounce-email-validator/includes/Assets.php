<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

class Assets {
    public static function init() {
        add_action( 'wp_enqueue_scripts',   [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    public static function enqueue_frontend_assets() {
        if ( ! current_user_can( 'manage_options' ) || ! Settings::is_connected() ) {
            return;
        }
        wp_register_style(
            'zbounce-email-validator',
            ZB_EVAL_URL . 'assets/css/validator.css',
            [],
            ZB_EVAL_VERSION
        );

        wp_register_script(
            'zbounce-email-validator',
            ZB_EVAL_URL . 'assets/js/validator.js',
            [ 'jquery' ],
            ZB_EVAL_VERSION,
            true
        );

        wp_localize_script( 'zbounce-email-validator', 'zbEmailValidator', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'zb_email_validator' ),
            'strings'  => [
                'invalid_email' => __( 'Invalid email format', 'zbounce-email-validator' ),
                'valid' => __( 'Valid', 'zbounce-email-validator' ),
                'invalid' => __( 'Invalid', 'zbounce-email-validator' ),
                'yes' => __( 'Yes', 'zbounce-email-validator' ),
                'no' => __( 'No', 'zbounce-email-validator' ),
                'unknown' => __( 'Not checked', 'zbounce-email-validator' ),
                'unreliable' => __( 'Yes (unreliable)', 'zbounce-email-validator' ),
                'accepts_all' => __( 'Server accepts all', 'zbounce-email-validator' ),
                'checking' => __( 'Checking…', 'zbounce-email-validator' ),
                'error'         => __( 'Validation error', 'zbounce-email-validator' ),
            ],
        ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_zbounce-email-validator' === $hook ) {
            wp_enqueue_style(
                'zb-email-validator-admin',
                ZB_EVAL_URL . 'assets/css/admin.css',
                [],
                ZB_EVAL_VERSION
            );
        }
    }
}
