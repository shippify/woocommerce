<?php

    /**
    * Plugin Name: WooCommerce Shippify
    * Plugin URI: https://docs.logistics.shippify.co/
    * Description: Adds Shippify shipping methods to your WooCommerce store. 
    * Version: 0.0.1
    * Author: Shippify
    * Author URI: https://docs.logistics.shippify.co/
    * Developer: Leonardo Kuffo
    * Developer URI: http://yourdomain.com/
    * Text Domain: woocommerce-shippify-extension
    * Copyright: © 2016-2017 Shippify.
    * License: GNU General Public License v3.0
    * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Shippify' ) ){

    class WC_Shippify{

        public function __construct() {

            add_action( 'init', array($this, 'start_session'), 1);
            add_filter( 'woocommerce_shipping_methods', array( $this, 'include_shipping_methods'), 2);
            $this->includes();
            add_action( 'woocommerce_shipping_init',  array( $this, 'shipping_method_init'), 1);
        }

        public function start_session() {
            if(!session_id()) {
                //session_start();
            }
        }

        public function include_shipping_methods($methods){
            $methods['shippify'] = 'WC_Shippify_Shipping';
            return $methods;
        }

        public function shipping_method_init(){
            include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-shipping.php';
        }

        public function includes(){
            include_once dirname( __FILE__ ) . '/includes/views/class-wc-shippify-settings.php';
            include_once dirname( __FILE__ ) . '/includes/views/class-wc-shippify-admin-back-office.php';
            include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-order-processing.php';
            include_once dirname( __FILE__ ) . '/includes/class-wc-shippify-checkout.php';
            
            //include_once dirname( __FILE__ ) . '/includes/wc-shippify-ajax-handler.php';
        }
    }
}

new WC_Shippify();

?>
