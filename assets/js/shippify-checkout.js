/**
 *
 * This function triggers the appearance and dissapearance of the Shippify fields in checkout and the calculate shipping method.
 */
jQuery( function($) {
	$( document ).ready(function(){



        // We change the address field value. Then trigger 'change' event. Which triggers calculate_shipping()

        $( '#billing_address_1' ).trigger( 'focusout' );

		$( '#order_review' ).click(function(){
		    if( ! $( '#shipping_method_0_shippify' ).is( ':checked' ) ) {
		    	$( '#shippify_checkout' ).hide();
		    	$( '#shippify_map' ).hide();
		    } else {
		    	$( '#shippify_checkout' ).show();
		    	$( '#shippify_map' ).show();
		    }

		    if( $( '#shipping_method_0' ).val() === 'shippify' ) {
		    	$( '#shippify_checkout' ).show();    	
		    	$( '#shippify_map' ).show();    	
		    }
		});


	});

});