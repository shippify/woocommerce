<?php

session_start();

if (isset($_POST["shippify_longitude"])){
	$_SESSION["shippify_longitude"] = $_POST["shippify_longitude"]; 	
} 

if (isset($_POST["shippify_latitude"])){
	$_SESSION["shippify_latitude"] = $_POST["shippify_latitude"]; 	
} 

?>