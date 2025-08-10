<?php
if (!defined('ABSPATH')) {
    exit;
}

class ExplmParcelStatuses
{
    public function __construct() {
        add_action('wp_ajax_explm_parcel_statuses', array($this, 'explm_parcel_statuses'));
        add_action('wp_ajax_get_orders', array($this, 'get_orders'));

        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_hour'] = array(
                'interval' => 3600,
                'display' => __('Every Hour')
            );
            return $schedules;
        });

        // HP
        if (!wp_next_scheduled('explm_cron_hp_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_hp_status_update');
        }
        add_action('explm_cron_hp_status_update', array($this, 'update_hp_parcel_statuses'));

        // GLS
        if (!wp_next_scheduled('explm_cron_gls_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_gls_status_update');
        }
        add_action('explm_cron_gls_status_update', array($this, 'update_gls_parcel_statuses'));

        // OVERSEAS
        if (!wp_next_scheduled('explm_cron_overseas_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_overseas_status_update');
        }
        add_action('explm_cron_overseas_status_update', array($this, 'update_overseas_parcel_statuses'));
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

        $two_weeks_ago = strtotime('-14 days');

        $parcel_requests = array();
        $order_number_to_id = array();
        $userStatusObj = new ExplmUserStatusData();

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) > $two_weeks_ago) {
                continue;
            }

            $pl_number = $order->get_meta('hr_hp_parcels');
            if (empty($pl_number)) continue;

            $pl_number_parts = explode(',', $pl_number);
            $parcel_number = trim(end($pl_number_parts));

            $user_data_status = $userStatusObj->explm_getUserStatusData('hp_parcels', $parcel_number);
            if (empty($user_data_status['user']) || empty($user_data_status['parcel_number'])) continue;

            $order_number = (string) $order->get_order_number();
            $parcel_requests[] = array(
                'order_number' => $order_number,
                'parcel_number' => $user_data_status['parcel_number']
            );

            $order_number_to_id[$order_number] = $order_id;
            $url = $user_data_status['url'];
            $user = $user_data_status['user'];
        }

        if (empty($parcel_requests)) {
            return;
        }

        $body = array(
            'user' => $user,
            'parcels' => $parcel_requests
        );

  /*       error_log('response body: ' . print_r($body, true)); */

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120,
        );

        $remote_response = wp_remote_request($url, $args);

        if (!is_wp_error($remote_response)) {
            $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);

           /*  error_log('response body: ' . print_r($response_body, true)); */

            if (!empty($response_body['data']['statuses']) && is_array($response_body['data']['statuses'])) {
                $grouped_statuses = [];
                foreach ($response_body['data']['statuses'] as $status) {
                    $order_number = $status['order_number'] ?? null;
                    if (!$order_number) continue;

                    if (!isset($grouped_statuses[$order_number])) {
                        $grouped_statuses[$order_number] = [];
                    }

                    $grouped_statuses[$order_number][] = $status;
                }

                foreach ($grouped_statuses as $order_number => $statuses) {
                    $last_status = end($statuses);
                    $order_id = $order_number_to_id[$order_number] ?? null;

                    if ($order_id && $last_status) {
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $last_status['status_message'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_date', $last_status['status_date'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_code', $last_status['status_code'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_color', $last_status['color'] ?? '');
                    }
                }
            }
        }
    }

    public function update_gls_parcel_statuses() {
        $saved_gls_username = get_option('explm_gls_username_option', '');
        $saved_gls_password = get_option('explm_gls_password_option', '');
        $saved_gls_client_number = get_option('explm_gls_client_number_option', '');

        if ( empty($saved_gls_username) || empty($saved_gls_password) || empty($saved_gls_client_number) ) {
            return;
        }

        $orders = wc_get_orders(array(
            'limit' => -1,
            'return' => 'ids'
        ));

        $two_weeks_ago = strtotime('-14 days');

        $parcel_requests = array();
        $order_number_to_id = array();
        $userStatusObj = new ExplmUserStatusData();

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) > $two_weeks_ago) {
                continue;
            }

            $pl_number = $order->get_meta('hr_gls_parcels');
            if (empty($pl_number)) continue;

            $pl_number_parts = explode(',', $pl_number);
            $parcel_number = trim(end($pl_number_parts));

            $user_data_status = $userStatusObj->explm_getUserStatusData('gls_parcels', $parcel_number);
            if (empty($user_data_status['user']) || empty($user_data_status['parcel_number'])) continue;

            $order_number = (string) $order->get_order_number();
            $parcel_requests[] = array(
                'order_number' => $order_number,
                'parcel_number' => $user_data_status['parcel_number']
            );

            $order_number_to_id[$order_number] = $order_id;
            $url = $user_data_status['url'];
            $user = $user_data_status['user'];
        }

        if (empty($parcel_requests)) {
            return;
        }

        $body = array(
            'user' => $user,
            'parcels' => $parcel_requests
        );

/*         error_log('GLS request body: ' . print_r($body, true)); */

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120,
        );

        $remote_response = wp_remote_request($url, $args);

        if (!is_wp_error($remote_response)) {
            $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);

        /*     error_log('GLS response body: ' . print_r($response_body, true)); */

             if (!empty($response_body['data']['statuses']) && is_array($response_body['data']['statuses'])) {
                $grouped_statuses = [];
                foreach ($response_body['data']['statuses'] as $status) {
                    $order_number = $status['order_number'] ?? null;
                    if (!$order_number) continue;

                    if (!isset($grouped_statuses[$order_number])) {
                        $grouped_statuses[$order_number] = [];
                    }

                    $grouped_statuses[$order_number][] = $status;
                }

                foreach ($grouped_statuses as $order_number => $statuses) {
                    $last_status = end($statuses);
                    $order_id = $order_number_to_id[$order_number] ?? null;

                    if ($order_id && $last_status) {
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $last_status['status_message'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_date', $last_status['status_date'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_code', $last_status['status_code'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_color', $last_status['color'] ?? '');
                    }
                }
            }
        }
    }

        public function update_overseas_parcel_statuses() {
        $saved_api_key = get_option('explm_overseas_api_key_option', '');

        if ( empty($saved_api_key) ) {
            return;
        }

        $orders = wc_get_orders(array(
            'limit' => -1,
            'return' => 'ids'
        ));

        $two_weeks_ago = strtotime('-14 days');

        $parcel_requests = array();
        $order_number_to_id = array();
        $userStatusObj = new ExplmUserStatusData();

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) > $two_weeks_ago) {
                continue;
            }

            $pl_number = $order->get_meta('hr_overseas_parcels');
            if (empty($pl_number)) continue;

            $pl_number_parts = explode(',', $pl_number);
            $parcel_number = trim(end($pl_number_parts));

            $user_data_status = $userStatusObj->explm_getUserStatusData('overseas_parcels', $parcel_number);
            if (empty($user_data_status['user']) || empty($user_data_status['parcel_number'])) continue;

            $order_number = (string) $order->get_order_number();
            $parcel_requests[] = array(
                'order_number' => $order_number,
                'parcel_number' => $user_data_status['parcel_number']
            );

            $order_number_to_id[$order_number] = $order_id;
            $url = $user_data_status['url'];
            $user = $user_data_status['user'];
        }

        if (empty($parcel_requests)) {
            return;
        }

        $body = array(
            'user' => $user,
            'parcels' => $parcel_requests
        );

/*         error_log('overseas request body: ' . print_r($body, true)); */

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120,
        );

        $remote_response = wp_remote_request($url, $args);

        if (!is_wp_error($remote_response)) {
            $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);

            /* error_log('overseas response body: ' . print_r($response_body, true)); */

             if (!empty($response_body['data']['statuses']) && is_array($response_body['data']['statuses'])) {
                $grouped_statuses = [];
                foreach ($response_body['data']['statuses'] as $status) {
                    $order_number = $status['order_number'] ?? null;
                    if (!$order_number) continue;

                    if (!isset($grouped_statuses[$order_number])) {
                        $grouped_statuses[$order_number] = [];
                    }

                    $grouped_statuses[$order_number][] = $status;
                }

                foreach ($grouped_statuses as $order_number => $statuses) {
                    $last_status = end($statuses);
                    $order_id = $order_number_to_id[$order_number] ?? null;

                    if ($order_id && $last_status) {
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status', $last_status['status_message'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_date', $last_status['status_date'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_code', $last_status['status_code'] ?? '');
                        ExplmLabelMaker::update_order_meta($order_id, 'explm_parcel_status_color', $last_status['color'] ?? '');
                    }
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