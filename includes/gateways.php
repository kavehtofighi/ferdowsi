<?php
/**
 * Payment Gateways: ZarinPal + BitPay.ir for WooCommerce
 * Safe when WooCommerce is not installed.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Gateways {

    public static function init() {
        add_action( 'plugins_loaded', array( __CLASS__, 'load_gateway_classes' ), 15 );
        add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateways' ) );
    }

    public static function load_gateway_classes() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }
        // Include class definitions only when parent class exists
        require_once FERDOWSI_PLUGIN_DIR . 'includes/gateways-classes.php';
    }

    public static function add_gateways( $gateways ) {
        $o = ferdowsi_get_options();
        if ( ! empty( $o['enable_zarinpal'] ) && class_exists( 'WC_Gateway_Ferdowsi_ZarinPal' ) ) {
            $gateways[] = 'WC_Gateway_Ferdowsi_ZarinPal';
        }
        if ( ! empty( $o['enable_bitpay'] ) && class_exists( 'WC_Gateway_Ferdowsi_BitPay' ) ) {
            $gateways[] = 'WC_Gateway_Ferdowsi_BitPay';
        }
        return $gateways;
    }
}
