<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify general settings class. 
 * This class provides a settings section in which the admin will be able
 * to provide the APP ID and APP SECRET to access the Shippify API.
 */

class WC_Shippify_Settings{

    public function __construct() {
        add_filter( 'woocommerce_get_sections_api', array( $this, 'add_shippify_to_settings' ));
        add_filter( 'woocommerce_get_settings_api', array( $this, 'shippify_all_settings' ), 10, 2);       
    }

    /**
     * Hooked to woocommerce_get_sections_api,
     * This method creates the Shippify general settings section in the API tab.
     * @param array $sections Contains all the sections.
     */
    public function add_shippify_to_settings($sections){
        $sections['shippify'] = __( 'Shippify', 'woocommerce-shippify' );
        return $sections;
    }

    /**
     * Hooked to woocommerce_get_settings_api,
     * This method creates the fields of the Shippify general settings section.
     * @param array $settings Setting of the current section
     * @param string $current_section Current Section ID
     */
    public function shippify_all_settings($settings, $current_section = ""){

        if ( $_GET['section'] == "shippify" ) {

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

new WC_Shippify_Settings();

?>