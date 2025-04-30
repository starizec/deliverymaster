<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmCollectionRequest
{
    public function __construct()
    {
        add_action('wp_ajax_explm_collection_request', array($this, 'explm_collection_request'));
    }

    function explm_collection_request() {
        check_ajax_referer('explm_nonce', 'security');

        $courier = isset($_POST['chosenCourier']) ? sanitize_text_field(wp_unslash($_POST['chosenCourier'])) : '';
        $saved_country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $order_id = isset($_POST['orderId']) ? intval(wp_unslash($_POST['orderId'])) : 0;

        if (empty($courier) || empty($saved_country) || empty($order_id)) {
            wp_send_json_error(array('error_message' => __('Invalid input provided.', 'express-label-maker')));
        }
        
        $order = ExplmLabelMaker::get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('error_message' => __('Order not found.', 'express-label-maker')));
        }

        $userObj = new ExplmUser();
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
            $errors = array();
        
            if (!empty($body_response['errors']) && is_array($body_response['errors'])) {
                foreach ($body_response['errors'] as $error) {
                    $errors[] = array(
                        'error_id' => !empty($error['error_id']) ? $error['error_id'] : 'unknown',
                        'error_message' => !empty($error['error_details']) ? $error['error_details'] : 'unknown'
                    );
                }
            } elseif (!empty($body_response['error'])) {
                $errors[] = array(
                    'error_id' => 'unknown',
                    'error_message' => $body_response['error']
                );
            }
        
            wp_send_json_error(array('errors' => $errors));
        }        

        $meta_key = $saved_country . "_" . $courier . "_collection_request";
        
        $existing_meta_value = ExplmLabelMaker::get_order_meta($order_id, $meta_key);
        $reference = $body_response['data']['reference'] ?? 'unknown';
        $code = $body_response['data']['code'] ?? 'unknown';

        if (!empty($existing_meta_value)) {
            $new_meta_value = $existing_meta_value . "," . $reference;
        } else {
            $new_meta_value = $reference;
        }

        $success = ExplmLabelMaker::update_order_meta($order_id, $meta_key, $new_meta_value);
        
        if (!$success) {
            wp_send_json_error(array('error_message' => __('Failed to update order meta.', 'express-label-maker')));
        }

        wp_send_json_success(array(
            'reference' => $reference,
            'code' => $code
        ));
    }
}

function explm_initialize_collection_request()
{
    new ExplmCollectionRequest();
}
add_action('plugins_loaded', 'explm_initialize_collection_request');