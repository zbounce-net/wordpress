<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

class ApiClient {
    public static function init() {
        add_action( 'admin_post_zb_check_connection', [ __CLASS__, 'check_connection' ] );
        // The standalone tester is administrator-only; forms validate server-side.
        add_action( 'wp_ajax_zb_create_validation_task', [ __CLASS__, 'create_validation_task' ] );
    }

    public static function check_connection() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Administrator access required.', 'zbounce-email-validator' ) );
        }
        check_admin_referer( 'zb_check_connection' );
        $message = __( 'Accept the agreement and save your API key first.', 'zbounce-email-validator' );
        if ( Settings::is_api_configured() ) {
            $data = self::get_balance( true );
            $message = __( 'Connection failed. Check your API key and try again.', 'zbounce-email-validator' );
            if ( isset( $data['remaining'] ) ) {
                /* translators: %s: remaining verification credits. */
                $message = sprintf( __( 'Connected. Remaining verification credits: %s.', 'zbounce-email-validator' ), number_format_i18n( $data['remaining'] ) );
            }
        }
        set_transient( 'zb_connection_' . get_current_user_id(), [ 'message' => $message ], 60 );
        wp_safe_redirect( admin_url( 'options-general.php?page=zbounce-email-validator' ) );
        exit;
    }

    public static function get_balance( bool $refresh = false ): array {
        if ( ! current_user_can( 'manage_options' ) || ! Settings::is_api_configured() ) { return []; }
        $settings = Settings::get_settings();
        $key = 'zb_balance_' . hash( 'sha256', $settings['api_key'] );
        $cached = get_transient( $key );
        if ( ! $refresh && is_array( $cached ) ) { return $cached; }
        $response = wp_remote_get( ZB_EVAL_API_BASE . '/v1/me', [
            'headers' => [ 'X-API-Key' => $settings['api_key'] ], 'timeout' => 15,
            'redirection' => 0, 'limit_response_size' => 65536,
        ] );
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { delete_transient( $key ); return []; }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || ! isset( $data['remaining'] ) || ! is_numeric( $data['remaining'] ) || $data['remaining'] < 0 ) { delete_transient( $key ); return []; }
        $result = [ 'remaining' => $data['remaining'] ];
        set_transient( $key, $result, 60 );
        return $result;
    }

    public static function create_validation_task() {
        check_ajax_referer( 'zb_email_validator', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Administrator access required.', 'zbounce-email-validator' ) ], 403 );
        }
        if ( ! Settings::is_api_configured() ) {
            wp_send_json_error( [ 'message' => __( 'Accept the agreement and save your purchased API key in Settings first.', 'zbounce-email-validator' ) ], 400 );
        }
        $email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid email format', 'zbounce-email-validator' ) ], 400 );
        }
        $use_cache = ! isset( $_POST['use_cache'] ) || '0' !== $_POST['use_cache'];
        $result = $use_cache ? self::get_cached_result( md5( $email ), true ) : false;
        $from_cache = (bool) $result;
        if ( ! $result ) {
            $result = self::sync_verify( $email, true );
            if ( ! $result ) {
                wp_send_json_error( [ 'message' => __( 'ZBounce could not complete this check. Check your key, balance and connection.', 'zbounce-email-validator' ) ], 502 );
            }
            self::cache_result( md5( $email ), $result, true );
        }
        $labels = Settings::status_labels();
        $balance = self::get_balance();
        /* translators: %s: number of remaining verification credits. */
        $balance_text = isset( $balance['remaining'] ) ? sprintf( __( 'Remaining credits: %s', 'zbounce-email-validator' ), number_format_i18n( $balance['remaining'] ) ) : __( 'Balance unavailable. Check your key and connection.', 'zbounce-email-validator' );
        wp_send_json_success( [ 'result' => $result, 'from_cache' => $from_cache,
            'balance_text' => $balance_text,
            'status_label' => $labels[ $result['verification_status'] ] ?? '',
            'allowed' => '' === Validator::result_error( $result ),
        ] );
    }

    public static function sync_verify( string $email, bool $manual = false ): ?array {
        if ( ! Settings::can_request_api( $manual ) ) {
            return null;
        }
        if ( ! is_email( $email ) ) { return null; }
        $s = Settings::get_settings();
        $resp = wp_remote_post( ZB_EVAL_API_BASE . '/v1/fast-verify', [
            'headers' => [ 'Content-Type' => 'application/json', 'X-API-Key' => $s['api_key'] ],
            'body' => wp_json_encode( [ 'email' => $email ] ),
            'timeout' => 15,
            'redirection' => 0,
            'limit_response_size' => 65536,
        ] );
        delete_transient( 'zb_balance_' . hash( 'sha256', $s['api_key'] ) );
        if ( is_wp_error( $resp ) ) {
            Logger::log( 'Verification connection failed.' );
            return null;
        }
        $code = wp_remote_retrieve_response_code( $resp );
        if ( 200 !== $code ) {
            Logger::log( 'Verification failed; HTTP status ' . intval( $code ) . '.' );
            return null;
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        return self::normalize_result( $email, $data );
    }

    private static function normalize_result( string $email, $data ): ?array {
        if ( ! is_array( $data ) ) { return null; }
        foreach ( [ 'valid', 'disposable', 'accept_all' ] as $field ) {
            if ( ! isset( $data[ $field ] ) || ! is_bool( $data[ $field ] ) ) { return null; }
        }
        foreach ( [ 'exists', 'permanent_error' ] as $field ) {
            if ( isset( $data[ $field ] ) && ! is_bool( $data[ $field ] ) ) { return null; }
        }
        $category = isset( $data['error_category'] ) && is_string( $data['error_category'] ) ? sanitize_text_field( $data['error_category'] ) : '';
        $exists = $data['exists'] ?? null;
        $permanent = $data['permanent_error'] ?? false;
        // SMTP refusal/timeout is not proof of a missing mailbox. Keep the
        // engine's known missing-address outcomes distinct from probe failures.
        $missing = [ 'mailbox_not_found', 'mailbox_name_invalid', 'no_mx', 'no_mail', 'domain_not_accepts_mail' ];
        if ( $data['valid'] && ! $data['disposable'] && false === $exists && ! $data['accept_all'] && ( ! $permanent || ! in_array( $category, $missing, true ) ) ) { $exists = null; }
        if ( $data['accept_all'] ) { $exists = null; }
        $status = 'unknown';
        if ( ! $data['valid'] ) { $status = 'invalid'; }
        elseif ( $data['disposable'] ) { $status = 'disposable'; }
        elseif ( $data['accept_all'] ) { $status = 'risky'; }
        elseif ( false === $exists ) { $status = in_array( $category, [ 'no_mx', 'no_mail', 'domain_not_accepts_mail' ], true ) ? 'no_mx' : 'mailbox_not_found'; }
        elseif ( true === $exists ) { $status = 'valid'; }
        $result = [
            'verification_status' => $status,
            'email' => $email,
            'valid' => $data['valid'],
            'exists' => $exists,
            'disposable' => $data['disposable'],
            'accept_all' => $data['accept_all'],
            'permanent_error' => $permanent,
            'error_category' => $category,
        ];
        if ( isset( $data['ttl'] ) && is_int( $data['ttl'] ) && $data['ttl'] > 0 ) { $result['ttl'] = $data['ttl']; }
        return $result;
    }

    public static function get_cached_result( string $key, bool $manual = false ) {
        if ( ! Settings::can_request_api( $manual ) ) {
            return false;
        }
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'zb_email_cache' );
        // Table name is derived only from WordPress' trusted database prefix.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT validation_data, created FROM {$table} WHERE email_hash = %s", $key ), ARRAY_A );
        if ( ! $row || time() - strtotime( $row['created'] . ' UTC' ) > Settings::get_cache_duration() ) {
            return false;
        }
        $data = json_decode( $row['validation_data'], true );
        if ( ! is_array( $data ) || empty( $data['email'] ) || ! is_string( $data['email'] ) || md5( $data['email'] ) !== $key ) { return false; }
        if ( isset( $data['_expires_at'] ) && ( ! is_int( $data['_expires_at'] ) || $data['_expires_at'] <= time() ) ) { return false; }
        $normalized = self::normalize_result( $data['email'], $data );
        return $normalized && 'unknown' !== $normalized['verification_status'] ? $normalized : false;
    }

    public static function cache_result( string $key, array $data, bool $manual = false ) {
        if ( ! Settings::can_request_api( $manual ) || empty( $data['email'] ) || ! is_string( $data['email'] ) || md5( $data['email'] ) !== $key ) {
            return;
        }
        $data = self::normalize_result( $data['email'], $data );
        if ( null === $data || 'unknown' === $data['verification_status'] ) { return; }
        $data['_expires_at'] = time() + min( Settings::get_cache_duration(), $data['ttl'] ?? Settings::get_cache_duration() );
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'zb_email_cache' );
        // Remove expired rows on writes; expiry also applies on reads.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created < %s", gmdate( 'Y-m-d H:i:s', time() - Settings::get_cache_duration() ) ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace( $table, [
            'email_hash' => $key,
            'validation_data' => wp_json_encode( $data ),
            'created' => current_time( 'mysql', true ),
        ], [ '%s', '%s', '%s' ] );
    }
}
