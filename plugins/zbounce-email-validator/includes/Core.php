<?php
namespace ZbEmailValidator;

class Core {
    public static function init() {
        // Load components
        Settings::init();
        ApiClient::init();
        Validator::init();
        Assets::init();

        // Admin interface
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        }
        // Register async validation handler
        add_action('zb_async_email_validation', ['ZbEmailValidator\ApiClient', 'async_validation_handler']);
    }

    public static function activate() {
        // Set default settings
        if (!get_option('zb_email_validator_settings')) {
            update_option('zb_email_validator_settings', [
                'api_key' => '',
                'cache_duration' => 24,
                'validation_mode' => 'async'
            ]);
        }

        // Create cache table
        global $wpdb;
        $table_name = $wpdb->prefix . 'zb_email_cache';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email_hash varchar(64) NOT NULL,
            validation_data text NOT NULL,
            created datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_hash (email_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('zb_email_validator_clean_cache');
    }

    public static function add_admin_menu() {
        add_options_page(
            'ZBounce Email Validator',
            'ZBounce Email',
            'manage_options',
            'zb-email-validator',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function render_settings_page() {
        require_once ZB_EVAL_PATH . 'templates/settings-page.php';
    }
}