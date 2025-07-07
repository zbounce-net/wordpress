<?php
namespace ZbEmailValidator;

class Integrations {

    public static function init() {
        $s = Settings::get_settings();

        // Gravity Forms
        if ( ! empty( $s['gf_forms'] ) && class_exists( 'GFAPI' ) ) {
            add_filter( 'gform_field_validation', [ __CLASS__, 'validate_gravity' ], 10, 4 );
        }

        // WPForms
        if ( ! empty( $s['wpf_forms'] ) && class_exists( 'WPForms' ) ) {
            add_filter( 'wpforms_process_validate_field', [ __CLASS__, 'validate_wpforms' ], 10, 4 );
        }

        // Ninja Forms
        if ( ! empty( $s['nf_forms'] ) && class_exists( 'Ninja_Forms' ) ) {
            add_filter( 'ninja_forms_process_before_errors', [ __CLASS__, 'validate_ninja' ], 10, 2 );
        }
    }

    public static function validate_gravity( $result, $value, $form, $field ) {
        $s   = Settings::get_settings();
        $fid = intval( $form['id'] );
        if ( ! in_array( $fid, (array) $s['gf_forms'], true ) || $field->type !== 'email' ) {
            return $result;
        }

        $v = Validator::run_sync_validation( $value );
        if ( ! $v['valid'] ) {
            $result['is_valid'] = false;
            $result['message']  = __( 'Invalid email format', 'zb-email-validator' );
        } elseif ( $v['disposable'] ) {
            $result['is_valid'] = false;
            $result['message']  = __( 'Disposable emails are not allowed', 'zb-email-validator' );
        } elseif ( $v['exists'] === false ) {
            $result['is_valid'] = false;
            $result['message']  = __( 'Email address does not exist', 'zb-email-validator' );
        }

        return $result;
    }

    public static function validate_wpforms( $field_data, $fields, $entry, $form_data ) {
        $s   = Settings::get_settings();
        $fid = intval( $form_data['id'] );
        if ( ! in_array( $fid, (array) $s['wpf_forms'], true ) || empty( $field_data['type'] ) ) {
            return $field_data;
        }

        if ( 'email' === $field_data['type'] ) {
            $email = sanitize_email( $field_data['value'] );
            $v     = Validator::run_sync_validation( $email );
            $msg   = '';

            if ( ! $v['valid'] ) {
                $msg = __( 'Invalid email format', 'zb-email-validator' );
            } elseif ( $v['disposable'] ) {
                $msg = __( 'Disposable emails are not allowed', 'zb-email-validator' );
            } elseif ( $v['exists'] === false ) {
                $msg = __( 'Email address does not exist', 'zb-email-validator' );
            }

            if ( $msg ) {
                $field_data['error'] = $msg;
            }
        }

        return $field_data;
    }

    public static function validate_ninja( $errors, $form_data ) {
        $s   = Settings::get_settings();
        $fid = intval( $form_data['id'] );
        if ( ! in_array( $fid, (array) $s['nf_forms'], true ) ) {
            return $errors;
        }

        foreach ( $form_data['fields'] as $field ) {
            if ( $field['type'] !== 'email' ) {
                continue;
            }
            $email = sanitize_email( $field['value'] );
            $v     = Validator::run_sync_validation( $email );
            $msg   = '';

            if ( ! $v['valid'] ) {
                $msg = __( 'Invalid email format', 'zb-email-validator' );
            } elseif ( $v['disposable'] ) {
                $msg = __( 'Disposable emails are not allowed', 'zb-email-validator' );
            } elseif ( $v['exists'] === false ) {
                $msg = __( 'Email address does not exist', 'zb-email-validator' );
            }

            if ( $msg ) {
                $errors[] = [
                    'id'      => $field['id'],
                    'message' => $msg,
                ];
            }
        }

        return $errors;
    }
}
