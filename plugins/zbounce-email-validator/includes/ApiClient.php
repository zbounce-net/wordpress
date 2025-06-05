<?php
namespace ZbEmailValidator;

class ApiClient {
    public static function init() {
        add_action('wp_ajax_zb_create_validation_task', [__CLASS__, 'create_validation_task']);
        add_action('wp_ajax_nopriv_zb_create_validation_task', [__CLASS__, 'create_validation_task']);

        add_action('wp_ajax_zb_check_validation_status', [__CLASS__, 'check_validation_status']);
        add_action('wp_ajax_nopriv_zb_check_validation_status', [__CLASS__, 'check_validation_status']);

        add_action('wp_ajax_zb_get_validation_result', [__CLASS__, 'get_validation_result']);
        add_action('wp_ajax_nopriv_zb_get_validation_result', [__CLASS__, 'get_validation_result']);
    }
    public static function async_validation_handler($email) {
        $is_pro = Settings::is_pro();
        $cache_key = md5($email);

        // Check cache again in case it was updated since scheduling
        $cached = self::get_cached_result($cache_key);
        if ($cached) return;

        $endpoint = $is_pro ? '/v1/tasks' : '/demo/tasks';
        $body = $is_pro ? json_encode(['emails' => [$email]]) : json_encode(['email' => $email]);

        $args = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'timeout' => 15,
        ];

        if ($is_pro) {
            $settings = Settings::get_settings();
            $args['headers']['X-API-Key'] = $settings['api_key'];
        }

        $response = wp_remote_post(ZB_EVAL_API_BASE . $endpoint, $args);

        if (is_wp_error($response)) {
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200 || empty($body['task_id'])) {
            return;
        }

        // Wait for task completion with extended timeout
        $result = self::wait_for_task_completion($body['task_id'], $is_pro, 30, 3);

        if ($result) {
            self::cache_result($cache_key, $result);
        }

        // Release lock regardless of result
        delete_transient("zb_validation_lock_{$cache_key}");
    }
    private static function normalize_pro_result($data) {
        return [
            'email' => $data['Email'] ?? ($data['email'] ?? ''),
            'valid' => $data['Valid'] ?? ($data['valid'] ?? false),
            'exists' => $data['Exists'] ?? ($data['exists'] ?? null),
            'disposable' => $data['Disposable'] ?? ($data['disposable'] ?? false),
            'accept_all' => $data['AcceptAll'] ?? ($data['accept_all'] ?? false),
            'smtp_error' => $data['SMTPError'] ?? ($data['smtp_error'] ?? ''),
            'error_category' => $data['ErrorCategory'] ?? ($data['error_category'] ?? ''),
            'permanent_error' => $data['PermanentError'] ?? false
        ];
    }

    private static function normalize_demo_result($data) {
        return [
            'email' => $data['email'] ?? '',
            'valid' => (bool)($data['valid'] ?? false),
            'exists' => isset($data['exists']) ? (bool)$data['exists'] : null,
            'disposable' => (bool)($data['disposable'] ?? false),
            'accept_all' => (bool)($data['accept_all'] ?? false),
            'smtp_error' => $data['smtp_error'] ?? '',
            'error_category' => $data['error_category'] ?? '',
            'permanent_error' => (bool)($data['permanent_error'] ?? false)
        ];
    }

    public static function create_validation_task() {

        $is_pro = Settings::is_pro();

        // Verify nonce
        check_ajax_referer('zb_email_validator', 'security');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Invalid email format']);
        }

        // Check cache first
        $cache_key = md5($email);
        $cached = self::get_cached_result($cache_key);

        if ($cached) {
            wp_send_json_success([
                'status' => 'completed',
                'cache_key' => $cache_key,
                'result' => $cached
            ]);
        }

        // define mode pro or demo
        $endpoint = $is_pro ? '/v1/tasks' : '/demo/tasks';

        // body request format depends on mode
        $body = $is_pro
            ? json_encode(['emails' => [$email]])
            : json_encode(['email' => $email]);

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
            'timeout' => 15,
        ];

        if ($is_pro) {
            $settings = Settings::get_settings();
            $args['headers']['X-API-Key'] = $settings['api_key'];
        }

        $response = wp_remote_post(ZB_EVAL_API_BASE . $endpoint, $args);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API connection failed']);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200 || empty($body['task_id'])) {
            wp_send_json_error(['message' => 'Failed to create validation task']);
        }

        // For synchronous mode, wait for completion
        if (Settings::get_validation_mode() === 'sync') {
            $result = self::wait_for_task_completion($body['task_id'], $is_pro);

            if ($result) {
                self::cache_result($cache_key, $result);
                wp_send_json_success([
                    'status' => 'completed',
                    'cache_key' => $cache_key,
                    'result' => $result
                ]);
            }
        }

        wp_send_json_success([
            'status' => 'processing',
            'task_id' => $body['task_id'],
            'cache_key' => $cache_key,
            'is_pro' => $is_pro
        ]);
    }

    public static function check_validation_status() {

        check_ajax_referer('zb_email_validator', 'security');

        $task_id = isset($_POST['task_id']) ? sanitize_text_field($_POST['task_id']) : '';

        if (empty($task_id)) {
            wp_send_json_error(['message' => 'Missing task ID']);
        }

        $is_pro = Settings::is_pro();
        $endpoint = $is_pro ? '/v1/tasks/' : '/demo/tasks/';

        $args = ['timeout' => 10]; // TODO add to settings


        if ($is_pro) {
            $settings = Settings::get_settings();
            $args['headers'] = ['X-API-Key' => $settings['api_key']];
        }

        $response = wp_remote_get(ZB_EVAL_API_BASE . $endpoint . $task_id, $args);


        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API connection failed']);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // TODO remove after debug
        error_log("[ZB_Validator] API Response Code: $response_code");
        error_log("[ZB_Validator] API Response Body: " . print_r($body, true));

        // body response
        $decoded_body = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[ZB_Validator] JSON Parse Error: " . json_last_error_msg());
            wp_send_json_error([
                'message' => 'Invalid JSON response',
                'code' => $response_code,
                'body' => $body
            ]);
        }

        if ($response_code !== 200) {
            wp_send_json_error([
                'message' => 'Invalid API response',
                'code' => $response_code,
                'body' => $body
            ]);
            error_log($body); // TODO remove after debug
        }
        // define response format depends on mode
        $status = null;
        $progress = 0;

        // DEMO API format
        if (!$is_pro) {
            if (isset($decoded_body['status'])) {
                $status = $decoded_body['status'];

                if ($status === 'completed') {
                    $progress = 100;
                }

                elseif (isset($decoded_body['processed_pages'], $decoded_body['total_pages'])) {
                    $progress = $decoded_body['total_pages'] > 0
                        ? min(100, round(($decoded_body['processed_pages'] / $decoded_body['total_pages']) * 100))
                        : 0;
                }

                elseif (isset($decoded_body['progress'])) {
                    $progress = $decoded_body['progress'];
                }
            }
        }
        // PRO API format
        else {
            // API (v1) response
            if (isset($decoded_body['data']['status'])) {
                $status = $decoded_body['data']['status'];
                if (isset($decoded_body['data']['progress'])) {
                    $progress = $decoded_body['data']['progress'];
                }
            }
            // API (v2) response
            elseif (isset($decoded_body['status'])) {
                $status = $decoded_body['status'];
                if (isset($decoded_body['processed'], $decoded_body['total'])) {
                    $progress = $decoded_body['total'] > 0
                        ? min(100, round(($decoded_body['processed'] / $decoded_body['total']) * 100))
                        : 0;
                }
            }
        }

        if (!$status) {
            wp_send_json_error([
                'message' => 'Status field missing in response',
                'body' => $decoded_body
            ]);
        }
        wp_send_json_success([
            'status' => $status,
            'progress' => $progress
        ]);

    }

    public static function get_validation_result() {
        check_ajax_referer('zb_email_validator', 'security');

        $task_id = isset($_POST['task_id']) ? sanitize_text_field($_POST['task_id']) : '';
        $cache_key = isset($_POST['cache_key']) ? sanitize_text_field($_POST['cache_key']) : '';

        if (empty($task_id) || empty($cache_key)) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        $is_pro = Settings::is_pro(); // Важно: определять режим динамически
        $endpoint = $is_pro ? '/v1/tasks-results/' : '/demo/tasks-results/';

        $url = ZB_EVAL_API_BASE . $endpoint . $task_id . ($is_pro ? '?page=1&per_page=1' : '');

        $args = ['timeout' => 15];
        if ($is_pro) {
            $settings = Settings::get_settings();
            $args['headers'] = ['X-API-Key' => $settings['api_key']];
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API connection failed']);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            wp_send_json_error(['message' => 'Failed to retrieve validation results']);
        }

        $result = null;
        // Unified result extraction for both modes
        if ($is_pro) {
            if (isset($body['data'][0])) {
                $result = self::normalize_pro_result($body['data'][0]);
            } elseif (isset($body['Email'])) {
                $result = self::normalize_pro_result($body);
            }
        } else {
            // Handle both possible demo response formats
            if (isset($body[0])) {
                $result = self::normalize_demo_result($body[0]);
            }
            // Handle PRO-like structure in demo mode
            elseif (isset($body['data'][0])) {
                $result = self::normalize_demo_result($body['data'][0]);
            }
            // Handle single result object
            elseif (isset($body['email'])) {
                $result = self::normalize_demo_result($body);
            }
        }

        error_log("[ZB_Validator] Task results response: " . print_r([
                'response_code' => $response_code,
                'body' => $body,
                'is_pro' => $is_pro,
                'result_found' => ($result !== null)
            ], true));

        if (!$result) {
            wp_send_json_error(['message' => 'No validation data found']);
        }

        // Cache the result
        self::cache_result($cache_key, $result);

        wp_send_json_success(['result' => $result]);
    }

    private static function wait_for_task_completion($task_id, $is_pro, $max_attempts = 30, $delay = 2) {
        $attempt = 0;
        $endpoint = $is_pro ? '/v1/tasks/' : '/demo/tasks/';

        while ($attempt < $max_attempts) {
            sleep($delay);
            $attempt++;

            $args = ['timeout' => 10];
            if ($is_pro) {
                $settings = Settings::get_settings();
                $args['headers'] = ['X-API-Key' => $settings['api_key']];
            }

            $response = wp_remote_get(ZB_EVAL_API_BASE . $endpoint . $task_id, $args);

            if (is_wp_error($response)) {
                continue;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            // Unified status check
            $status = $body['status'] ?? ($body['data']['status'] ?? null);

            if ($status === 'completed') {
                return self::get_validation_result_data($task_id, $is_pro);
            } elseif ($status === 'failed') {
                break;
            }
        }

        return null;
    }

    private static function get_validation_result_data($task_id, $is_pro) {
        $endpoint = $is_pro ? '/v1/tasks-results/' : '/demo/tasks-results/';
        $url = ZB_EVAL_API_BASE . $endpoint . $task_id;

        if ($is_pro) {
            $url .= '?page=1&per_page=1';
        }

        $args = ['timeout' => 15];
        if ($is_pro) {
            $settings = Settings::get_settings();
            $args['headers'] = ['X-API-Key' => $settings['api_key']];
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // processing depends on format
        if ($is_pro) {
            // PRO API <=> ['data'][0]
            if (isset($data['data'][0])) {
                return self::normalize_pro_result($data['data'][0]);
            }
            // or just object
            elseif (isset($data['Email'])) {
                return self::normalize_pro_result($data);
            }
        } else {
            // DEMO API array response
            if (isset($data[0])) {
                return self::normalize_demo_result($data[0]);
            }
            // or just object
            elseif (isset($data['email'])) {
                return self::normalize_demo_result($data);
            }
        }

        return null;
    }

    public static function get_cached_result($cache_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'zb_email_cache';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT validation_data FROM $table WHERE email_hash = %s",
            $cache_key
        ));

        return $result ? json_decode($result, true) : false;
    }

    private static function cache_result($cache_key, $data) {
        if (empty($data['email']) || ($data['exists'] === null && !$data['permanent_error'])) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'zb_email_cache';

        $wpdb->replace($table, [
            'email_hash' => $cache_key,
            'validation_data' => json_encode($data),
            'created' => current_time('mysql')
        ]);
    }
}