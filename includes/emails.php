<?php
/**
 * Convert dates in WordPress & plugin emails
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Emails {

    public static function init() {
        add_filter( 'wp_mail', array( __CLASS__, 'filter_mail' ), 20 );
        // WooCommerce already handled in its module
    }

    public static function filter_mail( $args ) {
        if ( empty( $args['message'] ) ) return $args;

        // Replace common Gregorian date patterns with Jalali (simple heuristic)
        // More robust would parse exactly, but this helps many cases
        $args['message'] = preg_replace_callback(
            '/\b(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\b/',
            function( $m ) {
                $ts = mktime( 0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1] );
                return ferdowsi_jdate( $ts, 'Y/m/d', true );
            },
            $args['message']
        );

        return $args;
    }
}
