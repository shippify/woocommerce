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
        add_action( 'woocommerce_after_order_notes', array( $this,'display_custom_checkout_fields'));
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_checkout_fields'), 10, 2 );  
        add_filter( 'woocommerce_cart_shipping_packages', array( $this,'add_custom_package_fields'));
        //add_filter('woocommerce_shipping_method_chosen', 'change_selected');
        wp_enqueue_script('wc-shippify-checkout', plugins_url('../assets/js/shippify-checkout.js', __FILE__), array('jquery')); 
        wp_enqueue_style('wc-shippify-map-css', plugins_url('../assets/css/shippify-map.css', __FILE__)); 
        wp_enqueue_script('wc-shippify-map', plugins_url('../assets/js/shippify-map.js', __FILE__), array('jquery')); 
        add_action( 'woocommerce_after_checkout_form', array ( $this,'add_map'));
    }

    public function add_map($after){
    	echo '<h4>Ubiquese en el mapa: </h4>';
    	echo '<input id="pac-input" class="controls" type="text" placeholder="Search Box">';
    	echo '<div id="map"></div>';

    	wp_enqueue_script('wc-shippify-google-maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDEXSakl9V0EJ_K3qHnnrVy8IEy3Mmo5Hw&libraries=places&callback=initMap', $in_footer = true);
    }

    public function add_custom_package_fields($package){
    	//$shippingfields = $this->countries->get_address_fields( $this->countries->get_base_country(),'shipping_');
    	$fields = WC()->checkout()->checkout_fields;
    	$hola = WC()->checkout()->get_value("instructions");
    	//var_dump($hola);
    	//var_dump($fields);
    	//$package[0]["destination"]["instructions"] = $fields["shipping"]["instructions"];
    	//$package[0]["destination"]["hours"] = $fields["shipping"]["hours"];
    	//$package[0]["destination"]["latitude"] = $fields["shipping"]["latitude"];
    	//$package[0]["destination"]["longitude"] = $fields["shipping"]["longitude"];
    	return $package;
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
	public function save_custom_checkout_fields( $order_id, $posted ){
	    // don't forget appropriate sanitization if you are using a different field type
	    if( isset( $posted['instructions'] ) ) {
	        update_post_meta( $order_id, '_instructions', sanitize_text_field( $posted['instructions'] ) );
	    }
	    if( isset( $posted['hours'] ) && in_array( $posted['hours'], array( 'a', 'b', 'c' ) ) ) {
	        update_post_meta( $order_id, '_hours', $posted['hours'] );
	    }
	   	if( isset( $posted['latitude'] ) && in_array( $posted['latitude'], array( 'a', 'b', 'c' ) ) ) {
	        update_post_meta( $order_id, '_latitude', $posted['latitude'] );
	    }
	   	if( isset( $posted['longitude'] ) && in_array( $posted['longitude'], array( 'a', 'b', 'c' ) ) ) {
	        update_post_meta( $order_id, '_longitude', $posted['longitude'] );
	    }
	}
  

   	public function customize_checkout_fields($fields){
   		if  ($this->is_selected){
	   		$fields["shippify"] = array(
	   			'shippify_instructions' => array(
					'type'         => 'text',
					'class'         => array('form-row form-row-wide'),
					'label'         => __('Instructions'),
					'placeholder'   => __('Al lado de una tienda...'),
					'required'     => false
				),
				'shippify_hours' => array(
					'type'			=> 'number',
					'class'			=> array('form-row form-row-wide'),
					'label'			=> __('Hour of Delivery'),
					'required'     => false,
					'placeholder'   => __('12'),
					'custom_attributes' => array(
									'max' 	=> '18',
									'min'	=> '6'
								) 
				),
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
}

$wcshippifyclass = new WC_Shippify_Checkout();

?>
