<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

// WordPress owns authorization and request confirmation for its privacy tools.
class Privacy {
    public static function init() {
        add_filter( 'wp_privacy_personal_data_exporters', [ __CLASS__, 'exporters' ] );
        add_filter( 'wp_privacy_personal_data_erasers', [ __CLASS__, 'erasers' ] );
    }
    public static function exporters( $exporters ) {
        $exporters['zbounce-email-validator'] = [ 'exporter_friendly_name' => 'ZBounce Email Validator', 'callback' => [ __CLASS__, 'export' ] ];
        return $exporters;
    }
    public static function erasers( $erasers ) {
        $erasers['zbounce-email-validator'] = [ 'eraser_friendly_name' => 'ZBounce Email Validator', 'callback' => [ __CLASS__, 'erase' ] ];
        return $erasers;
    }
    private static function matching( string $email ): array {
        global $wpdb;
        // Match old case variants as well as current hash keys. JSON encoding is
        // identical to cache writes; escaped LIKE characters cannot add wildcards.
        $encoded = wp_json_encode( strtolower( $email ) );
        return [ md5( $email ), md5( strtolower( $email ) ), '%' . $wpdb->esc_like( '"email":' . $encoded ) . '%' ];
    }
    public static function export( $email, $page = 1 ) {
        global $wpdb;
        $result = [ 'data' => [], 'done' => true ];
        if ( ! is_string( $email ) || ! is_email( $email ) ) { return $result; }
        $table = esc_sql( $wpdb->prefix . 'zb_email_cache' );
        $parameters = self::matching( $email );
        $offset = ( max( 1, intval( $page ) ) - 1 ) * 200;
        // The table name is derived only from the trusted WordPress prefix.
        // Privacy operations must read/delete the actual private cache, never stale object-cache data.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, validation_data, created FROM {$table} WHERE email_hash IN (%s, %s) OR LOWER(validation_data) LIKE %s ORDER BY id LIMIT 200 OFFSET %d", $parameters[0], $parameters[1], $parameters[2], $offset ), ARRAY_A );
        foreach ( (array) $rows as $row ) {
            $data = json_decode( $row['validation_data'], true );
            if ( ! is_array( $data ) || ! isset( $data['email'] ) || ! is_string( $data['email'] ) || 0 !== strcasecmp( $email, $data['email'] ) ) { continue; }
            $result['data'][] = [
                'group_id' => 'zbounce-email-verification', 'group_label' => __( 'Email verification cache', 'zbounce-email-validator' ),
                'item_id' => 'zbounce-cache-' . intval( $row['id'] ),
                'data' => [ [ 'name' => __( 'Email address', 'zbounce-email-validator' ), 'value' => $data['email'] ], [ 'name' => __( 'Cached at (UTC)', 'zbounce-email-validator' ), 'value' => $row['created'] ], [ 'name' => __( 'Verification result', 'zbounce-email-validator' ), 'value' => wp_json_encode( array_intersect_key( $data, array_flip( [ 'valid', 'exists', 'disposable', 'accept_all', 'permanent_error', 'error_category' ] ) ) ) ] ],
            ];
        }
        $result['done'] = count( (array) $rows ) < 200;
        return $result;
    }
    public static function erase( $email, $page = 1 ) {
        global $wpdb;
        $result = [ 'items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true ];
        if ( ! is_string( $email ) || ! is_email( $email ) ) { return $result; }
        $table = esc_sql( $wpdb->prefix . 'zb_email_cache' );
        $parameters = self::matching( $email );
        // Always delete the next batch; OFFSET would skip rows after deletion.
        // Privacy operations must read/delete the actual private cache, never stale object-cache data.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $removed = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE email_hash IN (%s, %s) OR LOWER(validation_data) LIKE %s LIMIT 200", $parameters[0], $parameters[1], $parameters[2] ) );
        if ( false === $removed ) {
            $result['items_retained'] = true;
            $result['messages'][] = __( 'Could not remove cached verification data. Try again.', 'zbounce-email-validator' );
        } else { $result['items_removed'] = $removed > 0; $result['done'] = $removed < 200; }
        return $result;
    }
}
