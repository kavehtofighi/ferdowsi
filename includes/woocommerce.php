<?php
/**
 * Deep WooCommerce Integration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_WooCommerce {

    public static function init() {
        if ( ! class_exists( 'WooCommerce' ) ) return;

        // Dates in orders list & details
        add_filter( 'woocommerce_admin_order_date_format', array( __CLASS__, 'order_date_format' ) );
        add_filter( 'woocommerce_order_date_created', array( __CLASS__, 'format_order_date' ), 10, 2 );
        add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'order_column_date' ), 20, 2 );
        add_filter( 'woocommerce_admin_order_preview_get_order_details', array( __CLASS__, 'preview_order_dates' ), 10, 2 );

        // Emails
        add_filter( 'woocommerce_email_order_date_format', array( __CLASS__, 'email_date_format' ) );
        add_action( 'woocommerce_email_header', array( __CLASS__, 'email_styles' ) );

        // Price formatting
        add_filter( 'wc_get_price_thousand_separator', function() { return '،'; } );
        add_filter( 'wc_get_price_decimal_separator', function() { return '.'; } );
        add_filter( 'woocommerce_price_format', array( __CLASS__, 'price_format' ), 10, 2 );

        // Checkout validation: Iranian mobile & national ID
        add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_iranian_fields' ) );
        add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'customize_checkout_fields' ) );

        // Analytics / reports date display (basic)
        add_filter( 'woocommerce_date_format', array( __CLASS__, 'wc_date_format' ) );
    }

    public static function order_date_format( $format ) {
        return 'j F Y';
    }

    public static function email_date_format( $format ) {
        return 'j F Y';
    }

    public static function format_order_date( $date, $order ) {
        if ( is_a( $date, 'WC_DateTime' ) ) {
            $ts = $date->getTimestamp();
            return ferdowsi_jdate( $ts, 'j F Y - H:i' );
        }
        return $date;
    }

    public static function order_column_date( $column, $post_id ) {
        if ( 'order_date' === $column ) {
            $order = wc_get_order( $post_id );
            if ( $order ) {
                echo ferdowsi_jdate( $order->get_date_created()->getTimestamp(), 'j F Y' );
            }
        }
    }

    public static function preview_order_dates( $data, $order ) {
        if ( isset( $data['order_date'] ) ) {
            $data['order_date'] = ferdowsi_jdate( $order->get_date_created()->getTimestamp(), 'j F Y H:i' );
        }
        return $data;
    }

    public static function price_format( $format, $currency_pos ) {
        // Keep default but ensure Persian digits if enabled
        return $format;
    }

    public static function wc_date_format( $format ) {
        return 'Y/m/d';
    }

    public static function customize_checkout_fields( $fields ) {
        if ( isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['placeholder'] = '0912xxxxxxx';
            $fields['billing']['billing_phone']['label'] = 'شماره موبایل';
        }
        // Add national ID field
        $fields['billing']['billing_national_id'] = array(
            'label'       => 'کد ملی',
            'placeholder' => '0012345678',
            'required'    => false,
            'class'       => array( 'form-row-wide' ),
            'priority'    => 25,
        );
        return $fields;
    }

    public static function validate_iranian_fields() {
        $phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';
        $nid   = isset( $_POST['billing_national_id'] ) ? sanitize_text_field( $_POST['billing_national_id'] ) : '';

        // Iranian mobile: 09xxxxxxxxx
        if ( $phone && ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
            wc_add_notice( 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.', 'error' );
        }

        // National ID validation (simple checksum)
        if ( $nid && ! self::is_valid_national_id( $nid ) ) {
            wc_add_notice( 'کد ملی وارد شده معتبر نیست.', 'error' );
        }
    }

    public static function is_valid_national_id( $code ) {
        $code = ferdowsi_to_english_digits( $code );
        if ( ! preg_match( '/^[0-9]{10}$/', $code ) ) return false;
        if ( preg_match( '/^(\d)\1{9}$/', $code ) ) return false; // all same digits
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum += (int) $code[ $i ] * ( 10 - $i );
        }
        $r = $sum % 11;
        $check = (int) $code[9];
        return ( $r < 2 && $check === $r ) || ( $r >= 2 && $check === ( 11 - $r ) );
    }

    public static function email_styles() {
        echo '<style>body, .td { font-family: Tahoma, Vazirmatn, Arial, sans-serif !important; direction: rtl; text-align: right; }</style>';
    }
}
