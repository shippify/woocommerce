<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify thankyou class. Handles the Thankyou page action and filters.
 * @since   1.0.0
 * @version 1.2.3
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

		// Check for automatic dispatch

		if ( get_option( 'shippify_instant_dispatch' ) == 'yes' ) {
			$status = $this->create_shippify_task( $order_id );
			if ( false == $status ) {
				echo __( 'Your order was not dispatched instantly. Please, wait for the store admin to dispatch it manually. Thanks you for choosing shippify!', 'woocommerce-shippify' );
			} else {
				echo __( 'Your order has been dispatched! It will soon arrive. Thanks you for choosing shippify!', 'woocommerce-shippify' );
			}
		} else {
			echo __( 'Your order was not dispatched instantly. Please, wait for the store admin to dispatch it manually. Thanks you for choosing shippify!', 'woocommerce-shippify' );
		}
    }

    /**
     * Creates the Shippify Task of a shop order.
     * @param string $order_ir Shop order identifier.
     */
    public function create_shippify_task( $order_id ) {

        $task_endpoint = "https://api.shippify.co/task/new";

        $order = new WC_Order( $order_id );

        // Sender Email
        $sender_mail = get_option( 'shippify_sender_email' );

        // Recipient Information
        $recipient_name = get_post_meta( $order_id, '_billing_first_name', true ) . get_post_meta( $order_id, '_billing_last_name', true ) ;
        $recipient_email = get_post_meta( $order_id, '_billing_email', true );
        $recipient_phone = get_post_meta( $order_id, '_billing_phone', true );

        // Shipping Zone information
        $pickup_warehouse = get_post_meta( $order_id, 'pickup_id', true );
        $pickup_latitude = get_post_meta( $order_id, 'pickup_latitude', true );
        $pickup_longitude = get_post_meta( $order_id, 'pickup_longitude', true );
        $pickup_address = get_post_meta( $order_id, 'pickup_address', true );

        // Delivery Information
        $deliver_lat = get_post_meta( $order_id, 'Latitude', true );
        $deliver_lon = get_post_meta( $order_id, 'Longitude', true );
        $deliver_address = get_post_meta( $order_id, '_billing_address_1', true ) .  get_post_meta( $order_id, '_billing_address_2', true );

        // References
        $note = get_post_meta( $order_id, 'Instructions', true );

        $ref_id = $order_id;

        // Credentials
        $api_id = get_option( 'shippify_id' );
        $api_secret = get_option( 'shippify_secret' );

        // Constructing the items array
        $items = "[";
        foreach ( $order->get_items() as $item_id => $_preproduct ) {
            $_product = $_preproduct->get_product();
            $items = $items . '{"id":"' . $_product->get_id() . '",
                                "name":"' . $_product->get_name() . '",
                                "qty": "' . $_preproduct['quantity'] . '",
                                "size": "' . $this->calculate_product_shippify_size($_product) . '"
                                },';
        }
        $items = substr( $items, 0, -1 ) . ']';

        // Basic Auth
        $wh_args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
            ),
            'method'  => 'GET'
        );

        // If there is defined a warehouse id. Check if valid. Then use the coordinates of that warehouse.
        $pickup_id = '';
        if ( "" != $pickup_warehouse  || isset( $pickup_warehouse ) ) {
            $warehouse_response = wp_remote_get( 'https://api.shippify.co/warehouse/list', $wh_args );
            if ( ! is_wp_error( $warehouse_response ) ) {
                $warehouse_response = json_decode( $warehouse_response['body'], true );
                $warehouse_info = $warehouse_response["warehouses"];
                foreach ( $warehouse_info as $warehouse ) {
                    if ( $warehouse["id"] == $pickup_warehouse ) {
                        $pickup_id = $pickup_warehouse;
                        break;
                    }
                }
            }
        }

        if ( '' == $pickup_id ) {
            $warehouse_to_request = '';
        } else {
            $warehouse_to_request = ',
                "warehouse": "' .  $pickup_id .'"';
        }

        // Checking if Cash on Delivery
        $total_amount = '';
        $payment_method = get_post_meta( $order_id, '_payment_method', true );
        if ( 'cod' == $payment_method ) {
            $order_total = $order->get_total();
            $total_amount = '"total_amount": "' . $order_total . '",';
        }

        // Constructing the POST request
        $request_body = '
        {
            "task" : {
                "products": ' . $items . ',
                "sender" : {
                    "email": "' . $sender_mail . '"
                },
                "recipient": {
                    "name": "' . $recipient_name . '",
                    "email": "' . $recipient_email . '",
                    "phone": "' . $recipient_phone . '"
                },
                "pickup": {
                    "lat": ' . $pickup_latitude . ',
                    "lng": ' . $pickup_longitude . ',
                    "address": "' . $pickup_address . '"'. $warehouse_to_request . '
                },
                '. $total_amount . '
                "deliver": {
                    "lat": ' . $deliver_lat . ',
                    "lng": ' . $deliver_lon . ',
                    "address": "' . $deliver_address . '"
                },
                "ref_id": "' . $ref_id . '",
                "extra": {
                    "note":  "' . $note . '"
                }
            }
        }';

        //Basic Authorization

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret ),
                'Content-Type'  => 'application/json'
            ),
            'method'  => 'POST',
            'body'    => $request_body
        );

        $response = wp_remote_post( $task_endpoint, $args );

        if ( ! is_wp_error( $response ) ) {
        	$response = json_decode( $response['body'], true );

        	if ( isset( $response['id'] ) ) {
	        	update_post_meta( $order_id, '_is_dispatched', 'yes' );
	        	update_post_meta( $order_id, '_shippify_id', $response['id'] );
	        	return true;
        	} else {
        		return false;
        	}

        } else {
        	return false;
        }
    }

    /**
    * Diffuse Logic Algorithm used to calculate Shippify product size based on the product dimensions.
    * @param WC_Product The product to calculate the size.
    */
    public function calculate_product_shippify_size( $product ) {

        $height = $product->get_height();
        $width  = $product->get_width();
        $length = $product->get_length();

        if ( ! isset($height) ||  "" == $height ) {
            return "3";
        }
        if ( ! isset($width) || "" == $width ) {
            return "3";
        }
        if ( ! isset($length) || "" == $length ) {
            return "3";
        }

        $width = floatval( $width );
        $height = floatval( $height );
        $length = floatval( $length );

        $array_size = array( 1, 2, 3, 4, 5 );
        $array_dimensions = array( 50, 80, 120, 150, 150 );
        $radio_membership = 10;
        $dimensions_array = array( 10, 10, 10 );
        $final_percentages = array();

        foreach ( $array_size as $size ) {
            $percentage = 0;
            $max_percentage = 100/3;
            foreach ( $dimensions_array as $dimension ) {
                if  ( $dimension < $array_dimensions[$size-1] ) {
                    $percentage = $percentage + $max_percentage;
                }elseif( $dimension < $array_dimensions[$size-1] + $radio_membership ) {
                    $pre_result = ( 1-( abs( $array_dimensions[$size-1] - $dimension ) / ( 2 * $radio_membership ) ) );
                    $tmp_p = $pre_result < 0 ? 0 : $pre_result;
                    $percentage = $percentage + ( ( ( $pre_result * 100 ) * $max_percentage ) / 100 );
                }else {
                    $percentage = $percentage + 0;
                }
            }
            $final_percentages[] = $percentage;
        }
        $maxs = array_keys( $final_percentages, max( $final_percentages ) );
        return $array_size[$maxs[0]];
    }
}

new WC_Shippify_Thankyou();
