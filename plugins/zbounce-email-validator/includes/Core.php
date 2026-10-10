<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

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
        Privacy::init();

        // AJAX, интеграции и фронтенд
        ApiClient::init();
        Validator::init();
        Assets::init();
        Integrations::init();

        // В админке добавляем пункт меню
        if ( is_admin() ) {
            add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
        }
    }

    public static function activate() {
        // Сохраняем настройки по умолчанию, если их ещё нет
        if ( ! get_option( Settings::OPTION_KEY ) ) {
            update_option( Settings::OPTION_KEY, [
                'api_key'        => '',
                'service_enabled'=> false,
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
            __( 'ZBounce Email Validator', 'zbounce-email-validator' ),
            __( 'ZBounce Email', 'zbounce-email-validator' ),
            'manage_options',
            'zbounce-email-validator',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require ZB_EVAL_PATH . 'templates/settings-page.php';
    }
}
