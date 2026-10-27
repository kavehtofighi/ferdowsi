<?php
/**
 * WooCommerce Gateway Class Definitions
 * This file is only included when WC_Payment_Gateway exists.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
}

/**
 * ZarinPal Gateway
 */
class WC_Gateway_Ferdowsi_ZarinPal extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'ferdowsi_zarinpal';
        $this->method_title       = 'زرین‌پال (فردوسی)';
        $this->method_description = 'پرداخت امن از طریق درگاه زرین‌پال';
        $this->has_fields         = false;
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title', 'پرداخت آنلاین زرین‌پال' );
        $this->description = $this->get_option( 'description', 'پرداخت امن با کارت‌های عضو شتاب' );
        $this->merchant_id = $this->get_option( 'merchant_id' );
        $this->sandbox     = 'yes' === $this->get_option( 'sandbox', 'no' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_ferdowsi_zarinpal', array( $this, 'callback_handler' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'فعال‌سازی',
                'type'    => 'checkbox',
                'label'   => 'فعال کردن درگاه زرین‌پال',
                'default' => 'no',
            ),
            'title' => array(
                'title'   => 'عنوان',
                'type'    => 'text',
                'default' => 'پرداخت آنلاین زرین‌پال',
            ),
            'description' => array(
                'title'   => 'توضیحات',
                'type'    => 'textarea',
                'default' => 'پرداخت امن با کلیه کارت‌های عضو شتاب',
            ),
            'merchant_id' => array(
                'title'       => 'مرچنت کد (Merchant ID)',
                'type'        => 'text',
                'description' => 'کد ۳۶ کاراکتری از پنل زرین‌پال',
            ),
            'sandbox' => array(
                'title'   => 'حالت آزمایشی (Sandbox)',
                'type'    => 'checkbox',
                'label'   => 'استفاده از درگاه آزمایشی',
                'default' => 'no',
            ),
        );
    }

    public function process_payment( $order_id ) {
        $order  = wc_get_order( $order_id );
        $amount = (int) $order->get_total();
        $o = ferdowsi_get_options();
        if ( ( $o['currency_unit'] ?? 'toman' ) === 'toman' ) {
            $amount = $amount * 10;
        }

        $callback = add_query_arg( array(
            'wc-api'   => 'WC_Gateway_Ferdowsi_ZarinPal',
            'order_id' => $order_id,
        ), home_url( '/' ) );

        $base = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/'
            : 'https://payment.zarinpal.com/pg/v4/payment/';

        $body = array(
            'merchant_id'  => $this->merchant_id,
            'amount'       => $amount,
            'callback_url' => $callback,
            'description'  => 'پرداخت سفارش #' . $order->get_order_number(),
            'metadata'     => array(
                'order_id' => (string) $order_id,
                'mobile'   => $order->get_billing_phone(),
                'email'    => $order->get_billing_email(),
            ),
        );

        $response = wp_remote_post( $base . 'request.json', array(
            'timeout' => 20,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            wc_add_notice( 'خطا در اتصال به زرین‌پال: ' . $response->get_error_message(), 'error' );
            return array( 'result' => 'failure' );
        }

        $result = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $result['data']['code'] ) && (int) $result['data']['code'] === 100 ) {
            $authority = $result['data']['authority'];
            $order->update_meta_data( '_zarinpal_authority', $authority );
            $order->save();

            $start = $this->sandbox
                ? 'https://sandbox.zarinpal.com/pg/StartPay/'
                : 'https://payment.zarinpal.com/pg/StartPay/';

            return array(
                'result'   => 'success',
                'redirect' => $start . $authority,
            );
        }

        $msg = isset( $result['errors']['message'] ) ? $result['errors']['message'] : ( isset( $result['errors'][0]['message'] ) ? $result['errors'][0]['message'] : 'خطای نامشخص از زرین‌پال' );
        wc_add_notice( 'زرین‌پال: ' . $msg, 'error' );
        return array( 'result' => 'failure' );
    }

    public function callback_handler() {
        $order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $authority = isset( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( $_GET['Authority'] ) ) : '';
        $status    = isset( $_GET['Status'] ) ? sanitize_text_field( wp_unslash( $_GET['Status'] ) ) : '';

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( 'سفارش یافت نشد.' );
        }

        if ( $status !== 'OK' || empty( $authority ) ) {
            $order->update_status( 'failed', 'پرداخت توسط کاربر لغو شد یا ناموفق بود.' );
            wp_redirect( $order->get_cancel_order_url() );
            exit;
        }

        $stored = $order->get_meta( '_zarinpal_authority' );
        if ( $stored && $stored !== $authority ) {
            $order->update_status( 'failed', 'Authority نامعتبر.' );
            wp_redirect( $order->get_cancel_order_url() );
            exit;
        }

        $amount = (int) $order->get_total();
        $o = ferdowsi_get_options();
        if ( ( $o['currency_unit'] ?? 'toman' ) === 'toman' ) {
            $amount = $amount * 10;
        }

        $base = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/'
            : 'https://payment.zarinpal.com/pg/v4/payment/';

        $body = array(
            'merchant_id' => $this->merchant_id,
            'amount'      => $amount,
            'authority'   => $authority,
        );

        $response = wp_remote_post( $base . 'verify.json', array(
            'timeout' => 20,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
        ) );

        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        $code   = isset( $result['data']['code'] ) ? $result['data']['code'] : 0;

        if ( in_array( (int) $code, array( 100, 101 ), true ) ) {
            $ref_id = isset( $result['data']['ref_id'] ) ? $result['data']['ref_id'] : '';
            $order->payment_complete( $ref_id );
            $order->add_order_note( 'پرداخت موفق زرین‌پال. کد پیگیری: ' . $ref_id );
            wp_redirect( $this->get_return_url( $order ) );
        } else {
            $order->update_status( 'failed', 'تأیید پرداخت زرین‌پال ناموفق بود.' );
            wp_redirect( $order->get_cancel_order_url() );
        }
        exit;
    }
}

/**
 * BitPay.ir Gateway
 */
class WC_Gateway_Ferdowsi_BitPay extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'ferdowsi_bitpay';
        $this->method_title       = 'بیت‌پی (فردوسی)';
        $this->method_description = 'پرداخت از طریق درگاه بیت‌پی';
        $this->has_fields         = false;
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title', 'پرداخت آنلاین بیت‌پی' );
        $this->description = $this->get_option( 'description', 'پرداخت امن با بیت‌پی' );
        $this->api_key     = $this->get_option( 'api_key' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_ferdowsi_bitpay', array( $this, 'callback_handler' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'فعال‌سازی',
                'type'    => 'checkbox',
                'label'   => 'فعال کردن درگاه بیت‌پی',
                'default' => 'no',
            ),
            'title' => array(
                'title'   => 'عنوان',
                'type'    => 'text',
                'default' => 'پرداخت آنلاین بیت‌پی',
            ),
            'description' => array(
                'title'   => 'توضیحات',
                'type'    => 'textarea',
                'default' => 'پرداخت امن از طریق بیت‌پی',
            ),
            'api_key' => array(
                'title'       => 'API Key',
                'type'        => 'text',
                'description' => 'کلید API از پنل بیت‌پی (bitpay.ir)',
            ),
        );
    }

    public function process_payment( $order_id ) {
        $order  = wc_get_order( $order_id );
        $amount = (int) $order->get_total();
        $o = ferdowsi_get_options();
        if ( ( $o['currency_unit'] ?? 'toman' ) === 'toman' ) {
            $amount = $amount * 10;
        }

        $redirect = add_query_arg( array(
            'wc-api'   => 'WC_Gateway_Ferdowsi_BitPay',
            'order_id' => $order_id,
        ), home_url( '/' ) );

        $url  = 'https://bitpay.ir/payment/gateway-send';
        $body = array(
            'api'         => $this->api_key,
            'amount'      => $amount,
            'redirect'    => $redirect,
            'factorId'    => $order_id,
            'name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email'       => $order->get_billing_email(),
            'description' => 'سفارش #' . $order->get_order_number(),
        );

        $response = wp_remote_post( $url, array(
            'timeout' => 20,
            'body'    => $body,
        ) );

        if ( is_wp_error( $response ) ) {
            wc_add_notice( 'خطا در اتصال به بیت‌پی: ' . $response->get_error_message(), 'error' );
            return array( 'result' => 'failure' );
        }

        $id_get = trim( wp_remote_retrieve_body( $response ) );

        if ( is_numeric( $id_get ) && (int) $id_get > 0 ) {
            $order->update_meta_data( '_bitpay_id_get', $id_get );
            $order->save();
            return array(
                'result'   => 'success',
                'redirect' => 'https://bitpay.ir/payment/gateway-' . $id_get . '-get',
            );
        }

        wc_add_notice( 'بیت‌پی: خطا در ایجاد تراکنش (' . esc_html( $id_get ) . ')', 'error' );
        return array( 'result' => 'failure' );
    }

    public function callback_handler() {
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $trans_id = isset( $_GET['trans_id'] ) ? sanitize_text_field( wp_unslash( $_GET['trans_id'] ) ) : '';
        $id_get   = isset( $_GET['id_get'] ) ? sanitize_text_field( wp_unslash( $_GET['id_get'] ) ) : '';

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( 'سفارش یافت نشد.' );
        }

        $stored = $order->get_meta( '_bitpay_id_get' );
        if ( $stored && (string) $stored !== (string) $id_get ) {
            $order->update_status( 'failed', 'شناسه تراکنش نامعتبر.' );
            wp_redirect( $order->get_cancel_order_url() );
            exit;
        }

        $verify_url = 'https://bitpay.ir/payment/gateway-result-second';
        $body = array(
            'api'      => $this->api_key,
            'trans_id' => $trans_id,
            'id_get'   => $id_get,
        );

        $response = wp_remote_post( $verify_url, array(
            'timeout' => 20,
            'body'    => $body,
        ) );

        $result = trim( wp_remote_retrieve_body( $response ) );

        if ( $result == '1' || ( is_numeric( $result ) && (int) $result > 0 ) ) {
            $order->payment_complete( $trans_id );
            $order->add_order_note( 'پرداخت موفق بیت‌پی. کد پیگیری: ' . $trans_id );
            wp_redirect( $this->get_return_url( $order ) );
        } else {
            $order->update_status( 'failed', 'تأیید پرداخت بیت‌پی ناموفق بود. پاسخ: ' . $result );
            wp_redirect( $order->get_cancel_order_url() );
        }
        exit;
    }
}
