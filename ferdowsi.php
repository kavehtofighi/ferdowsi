<?php
/**
 * Plugin Name: فردوسی | Ferdowsi
 * Description: بومی‌سازی کامل وردپرس و ووکامرس برای فارسی‌زبانان — تاریخ شمسی، فونت محلی، OTP موبایل، پیامک سفارش، فاکتور، درگاه زرین‌پال و بیت‌پی و بسیاری امکانات دیگر.
 * Version:     3.0.0
 * Author:      Ferdowsi Team
 * Text Domain: ferdowsi
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FERDOWSI_VERSION', '3.0.0' );
define( 'FERDOWSI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FERDOWSI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FERDOWSI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once FERDOWSI_PLUGIN_DIR . 'includes/jalali.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/settings.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/dates.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/fonts.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/numbers.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/currency.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/widgets.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/admin.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/woocommerce.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/permalinks.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/acf.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/emails.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/dashboard.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/shortcodes.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/search.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/gutenberg.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/tools.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/mobile-verify.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/gateways.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/sms-orders.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/otp-login.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/invoice.php';
require_once FERDOWSI_PLUGIN_DIR . 'includes/extras.php';

class Ferdowsi_Plugin {
    private static $instance = null;
    public static function instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init' ), 5 );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    public function load_textdomain() {
        load_plugin_textdomain( 'ferdowsi', false, dirname( FERDOWSI_PLUGIN_BASENAME ) . '/languages' );
    }
    public function init() {
        $o = ferdowsi_get_options();
        if ( ! empty( $o['enable_jalali_dates'] ) ) {
            Ferdowsi_Dates::init();
            if ( class_exists( 'Ferdowsi_Archives' ) ) Ferdowsi_Archives::init();
        }
        if ( ! empty( $o['enable_persian_font'] ) ) Ferdowsi_Fonts::init();
        if ( ! empty( $o['enable_persian_digits'] ) || ! empty( $o['enable_char_conversion'] ) ) Ferdowsi_Numbers::init( $o );
        if ( ! empty( $o['enable_currency'] ) ) Ferdowsi_Currency::init( $o );
        if ( ! empty( $o['enable_woocommerce'] ) ) Ferdowsi_WooCommerce::init();
        Ferdowsi_Permalinks::init();
        if ( ! empty( $o['enable_acf'] ) ) Ferdowsi_ACF::init();
        if ( ! empty( $o['enable_email_dates'] ) ) Ferdowsi_Emails::init();
        if ( is_admin() ) {
            Ferdowsi_Admin::init();
            if ( ! empty( $o['enable_dashboard_widget'] ) ) Ferdowsi_Dashboard::init();
            Ferdowsi_Tools::init();
        }
        Ferdowsi_Shortcodes::init();
        if ( ! empty( $o['enable_search_improve'] ) ) Ferdowsi_Search::init();
        if ( ! empty( $o['enable_gutenberg_font'] ) ) Ferdowsi_Gutenberg::init();
        if ( ! empty( $o['enable_mobile_verify'] ) ) Ferdowsi_Mobile_Verify::init();
        Ferdowsi_Gateways::init();
        Ferdowsi_SMS_Orders::init();
        Ferdowsi_OTP_Login::init();
        Ferdowsi_Invoice::init();
        Ferdowsi_Extras::init();

        if ( ! empty( $o['week_starts_saturday'] ) ) {
            add_filter( 'start_of_week', function() { return 6; } );
        }
        add_filter( 'body_class', function( $c ) { $c[] = 'ferdowsi-rtl'; $c[] = 'rtl'; return $c; } );
        add_filter( 'admin_body_class', function( $c ) { return $c . ' ferdowsi-admin-rtl'; } );
    }
    public function activate() {
        if ( ! get_option( 'ferdowsi_options' ) ) {
            update_option( 'ferdowsi_options', ferdowsi_get_default_options() );
        }
        flush_rewrite_rules();
    }
    public function deactivate() { flush_rewrite_rules(); }
}
Ferdowsi_Plugin::instance();
