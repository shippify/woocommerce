<?php
/**
 * Plugin Name: WooCommerce Shippify
 * Plugin URI: https://github.com/shippify/woocommerce-shippify/
 * Description: Adds Shippify shipping method to your WooCommerce store.
 * Version: 1.2.5
 * Author: Shippify
 * Author URI: http://www.shippify.co/
 * Developer: Leonardo Kuffo
 * Developer URI: https://github.com/lkuffo/
 * Text Domain: woocommerce-shippify
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * WooCommerce Shippify is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WooCommerce Shippify is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Shippify. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    if ( ! class_exists( 'WC_Shippify' ) ) {

        class WC_Shippify {

            protected static $instance = null;

            public function __construct() {

                if ( class_exists( 'WC_Integration' ) ) {

                    $this->includes();
                    add_filter( 'woocommerce_shipping_methods', array( $this, 'include_shipping_methods' ), 2 );
                    add_action( 'woocommerce_shipping_init',  array( $this, 'shipping_method_init' ), 1 );
                    add_filter( 'woocommerce_integrations', array( $this, 'add_shippify_integration' ) );
                }
            }

            /**
             * Include Shippify integration to WooCommerce.
             *
             * @param  array $integrations Default integrations.
             * @return array
             */
            public function add_shippify_integration( $integrations ) {
                $integrations[] = 'WC_Shippify_Integration';

                return $integrations;
            }

            /**
             * Return an instance of this class.
             *
             * @return object A single instance of this class.
             */
            public static function get_instance() {
                // If the single instance hasn't been set, set it now.
                if ( null === self::$instance ) {
                    self::$instance = new self;
                }

                return self::$instance;
            }

            /**
             * Hooked to filter: woocommerce_shipping_methods,
             * Add our Shippify shipping method to the shipping methods.
             * @param array $methods Contains all the shop shipping methods.
             */
            public function include_shipping_methods( $methods ){
                $methods['shippify'] = 'WC_Shippify_Shipping';
                return $methods;
            }

            /**
             * Hooked to action: woocommerce_shipping_init,
             * Include our shipping method class.
             */
            public function shipping_method_init() {
                include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-shipping.php';
            }

            /**
             *
             * Include every other class.
             */
            public function includes(){
                include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-integration.php';
                include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-admin-back-office.php';
                include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-thankyou.php';
                include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-checkout.php';
            }
        }
    }
    // Get the instance of the plugin
    add_action( 'plugins_loaded', array( 'WC_Shippify', 'get_instance' ) );
    //Load the plugin Text Domain
    add_action('plugins_loaded', 'wan_load_textdomain');
	function wan_load_textdomain() {
	load_plugin_textdomain( 'woocommerce-shippify', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
	}
}
