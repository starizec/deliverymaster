<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmPrintLabel
{
    public function __construct()
    {
        add_action('wp_ajax_explm_print_label', array($this, 'explm_print_label'));
    }

    function explm_print_label() {
        check_ajax_referer('explm_nonce', 'security');

        $courier = isset($_POST['chosenCourier']) ? sanitize_text_field(wp_unslash($_POST['chosenCourier'])) : '';
        $order_id = isset($_POST['orderId']) ? intval(wp_unslash($_POST['orderId'])) : 0;
        $parcel_data = isset($_POST['parcel']) ? array_map('sanitize_text_field', wp_unslash($_POST['parcel'])) : array();

        $saved_country = get_option("explm_country_option", '');

        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . $courier);

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

        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/' . $saved_country . '/' . $courier . '/create/label', $args);

        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($response['response']['code'] != '201') {
            $error_id = isset($body_response['errors'][0]['error_id']) ? $body_response['errors'][0]['error_id'] : 'unknown';
            $error_message = isset($body_response['errors'][0]['error_details']) ? $body_response['errors'][0]['error_details'] : 'unknown';
            wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
        }

        $decoded_data = base64_decode($body_response['data']['label'], true);

        $meta_key = $saved_country . "_" . $courier . "_parcels";
        $existing_meta_value = get_post_meta($order_id, $meta_key, true);
        $parcel_value = isset($body_response['data']['parcels']) ? $body_response['data']['parcels'] : 'unknown';

        if (!empty($existing_meta_value)) {
            $new_meta_value = $existing_meta_value . "," . $parcel_value;
        } else {
            $new_meta_value = $parcel_value;
        }

        update_post_meta($order_id, $meta_key, $new_meta_value);

        $meta_key_timestamp = $meta_key . '_last_updated';
        $timestamp = current_time('mysql');
        update_post_meta($order_id, $meta_key_timestamp, $timestamp);

        $timestamp = gmdate('dmy');
        $file_name_new = uniqid('', true) . "-$courier-$timestamp.pdf";

        $upload_dir = wp_upload_dir();
        $labels_dir = $upload_dir['basedir'] . '/elm-labels';
        $file_path = $labels_dir . '/' . $file_name_new;

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (!file_exists($labels_dir)) {
            $wp_filesystem->mkdir($labels_dir, FS_CHMOD_DIR);
        }

        if (get_option('explm_save_pdf_on_server_option', 'false') == 'true') {
            $wp_filesystem->put_contents($file_path, $decoded_data, FS_CHMOD_FILE);

            $pdf_url_route = $upload_dir['baseurl'] . '/elm-labels/' . $file_name_new;

            $existing_pdf_url_route = get_post_meta($order_id, 'explm_route_labels', true);

            if (!empty($existing_pdf_url_route)) {
                $pdf_url_route_to_store = $existing_pdf_url_route . ',' . $pdf_url_route;
            } else {
                $pdf_url_route_to_store = $pdf_url_route;
            }

            update_post_meta($order_id, 'explm_route_labels', $pdf_url_route_to_store);

            wp_send_json_success(array(
                'file_path' => $pdf_url_route,
                'file_name' => $file_name_new,
                'parcel_number' => $parcel_value
            ));
        }

        wp_send_json_success(array(
            'pdf_data' => base64_encode($decoded_data),
            'file_name' => $file_name_new,
            'parcel_number' => $parcel_value
        ));
    }
}

function explm_initialize_print_label()
{
    new ExplmPrintLabel();
}
add_action('plugins_loaded', 'explm_initialize_print_label');