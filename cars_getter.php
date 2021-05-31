<?php 
/*
Plugin Name: Cars Getter
*/
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'CARS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/*
    For the JWT token
*/
require_once( CARS__PLUGIN_DIR . 'tokenizer.php' );
require_once( CARS__PLUGIN_DIR . 'fetch_cars.php' );

/**
 * get all cars from a car dealer by a given token
 */
function getCars($tokenIn){
    $token = $tokenIn;
    $params = array();
    $response = wp_remote_get(REST_ENDPOINT, 
    array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token
        )
        ));
        $jsondata = $response['body'];
        $obj = json_decode($jsondata, true);
        return $obj;
}


function insertIntoWoo(){
    $token = new Tokenizer('car1', API_USERNAME, API_PASSWORD);
    $newToken = $token->get_token();
    $params = array();

    $obj = getCars($newToken);
    if($obj){
        $params = array_keys($obj[0]);
    }

    insert_data($obj, $params);
             
}
        
function construct_meta_values_xml($obj, $headers){
    $meta = array();
    
    foreach ($headers as $header ){
        $meta[$header] = $obj[$header];
    }
    return $meta;
}

function car_exists($car){
    global $wpdb;
    $car_in_db = $wpdb->get_col(" SELECT ID from {$wpdb->posts} WHERE  post_type = 'product'");
    return in_array($car["ID"], $car_in_db);
    
}

function insert_car($car, $csv_obj){
        
    $meta_inp = construct_meta_values_xml($car, $csv_obj);
    $meta_inp["DEALER"] = $car["DEALER"];

    if (car_exists($car)){
       
        $args = array(
            'ID' => $car['ID'],
            'post_title' => $car['EXT_MODFRAMLEIDANDI'] . '-' . $car['ID'],
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_name' => $car['ID'],
            'post_content' => "",
        );
        
        $id = wp_insert_post($args);
        foreach($meta_inp as $field => $value ){
          
            update_field(strtolower($field), $value, $id);
            
        }
        $product = wc_get_product($id);
        $product->set_regular_price($car['VERD']);
        return $id;

    }
    else {
        $args = array(
            'import_id' => $car['ID'],
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_title' => $car['EXT_MODFRAMLEIDANDI'] . '-' . $car['ID'],
            'post_name' => $car['ID'],
            'post_content' => '',
        );
        echo 'inserting post';
        $id = wp_insert_post($args);
        foreach($meta_inp as $field => $value ){
            update_field(strtolower($field), $value, $id);
        }
        return $id;
    }
    
}

function insert_data($cars, $csv_obj){
    $test = array();
    foreach ($cars as $car){
        insert_car($car, $csv_obj);
    }
}

/* 
====================================
             CRON
===================================
*/
add_action('bl_cron_hook', 'insertIntoWoo');

register_activation_hook(__FILE__, 'my_activation');
function my_activation(){
    if(!wp_next_scheduled('bl_cron_hook')){
        wp_schedule_event(time(), 'hourly', 'bl_cron_hook');
    }
}
register_deactivation_hook(__FILE__, 'my_deactivation');
function my_deactivation(){
    wp_clear_scheduled_hook( 'bl_cron_hook' );
}

// Shortcode
add_shortcode('token', 'tokenizer_token');
function tokenizer_token(){
    $token = new Tokenizer('car1', API_USERNAME, API_PASSWORD);
    $newToken = $token->get_token();
    $params = array();

    $obj = getCars($newToken);
    echo '<pre>';
    print_r($obj);
    echo '</pre>';
}
