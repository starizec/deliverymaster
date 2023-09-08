<?php

class user 
{
    public function getData($funcName) {
        if(method_exists($this, $funcName)){
            return $this->$funcName();
        } else {
            return array();
        }
    }

    public function HrDPD(){
        return [
            'domain' => $_SERVER['SERVER_NAME'],
            'activation_key' => get_option('elm_activation_key_option', ''),
            'email' => get_option('elm_email_option', ''),
            'username' => get_option("elm_dpd_username_option", ''),
            'password' => get_option("elm_dpd_password_option", '')
        ];
    }
}

function initialize_elm_user_data()
{
    return new user();
}
add_action('plugins_loaded', 'initialize_elm_user_data');