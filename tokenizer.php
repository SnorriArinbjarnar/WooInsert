<?php 

class Tokenizer {
    private $url;
    private $name;
    private $rest_username;
    private $rest_password;
    

    function __construct($name, $user, $pwd){
        $this->url = REST_TOKEN_ENDPOINT;
        $this->name = $name;
        $this->rest_username = $user;
        $this->rest_password = $pwd;
    }

    function get_token(){
        
        $key = 'auth_token_' . $this->name;
        $token = get_user_meta(1, $key, true);
        
        if(empty($token)){
            $token = $this->get_new_token();
            update_user_meta(1, $key, $token);
            return $token;
        }
        if($this->is_expired($token)){
            $token = $this->get_new_token();
            update_user_meta(1, $key, $token);
            return $token;
        }
        if($this->is_valid($token)){
            return $token;
        }
        return $token;


    }

    function get_new_token(){
        $url = $this->url . 'token';

        $opts = array(
            'method' => 'POST',
            'body' => array(
                'username' => $this->rest_username,
                'password' => $this->rest_password
            )
        );
        $response = wp_remote_post($url, $opts);
       
        $body = wp_remote_retrieve_body($response);
        $response_data = (!is_wp_error($response)) ? json_decode($body, true) : null;
        if($response_data){
            return $response_data['token'];
        }
        return $response_data;
    }

    function is_valid($token){
        $url = $this->url . 'token';

        $opts = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $token
            ),
        );
        
        $response = wp_remote_post($url, $opts);
       
        $body = wp_remote_retrieve_body($response);
        $response_data = (!is_wp_error($response)) ? json_decode($body, true) : null;
        if($response_data){
            if($response_data['data']['status'] == 200){
                return true;
            }
            else {
                return false;
            }
        }
    }

    function is_expired($token){
        $url = $this->url . 'token';

        $opts = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $token
            ),
        );
        
        $response = wp_remote_post($url, $opts);
       
        $body = wp_remote_retrieve_body($response);
        $response_data = (!is_wp_error($response)) ? json_decode($body, true) : null;
        if($response_data){
            if($response_data['message'] == "Expired token"){
                return True;
            }
            else {
                return False;
            }
        }
    }
}

