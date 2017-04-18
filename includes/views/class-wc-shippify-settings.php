<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Shippify_Settings{

    public function __construct() {
        add_filter( 'woocommerce_get_sections_products', array( $this, 'add_shippify_to_settings' ));
        add_filter( 'woocommerce_get_settings_products', array( $this, 'shippify_all_settings' ), 10, 2);       
    }


    public function add_shippify_to_settings($sections){
        $sections['shippify'] = __( 'Shippify', 'text-domain' );
        return $sections;
    }

    public function shippify_all_settings($settings, $current_section){
        if ( $current_section == 'shippify' ) {

            $settings_slider = array();

            $settings_slider[] = array( 
                'name' => __( 'Shippify Settings', 'woocommerce-shippify' ), 
                'type' => 'title', 
                'desc' => __( 'The following options are used to configure Shippify', 'woocommerce-shippify' ), 
                'id' => 'shippify' 
            );

            $settings_slider[] = array(
                'name'     => __( 'APP ID', 'woocommerce-shippify' ),
                'desc_tip' => __( 'APP ID obtained from the admin panel used to request our API', 'woocommerce-shippify' ),
                'id'       => 'shippify_id',
                'type'     => 'text',
                'desc'     => __( '', 'woocommerce-shippify' ),
            );

            $settings_slider[] = array(
                'name'     => __( 'APP SECRET', 'woocommerce-shippify' ),
                'desc_tip' => __( 'APP SECRET obtained from the admin panel used to request our API', 'woocommerce-shippify' ),
                'id'       => 'shippify_secret',
                'type'     => 'text',
                'desc'     => __( '', 'woocommerce-shippify' ),
            );

            $settings_slider[] = array( 
                'type' => 'sectionend', 
                'id' => 'shippify' 
            );

            return $settings_slider;

        }else{

            return $settings;

        }

    }

}

$wcshippifyclass = new WC_Shippify_Settings();

?>