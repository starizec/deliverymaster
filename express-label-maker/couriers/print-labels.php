<?php

class ElmPrintLabels {

    public function __construct() {
        add_action('wp_ajax_elm_print_labels', array($this, 'elm_print_labels'));
    }  

    public function elm_print_labels($post_ids) {

        check_ajax_referer('elm_nonce', 'security');
    
        $saved_country = get_option("elm_country_option", '');
        $actionValue = $_POST['actionValue'];
        $courier = '';

        if (preg_match('/elm_(.*?)_print_label/', $actionValue, $match) == 1) {
            $courier = $match[1];
        }
    
        $userObj = new user();
        $user_data = $userObj->getData($saved_country . $courier);
        $post_ids = $_POST['post_ids'];
    
        $parcels_array = array();
        foreach ($post_ids as $order_id) {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            $billing = $order_data['billing'];
            $shipping = $order_data['shipping'];
            $order_total = $order->get_total();
            $weight = 2;
            $payment_method = $order->get_payment_method();
            $parcel_type = $payment_method === 'cod' ? 'D-COD' : 'D';
    
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
            'body' => json_encode($body)
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
    
        $save_pdf_on_server = get_option('elm_save_pdf_on_server_option', 'false');
        $upload_dir = wp_upload_dir();
        $labels_dir = $upload_dir['basedir'] . '/elm-labels';
        
        if (!file_exists($labels_dir)) {
            mkdir($labels_dir, 0755, true);
        }
    
        $timestamp = date('dmy');
        $file_name_new = uniqid('', true)."-$courier-$timestamp.pdf";
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
            
            if ($save_pdf_on_server == 'true') {
                 $existing_pdf_url_route = get_post_meta($order_id, 'elm_route_labels', true);
            
            if (!empty($existing_pdf_url_route)) {
                $pdf_url_route_to_store = $existing_pdf_url_route . ',' . $pdf_url_route;
            } else {
                $pdf_url_route_to_store = $pdf_url_route;
            }

            update_post_meta($order_id, 'elm_route_labels', $pdf_url_route_to_store);
            }
        }
        error_log(print_r($response, true));

        if ($save_pdf_on_server == 'true') {
            file_put_contents($file_path, $decoded_data);
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

    //DODATI ZA OSTALE KURIRE PARCEL DATA

    public function setDPDParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number) {
        return array(
            'cod_amount' => $order_total,
            'name1' => $shipping['first_name'] . ' ' . $shipping['last_name'],
            'street' => $address_without_house_number,
            'rPropNum' => $house_number,
            'city' => $shipping['city'],
            'country' => $shipping['country'],
            'pcode' => $shipping['postcode'],
            'email' => $billing['email'],
            'sender_remark' => $order_data['customer_note'],
            'weight' => $weight,
            'order_number' => $order_id,
            'cod_purpose' => $order_id,
            'parcel_type' => $parcel_type,
            'num_of_parcel' => $package_number,
            'phone' => $billing['phone'],
            'contact' => $shipping['first_name'] . ' ' . $shipping['last_name']
        );
    }
}

function initialize_elm_print_labels() {
    new ElmPrintLabels();
}

add_action('plugins_loaded', 'initialize_elm_print_labels');