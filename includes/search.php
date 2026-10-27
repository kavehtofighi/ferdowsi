<?php
/**
 * Persian search improvements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Search {

    public static function init() {
        add_filter( 'posts_search', array( __CLASS__, 'improve_search' ), 10, 2 );
        add_filter( 'get_search_query', array( __CLASS__, 'normalize_query' ) );
    }

    public static function normalize_query( $query ) {
        // Convert Arabic chars and digits for better matching
        $query = ferdowsi_arabic_to_persian( $query );
        return $query;
    }

    public static function improve_search( $search, $wp_query ) {
        // Basic: ensure Arabic/Persian variants are considered
        // Full implementation would expand query with both variants
        return $search;
    }
}
