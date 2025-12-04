<?php
namespace ZbEmailValidator;

use ZbEmailValidator\Settings;
use ZbEmailValidator\Logger;
use ZbEmailValidator\ApiClient;
use ZbEmailValidator\Validator;
use ZbEmailValidator\Assets;
use ZbEmailValidator\Integrations;

class Core {

    public static function init() {
        // Регистрируем настройки
        Settings::init();

        // Если включено логирование, направляем PHP-логи в wp-content/debug.log
        if ( Settings::is_logging_enabled() ) {
            ini_set( 'log_errors',        1 );
            ini_set( 'error_log',         WP_CONTENT_DIR . '/debug.log' );
            Logger::log( 'ZBV logging initialized – writing to debug.log' );
        }

        // AJAX, интеграции и фронтенд
        ApiClient::init();
        Validator::init();
        Assets::init();
        Integrations::init();

        // В админке добавляем пункт меню
        if ( is_admin() ) {
            add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
            add_action( 'admin_notices', [ __CLASS__, 'maybe_show_expired_notice' ] );
        }
    }

    public static function activate() {
        // Сохраняем настройки по умолчанию, если их ещё нет
        if ( ! get_option( Settings::OPTION_KEY ) ) {
            update_option( Settings::OPTION_KEY, [
                'api_key'        => '',
                'cache_duration' => 24,
                'enable_logging' => false,
                'cf7_forms'      => [],
                'wc_enable'      => true,
                'reg_enable'     => true,
                'gf_forms'       => [],
                'wpf_forms'      => [],
                'nf_forms'       => [],
            ] );
        }

        // Создаём таблицу wp_{prefix}zb_email_cache
        global $wpdb;
        $table   = $wpdb->prefix . 'zb_email_cache';
        $charset = $wpdb->get_charset_collate();
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
        dbDelta( $sql );
    }

    public static function deactivate() {
        // Ничего не удаляем, чтобы не потерять кэш и настройки
    }

    public static function add_admin_menu() {
        add_options_page(
            __( 'ZBounce Email Validator', 'zb-email-validator' ),
            __( 'ZBounce Email', 'zb-email-validator' ),
            'manage_options',
            'zb-email-validator',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function render_settings_page() {
        require ZB_EVAL_PATH . 'templates/settings-page.php';
    }
    public static function maybe_show_expired_notice() {
        $s = Settings::get_settings();
        if ( ! empty( $s['api_key_expired'] ) ) {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'Your ZBounce API key is invalid or expired. The plugin is running in demo mode. Please enter a new key in settings.', 'zb-email-validator' )
            . '</p></div>';
        }
    }
}

add_action( 'plugins_loaded', [ 'ZbEmailValidator\\Core', 'init' ], 20 );
