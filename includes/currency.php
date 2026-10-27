<?php
/**
 * Currency (Toman / Rial) Support
 * Works with WooCommerce if present, and provides general helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ferdowsi_Currency {

    private static $options = array();

    public static function init( $options ) {
        self::$options = $options;

        // WooCommerce support
        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'woocommerce_currency_symbol', array( __CLASS__, 'wc_currency_symbol' ), 10, 2 );
            add_filter( 'woocommerce_currencies', array( __CLASS__, 'add_irr_currency' ) );
            // Optional: change price display suffix
            add_filter( 'woocommerce_get_price_suffix', array( __CLASS__, 'price_suffix' ), 10, 4 );
        }
    }

    public static function wc_currency_symbol( $symbol, $currency ) {
        $unit = self::$options['currency_unit'] ?? 'toman';
        if ( 'IRR' === $currency || 'IRT' === $currency ) {
            if ( 'toman' === $unit ) {
                return 'تومان';
            }
            return 'ریال';
        }
        return $symbol;
    }

    public static function add_irr_currency( $currencies ) {
        $currencies['IRR'] = __( 'ریال ایران', 'ferdowsi' );
        $currencies['IRT'] = __( 'تومان ایران', 'ferdowsi' );
        return $currencies;
    }

    public static function price_suffix( $html, $product, $price, $qty ) {
        // Can add extra if needed
        return $html;
    }

    /**
     * Helper: Format a number as currency
     */
    public static function format( $amount, $force_unit = null ) {
        $unit = $force_unit ?: ( self::$options['currency_unit'] ?? 'toman' );
        $symbol = ( 'toman' === $unit ) ? 'تومان' : 'ریال';
        $options = ferdowsi_get_options();
        $formatted = number_format( (float) $amount );
        if ( ! empty( $options['enable_persian_digits'] ) ) {
            $formatted = ferdowsi_to_persian_digits( $formatted );
        }
        return $formatted . ' ' . $symbol;
    }
}

/**
 * Template tag / helper function
 */
function ferdowsi_format_price( $amount, $unit = null ) {
    return Ferdowsi_Currency::format( $amount, $unit );
}
