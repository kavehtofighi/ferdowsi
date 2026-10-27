<?php
/**
 * Tools: Export/Import settings, Dark mode, misc
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Tools {

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'handle_export_import' ) );
        add_action( 'admin_head', array( __CLASS__, 'dark_mode_css' ) );
    }

    public static function handle_export_import() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Export
        if ( isset( $_GET['ferdowsi_export'] ) && check_admin_referer( 'ferdowsi_export' ) ) {
            $options = ferdowsi_get_options();
            header( 'Content-Type: application/json' );
            header( 'Content-Disposition: attachment; filename=ferdowsi-settings-' . date( 'Y-m-d' ) . '.json' );
            echo wp_json_encode( $options, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            exit;
        }

        // Import
        if ( isset( $_POST['ferdowsi_import'] ) && check_admin_referer( 'ferdowsi_import' ) ) {
            if ( ! empty( $_FILES['ferdowsi_import_file']['tmp_name'] ) ) {
                $content = file_get_contents( $_FILES['ferdowsi_import_file']['tmp_name'] );
                $data = json_decode( $content, true );
                if ( is_array( $data ) ) {
                    update_option( 'ferdowsi_options', $data );
                    add_settings_error( 'ferdowsi', 'imported', 'تنظیمات با موفقیت وارد شد.', 'success' );
                }
            }
        }
    }

    public static function dark_mode_css() {
        $options = ferdowsi_get_options();
        if ( empty( $options['enable_dark_mode'] ) ) return;
        ?>
        <style>
        /* Simple dark mode friendly overrides for admin when system prefers dark or forced */
        @media (prefers-color-scheme: dark) {
            body.wp-admin {
                /* Keep font, improve contrast slightly */
            }
        }
        body.ferdowsi-dark-mode {
            /* Users can add class via filter if needed */
        }
        </style>
        <?php
    }
}
