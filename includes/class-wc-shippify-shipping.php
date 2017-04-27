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

			/*
			* Initialize Shippify shipping method.
			*
			* @param int $instance_id Shipping zone instance ID.
			*/
			public function __construct( $instance_id = 0 ) {
				$this->id           = 'shippify';
				$this->method_title = __( 'Shippify', 'woocommerce-shippify' );
				$this->more_link    = 'http://shippify.co/';		
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

				// Load the form fields.
				$this->init();

				// Define user set variables.
				
				$this->enabled            = $this->get_option('enabled');
				$this->origin_postcode    = $this->get_option('origin_postcode');
				$this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1' );
				$this->show_delivery_time = $this->get_option( 'show_delivery_time' );
				$this->additional_time    = $this->get_option( 'additional_time' );
				$this->warehouse_adress     = $this->get_option( 'warehouse_adress' );
				$this->warehouse_latitude      = $this->get_option( 'warehouse_latitude' );
				$this->warehouse_longitude     = $this->get_option( 'warehouse_longitude' );
				$this->debug              = $this->get_option( 'debug' );

				$this->init_settings(); 

				add_action( 'woocommerce_update_options', array($this, 'process_admin_options' ) );


			}


			public function init(){
                $this->init_form_fields(); 
			}

			public function admin_options(){
				 ?>
				<h2><?php _e('Shippify','woocommerce'); ?></h2>
				<table class="form-table">
				<?php $this->generate_settings_html(); ?>
				</table> <?php
			}

			/**
			* Get log.
			*
			* @return string
			*/
			protected function get_log_link() {
				return ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'View logs.', 'woocommerce-shippify' ) . '</a>';
			}

			/**
			* Get shipping classes options.
			*
			* @return array
			*/
			protected function get_shipping_classes_options() {
				$shipping_classes = WC()->shipping->get_shipping_classes();
				$options          = array(
					'-1' => __( 'Any Shipping Class', 'woocommerce-shippify' ),
					'0'  => __( 'No Shipping Class', 'woocommerce-shippify' ),
				);

				if ( ! empty( $shipping_classes ) ) {
					$options += wp_list_pluck( $shipping_classes, 'name', 'term_id' );
				}

				return $options;
			}

			/**
			* Admin options fields.
			*/
			public function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'woocommerce-shippify' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable this shipping method', 'woocommerce-shippify' ),
						'default' => 'yes',
					),
					'Base WareHouse Configuration' => array(
						'title'   => __( 'Behavior Options', 'woocommerce-shippify' ),
						'type'    => 'title',
						'default' => '',
					),
					'origin_postcode' => array(
						'title'       => __( 'Origin Postcode', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'The postcode of the location your packages are delivered from.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'placeholder' => '00000-000',
						'default'     => '',
					),
					'shipping_class_id' => array(
						'title'       => __( 'Shipping Class', 'woocommerce-shippify' ),
						'type'        => 'select',
						'description' => __( 'If necessary, select a shipping class to apply this method.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => '',
						'class'       => 'wc-enhanced-select',
						'options'     => $this->get_shipping_classes_options(),
					),
					'show_delivery_time' => array(
						'title'       => __( 'Delivery Time', 'woocommerce-shippify' ),
						'type'        => 'checkbox',
						'label'       => __( 'Show estimated delivery time', 'woocommerce-shippify' ),
						'description' => __( 'Display the estimated delivery time in working days.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => 'no',
					),
					'additional_time' => array(
						'title'       => __( 'Additional Days', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'Additional working days to the estimated delivery.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => '0',
						'placeholder' => '0',
					),
					'warehouse_info' => array(
						'title'   => __( 'Warehouse Information', 'woocommerce-shippify' ),
						'type'    => 'title',
						'default' => '',
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
					),
					'testing' => array(
						'title'   => __( 'Testing', 'woocommerce-shippify' ),
						'type'    => 'title',
						'default' => '',
					),
					'debug' => array(
						'title'       => __( 'Debug Log', 'woocommerce-shippify' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable logging', 'woocommerce-shippify' ),
						'default'     => 'no',
						'description' => sprintf( __( 'Log %s events, such as WebServices requests.', 'woocommerce-shippify' ), $this->method_title ) . $this->get_log_link(),
					),
				);
			}

			/**
			* Check if package uses only the selected shipping class.
			*
			* @param  array $package Cart package.
			* @return bool
			*/
			protected function has_only_selected_shipping_class( $package ) {
				$only_selected = true;

				if ( -1 === $this->shipping_class_id ) {
					return $only_selected;
				}

				foreach ( $package['contents'] as $item_id => $values ) {
					$product = $values['data'];
					$qty     = $values['quantity'];

					if ( $qty > 0 && $product->needs_shipping() ) {
						if ( $this->shipping_class_id !== $product->get_shipping_class_id() ) {
							$only_selected = false;
							break;
						}
					}
				}

				return $only_selected;
			}

			/**
			* Calculates the shipping rate.
			*
			* @param array $package Order package.
			*/
			public function calculate_shipping( $package = array() ) {
				

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

				$pickup_latitude = get_option( 'woocommerce_shippify_settings' )["warehouse_latitude"];
				$pickup_longitude = get_option( 'woocommerce_shippify_settings' )["warehouse_longitude"];

				$delivery_latitude = $_SESSION["shippify_latitude"];
				$delivery_longitude = $_SESSION["shippify_longitude"];

				$items = "[";
				foreach ( $package['contents'] as $item_id => $values ) { 
			        $_product = $values['data']; 
			        $items = $items . '{"id":"' . $_product->get_id() . '", 
			        					"name":"' . $_product->get_name() . '", 
			        					"qty": "' . '1' . '", 
			        					"size": "' . $this->calculate_product_shippify_size($_product) . '", 
			        					"price": "' . $_product->get_price() . '"
			        					},';
			    }
			    $items = substr($items, 0, -1) . ']}]';


				$data_value = '[{"pickup_location":{"lat":'. $pickup_latitude .',"lng":'. $pickup_longitude . '},"delivery_location":{"lat":' . $delivery_latitude . ',"lng":'. $delivery_longitude .'},"items":' . $items;

				$request_url = $this->fare_API . "data=" . $data_value;


				$response = wp_remote_get( $request_url, $args );

            	$decoded = json_decode($response['body'], true);
            	$cost = $decoded['price'];

				if (is_wp_error($response)){
					$cost = 0;
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