<?php
/**
 * Extra features: unique validation, shipping, dual currency, reports, export
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Extras {
    public static function init() {
        $o = ferdowsi_get_options();

        if ( ! empty( $o['enable_unique_mobile'] ) ) {
            add_filter( 'woocommerce_checkout_process', array( __CLASS__, 'check_unique_mobile' ) );
            add_action( 'user_register', array( __CLASS__, 'check_unique_on_register' ), 10, 1 );
        }

        if ( ! empty( $o['enable_iran_shipping'] ) ) {
            add_action( 'woocommerce_shipping_init', array( __CLASS__, 'shipping_init' ) );
            add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'add_shipping_methods' ) );
        }

        if ( ! empty( $o['enable_dual_currency'] ) ) {
            add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'dual_price' ), 20, 2 );
        }

        if ( ! empty( $o['enable_order_export'] ) && is_admin() ) {
            add_action( 'admin_footer-edit.php', array( __CLASS__, 'export_button' ) );
            add_action( 'admin_init', array( __CLASS__, 'handle_export' ) );
        }

        if ( ! empty( $o['enable_jalali_reports'] ) && is_admin() ) {
            add_action( 'wp_dashboard_setup', array( __CLASS__, 'report_widget' ) );
        }
    }

    public static function check_unique_mobile() {
        $phone = isset($_POST['billing_phone']) ? ferdowsi_to_english_digits( sanitize_text_field($_POST['billing_phone']) ) : '';
        if ( ! $phone ) return;
        $users = get_users( array( 'meta_key' => 'ferdowsi_mobile', 'meta_value' => $phone, 'number' => 1 ) );
        if ( $users && ( ! is_user_logged_in() || $users[0]->ID != get_current_user_id() ) ) {
            wc_add_notice( 'این شماره موبایل قبلاً ثبت شده است.', 'error' );
        }
        $nid = isset($_POST['billing_national_id']) ? ferdowsi_to_english_digits( sanitize_text_field($_POST['billing_national_id']) ) : '';
        if ( $nid ) {
            $users = get_users( array( 'meta_key' => 'billing_national_id', 'meta_value' => $nid, 'number' => 1 ) );
            // simple check; store meta on order/user if needed
        }
    }

    public static function check_unique_on_register( $user_id ) {
        // placeholder
    }

    public static function shipping_init() {
        require_once FERDOWSI_PLUGIN_DIR . 'includes/shipping-method.php';
    }

    public static function add_shipping_methods( $methods ) {
        $methods['ferdowsi_flat'] = 'WC_Ferdowsi_Shipping_Flat';
        return $methods;
    }

    public static function dual_price( $html, $product ) {
        $price = (float) $product->get_price();
        if ( $price <= 0 ) return $html;
        $toman = number_format( $price );
        $rial  = number_format( $price * 10 );
        $o = ferdowsi_get_options();
        if ( ! empty( $o['enable_persian_digits'] ) ) {
            $toman = ferdowsi_to_persian_digits( $toman );
            $rial  = ferdowsi_to_persian_digits( $rial );
        }
        return $html . ' <small style="color:#666;">(' . $rial . ' ریال)</small>';
    }

    public static function export_button() {
        global $typenow;
        if ( $typenow !== 'shop_order' && !(isset($_GET['page']) && $_GET['page']==='wc-orders') ) return;
        $url = wp_nonce_url( admin_url('admin.php?ferdowsi_export_orders=1'), 'ferdowsi_export_orders' );
        echo '<script>jQuery(function($){$(".wrap h1").first().after(\'<a href="' . esc_url($url) . '" class="page-title-action">خروجی اکسل شمسی</a>\');});</script>';
    }

    public static function handle_export() {
        if ( empty($_GET['ferdowsi_export_orders']) || ! current_user_can('manage_woocommerce') ) return;
        check_admin_referer('ferdowsi_export_orders');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders-jalali-' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF"; // BOM
        $out = fopen('php://output', 'w');
        fputcsv( $out, array( 'شماره سفارش', 'تاریخ شمسی', 'وضعیت', 'نام', 'موبایل', 'مبلغ' ) );
        $orders = wc_get_orders( array( 'limit' => 500, 'orderby' => 'date', 'order' => 'DESC' ) );
        foreach ( $orders as $order ) {
            $ts = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time();
            $jdate = function_exists('ferdowsi_jdate') ? ferdowsi_jdate( $ts, 'Y/m/d' ) : date('Y-m-d', $ts);
            fputcsv( $out, array(
                $order->get_order_number(),
                $jdate,
                $order->get_status(),
                $order->get_formatted_billing_full_name(),
                $order->get_billing_phone(),
                $order->get_total(),
            ) );
        }
        fclose($out);
        exit;
    }

    public static function report_widget() {
        wp_add_dashboard_widget( 'ferdowsi_sales_report', 'گزارش فروش شمسی (فردوسی)', array( __CLASS__, 'render_report' ) );
    }

    public static function render_report() {
        if ( ! class_exists('WooCommerce') ) { echo 'ووکامرس فعال نیست.'; return; }
        $orders = wc_get_orders( array( 'limit' => 10, 'status' => array('completed','processing'), 'orderby'=>'date','order'=>'DESC' ) );
        echo '<table class="widefat"><thead><tr><th>سفارش</th><th>تاریخ</th><th>مبلغ</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            $ts = $order->get_date_created()->getTimestamp();
            $j = function_exists('ferdowsi_jdate') ? ferdowsi_jdate($ts,'j F Y') : '';
            echo '<tr><td>#' . esc_html($order->get_order_number()) . '</td><td>' . esc_html($j) . '</td><td>' . wp_kses_post($order->get_formatted_order_total()) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
