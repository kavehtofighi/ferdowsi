<?php
/**
 * Persian Font Loading (Vazirmatn) - Local files
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ferdowsi_Fonts {

    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_fonts' ) );
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_admin_fonts' ) );

        $options = ferdowsi_get_options();
        if ( ! empty( $options['frontend_font'] ) ) {
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_fonts' ) );
        }

        add_action( 'admin_head', array( __CLASS__, 'admin_font_css' ) );
        add_action( 'login_head', array( __CLASS__, 'admin_font_css' ) );
    }

    public static function get_font_url( $file ) {
        return FERDOWSI_PLUGIN_URL . 'assets/fonts/' . $file;
    }

    public static function enqueue_admin_fonts() {
        $css = self::generate_font_face_css();
        wp_register_style( 'ferdowsi-vazirmatn', false );
        wp_enqueue_style( 'ferdowsi-vazirmatn' );
        wp_add_inline_style( 'ferdowsi-vazirmatn', $css );
    }

    public static function enqueue_frontend_fonts() {
        $css = self::generate_font_face_css();
        wp_register_style( 'ferdowsi-vazirmatn-frontend', false );
        wp_enqueue_style( 'ferdowsi-vazirmatn-frontend' );
        wp_add_inline_style( 'ferdowsi-vazirmatn-frontend', $css . '
            body, button, input, select, textarea, .wp-block, .entry-content, .site-title {
                font-family: "Vazirmatn", "Vazirmatn FD", Tahoma, Arial, sans-serif !important;
            }
        ' );
    }

    public static function generate_font_face_css() {
        $base = FERDOWSI_PLUGIN_URL . 'assets/fonts/';
        return '
@font-face {
    font-family: "Vazirmatn";
    src: url("' . $base . 'Vazirmatn-Light.woff2") format("woff2");
    font-weight: 300;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn";
    src: url("' . $base . 'Vazirmatn-Regular.woff2") format("woff2");
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn";
    src: url("' . $base . 'Vazirmatn-Medium.woff2") format("woff2");
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn";
    src: url("' . $base . 'Vazirmatn-Bold.woff2") format("woff2");
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
/* Farsi Digits version for better Persian numerals */
@font-face {
    font-family: "Vazirmatn FD";
    src: url("' . $base . 'Vazirmatn-FD-Regular.woff2") format("woff2");
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn FD";
    src: url("' . $base . 'Vazirmatn-FD-Medium.woff2") format("woff2");
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn FD";
    src: url("' . $base . 'Vazirmatn-FD-Bold.woff2") format("woff2");
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
';
    }

    public static function admin_font_css() {
        echo '<style type="text/css">
            body, #wpadminbar, #adminmenu, .wp-admin, .login, 
            input, select, textarea, button, .button, 
            .wp-core-ui, .media-frame, .components-button,
            h1, h2, h3, h4, h5, h6, p, span, a, label, th, td,
            .wp-list-table, .form-table {
                font-family: "Vazirmatn", "Vazirmatn FD", "Segoe UI", Tahoma, Arial, sans-serif !important;
            }
            #wpadminbar * {
                font-family: "Vazirmatn", Tahoma, sans-serif !important;
            }
        </style>';
    }
}
