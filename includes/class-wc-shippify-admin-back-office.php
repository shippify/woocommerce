<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify Admin Back-Office class.
 * This class provides back-office functionality for the Admin to dispatch and
 * review orders that deliver with Shippify.
 *
 * @since   1.0.0
 * @version 1.2.3
 */
class WC_Shippify_Admin_Back_Office {


    /**
     * Shippify human readable equivalents to task status.
     *
     * @var array
     */
    protected $shippify_task_status = array(
        1  =>  'Getting Ready',
        2  =>  'Pending to assign',
        3  =>  'Getting Shipper Response',
        4  =>  'Shipper Confirmed',
        5  =>  'Picked up / In transit',
        6  =>  'Delivered',
        7  =>  'Completed Successfully',
        0  =>  'Not Delivered',
       -1  =>  'Cancelled'
    );

    /**
     * Initialize actions and filters.
     */
    public function __construct() {

        $this->retrieved_status = '';
        $this->fetched_flag = false;
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shippify_order_column' ), 11 );
        add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'fetch_shippify_orders_status' ), 10, 2  );
        add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_shippify_order_action_button' ), PHP_INT_MAX, 2 );
        add_action( 'admin_head', array( $this,'add_shippify_order_actions_button_css' ) );
        add_action( 'woocommerce_admin_order_actions_end', array( $this, 'execute_shippify_order_action' ) );
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_dispatch_action' ) );
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this,'dispatch_bulk_action_handler' ), 10, 3 );
        add_action( 'admin_notices', array($this, 'shippify_admin_notices') );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this,'display_order_data_in_admin' ) );

    }

    /**
     * Hooked to filter: manage_shop_order_posts_custom_column,
     * Fetch onto the Shippify dispatched order status and shows it in the order table.
     * @param string $column Shop order table column identifier.
     */
    public function fetch_shippify_orders_status( $column ) {
        global $post, $woocommerce, $the_order;

        $api_id = get_option( 'shippify_id' );
        $api_secret = get_option( 'shippify_secret' );
        $order_id = $the_order->id;

        switch ( $column ) {

            case 'order-status' :
                if ( false == $this->fetched_flag ) {

                    //Get all the orders
                    $this->fetched_flag = true;
                    $all = get_posts( array(
                        'numberposts' => -1,
                        'post_type'   => wc_get_order_types(),
                        'post_status' => array_keys( wc_get_order_statuses() ),
                    ));

                    $fetched_orders = '';

                    //Get all the orders shippify ID
                    foreach ( $all as $post ) {
                        $tmp = '';
                        $tmp = get_post_meta( $post->ID, '_shippify_id', true );
                        if ($tmp != '')
                        {
                            if ($fetched_orders == '')
                            {
                                $fetched_orders .= $tmp;
                            }
                            else
                            {
                                $fetched_orders .= ','.$tmp;
                            }
                        }

                    }

                    //Prepare the request and store the response in an instance variable
                    $args = array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
                        )
                        //'method'  => 'GET'
                    );

                    $fetch_endpoint = 'https://api.shippify.co/v1/deliveries/' . $fetched_orders;

                    $response = wp_remote_get( $fetch_endpoint, $args );

                    if ( is_wp_error( $response ) ) {
                        $this->retrieved_status = "Error Fetching. Try Again.";
                    }else {
                        $decoded = json_decode( $response['body'], true );
                        if ( ! isset($decoded["deliveries"])){
                            $this->retrieved_status = "Error Fetching. Try Again.";
                        }else{
                            $this->retrieved_status = $decoded["deliveries"];
                        }

                    }
                }

                $shippify_is_selected = false;
                $shipping_methods = get_post_meta( $order_id, '_shipping_method', true );
                if ( is_array( $shipping_methods ) ) {
                    if ( in_array( "shippify", $shipping_methods ) ) {
                        $shippify_is_selected = true;
                    }
                } else {
                    if ( "shippify" == $shipping_methods ){
                        $shippify_is_selected = true;
                    }
                }

                //Search for every order status shipped via-Shippify on the response.
                if ( $shippify_is_selected && ( 'yes' == get_post_meta( $the_order->id, '_is_dispatched', true ) ) ){
                    //creo que esto no se usa
                    //$order_to_fetch = get_post_meta( $order_id, '_shippify_id', true );

                    if ( $this->retrieved_status == "Error Fetching. Try Again." ) {
                        $col_val = "Error Fetching. Try Again.";
                    }else {
                        $found = false;
                        foreach ( $this->retrieved_status as $response_order ) {
                            if ( $response_order['order'] == $order_id) {
                                $col_val = __($this->shippify_task_status[$response_order["state"]],'woocommerce-shippify');
                                $found = true;
                                break;
                            }
                        }
                        if ( false == $found ) {
                            $col_val = "Error Fetching. Try Again.";
                        }
                    }
                }else {
                    $col_val = '-';
                }
                //Show the result on the table
                echo $col_val;

            break;
        }
    }



    /**
    * Hooked to action: admin_notices.
    * Shows admin alerts when the user performs a dispatch action or dispatch bulk action Shippify or
    * when the user hasn't set their Shippify credentials.
    * @param string $doaction Bulk action applied.
    * @param string $redirect_to URL to redirect.
    * @return array $post_ids Contains all the selected orders ids.
    */
    public function shippify_admin_notices() {
        // Single Dispatch Error
        if ( isset($_GET['error']) && 'singleError' == $_GET['error']  && isset($_GET['post_type']) && 'shop_order' == $_GET['post_type'] ) {
            echo '<div class="error notice is-dismissible"><p>' . __('The order','woocommerce-shippify'). '#'. $_GET['order_dispatched'] . __(' was not dispatched correctly. Check your settings or try again later.','woocommerce-shippify') . '</p></div>';
        }
        // Bulk Dispatch Error
        if ( isset($_GET['error']) && 'multipleError' == $_GET['error']  && isset($_GET['post_type']) && 'shop_order' == $_GET['post_type'] ) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . __('One or more orders were not successfully dispatched. Try dispatching orders individually, check your settings or try again later.','woocommerce-shippify') . '</p></div>';
        }
        // Single Dispatch Success
        if ( isset($_GET['error']) &&  'none' == $_GET['error'] && isset($_GET['post_type']) && 'shop_order' == $_GET['post_type'] && ! isset( $_GET['bulk_dispatched_orders'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('The order','woocommerce-shippify'). '#'. $_GET['order_dispatched'] . __(' was dispatched successfully. ','woocommerce-shippify') . '</p></div>';
        }
        // Bulk Dispatch Success
        if ( isset($_GET['error']) && 'none' == $_GET['error']  && isset( $_GET['bulk_dispatched_orders'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('All the selected orders were dispatched successfully.','woocommerce-shippify') . '</p></div>';
        }
        // Empty Credentials
        if ( isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['section'] ) && 'shippify-integration' == $_GET['section'] ) return; // Don't show these notices in the same settings screen.

        $api_key = get_option( 'shippify_id' );
        $api_secret = get_option( 'shippify_secret' );

        if ( '' == $api_key || '' == $api_secret ) {
            $url = $this->get_settings_url();
            echo '<div class="updated fade"><p>' . sprintf( __( '%sWooCommerce Shippify is almost ready.%s To get started, %s connect your Shippify account%s.', 'woocommerce-shippify' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
        }
    }

    /**
    *
    * Get Shippify Integration Settings URL.
    * @return string Contains the Shippify Integration Settings URL.
    */
    public function get_settings_url(){
        $url = admin_url( 'admin.php' );
        $url = add_query_arg( 'page', 'wc-settings', $url );
        $url = add_query_arg( 'tab', 'integration', $url );
        $url = add_query_arg( 'section', 'shippify-integration', $url );

        return $url;
    }


    /**
    * Hooked to filter: handle_bulk_actions-edit-shop_order.
    * Handle the bulk action to dispatch orders with Shippify.
    * @param string $doaction Bulk action applied.
    * @param string $redirect_to URL to redirect.
    * @return array $post_ids Contains all the selected orders ids.
    */
    function dispatch_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {

        if ( $doaction !== 'shippify_dispatch' ) {
            return $redirect_to;
        }
        foreach ( $post_ids as $post_id ) {

            $shippify_is_selected = false;
            $shipping_methods = get_post_meta( $post_id, '_shipping_method', true );
            if ( is_array( $shipping_methods ) ) {
                if ( in_array( "shippify", $shipping_methods ) ) {
                    $shippify_is_selected = true;
                }
            } else {
                if ( "shippify" == $shipping_methods ){
                    $shippify_is_selected = true;
                }
            }

            if ( (get_post_meta( $post_id, '_is_dispatched', true ) == 'no' || get_post_meta( $post_id, '_is_dispatched', true ) == '' ) && $shippify_is_selected ) {

                $res = $this->create_shippify_task($post_id);

                if ( $res != false ) {
                    $response = json_decode( $res['body'], true );
                    if ( isset($response['id'] ) ) {
                        update_post_meta( $post_id, '_is_dispatched', 'yes' );
                        update_post_meta( $post_id, '_shippify_id', $response['id'] );
                        $error = 'none';
                    }else {
                        $error = 'multipleError';
                    }
                }else {
                    $error = 'multipleError';
                }

            }

        }
        $redirect_to = add_query_arg( array(
            'bulk_dispatched_orders' => count( $post_ids ),
            'error' => $error
            ),
            $redirect_to );

        return $redirect_to;
    }

    /**
     * Hooked to filter: bulk_actions-edit-shop_order.
     * Adds a new item into the Bulk Actions dropdown of shop orders.
     * @param array $bulk_actions All the bulk actions
     */
    public function register_bulk_dispatch_action( $bulk_actions ) {

        $bulk_actions['shippify_dispatch'] = __( 'Dispatch (Shippify)', 'woocommerce-shippify' );

        return $bulk_actions;
    }


    /**
     * Hooked to action: woocommerce_admin_order_actions.
     * Adds a new action button to the shop orders that are being shipped via Shippify.
     * @param array $actions All the actions.
     * @param WC_Order $the_order The order.
     */
    function add_shippify_order_action_button( $actions, $the_order ) {

        $shippify_is_selected = false;
        $shipping_methods = get_post_meta( $the_order->id, '_shipping_method', true );
        if ( is_array( $shipping_methods ) ) {
            if ( in_array( "shippify", $shipping_methods ) ) {
                $shippify_is_selected = true;
            }
        } else {
            if ( "shippify" == $shipping_methods ){
                $shippify_is_selected = true;
            }
        }
        if ( $shippify_is_selected && ( get_post_meta( $the_order->id, '_is_dispatched', true ) != 'yes' ) && ! isset( $_GET['post_status'] ) ) {
            $actions['shippify_action'] = array(
                'url'       => wp_nonce_url( admin_url( 'edit.php?post_type=shop_order&myaction=woocommerce_shippify_dispatch&stablishedorder=' . $the_order->id ), 'woocommerce-shippify-dispatch' ),
                'name'      => __( 'Dispatch', 'woocommerce-shippify' ),
                'action'    => "view shippify",     // setting "view" for proper button CSS.
            );
        }
        return $actions;
    }


    /**
     * Hooked to action: woocommerce_admin_order_actions_end.
     * Serves the call of the Shippify Dispatch Action. Creates the task corresponding to the order.
     * @param WC_Order $order The order to be dispatched.
     */
    function execute_shippify_order_action( $order ) {
        $extra = '';
        if (  'woocommerce_shippify_dispatch' == $_GET['myaction'] && $order->id == $_GET['stablishedorder'] ) {
            $res = $this->create_shippify_task($order->id);
            if ( $res != false ) {
                $response = json_decode($res['body'], true);
                if ( isset($response['id'] ) ) {
                    update_post_meta( $order->id, '_is_dispatched', 'yes' );
                    update_post_meta( $order->id, '_shippify_id', $response['id'] );
                    $extra = 'none';
                }else {
                    $extra = 'singleError';
                }
            }else {
                $extra = 'singleError';
            }
            $redirect = admin_url( 'edit.php?post_type=shop_order&order_dispatched='. $order->id .'&error=' . $extra );
            wp_safe_redirect( $redirect );
            exit;
        }
    }

    /**
     * Hooked to action: admin_head.
     * Adds css to the Shippify dispatch action button.
     */
    function add_shippify_order_actions_button_css() {

        //echo '<style>.view.cancel::after { content: "\f174" !important; }</style>';
        echo '<style>.view.shippify::after { content: "" ;background: url(https://admin.shippify.co/panel_img/logo_big_login.png); background-size: 16px 17.5px; background-repeat: no-repeat;  background-position: center; }</style>';
    }

    /**
     * Hooked to filter: manage_edit-shop_order_columns.
     * Add a column to the shop orders table.
     * @param array $columns Shop order table columns.
     */
    public function custom_shippify_order_column( $columns ) {
        if ($_GET['post_status'] != 'trash'){
            $columns['order-status'] = __( 'Shippify Order Status','woocommerce-shippify');
        }

        return $columns;
    }


    /**
     * Hooked to Action: woocommerce_admin_order_data_after_order_details
     * Display Shippify order meta data in the order detail admin page.
     * @param WC_Order $order The order
     */
    public function display_order_data_in_admin( $order ) {
        $shippify_is_selected = false;
        $shipping_methods = get_post_meta( $order->id, '_shipping_method', true );
        if ( is_array( $shipping_methods ) ) {
            if ( in_array( "shippify", $shipping_methods ) ) {
                $shippify_is_selected = true;
            }
        } else {
            if ( "shippify" == $shipping_methods ){
                $shippify_is_selected = true;
            }
        }
        if ( $shippify_is_selected  ) {
            ?>
            <div class="order_data_column">
                <h4><?php _e( 'Shippify', 'woocommerce' ); ?></h4>
                <?php
                    echo '<p><strong>' . __( 'Instructions', 'woocommerce-shippify' ) . ':</strong>' . " \n" . get_post_meta( $order->id, 'Instructions', true ) . '</p>';
                    echo '<p><strong>' . __( 'Shippify ID', 'woocommerce-shippify' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, '_shippify_id', true ) . '</p>';
                    echo '<p><strong>' . __( 'Deliver Latitude', 'woocommerce-shippify' ) . ':</strong>' .  " \n"  .  get_post_meta( $order->id, 'Latitude', true ) . '</p>';
                    echo '<p><strong>' . __( 'Deliver Longitude', 'woocommerce-shippify' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, 'Longitude', true ) . '</p>';
                    echo '<p><strong>' . __( 'Pickup Latitude', 'woocommerce-shippify' ) . ':</strong>' .  " \n"  .  get_post_meta( $order->id, 'pickup_latitude', true ) . '</p>';
                    echo '<p><strong>' . __( 'Pickup Longitude', 'woocommerce-shippify' ) . ':</strong>' .  " \n"  . get_post_meta( $order->id, 'pickup_longitude', true ) . '</p>'; ?>
            </div>
            <?php
        }
    }

    /**
     * Creates the Shippify Task of a shop order.
     * @param string $order_ir Shop order identifier.
     */
    public function create_shippify_task( $order_id ) {

        $task_endpoint = "https://api.shippify.co/task/new";

        $order = new WC_Order( $order_id );

        // Sender Email
        $sender_mail = get_option( 'shippify_sender_email' );

        // Recipient Information
        $recipient_name = get_post_meta( $order_id, '_billing_first_name', true ) . get_post_meta( $order_id, '_billing_last_name', true ) ;
        $recipient_email = get_post_meta( $order_id, '_billing_email', true );
        $recipient_phone = get_post_meta( $order_id, '_billing_phone', true );

        // Shipping Zone information
        $pickup_warehouse = get_post_meta( $order_id, 'pickup_id', true );
        $pickup_latitude = get_post_meta( $order_id, 'pickup_latitude', true );
        $pickup_longitude = get_post_meta( $order_id, 'pickup_longitude', true );
        $pickup_address = get_post_meta( $order_id, 'pickup_address', true );

        // Delivery Information
        $deliver_lat = get_post_meta( $order_id, 'Latitude', true );
        $deliver_lon = get_post_meta( $order_id, 'Longitude', true );
        $deliver_address = get_post_meta( $order_id, '_billing_address_1', true ) .  get_post_meta( $order_id, '_billing_address_2', true );

        // References
        $note = get_post_meta( $order_id, 'Instructions', true );

        $ref_id = $order_id;

        // Credentials
        $api_id = get_option( 'shippify_id' );
        $api_secret = get_option( 'shippify_secret' );

        // Constructing the items array
        $items = "[";
        foreach ( $order->get_items() as $item_id => $_preproduct ) {
            $_product = $_preproduct->get_product();
            $items = $items . '{"id":"' . $_product->get_id() . '",
                                "name":"' . $_product->get_name() . '",
                                "qty": "' . $_preproduct['quantity'] . '",
                                "size": "' . $this->calculate_product_shippify_size($_product) . '"
                                },';
        }
        $items = substr( $items, 0, -1 ) . ']';

        // Basic Auth
        $wh_args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
            ),
            'method'  => 'GET'
        );

        // If there is defined a warehouse id. Check if valid. Then use the coordinates of that warehouse.
        $pickup_id = '';
        if ( "" != $pickup_warehouse  || isset( $pickup_warehouse ) ) {
            $warehouse_response = wp_remote_get( 'https://api.shippify.co/warehouse/list', $wh_args );
            if ( ! is_wp_error( $warehouse_response ) ) {
                $warehouse_response = json_decode( $warehouse_response['body'], true );
                $warehouse_info = $warehouse_response["warehouses"];
                foreach ( $warehouse_info as $warehouse ) {
                    if ( $warehouse["id"] == $pickup_warehouse ) {
                        $pickup_id = $pickup_warehouse;
                        break;
                    }
                }
            }
        }

        if ( '' == $pickup_id ) {
            $warehouse_to_request = '';
        } else {
            $warehouse_to_request = ',
                "warehouse": "' .  $pickup_id .'"';
        }

        // Checking if Cash on Delivery
        $total_amount = '';
        $payment_method = get_post_meta( $order_id, '_payment_method', true );
        if ( 'cod' == $payment_method ) {
            $order_total = $order->get_total();
            $total_amount = '"total_amount": "' . $order_total . '",';
        }

        // Constructing the POST request
        $request_body = '
        {
            "task" : {
                "products": ' . $items . ',
                "sender" : {
                    "email": "' . $sender_mail . '"
                },
                "recipient": {
                    "name": "' . $recipient_name . '",
                    "email": "' . $recipient_email . '",
                    "phone": "' . $recipient_phone . '"
                },
                "pickup": {
                    "lat": ' . $pickup_latitude . ',
                    "lng": ' . $pickup_longitude . ',
                    "address": "' . $pickup_address . '"'. $warehouse_to_request . '
                },
                '. $total_amount . '
                "deliver": {
                    "lat": ' . $deliver_lat . ',
                    "lng": ' . $deliver_lon . ',
                    "address": "' . $deliver_address . '"
                },
                "ref_id": "' . $ref_id . '",
                "extra": {
                    "note":  "' . $note . '"
                }
            }
        }';

        //Basic Authorization

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret ),
                'Content-Type'  => 'application/json'
            ),
            'method'  => 'POST',
            'body'    => $request_body
        );

        $response = wp_remote_post( $task_endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return $response;

    }

    /**
    * Diffuse Logic Algorithm used to calculate Shippify product size based on the product dimensions.
    * @param WC_Product The product to calculate the size.
    */
    public function calculate_product_shippify_size( $product ) {

        $height = $product->get_height();
        $width  = $product->get_width();
        $length = $product->get_length();

        if ( ! isset($height) ||  "" == $height ) {
            return "3";
        }
        if ( ! isset($width) || "" == $width ) {
            return "3";
        }
        if ( ! isset($length) || "" == $length ) {
            return "3";
        }

        $width = floatval( $width );
        $height = floatval( $height );
        $length = floatval( $length );

        $array_size = array( 1, 2, 3, 4, 5 );
        $array_dimensions = array( 50, 80, 120, 150, 150 );
        $radio_membership = 10;
        $dimensions_array = array( 10, 10, 10 );
        $final_percentages = array();

        foreach ( $array_size as $size ) {
            $percentage = 0;
            $max_percentage = 100/3;
            foreach ( $dimensions_array as $dimension ) {
                if  ( $dimension < $array_dimensions[$size-1] ) {
                    $percentage = $percentage + $max_percentage;
                }elseif( $dimension < $array_dimensions[$size-1] + $radio_membership ) {
                    $pre_result = ( 1-( abs( $array_dimensions[$size-1] - $dimension ) / ( 2 * $radio_membership ) ) );
                    $tmp_p = $pre_result < 0 ? 0 : $pre_result;
                    $percentage = $percentage + ( ( ( $pre_result * 100 ) * $max_percentage ) / 100 );
                }else {
                    $percentage = $percentage + 0;
                }
            }
            $final_percentages[] = $percentage;
        }
        $maxs = array_keys( $final_percentages, max( $final_percentages ) );
        return $array_size[$maxs[0]];
    }
}

new WC_Shippify_Admin_Back_Office();
