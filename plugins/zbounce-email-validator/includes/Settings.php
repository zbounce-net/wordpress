<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

use WPCF7_ContactForm;

class Settings {
    const OPTION_KEY = 'zb_email_validator_settings';
    const DEFAULT_SETTINGS = [
        'api_key'        => '',
        'cache_duration' => 24,
        'allowed_statuses' => [ 'valid', 'risky', 'unknown', 'unavailable' ],
        'enable_logging' => false,
        'service_enabled'=> false,
        'license_accepted' => false,
        'license_accepted_at' => 0,
        'license_accepted_by' => 0,

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
        register_setting( 'zb_email_validator_settings', self::OPTION_KEY, [ 'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ] ] );

        // --- API Section ---
        add_settings_section(
            'api_settings',
            __( 'API Configuration', 'zbounce-email-validator' ),
            null,
            'zbounce-email-validator'
        );
        add_settings_field(
            'api_key',
            __( 'Purchased ZBounce API key', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_api_key_field' ],
            'zbounce-email-validator',
            'api_settings'
        );
        add_settings_field(
            'cache_duration',
            __( 'Cache Duration', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_cache_duration_field' ],
            'zbounce-email-validator',
            'api_settings'
        );
        add_settings_field(
            'enable_logging',
            __( 'Enable Debug Logging', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_logging_field' ],
            'zbounce-email-validator',
            'api_settings'
        );

        add_settings_field(
            'service_enabled',
            __( 'External verification', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_service_enabled_field' ],
            'zbounce-email-validator',
            'api_settings'
        );

        add_settings_field( 'allowed_statuses', __( 'Accepted verification results', 'zbounce-email-validator' ), [ __CLASS__, 'render_allowed_statuses_field' ], 'zbounce-email-validator', 'api_settings' );

        add_settings_field( 'license_accepted', __( 'License agreement', 'zbounce-email-validator' ), [ __CLASS__, 'render_license_field' ], 'zbounce-email-validator', 'api_settings' );
        // --- Advanced Integrations ---
        add_settings_section(
            'advanced_settings',
            __( 'Advanced Integrations', 'zbounce-email-validator' ),
            null,
            'zbounce-email-validator'
        );
        // CF7
        add_settings_field(
            'cf7_forms',
            __( 'Contact Form 7 to Validate', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_cf7_forms_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
        // WooCommerce
        add_settings_field(
            'wc_enable',
            __( 'Enable WooCommerce Checkout', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_wc_enable_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
        // WP Registration
        add_settings_field(
            'reg_enable',
            __( 'Enable WP Registration', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_reg_enable_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
        // Gravity Forms, WPForms, Ninja Forms
        add_settings_field(
            'gf_forms',
            __( 'Gravity Forms to Validate', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_gf_forms_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
        add_settings_field(
            'wpf_forms',
            __( 'WPForms to Validate', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_wpf_forms_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
        add_settings_field(
            'nf_forms',
            __( 'Ninja Forms to Validate', 'zbounce-email-validator' ),
            [ __CLASS__, 'render_nf_forms_field' ],
            'zbounce-email-validator',
            'advanced_settings'
        );
    }

    public static function render_api_key_field() {
        $s = self::get_settings();
        ?>
        <input type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_key]"
               value="<?php echo esc_attr( $s['api_key'] ); ?>" class="regular-text" autocomplete="off">
        <p class="description">
            <?php esc_html_e( 'Buy verification credits at zbounce.net/pricing.html and paste the API key received after payment.', 'zbounce-email-validator' ); ?>
        </p>
        <?php
    }

    public static function render_cache_duration_field() {
        $s = self::get_settings();
        ?>
        <input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cache_duration]"
               value="<?php echo esc_attr( $s['cache_duration'] ); ?>" min="1" max="168" step="1">
        <?php esc_html_e( 'hours', 'zbounce-email-validator' ); ?>
        <p class="description">
            <?php esc_html_e( 'Cache storage duration (hours)', 'zbounce-email-validator' ); ?>
        </p>
        <?php
    }

    public static function render_logging_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['enable_logging'] );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_logging]"
                   value="1" <?php checked( $on, true ); ?>>
            <?php esc_html_e( 'Enable logging to PHP error_log', 'zbounce-email-validator' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'Debug logs are written to PHP error_log', 'zbounce-email-validator' ); ?>
        </p>
        <?php if ( $on ): ?>
            <div style="margin-top:10px;padding:10px;background:#fffbe6;border-left:4px solid #ffba00;">
                <strong>
                    <?php esc_html_e(
                        'To write logs into wp-content/debug.log, add these lines to your wp-config.php:',
                        'zbounce-email-validator'
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
            echo '<p>' . esc_html__( 'Contact Form 7 not detected.', 'zbounce-email-validator' ) . '</p>';
        }

        echo '<p class="description">'
            . esc_html__( 'Select the forms to validate. An empty selection validates all Contact Form 7 forms; disable external verification to stop all checks.', 'zbounce-email-validator' )
            . '</p>';
    }

    public static function render_wc_enable_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['wc_enable'] );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wc_enable]"
                   value="1" <?php checked( $on, true ); ?>>
            <?php esc_html_e( 'Enable WooCommerce checkout validation', 'zbounce-email-validator' ); ?>
        </label>
        <?php
    }

    public static function render_reg_enable_field() {
        $s  = self::get_settings();
        $on = ! empty( $s['reg_enable'] );
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[reg_enable]"
                   value="1" <?php checked( $on, true ); ?>>
            <?php esc_html_e( 'Enable WP registration validation', 'zbounce-email-validator' ); ?>
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
            echo '<p>' . esc_html__( 'Gravity Forms not detected.', 'zbounce-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select Gravity Forms by ID', 'zbounce-email-validator' ) . '</p>';
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
            echo '<p>' . esc_html__( 'WPForms not detected.', 'zbounce-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select WPForms by ID', 'zbounce-email-validator' ) . '</p>';
    }

    public static function is_logging_enabled(): bool {
        $s = self::get_settings();
        return ! empty( $s['enable_logging'] );
    }

    public static function is_connected(): bool {
        $s = self::get_settings();
        return ! empty( $s['license_accepted'] ) && ! empty( $s['service_enabled'] ) && '' !== trim( $s['api_key'] );
    }

    public static function sanitize_settings( $input ): array {
        $input = is_array( $input ) ? $input : [];
        $clean = self::DEFAULT_SETTINGS;
        $clean['api_key'] = isset( $input['api_key'] ) && is_string( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
        $clean['cache_duration'] = max( 1, min( 168, absint( $input['cache_duration'] ?? 24 ) ) );
        foreach ( [ 'service_enabled', 'enable_logging', 'wc_enable', 'reg_enable' ] as $field ) {
            $clean[ $field ] = ! empty( $input[ $field ] );
        }
        foreach ( [ 'cf7_forms', 'gf_forms', 'wpf_forms', 'nf_forms' ] as $field ) {
            $clean[ $field ] = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $input[ $field ] ?? [] ) ) ) ) );
        }
        if ( array_key_exists( 'allowed_statuses', $input ) ) {
            $values = is_array( $input['allowed_statuses'] ) ? $input['allowed_statuses'] : [];
            $clean['allowed_statuses'] = array_values( array_intersect( array_keys( self::status_labels() ), array_filter( $values, 'is_string' ) ) );
        }
        $previous = self::get_settings();
        $clean['license_accepted'] = isset( $input['license_accepted'] ) && in_array( $input['license_accepted'], [ '1', 1, true ], true );
        if ( $clean['license_accepted'] ) {
            $clean['license_accepted_at'] = ! empty( $previous['license_accepted_at'] ) && ! empty( $previous['license_accepted'] ) ? absint( $previous['license_accepted_at'] ) : time();
            $clean['license_accepted_by'] = ! empty( $previous['license_accepted_at'] ) && ! empty( $previous['license_accepted'] ) ? absint( $previous['license_accepted_by'] ) : get_current_user_id();
        } elseif ( $clean['service_enabled'] ) {
            $clean['service_enabled'] = false;
            add_settings_error( self::OPTION_KEY, 'zb_license_required', __( 'Accept the ZBounce service license agreement before enabling verification.', 'zbounce-email-validator' ) );
        }
        if ( $clean['service_enabled'] && '' === $clean['api_key'] ) {
            $clean['service_enabled'] = false;
            add_settings_error( self::OPTION_KEY, 'zb_missing_key', __( 'Enter your purchased API key before enabling external verification.', 'zbounce-email-validator' ) );
        }
        return $clean;
    }

    public static function status_labels(): array {
        return [
            'valid' => __( 'Valid — mailbox confirmed', 'zbounce-email-validator' ),
            'risky' => __( 'Risky — accept-all domain', 'zbounce-email-validator' ),
            'disposable' => __( 'Disposable email', 'zbounce-email-validator' ),
            'invalid' => __( 'Invalid email format', 'zbounce-email-validator' ),
            'no_mx' => __( 'Domain does not accept mail', 'zbounce-email-validator' ),
            'mailbox_not_found' => __( 'Mailbox does not exist', 'zbounce-email-validator' ),
            'unknown' => __( 'Unknown — mailbox could not be confirmed', 'zbounce-email-validator' ),
            'unavailable' => __( 'API unavailable — connection, key or balance problem', 'zbounce-email-validator' ),
        ];
    }

    public static function render_allowed_statuses_field() {
        $settings = self::get_settings();
        // The hidden marker permits saving an intentionally empty selection.
        printf( '<input type="hidden" name="%s[allowed_statuses][]" value="">', esc_attr( self::OPTION_KEY ) );
        foreach ( self::status_labels() as $status => $label ) {
            printf( '<label><input type="checkbox" name="%1$s[allowed_statuses][]" value="%2$s" %3$s> %4$s</label><br>', esc_attr( self::OPTION_KEY ), esc_attr( $status ), checked( in_array( $status, $settings['allowed_statuses'], true ), true, false ), esc_html( $label ) );
        }
        echo '<p class="description">' . esc_html__( 'Checked results pass; unchecked results reject the submission. Rules apply to every enabled integration, including cached results. Native form email-format validation still applies. API availability is separate from an unknown mailbox result.', 'zbounce-email-validator' ) . '</p>';
    }

    public static function render_license_field() {
        $settings = self::get_settings();
        printf( '<label><input type="checkbox" name="%s[license_accepted]" value="1" %s> %s</label> ', esc_attr( self::OPTION_KEY ), checked( ! empty( $settings['license_accepted'] ), true, false ), esc_html__( 'I accept the ZBounce service license agreement.', 'zbounce-email-validator' ) );
        printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://zbounce.net/license.html' ), esc_html__( 'Read the license agreement', 'zbounce-email-validator' ) );
        echo '<p class="description">' . esc_html__( 'Acceptance is required before using ZBounce verification. Removing acceptance stops all API requests.', 'zbounce-email-validator' ) . '</p>';
    }

    public static function render_balance_field() {
        if ( ! self::is_connected() ) {
            /* translators: %s: number of remaining verification credits. */
            echo '<strong class="zb-key-balance">' . esc_html( sprintf( __( 'Remaining credits: %s', 'zbounce-email-validator' ), '—' ) ) . '</strong><p>' . esc_html__( 'Accept the agreement, save your key and enable verification to see the balance.', 'zbounce-email-validator' ) . '</p>';
            return;
        }
        $balance = ApiClient::get_balance();
        if ( isset( $balance['remaining'] ) ) {
            /* translators: %s: number of remaining verification credits. */
            echo '<strong class="zb-key-balance">' . esc_html( sprintf( __( 'Remaining credits: %s', 'zbounce-email-validator' ), number_format_i18n( $balance['remaining'] ) ) ) . '</strong>';
        } else {
            echo '<span class="zb-key-balance">' . esc_html__( 'Balance unavailable. Check your key and connection.', 'zbounce-email-validator' ) . '</span>';
        }
    }

    public static function render_service_enabled_field() {
        $s = self::get_settings();
        ?>
        <label><input type="checkbox" id="zb-service-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[service_enabled]" value="1" <?php checked( ! empty( $s['service_enabled'] ) ); ?>>
        <?php esc_html_e( 'Enable checks and send submitted email addresses to ZBounce.', 'zbounce-email-validator' ); ?></label>
        <p class="description"><?php esc_html_e( 'Checks use your purchased credits. No requests are sent until you enable this option and enter a key.', 'zbounce-email-validator' ); ?></p>
        <?php
    }

    public static function get_cache_duration(): int {
        $s = self::get_settings();
        return intval( $s['cache_duration'] ) * HOUR_IN_SECONDS;
    }

    public static function render_nf_forms_field() {
        $s = self::get_settings();
        if ( function_exists( 'Ninja_Forms' ) ) {
            foreach ( Ninja_Forms()->form()->get_forms() as $form ) {
                $id = absint( $form->get_id() );
                printf(
                    '<label><input type="checkbox" name="%1$s[nf_forms][]" value="%2$d"%3$s> %4$s (ID:%2$d)</label><br>',
                    esc_attr( self::OPTION_KEY ),
                    absint( $id ),
                    in_array( $id, $s['nf_forms'], true ) ? ' checked' : '',
                    esc_html( $form->get_setting( 'title' ) )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'Ninja Forms not detected.', 'zbounce-email-validator' ) . '</p>';
        }
        echo '<p class="description">' . esc_html__( 'Select Ninja Forms by ID', 'zbounce-email-validator' ) . '</p>';
    }
}
