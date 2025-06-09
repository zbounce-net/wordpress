<?php
namespace ZbEmailValidator;

class Settings {
    const OPTION_KEY       = 'zb_email_validator_settings';
    const DEFAULT_SETTINGS = [
        'api_key'         => '',
        'cache_duration'  => 24,   // hours
        'enable_logging'  => false // новый флаг для логирования
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
            'enable_logging',
            'Enable Debug Logging',
            [__CLASS__, 'render_logging_field'],
            'zb-email-validator',
            'api_settings'
        );
    }

    public static function render_api_key_field() {
        $s = self::get_settings(); ?>
        <input type="password"
               name="<?= self::OPTION_KEY ?>[api_key]"
               value="<?= esc_attr($s['api_key']) ?>"
               class="regular-text">
        <p class="description">Оставьте пустым для демо-режима</p>
    <?php }

    public static function render_cache_duration_field() {
        $s = self::get_settings(); ?>
        <input type="number"
               name="<?= self::OPTION_KEY ?>[cache_duration]"
               value="<?= esc_attr($s['cache_duration']) ?>"
               min="1" max="720" step="1"> hours
        <p class="description">Длительность хранения кэша</p>
    <?php }

    public static function render_logging_field() {
        $s = self::get_settings(); ?>
        <label>
            <input type="checkbox"
                   name="<?= self::OPTION_KEY ?>[enable_logging]"
                   value="1" <?= checked($s['enable_logging'], true, false) ?>>
            Включить логирование (error_log)
        </label>
        <p class="description">Логи в PHP error_log для отладки работы плагина</p>
    <?php }

    public static function get_settings(): array {
        return wp_parse_args(
            get_option(self::OPTION_KEY, []),
            self::DEFAULT_SETTINGS
        );
    }

    public static function is_pro(): bool {
        $s   = self::get_settings();
        $key = trim($s['api_key'] ?? '');
        return (bool) $key;
    }

    public static function get_cache_duration(): int {
        $s = self::get_settings();
        return (int)$s['cache_duration'] * HOUR_IN_SECONDS;
    }

    public static function is_logging_enabled(): bool {
        $s = self::get_settings();
        return !empty($s['enable_logging']);
    }
}
