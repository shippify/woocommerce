<?php
/**
 * Shippify shipping method.
 *
 * @package 
 * @since   
 * @version 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shippify shiping method class
 * 
 */

class WC_Shippify_Order_Processing{

    public function __construct() {
		add_action( 'woocommerce_thankyou', array($this, 'display_shippify_order_data'), 20 );
    }

    public function display_shippify_order_data($order_id){?>
	    <h2><?php _e( 'Shippify' ); ?></h2>
	    <table class="shop_table shop_table_responsive additional_info">
	        <tbody>
	            <tr>
	                <th><?php _e( 'Instrucciones:' ); ?></th>
	                <td><?php echo get_post_meta( $order_id, 'Instructions', true ); ?></td>
	            </tr>
	        </tbody>
	    </table>
		<?php 

    }
  
}

new WC_Shippify_Order_Processing();

?>
