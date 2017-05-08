<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify thankyou class. Handles the Thankyou page action and filters. 
 * @since   1.0.0
 * @version 1.0.0
 */

class WC_Shippify_Thankyou {

    public function __construct() {
		add_action( 'woocommerce_thankyou', array( $this, 'display_shippify_order_data' ), 20 );
    }

    public function display_shippify_order_data( $order_id ) {

    	?>
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

new WC_Shippify_Thankyou();