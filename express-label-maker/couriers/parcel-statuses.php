<?php
if (!defined('ABSPATH')) { exit; }

class ExplmParcelStatuses
{
    public function __construct() {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_hour'] = array(
                'interval' => 3600,
                'display'  => __('Every Hour', 'explm')
            );
            return $schedules;
        });

        if (!wp_next_scheduled('explm_cron_hp_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_hp_status_update');
        }
        if (!wp_next_scheduled('explm_cron_gls_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_gls_status_update');
        }
        if (!wp_next_scheduled('explm_cron_overseas_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_overseas_status_update');
        }
        if (!wp_next_scheduled('explm_cron_dpd_status_update')) {
            wp_schedule_event(time(), 'every_hour', 'explm_cron_dpd_status_update');
        }

        add_action('explm_cron_hp_status_update',       array($this, 'update_hp_parcel_statuses'));
        add_action('explm_cron_gls_status_update',      array($this, 'update_gls_parcel_statuses'));
        add_action('explm_cron_overseas_status_update', array($this, 'update_overseas_parcel_statuses'));
        add_action('explm_cron_dpd_status_update',      array($this, 'update_dpd_parcel_statuses'));
    }

    // HP
    public function update_hp_parcel_statuses() {
        $creds_ok = function () {
            return get_option('explm_hp_username_option') && get_option('explm_hp_password_option');
        };
            $saved_country = strtolower(get_option('explm_country_option', ''));
            $meta_key = $saved_country . '_hp_parcels';

            $this->update_carrier_statuses('hp_parcels', $meta_key, $creds_ok);
    }

    // GLS
    public function update_gls_parcel_statuses() {
        $creds_ok = function () {
            return get_option('explm_gls_username_option')
                && get_option('explm_gls_password_option')
                && get_option('explm_gls_client_number_option');
        };
            $saved_country = strtolower(get_option('explm_country_option', ''));
            $meta_key = $saved_country . '_gls_parcels';

            $this->update_carrier_statuses('gls_parcels', $meta_key, $creds_ok);
    }

    // OVERSEAS
    public function update_overseas_parcel_statuses() {
        $creds_ok = function () {
            return get_option('explm_overseas_api_key_option');
        };
            $saved_country = strtolower(get_option('explm_country_option', ''));
            $meta_key = $saved_country . '_overseas_parcels';

            $this->update_carrier_statuses('overseas_parcels', $meta_key, $creds_ok);
    }

    // DPD
    public function update_dpd_parcel_statuses() {
        $creds_ok = function () {
            return get_option('explm_dpd_username_option') && get_option('explm_dpd_password_option');
        };
            $saved_country = strtolower(get_option('explm_country_option', ''));
            $meta_key = $saved_country . '_dpd_parcels';

            $this->update_carrier_statuses('dpd_parcels', $meta_key, $creds_ok);
    }


    private function update_carrier_statuses($carrier_key, $order_meta_key, callable $creds_ok) {
        if (!$creds_ok()) return;

 
        $orders = wc_get_orders(array(
            'limit'      => -1,
            'return'     => 'ids',
            'date_after' => (new DateTime('-31 days'))->format('Y-m-d'), 
        ));

        error_log("[ParcelStatuses][$carrier_key] Found " . count($orders) . " orders for processing.");

        $two_weeks_ago = strtotime('-14 days');

        $parcel_requests   = array();
        $order_number_to_id = array();
        $userStatusObj     = new ExplmUserStatusData();
        $user = $url = null;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;


            $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) < $two_weeks_ago) {
                continue;
            }

            $pl_number = $order->get_meta($order_meta_key);
            if (empty($pl_number)) continue;

            $parts          = explode(',', $pl_number);
            $parcel_number  = trim(end($parts));
            if (!$parcel_number) continue;

            $u = $userStatusObj->explm_getUserStatusData($carrier_key, $parcel_number);
            if (empty($u['user']) || empty($u['parcel_number']) || empty($u['url'])) continue;

            $order_number = (string) $order->get_order_number();
            $parcel_requests[] = array(
                'order_number' => $order_number,
                'parcel_number'=> $u['parcel_number'],
            );

            $order_number_to_id[$order_number] = $order_id;
            $user = $u['user'];
            $url  = $u['url'];
        }

        error_log("[ParcelStatuses][$carrier_key] Prepared " . count($parcel_requests) . " parcel requests.");

        if (empty($parcel_requests) || !$url) return;

        $args = array(
            'method'  => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array(
                'user'    => $user,
                'parcels' => $parcel_requests,
            )),
            'timeout' => 120,
        );

        $remote_response = wp_remote_request($url, $args);
        if (is_wp_error($remote_response)) return;

        $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);
        if (empty($response_body['data']['statuses']) || !is_array($response_body['data']['statuses'])) return;

        $grouped = array();
        foreach ($response_body['data']['statuses'] as $status) {
            $on = $status['order_number'] ?? null;
            if (!$on) continue;
            if (!isset($grouped[$on])) $grouped[$on] = array();
            $grouped[$on][] = $status;
        }

        foreach ($grouped as $order_number => $statuses) {
            $last = end($statuses);
            $oid  = $order_number_to_id[$order_number] ?? null;
            if (!$oid || !$last) continue;

            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status',       $last['status_message'] ?? '');
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_date',  $last['status_date'] ?? '');
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_code',  $last['status_code'] ?? '');
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_color', $last['color'] ?? '');
        }
    }
}

function explm_initialize_parcel_statuses() {
    new ExplmParcelStatuses();
}
add_action('plugins_loaded', 'explm_initialize_parcel_statuses');