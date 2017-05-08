<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify general integration settings class. 
 * This class provides a settings section in which the admin will be able
 * to provide the APP ID and APP SECRET to access the Shippify API.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! class_exists( 'WC_Integration_Demo_Integration' ) ) {

    class WC_Shippify_Integration extends WC_Integration {

        /**
         * Init and hook in the integration.
         */
        public function __construct() {

            $this->id = 'shippify-integration';
            $this->method_title       = __( 'Shippify', 'woocommerce-shippify' );
            $this->method_description = __( 'The following options are used to configure the Shippify Integration.', 'woocommerce-integration-demo' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();    

            // Define user set variables.
            $this->shippify_api_key    = $this->get_option( 'api_key' );
            $this->shippify_api_secret = $this->get_option( 'api_secret' );
            $this->shippify_sender_email = $this->get_option( 'sender_email' );

            update_option( 'shippify_id', $this->shippify_api_key );
            update_option( 'shippify_secret', $this->shippify_api_secret );
            update_option( 'shippify_sender_email', $this->shippify_sender_email );

            add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

        }

        /**
         * Initialize Shippify integration settings form fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(

                'api_key' => array(
                    'title'             => __( 'APP ID', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your APP ID. You can find it in your Shippify Dashboard configurations panel.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'api_secret' => array(
                    'title'             => __( 'APP SECRET', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your APP ID. You can find it in your Shippify Dashboard configurations panel.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'sender_email' => array(
                    'title'             => __( 'Sender E-mail', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter the e-mail you want to recieve notifications from Shippify. This email is going to be used to create tasks.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                )
            );
        }
    }
}
