<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmPrintLabels {

    public function __construct() {
        add_action('wp_ajax_explm_print_labels', array($this, 'explm_print_labels'));
    }  

    public function explm_print_labels()
    {
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
            $order = ExplmLabelMaker::get_order($order_id);
            if (!$order) continue;
    
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
            $parcel_data = $this->{"set{$courierUpper}ParcelsData"}($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, 1, $payment_method);
    
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
            wp_send_json_error(array('errors' => array(
                array(
                    'order_number' => 'unknown',
                    'error_message' => $response->get_error_message()
                )
            )));
        }
    
        $body_response = json_decode(wp_remote_retrieve_body($response), true);
/* 
        error_log('response body: ' . print_r($body_response, true)); */
    
        $errors = array();

        if (!empty($body_response['errors']) && is_array($body_response['errors'])) {
            foreach ($body_response['errors'] as $error) {
                $errors[] = array(
                    'order_number' => !empty($error['order_number']) ? $error['order_number'] : 'unknown',
                    'error_code' => !empty($error['error_code']) ? $error['error_code'] : 'unknown',
                    'error_message' => !empty($error['error_message']) ? $error['error_message'] : 'unknown'
                );
            }
        } elseif (!empty($body_response['error'])) {
            $errors[] = array(
                'order_number' => 'unknown',
                'error_code' => 'unknown',
                'error_message' => $body_response['error']
            );
        }
    
        $save_pdf_on_server = get_option('explm_save_pdf_on_server_option', 'true');
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
    
        if (isset($body_response['data']['label']) && is_string($body_response['data']['label']) && !empty($body_response['data']['label'])) {
            $decoded_data = base64_decode($body_response['data']['label']);
            $pdf_url_route = $upload_dir['baseurl'] . '/elm-labels/' . $file_name_new;
    
            $wp_filesystem->put_contents($file_path, $decoded_data, FS_CHMOD_FILE);
    
            // spremanje info u order meta
            if (!empty($body_response['data']['parcels']) && is_array($body_response['data']['parcels'])) {
                foreach ($body_response['data']['parcels'] as $parcel_response) {
                    $order_id = $parcel_response['order_number'];
                    $meta_key = $saved_country . "_" . $courier . "_parcels";
                    $existing_meta_value = ExplmLabelMaker::get_order_meta($order_id, $meta_key);
                    $parcel_value = isset($parcel_response['parcel_number']) ? $parcel_response['parcel_number'] : 'unknown';
    
                    if (!empty($existing_meta_value)) {
                        $new_meta_value = $existing_meta_value . "," . $parcel_value;
                    } else {
                        $new_meta_value = $parcel_value;
                    }
    
                    ExplmLabelMaker::update_order_meta($order_id, $meta_key, $new_meta_value);
    
                    $meta_key_timestamp = $meta_key . '_last_updated';
                    $timestamp = current_time('mysql');
                    ExplmLabelMaker::update_order_meta($order_id, $meta_key_timestamp, $timestamp);
    
                    if ($save_pdf_on_server == 'true') {
                        $existing_pdf_url_route = ExplmLabelMaker::get_order_meta($order_id, 'explm_route_labels');
                        if (!empty($existing_pdf_url_route)) {
                            $pdf_url_route_to_store = $existing_pdf_url_route . ',' . $pdf_url_route;
                        } else {
                            $pdf_url_route_to_store = $pdf_url_route;
                        }
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_route_labels', $pdf_url_route_to_store);
                    }
                }
            }
    
            wp_send_json_success(array(
                'file_path' => $pdf_url_route,
                'file_name' => $file_name_new,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array('errors' => $errors));
        }
    }     

    public function setDPDParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method = null) {
        
        $dpd_note = get_option('explm_dpd_customer_note', '');
        $sender_remark = !empty($dpd_note) 
            ? $dpd_note 
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : null);
        
        if (!empty($sender_remark) && mb_strlen($sender_remark) > 50) {
            $sender_remark = mb_substr($sender_remark, 0, 47) . '...';
        }
        
        $data = array(
            'cod_amount'    => (float)$order_total,
            'name1'         => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'street'        => (string)$address_without_house_number, 
            'rPropNum'      => (string)$house_number, 
            'city'          => (string)$shipping['city'], 
            'country'       => (string)$shipping['country'], 
            'pcode'         => (string)$shipping['postcode'],
            'email'         => isset($billing['email']) ? (string)$billing['email'] : null, 
            'sender_remark' => $sender_remark, 
            'weight'        => (float)$weight,
            'order_number'  => (string)$order_id,
            'cod_purpose'   => (string)$order_id,
            'parcel_type'   => (string)$parcel_type,
            'num_of_parcel' => (int)$package_number, 
            'phone'         => isset($billing['phone']) ? (string)$billing['phone'] : null,
            'contact'       => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']) 
        );
    
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'dpd_parcel_locker_location_id', true);
        if (!empty($locker_id)) {
            $data['pudo_id'] = (string)$locker_id;
            $data['parcel_type'] = 'D-B2C-PSD';
        } elseif (!empty($_POST['dpd_parcel_locker_location_id'])) {
            $data['pudo_id'] = (string)sanitize_text_field($_POST['dpd_parcel_locker_location_id']);
            $data['parcel_type'] = 'D-B2C-PSD';
        }        
    
        return $data;
    }      
    
    public function setOVERSEASParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method) {
        
        $overseas_note = get_option('explm_overseas_customer_note', '');
        $sender_remark = !empty($overseas_note) 
            ? $overseas_note 
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : null);
        
        if (!empty($sender_remark) && mb_strlen($sender_remark) > 35) {
            $sender_remark = mb_substr($sender_remark, 0, 32) . '...';
        }        

        $data = array(
            'cod_amount'     => $payment_method === 'cod' ? (float)$order_total : null,
            'name1'          => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'rPropNum'       => (string)($address_without_house_number . $house_number), 
            'city'           => (string)$shipping['city'],
            'pcode'          => (string)$shipping['postcode'],
            'email'          => isset($billing['email']) ? (string)$billing['email'] : null,
            'sender_remark'  => $sender_remark, 
            'order_number'   => (string)$order_id,
            'num_of_parcel'  => (int)$package_number,
            'phone'          => isset($billing['phone']) ? (string)$billing['phone'] : null
        );
    
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'overseas_parcel_locker_location_id', true);
        if (!empty($locker_id)) {
            $data['pudo_id'] = (string)$locker_id;
        } elseif (!empty($_POST['overseas_parcel_locker_location_id'])) {
            $data['pudo_id'] = (string)sanitize_text_field($_POST['overseas_parcel_locker_location_id']);
        }
    
        return $data;
    }     
}

function explm_initialize_print_labels() {
    new ExplmPrintLabels();
}

add_action('plugins_loaded', 'explm_initialize_print_labels');