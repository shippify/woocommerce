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

    public function __construct() {
        add_action( 'woocommerce_checkout_after_customer_details', array( $this,'display_custom_checkout_fields'));
        add_filter( 'woocommerce_checkout_fields' , array( $this, 'customize_checkout_fields' ));
        add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields', 10, 2 );  
        add_filter('woocommerce_shipping_method_chosen', 'change_selected');
        wp_enqueue_script('wc-shippify-checkout', plugins_url('../assets/js/shippify-checkout.js', __FILE__), array('jquery')); 
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

		echo '<div id="shippify_checkout" class="col2-set"><h3>' . __('Shippify') . '</h3>';

	    foreach ( $checkout->checkout_fields['shippify_fields'] as $key => $field ) : 
	 
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
	}
  

   	public function customize_checkout_fields($fields){
   		if  ($this->is_selected){
	   		$fields["shippify_fields"] = array(
	   			'instructions' => array(
					'type'         => 'text',
					'class'         => array('form-row form-row-wide'),
					'label'         => __('Instructions'),
					'placeholder'   => __('Al lado de una tienda...'),
					'required'     => false
				),
				'hours' => array(
					'type'			=> 'number',
					'class'			=> array('form-row form-row-wide'),
					'label'			=> __('Hour of Delivery'),
					'required'     => false,
					'placeholder'   => __('12'),
					'custom_attributes' => array(
									'max' 	=> '18',
									'min'	=> '6'
								) 
						)
			);   
   		}else{
   			if (isset($fields["shippify_fields"])){
   				unset($fields["shippify_fields"]);
   			}
   		}

   		return $fields;

   	}
}

$wcshippifyclass = new WC_Shippify_Checkout();

?>
