<?php
namespace ZbEmailValidator;

class Validator {
    public static function init() {
        // Shortcode
        add_shortcode('zb_email_validator', [__CLASS__, 'render_validator']);

        // Contact Form 7 integration
        add_filter('wpcf7_validate_email', [__CLASS__, 'validate_cf7_email'], 20, 2);
        add_filter('wpcf7_validate_email*', [__CLASS__, 'validate_cf7_email'], 20, 2);

        // WooCommerce checkout validation
        add_action('woocommerce_after_checkout_validation', [__CLASS__, 'validate_woocommerce_email'], 10, 2);

        // WordPress registration validation
        add_filter('registration_errors', [__CLASS__, 'validate_registration_email'], 10, 3);
    }

    public static function render_validator($atts) {
        wp_enqueue_style('zb-email-validator');
        wp_enqueue_script('zb-email-validator');

        ob_start();
        ?>
        <div class="zb-email-validator">
            <div class="zb-input-group">
                <input type="email" class="zb-email-input" placeholder="Enter email address">
                <button class="zb-validate-btn">Validate</button>
            </div>

            <div class="zb-validation-status" style="display:none;">
                <div class="zb-status-header">
                    <span class="zb-status-label">Status:</span>
                    <span class="zb-status-value">❔ Waiting</span>
                </div>
                <div class="zb-progress-container">
                    <div class="zb-progress-bar"></div>
                </div>
            </div>

            <div class="zb-validation-results" style="display:none;">
                <h3>Results for: <span class="zb-result-email"></span></h3>

                <!-- Updated structure with labels -->
                <div class="zb-result-item">
                    <span class="zb-result-label">Email Format:</span>
                    <span class="zb-result-value zb-validity-badge"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Mailbox Exists:</span>
                    <span class="zb-result-value zb-exists-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Disposable:</span>
                    <span class="zb-result-value zb-disposable-status"></span>
                </div>
                <div class="zb-result-item">
                    <span class="zb-result-label">Accepts All:</span>
                    <span class="zb-result-value zb-acceptall-status"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function validate_cf7_email($result, $tag) {
        if ($tag->basetype !== 'email') return $result;

        $email = isset($_POST[$tag->name]) ? sanitize_email($_POST[$tag->name]) : '';

        if (!empty($email)) {
            // Trigger async validation
            self::trigger_async_validation($email);

            $validation = self::validate_email($email);

            if (!$validation['valid']) {
                $result->invalidate($tag, 'Invalid email format');
            } elseif ($validation['disposable']) {
                $result->invalidate($tag, 'Disposable emails are not allowed');
            } elseif ($validation['exists'] === false) {
                $result->invalidate($tag, 'Email address does not exist');
            } elseif ($validation['validation_pending']) {
                $result->invalidate($tag, 'Email validation in progress. Please wait a moment and try again');
            }
        }

        return $result;
    }

    public static function validate_woocommerce_email($data, $errors) {
        $email = $data['billing_email'] ?? '';

        if (!empty($email)) {
            // Trigger async validation
            self::trigger_async_validation($email);

            $validation = self::validate_email($email);

            if (!$validation['valid']) {
                $errors->add('validation', 'Invalid email format');
            } elseif ($validation['disposable']) {
                $errors->add('validation', 'Disposable emails are not allowed');
            } elseif ($validation['exists'] === false) {
                $errors->add('validation', 'Email address does not exist');
            } elseif ($validation['validation_pending']) {
                $errors->add('validation', 'Email validation in progress. Please wait a moment and try again');
            }
        }
    }
    public static function validate_registration_email($errors, $sanitized_user_login, $user_email) {
        // Trigger async validation
        self::trigger_async_validation($user_email);

        $validation = self::validate_email($user_email);

        if (!$validation['valid']) {
            $errors->add('invalid_email', 'Invalid email format');
        } elseif ($validation['disposable']) {
            $errors->add('disposable_email', 'Disposable emails are not allowed');
        } elseif ($validation['exists'] === false) {
            $errors->add('email_not_exists', 'Email address does not exist');
        } elseif ($validation['validation_pending']) {
            $errors->add('validation_pending', 'Email validation in progress. Please try again later');
        }

        return $errors;
    }
    private static function trigger_async_validation($email) {
        $cache_key = md5($email);

        // Check if validation is already in progress
        $validation_lock = get_transient("zb_validation_lock_{$cache_key}");
        if ($validation_lock) return;

        // Set lock for 2 minutes to prevent duplicate requests
        set_transient("zb_validation_lock_{$cache_key}", 'processing', 120);

        // Schedule async validation
        wp_schedule_single_event(time(), 'zb_async_email_validation', [$email]);
    }

    private static function validate_email($email) {
        $cache_key = md5($email);
        $cached = ApiClient::get_cached_result($cache_key);

        if ($cached) {
            return $cached;
        }

        // Check if validation is in progress
        $validation_lock = get_transient("zb_validation_lock_{$cache_key}");

        return [
            'email' => $email,
            'valid' => true, // Assume valid until proven otherwise
            'exists' => null,
            'disposable' => false,
            'accept_all' => false,
            'validation_pending' => (bool)$validation_lock
        ];
    }
}