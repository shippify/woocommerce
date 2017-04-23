jQuery(function($) {
	$("#order_review").click(function() {
	    if( !$('#shipping_method_0_shippify').is(':checked')) {
	    	$("#shippify_checkout").hide();
	    }else{
	    	$("#shippify_checkout").show();    	
	    }
	});
});
