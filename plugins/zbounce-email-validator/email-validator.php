<?php
/**
 * Plugin Name: ZBounce Email Engine Platform Validator
 * Plugin URI:  https://zbounce.net
 * Description: Integrates your site with the ZBounce Email Engine Platform API for real-time email validation.
 * Version:     1.1.0
 * Author:      ZBounce.net
 * Author URI:  https://zbounce.net
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zb-email-validator
 * Domain Path: /languages
 *
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.0
 * Tags: email, validation, verification, cf7, woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'ZB_EVAL_VERSION', '2.0.0' );
define( 'ZB_EVAL_PATH',    plugin_dir_path( __FILE__ ) );
define( 'ZB_EVAL_URL',     plugin_dir_url( __FILE__ ) );
define( 'ZB_EVAL_API_BASE','https://api.zbounce.net' );

// Load translations
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'zb-email-validator',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}, 1 );

spl_autoload_register( function ( $class ) {
    $prefix  = 'ZbEmailValidator\\';
    $baseDir = ZB_EVAL_PATH . 'includes/';

    if ( 0 !== strncmp( $prefix, $class, strlen( $prefix ) ) ) {
        return;
    }

    $relative_class = substr( $class, strlen( $prefix ) );
    $file           = $baseDir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

register_activation_hook( __FILE__,   ['ZbEmailValidator\\Core', 'activate'] );
register_deactivation_hook( __FILE__, ['ZbEmailValidator\\Core', 'deactivate'] );

add_action( 'plugins_loaded', ['ZbEmailValidator\\Core', 'init'] );
