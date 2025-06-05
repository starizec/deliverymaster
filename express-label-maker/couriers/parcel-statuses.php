<?php
if (!defined('ABSPATH')) {
    exit;
}

class ExplmParcelStatuses
{
    public function __construct()
    {
        add_action('wp_ajax_explm_parcel_statuses', array($this, 'explm_parcel_statuses'));
        add_action('wp_ajax_get_orders', array($this, 'get_orders'));

        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_hour'] = array(
                'interval' => 3600,
                'display' => __('Every Hour')
            );
            return $schedules;
        });

        if (!wp_next_scheduled('explm_cron_hp_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_hp_status_update');
        }

        add_action('explm_cron_hp_status_update', array($this, 'update_hp_parcel_statuses'));
    }

    public function explm_parcel_statuses()
    {
        check_ajax_referer('explm_nonce', 'security');

        $order_id = isset($_POST['order_id']) ? intval(wp_unslash($_POST['order_id'])) : 0;
        $pl_status = isset($_POST['pl_status']) ? sanitize_text_field(wp_unslash($_POST['pl_status'])) : '';

        if ($order_id > 0 && !empty($pl_status)) {
            $success = ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $pl_status);

            if ($success) {
                wp_send_json_success();
            }
        }
        wp_send_json_error('Invalid order ID or pl_status.');
    }

    public function get_orders()
    {
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
            $order = ExplmLabelMaker::get_order($order_id);
            if (!$order) continue;

            $meta_data = $order->get_meta_data();
            $latest_parcel_meta = null;
            $latest_timestamp = 0;

            foreach ($meta_data as $meta) {
                $meta_key = $meta->key;

                if (strpos($meta_key, '_parcels') !== false) {
                    $timestamp_key = $meta_key . '_last_updated';
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

            if (!$latest_parcel_meta) continue;

            $pl_number_meta = $latest_parcel_meta->meta_key;
            $pl_number_raw = $latest_parcel_meta->meta_value;
            $pl_parcels_parts = explode('_', $pl_number_meta, 2);
            $pl_parcels = $pl_parcels_parts[1] ?? '';
            $pl_number_parts = explode(',', $pl_number_raw);
            $pl_number = trim(end($pl_number_parts));

            $userStatusObj = new ExplmUserStatusData();
            $user_data_status = $userStatusObj->explm_getUserStatusData($pl_parcels, $pl_number);

            if (stripos($pl_parcels, 'overseas') !== false && !empty($user_data_status['url'])) {
                $remote_response = wp_remote_get($user_data_status['url']);

                if (!is_wp_error($remote_response)) {
                    $body = json_decode(wp_remote_retrieve_body($remote_response), true);
                    if (!empty($body['data']['CargoID'])) {
                        ExplmLabelMaker::update_order_meta($order_id, 'overseas_cargo_id', $body['data']['CargoID']);
                    }
                }
            }

            $response[] = array(
                'order_id' => $order_id,
                'pl_number' => $pl_number,
                'pl_number_meta' => $pl_number_meta,
                'pl_parcels' => $user_data_status,
                'explm_parcel_status' => $order->get_meta('explm_parcel_status'),
                'explm_parcel_status_date' => $order->get_meta('explm_parcel_status_date'),
                'explm_parcel_status_code' => $order->get_meta('explm_parcel_status_code'),
                'explm_parcel_status_color' => $order->get_meta('explm_parcel_status_color'),
            );
        }

        wp_send_json_success($response);
    }

    public function update_hp_parcel_statuses() {
        $saved_hp_username = get_option('explm_hp_username_option', '');
        $saved_hp_password = get_option('explm_hp_password_option', '');

        if ( empty($saved_hp_username) || empty($saved_hp_password) ) {
            return;
        }

        $orders = wc_get_orders(array(
            'limit' => -1,
            'return' => 'ids'
        ));

/*         $two_weeks_ago = strtotime('-14 days'); */

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);

            if (!$order) continue;

/*             $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) > $two_weeks_ago) {
                continue;
            } */

            $pl_number = $order->get_meta('hr_hp_parcels');
            if (empty($pl_number)) continue;

            $pl_number_parts = explode(',', $pl_number);
            $parcel_number = trim(end($pl_number_parts));

            $userStatusObj = new ExplmUserStatusData();
            $user_data_status = $userStatusObj->explm_getUserStatusData('hp_parcels', $parcel_number);

            $body = array(
                'user' => $user_data_status['user'],
                'parcels' => array(
                    array(
                        'order_number' => (string) $order->get_order_number(),
                        'parcel_number' => $user_data_status['parcel_number']
                    )
                )
            );

/*             error_log('response body: ' . print_r($body, true));  */

            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($body),
                'timeout' => 20,
            );

            $remote_response = wp_remote_request($user_data_status['url'], $args);

            if (!is_wp_error($remote_response)) {
                $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);

   /*                        error_log('response body: ' . print_r($response_body, true));  */

                if (!empty($response_body['data']['statuses'][0])) {
                    $status = $response_body['data']['statuses'][0];

                    ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $status['status_message'] ?? '');
                    ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_date', $status['status_date'] ?? '');
                    ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_code', $status['status_code'] ?? '');
                    ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_color', $status['color'] ?? '');
                }
            }
        }
    }
}

function explm_initialize_parcel_statuses()
{
    new ExplmParcelStatuses();
}
add_action('plugins_loaded', 'explm_initialize_parcel_statuses');