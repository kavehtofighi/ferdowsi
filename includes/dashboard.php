<?php
/**
 * Admin Dashboard improvements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Dashboard {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_widgets' ) );
        add_filter( 'dashboard_glance_items', array( __CLASS__, 'glance_items' ), 20 );
        add_action( 'admin_footer', array( __CLASS__, 'convert_numbers_in_admin' ) );
    }

    public static function add_widgets() {
        wp_add_dashboard_widget(
            'ferdowsi_calendar_widget',
            'تقویم شمسی فردوسی',
            array( __CLASS__, 'render_calendar_widget' )
        );
    }

    public static function render_calendar_widget() {
        if ( class_exists( 'Ferdowsi_Calendar_Widget' ) ) {
            echo Ferdowsi_Calendar_Widget::render_calendar();
        }
    }

    public static function glance_items( $items ) {
        // Dates already handled by main date filters
        return $items;
    }

    public static function convert_numbers_in_admin() {
        $options = ferdowsi_get_options();
        if ( empty( $options['enable_persian_digits'] ) ) return;
        ?>
        <script>
        (function(){
            // Light conversion for visible numbers in admin tables (non-destructive)
            function toPersian(str) {
                return str.replace(/[0-9]/g, function(d) {
                    return '۰۱۲۳۴۵۶۷۸۹'[d];
                });
            }
            // Only apply to specific safe elements to avoid breaking JS
            document.querySelectorAll('.displaying-num, .subsubsub .count, #dashboard_right_now .b').forEach(function(el){
                if (el && el.textContent) el.textContent = toPersian(el.textContent);
            });
        })();
        </script>
        <?php
    }
}
