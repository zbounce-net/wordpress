<?php
namespace ZbEmailValidator;

class Settings {
    const OPTION_KEY = 'zb_email_validator_settings';
    const DEFAULT_SETTINGS = [
        'api_key' => '',
        'cache_duration' => 24, // hours
        'validation_mode' => 'async' // async|sync
    ];

    public static function init() {
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function register_settings() {
        register_setting('zb_email_validator_settings', self::OPTION_KEY);

        add_settings_section(
            'api_settings',
            'API Configuration',
            null,
            'zb-email-validator'
        );

        add_settings_field(
            'api_key',
            'API Key (Pro Version)',
            [__CLASS__, 'render_api_key_field'],
            'zb-email-validator',
            'api_settings'
        );

        add_settings_field(
            'cache_duration',
            'Cache Duration',
            [__CLASS__, 'render_cache_duration_field'],
            'zb-email-validator',
            'api_settings'
        );

        add_settings_field(
            'validation_mode',
            'Validation Mode',
            [__CLASS__, 'render_validation_mode_field'],
            'zb-email-validator',
            'api_settings'
        );
    }

    public static function render_api_key_field() {
        $settings = self::get_settings();
        ?>
        <input type="password" name="<?= self::OPTION_KEY ?>[api_key]"
               value="<?= esc_attr($settings['api_key']) ?>" class="regular-text">
        <p class="description">For demo mode, leave blank</p>
        <?php
    }

    public static function render_cache_duration_field() {
        $settings = self::get_settings();
        ?>
        <input type="number" name="<?= self::OPTION_KEY ?>[cache_duration]"
               value="<?= esc_attr($settings['cache_duration']) ?>" min="1" max="720" step="1">
        <span>hours</span>
        <p class="description">How long to store validation results</p>
        <?php
    }

    public static function render_validation_mode_field() {
        $settings = self::get_settings();
        ?>
        <select name="<?= self::OPTION_KEY ?>[validation_mode]">
            <option value="async" <?php selected($settings['validation_mode'], 'async'); ?>>Asynchronous (recommended)</option>
            <option value="sync" <?php selected($settings['validation_mode'], 'sync'); ?>>Synchronous</option>
        </select>
        <p class="description">Async mode provides better performance</p>
        <?php
    }

    public static function get_settings() {
        return wp_parse_args(
            get_option(self::OPTION_KEY, []),
            self::DEFAULT_SETTINGS
        );
    }

    public static function is_pro() {
        $settings = self::get_settings();
        $api_key = trim($settings['api_key'] ?? '');
        return !empty($api_key);  // !!!: check non empty after trim
    }

    public static function get_cache_duration() {
        $settings = self::get_settings();
        return (int) $settings['cache_duration'] * HOUR_IN_SECONDS;
    }

    public static function get_validation_mode() {
        $settings = self::get_settings();
        return $settings['validation_mode'];
    }
}