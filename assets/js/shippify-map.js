/*
 * JavaScript file to produce the interactive Map and its functionalities in Checkout.
 */
var marker;
var map;

// Init the checkout map and configurates it.
function initMap() {
    var uluru = {lat: -2.1521517, lng: -80.1199984};
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
    marker = new google.maps.Marker({
        position: location, 
        map: map
    });

}

/*
* Using this function, I get a marker on the map, giving the address in natural Language
* This function use placeMarker function
*/
function codeAddress(address) {

    //In this case it gets the address from an element on the page, but obviously you  could just pass it to the method instea

    var geocoder = new google.maps.Geocoder();
    
    geocoder.geocode( { 'address': address}, function(results, status) {
    
      if (status == google.maps.GeocoderStatus.OK) {
        console.log(results[0].geometry.location.lng());
        console.log(results[0].geometry.location.lat());
        placeMarker(results[0].geometry.location);
      } 
    });
}




jQuery(function($) {
    /** 
    * Everytime the user clicks on the map, we need to trigger a 'change' in the latitude and longitud fields in order to update automatically the total
    * costs of the order. Which triggers the shipping calculation. 
    */
    $("#map").click(function() {

        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');

        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 

        // We change the address field value. Then trigger 'change' event. Which triggers calculate_shipping()
        $( '#billing_address_1' ).val($( '#billing_address_1' ).val() + ' ' );

        $( '#billing_address_1' ).trigger( 'change' );

        // We use cookies to store the marker coordinates
        document.cookie = "shippify_latitude=" + marker.getPosition().lat();
        document.cookie = "shippify_longitude=" + marker.getPosition().lng();
         


    });
    /*  
    *   Every time the user focus out one of the billing address fields, the map is updated
    *
    */
    $("#billing_address_1").focusout(function()
    {
        var dir = $("#billing_address_1").val();
        var city = $("#billing_city").val();
        var state = $("#billing_state").val();
        codeAddress(dir+', '+city+', '+state);
        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');

        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 

        

        document.cookie = "shippify_latitude=" + marker.getPosition().lat();
        document.cookie = "shippify_longitude=" + marker.getPosition().lng();

        // We change the address field value. Then trigger 'change' event. Which triggers calculate_shipping()
        $( '#billing_address_1' ).val($( '#billing_address_1' ).val() + ' ' );

        $( '#billing_address_1' ).trigger( 'change' );
    });
    $("#billing_city").focusout(function()
    {
        var dir = $("#billing_address_1").val();
        var city = $("#billing_city").val();
        var state = $("#select2-billing_country-container").val();
        codeAddress(dir+', '+city+', '+state);
        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');

        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 

       

        document.cookie = "shippify_latitude=" + marker.getPosition().lat();
        document.cookie = "shippify_longitude=" + marker.getPosition().lng();

         // We change the address field value. Then trigger 'change' event. Which triggers calculate_shipping()
        $( '#billing_address_1' ).val($( '#billing_address_1' ).val() + ' ' );

        $( '#billing_address_1' ).trigger( 'change' );
    });
    $("#billing_state").focusout(function()
    {
        var dir = $("#billing_address_1").val();
        var city = $("#billing_city").val();
        var state = $("#billing_state").val();
        codeAddress(dir+', '+city+', '+state);
        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');

        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 



        document.cookie = "shippify_latitude=" + marker.getPosition().lat();
        document.cookie = "shippify_longitude=" + marker.getPosition().lng();

        // We change the address field value. Then trigger 'change' event. Which triggers calculate_shipping()
        $( '#billing_address_1' ).val($( '#billing_address_1' ).val() + ' ' );

        $( '#billing_address_1' ).trigger( 'change' );
    });

});
