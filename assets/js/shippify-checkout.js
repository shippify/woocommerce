/**
 *
 * This file triggers the appearance and dissapearance of the Shippify fields in checkout.
 */
jQuery( function($) {
	$( document ).ready(function(){

		$( '#billing_address_1' ).val($( '#billing_address_1' ).val() + ' ' );

		$( '#billing_address_1' ).trigger( 'change' );

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