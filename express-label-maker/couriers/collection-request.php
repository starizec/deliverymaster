<?php

class ElmCollectionRequest
{

    public function __construct()
    {
        add_action('wp_ajax_elm_collection_request', array($this, 'elm_collection_request'));
    }

    function elm_collection_request() {
        
        check_ajax_referer('elm_nonce', 'security');

        $courier = $_POST['chosenCourier'];
        $saved_country = $_POST['country'];
        $order_id = $_POST['orderId'];

/*         error_log(print_r($courier , true));
        error_log(print_r($saved_country , true)); */
    
        $userObj = new user();
        $user_data = $userObj->getData($saved_country.$courier);

        $parcel_data = $_POST['parcel'];
    
        $body = array(
            "user" => $user_data,
            "parcel" => $parcel_data
        );

/*         error_log(print_r($body, true)); */
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 120
        );
    
        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/' . $saved_country . '/' . $courier . '/create/collection-request', $args);
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

            $body_response = json_decode(wp_remote_retrieve_body($response), true);

            if ($response['response']['code'] != '201') {
                /* error_log(print_r($response, true)); */
                $error_id = $body_response['errors'][0]['error_id'];
                $error_message = $body_response['errors'][0]['error_details'];
                wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
            }
        
                $meta_key = $saved_country . "_" . $courier . "_collection_request";
                $existing_meta_value = get_post_meta($order_id, $meta_key, true);
                $reference = isset($body_response['data']['reference']) ? $body_response['data']['reference'] : 'unknown';
                $code = isset($body_response['data']['code']) ? $body_response['data']['code'] : 'unknown';
            
                if (!empty($existing_meta_value)) {
                    $new_meta_value = $existing_meta_value . "," . $reference;
                } else {
                    $new_meta_value = $reference;
                }
            
                update_post_meta($order_id, $meta_key, $new_meta_value);
                    
                    wp_send_json_success(array(
                        'reference' => $reference,
                        'code' => $code
                    ));
                }
            }

function initialize_elm_collection_request()
{
    new ElmCollectionRequest();
}
add_action('plugins_loaded', 'initialize_elm_collection_request');