/**
 *
 * This function triggers the appearance and dissapearance of the Shippify fields in checkout and the calculate shipping method.
 */
jQuery( function($) {
	$( document ).ready(function(){



        // We focusout the address field value. to get the marker, and then inside focusout function 
        //the address field is changed, which triggers the calculate shipping function.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
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