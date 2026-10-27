<?php
/**
 * Shamsi Permalinks Support
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Permalinks {

    public static function init() {
        $options = ferdowsi_get_options();
        if ( empty( $options['enable_shamsi_permalinks'] ) ) return;

        add_filter( 'post_link', array( __CLASS__, 'post_link' ), 10, 3 );
        add_filter( 'post_type_link', array( __CLASS__, 'post_link' ), 10, 3 );
        add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ), 20 );
        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'parse_request' ) );

        // Redirect old Gregorian year/month/day to new if possible (basic)
        add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ) );
    }

    public static function post_link( $permalink, $post, $leavename = false ) {
        if ( strpos( $permalink, '%year%' ) === false && ! preg_match( '#/\d{4}/#', $permalink ) ) {
            return $permalink;
        }

        $ts = get_post_time( 'U', false, $post );
        list( $jy, $jm, $jd ) = ferdowsi_gregorian_to_jalali(
            (int) date( 'Y', $ts ),
            (int) date( 'n', $ts ),
            (int) date( 'j', $ts )
        );

        $permalink = str_replace(
            array( '%year%', '%monthnum%', '%day%', date( 'Y', $ts ), date( 'm', $ts ), date( 'd', $ts ) ),
            array( $jy, sprintf( '%02d', $jm ), sprintf( '%02d', $jd ), $jy, sprintf( '%02d', $jm ), sprintf( '%02d', $jd ) ),
            $permalink
        );

        return $permalink;
    }

    public static function add_rewrite_rules() {
        // Support /1405/08/05/post-name/
        add_rewrite_rule(
            '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/?$',
            'index.php?ferdowsi_year=$matches[1]&ferdowsi_month=$matches[2]&ferdowsi_day=$matches[3]&name=$matches[4]',
            'top'
        );
        add_rewrite_rule(
            '([0-9]{4})/([0-9]{1,2})/([^/]+)/?$',
            'index.php?ferdowsi_year=$matches[1]&ferdowsi_month=$matches[2]&name=$matches[3]',
            'top'
        );
    }

    public static function query_vars( $vars ) {
        $vars[] = 'ferdowsi_year';
        $vars[] = 'ferdowsi_month';
        $vars[] = 'ferdowsi_day';
        return $vars;
    }

    public static function parse_request( $query ) {
        if ( ! $query->is_main_query() || is_admin() ) return;

        $jy = get_query_var( 'ferdowsi_year' );
        $jm = get_query_var( 'ferdowsi_month' );
        $jd = get_query_var( 'ferdowsi_day' );

        if ( $jy && $jm ) {
            list( $gy, $gm, $gd ) = ferdowsi_jalali_to_gregorian( (int)$jy, (int)$jm, $jd ? (int)$jd : 1 );
            $query->set( 'year', $gy );
            $query->set( 'monthnum', $gm );
            if ( $jd ) $query->set( 'day', $gd );
        }
    }

    public static function maybe_redirect() {
        // Basic: if someone visits old Gregorian structure and we have Shamsi enabled,
        // we can leave it for now to avoid breaking existing links.
    }
}
