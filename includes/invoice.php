<?php
/**
 * Persian order invoice (printable HTML)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Invoice {
    public static function init() {
        $o = ferdowsi_get_options();
        if ( empty( $o['enable_invoice'] ) ) return;
        add_action( 'woocommerce_account_orders_actions', array( __CLASS__, 'add_invoice_button' ), 10, 2 );
        add_action( 'template_redirect', array( __CLASS__, 'render_invoice' ) );
        add_action( 'woocommerce_admin_order_actions_end', array( __CLASS__, 'admin_button' ) );
    }

    public static function add_invoice_button( $actions, $order ) {
        $actions['ferdowsi_invoice'] = array(
            'url'  => add_query_arg( array( 'ferdowsi_invoice' => $order->get_id(), 'key' => $order->get_order_key() ), home_url('/') ),
            'name' => 'فاکتور',
        );
        return $actions;
    }

    public static function admin_button( $order ) {
        $url = add_query_arg( array( 'ferdowsi_invoice' => $order->get_id(), 'key' => $order->get_order_key() ), home_url('/') );
        echo '<a class="button" target="_blank" href="' . esc_url( $url ) . '">فاکتور</a>';
    }

    public static function render_invoice() {
        if ( empty( $_GET['ferdowsi_invoice'] ) ) return;
        $order_id = absint( $_GET['ferdowsi_invoice'] );
        $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_order_key() !== $key ) {
            wp_die('دسترسی غیرمجاز');
        }
        $date = function_exists('ferdowsi_jdate') ? ferdowsi_jdate( $order->get_date_created()->getTimestamp(), 'j F Y' ) : $order->get_date_created()->date_i18n('Y/m/d');
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>فاکتور #<?php echo $order->get_order_number(); ?></title>
<style>
body{font-family:Tahoma,Vazirmatn,sans-serif;direction:rtl;padding:30px;max-width:800px;margin:0 auto;}
h1{font-size:20px;} table{width:100%;border-collapse:collapse;margin:20px 0;}
th,td{border:1px solid #ccc;padding:8px;text-align:right;} th{background:#f5f5f5;}
.total{font-weight:bold;font-size:16px;} .print{margin-bottom:20px;}
@media print {.print{display:none;}}
</style></head>
<body>
<button class="print" onclick="window.print()">چاپ فاکتور</button>
<h1>فاکتور فروش</h1>
<p>شماره فاکتور: <strong><?php echo esc_html( $order->get_order_number() ); ?></strong></p>
<p>تاریخ: <?php echo esc_html( $date ); ?></p>
<p>خریدار: <?php echo esc_html( $order->get_formatted_billing_full_name() ); ?> — <?php echo esc_html( $order->get_billing_phone() ); ?></p>
<table>
<thead><tr><th>ردیف</th><th>محصول</th><th>تعداد</th><th>مبلغ</th></tr></thead>
<tbody>
<?php $i=1; foreach( $order->get_items() as $item ): ?>
<tr>
<td><?php echo $i++; ?></td>
<td><?php echo esc_html( $item->get_name() ); ?></td>
<td><?php echo $item->get_quantity(); ?></td>
<td><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="total">جمع کل: <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></p>
<p style="margin-top:40px;color:#666;font-size:13px;">با تشکر از خرید شما</p>
</body></html>
        <?php
        exit;
    }
}
