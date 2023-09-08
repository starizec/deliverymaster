<?php

class ElmPrintLabel
{

    public function __construct()
    {
        add_action('wp_ajax_elm_print_label', array($this, 'elm_print_label'));
    }

    function elm_print_label() {
        
        check_ajax_referer('elm_nonce', 'security');

        $courier = $_POST['chosenCourier'];
    
        $saved_country = get_option("elm_country_option", '');
        $saved_username = get_option("elm_{$courier}_username_option", '');
        $saved_password = get_option("elm_{$courier}_password_option", '');
        $saved_email = get_option('elm_email_option', '');
        $saved_activation_key = get_option('elm_activation_key_option', '');
        
        $domain = $_SERVER['SERVER_NAME'];
    
        $user_data = array(
            "username" => $saved_username,
            "password" => $saved_password,
            "domain" => $domain,
            "licence" => $saved_activation_key,
            "email" => $saved_email
        );
    
        $parcel_data = $_POST['parcel'];
    
        $body = array(
            "user" => $user_data,
            "parcel" => $parcel_data
        );

        error_log(print_r($parcel_data, true));
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body)
        );
    
        $response = wp_remote_request('https://api.expresslabelmaker.com/wordpress/' . $saved_country . '/' . $courier . '/printLabel', $args);
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('info' => $response->get_error_message()));
        } else {
            $body_response = json_decode(wp_remote_retrieve_body($response), true);
            error_log(print_r($response, true));
            /* error_log(print_r($body_response, true)); */

            if (substr($response['response']['code'], 0, 1) == '2') {
   
                $decoded_data = base64_decode($body_response['data']['labels']);

                $upload_dir = wp_upload_dir();
        
                $parcel_value = isset($body_response['data']['parcels']) ? $body_response['data']['parcels'] : 'unknown';
        
                $file_name = "$courier-" . $parcel_value . ".pdf";

                $save_pdf_on_server = get_option('elm_save_pdf_on_server_option', 'false');

                if ($save_pdf_on_server == 'true') {
                    $upload_dir = wp_upload_dir();
                    $file_path = $upload_dir['path'] . '/' . $file_name;
            
                    file_put_contents($file_path, $decoded_data);
            
                    wp_send_json_success(array('file_path' => $upload_dir['url'] . '/' . $file_name));
                } else {
                    wp_send_json_success(array('pdf_data' => base64_encode($decoded_data)));
                }

            } else {
                wp_send_json_error($body_response);
            }
        }
    }
    

}

function initialize_elm_print_label()
{
    new ElmPrintLabel();
}
add_action('plugins_loaded', 'initialize_elm_print_label');