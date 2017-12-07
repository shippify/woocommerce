<?php

/**
 * Shippify shipping method.
 *
 * @since   1.0.0
 * @version 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


$active_plugins = (array) get_option( 'active_plugins', array() );

// Check for multisite configuration
if ( is_multisite() ) {

    $network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
    $active_plugins            = array_merge( $active_plugins, $network_activated_plugins );

}

// Check if woocommerce is active
if ( in_array( 'woocommerce/woocommerce.php', $active_plugins) )  {

	if ( ! class_exists( 'WC_Shippify_Shipping' ) ) {

		/**
		 *
		 * Shippify shiping method class. Supports shipping-zones and instance settings.
		 * Shipping calculations are based on Shippify API.
		 */
		class WC_Shippify_Shipping extends WC_Shipping_Method {

			public $fare_API = 'https://api.shippify.co/task/fare?';
			public $warehouse_API = 'https://api.shippify.co/warehouse/list';

			/**
			* Initialize Shippify shipping method.
			*
			* @param int $instance_id Shipping zone instance ID.
			*/
			public function __construct( $instance_id = 0 ) {

					$this->id           = 'shippify';
					// $this->method_id    = '';
					$this->enabled 		= 'yes';
					$this->method_title = __( 'Shippify', 'woocommerce-shippify' );
					$this->more_link    = 'http://shippify.co/';
					$this->instance_id        = absint( $instance_id );
					$this->method_description = sprintf( __( '%s is a shipping option.', 'woocommerce-shippify' ), $this->method_title );
					$this->supports           = array(
						'shipping-zones',
						'instance-settings',
					);
					$this->title        = 'Shippify';
					// $this->availability = 'including';
					$this->countries    = array(
						'EC',
						'BR',
						'CL',
						'MX'
					);

					// Load the form fields.
					$this->init_form_fields();
					//$this->init_settings();

					// Set instance options values if they are defined.
					$this->warehouse_id     	= $this->get_instance_option( 'warehouse_id' );
					$this->warehouse_adress     = $this->get_instance_option( 'warehouse_adress' );
					$this->warehouse_latitude   = $this->get_instance_option( 'warehouse_latitude' );
					$this->warehouse_longitude  = $this->get_instance_option( 'warehouse_longitude' );

					/**
					 *
					 * Since (in our shipping method), the checkout page needs to know the information about the instance (to validate task creation),
					 * we use cookies to store the warehouse information concerning the instance, so the checkout can have access to them.
					 */
					if ( 0 != $this->instance_id && ! is_cart()) {

						setcookie( 'warehouse_id', $this->warehouse_id );

						$api_id = get_option( 'shippify_id' );
						$api_secret = get_option( 'shippify_secret' );
			            $args = array(
			                'headers' => array(
			                    'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
			                ),
			                'method'  => 'GET'
			            );

						if ( "" != $this->warehouse_id || isset( $this->warehouse_id ) ) {
							$warehouse_response = wp_remote_get( $this->warehouse_API, $args );
							if ( ! is_wp_error( $warehouse_response ) ) {
								$warehouse_response = json_decode( $warehouse_response['body'], true );
								$warehouse_info = $warehouse_response["warehouses"];
								if ( isset( $warehouse_info ) && '' !== $warehouse_info  ) {
									foreach ( $warehouse_info as $warehouse ) {
										if ( $warehouse["id"] == $this->warehouse_id ) {

											$this->warehouse_longitude = $warehouse["lng"];
											$this->warehouse_latitude = $warehouse["lat"];
											break;
										}
									}
								}
							}
						}
						setcookie( 'warehouse_address', $this->warehouse_adress );
						setcookie( 'warehouse_latitude', $this->warehouse_latitude );
						setcookie( 'warehouse_longitude', $this->warehouse_longitude );
					}

					add_action( 'woocommerce_update_options_shipping_shippify', array( $this, 'process_admin_options' ), 3 );

			}

			/**
			*
			* Admin instance options fields.
			*/
			public function init_form_fields() {
				$this->instance_form_fields = array(
					'warehouse_info' => array(
						'title'   => __( 'Warehouse Information', 'woocommerce-shippify' ),
						'type'    => 'title',
						'default' => '',
					),
					'warehouse_id' => array(
						'title'       => __( 'Warehouse ID', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'The id of the warehouse from which the product is going to be dispatched' ),
						'desc_tip'    => true,
						'default'     => '',
					),
					'warehouse_adress' => array(
						'title'       => __( 'Warehouse Adress', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'The adress of the warehouse from which the product is going to be dispatched' ),
						'desc_tip'    => true,
						'default'     => '',
					),
					'warehouse_latitude' => array(
						'title'       => __( 'Warehouse Latitude', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'The latitude coordinate of the warehouse from which the product is going to be dispatched' ),
						'desc_tip'    => true,
						'default'     => '',
					),
					'warehouse_longitude' => array(
						'title'       => __( 'Warehouse Longitude', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'The longitude coordinate of the warehouse from which the product is going to be dispatched' ),
						'desc_tip'    => true,
						'default'     => '',
					)
				);
			}

			/**
			* Calculates the shipping rate. This calculations are based on the dinamically produced coordinates in checkout, warehouse information
			* of the shippign zone and package information.
			* @param array $package Order package.
			*/
			public function calculate_shipping( $package = array() ) {

				// Check if valid to be calculeted.
				if ( ! in_array( $package['destination']['country'], $this->countries ) ) {
					return;
				}

				// Credentials
				$api_id = get_option( 'shippify_id' );
				$api_secret = get_option( 'shippify_secret' );

				// If integration settings are not configured, method doesnt show.
				if ('' == $api_id || '' == $api_secret){
					return;
				}

				// Basic Authentication
	            $args = array(
	                'headers' => array(
	                    'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
	                ),
	                'method'  => 'GET'
	            );


				$pickup_latitude = isset($_COOKIE["warehouse_latitude"])?$_COOKIE["warehouse_latitude"]:'';
				$pickup_longitude = isset($_COOKIE["warehouse_longitude"])?$_COOKIE["warehouse_longitude"]:'';
				$pickup_id = isset($_COOKIE["warehouse_id"])?$_COOKIE["warehouse_id"]:'';

				// If shipping zone is not configured, method doesnt show.
				if ('' == $pickup_id && '' == $pickup_longitude && '' == $pickup_latitude){
					return;
				}

				// Dinamically generated coordinates
				$delivery_latitude = $_COOKIE["shippify_latitude"];
				$delivery_longitude = $_COOKIE["shippify_longitude"];


				// If there is defined a warehouse id. Check if valid. Then use the coordinates of that warehouse.
				if ( "" != $pickup_id || isset( $pickup_id ) ) {
					$warehouse_response = wp_remote_get( $this->warehouse_API, $args );
					if ( ! is_wp_error( $warehouse_response ) ) {
						$warehouse_response = json_decode( $warehouse_response['body'], true );
						$warehouse_info = $warehouse_response["warehouses"];
						if ( isset( $warehouse_info ) && '' !== $warehouse_info  ) {
							foreach ( $warehouse_info as $warehouse ) {
								if ( $warehouse["id"] == $pickup_id ) {
									$pickup_longitude = $warehouse["lng"];
									$pickup_latitude = $warehouse["lat"];
									break;
								}
							}
						}
					}
				}

				// Constructing the items array
				$items = "[";
				foreach ( $package['contents'] as $item_id => $values ) {
			        $_product = $values['data'];
			        $items = $items . '{"id":"' . "productid" . '",
			        					"name":"' . "productname" . '",
			        					"qty": "' . $values['quantity'] . '",
			        					"size": "' . $this->calculate_product_shippify_size( $_product ) . '",
			        					"price": "' . $_product->get_price() . '"
			        					},';
			    }
			    $items = substr( $items, 0, -1 ) . ']}]';
			    $items = preg_replace('/\s+/', '', $items);

			    // Merging the request parameter
				$data_value = '[{"pickup_location":{"lat":'. $pickup_latitude .',"lng":'. $pickup_longitude . '},"delivery_location":{"lat":' . $delivery_latitude . ',"lng":'. $delivery_longitude .'},"items":' . $items;

				// Final request url
				$request_url = $this->fare_API . "data=" . $data_value;


				$response = wp_remote_get( $request_url, $args );

				if ( is_wp_error( $response ) ) {
					return;
				}else{
	            	$decoded = json_decode( $response['body'], true );
	            	$cost = $decoded['price'];
				}


				if ( is_cart() || 'yes' == get_option( 'shippify_free_shipping' ) ) {
					$cost = 0;
				}

				$rate = array(
					'id' 	=> $this->id,
					'label' => $this->title,
					'cost'  => $cost
				);

				$this->add_rate( $rate );
			}

		    /**
		    * Diffuse Logic Algorithm used to calculate Shippify product size based on the product dimensions.
		    * @param WC_Product The product to calculate the size.
		    */
			public function calculate_product_shippify_size( $product ) {

		        $height = $product->get_height();
		        $width = $product->get_width();
		        $length = $product->get_length();

		        if ( ! isset($height) || "" ==  $height ) {
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
		                    $pre_result = ( 1-( abs( $array_dimensions[$size-1] - $dimension ) / ( 2 * $radio_membership) ) );
		                    $tmp_p = $pre_result < 0 ? 0 : $pre_result;
		                    $percentage = $percentage + ( ( ( $pre_result * 100 ) * $max_percentage ) / 100 );
		                }else{
		                    $percentage = $percentage + 0;
		                }
		            }
		            $final_percentages[] = $percentage;
		        }
		        $maxs = array_keys( $final_percentages, max( $final_percentages ) );
		        return $array_size[$maxs[0]];
		    }
		}
	}
}
