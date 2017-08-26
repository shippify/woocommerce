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
 * @version 1.2.3
 */

if ( ! class_exists( 'WC_Integration_Demo_Integration' ) ) {

    class WC_Shippify_Integration extends WC_Integration {

        /**
         * Init and hook in the integration.
         */
        public function __construct() {

            $this->id = 'shippify-integration';
            $this->method_title       = __( 'Shippify', 'woocommerce-shippify' );
            $this->method_description = __( 'The following options are used to configure the Shippify Integration.', 'woocommerce-shippify' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->shippify_api_key    = $this->get_option( 'api_key' );
            $this->shippify_api_secret = $this->get_option( 'api_secret' );
            $this->google_api_secret = $this->get_option( 'google_api_secret' );
            $this->shippify_sender_email = $this->get_option( 'sender_email' );
            $this->shippify_sameday = $this->get_option( 'shippify_sameday' );
            $this->shippify_free_shipping = $this->get_option( 'shippify_free_shipping' );
            $this->shippify_instant_dispatch = $this->get_option( 'shippify_instant_dispatch' );

            update_option( 'shippify_id', $this->shippify_api_key );
            update_option( 'shippify_secret', $this->shippify_api_secret );
            update_option( 'google_secret', $this->google_api_secret );
            update_option( 'shippify_sender_email', $this->shippify_sender_email );
            update_option( 'shippify_sameday', $this->shippify_sameday );
            update_option( 'shippify_free_shipping', $this->shippify_free_shipping );
            update_option( 'shippify_instant_dispatch', $this->shippify_instant_dispatch );

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
                    'description'       => __( 'Enter your Shippify APP ID. You can find it in your Shippify Dashboard configurations panel.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'api_secret' => array(
                    'title'             => __( 'APP SECRET', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your Shippify APP ID. You can find it in your Shippify Dashboard configurations panel.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'google_api_secret' => array(
                    'title'             => __( 'GOOGLE API KEY', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your GOOGLE API KEY. You can find it in your GOOGLE MAPS Dashboard configurations panel.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'sender_email' => array(
                    'title'             => __( 'Sender E-mail', 'woocommerce-shippify' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter the e-mail you want to recieve notifications from Shippify. This email is going to be used to create tasks.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'shippify_customizations' => array(
                    'title'   => __( 'Store Customizations', 'woocommerce-shippify' ),
                    'type'    => 'title',
                    'default' => '',
                ),
                'shippify_sameday' => array(
                    'title'             => __( 'Show "Same Day Delivery" label', 'woocommerce-shippify' ),
                    'type'              => 'checkbox',
                    'label'             => ' ',
                    'description'       => __( 'If marked, it will show "Same Day Delivery" label.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => 'yes'
                ),
                'shippify_free_shipping' => array(
                    'title'             => __( 'Store Pays the Delivery', 'woocommerce-shippify' ),
                    'type'              => 'checkbox',
                    'label'             => ' ',
                    'description'       => __( 'If marked, the shipping fare would be charged to the store insted of the final customer. ', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'shippify_instant_dispatch' => array(
                    'title'             => __( 'Instant Dispatch', 'woocommerce-shippify' ),
                    'type'              => 'checkbox',
                    'label'             => ' ',
                    'description'       => __( 'If marked, the order is dispatched inmediatelly after the customer place the order. Please, make sure your store is capable of handling this option.', 'woocommerce-shippify' ),
                    'desc_tip'          => true,
                    'default'           => ''
                )
            );
        }
    }
}
