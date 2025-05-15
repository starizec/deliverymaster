<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmUser 
{
    public function getData($funcName) {
        if (method_exists($this, $funcName)) {
            return $this->$funcName();
        } else {
            return array();
        }
    }

    //DODATI KURIRE HrOverseas(), HrGLS

    public function HrDPD() {
        $plugin_version = ExplmLabelMaker::get_plugin_version();

        $server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        return [
            'domain' => $server_name, //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('explm_licence_option', ''),
            'email' => get_option('explm_email_option', ''),
            'username' => get_option("explm_dpd_username_option", ''),
            'password' => get_option("explm_dpd_password_option", ''),
            'platform' => 'wordpress',
            'version'   => $plugin_version,
            'language' => get_user_locale() 
        ];
    }

    public function HrOverseas() {
        $plugin_version = ExplmLabelMaker::get_plugin_version();

        $server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        return [
            'domain' => $server_name, //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('explm_licence_option', ''),
            'email' => get_option('explm_email_option', ''),
            'apiKey' => get_option("explm_overseas_api_key_option", ''),
            'platform' => 'wordpress',
            'version'   => $plugin_version,
            'language' => get_user_locale() 
        ];
    }

    public function HrHP() {
        $plugin_version = ExplmLabelMaker::get_plugin_version();

        $server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        return [
            'domain' => $server_name, //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('explm_licence_option', ''),
            'email' => get_option('explm_email_option', ''),
            'username' => get_option("explm_hp_username_option", ''),
            'password' => get_option("explm_hp_password_option", ''),
            'platform' => 'wordpress',
            'version'   => $plugin_version,
            'language' => get_user_locale() 
        ];
    }
}

function explm_initialize_user_data()
{
    return new ExplmUser();
}
add_action('plugins_loaded', 'explm_initialize_user_data');