<?php
/**
 * Advanced Custom Fields (ACF) Jalali Date Support
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_ACF {

    public static function init() {
        if ( ! class_exists( 'ACF' ) ) return;

        add_action( 'acf/include_field_types', array( __CLASS__, 'register_field' ) );
        add_filter( 'acf/format_value/type=date_picker', array( __CLASS__, 'format_date_picker' ), 10, 3 );
        add_filter( 'acf/format_value/type=date_time_picker', array( __CLASS__, 'format_date_picker' ), 10, 3 );
    }

    public static function register_field() {
        // Simple note: we enhance existing date fields display
        // Full custom field type would require more code; this enhances display
    }

    public static function format_date_picker( $value, $post_id, $field ) {
        if ( empty( $value ) ) return $value;

        // ACF stores Ymd or timestamp-like
        $ts = is_numeric( $value ) ? $value : strtotime( $value );
        if ( ! $ts ) {
            // Try Ymd format
            if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $value, $m ) ) {
                $ts = mktime( 0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1] );
            }
        }
        if ( $ts ) {
            $options = ferdowsi_get_options();
            return ferdowsi_jdate( $ts, 'j F Y', ! empty( $options['enable_persian_digits'] ) );
        }
        return $value;
    }
}
