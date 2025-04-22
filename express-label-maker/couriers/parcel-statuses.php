<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmParcelStatuses {

    public function __construct() {
        add_action('wp_ajax_explm_parcel_statuses', array($this, 'explm_parcel_statuses'));
        add_action('wp_ajax_get_orders', array($this, 'get_orders'));
    }

    function explm_parcel_statuses() {
        check_ajax_referer('explm_nonce', 'security');

        $order_id = isset($_POST['order_id']) ? intval(wp_unslash($_POST['order_id'])) : 0;
        $pl_status = isset($_POST['pl_status']) ? sanitize_text_field(wp_unslash($_POST['pl_status'])) : '';

        if ($order_id > 0 && !empty($pl_status)) {
            // Koristimo centraliziranu metodu za ažuriranje meta podataka
            $success = ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $pl_status);
            
            if ($success) {
                wp_send_json_success();
            }
        }
        wp_send_json_error('Invalid order ID or pl_status.');
    }

    function get_orders() {
        check_ajax_referer('explm_nonce', 'security');

        $limit = isset($_POST['limit']) ? intval(wp_unslash($_POST['limit'])) : 10;
        $offset = isset($_POST['offset']) ? intval(wp_unslash($_POST['offset'])) : 0;

        $orders = wc_get_orders(array(
            'limit' => $limit,
            'offset' => $offset,
            'return' => 'ids'
        ));

        $response = array();
        
        foreach ($orders as $order_id) {
            // Koristimo centraliziranu metodu za dobivanje narudžbe
            $order = ExplmLabelMaker::get_order($order_id);
            if (!$order) continue;

            // Dohvaćanje svih meta podataka
            $meta_data = $order->get_meta_data();
            $latest_parcel_meta = null;
            $latest_timestamp = 0;

            foreach ($meta_data as $meta) {
                $meta_key = $meta->key;
                
                // Tražimo meta podatke koji završavaju s '_parcels'
                if (strpos($meta_key, '_parcels') !== false) {
                    $timestamp_key = $meta_key . '_last_updated';
                    // Koristimo centraliziranu metodu za dobivanje meta podataka
                    $timestamp = ExplmLabelMaker::get_order_meta($order_id, $timestamp_key);
                    
                    if ($timestamp && strtotime($timestamp) > $latest_timestamp) {
                        $latest_timestamp = strtotime($timestamp);
                        $latest_parcel_meta = (object) array(
                            'meta_key' => $meta_key,
                            'meta_value' => $meta->value
                        );
                    }
                }
            }

            if (!$latest_parcel_meta) {
                continue;
            }

            $pl_number_meta = $latest_parcel_meta->meta_key;
            $pl_number_raw = $latest_parcel_meta->meta_value;

            $pl_parcels_parts = explode('_', $pl_number_meta, 2);
            $pl_parcels = $pl_parcels_parts[1] ?? '';

            $pl_number_parts = explode(',', $pl_number_raw);
            $pl_number = trim(end($pl_number_parts));

            $userStatusObj = new ExplmUserStatusData();
            $user_data_status = $userStatusObj->explm_getUserStatusData($pl_parcels, $pl_number);

            $response[] = array(
                'order_id' => $order_id,
                'pl_number' => $pl_number,
                'pl_number_meta' => $pl_number_meta,
                'pl_parcels' => $user_data_status
            );
        }

        wp_send_json_success($response);
    }
}

function explm_initialize_parcel_statuses() {
    new ExplmParcelStatuses();
}
add_action('plugins_loaded', 'explm_initialize_parcel_statuses');