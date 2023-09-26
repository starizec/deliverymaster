<?php

class ElmParcelStatuses {

    public function __construct() {
        add_action('wp_ajax_elm_parcel_statuses', array($this, 'elm_parcel_statuses'));
        add_action('wp_ajax_get_orders', array($this, 'get_orders'));
    }

    function elm_parcel_statuses() {

    check_ajax_referer('elm_nonce', 'security');

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $pl_status = isset($_POST['pl_status']) ? sanitize_text_field($_POST['pl_status']) : '';

    if ($order_id > 0 && !empty($pl_status)) {
        update_post_meta($order_id, 'elm_parcel_status', $pl_status);
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid order ID or pl_status.');
        }

        wp_die();
    }


    function get_orders() {

        check_ajax_referer('elm_nonce', 'security');
    
        $orders = wc_get_orders(array(
            'limit' => $_POST['limit'],
            'offset' => $_POST['offset']
        ));
    
        $response = array();
        foreach ($orders as $order) {
            $order = wc_get_order($order->get_id());
            
            // Pokušavamo pronaći ključ koji završava s "_parcels".
            $pl_number_raw = null;
            $meta_data = $order->get_meta_data();
            foreach ($meta_data as $meta) {
                if (preg_match('/_parcels$/', $meta->key)) {
                    $pl_number_meta = $meta->key;
                    $pl_number_raw = $meta->value;
                    break;
                }
            }
            error_log(print_r($pl_number_meta, true));
            // Ako nije pronađen takav ključ, preskačemo ovu iteraciju petlje.
            if ($pl_number_raw === null) {
                continue;
            }

            $pl_parcels_parts = explode('_', $pl_number_meta, 2);
            $pl_parcels = $pl_parcels_parts[1];
    
            $pl_number_parts = explode(',', $pl_number_raw, 2);
            $pl_number = $pl_number_parts[0];

            $userStatusObj = new userStatusData();
            $user_data_status = $userStatusObj->getUserStatusData($pl_parcels, $pl_number);
    
            $response[] = array(
                'order_id' => $order->get_id(),
                'pl_number' => $pl_number,
                'pl_number_meta' => $pl_number_meta,
                'pl_parcels' => $user_data_status
            );
        }
    
        wp_send_json_success($response);
    }
    
}

function initialize_elm_parcel_statuses() {
    new ElmParcelStatuses();
}
add_action('plugins_loaded', 'initialize_elm_parcel_statuses');