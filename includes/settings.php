<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function ferdowsi_get_default_options() {
    return array(
        'enable_jalali_dates'       => 1,
        'enable_persian_font'       => 1,
        'enable_persian_digits'     => 1,
        'enable_char_conversion'    => 1,
        'enable_currency'           => 1,
        'currency_unit'             => 'toman',
        'week_starts_saturday'      => 1,
        'frontend_font'             => 1,
        'show_gregorian_beside'     => 0,
        'date_format'               => 'j F Y',
        'enable_woocommerce'        => 1,
        'enable_shamsi_permalinks'  => 0,
        'enable_acf'                => 1,
        'enable_gutenberg_font'     => 1,
        'enable_calendar_widget'    => 1,
        'enable_dashboard_widget'   => 1,
        'enable_email_dates'        => 1,
        'enable_admin_numbers'      => 1,
        'enable_search_improve'     => 1,
        'enable_dark_mode'          => 0,
        'enable_export_import'      => 1,
        'enable_mobile_verify'      => 0,
        'payamak_username'          => '',
        'payamak_password'          => '',
        'payamak_from'              => '',
        'mobile_verify_message'     => 'برای ادامه استفاده از سایت، لطفاً شماره موبایل خود را از طریق کد پیامکی تأیید کنید.',
        'enable_zarinpal'           => 0,
        'enable_bitpay'             => 0,
        // New v3
        'enable_order_sms'          => 0,
        'sms_tpl_processing'        => 'سفارش #{order_id} ثبت شد و در حال بررسی است.',
        'sms_tpl_completed'         => 'سفارش #{order_id} با موفقیت تکمیل و ارسال شد. مبلغ: {total}',
        'sms_tpl_cancelled'         => '',
        'enable_otp_login'          => 0,
        'otp_login_replace_wp'      => 0,
        'enable_invoice'            => 1,
        'enable_unique_mobile'      => 0,
        'enable_iran_shipping'      => 0,
        'enable_dual_currency'      => 0,
        'enable_order_export'       => 1,
        'enable_jalali_reports'     => 1,
    );
}

function ferdowsi_get_options() {
    return wp_parse_args( get_option( 'ferdowsi_options', array() ), ferdowsi_get_default_options() );
}
