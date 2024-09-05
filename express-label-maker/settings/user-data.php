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

    //DODATI KURIRE HrOverseas(), HrGLS

    public function HrDPD(){
        return [
            'domain' => $_SERVER['SERVER_NAME'], //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('elm_licence_option', ''),
            'email' => get_option('elm_email_option', ''),
            'username' => get_option("elm_dpd_username_option", ''),
            'password' => get_option("elm_dpd_password_option", ''),
            'platform' => 'wordpress'
        ];
    }

    public function HrOverseas(){
        return [
            'domain' => $_SERVER['SERVER_NAME'], //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('elm_licence_option', ''),
            'email' => get_option('elm_email_option', ''),
            'apiKey' => get_option("elm_overseas_api_key_option", ''),
            'platform' => 'wordpress'
        ];
    }
}

function initialize_elm_user_data()
{
    return new user();
}
add_action('plugins_loaded', 'initialize_elm_user_data');