<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

use ZbEmailValidator\Settings;
use ZbEmailValidator\Logger;
use WPCF7_ContactForm;

class Validator {

    public static function init() {
        $s = Settings::get_settings();

        add_shortcode( 'zb_email_validator', [ __CLASS__, 'render_validator' ] );

        if ( class_exists( WPCF7_ContactForm::class ) ) {
            add_filter( 'wpcf7_validate_email',  [ __CLASS__, 'validate_cf7_email' ], 20, 2 );
            add_filter( 'wpcf7_validate_email*', [ __CLASS__, 'validate_cf7_email' ], 20, 2 );
        }

        if ( ! empty( $s['wc_enable'] ) && class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ __CLASS__, 'validate_store_api_email' ], 10, 2 );
            add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'validate_woocommerce_email' ], 10, 2 );
        }
        if ( ! empty( $s['reg_enable'] ) ) {
            add_filter( 'registration_errors', [ __CLASS__, 'validate_registration_email' ], 10, 3 );
        }
    }

    public static function render_validator( $atts ) {
        $ready = Settings::is_api_configured();
        $settings_preview = is_admin() && ! empty( $atts['settings_preview'] );
        if ( ! current_user_can( 'manage_options' ) || ( ! $ready && ! $settings_preview ) ) {
            return '';
        }
        wp_enqueue_style(  'zbounce-email-validator' );
        wp_enqueue_script( 'zbounce-email-validator' );

        static $instance = 0;
        $zbounce_input_id = 'zb-email-' . ++$instance;
        ob_start();
        ?>
        <div class="zb-email-validator">
            <div class="zb-input-group">
                <label class="screen-reader-text" for="<?php echo esc_attr( $zbounce_input_id ); ?>"><?php esc_html_e( 'Enter email address', 'zbounce-email-validator' ); ?></label>
                <input id="<?php echo esc_attr( $zbounce_input_id ); ?>" type="email" class="zb-email-input" <?php disabled( ! $ready ); ?>
                       placeholder="<?php echo esc_attr( __( 'Enter email address', 'zbounce-email-validator' ) ); ?>">
                <button type="button" class="zb-validate-btn" <?php disabled( ! $ready ); ?>><?php echo esc_html( __( 'Validate', 'zbounce-email-validator' ) ); ?></button>
            </div>
            <label><input type="checkbox" class="zb-use-cache" checked <?php disabled( ! $ready ); ?>> <?php esc_html_e( 'Use cached results', 'zbounce-email-validator' ); ?></label>
            <p class="description"><?php esc_html_e( 'Disable cache to request a fresh API check using verification credits.', 'zbounce-email-validator' ); ?></p>
            <div class="zb-status-value" role="status" aria-live="polite" style="display:none;"></div>
            <div class="zb-validation-results" style="display:none;">
                <p><strong><?php esc_html_e( 'Verification result:', 'zbounce-email-validator' ); ?></strong> <span class="zb-policy-status"></span></p>
                <p><strong><?php esc_html_e( 'Result under your form policy:', 'zbounce-email-validator' ); ?></strong> <span class="zb-policy-decision"></span></p>
                <p><strong><?php esc_html_e( 'Result source:', 'zbounce-email-validator' ); ?></strong> <span class="zb-result-source"></span></p>
                <h3>
                    <?php printf(
                        /* translators: %s: email address displayed in a text-only span. */
                        esc_html__( 'Results for: %s', 'zbounce-email-validator' ),
                        '<span class="zb-result-email"></span>'
                    ); ?>
                </h3>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php esc_html_e( 'Email Format:', 'zbounce-email-validator' ); ?></span>
                    <span class="zb-result-value zb-validity-badge"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php esc_html_e( 'Mailbox Exists:', 'zbounce-email-validator' ); ?></span>
                    <span class="zb-result-value zb-exists-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php esc_html_e( 'Disposable:', 'zbounce-email-validator' ); ?></span>
                    <span class="zb-result-value zb-disposable-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php esc_html_e( 'Accepts All:', 'zbounce-email-validator' ); ?></span>
                    <span class="zb-result-value zb-acceptall-status"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function run_sync_validation( string $email ): array {
        $email = sanitize_email( $email );

        $defaults = [
            'email'           => $email,
            'valid'           => (bool) is_email( $email ),
            'exists'          => null,
            'disposable'      => false,
            'accept_all'      => false,
            'permanent_error' => false,
            'error_category'  => '',
            'verification_status' => 'unavailable',
        ];

        if ( ! Settings::is_verification_enabled() || ! is_email( $email ) ) {
            return $defaults;
        }
        $cache_key = md5( $email );
        $cached    = ApiClient::get_cached_result( $cache_key );
        if ( $cached ) {
            return wp_parse_args( $cached, $defaults );
        }

        $data = ApiClient::sync_verify( $email ) ?: [];

        ApiClient::cache_result( $cache_key, $data );

        return wp_parse_args( $data, $defaults );
    }

    public static function validation_error( string $email ): string {
        if ( ! Settings::is_verification_enabled() || '' === $email ) { return ''; }
        return self::result_error( self::run_sync_validation( $email ) );
    }

    public static function result_error( array $result ): string {
        $status = ! $result['valid'] ? 'invalid' : $result['verification_status'];
        $settings = Settings::get_settings();
        if ( in_array( $status, $settings['allowed_statuses'], true ) ) { return ''; }
        if ( 'invalid' === $status ) { return __( 'Invalid email format', 'zbounce-email-validator' ); }
        if ( 'disposable' === $status ) { return __( 'Disposable emails are not allowed', 'zbounce-email-validator' ); }
        if ( in_array( $status, [ 'mailbox_not_found', 'no_mx' ], true ) ) { return __( 'Email address does not exist', 'zbounce-email-validator' ); }
        if ( 'risky' === $status ) { return __( 'Accept-all email domains are not allowed', 'zbounce-email-validator' ); }
        if ( 'unavailable' === $status ) { return __( 'Email verification is temporarily unavailable. Please try again.', 'zbounce-email-validator' ); }
        return __( 'The mailbox could not be confirmed. Please use another email address.', 'zbounce-email-validator' );
    }

    public static function validate_store_api_email( $order, $request ) {
        // PUT/PATCH update checkout drafts; only placing an order spends credits.
        if ( ! $request instanceof \WP_REST_Request || 'POST' !== $request->get_method() || empty( Settings::get_settings()['wc_enable'] ) ) { return; }
        if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) { return; }
        $message = self::validation_error( sanitize_email( $order->get_billing_email() ) );
        if ( '' !== $message ) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'zbounce_email_rejected', esc_html( $message ), 400 );
        }
    }

    public static function validate_cf7_email( $result, $tag ) {
        if ( $tag->basetype !== 'email' ) {
            return $result;
        }

        $s    = Settings::get_settings();
        $form = WPCF7_ContactForm::get_current();
        $fid  = $form ? $form->id() : null;
        if ( ! empty( $s['cf7_forms'] ) && $fid && ! in_array( $fid, (array) $s['cf7_forms'], true ) ) {
            return $result;
        }

        // Contact Form 7 performs nonce/submission checks before this validation filter.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $email = sanitize_email( isset( $_POST[ $tag->name ] ) && is_string( $_POST[ $tag->name ] ) ? wp_unslash( $_POST[ $tag->name ] ) : '' );
        if ( ! $email ) {
            return $result;
        }

        $message = self::validation_error( $email );
        if ( '' !== $message ) { $result->invalidate( $tag, $message ); }

        return $result;
    }

    public static function validate_woocommerce_email( $data, $errors ) {
        $s = Settings::get_settings();
        if ( empty( $s['wc_enable'] ) ) {
            return;
        }

        $email = sanitize_email( $data['billing_email'] ?? '' );
        if ( ! $email ) {
            return;
        }

        $message = self::validation_error( $email );
        if ( '' !== $message ) { $errors->add( 'validation', $message ); }

    }

    public static function validate_registration_email( $errors, $user_login, $user_email ) {
        $s = Settings::get_settings();
        if ( empty( $s['reg_enable'] ) ) {
            return $errors;
        }

        $email = sanitize_email( $user_email );
        if ( ! $email ) {
            return $errors;
        }

        $message = self::validation_error( $email );
        if ( '' !== $message ) { $errors->add( 'zbounce_email_rejected', $message ); }

        return $errors;
    }
}
