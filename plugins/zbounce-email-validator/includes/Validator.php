<?php
namespace ZbEmailValidator;

use ZbEmailValidator\Logger;

class Validator {
    public static function init() {
        add_shortcode( 'zb_email_validator',        [ __CLASS__, 'render_validator' ] );
        add_filter( 'wpcf7_validate_email',         [ __CLASS__, 'validate_cf7_email' ], 20, 2 );
        add_filter( 'wpcf7_validate_email*',        [ __CLASS__, 'validate_cf7_email' ], 20, 2 );
        add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'validate_woocommerce_email' ], 10, 2 );
        add_filter( 'registration_errors',          [ __CLASS__, 'validate_registration_email' ], 10, 3 );
    }

    public static function render_validator( $atts ) {
        wp_enqueue_style(  'zb-email-validator' );
        wp_enqueue_script( 'zb-email-validator' );

        ob_start();
        ?>
        <div class="zb-email-validator">
            <div class="zb-input-group">
                <input type="email" class="zb-email-input" placeholder="Enter email address">
                <button class="zb-validate-btn">Validate</button>
            </div>

            <div class="zb-status-value" style="display:none;"></div>

            <div class="zb-validation-results" style="display:none;">
                <h3>Results for: <span class="zb-result-email"></span></h3>
                <div class="zb-result-item">
                    <span class="zb-result-label">Email Format:</span>
                    <span class="zb-result-value zb-validity-badge"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Mailbox Exists:</span>
                    <span class="zb-result-value zb-exists-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Disposable:</span>
                    <span class="zb-result-value zb-disposable-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Accepts All:</span>
                    <span class="zb-result-value zb-acceptall-status"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Выполняет синхронную проверку и возвращает полный массив результатов.
     */
    private static function run_sync_validation( string $email ): array {
        Logger::log( "Validator::run_sync_validation email={$email}" );
        $data = ApiClient::sync_verify( $email ) ?: [];
        Logger::log( "Validator result: " . print_r( $data, true ) );
        return wp_parse_args( $data, [
            'email'           => $email,
            'valid'           => false,
            'exists'          => null,
            'disposable'      => false,
            'accept_all'      => false,
            'permanent_error' => false,
            'error_category'  => '',
        ] );
    }

    /**
     * Валидация поля Contact Form 7.
     */
    public static function validate_cf7_email( $result, $tag ) {
        if ( $tag->basetype !== 'email' ) {
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
            $result->invalidate( $tag, 'Invalid email format' );
        }
        elseif ( $v['disposable'] ) {
            Logger::log( "CF7 disposable email" );
            $result->invalidate( $tag, 'Disposable emails are not allowed' );
        }
        elseif ( $v['exists'] === false ) {
            Logger::log( "CF7 email does not exist" );
            $result->invalidate( $tag, 'Email address does not exist' );
        }

        return $result;
    }

    /**
     * Валидация WooCommerce checkout.
     */
    public static function validate_woocommerce_email( $data, $errors ) {
        $email = sanitize_email( $data['billing_email'] ?? '' );
        Logger::log( "WC validate billing_email={$email}" );
        if ( ! $email ) {
            return;
        }

        $v = self::run_sync_validation( $email );

        if ( ! $v['valid'] ) {
            Logger::log( "WC invalid format" );
            $errors->add( 'validation', 'Invalid email format' );
        }
        elseif ( $v['disposable'] ) {
            Logger::log( "WC disposable email" );
            $errors->add( 'validation', 'Disposable emails are not allowed' );
        }
        elseif ( $v['exists'] === false ) {
            Logger::log( "WC email does not exist" );
            $errors->add( 'validation', 'Email address does not exist' );
        }
    }

    /**
     * Валидация WordPress регистрации.
     */
    public static function validate_registration_email( $errors, $user_login, $user_email ) {
        $email = sanitize_email( $user_email );
        Logger::log( "WP registration validate email={$email}" );
        if ( ! $email ) {
            return $errors;
        }

        $v = self::run_sync_validation( $email );

        if ( ! $v['valid'] ) {
            Logger::log( "Reg invalid format" );
            $errors->add( 'invalid_email', 'Invalid email format' );
        }
        elseif ( $v['disposable'] ) {
            Logger::log( "Reg disposable email" );
            $errors->add( 'disposable_email', 'Disposable emails are not allowed' );
        }
        elseif ( $v['exists'] === false ) {
            Logger::log( "Reg email does not exist" );
            $errors->add( 'email_not_exists', 'Email address does not exist' );
        }

        return $errors;
    }
}
