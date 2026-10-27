<?php
/**
 * Shamsi Calendar Widget and Archive helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_Widget' ) ) { return; }

class Ferdowsi_Calendar_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ferdowsi_calendar',
            __( 'تقویم شمسی فردوسی', 'ferdowsi' ),
            array( 'description' => __( 'نمایش تقویم ماهانه شمسی', 'ferdowsi' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        echo self::render_calendar();
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'تقویم', 'ferdowsi' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'عنوان:', 'ferdowsi' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }

    public static function render_calendar( $year = null, $month = null ) {
        $now = current_time( 'timestamp' );
        if ( ! $year || ! $month ) {
            list( $jy, $jm, $jd ) = ferdowsi_gregorian_to_jalali(
                (int) date( 'Y', $now ),
                (int) date( 'n', $now ),
                (int) date( 'j', $now )
            );
            $year  = $jy;
            $month = $jm;
        }

        $months = ferdowsi_get_month_names();
        $weekdays = array( 'ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج' ); // شنبه first

        // First day of month in Gregorian
        list( $gy, $gm, $gd ) = ferdowsi_jalali_to_gregorian( $year, $month, 1 );
        $first_ts = mktime( 0, 0, 0, $gm, $gd, $gy );
        $first_w  = (int) date( 'w', $first_ts ); // 0=Sun ... 6=Sat
        // Convert to Saturday-start: Sat=0, Sun=1, ..., Fri=6
        $start_offset = ( $first_w + 1 ) % 7;

        $days_in_month = ( $month <= 6 ) ? 31 : ( ( $month <= 11 ) ? 30 : ( ferdowsi_is_jalali_leap( $year ) ? 30 : 29 ) );

        $html = '<div class="ferdowsi-calendar" style="font-family: Vazirmatn, Tahoma; direction: rtl; text-align: center;">';
        $html .= '<div style="font-weight:bold; margin-bottom:8px;">' . ferdowsi_to_persian_digits( $year . ' ' . $months[ $month ] ) . '</div>';
        $html .= '<table style="width:100%; border-collapse:collapse; font-size:13px;">';
        $html .= '<tr>';
        foreach ( $weekdays as $wd ) {
            $html .= '<th style="padding:4px; background:#f0f0f1;">' . $wd . '</th>';
        }
        $html .= '</tr><tr>';

        // Empty cells
        for ( $i = 0; $i < $start_offset; $i++ ) {
            $html .= '<td></td>';
        }

        $today_j = ferdowsi_gregorian_to_jalali( (int)date('Y',$now), (int)date('n',$now), (int)date('j',$now) );

        for ( $d = 1; $d <= $days_in_month; $d++ ) {
            $is_today = ( $year == $today_j[0] && $month == $today_j[1] && $d == $today_j[2] );
            $style = $is_today ? 'background:#2271b1;color:#fff;border-radius:4px;font-weight:bold;' : '';
            $html .= '<td style="padding:6px;' . $style . '">' . ferdowsi_to_persian_digits( $d ) . '</td>';
            if ( ( $start_offset + $d ) % 7 === 0 && $d < $days_in_month ) {
                $html .= '</tr><tr>';
            }
        }
        $html .= '</tr></table></div>';

        return $html;
    }
}

function ferdowsi_register_widgets() {
    register_widget( 'Ferdowsi_Calendar_Widget' );
}
add_action( 'widgets_init', 'ferdowsi_register_widgets' );

/**
 * Shamsi Monthly Archives
 */
class Ferdowsi_Archives {
    public static function init() {
        add_filter( 'get_archives_link', array( __CLASS__, 'filter_archives_link' ), 10, 6 );
        // Optional shortcode
        add_shortcode( 'ferdowsi_calendar', function() {
            return Ferdowsi_Calendar_Widget::render_calendar();
        } );
    }

    public static function filter_archives_link( $link_html, $url, $text, $format, $before, $after ) {
        // Try to convert English month names if present (basic)
        // For full support more complex rewrite is needed; this is display enhancement
        return $link_html;
    }
}
