<?php
/**
 * Shortcodes and extra template tags
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Shortcodes {

    public static function init() {
        add_shortcode( 'ferdowsi_date', array( __CLASS__, 'date_shortcode' ) );
        add_shortcode( 'ferdowsi_calendar', array( __CLASS__, 'calendar_shortcode' ) );
        add_shortcode( 'ferdowsi_price', array( __CLASS__, 'price_shortcode' ) );
        add_shortcode( 'ferdowsi_convert', array( __CLASS__, 'convert_shortcode' ) );
    }

    public static function date_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'format' => 'j F Y',
            'timestamp' => null,
        ), $atts, 'ferdowsi_date' );

        $ts = $atts['timestamp'] ? (int) $atts['timestamp'] : current_time( 'timestamp' );
        $options = ferdowsi_get_options();
        return ferdowsi_jdate( $ts, $atts['format'], ! empty( $options['enable_persian_digits'] ) );
    }

    public static function calendar_shortcode( $atts ) {
        if ( class_exists( 'Ferdowsi_Calendar_Widget' ) ) {
            return Ferdowsi_Calendar_Widget::render_calendar();
        }
        return '';
    }

    public static function price_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'amount' => 0, 'unit' => null ), $atts, 'ferdowsi_price' );
        return ferdowsi_format_price( (float) $atts['amount'], $atts['unit'] );
    }

    public static function convert_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'from' => 'gregorian',
            'year' => 0, 'month' => 0, 'day' => 0,
        ), $atts, 'ferdowsi_convert' );

        if ( 'gregorian' === $atts['from'] ) {
            list( $jy, $jm, $jd ) = ferdowsi_gregorian_to_jalali( (int)$atts['year'], (int)$atts['month'], (int)$atts['day'] );
            return ferdowsi_to_persian_digits( "$jy/$jm/$jd" );
        } else {
            list( $gy, $gm, $gd ) = ferdowsi_jalali_to_gregorian( (int)$atts['year'], (int)$atts['month'], (int)$atts['day'] );
            return "$gy/$gm/$gd";
        }
    }
}
