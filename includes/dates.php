<?php
/**
 * Jalali Date Handling throughout WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ferdowsi_Dates {

    public static function init() {
        // Core date filters
        add_filter( 'date_i18n', array( __CLASS__, 'filter_date_i18n' ), 10, 4 );
        add_filter( 'get_the_date', array( __CLASS__, 'filter_get_the_date' ), 10, 3 );
        add_filter( 'get_the_time', array( __CLASS__, 'filter_get_the_time' ), 10, 3 );
        add_filter( 'get_comment_date', array( __CLASS__, 'filter_get_comment_date' ), 10, 3 );
        add_filter( 'get_comment_time', array( __CLASS__, 'filter_get_comment_time' ), 10, 5 );

        // Human time diff can stay or be left
        // Admin list tables
        add_filter( 'post_date_column_time', array( __CLASS__, 'filter_post_date_column' ), 10, 4 );

        // Media library
        add_filter( 'wp_prepare_attachment_for_js', array( __CLASS__, 'filter_attachment_js' ), 10, 3 );

        // Optional: change default date format in general settings display (display only)
        add_filter( 'option_date_format', array( __CLASS__, 'filter_option_date_format' ) );
        add_filter( 'option_time_format', array( __CLASS__, 'filter_option_time_format' ) );
    }

    public static function filter_date_i18n( $date, $format, $timestamp, $gmt ) {
        if ( empty( $timestamp ) ) {
            $timestamp = current_time( 'timestamp', $gmt );
        }
        // Skip if format is special or already processed carefully
        $options = ferdowsi_get_options();
        $persian = ! empty( $options['enable_persian_digits'] );

        // Use our jdate for common formats
        $jalali = ferdowsi_jdate( $timestamp, $format, $persian );

        if ( ! empty( $options['show_gregorian_beside'] ) ) {
            $greg = date_i18n( $format, $timestamp, $gmt ); // original without our filter? careful
            // To avoid recursion we can use date()
            $greg = date( $format, $timestamp );
            $jalali .= ' (' . $greg . ')';
        }

        return $jalali;
    }

    public static function filter_get_the_date( $the_date, $format, $post ) {
        if ( empty( $format ) ) {
            $format = get_option( 'date_format' );
        }
        $timestamp = get_post_time( 'U', false, $post );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $timestamp, $format, ! empty( $options['enable_persian_digits'] ) );
    }

    public static function filter_get_the_time( $the_time, $format, $post ) {
        if ( empty( $format ) ) {
            $format = get_option( 'time_format' );
        }
        $timestamp = get_post_time( 'U', false, $post );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $timestamp, $format, ! empty( $options['enable_persian_digits'] ) );
    }

    public static function filter_get_comment_date( $date, $format, $comment ) {
        if ( empty( $format ) ) {
            $format = get_option( 'date_format' );
        }
        $timestamp = strtotime( $comment->comment_date );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $timestamp, $format, ! empty( $options['enable_persian_digits'] ) );
    }

    public static function filter_get_comment_time( $date, $format, $gmt, $translate, $comment ) {
        if ( empty( $format ) ) {
            $format = get_option( 'time_format' );
        }
        $timestamp = $gmt ? strtotime( $comment->comment_date_gmt ) : strtotime( $comment->comment_date );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $timestamp, $format, ! empty( $options['enable_persian_digits'] ) );
    }

    public static function filter_post_date_column( $h_time, $post, $column_name, $mode ) {
        $timestamp = get_post_time( 'U', false, $post );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $timestamp, 'j F Y', ! empty( $options['enable_persian_digits'] ) );
    }

    public static function filter_attachment_js( $response, $attachment, $meta ) {
        if ( isset( $response['dateFormatted'] ) ) {
            $timestamp = strtotime( $attachment->post_date );
            $options = ferdowsi_get_options();
            $response['dateFormatted'] = ferdowsi_jdate( $timestamp, 'j F Y', ! empty( $options['enable_persian_digits'] ) );
        }
        return $response;
    }

    public static function filter_option_date_format( $format ) {
        // We keep the option as is for storage, but display is handled by filters.
        // Optionally suggest Persian friendly format.
        return $format;
    }

    public static function filter_option_time_format( $format ) {
        return $format;
    }
}
