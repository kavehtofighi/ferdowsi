<?php
/**
 * Gutenberg / Block Editor enhancements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Gutenberg {

    public static function init() {
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
        // Date display in editor is mostly handled by PHP filters already
    }

    public static function editor_assets() {
        $options = ferdowsi_get_options();
        if ( empty( $options['enable_persian_font'] ) ) return;

        // Inject font into block editor
        $css = Ferdowsi_Fonts::generate_font_face_css();
        wp_register_style( 'ferdowsi-editor-font', false );
        wp_enqueue_style( 'ferdowsi-editor-font' );
        wp_add_inline_style( 'ferdowsi-editor-font', $css . '
            .editor-styles-wrapper, .block-editor-writing-flow,
            .components-button, .components-base-control {
                font-family: "Vazirmatn", "Vazirmatn FD", Tahoma, sans-serif !important;
            }
        ' );
    }
}
