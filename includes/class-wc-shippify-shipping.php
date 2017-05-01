<?php 
//session_start();
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

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Shippify_Shipping' ) ) {

		class WC_Shippify_Shipping extends WC_Shipping_Method {


			public $fare_API = 'https://api.shippify.co/task/fare?';
			public $warehouse_API = 'https://api.shippify.co/warehouse/list';

			/*
			* Initialize Shippify shipping method.
			*
			* @param int $instance_id Shipping zone instance ID.
			*/
			public function __construct( $instance_id = 0 ) {

				//if ($instance_id != 0){


					$this->id           = 'shippify';
					$this->method_id    = 
					$this->enabled = 'yes';
					$this->method_title = __( 'Shippify', 'woocommerce-shippify' );
					//$this->more_link    = 'http://shippify.co/';		
					$this->instance_id        = absint( $instance_id );
					$this->method_description = sprintf( __( '%s is a shipping method.', 'woocommerce-shippify' ), $this->method_title );
					$this->supports           = array(
						'shipping-zones',
						'instance-settings',
					);
					$this->title              = 'Shippify';
					$this->availability = 'including';
					$this->countries = array(
						'EC',
						'BR',
						'CL',
						'MX'
					);


					//var_dump($this->warehouse_id);

					// Load the form fields.

					$this->init_form_fields(); 
                	//$this->init_settings();
					//var_dump($this->instance_id);


					$this->warehouse_id     	= $this->get_instance_option( 'warehouse_id' );
					$this->warehouse_adress     = $this->get_instance_option( 'warehouse_adress' );
					$this->warehouse_latitude   = $this->get_instance_option( 'warehouse_latitude' );
					$this->warehouse_longitude  = $this->get_instance_option( 'warehouse_longitude' );

					if ($this->instance_id != 0){
						$_SESSION["shippify_instance_settings"] = array(
								'warehouse_id' => 			$this->warehouse_id,
								'warehouse_address' =>		$this->warehouse_adress, 
								'warehouse_latitude' =>		$this->warehouse_latitude,
								'warehouse_longitude' =>	$this->warehouse_longitude
						);						
					}



					//if ($this->instance_id == $_GET['instance_id']){
					add_action( 'woocommerce_update_options_shipping_shippify', array($this, 'process_admin_options' ), 3 );	
					//}

					
			}

			/**
			* Admin options fields.
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
			* Calculates the shipping rate.
			*
			* @param array $package Order package.
			*/
			public function calculate_shipping( $package = array() ) {

				session_start();
				

				// Check if valid to be calculeted.
				//if ( '' === $package['destination']['postcode'] || 'BR' !== $package['destination']['country'] ) {
				//	return;
				//}

				// Check for shipping classes.
				//if ( ! $this->has_only_selected_shipping_class( $package ) ) {
				//	return;
				//}

				$api_id = get_option('shippify_id');
				$api_secret = get_option('shippify_secret');

	            $args = array(
	                'headers' => array(
	                    'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
	                ),
	                'method'  => 'GET'
	            );                  

				$pickup_latitude = $_SESSION['shippify_instance_settings']["warehouse_latitude"];
				$pickup_longitude = $_SESSION['shippify_instance_settings']["warehouse_longitude"];

				$pickup_id = $_SESSION['shippify_instance_settings']["warehouse_id"];

				$delivery_latitude = $_SESSION["shippify_latitude"];
				$delivery_longitude = $_SESSION["shippify_longitude"];
				//var_dump($pickup_id);
				if ($pickup_id != "" || isset($pickup_id)){
					$warehouse_response = wp_remote_get($this->warehouse_API, $args);
					if (!is_wp_error($warehouse_response)){
						$warehouse_response = json_decode($warehouse_response['body'], true);
						$warehouse_info = $warehouse_response["warehouses"];
						foreach ($warehouse_info as $warehouse){
							if ($warehouse["id"] == $pickup_id){
								$pickup_longitude = $warehouse["lng"];
								$pickup_latitude = $warehouse["lat"];	
								break;							
							}
						}
					}
				}

				$items = "[";
				foreach ( $package['contents'] as $item_id => $values ) { 
			        $_product = $values['data']; 
			        $items = $items . '{"id":"' . $_product->get_id() . '", 
			        					"name":"' . $_product->get_name() . '", 
			        					"qty": "' . $values['quantity'] . '", 
			        					"size": "' . $this->calculate_product_shippify_size($_product) . '", 
			        					"price": "' . $_product->get_price() . '"
			        					},';
			    }
			    $items = substr($items, 0, -1) . ']}]';


				$data_value = '[{"pickup_location":{"lat":'. $pickup_latitude .',"lng":'. $pickup_longitude . '},"delivery_location":{"lat":' . $delivery_latitude . ',"lng":'. $delivery_longitude .'},"items":' . $items;


				$request_url = $this->fare_API . "data=" . $data_value;


				$response = wp_remote_get( $request_url, $args );

				if (is_wp_error($response)){
					return;
				}else{
	            	$decoded = json_decode($response['body'], true);
	            	$cost = $decoded['price'];					
				}
				if (is_cart()){
					$cost = 0;
				}
				
				$rate = array(
					'id' => $this->id,
					'label' => $this->title,
					'cost' => $cost
				);
				
				$this->add_rate($rate);
			}


			public function calculate_product_shippify_size($product){

		        $height = $product->get_height();
		        $width = $product->get_width();
		        $length = $product->get_length();

		        if (!isset($height) || $height == ""){
		            return "3";
		        }
		        if (!isset($width) || $width == ""){
		            return "3";
		        }
		        if (!isset($length) || $length == ""){
		            return "3";
		        }

		        $width = floatval($width);
		        $height = floatval($height);
		        $length = floatval($length);

		        $array_size = array(1,2,3,4,5); 
		        $array_dimensions = array(50,80,120,150,150);
		        $radio_membership = 10;
		        $dimensions_array = array(10, 10, 10);
		        $final_percentages = array();

		        foreach ($array_size as $size){
		            $percentage = 0;
		            $max_percentage = 100/3;
		            foreach ($dimensions_array as $dimension) {
		                if  ($dimension < $array_dimensions[$size-1]){
		                    $percentage = $percentage + $max_percentage;
		                }elseif($dimension < $array_dimensions[$size-1] + $radio_membership){
		                    $pre_result = (1-(abs($array_dimensions[$size-1] - $dimension) / (2 * $radio_membership)));
		                    $tmp_p = $pre_result < 0 ? 0 : $pre_result;
		                    $percentage = $percentage + ((($pre_result * 100) * $max_percentage) / 100);
		                }else{
		                    $percentage = $percentage + 0;
		                }
		            }
		            $final_percentages[] = $percentage;
		        }
		        $maxs = array_keys($final_percentages, max($final_percentages));
		        return $array_size[$maxs[0]];
		    }
		}
	}
}