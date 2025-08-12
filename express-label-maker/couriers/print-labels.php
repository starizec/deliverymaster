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
            $payment_method = $order->get_payment_method();
            $parcel_type = '';
            $currency = $order->get_currency();

            $weight = 2;
            $total_weight = 0;

            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $product_weight = (float) $product->get_weight();
                    $quantity = $item->get_quantity();
                    $total_weight += $product_weight * $quantity;
                }
            }

            if ($total_weight > 0) {
                $weight = $total_weight;
            }
    
            preg_match('/\d[\w\s-]*$/', $shipping['address_1'], $house_number);
            $house_number = isset($house_number[0]) ? $house_number[0] : '';
            $address_without_house_number = preg_replace('/\d[\w\s-]*$/', '', $shipping['address_1']);
            $courierUpper = strtoupper($courier);
            $parcel_data = $this->{"set{$courierUpper}ParcelsData"}($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, 1, $payment_method, $currency);
    

            $parcels_array[] = $parcel_data;

        }
    
        $body = array(
            "user" => $user_data,
            "parcels" => $parcels_array
        );

        error_log('$body: ' . print_r($body, true));
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );
    
        $response = wp_remote_request(EXPLM_API_BASE_URL . 'api/v1/' . $saved_country . '/' . $courier . '/create/labels', $args);

        error_log(' $response: ' . print_r($response, true));
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('errors' => array(
                array(
                    'order_number' => 'unknown',
                    'error_message' => $response->get_error_message()
                )
            )));
        }
    
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        error_log('response body: ' . print_r($body_response, true));
    
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

    
    public function setDPDParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method, $currency)
    {

        $dpd_note = get_option('explm_dpd_customer_note', '');
        $sender_remark = !empty($dpd_note) 
            ? $dpd_note 
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : null);
        
        if (!empty($sender_remark) && mb_strlen($sender_remark) > 50) {
            $sender_remark = mb_substr($sender_remark, 0, 47) . '...';
        }

        $parcel_value   = number_format((float)$order_total, 2, '.', '');
        $parcel_weight  = number_format((float)$weight, 2, '.', '');
       
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'dpd_parcel_locker_location_id', true);
        $locker_type = ExplmLabelMaker::get_order_meta($order_id, 'dpd_parcel_locker_type', true);

        $data = [
            'recipient_name'        => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'recipient_phone'       => isset($billing['phone']) ? (string)$billing['phone'] : '',
            'recipient_email'       => isset($billing['email']) ? (string)$billing['email'] : '',
            'recipient_adress'      => (string)trim($address_without_house_number . ' ' . $house_number),
            'recipient_city'        => (string)$shipping['city'],
            'recipient_postal_code' => (string)$shipping['postcode'],
            'recipient_country'     => strtoupper((string)$shipping['country']),

            'sender_name'           => (string)get_option('explm_dpd_company_or_personal_name', ''),
            'sender_phone'          => (string)get_option('explm_dpd_phone', ''),
            'sender_email'          => (string)get_option('explm_dpd_email', ''),
            'sender_adress'         => (string)trim(get_option('explm_dpd_street', '') . ' ' . get_option('explm_dpd_property_number', '')),
            'sender_city'           => (string)get_option('explm_dpd_city', ''),
            'sender_postal_code'    => (string)get_option('explm_dpd_postal_code', ''),
            'sender_country'        => strtoupper((string)get_option('explm_dpd_country', '')),

            'order_number'          => (string)$order_id,
            'parcel_weight'         => (string)$parcel_weight,
            'parcel_remark'         => (string)$sender_remark,
            'parcel_value'          => (string)$parcel_value,
            'parcel_size'           => (string)get_option('explm_dpd_parcel_size', ''),
            'parcel_count'          => (int)$package_number,
            'cod_amount'            => $payment_method === 'cod' ? (float)$order_total : '',
            'cod_currency'          => $payment_method === 'cod' ? (string)$currency : '',

            'delivery_service'       => (string)get_option('explm_dpd_service_type_option', ''),

            'location_id'   => (string)(!empty($locker_id) ? $locker_id : (isset($_POST['dpd_parcel_locker_location_id']) ? sanitize_text_field(wp_unslash($_POST['dpd_parcel_locker_location_id'])) : '')),
            'location_type' => (string)(!empty($locker_type) ? $locker_type : (isset($_POST['dpd_parcel_locker_type']) ? sanitize_text_field(wp_unslash($_POST['dpd_parcel_locker_type'])) : '')),

        ];

        return $data;
    }

    

    public function setOVERSEASParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method, $currency)
        {

        $overseas_note = get_option('explm_overseas_customer_note', '');
        $sender_remark = !empty($overseas_note) 
            ? $overseas_note 
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : null);
        
        if (!empty($sender_remark) && mb_strlen($sender_remark) > 35) {
            $sender_remark = mb_substr($sender_remark, 0, 32) . '...';
        }     

        $parcel_value   = number_format((float)$order_total, 2, '.', '');
        $parcel_weight  = number_format((float)$weight, 2, '.', '');
       
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'overseas_parcel_locker_location_id', true);
        $locker_type = ExplmLabelMaker::get_order_meta($order_id, 'overseas_parcel_locker_type', true);

        $data = [
            'recipient_name'        => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'recipient_phone'       => isset($billing['phone']) ? (string)$billing['phone'] : '',
            'recipient_email'       => isset($billing['email']) ? (string)$billing['email'] : '',
            'recipient_adress'      => (string)trim($address_without_house_number . ' ' . $house_number),
            'recipient_city'        => (string)$shipping['city'],
            'recipient_postal_code' => (string)$shipping['postcode'],
            'recipient_country'     => strtoupper((string)$shipping['country']),

            'sender_name'           => (string)get_option('explm_overseas_company_or_personal_name', ''),
            'sender_phone'          => (string)get_option('explm_overseas_phone', ''),
            'sender_email'          => (string)get_option('explm_overseas_email', ''),
            'sender_adress'         => (string)trim(get_option('explm_overseas_street', '') . ' ' . get_option('explm_overseas_property_number', '')),
            'sender_city'           => (string)get_option('explm_overseas_city', ''),
            'sender_postal_code'    => (string)get_option('explm_overseas_postal_code', ''),
            'sender_country'        => strtoupper((string)get_option('explm_overseas_country', '')),

            'order_number'          => (string)$order_id,
            'parcel_weight'         => (string)$parcel_weight,
            'parcel_remark'         => (string)$sender_remark,
            'parcel_value'          => (string)$parcel_value,
            'parcel_size'           => (string)get_option('explm_overseas_parcel_size', ''),
            'parcel_count'          => (int)$package_number,
            'cod_amount'            => $payment_method === 'cod' ? (float)$order_total : '',
            'cod_currency'          => $payment_method === 'cod' ? (string)$currency : '',

            'location_id'   => (string)(!empty($locker_id) ? $locker_id : (isset($_POST['overseas_parcel_locker_location_id']) ? sanitize_text_field(wp_unslash($_POST['overseas_parcel_locker_location_id'])) : '')),
            'location_type' => (string)(!empty($locker_type) ? $locker_type : (isset($_POST['overseas_parcel_locker_type']) ? sanitize_text_field(wp_unslash($_POST['overseas_parcel_locker_type'])) : '')),

        ];

        return $data;
    }
    
    public function setHPParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method, $currency)
    {
        $hp_note = get_option('explm_hp_customer_note', '');
        $sender_remark = !empty($hp_note)
            ? $hp_note
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : '');

        if (!empty($sender_remark) && mb_strlen($sender_remark) > 100) {
            $sender_remark = mb_substr($sender_remark, 0, 97) . '...';
        }

        $parcel_value   = number_format((float)$order_total, 2, '.', '');
        $parcel_weight  = number_format((float)$weight, 2, '.', '');
       
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'hp_parcel_locker_location_id', true);
        $locker_type = ExplmLabelMaker::get_order_meta($order_id, 'hp_parcel_locker_type', true);

        $insured_option = get_option('explm_hp_insured_value', '0');
        $value_param = ($insured_option === '1') ? (string)$parcel_value : '';

        $data = [
            'recipient_name'        => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'recipient_phone'       => isset($billing['phone']) ? (string)$billing['phone'] : '',
            'recipient_email'       => isset($billing['email']) ? (string)$billing['email'] : '',
            'recipient_adress'      => (string)trim($address_without_house_number . ' ' . $house_number),
            'recipient_city'        => (string)$shipping['city'],
            'recipient_postal_code' => (string)$shipping['postcode'],
            'recipient_country'     => strtoupper((string)$shipping['country']),

            'sender_name'           => (string)get_option('explm_hp_company_or_personal_name', ''),
            'sender_phone'          => (string)get_option('explm_hp_phone', ''),
            'sender_email'          => (string)get_option('explm_hp_email', ''),
            'sender_adress'         => (string)trim(get_option('explm_hp_street', '') . ' ' . get_option('explm_hp_property_number', '')),
            'sender_city'           => (string)get_option('explm_hp_city', ''),
            'sender_postal_code'    => (string)get_option('explm_hp_postal_code', ''),
            'sender_country'        => strtoupper((string)get_option('explm_hp_country', '')),

            'order_number'          => (string)$order_id,
            'parcel_weight'         => (string)$parcel_weight,
            'parcel_remark'         => (string)$sender_remark,
            'parcel_value'          => (string)$parcel_value,
            'parcel_size'           => (string)get_option('explm_hp_parcel_size', ''),
            'parcel_count'          => (int)$package_number,
            'cod_amount'            => $payment_method === 'cod' ? (float)$order_total : '',
            'cod_currency'          => $payment_method === 'cod' ? (string)$currency : '',
            'value'                 => $value_param,

            'additional_services'   => (string)get_option('explm_hp_delivery_additional_services', ''),
            'delivery_service'       => (string)get_option('explm_hp_delivery_service', ''),
            'location_id'   => (string)(!empty($locker_id) ? $locker_id : (isset($_POST['hp_parcel_locker_location_id']) ? sanitize_text_field(wp_unslash($_POST['hp_parcel_locker_location_id'])) : '')),
            'location_type' => (string)(!empty($locker_type) ? $locker_type : (isset($_POST['hp_parcel_locker_type']) ? sanitize_text_field(wp_unslash($_POST['hp_parcel_locker_type'])) : '')),

        ];

        return $data;
    }

public function setGLSParcelsData($shipping, $billing, $order_data, $order_total, $address_without_house_number, $house_number, $weight, $order_id, $parcel_type, $package_number, $payment_method, $currency)
    {

        $gls_note = get_option('explm_gls_customer_note', '');
        $sender_remark = !empty($gls_note)
            ? $gls_note
            : (!empty($order_data['customer_note']) ? (string)$order_data['customer_note'] : '');

        if (!empty($sender_remark) && mb_strlen($sender_remark) > 100) {
            $sender_remark = mb_substr($sender_remark, 0, 97) . '...';
        }

        $parcel_value   = number_format((float)$order_total, 2, '.', '');
        $parcel_weight  = number_format((float)$weight, 2, '.', '');
       
        $locker_id = ExplmLabelMaker::get_order_meta($order_id, 'gls_parcel_locker_location_id', true);
        $locker_type = ExplmLabelMaker::get_order_meta($order_id, 'gls_parcel_locker_type', true);

        $data = [
            'recipient_name'        => (string)trim($shipping['first_name'] . ' ' . $shipping['last_name']),
            'recipient_phone'       => isset($billing['phone']) ? (string)$billing['phone'] : '',
            'recipient_email'       => isset($billing['email']) ? (string)$billing['email'] : '',
            'recipient_adress'      => (string)trim($address_without_house_number . ' ' . $house_number),
            'recipient_city'        => (string)$shipping['city'],
            'recipient_postal_code' => (string)$shipping['postcode'],
            'recipient_country'     => strtoupper((string)$shipping['country']),

            'sender_name'           => (string)get_option('explm_gls_company_or_personal_name', ''),
            'sender_phone'          => (string)get_option('explm_gls_phone', ''),
            'sender_email'          => (string)get_option('explm_gls_email', ''),
            'sender_adress'         => (string)trim(get_option('explm_gls_street', '') . ' ' . get_option('explm_gls_property_number', '')),
            'sender_city'           => (string)get_option('explm_gls_city', ''),
            'sender_postal_code'    => (string)get_option('explm_gls_postal_code', ''),
            'sender_country'        => strtoupper((string)get_option('explm_gls_country', '')),

            'order_number'          => (string)$order_id,
            'parcel_weight'         => (string)$parcel_weight,
            'parcel_remark'         => (string)$sender_remark,
            'parcel_value'          => (string)$parcel_value,
            'parcel_size'           => (string)get_option('explm_gls_parcel_size', ''),
            'parcel_count'          => (int)$package_number,
            'cod_amount'            => $payment_method === 'cod' ? (float)$order_total : '',
            'cod_currency'          => $payment_method === 'cod' ? (string)$currency : '',

            'additional_services'   => (string)get_option('explm_gls_delivery_additional_services', ''),
            'print_position'       => (string)get_option('explm_gls_print_position', ''),
            'printer_type'       => (string)get_option('explm_gls_printer_type', ''),
            'location_id'   => (string)(!empty($locker_id) ? $locker_id : (isset($_POST['gls_parcel_locker_location_id']) ? sanitize_text_field(wp_unslash($_POST['gls_parcel_locker_location_id'])) : '')),
            'location_type' => (string)(!empty($locker_type) ? $locker_type : (isset($_POST['gls_parcel_locker_type']) ? sanitize_text_field(wp_unslash($_POST['gls_parcel_locker_type'])) : '')),

        ];

        return $data;
    }

}

function explm_initialize_print_labels() {
    new ExplmPrintLabels();
}

add_action('plugins_loaded', 'explm_initialize_print_labels');