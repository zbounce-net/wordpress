<?php
namespace ZbEmailValidator;

class Core {
    public static function init() {
        Settings::init();
        ApiClient::init();
        Validator::init();
        Assets::init();

        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        }
    }

    public static function activate() {
        // Default settings
        if (!get_option(Settings::OPTION_KEY)) {
            update_option(Settings::OPTION_KEY, [
                'api_key'        => '',
                'cache_duration' => 24,
            ]);
        }

        // Create cache table
        global $wpdb;
        $table       = $wpdb->prefix . 'zb_email_cache';
        $charset     = $wpdb->get_charset_collate();
        $sql = "
            CREATE TABLE {$table} (
                id              mediumint(9) NOT NULL AUTO_INCREMENT,
                email_hash      varchar(64) NOT NULL,
                validation_data longtext NOT NULL,
                created         datetime NOT NULL,
                PRIMARY KEY     (id),
                UNIQUE KEY      email_hash (email_hash)
            ) {$charset};
        ";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function deactivate() {
        // no scheduled hooks to clear
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
        require ZB_EVAL_PATH . 'templates/settings-page.php';
    }
}
