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

        //add_action( 'woocommerce_after_checkout_validation', 'shippify_validate_order' , 10 );

        wp_enqueue_script('wc-shippify-checkout', plugins_url('../assets/js/shippify-checkout.js', __FILE__), array('jquery')); 
        wp_enqueue_style('wc-shippify-map-css', plugins_url('../assets/css/shippify-map.css', __FILE__)); 
        wp_enqueue_script('wc-shippify-map', plugins_url('../assets/js/shippify-map.js', __FILE__), array('jquery'), '20170423', true);


        add_action( 'woocommerce_after_checkout_form', array ( $this,'add_map'));

    }

    //HAY QUE MOVER
    public function display_order_data_in_admin($order){
    	var_dump(in_array("shippify", get_post_meta( $order->id, '_shipping_method', true ))); 
    	?>

	    <div class="order_data_column">
	        <h4><?php _e( 'Shippify', 'woocommerce' ); ?></h4>
	        <?php 
	            echo '<p><strong>' . __( 'Instructions' ) . ':</strong>' . get_post_meta( $order->id, 'Instructions', true ) . '</p>';
	            echo '<p><strong>' . __( 'Shippify ID' ) . ':</strong>' . get_post_meta( $order->id, '_shippify_id', true ) . '</p>'; 
	            echo '<p><strong>' . __( 'Latitude' ) . ':</strong>' . get_post_meta( $order->id, 'Latitude', true ) . '</p>'; 
	            echo '<p><strong>' . __( 'Longitude' ) . ':</strong>' . get_post_meta( $order->id, 'Longitude', true ) . '</p>'; ?>
	    </div>
		<?php

    }

    public function add_map($after){
    	echo '<div id="shippify_map">';
    	echo '<h4>Ubiquese en el mapa: </h4>';
    	echo '<input id="pac-input" class="controls" type="text" placeholder="Search Box">';
    	echo '<div id="map"></div>';
    	wp_enqueue_script('wc-shippify-google-maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDEXSakl9V0EJ_K3qHnnrVy8IEy3Mmo5Hw&libraries=places&callback=initMap', $in_footer = true);
    	echo '</div>';

    }

	public function change_selected($chosen_method) {
		
	    $chosen_methods = WC()->session->get('chosen_shipping_methods');
	    $chosen_shipping = $chosen_methods[0];

	    if ($chosen_shipping == 'shippify') { 
	        //$this->is_selected = true;
	    }else{
	    	$this->is_selected = false;
	    }

	}

    public function display_custom_checkout_fields($checkout){

        $checkout = WC()->checkout(); 

		echo '<div id="shippify_checkout" class="col3-set"><h2>' . __('Shippify') . '</h2>';

	    foreach ( $checkout->checkout_fields['shippify'] as $key => $field ) : 
	 
	            woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	        endforeach;
	    echo '</div>';
    }


	// save the extra field when checkout is processed
	public function save_custom_checkout_fields( $order_id){
	    // don't forget appropriate sanitization if you are using a different field type
	    if( ! empty( $_POST['shippify_instructions'] ) ) {
	        update_post_meta( $order_id, 'Instructions', sanitize_text_field($_POST['shippify_instructions'] ));
	    }
	    //if( ! empty( $_POST['shippify_hours'] ) ) {
	    //    update_post_meta( $order_id, 'Hours', sanitize_text_field($_POST['shippify_hours'] ));
	    //}
	   	if( ! empty( $_POST['shippify_latitude'] ) ) {
	        update_post_meta( $order_id, 'Latitude', sanitize_text_field($_POST['shippify_latitude'] ));
	    }
	   	if( ! empty( $_POST['shippify_longitude'] ) ) {
	        update_post_meta( $order_id, 'Longitude', sanitize_text_field($_POST['shippify_longitude'] ));
	    }
	}
  

   	public function customize_checkout_fields($fields){
   		//echo '<script> alert("aqui"); </script>';
   		if  ($this->is_selected){
	   		$fields["shippify"] = array(
	   			'shippify_instructions' => array(
					'type'         => 'text',
					'class'         => array('form-row form-row-wide'),
					'label'         => __('Instructions'),
					'placeholder'   => __('Al lado de una tienda...'),
					'required'     => false
				),
				/*'shippify_hours' => array(
					'type'			=> 'number',
					'class'			=> array('form-row form-row-wide'),
					'label'			=> __('Hour of Delivery'),
					'required'     => false,
					'placeholder'   => __('12'),
					'custom_attributes' => array(
									'max' 	=> '18',
									'min'	=> '6'
								) 
				),*/
	   			'shippify_latitude' => array(
					'type'         => 'text',
					'class'         => array('form-row form-row-wide'),
					'label'         => __('Latitude'),
					'placeholder'   => __(''),
					'required'     => false,
					'class' 	=> array ('address-field', 'update_totals_on_change' )
				),
				'shippify_longitude' => array(
					'type'         => 'text',
					'class'         => array('form-row form-row-wide'),
					'label'         => __('Longitude'),
					'placeholder'   => __(''),
					'required'     => false,
					'class' 	=> array ('address-field', 'update_totals_on_change' )
				)
			);   
   		}else{
   			if (isset($fields["shipping"]["instructions"])){
   				unset($fields["shipping"]["instructions"]);
   			}
   		}

   		return $fields;

   	}

	public function shippify_validate_order( $posted )   {

		
	 
	    $packages = WC()->shipping->get_packages();
	     
	    $chosen_methods = WC()->session->get('chosen_shipping_methods');
	     
	    if( is_array( $chosen_methods ) && in_array( 'shippify', $chosen_methods ) ) {
	         
	        foreach ( $packages as $i => $package ) {
	     
	            if ( $chosen_methods[ $i ] != "shippify" ) {
	                         
	                continue;
	                         
	            }

	            session_start();

				$context = stream_context_create($opts);


				$pickup_latitude = get_option( 'woocommerce_shippify_settings' )["warehouse_latitude"];
				$pickup_longitude = get_option( 'woocommerce_shippify_settings' )["warehouse_longitude"];

				$delivery_latitude = $_SESSION["shippify_latitude"];
				$delivery_longitude = $_SESSION["shippify_longitude"];

	            $data_value = '[{"pickup_location":{"lat":'. $pickup_latitude .',"lng":'. $pickup_longitude . '},"delivery_location":{"lat":' . $delivery_latitude . ',"lng":'. $delivery_longitude .'},"items":[{"id":"10234","name":"TV","qty":"2","size":"3","price":"0"}]}]';

				$request_url = $this->fare_API . "data=" . $data_value;

				$api_response = json_decode(file_get_contents($request_url, false, $context), true);
				$cost = 1;
				$cost = $api_response["price"];

				if (!isset($cost) || $cost == ""){
                    $message = 'Sorry, unable to make the route. Verify your checkout fields or the marker in the map.';
                         
                    $messageType = "error";
     
                    if( ! wc_has_notice( $message, $messageType ) ) {
                     
                        wc_add_notice( $message, $messageType );
                  
                    }
				}

	        }       
	    } 
	}
}

$wcshippifyclass = new WC_Shippify_Checkout();

?>
