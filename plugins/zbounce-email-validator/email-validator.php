<?php
/**
 * Plugin Name: ZBounce Email Validator
 * Description: Professional email verification service with WordPress integration
 * Version: 1.0.0
 * Author: zbounce.net
 * License: GPLv3
 */

defined('ABSPATH') || exit;

// Plugin constants
define('ZB_EVAL_VERSION', '2.0.0');
define('ZB_EVAL_PATH', plugin_dir_path(__FILE__));
define('ZB_EVAL_URL', plugin_dir_url(__FILE__));
define('ZB_EVAL_API_BASE', 'https://api.zbounce.net');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'ZbEmailValidator\\';
    $base_dir = ZB_EVAL_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) require $file;
});

// Activation/deactivation hooks
register_activation_hook(__FILE__, ['ZbEmailValidator\\Core', 'activate']);
register_deactivation_hook(__FILE__, ['ZbEmailValidator\\Core', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', ['ZbEmailValidator\\Core', 'init']);