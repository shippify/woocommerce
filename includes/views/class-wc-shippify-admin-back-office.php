<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shippify general settings class. 
 * This class provides a settings section in which the admin will be able
 * to provide the APP ID and APP SECRET to access the Shippify API.
 */

class WC_Shippify_Admin_Back_Office{

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

    public function __construct() {
        add_filter( 'manage_edit-shop_order_columns', array($this, 'custom_shippify_order_column'),11);
        add_action( 'manage_shop_order_posts_custom_column' , array($this, 'custom_shippify_orders_column_content'), 10, 2 );
        add_filter( 'woocommerce_admin_order_actions', array($this,'add_shippify_order_action_button'), PHP_INT_MAX, 2 );
        add_action( 'admin_head', array($this,'add_shippify_order_actions_button_css' ));
        add_action( 'woocommerce_admin_order_actions_end', array( $this, 'execute_shippify_order_action' ) );
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_dispatch_action' ));
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this,'dispatch_bulk_action_handler'), 10, 3 );
    }


    function dispatch_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {

        if ( $doaction !== 'shippify_dispatch' ) {
            return $redirect_to;
        }
        foreach ( $post_ids as $post_id ) {
            if ((get_post_meta( $post_id, '_is_dispatched', true ) == 'no' || get_post_meta( $post_id, '_is_dispatched', true ) == '') && in_array("shippify", get_post_meta( $post_id, '_shipping_method', true ))){

                $res = $this->create_shippify_task($post_id);

                $response = json_decode($res['body'], true);
                update_post_meta($post_id, '_is_dispatched', 'noo');
                if (isset($response['id'])){
                    update_post_meta($post_id, '_is_dispatched', 'yes');
                    update_post_meta($post_id, '_shippify_id', $response['id']);
                }                
            }

        }
        $redirect_to = add_query_arg( 'bulk_dispatched_orders', count( $post_ids ), $redirect_to );
        return $redirect_to;
    }

    /**
     * Adds a new item into the Bulk Actions dropdown.
     */
    public function register_bulk_dispatch_action( $bulk_actions ) {
        $bulk_actions['shippify_dispatch'] = __( 'Dispatch (Shippify)', 'text' );

        return $bulk_actions;
    }
   


    function add_shippify_order_action_button( $actions, $the_order ) {
        //var_dump(get_post_meta( $the_order->id, '_is_dispatched', true ));
        if (in_array("shippify", get_post_meta( $the_order->id, '_shipping_method', true )) && (get_post_meta( $the_order->id, '_is_dispatched', true ) != 'yes')){ 
            $actions['shippify_action'] = array(
                'url'       => wp_nonce_url( admin_url( 'edit.php?post_type=shop_order&myaction=woocommerce_shippify_dispatch&stablishedorder=' . $the_order->id ), 'woocommerce-shippify-dispatch'), //esto hay que cambiar
                //'url'       => 'mifunction',
                'name'      => __( 'Dispatch', 'woocommerce-shippify' ),
                'action'    => "view shippify", // setting "view" for proper button CSS
            );
        }
        return $actions;
    }

    function execute_shippify_order_action($order){
        if ($_GET['myaction'] == 'woocommerce_shippify_dispatch' && $order->id == $_GET['stablishedorder'] ){
            $res = $this->create_shippify_task($order->id);
            //var_dump($res);
            $response = json_decode($res['body'], true);
            //var_dump($response);
            if (isset($response['id'])){
                update_post_meta($order->id, '_is_dispatched', 'yes');
                update_post_meta($order->id, '_shippify_id', $response['id']);
            }
            //var_dump(get_post_meta( $order->id, '_is_dispatched', true ));
            wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ));
            exit;
        }
    }


    function add_shippify_order_actions_button_css() {
        //echo '<style>.view.cancel::after { content: "\f174" !important; }</style>';
        echo '<style>.view.shippify::after { content: "" ;background: url(https://admin.shippify.co/panel_img/logo_big_login.png); background-size: 16px 17.5px; background-repeat: no-repeat;  background-position: center; }</style>';
    }

    /**
     * Hooked to woocommerce_get_sections_products,
     * This method creates the Shippify general settings section in the products tab.
     * 
     */
    public function custom_shippify_order_column($columns){
        $columns['order-status'] = __( 'Shippify Order Status','woocommerce-shippify');
        return $columns;
    }


    /**
     * Hooked to woocommerce_get_settings_products,
     * This method creates the fields of the Shippify general settings section.
     * 
     */
    public function custom_shippify_orders_column_content($column){
        global $post, $woocommerce, $the_order;
        $api_id = get_option('shippify_id');
        $api_secret = get_option('shippify_secret');
        $order_id = $the_order->id;
        $task_endpoint =  "https://api.shippify.co/task/info/" . get_post_meta( $order_id, '_shippify_id', true);

        //var_dump($task_endpoint);

        switch ( $column ){
            case 'order-status' :
                if (in_array("shippify", get_post_meta( $order_id, '_shipping_method', true)) && (get_post_meta( $the_order->id, '_is_dispatched', true ) == 'yes')){

                    $args = array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret )
                        ),
                        'method'  => 'GET'
                    );                    

                    $response = wp_remote_get( $task_endpoint, $args );
                    $decoded = json_decode($response['body'], true);
                    //var_dump($decoded);
                    //var_dump($response);
                    //var_dump($response['body']);
                    $status = $decoded['data']['status'];
                    
                    $col_val = $this->shippify_task_status[$status];


                }else{
                    $col_val = '-';
                }
                echo $col_val;

                break;
        }
    }

    public function create_shippify_task($order_id){

        $task_endpoint = "https://api.shippify.co/task/new";

        $products = '[{"id":"10234","name":"TV","qty":"2","size":"3","price":"0"}]'; //coger de package

        $sender_mail = "lkuffo@espol.edu.ec"; //poner y coger de settings

        $recipient_name = get_post_meta( $order_id, '_billing_first_name', true ) . get_post_meta( $order_id, '_billing_last_name', true ) ;
        $recipient_email = get_post_meta( $order_id, '_billing_email', true );
        $recipient_phone = get_post_meta( $order_id, '_billing_phone', true );


        $pickup_latitude = get_option( 'woocommerce_shippify_settings' )["warehouse_latitude"];
        $pickup_longitude = get_option( 'woocommerce_shippify_settings' )["warehouse_longitude"];
        $pickup_address =  "test"; //poner y coger de settings

        $deliver_lat = get_post_meta( $order_id, 'Latitude', true );
        $deliver_lon = get_post_meta( $order_id, 'Longitude', true );
        $deliver_address = get_post_meta( $order_id, '_billing_address_1', true ) .  get_post_meta( $order_id, '_billing_address_2', true );

        $note = get_post_meta( $order_id, 'Instructions', true );

        $ref_id = $order_id;

        $api_id = get_option('shippify_id');
        $api_secret = get_option('shippify_secret');

        $request_body = '
        {
            "task" : {
                "products": [
                    {
                        "id":"10234",
                        "name":"TV",
                        "qty":"2",
                        "size":"3"
                    }
                ],
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

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_secret ),
                'Content-Type' => 'application/json'
            ),
            'method'  => 'POST',
            'body' => $request_body
        );

        $response = wp_remote_post( $task_endpoint, $args );

        return $response;

    }    

}

$wcadminbackoffice = new WC_Shippify_Admin_Back_Office();

?>