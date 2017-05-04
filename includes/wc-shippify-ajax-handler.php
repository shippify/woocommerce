<?php
session_start();

/**
 * This file is only used to handle the dinamically produced Latitude and Longitude coordinates from the Map on checkout. 
 * In order to calculate the shipping dinamically, shippify-map.js file do a POST request to this file. SESSION variables are updated.
 * These SESSION variables are used to calculate the shipping in class-wc-shippify-shipping.php.  
 * @since   1.0.0
 * @version 1.0.0
*/

if ( isset( $_POST["shippify_longitude"] ) ) {
	$_SESSION["shippify_longitude"] = $_POST["shippify_longitude"]; 	
} 

if ( isset( $_POST["shippify_latitude"] ) ) {
	$_SESSION["shippify_latitude"] = $_POST["shippify_latitude"]; 	
} 