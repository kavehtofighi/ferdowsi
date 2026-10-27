<?php
/**
 * Persian Digits and Character Conversion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ferdowsi_Numbers {

    private static $options = array();

    public static function init( $options ) {
        self::$options = $options;

        if ( ! empty( $options['enable_persian_digits'] ) ) {
            // Convert numbers in various places (careful not to break JS/CSS)
            add_filter( 'the_content', array( __CLASS__, 'convert_digits_in_content' ), 99 );
            add_filter( 'the_title', array( __CLASS__, 'convert_digits_in_content' ), 99 );
            add_filter( 'widget_text', array( __CLASS__, 'convert_digits_in_content' ), 99 );
            // Admin numbers are mostly handled via date filters
        }

        if ( ! empty( $options['enable_char_conversion'] ) ) {
            add_filter( 'the_content', array( __CLASS__, 'convert_chars' ), 20 );
            add_filter( 'the_title', array( __CLASS__, 'convert_chars' ), 20 );
            add_filter( 'the_excerpt', array( __CLASS__, 'convert_chars' ), 20 );
            add_filter( 'comment_text', array( __CLASS__, 'convert_chars' ), 20 );
            // On save to normalize content
            add_filter( 'content_save_pre', array( __CLASS__, 'convert_chars' ) );
            add_filter( 'title_save_pre', array( __CLASS__, 'convert_chars' ) );
        }
    }

    public static function convert_digits_in_content( $content ) {
        // Only convert digits that are not inside HTML tags attributes carefully
        // Simple approach: convert standalone numbers
        return ferdowsi_to_persian_digits( $content );
    }

    public static function convert_chars( $content ) {
        return ferdowsi_arabic_to_persian( $content );
    }
}
