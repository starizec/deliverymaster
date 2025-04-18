<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmPrintLabels {

    public function __construct() {
        add_action('wp_ajax_explm_print_labels', array($this, 'explm_print_labels'));
    }  

    public function explm_print_labels() {
        check_ajax_referer('explm_nonce', 'security');

        $actionValue = isset($_POST['actionValue']) ? sanitize_text_field(wp_unslash($_POST['actionValue'])) : '';
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', wp_unslash($_POST['post_ids'])) : array();

        $saved_country = get_option("explm_country_option", '');
        $saved_service_type = get_option("explm_dpd_service_type_option", '');
        $courier = '';

        if (preg_match('/explm_(.*?)_print_label/', $actionValue, $match) === 1) {
            $courier = $match[1];
        }
    
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . $courier);
    
        $parcels_array = array();
        foreach ($post_ids as $order_id) {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            $billing = $order_data['billing'];
            $shipping = $order_data['shipping'];
            $order_total = $order->get_total();
            $weight = 2;
            $payment_method = $order->get_payment_method();
            $parcel_type = '';

            if ($saved_service_type === 'DPD Classic') {
                $parcel_type = $payment_method === 'cod' ? 'D-COD' : 'D';
            } elseif ($saved_service_type === 'DPD Home') {
                $parcel_type = $payment_method === 'cod' ? 'D-COD-B2C' : 'D-B2C';
            }
    
            preg_match('/\d[\w\s-]*$/', $shipping['address_1'], $house_number);
            $house_number = isset($house_number[0]) ? $house_number[0] : '';
            $address_without_house_number = preg_replace('/\d[\w\s-]*$/', '', $shipping['address_1']);
            $courierUpper = strtoupper($courier);
            $parcel_data = $this->{"set{$courierUpper}ParcelsData"}($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, 1);

            $parcels_array[] = array(
                "order_number" => (string)$order_id,
                "parcel" => $parcel_data
            );
        }

        $body = array(
            "user" => $user_data,
            "parcels" => $parcels_array
        );

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );

        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/' . $saved_country . '/' . $courier . '/create/labels', $args);

        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        } 
    
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($response['response']['code'] != '201') {
            $error_id = $body_response['errors'][0]['error_id'];
            $error_message = $body_response['errors'][0]['error_details'];
            wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
        }

        $save_pdf_on_server = get_option('explm_save_pdf_on_server_option', 'false');
        $upload_dir = wp_upload_dir();
        $labels_dir = $upload_dir['basedir'] . '/elm-labels';

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (!file_exists($labels_dir)) {
            $wp_filesystem->mkdir($labels_dir, FS_CHMOD_DIR);
        }

        $timestamp = gmdate('dmy');
        $file_name_new = uniqid('', true) . "-$courier-$timestamp.pdf";
        $file_path = $labels_dir . '/' . $file_name_new;
        $decoded_data = base64_decode($body_response['data']['label']);
        $pdf_url_route = $upload_dir['baseurl'] . '/elm-labels/' . $file_name_new;

        foreach ($body_response['data']['parcels'] as $parcel_response) {
            $order_id = $parcel_response['order_number'];
            $meta_key = $saved_country . "_" . $courier . "_parcels";
            $existing_meta_value = get_post_meta($order_id, $meta_key, true);
            $parcel_value = isset($parcel_response['parcel_number']) ? $parcel_response['parcel_number'] : 'unknown';

            if (!empty($existing_meta_value)) {
                $new_meta_value = $existing_meta_value . "," . $parcel_value;
            } else {
                $new_meta_value = $parcel_value;
            }

            update_post_meta($order_id, $meta_key, $new_meta_value);

            $meta_key_timestamp = $meta_key . '_last_updated';
            $timestamp = current_time('mysql');
            update_post_meta($order_id, $meta_key_timestamp, $timestamp);

            if ($save_pdf_on_server == 'true') {
                $existing_pdf_url_route = get_post_meta($order_id, 'explm_route_labels', true);

                if (!empty($existing_pdf_url_route)) {
                    $pdf_url_route_to_store = $existing_pdf_url_route . ',' . $pdf_url_route;
                } else {
                    $pdf_url_route_to_store = $pdf_url_route;
                }

                update_post_meta($order_id, 'explm_route_labels', $pdf_url_route_to_store);
            }
        }

        if ($save_pdf_on_server == 'true') {
            $wp_filesystem->put_contents($file_path, $decoded_data, FS_CHMOD_FILE);
            wp_send_json_success(array(
                'file_path' => $pdf_url_route,
                'file_name' => $file_name_new
            ));
        }

        wp_send_json_success(array(
            'pdf_data' => base64_encode($decoded_data),
            'file_name' => $file_name_new
        ));
    }

    public function setDPDParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number) {
        $data = array(
            'cod_amount'    => $order_total,
            'name1'         => $shipping['first_name'] . ' ' . $shipping['last_name'],
            'street'        => $address_without_house_number,
            'rPropNum'      => $house_number,
            'city'          => $shipping['city'],
            'country'       => $shipping['country'],
            'pcode'         => $shipping['postcode'],
            'email'         => $billing['email'],
            'sender_remark' => $order_data['customer_note'],
            'weight'        => $weight,
            'order_number'  => $order_id,
            'cod_purpose'   => $order_id,
            'parcel_type'   => $parcel_type,
            'num_of_parcel' => $package_number,
            'phone'         => $billing['phone'],
            'contact'       => $shipping['first_name'] . ' ' . $shipping['last_name']
        );
    
        $locker_id = get_post_meta($order_id, 'parcel_locker_id', true);
        if (!empty($locker_id)) {
            $data['pudo_id'] = $locker_id;
            $data['parcel_type'] = 'D-B2C-PSD';
        } elseif (isset($_POST['parcel_locker_id']) && !empty($_POST['parcel_locker_id'])) {
            $data['pudo_id'] = sanitize_text_field($_POST['parcel_locker_id']);
            $data['parcel_type'] = 'D-B2C-PSD';
        }
    
        return $data;
    }    
    
    public function setOVERSEASParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number) {
        return array(
            'cod_amount' => $payment_method === 'cod' ? $order_total : null,
            'name1' => $shipping['first_name'] . ' ' . $shipping['last_name'],
            'rPropNum' => $address_without_house_number . $house_number,
            'city' => $shipping['city'],
            'pcode' => $shipping['postcode'],
            'email' => $billing['email'],
            'sender_remark' => $order_data['customer_note'],
            'order_number' => $order_id,
            'num_of_parcel' => $package_number,
            'phone' => $billing['phone']
        );
    }
}

function explm_initialize_print_labels() {
    new ExplmPrintLabels();
}

add_action('plugins_loaded', 'explm_initialize_print_labels');