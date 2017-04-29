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

class WC_Shippify_Checkout{

	protected $is_selected = true;
	var $countries;

    public function __construct() {



        add_filter( 'woocommerce_checkout_fields' , array( $this, 'customize_checkout_fields' ));

        add_action( 'woocommerce_after_order_notes', array( $this,'display_custom_checkout_fields' ));

        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_checkout_fields'));  

        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this,'display_order_data_in_admin') );

        wp_enqueue_script('wc-shippify-checkout', plugins_url('../assets/js/shippify-checkout.js', __FILE__), array('jquery')); 
        wp_enqueue_style('wc-shippify-map-css', plugins_url('../assets/css/shippify-map.css', __FILE__)); 
        wp_enqueue_style('wc-shippify-fields-css', plugins_url('../assets/css/shippify-checkout-fields.css', __FILE__)); 
        wp_enqueue_script('wc-shippify-map-js', plugins_url('../assets/js/shippify-map.js', __FILE__));

        add_action( 'woocommerce_after_checkout_form', array ( $this,'add_map'));

        add_action( 'woocommerce_checkout_process', array ( $this,'shippify_validate_order') , 10 );

		add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'change_shipping_label'), 10, 2 );

		add_action('woocommerce_checkout_update_order_review', array($this, 'action_woocommerce_checkout_update_order_review'), 900, 2);
		
		add_filter( 'style_loader_src', array($this, 'remove_cssjs_query_string'), 1, 2 );
		add_filter( 'script_loader_src', array($this, 'remove_cssjs_query_string'), 1, 2 );

    }


	function remove_cssjs_query_string( $src ) {
		if( strpos( $src, '?ver=' ) )
		$src = remove_query_arg( 'ver', $src );
		return $src;
	}


	function action_woocommerce_checkout_update_order_review($array, $int)
	{

		if (in_array("shippify", WC()->session->get('chosen_shipping_methods'))){
			WC()->cart->calculate_shipping();		
		}
					
	    return;
	}



	public function change_shipping_label( $full_label, $method ){
		if ($method->id == "shippify"){
			if (is_cart()){
				$full_label = "Shippify: Same Day Delivery - Proceed to Checkout for fares";	
			} elseif (is_checkout()) {
				$full_label = $full_label . " - Same Day Delivery ";
			}	
		}
	    return $full_label;
	}

    //HAY QUE MOVER
    public function display_order_data_in_admin($order){
    	if (in_array("shippify", get_post_meta( $order->id, '_shipping_method', true ))){
    		?>
		    <div class="order_data_column">
		        <h4><?php _e( 'Shippify', 'woocommerce' ); ?></h4>
		        <?php 
		            echo '<p><strong>' . __( 'Instructions' ) . ':</strong>' . " \n" . get_post_meta( $order->id, 'Instructions', true ) . '</p>';
		            echo '<p><strong>' . __( 'Shippify ID' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, '_shippify_id', true ) . '</p>'; 
		            echo '<p><strong>' . __( 'Deliver Latitude' ) . ':</strong>' .  " \n"  .  get_post_meta( $order->id, 'Latitude', true ) . '</p>'; 
		            echo '<p><strong>' . __( 'Deliver Longitude' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, 'Longitude', true ) . '</p>';
		            echo '<p><strong>' . __( 'Pickup Latitude' ) . ':</strong>' .  " \n"  .  get_post_meta( $order->id, 'pickup_latitude', true ) . '</p>'; 
		            echo '<p><strong>' . __( 'Pickup Longitude' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, 'pickup_longitude', true ) . '</p>'; ?>
		    </div>
			<?php    		
    	}
	
    }

    public function add_map($after){
    	echo '<div id="shippify_map">';
    	echo '<h4>Delivery Position  </h4> <p> Click on the map to put a marker. </p>';
    	echo '<input id="pac-input" class="controls" type="text" placeholder="Search Box">';
    	echo '<div id="map"></div>';
    	wp_enqueue_script('wc-shippify-google-maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDEXSakl9V0EJ_K3qHnnrVy8IEy3Mmo5Hw&libraries=places&callback=initMap', $in_footer = true);
    	echo '</div>';
    }


    public function display_custom_checkout_fields($checkout){

        

		echo '<div id="shippify_checkout" class="col3-set"><h2>' . __('Shippify') . '</h2>';

	    foreach ( $checkout->checkout_fields['shippify'] as $key => $field ) : 
	 
	            woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	        endforeach;
	    echo '</div>';

	    session_start();
   		unset($_SESSION['shippify_longitude']);
   		unset($_SESSION['shippify_latitude']);
	    WC()->cart->calculate_shipping();
    }


	// save the extra field when checkout is processed
	public function save_custom_checkout_fields( $order_id){
	    if( ! empty( $_POST['shippify_instructions'] ) ) {
	        update_post_meta( $order_id, 'Instructions', sanitize_text_field($_POST['shippify_instructions'] ));
	    }
	   	if( ! empty( $_POST['shippify_latitude'] ) ) {
	        update_post_meta( $order_id, 'Latitude', sanitize_text_field($_POST['shippify_latitude'] ));
	    }
	   	if( ! empty( $_POST['shippify_longitude'] ) ) {
	        update_post_meta( $order_id, 'Longitude', sanitize_text_field($_POST['shippify_longitude'] ));
	    }
	    update_post_meta( $order_id, 'pickup_latitude', sanitize_text_field(get_option( 'shippify_instance_settings' )["warehouse_latitude"]));
	    update_post_meta( $order_id, 'pickup_longitude', sanitize_text_field(get_option( 'shippify_instance_settings' )["warehouse_longitude"]));
	}

  

   	public function customize_checkout_fields($fields){
   		
   		global $woocommerce;
    	//var_dump($woocommerce->cart);

   		$fields["shippify"] = array(
   			'shippify_instructions' => array(
				'type'         => 'text',
				'class'         => array('form-row form-row-wide'),
				'label'         => __('Instructions'),
				'placeholder'   => __('Al lado de una tienda...'),
				'required'     => false
			),
   			'shippify_latitude' => array(
				'type'         => 'text',
				'class'         => array('form-row form-row-wide'),
				'label'         => __('Latitude'),
				'required'     => false,
				'class' 	=> array ('address-field', 'update_totals_on_change' )
			),
			'shippify_longitude' => array(
				'type'         => 'text',
				'class'         => array('form-row form-row-wide'),
				'label'         => __('Longitude'),
				'required'     => false,
				'class' 	=> array ('address-field', 'update_totals_on_change' )
			)
		);   

   		return $fields;

   	}

	public function shippify_validate_order() {

		if ( in_array( 'shippify', $_POST["shipping_method"])){

			if ($_POST['shippify_latitude'] == "" || $_POST['shippify_longitude'] == "" ){
				wc_add_notice( __( 'Shippify: Please, locate the marker of your address in the map.' ), 'error' );
			}
			if ($_POST['shippify_instructions'] == "" || strlen($_POST['shippify_instructions']) < 10 ){
				wc_add_notice( __( 'Shippify: Please, write descriptive instructions.' ), 'error' );
			}


			$pickup_latitude = get_option( 'shippify_instance_settings' )["warehouse_latitude"];
			$pickup_longitude = get_option( 'shippify_instance_settings' )["warehouse_longitude"];

			$delivery_latitude = $_POST["shippify_latitude"];
			$delivery_longitude = $_POST["shippify_longitude"];

            $data_value = '[{"pickup_location":{"lat":'. $pickup_latitude .',"lng":'. $pickup_longitude . '},"delivery_location":{"lat":' . $delivery_latitude . ',"lng":'. $delivery_longitude .'},"items":[{"id":"10234","name":"TV","qty":"2","size":"3","price":"0"}]}]';


			$task_endpoint = 'https://api.shippify.co/task/fare?data='. $data_value;
			$api_id = get_option('shippify_id');
			$api_secret = get_option('shippify_secret');

            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
                ),
                'method'  => 'GET'
            );                    

            $response = wp_remote_get( $task_endpoint, $args );
            $decoded = json_decode($response['body'], true);
            $price = $decoded['price'];

			if (!isset($price) || $price == ""){
				wc_add_notice( __( 'Shippify: We are unable to make a route to your address. Verify the marker in the map is correctly positioned.'), 'error' );
			}  
		}
	}
}

new WC_Shippify_Checkout();

?>
