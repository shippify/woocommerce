<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify Admin Back-Office class. 
 * This class provides back-office functionality for the Admin to dispatch and
 * review orders that deliver with Shippify.
 */
class WC_Shippify_Admin_Back_Office{

    public $fetched_orders = "";

    /**
     * Shippify human readable equivalents to task status.
     *
     * @var array
     */
    protected $shippify_task_status = array(
        1 => 'Getting Ready',
        2 =>  'Available',
        3 =>  'Driver Assigned / Waiting for Response',
        4 =>  'Driver Applied',
        5 =>  'Picked Up',
        6 =>  'Delivered',
        7 =>  'Confirmed',
        0 =>  'Not Delivered',
        -1 => 'Canceled'
    );

    /**
     * Initialize actions and filters.
     */
    public function __construct() {
        add_filter( 'manage_edit-shop_order_columns', array($this, 'custom_shippify_order_column'),11);
        add_action( 'manage_shop_order_posts_custom_column' , array($this, 'custom_shippify_orders_column_content'), 10, 2 );
        add_filter( 'woocommerce_admin_order_actions', array($this,'add_shippify_order_action_button'), PHP_INT_MAX, 2 );
        add_action( 'admin_head', array($this,'add_shippify_order_actions_button_css' ));
        add_action( 'woocommerce_admin_order_actions_end', array( $this, 'execute_shippify_order_action' ));
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_dispatch_action' ));
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this,'dispatch_bulk_action_handler'), 10, 3 );
        add_action( 'admin_notices', array($this,'shippify_admin_notices'));
    }

    /**
    * Hooked to action: admin_notices.
    * Shows admin alerts when the user performs a dispatch action or dispatch bulk action Shippify.
    * @param string $doaction Bulk action applied.
    * @param string $redirect_to URL to redirect.
    * @return array $post_ids Contains all the selected orders ids.
    */   
    public function shippify_admin_notices(){

        if ($_GET['error'] == 'singleError' && $_GET['post_type'] == 'shop_order' ){
            echo '<div class="error notice is-dismissible"><p>' . 'The order #'. $_GET['order_dispatched'] . ' was not dispatched correctly. Check your settings or try again later.' . '</p></div>';    
        }
        if ($_GET['error'] == 'multipleError' && $_GET['post_type'] == 'shop_order' ){
            echo '<div class="notice notice-warning is-dismissible"><p>' . 'One or more orders were not successfully dispatched. Try dispatching orders individually, check your settings or try again later.' . '</p></div>';    
        }
        if ($_GET['error'] == 'none' && $_GET['post_type'] == 'shop_order' && !isset($_GET['bulk_dispatched_orders'])){
            echo '<div class="notice notice-success is-dismissible"><p>' . 'The order #'. $_GET['order_dispatched'] . ' was dispatched successfully. ' . '</p></div>'; 
        }
        if ($_GET['error'] == 'none' && isset($_GET['bulk_dispatched_orders'])){
            echo '<div class="notice notice-success is-dismissible"><p>' . 'All the selected orders were dispatched successfully.' . '</p></div>'; 
        }
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
            if ((get_post_meta( $post_id, '_is_dispatched', true ) == 'no' || get_post_meta( $post_id, '_is_dispatched', true ) == '') && in_array("shippify", get_post_meta( $post_id, '_shipping_method', true ))){

                $res = $this->create_shippify_task($post_id);

                if ($res != false){
                    $response = json_decode($res['body'], true);
                    if (isset($response['id'])){
                        update_post_meta($post_id, '_is_dispatched', 'yes');
                        update_post_meta($post_id, '_shippify_id', $response['id']);
                        $error = 'none';
                    }else{
                        $error = 'multipleError';    
                    } 
                }else{
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
        $bulk_actions['shippify_dispatch'] = __( 'Dispatch (Shippify)', 'text' );

        return $bulk_actions;
    }
   

    /**
     * Hooked to action: woocommerce_admin_order_actions.
     * Adds a new action button to the shop orders that are being shipped via Shippify.
     * @param array $actions All the actions.
     * @param WC_Order $the_order The order.
     */
    function add_shippify_order_action_button( $actions, $the_order ) {

        // esto me dio error en algunas ordenes... 
        if (in_array("shippify", get_post_meta( $the_order->id, '_shipping_method', true )) && (get_post_meta( $the_order->id, '_is_dispatched', true ) != 'yes') && !isset($_GET['post_status'])){ 
            $actions['shippify_action'] = array(
                'url'       => wp_nonce_url( admin_url( 'edit.php?post_type=shop_order&myaction=woocommerce_shippify_dispatch&stablishedorder=' . $the_order->id ), 'woocommerce-shippify-dispatch'), 
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
    function execute_shippify_order_action($order){
        $extra = '';
        if ($_GET['myaction'] == 'woocommerce_shippify_dispatch' && $order->id == $_GET['stablishedorder'] ){
            $res = $this->create_shippify_task($order->id);
            if ($res != false){
                $response = json_decode($res['body'], true);
                if (isset($response['id'])){
                    update_post_meta($order->id, '_is_dispatched', 'yes');
                    update_post_meta($order->id, '_shippify_id', $response['id']);
                    $extra = 'none';
                }else{
                    $extra = 'singleError';
                }
            }else{
                $extra = 'singleError';
            }
            $redirect = admin_url( 'edit.php?post_type=shop_order&order_dispatched='. $order->id .'&error=' . $extra );
            wp_safe_redirect($redirect);
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
    public function custom_shippify_order_column($columns){
        $columns['order-status'] = __( 'Shippify Order Status','woocommerce-shippify');
        return $columns;
    }


    /**
     * Hooked to filter: manage_shop_order_posts_custom_column,
     * Fetch onto the Shippify dispatched order status and shows it in the order table.
     * @param string $column Shop order table column identifier. 
     */
    public function custom_shippify_orders_column_content($column){

        global $post, $woocommerce, $the_order;
        $api_id = get_option('shippify_id');
        $api_secret = get_option('shippify_secret');
        $order_id = $the_order->id;
        $task_endpoint =  "https://api.shippify.co/task/info/" . get_post_meta( $order_id, '_shippify_id', true);  

        switch ( $column ){
            case 'order-status' :
                if (in_array("shippify", get_post_meta( $order_id, '_shipping_method', true)) && (get_post_meta( $the_order->id, '_is_dispatched', true ) == 'yes')){
                    $this->fetched_orders = $this->fetched_orders . get_post_meta( $order_id, '_shippify_id', true). ',';

                    // Basic Auth
                    $args = array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
                        ),
                        'method'  => 'GET'
                    );                    
                    // Fetching the task
                    $response = wp_remote_get( $task_endpoint, $args );
                    if (is_wp_error($response)){
                        $col_val = "Error Fetching. Try Again.";
                    }else{
                        $decoded = json_decode($response['body'], true);
                        $status = $decoded['data']['status'];
                        if (!isset($status) || $status == ""){
                            $col_val = "Error Fetching. Try Again.";    
                        }else{
                            $col_val = $this->shippify_task_status[$status]; 
                        }                
                    }
                //When the order isnt shipped via Shippify
                }else{
                    $col_val = '-';
                }

                echo $col_val;

                break;

        }
        //echo "hola";
    }

    /**
     * Creates the Shippify Task of a shop order.
     * @param string $order_ir Shop order identifier. 
     */
    public function create_shippify_task($order_id){

        $task_endpoint = "https://api.shippify.co/task/new";

        $order = new WC_Order($order_id);

        $products = '[{"id":"10234","name":"TV","qty":"2","size":"3","price":"0"}]'; //coger de package

        $sender_mail = "lkuffo@espol.edu.ec"; //poner y coger de settings

        $recipient_name = get_post_meta( $order_id, '_billing_first_name', true ) . get_post_meta( $order_id, '_billing_last_name', true ) ;
        $recipient_email = get_post_meta( $order_id, '_billing_email', true );
        $recipient_phone = get_post_meta( $order_id, '_billing_phone', true );


        $pickup_latitude = get_post_meta( $order_id, 'pickup_latitude', true );
        $pickup_longitude = get_post_meta( $order_id, 'pickup_longitude', true );
        $pickup_address =  "test"; //poner y coger de settings

        $deliver_lat = get_post_meta( $order_id, 'Latitude', true );
        $deliver_lon = get_post_meta( $order_id, 'Longitude', true );
        $deliver_address = get_post_meta( $order_id, '_billing_address_1', true ) .  get_post_meta( $order_id, '_billing_address_2', true );

        $note = get_post_meta( $order_id, 'Instructions', true );

        $ref_id = $order_id;

        $api_id = get_option('shippify_id');
        $api_secret = get_option('shippify_secret');

        $items = "[";
        foreach ($order->get_items() as $item_id => $_product ) { 
            $_product = $_product->get_product();
            $items = $items . '{"id":"' . $_product->get_id() . '", 
                                "name":"' . $_product->get_name() . '", 
                                "qty": "' . '1' . '", 
                                "size": "' . $this->calculate_product_shippify_size($_product) . '"
                                },';
        }
        $items = substr($items, 0, -1) . ']';

        $request_body = '
        {
            "task" : {
                "products": '. $items . ',
                "sender" : {
                    "email": "'. $sender_mail . '"
                },
                "recipient": {
                    "name": "'. $recipient_name . '",
                    "email": "'. $recipient_email . '",
                    "phone": "'. $recipient_phone . '"
                },
                "pickup": {
                    "lat": '. $pickup_latitude . ',
                    "lng": '. $pickup_longitude . ',
                    "address": "'. $pickup_address . '"
                },
                "deliver": {
                    "lat": '. $deliver_lat . ',
                    "lng": '. $deliver_lon . ',
                    "address": "'. $deliver_address . '"
                },
                "ref_id": "'. $ref_id .'",
                "extra": {
                    "note":  "'. $note . '" 
                }
            }
        }';

        //Basic Authorization

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret ),
                'Content-Type' => 'application/json'
            ),
            'method'  => 'POST',
            'body' => $request_body
        );

        $response = wp_remote_post( $task_endpoint, $args );

        if (is_wp_error($response)){
            return false;
        }


        return $response;

    }

    public function calculate_product_shippify_size($product){

        $height = $product->get_height();
        $width = $product->get_width();
        $length = $product->get_length();

        if (!isset($height) || $height == ""){
            return "3";
        }
        if (!isset($width) || $width == ""){
            return "3";
        }
        if (!isset($length) || $length == ""){
            return "3";
        }

        $width = floatval($width);
        $height = floatval($height);
        $length = floatval($length);

        $array_size = array(1,2,3,4,5); 
        $array_dimensions = array(50,80,120,150,150);
        $radio_membership = 10;
        $dimensions_array = array(10, 10, 10);
        $final_percentages = array();

        foreach ($array_size as $size){
            $percentage = 0;
            $max_percentage = 100/3;
            foreach ($dimensions_array as $dimension) {
                if  ($dimension < $array_dimensions[$size-1]){
                    $percentage = $percentage + $max_percentage;
                }elseif($dimension < $array_dimensions[$size-1] + $radio_membership){
                    $pre_result = (1-(abs($array_dimensions[$size-1] - $dimension) / (2 * $radio_membership)));
                    $tmp_p = $pre_result < 0 ? 0 : $pre_result;
                    $percentage = $percentage + ((($pre_result * 100) * $max_percentage) / 100);
                }else{
                    $percentage = $percentage + 0;
                }
            }
            $final_percentages[] = $percentage;
        }
        $maxs = array_keys($final_percentages, max($final_percentages));
        return $array_size[$maxs[0]];
    }
}

new WC_Shippify_Admin_Back_Office();

?>