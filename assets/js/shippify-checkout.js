jQuery(function($) {
	$(document).ready(function(){
		$("#order_review").click(function() {
		    if( !$('#shipping_method_0_shippify').is(':checked')) {
		    	$("#shippify_checkout").hide();
		    	$("#shippify_map").hide();
		    }else{
		    	$("#shippify_checkout").show();    	
		    	$("#shippify_map").show();
		    }
		});
	});

});
