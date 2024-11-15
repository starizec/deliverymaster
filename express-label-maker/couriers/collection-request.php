<?php

class ElmCollectionRequest
{
    public function __construct()
    {
        add_action('wp_ajax_elm_collection_request', array($this, 'elm_collection_request'));
    }

    function elm_collection_request() {
        
        check_ajax_referer('elm_nonce', 'security');

        $courier = isset($_POST['chosenCourier']) ? sanitize_text_field(wp_unslash($_POST['chosenCourier'])) : '';
        $saved_country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $order_id = isset($_POST['orderId']) ? intval(wp_unslash($_POST['orderId'])) : 0;

        if (empty($courier) || empty($saved_country) || empty($order_id)) {
            wp_send_json_error(array('error_message' => __('Invalid input provided.', 'express-label-maker')));
        }

        $userObj = new user();
        $user_data = $userObj->getData($saved_country . $courier);

        $parcel_data = isset($_POST['parcel']) ? array_map('sanitize_text_field', wp_unslash($_POST['parcel'])) : array();

        $body = array(
            "user" => $user_data,
            "parcel" => $parcel_data
        );

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );

        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/' . $saved_country . '/' . $courier . '/create/collection-request', $args);

        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($response['response']['code'] != '201') {
            $error_id = isset($body_response['errors'][0]['error_id']) ? $body_response['errors'][0]['error_id'] : 'unknown';
            $error_message = isset($body_response['errors'][0]['error_details']) ? $body_response['errors'][0]['error_details'] : 'unknown';
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