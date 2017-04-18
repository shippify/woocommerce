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

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Shippify_Shipping' ) ) {

		class WC_Shippify_Shipping extends WC_Shipping_Method {
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
				$this->minimum_height     = $this->get_option( 'minimum_height' );
				$this->minimum_width      = $this->get_option( 'minimum_width' );
				$this->minimum_length     = $this->get_option( 'minimum_length' );
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
					'optional_services' => array(
						'title'       => __( 'Optional Services', 'woocommerce-shippify' ),
						'type'        => 'title',
						'description' => __( 'Use these options to add the value of each service provided by the Correios.', 'woocommerce-shippify' ),
						'default'     => '',
					),
					'declare_value' => array(
						'title'       => __( 'Declare Value for Insurance', 'woocommerce-shippify' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable declared value', 'woocommerce-shippify' ),
						'description' => __( 'This controls if the price of the package must be declared for insurance purposes.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => 'yes',
					),
					'service_options' => array(
						'title'   => __( 'Service Options', 'woocommerce-shippify' ),
						'type'    => 'title',
						'default' => '',
					),
					'minimum_height' => array(
						'title'       => __( 'Minimum Height', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'Minimum height of your shipping packages. Correios needs at least 2cm.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => '2',
					),
					'minimum_width' => array(
						'title'       => __( 'Minimum Width', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'Minimum width of your shipping packages. Correios needs at least 11cm.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => '11',
					),
					'minimum_length' => array(
						'title'       => __( 'Minimum Length', 'woocommerce-shippify' ),
						'type'        => 'text',
						'description' => __( 'Minimum length of your shipping packages. Correios needs at least 16cm.', 'woocommerce-shippify' ),
						'desc_tip'    => true,
						'default'     => '16',
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
			* Get the declared value from the package.
			*
			* @param  array $package Cart package.
			*
			* @return float
			*/
			protected function get_declared_value( $package ) {
				return $package['contents_cost'];
			}

			/**
			* Get shipping rate.
			*
			* @param  array $package Cart package.
			*
			* @return SimpleXMLElement|null
			*/
			protected function get_rate( $package ) {
				/*
				$api = new WC_Correios_Webservice( $this->id, $this->instance_id );
				$api->set_debug( $this->debug );
				$api->set_service( $this->get_code() );
				$api->set_package( $package );
				$api->set_origin_postcode( $this->origin_postcode );
				$api->set_destination_postcode( $package['destination']['postcode'] );

				if ( 'yes' === $this->declare_value ) {
					$api->set_declared_value( $this->get_declared_value( $package ) );
				}

				$api->set_own_hands( 'yes' === $this->own_hands ? 'S' : 'N' );
				$api->set_receipt_notice( 'yes' === $this->receipt_notice ? 'S' : 'N' );

				$api->set_login( $this->get_login() );
				$api->set_password( $this->get_password() );

				$api->set_minimum_height( $this->minimum_height );
				$api->set_minimum_width( $this->minimum_width );
				$api->set_minimum_length( $this->minimum_length );

				$shipping = $api->get_shipping();
				*/
				$shipping = 10;
				return $shipping;
			}

			/**
			* Get additional time.
			*
			* @param  array $package Package data.
			*
			* @return array
			*/
			protected function get_additional_time( $package = array() ) {
				return apply_filters( 'woocommerce_correios_shipping_additional_time', $this->additional_time, $package );
			}

			/**
			* Get accepted error codes.
			*
			* @return array
			*/
			protected function get_accepted_error_codes() {
				/*
				$codes   = apply_filters( 'woocommerce_correios_accepted_error_codes', array( '-33', '-3', '010' ) );
				$codes[] = '0';

				return $codes;
				*/
			}

			/**
			* Get shipping method label.
			*
			* @param  int   $days Days to deliver.
			* @param  array $package Package data.
			*
			* @return string
			*/
			protected function get_shipping_method_label( $days, $package ) {
				/*
				if ( 'yes' === $this->show_delivery_time ) {
					return wc_correios_get_estimating_delivery( $this->title, $days, $this->get_additional_time( $package ) );
				}

				return $this->title;
				*/
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
				/*
				if ( '' === $package['destination']['postcode'] || 'BR' !== $package['destination']['country'] ) {
					return;
				}

				// Check for shipping classes.
				if ( ! $this->has_only_selected_shipping_class( $package ) ) {
					return;
				}

				$shipping = $this->get_rate( $package );

				if ( ! isset( $shipping->Erro ) ) {
					return;
				}

				$error_number = (string) $shipping->Erro;

				// Exit if have errors.
				if ( ! in_array( $error_number, $this->get_accepted_error_codes(), true ) ) {
					return;
				}



				// Display Correios errors.
				
				$error_message = wc_correios_get_error_message( $error_number );
				if ( '' !== $error_message && is_cart() ) {
					$notice_type = ( '010' === $error_number ) ? 'notice' : 'error';
					$notice      = '<strong>' . $this->title . ':</strong> ' . esc_html( $error_message );
					wc_add_notice( $notice, $notice_type );
				}

				// Set the shipping rates.
				$label = $this->get_shipping_method_label( (int) $shipping->PrazoEntrega, $package );
				$cost  = wc_correios_normalize_price( esc_attr( (string) $shipping->Valor ) );

				// Exit if don't have price.
				if ( 0 === intval( $cost ) ) {
					return;
				}

				// Apply fees.
				$fee = $this->get_fee( $this->fee, $cost );

				// Create the rate and apply filters.
				$rate = apply_filters( 'woocommerce_correios_' . $this->id . '_rate', array(
					'id'    => $this->id . $this->instance_id,
					'label' => $label,
					'cost'  => (float) $cost + (float) $fee,
				), $this->instance_id, $package );

				// Deprecated filter.
				$rates = apply_filters( 'woocommerce_correios_shipping_methods', array( $rate ), $package );

				// Add rate to WooCommerce.
				$this->add_rate( $rates[0] );
				*/
				$cost = 20;
				$rate = array(
					'id' => $this->id,
					'label' => $this->title,
					'cost' => $cost
				);
				
				$this->add_rate( $rate );
			}
		}
	}
}