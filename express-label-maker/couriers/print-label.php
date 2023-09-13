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
        $order_id = $_POST['orderId'];
    
        $userObj = new user();
        $user_data = $userObj->getData($saved_country.$courier);

        $parcel_data = $_POST['parcel'];
    
        $body = array(
            "user" => $user_data,
            "parcel" => $parcel_data
        );

        /* error_log(print_r($user_data, true)); */
       /*  error_log(print_r($parcel_data, true)); */
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body)
        );
    
        $response = wp_remote_request('https://api.expresslabelmaker.com/v1/' . $saved_country . '/' . $courier . '/create/label', $args);
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('info' => $response->get_error_message()));
        } else {
            $body_response = json_decode(wp_remote_retrieve_body($response), true);
            /* error_log(print_r($response, true)); */
            /* error_log(print_r($body_response, true)); */

            if ($response['response']['code'] == '201') {
   
                $decoded_data = base64_decode($body_response['data']['label']);

                $meta_key = $saved_country . "_" . $courier . "_adresnica";

                $existing_meta_value = get_post_meta($order_id, $meta_key, true);
        
                $parcel_value = isset($body_response['data']['parcels']) ? $body_response['data']['parcels'] : 'unknown';

                if (!empty($existing_meta_value)) {
                    $new_meta_value = $existing_meta_value . "," . $parcel_value;
                } else {
                    $new_meta_value = $parcel_value;
                }
    
                update_post_meta($order_id, $meta_key, $new_meta_value);

                $upload_dir = wp_upload_dir();
        
                $file_name = "$courier-" . $parcel_value . ".pdf";

                $save_pdf_on_server = get_option('elm_save_pdf_on_server_option', 'false');

                if ($save_pdf_on_server == 'true') {
                    $upload_dir = wp_upload_dir();
                    $file_path = $upload_dir['path'] . '/' . $file_name;
            
                    file_put_contents($file_path, $decoded_data);
            
                    wp_send_json_success(array(
                        'file_path' => $upload_dir['url'] . '/' . $file_name,
                        'file_name' => $file_name
                    ));
                } else {
                    wp_send_json_success(array(
                        'pdf_data' => base64_encode($decoded_data),
                        'file_name' => $file_name
                    ));
                }

            } else {
                error_log(print_r($body_response, true));
                $error_id = $body_response['errors'][0]['error_id'];
                $error_message = $body_response['errors'][0]['error_details'];
                wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
            }
        }
    }
    

}

function initialize_elm_print_label()
{
    new ElmPrintLabel();
}
add_action('plugins_loaded', 'initialize_elm_print_label');