<?php
/** Remove plugin-owned settings and cache when the plugin is deleted. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_option( 'zb_email_validator_settings' );
global $wpdb;
$zbounce_email_validator_table = esc_sql( $wpdb->prefix . 'zb_email_cache' );
// The identifier is constructed from the trusted WordPress prefix only.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$zbounce_email_validator_table}" );
