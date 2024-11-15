<?php

class ElmParcelStatuses {

    public function __construct() {
        add_action('wp_ajax_elm_parcel_statuses', array($this, 'elm_parcel_statuses'));
        add_action('wp_ajax_get_orders', array($this, 'get_orders'));
    }

    function elm_parcel_statuses() {
        check_ajax_referer('elm_nonce', 'security');

        $order_id = isset($_POST['order_id']) ? intval(wp_unslash($_POST['order_id'])) : 0;
        $pl_status = isset($_POST['pl_status']) ? sanitize_text_field(wp_unslash($_POST['pl_status'])) : '';

        if ($order_id > 0 && !empty($pl_status)) {
            update_post_meta($order_id, 'elm_parcel_status', $pl_status);
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid order ID or pl_status.');
        }

        wp_die();
    }

    function get_orders() {
        global $wpdb;
        check_ajax_referer('elm_nonce', 'security');

        $limit = isset($_POST['limit']) ? intval(wp_unslash($_POST['limit'])) : 10;
        $offset = isset($_POST['offset']) ? intval(wp_unslash($_POST['offset'])) : 0;

        $orders = wc_get_orders(array(
            'limit' => $limit,
            'offset' => $offset
        ));

        $response = array();
        foreach ($orders as $order) {
            $order_id = $order->get_id();

            $query = $wpdb->prepare(
                "SELECT pm.meta_key, pm.meta_value
                FROM {$wpdb->postmeta} pm
                JOIN (
                    SELECT meta_key, MAX(CAST(meta_value AS DATETIME)) as last_updated
                    FROM {$wpdb->postmeta}
                    WHERE post_id = %d AND meta_key LIKE %s
                    GROUP BY meta_key
                ) pm_last ON pm.meta_key = REPLACE(pm_last.meta_key, '_last_updated', '')
                WHERE pm.post_id = %d 
                AND pm.meta_key LIKE %s
                ORDER BY pm_last.last_updated DESC
                LIMIT 1",
                $order_id,
                '%_parcels_last_updated',
                $order_id,
                '%_parcels'
            );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $latest_parcel_meta = $wpdb->get_row($query);


            if (!$latest_parcel_meta) {
                continue;
            }

            $pl_number_meta = $latest_parcel_meta->meta_key;
            $pl_number_raw = $latest_parcel_meta->meta_value;

            $pl_parcels_parts = explode('_', $pl_number_meta, 2);
            $pl_parcels = $pl_parcels_parts[1];

            $pl_number_parts = explode(',', $pl_number_raw);
            $pl_number = trim(end($pl_number_parts));

            $userStatusObj = new userStatusData();
            $user_data_status = $userStatusObj->getUserStatusData($pl_parcels, $pl_number);

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

function initialize_elm_parcel_statuses() {
    new ElmParcelStatuses();
}
add_action('plugins_loaded', 'initialize_elm_parcel_statuses');