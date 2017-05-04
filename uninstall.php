<?php
/**
 * WooCommerce Shippify Uninstall
 *
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


delete_option( 'shippify_id' );
delete_option( 'shippify_secret' );