<?php
namespace ZbEmailValidator;

use ZbEmailValidator\Logger;

class ApiClient {
    public static function init() {
        add_action('wp_ajax_zb_create_validation_task',     [__CLASS__, 'create_validation_task']);
        add_action('wp_ajax_nopriv_zb_create_validation_task', [__CLASS__, 'create_validation_task']);
    }

    public static function create_validation_task() {
        check_ajax_referer('zb_email_validator', 'security');
        $email = sanitize_email($_POST['email'] ?? '');
        Logger::log("AJAX create_validation_task called, email={$email}");

        if (!is_email($email)) {
            Logger::log("Invalid email format: {$email}");
            wp_send_json_error(['message' => 'Invalid email format']);
        }

        $cache_key = md5($email);
        if ($cached = self::get_cached_result($cache_key)) {
            Logger::log("Cache HIT for {$cache_key}");
            wp_send_json_success(['result' => $cached]);
        }
        Logger::log("Cache MISS for {$cache_key}");

        $result = self::sync_verify($email);
        if (!$result) {
            Logger::log("sync_verify returned null for {$email}");
            wp_send_json_error(['message' => 'Verification failed']);
        }
        Logger::log("sync_verify success: " . print_r($result, true));

        self::cache_result($cache_key, $result);
        Logger::log("Result cached under {$cache_key}");

        wp_send_json_success(['result' => $result]);
    }

    public static function sync_verify(string $email): ?array {
        if (Settings::is_pro()) {
            Logger::log("Using PRO fast_verify for {$email}");
            return self::fast_verify($email);
        }
        Logger::log("Using DEMO demo_verify for {$email}");
        return self::demo_verify($email);
    }

    private static function fast_verify(string $email): ?array {
        Logger::log("fast_verify POST /v1/fast-verify body=" . wp_json_encode(['email'=>$email]));
        $s = Settings::get_settings();
        $resp = wp_remote_post(
            ZB_EVAL_API_BASE . '/v1/fast-verify',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key'    => $s['api_key'],
                ],
                'body'    => wp_json_encode(['email'=>$email]),
                'timeout' => 15
            ]
        );
        if (is_wp_error($resp)) {
            Logger::log("fast_verify WP_Error: " . $resp->get_error_message());
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        Logger::log("fast_verify response code={$code}, body={$body}");
        if ($code !== 200) {
            return null;
        }
        return self::normalize_pro_result(json_decode($body, true));
    }

    private static function demo_verify(string $email): ?array {
        Logger::log("demo_verify POST /v1/demo body=" . wp_json_encode(['email'=>$email]));
        $resp = wp_remote_post(
            ZB_EVAL_API_BASE . '/v1/demo',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode(['email'=>$email]),
                'timeout' => 15
            ]
        );
        if (is_wp_error($resp)) {
            Logger::log("demo_verify WP_Error: " . $resp->get_error_message());
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        Logger::log("demo_verify response code={$code}, body={$body}");
        if ($code !== 200) {
            return null;
        }
        return self::normalize_demo_result(json_decode($body, true));
    }

    private static function normalize_pro_result(array $d): array {
        Logger::log("normalize_pro_result: " . print_r($d, true));
        return [
            'email'           => $d['email']           ?? '',
            'valid'           => (bool)$d['valid'],
            'exists'          => isset($d['exists'])   ? (bool)$d['exists']   : null,
            'disposable'      => (bool)$d['disposable'],
            'accept_all'      => (bool)$d['accept_all'],
            'permanent_error' => (bool)$d['permanent_error'],
            'error_category'  => $d['error_category'] ?? '',
        ];
    }

    private static function normalize_demo_result(array $d): array {
        Logger::log("normalize_demo_result: " . print_r($d, true));
        return [
            'email'           => $d['email']           ?? '',
            'valid'           => (bool)$d['valid'],
            'exists'          => isset($d['exists'])   ? (bool)$d['exists']   : null,
            'disposable'      => (bool)$d['disposable'],
            'accept_all'      => (bool)$d['accept_all'],
            'permanent_error' => (bool)$d['permanent_error'],
            'error_category'  => $d['error_category'] ?? '',
        ];
    }

    public static function get_cached_result(string $key) {
        global $wpdb;
        $table = $wpdb->prefix . 'zb_email_cache';
        $row   = $wpdb->get_var($wpdb->prepare(
            "SELECT validation_data FROM $table WHERE email_hash = %s",
            $key
        ));
        return $row ? json_decode($row, true) : false;
    }

    private static function cache_result(string $key, array $data) {
        if (empty($data['email'])) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'zb_email_cache';
        $wpdb->replace(
            $table,
            [
                'email_hash'      => $key,
                'validation_data' => wp_json_encode($data),
                'created'         => current_time('mysql'),
            ]
        );
    }
}

