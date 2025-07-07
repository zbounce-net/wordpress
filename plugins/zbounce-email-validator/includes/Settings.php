<?php
namespace ZbEmailValidator;

use WPCF7_ContactForm;

class Settings {
    const OPTION_KEY = 'zb_email_validator_settings';
    const DEFAULT_SETTINGS = [
        'api_key'        => '',
        'cache_duration' => 24,
        'enable_logging' => false,

        'cf7_forms'      => [],    // Contact Form 7 IDs
        'wc_enable'      => true,  // WooCommerce checkout
        'reg_enable'     => true,  // WP registration

        'gf_forms'       => [],    // Gravity Forms IDs
        'wpf_forms'      => [],    // WPForms IDs
        'nf_forms'       => [],    // Ninja Forms IDs
    ];

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings() {
        register_setting( 'zb_email_validator_settings', self::OPTION_KEY );

        // --- API Section ---
        add_settings_section(
            'api_settings',
            __( 'API Configuration', 'zb-email-validator' ),
            null,
            'zb-email-validator'
        );
        add_settings_field(
            'api_key',
            __( 'API Key (Pro Version)', 'zb-email-validator' ),
            [ __CLASS__, 'render_api_key_field' ],
            'zb-email-validator',
            'api_settings'
        );
        add_settings_field(
            'cache_duration',
            __( 'Cache Duration', 'zb-email-validator' ),
            [ __CLASS__, 'render_cache_duration_field' ],
            'zb-email-validator',
            'api_settings'
        );
        add_settings_field(
            'enable_logging',
            __( 'Enable Debug Logging', 'zb-email-validator' ),
            [ __CLASS__, 'render_logging_field' ],
            'zb-email-validator',
            'api_settings'
        );

        // --- Advanced Integrations ---
        add_settings_section(
            'advanced_settings',
            __( 'Advanced Integrations', 'zb-email-validator' ),
            null,
            'zb-email-validator'
        );
        // CF7
        add_settings_field(
            'cf7_forms',
            __( 'Contact Form 7 to Validate', 'zb-email-validator' ),
            [ __CLASS__, 'render_cf7_forms_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
        // WooCommerce
        add_settings_field(
            'wc_enable',
            __( 'Enable WooCommerce Checkout', 'zb-email-validator' ),
            [ __CLASS__, 'render_wc_enable_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
        // WP Registration
        add_settings_field(
            'reg_enable',
            __( 'Enable WP Registration', 'zb-email-validator' ),
            [ __CLASS__, 'render_reg_enable_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
        // Gravity Forms, WPForms, Ninja Forms
        add_settings_field(
            'gf_forms',
            __( 'Gravity Forms to Validate', 'zb-email-validator' ),
            [ __CLASS__, 'render_gf_forms_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
        add_settings_field(
            'wpf_forms',
            __( 'WPForms to Validate', 'zb-email-validator' ),
            [ __CLASS__, 'render_wpf_forms_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
        add_settings_field(
            'nf_forms',
            __( 'Ninja Forms to Validate', 'zb-email-validator' ),
            [ __CLASS__, 'render_nf_forms_field' ],
            'zb-email-validator',
            'advanced_settings'
        );
    }

    public static function render_api_key_field() {
        $s = self::get_settings();
        ?>
        <input type="password" name="<?= self::OPTION_KEY ?>[api_key]"
               value="<?= esc_attr( $s['api_key'] ) ?>" class="regular-text">
        <p class="description">
            <?php _e( 'Leave empty to use the free demo endpoint', 'zb-email-validator' ); ?>
        </p>
        <?php
    }

    public static function render_cache_duration_field() {
        $s = self::get_settings();
        ?>
        <input type="number" name="<?= self::OPTION_KEY ?>[cache_duration]"
               value="<?= esc_attr( $s['cache_duration'] ) ?>" min="1" step="1">
        <?php _e( 'hours', 'zb-email-validator' ); ?>
        <p class="description">
            <?php _e( 'Cache storage duration (hours)', 'zb-email-validator' ); ?>
        </p>
        <?php
    }

    public static function render_logging_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['enable_logging'] );
        ?>
        <label>
            <input type="checkbox" name="<?= self::OPTION_KEY ?>[enable_logging]"
                   value="1" <?= checked( $on, true, false ); ?>>
            <?php _e( 'Enable logging to PHP error_log', 'zb-email-validator' ); ?>
        </label>
        <p class="description">
            <?php _e( 'Debug logs are written to PHP error_log', 'zb-email-validator' ); ?>
        </p>
        <?php if ( $on ): ?>
            <div style="margin-top:10px;padding:10px;background:#fffbe6;border-left:4px solid #ffba00;">
                <strong>
                    <?php _e(
                        'To write logs into wp-content/debug.log, add these lines to your wp-config.php:',
                        'zb-email-validator'
                    ); ?>
                </strong>
                <pre style="background:#f6f8fa;padding:8px;border:1px solid #ddd;border-radius:4px;">
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
                </pre>
            </div>
        <?php endif;
    }

    public static function render_cf7_forms_field() {
        $s   = self::get_settings();
        $sel = (array) $s['cf7_forms'];

        if ( class_exists( WPCF7_ContactForm::class ) ) {

            $forms = WPCF7_ContactForm::find( [ 'status' => 'publish' ] );


            if ( empty( $sel ) ) {
                foreach ( $forms as $f ) {
                    $sel[] = intval( $f->id() );
                }
            }

            foreach ( $forms as $f ) {
                printf(
                    '<label><input type="checkbox" name="%1$s[cf7_forms][]" value="%2$d"%3$s> %4$s (ID:%2$d)</label><br>',
                    esc_attr( self::OPTION_KEY ),
                    intval( $f->id() ),
                    in_array( $f->id(), $sel, true ) ? ' checked' : '',
                    esc_html( $f->title() )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'Contact Form 7 not detected.', 'zb-email-validator' ) . '</p>';
        }

        echo '<p class="description">'
            . esc_html__( 'Leave all checked to validate all forms. Uncheck to disable validation for a specific form.', 'zb-email-validator' )
            . '</p>';
    }

    public static function render_wc_enable_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['wc_enable'] );
        ?>
        <label>
            <input type="checkbox" name="<?= self::OPTION_KEY ?>[wc_enable]"
                   value="1" <?= checked( $on, true, false ); ?>>
            <?php _e( 'Enable WooCommerce checkout validation', 'zb-email-validator' ); ?>
        </label>
        <?php
    }

    public static function render_reg_enable_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['reg_enable'] );
        ?>
        <label>
            <input type="checkbox" name="<?= self::OPTION_KEY ?>[reg_enable]"
                   value="1" <?= checked( $on, true, false ); ?>>
            <?php _e( 'Enable WP registration validation', 'zb-email-validator' ); ?>
        </label>
        <?php
    }

    public static function get_settings(): array {
        return wp_parse_args( get_option( self::OPTION_KEY, [] ), self::DEFAULT_SETTINGS );
    }

    public static function render_gf_forms_field() {
        $s   = self::get_settings();
        $sel = (array) $s['gf_forms'];
        if ( class_exists( 'GFAPI' ) ) {
            $forms = GFAPI::get_forms();
            foreach ( $forms as $f ) {
                printf(
                    '<label><input type="checkbox" name="%1$s[gf_forms][]" value="%2$d"%3$s> %4$s (ID:%2$d)</label><br>',
                    esc_attr( self::OPTION_KEY ),
                    intval( $f['id'] ),
                    in_array( $f['id'], $sel, true ) ? ' checked' : '',
                    esc_html( $f['title'] )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'Gravity Forms not detected.', 'zb-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select Gravity Forms by ID', 'zb-email-validator' ) . '</p>';
    }

    public static function render_wpf_forms_field() {
        $s     = self::get_settings();
        $sel   = (array) $s['wpf_forms'];
        $posts = get_posts( [
            'post_type'      => 'wpforms',
            'posts_per_page' => -1,
        ] );
        if ( $posts ) {
            foreach ( $posts as $p ) {
                printf(
                    '<label><input type="checkbox" name="%1$s[wpf_forms][]" value="%2$d"%3$s> %4$s (ID:%2$d)</label><br>',
                    esc_attr( self::OPTION_KEY ),
                    intval( $p->ID ),
                    in_array( $p->ID, $sel, true ) ? ' checked' : '',
                    esc_html( $p->post_title )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'WPForms not detected.', 'zb-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select WPForms by ID', 'zb-email-validator' ) . '</p>';
    }

    public static function is_logging_enabled(): bool {
        $s = self::get_settings();
        return ! empty( $s['enable_logging'] );
    }

    public static function is_pro(): bool {
        $s = self::get_settings();
        return (bool) trim( $s['api_key'] ?? '' );
    }

    public static function get_cache_duration(): int {
        $s = self::get_settings();
        return intval( $s['cache_duration'] ) * HOUR_IN_SECONDS;
    }

    public static function render_nf_forms_field() {
        $s     = self::get_settings();
        $sel   = (array) $s['nf_forms'];
        $posts = get_posts( [
            'post_type'      => 'nf_form',
            'posts_per_page' => -1,
        ] );
        if ( $posts ) {
            foreach ( $posts as $p ) {
                printf(
                    '<label><input type="checkbox" name="%1$s[nf_forms][]" value="%2$d"%3$s> %4$s (ID:%2$d)</label><br>',
                    esc_attr( self::OPTION_KEY ),
                    intval( $p->ID ),
                    in_array( $p->ID, $sel, true ) ? ' checked' : '',
                    esc_html( $p->post_title )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'Ninja Forms not detected.', 'zb-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select Ninja Forms by ID', 'zb-email-validator' ) . '</p>';
    }
}
