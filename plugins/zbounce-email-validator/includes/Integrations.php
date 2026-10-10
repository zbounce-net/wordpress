<?php
namespace ZbEmailValidator;

defined( 'ABSPATH' ) || exit;

class Integrations {
    public static function init() {
        $s = Settings::get_settings();
        if ( ! empty( $s['gf_forms'] ) && class_exists( 'GFAPI' ) ) {
            add_filter( 'gform_field_validation', [ __CLASS__, 'validate_gravity' ], 20, 4 );
        }
        if ( ! empty( $s['wpf_forms'] ) && function_exists( 'wpforms' ) ) {
            add_action( 'wpforms_process_validate_email', [ __CLASS__, 'validate_wpforms' ], 20, 3 );
        }
        if ( ! empty( $s['nf_forms'] ) && function_exists( 'Ninja_Forms' ) ) {
            add_filter( 'ninja_forms_submit_data', [ __CLASS__, 'validate_ninja' ], 20 );
        }
    }

    private static function error_message( string $email ): string {
        if ( '' === $email ) {
            return ''; // Required-field validation belongs to the form plugin.
        }
        return Validator::validation_error( $email );
    }

    public static function validate_gravity( $result, $value, $form, $field ) {
        $s = Settings::get_settings();
        if ( ! in_array( absint( $form['id'] ), $s['gf_forms'], true ) || 'email' !== $field->type || ! is_string( $value ) ) {
            return $result;
        }
        if ( empty( $result['is_valid'] ) ) { return $result; }
        $message = self::error_message( sanitize_email( $value ) );
        if ( '' !== $message ) {
            $result['is_valid'] = false;
            $result['message'] = $message;
        }
        return $result;
    }

    public static function validate_wpforms( $field_id, $value, $form ) {
        $s = Settings::get_settings();
        if ( ! in_array( absint( $form['id'] ), $s['wpf_forms'], true ) ) {
            return;
        }
        // Confirmation fields may submit a primary/secondary pair.
        $value = is_array( $value ) ? ( $value['primary'] ?? '' ) : $value;
        if ( ! is_string( $value ) ) {
            return;
        }
        $message = self::error_message( sanitize_email( $value ) );
        if ( '' !== $message && empty( wpforms()->obj( 'process' )->errors[ absint( $form['id'] ) ][ absint( $field_id ) ] ) ) {
            wpforms()->obj( 'process' )->errors[ absint( $form['id'] ) ][ absint( $field_id ) ] = $message;
        }
    }

    public static function validate_ninja( $form_data ) {
        $s = Settings::get_settings();
        if ( ! in_array( absint( $form_data['id'] ?? 0 ), $s['nf_forms'], true ) ) {
            return $form_data;
        }
        foreach ( (array) ( $form_data['fields'] ?? [] ) as $field ) {
            if ( 'email' !== ( $field['type'] ?? '' ) || ! isset( $field['value'] ) || ! is_string( $field['value'] ) ) {
                continue;
            }
            if ( ! empty( $form_data['errors']['fields'][ absint( $field['id'] ?? 0 ) ] ) ) { continue; }
            $message = self::error_message( sanitize_email( $field['value'] ) );
            if ( '' !== $message ) {
                $form_data['errors']['fields'][ absint( $field['id'] ) ] = $message;
            }
        }
        return $form_data;
    }
}
