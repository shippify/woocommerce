/*
 * JavaScript file to produce the interactive Map and its functionalities in Checkout.
 */

var marker;
var map;

// Init the checkout map and configurates it.
function initMap() {
    var uluru = {lat: -25.363, lng: 131.044};
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 1,
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

function placeMarker(location) {
    marker.setMap(null);
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
        $("#shippify_latitude").val(marker.getPosition().lat());
        $("#shippify_latitude").trigger('change');

        $("#shippify_longitude").val(marker.getPosition().lng());
        $("#shippify_longitude").trigger('change'); 
    // POST request made to our handler file to update the SESSION variables of latitude and longitude coordinates which are used to calculate shipping.
        $.post("../wp-content/plugins/woocommerce-shippify/includes/wc-shippify-ajax-handler.php", {
            shippify_latitude:marker.getPosition().lat(), 
            shippify_longitude:marker.getPosition().lng()
        });
    });
});
