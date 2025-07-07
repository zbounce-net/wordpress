<?php
namespace ZbEmailValidator;

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
            add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'validate_woocommerce_email' ], 10, 2 );
        }
        if ( ! empty( $s['reg_enable'] ) ) {
            add_filter( 'registration_errors', [ __CLASS__, 'validate_registration_email' ], 10, 3 );
        }
    }

    public static function render_validator( $atts ) {
        wp_enqueue_style(  'zb-email-validator' );
        wp_enqueue_script( 'zb-email-validator' );

        ob_start();
        ?>
        <div class="zb-email-validator">
            <!-- UI markup не изменился -->
            <div class="zb-input-group">
                <input type="email" class="zb-email-input"
                       placeholder="<?php echo esc_attr( __( 'Enter email address', 'zb-email-validator' ) ); ?>">
                <button class="zb-validate-btn"><?php echo esc_html( __( 'Validate', 'zb-email-validator' ) ); ?></button>
            </div>
            <div class="zb-status-value" style="display:none;"></div>
            <div class="zb-validation-results" style="display:none;">
                <h3>
                    <?php printf(
                        __( 'Results for: %s', 'zb-email-validator' ),
                        '<span class="zb-result-email"></span>'
                    ); ?>
                </h3>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php _e( 'Email Format:', 'zb-email-validator' ); ?></span>
                    <span class="zb-result-value zb-validity-badge"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php _e( 'Mailbox Exists:', 'zb-email-validator' ); ?></span>
                    <span class="zb-result-value zb-exists-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php _e( 'Disposable:', 'zb-email-validator' ); ?></span>
                    <span class="zb-result-value zb-disposable-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label"><?php _e( 'Accepts All:', 'zb-email-validator' ); ?></span>
                    <span class="zb-result-value zb-acceptall-status"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function run_sync_validation( string $email ): array {
        Logger::log( "Validator::run_sync_validation email={$email}" );

        $defaults = [
            'email'           => $email,
            'valid'           => false,
            'exists'          => null,
            'disposable'      => false,
            'accept_all'      => false,
            'permanent_error' => false,
            'error_category'  => '',
        ];

        $cache_key = md5( $email );
        $cached    = ApiClient::get_cached_result( $cache_key );
        if ( $cached ) {
            Logger::log( "Cache HIT for {$email}" );
            return wp_parse_args( $cached, $defaults );
        }
        Logger::log( "Cache MISS for {$email}" );

        $data = ApiClient::sync_verify( $email ) ?: [];
        Logger::log( "Validator result: " . print_r( $data, true ) );

        ApiClient::cache_result( $cache_key, $data );
        Logger::log( "Result cached under {$cache_key}" );

        return wp_parse_args( $data, $defaults );
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

        $email = sanitize_email( $_POST[ $tag->name ] ?? '' );
        Logger::log( "CF7 validate email={$email}" );
        if ( ! $email ) {
            return $result;
        }

        $v = self::run_sync_validation( $email );
        if ( ! $v['valid'] ) {
            Logger::log( "CF7 invalid format" );
            $result->invalidate( $tag, __( 'Invalid email format', 'zb-email-validator' ) );
        } elseif ( $v['disposable'] ) {
            Logger::log( "CF7 disposable email" );
            $result->invalidate( $tag, __( 'Disposable emails are not allowed', 'zb-email-validator' ) );
        } elseif ( $v['exists'] === false ) {
            Logger::log( "CF7 email does not exist" );
            $result->invalidate( $tag, __( 'Email address does not exist', 'zb-email-validator' ) );
        }

        return $result;
    }

    public static function validate_woocommerce_email( $data, $errors ) {
        $s = Settings::get_settings();
        if ( empty( $s['wc_enable'] ) ) {
            return;
        }

        $email = sanitize_email( $data['billing_email'] ?? '' );
        Logger::log( "WC validate billing_email={$email}" );
        if ( ! $email ) {
            return;
        }

        $v = self::run_sync_validation( $email );
        if ( ! $v['valid'] ) {
            Logger::log( "WC invalid format" );
            $errors->add( 'validation', __( 'Invalid email format', 'zb-email-validator' ) );
        } elseif ( $v['disposable'] ) {
            Logger::log( "WC disposable email" );
            $errors->add( 'validation', __( 'Disposable emails are not allowed', 'zb-email-validator' ) );
        } elseif ( $v['exists'] === false ) {
            Logger::log( "WC email does not exist" );
            $errors->add( 'validation', __( 'Email address does not exist', 'zb-email-validator' ) );
        }
    }

    public static function validate_registration_email( $errors, $user_login, $user_email ) {
        $s = Settings::get_settings();
        if ( empty( $s['reg_enable'] ) ) {
            return $errors;
        }

        $email = sanitize_email( $user_email );
        Logger::log( "WP registration validate email={$email}" );
        if ( ! $email ) {
            return $errors;
        }

        $v = self::run_sync_validation( $email );
        if ( ! $v['valid'] ) {
            Logger::log( "Reg invalid format" );
            $errors->add( 'invalid_email', __( 'Invalid email format', 'zb-email-validator' ) );
        } elseif ( $v['disposable'] ) {
            Logger::log( "Reg disposable email" );
            $errors->add( 'disposable_email', __( 'Disposable emails are not allowed', 'zb-email-validator' ) );
        } elseif ( $v['exists'] === false ) {
            Logger::log( "Reg email does not exist" );
            $errors->add( 'email_not_exists', __( 'Email address does not exist', 'zb-email-validator' ) );
        }

        return $errors;
    }
}
