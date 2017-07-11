/*
 * JavaScript file to produce the interactive Map and its functionalities in Checkout.
 */
var marker;
var map;

// Init the checkout map and configurates it.
function initMap() {
    var uluru = {lat: -21.0875614, lng: 95.405639};
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 16,
        center: uluru
    });
    

   

    marker = new google.maps.Marker({
        position: uluru,
        map: map
    });

    


    var input = document.getElementById('pac-input');
    var searchBox = new google.maps.places.SearchBox(input);


    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    map.addListener('bounds_changed', function() {
        searchBox.setBounds(map.getBounds());
    });

    google.maps.event.addListener(map, 'click', function(event) {
        placeMarker(event.latLng);
    });


    searchBox.addListener('places_changed', function() {
        var places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }

        // For each place, get the icon, name and location.
        var bounds = new google.maps.LatLngBounds();
        places.forEach(function(place) {
            if (!place.geometry) {
                console.log("Returned place contains no geometry");
                return;
            }

            if (place.geometry.viewport) {
            // Only geocodes have viewport.
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
}

/*
* Function that place a marker in the map.
* This function is used in the function code Address.
*/
function placeMarker(location) {
    marker.setMap(null);
    map.setCenter({lat:location.lat(), lng:location.lng()});
    map.setZoom(16);
    marker = new google.maps.Marker({
        position: location, 
        map: map
    });

}



jQuery(function($) {
    /** 
    * Everytime the user clicks on the map, we need to trigger a 'change' in the latitude and longitud fields in order to update automatically the total
    * costs of the order. Which triggers the shipping calculation. 
    */
    


    $("#map").click(function() {

        updateShippingInfo();
    });


    /** 
    * Everytime the user click on Shipping to another address checkbox have to change the marker and the price.
    */
    $("#ship-to-different-address-checkbox").click( function(){
        if ( $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {

            $("#shipping_address_1").trigger('focusout');
            
        }
        else
        {

            $("#billing_address_1").trigger('focusout');
            
        }
    });
    /*  
    *   Every time the user focus out one of the billing address fields, the map is updated
    *
    */
    $("#billing_address_1").focusout( function()
    {
        if (! $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            var dir = $("#billing_address_1").val();
            var city = $("#billing_city").val();
            var state = $("#billing_state").val();
            codeAddress(dir+', '+city+', '+state);
        }  


        
    });
    $("#billing_city").focusout(function()
    {
        if (! $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            var dir = $("#billing_address_1").val();
            var city = $("#billing_city").val();
            var state = $("#shipping_state").val();
            codeAddress(dir+', '+city+', '+state);
        }
        
    });
    $("#billing_state").focusout(function()
    {
        if (! $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            var dir = $("#billing_address_1").val();
            var city = $("#billing_city").val();
            var state = $("#billing_state").val();
            codeAddress(dir+', '+city+', '+state);
        }    
 
    });
        /*  
    *   Every time the user focus out one of the shipping address fields, the map is updated
    *
    */
    $("#shipping_address_1").focusout(function()
    {
        if ($( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            var dir = $("#shipping_address_1").val();
            var city = $("#shipping_city").val();
            var state = $("#shipping_state").val();
            codeAddress(dir+', '+city+', '+state);
        }  
        
    });
    $("#shipping_city").focusout(function()
    {
        if ($( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            var dir = $("#shipping_address_1").val();
            var city = $("#shipping_city").val();
            var state = $("#shipping_state").val();
            codeAddress(dir+', '+city+', '+state);
        }
        
    });
    $("#shipping_state").focusout(function()
    {
        if ($( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {

            var dir = $("#shipping_address_1").val();
            var city = $("#shipping_city").val();
            var state = $("#shipping_state").val();
            codeAddress(dir+', '+city+', '+state);

        }

 
    });

    /*
    * Using this function, I get a marker on the map, giving the address in natural Language
    * This function use placeMarker function
    */

    function codeAddress(address) {

    //In this case it gets the address from an element on the page, but obviously you  could just pass it to the method instea

        return new Promise(function(resolve)
        {
            var geocoder = new google.maps.Geocoder();
            
            geocoder.geocode( { 'address': address}, function(results, status) {
            
              if (status == google.maps.GeocoderStatus.OK) {
                placeMarker(results[0].geometry.location);
                updateShippingInfo();
                resolve();
              } 
            });
    
        });
            
    }

    //This function updates the shippify Info
    function updateShippingInfo()
    {

        // We use cookies to store the marker coordinates
        document.cookie = "shippify_latitude=" + marker.getPosition().lat();
        document.cookie = "shippify_longitude=" + marker.getPosition().lng();
        
        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');
    
        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 
        if (! $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) )
        {
            //need to trigger this event to recalculate the shipping price
            $( '#billing_address_1' ).val($( '#billing_address_1' ).val()+ ' ');
    
            $( '#billing_address_1' ).trigger( 'change' );
        }
        else
        {
            //need to trigger this event to recalculate the shipping price
            $( '#shipping_address_1' ).val($( '#shipping_address_1' ).val() + ' ' );
            $( '#shipping_address_1' ).trigger( 'change' );
        }

    }
});
