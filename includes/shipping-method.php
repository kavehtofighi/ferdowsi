<?php
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Shipping_Method' ) ) return;

class WC_Ferdowsi_Shipping_Flat extends WC_Shipping_Method {
    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'ferdowsi_flat';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = 'ارسال ایرانی (فردوسی)';
        $this->method_description = 'پست پیشتاز / تیپاکس / پیک (نرخ ثابت قابل تنظیم)';
        $this->supports           = array( 'shipping-zones', 'instance-settings' );
        $this->init();
    }
    public function init() {
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option( 'title', 'پست پیشتاز' );
        $this->cost  = $this->get_option( 'cost', '30000' );
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'title' => array( 'title' => 'عنوان', 'type' => 'text', 'default' => 'پست پیشتاز' ),
            'cost'  => array( 'title' => 'هزینه (تومان)', 'type' => 'text', 'default' => '30000' ),
        );
    }
    public function calculate_shipping( $package = array() ) {
        $this->add_rate( array(
            'id'    => $this->get_rate_id(),
            'label' => $this->title,
            'cost'  => $this->cost,
        ) );
    }
}
