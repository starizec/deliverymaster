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
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    
        $plugin_file = dirname(__DIR__) . '/express-label-maker.php'; 
        $plugin_data = get_plugin_data($plugin_file);
        $plugin_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';

        $server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        return [
            'domain' => $server_name, //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('explm_licence_option', ''),
            'email' => get_option('explm_email_option', ''),
            'username' => get_option("explm_dpd_username_option", ''),
            'password' => get_option("explm_dpd_password_option", ''),
            'platform' => 'wordpress',
            'version'   => $plugin_version 
        ];
    }

    public function HrOverseas() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    
        $plugin_file = dirname(__DIR__) . '/express-label-maker.php';
        $plugin_data = get_plugin_data($plugin_file);
        $plugin_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';

        $server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        return [
            'domain' => $server_name, //DODATI EVENTUALNO ZA TEST
            'licence' => get_option('explm_licence_option', ''),
            'email' => get_option('explm_email_option', ''),
            'apiKey' => get_option("explm_overseas_api_key_option", ''),
            'platform' => 'wordpress',
            'version'   => $plugin_version 
        ];
    }
}

function explm_initialize_user_data()
{
    return new ExplmUser();
}
add_action('plugins_loaded', 'explm_initialize_user_data');