<?php
/**
 * Order status SMS notifications via Payamak Panel
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_SMS_Orders {
    public static function init() {
        $o = ferdowsi_get_options();
        if ( empty( $o['enable_order_sms'] ) ) return;
        add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_change' ), 10, 4 );
        add_action( 'woocommerce_new_order', array( __CLASS__, 'on_new_order' ), 10, 1 );
    }

    public static function on_new_order( $order_id ) {
        self::send_for_status( $order_id, 'processing' ); // or pending
    }

    public static function on_status_change( $order_id, $old, $new, $order ) {
        self::send_for_status( $order_id, $new );
    }

    public static function send_for_status( $order_id, $status ) {
        $o = ferdowsi_get_options();
        $map = array(
            'pending'    => $o['sms_tpl_pending'] ?? '',
            'processing' => $o['sms_tpl_processing'] ?? 'سفارش #{order_id} ثبت شد و در حال بررسی است.',
            'on-hold'    => $o['sms_tpl_onhold'] ?? '',
            'completed'  => $o['sms_tpl_completed'] ?? 'سفارش #{order_id} تکمیل و ارسال شد.',
            'cancelled'  => $o['sms_tpl_cancelled'] ?? '',
            'refunded'   => $o['sms_tpl_refunded'] ?? '',
            'failed'     => $o['sms_tpl_failed'] ?? '',
        );
        $tpl = $map[ $status ] ?? '';
        if ( empty( $tpl ) ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        $phone = $order->get_billing_phone();
        if ( ! $phone ) return;
        $phone = preg_replace( '/[^0-9]/', '', ferdowsi_to_english_digits( $phone ) );
        if ( strpos( $phone, '09' ) !== 0 && strlen( $phone ) === 10 ) $phone = '0' . $phone;

        $msg = str_replace(
            array( '{order_id}', '{status}', '{total}', '{name}' ),
            array( $order->get_order_number(), wc_get_order_status_name( $status ), $order->get_formatted_order_total(), $order->get_billing_first_name() ),
            $tpl
        );
        self::send_sms( $phone, $msg );
    }

    public static function send_sms( $to, $text ) {
        $o = ferdowsi_get_options();
        $username = trim( $o['payamak_username'] ?? '' );
        $password = trim( $o['payamak_password'] ?? '' );
        $from     = trim( $o['payamak_from'] ?? '' );
        if ( ! $username || ! $password || ! $from ) return false;

        $url = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
        $body = array(
            'username' => $username,
            'password' => $password,
            'to'       => $to,
            'from'     => $from,
            'text'     => $text,
            'isflash'  => false,
        );
        $res = wp_remote_post( $url, array( 'timeout' => 15, 'body' => $body ) );
        return ! is_wp_error( $res );
    }
}
