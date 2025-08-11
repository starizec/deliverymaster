<?php
if (!defined('ABSPATH')) { exit; }

class ExplmParcelStatuses
{
    public function __construct() {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_hour'] = [
                'interval' => 3600,
                'display'  => __('Every Hour', 'express-label-maker')
            ];
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

        add_action('explm_cron_hp_status_update',       [$this, 'update_hp_parcel_statuses']);
        add_action('explm_cron_gls_status_update',      [$this, 'update_gls_parcel_statuses']);
        add_action('explm_cron_overseas_status_update', [$this, 'update_overseas_parcel_statuses']);
        add_action('explm_cron_dpd_status_update',      [$this, 'update_dpd_parcel_statuses']);

        add_action('restrict_manage_posts', [$this, 'add_parcel_status_filter_legacy'], 20, 2);
        add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'add_parcel_status_filter_hpos']);
        add_action('pre_get_posts', [$this, 'filter_legacy_orders_by_parcel_status']);
        add_filter('woocommerce_order_list_table_prepare_items_query_args', [$this, 'filter_hpos_orders_by_parcel_status']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_orders_filter_assets']);

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
        if (!$creds_ok()) {
            error_log("[ParcelStatuses][$carrier_key] Credentials missing – skipping update.");
            return;
        }

        $orders = wc_get_orders([
            'limit'      => -1,
            'return'     => 'ids',
            'date_after' => (new DateTime('-31 days'))->format('Y-m-d'),
        ]);
        error_log("[ParcelStatuses][$carrier_key] Found " . count($orders) . " orders for processing.");

        $two_weeks_ago = strtotime('-14 days');

        $parcel_requests     = [];
        $order_number_to_id  = [];
        $userStatusObj       = new ExplmUserStatusData();
        $user = $url = null;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $status_date = $order->get_meta('explm_parcel_status_date');
            if ($status_date && strtotime($status_date) < $two_weeks_ago) {
                continue; // older than 14 days → skip
            }

            $pl_number = $order->get_meta($order_meta_key);
            if (empty($pl_number)) continue;

            $parts         = explode(',', $pl_number);
            $parcel_number = trim(end($parts));
            if (!$parcel_number) continue;

            $u = $userStatusObj->explm_getUserStatusData($carrier_key, $parcel_number);
            if (empty($u['user']) || empty($u['parcel_number']) || empty($u['url'])) continue;

            $order_number = (string) $order->get_order_number();
            $parcel_requests[] = [
                'order_number' => $order_number,
                'parcel_number'=> $u['parcel_number'],
            ];

            $order_number_to_id[$order_number] = $order_id;
            $user = $u['user'];
            $url  = $u['url'];
        }

        error_log("[ParcelStatuses][$carrier_key] Prepared " . count($parcel_requests) . " parcel requests.");
        if (empty($parcel_requests) || !$url) {
            error_log("[ParcelStatuses][$carrier_key] No requests to send or URL missing.");
            return;
        }

        $args = [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['user' => $user, 'parcels' => $parcel_requests]),
            'timeout' => 120,
        ];

        $remote_response = wp_remote_request($url, $args);
        if (is_wp_error($remote_response)) {
            error_log("[ParcelStatuses][$carrier_key] Request error: " . $remote_response->get_error_message());
            return;
        }

        $response_body = json_decode(wp_remote_retrieve_body($remote_response), true);
        if (empty($response_body['data']['statuses']) || !is_array($response_body['data']['statuses'])) {
            error_log("[ParcelStatuses][$carrier_key] No statuses in API response.");
            return;
        }

        $grouped = [];
        foreach ($response_body['data']['statuses'] as $status) {
            $on = $status['order_number'] ?? null;
            if (!$on) continue;
            if (!isset($grouped[$on])) $grouped[$on] = [];
            $grouped[$on][] = $status;
        }

        $updated_count = 0;
        foreach ($grouped as $order_number => $statuses) {
            $last = end($statuses);
            $oid  = $order_number_to_id[$order_number] ?? null;
            if (!$oid || !$last) continue;

            $status_message = !empty($last['status_message'])
                ? $last['status_message']
                : __('Status nije dostupan', 'express-label-maker');

            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status',       $status_message);
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_date',  $last['status_date'] ?? '');
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_code',  $last['status_code'] ?? '');
            ExplmLabelMaker::update_order_meta($oid, 'explm_parcel_status_color', $last['color'] ?? '');

            $this->add_status_to_cache($status_message);
            $updated_count++;
        }
        error_log("[ParcelStatuses][$carrier_key] Updated statuses for {$updated_count} orders.");
    }

    public function add_parcel_status_filter_legacy($post_type, $which = null) {
        if ($post_type !== 'shop_order') return;

        $selected = isset($_GET['explm_parcel_status']) ? (array) $_GET['explm_parcel_status'] : [];
        $selected = array_map('sanitize_text_field', $selected);
        $options  = $this->get_parcel_status_options();
        ?>
        <select
            name="explm_parcel_status[]"
            class="wc-enhanced-select"
            multiple="multiple"
            data-placeholder="<?php esc_attr_e('Filter by parcel status', 'express'); ?>"
            data-allow_clear="true"
            style="min-width:280px">

            <?php foreach ($options as $opt): ?>
                <option value="<?php echo esc_attr($opt); ?>" <?php selected(in_array($opt, $selected, true)); ?>>
                    <?php echo esc_html($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function add_parcel_status_filter_hpos() {
        $selected = isset($_GET['explm_parcel_status']) ? (array) $_GET['explm_parcel_status'] : [];
        $selected = array_map('sanitize_text_field', $selected);
        $options  = $this->get_parcel_status_options();
        ?>
        <select
            name="explm_parcel_status[]"
            class="wc-enhanced-select"
            multiple="multiple"
            data-placeholder="<?php esc_attr_e('Filter by parcel status', 'express-label-maker'); ?>"
            data-allow_clear="true"
            style="min-width:280px">

            <?php foreach ($options as $opt): ?>
                <option value="<?php echo esc_attr($opt); ?>" <?php selected(in_array($opt, $selected, true)); ?>>
                    <?php echo esc_html($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function filter_legacy_orders_by_parcel_status($query) {
        if (!is_admin() || !$query->is_main_query()) return;

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'edit-shop_order') return;

        if (empty($_GET['explm_parcel_status'])) return;
        $statuses = array_filter(array_map('sanitize_text_field', (array) $_GET['explm_parcel_status']));
        if (!$statuses) return;

        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) $meta_query = [];

        $or = ['relation' => 'OR'];
        foreach ($statuses as $s) {
            $or[] = [
                'key'     => 'explm_parcel_status',
                'value'   => $s,
                'compare' => '=',
            ];
        }
        $meta_query[] = $or;
        $query->set('meta_query', $meta_query);
    }

    public function filter_hpos_orders_by_parcel_status($args) {
        if (empty($_GET['explm_parcel_status'])) return $args;
        $statuses = array_filter(array_map('sanitize_text_field', (array) $_GET['explm_parcel_status']));
        if (!$statuses) return $args;

        if (!isset($args['meta_query']) || !is_array($args['meta_query'])) {
            $args['meta_query'] = [];
        }

        $or = ['relation' => 'OR'];
        foreach ($statuses as $s) {
            $or[] = [
                'key'     => 'explm_parcel_status',
                'value'   => $s,
                'compare' => '=',
            ];
        }
        $args['meta_query'][] = $or;

        return $args;
    }

    private function get_parcel_status_options(): array {
        $cached = get_transient('explm_parcel_status_options');
        if (is_array($cached) && $cached) {
            return $cached;
        }
        $options = $this->get_distinct_parcel_statuses();
        set_transient('explm_parcel_status_options', $options, 6 * HOUR_IN_SECONDS);
        return $options;
    }

    private function add_status_to_cache(string $status): void {
        $status = trim($status);
        if ($status === '') return;

        $list = get_transient('explm_parcel_status_options');
        if (!is_array($list)) $list = [];
        if (!in_array($status, $list, true)) {
            $list[] = $status;
            sort($list, SORT_NATURAL | SORT_FLAG_CASE);
            set_transient('explm_parcel_status_options', $list, 6 * HOUR_IN_SECONDS);
        }
    }

    private function get_distinct_parcel_statuses(): array {
        global $wpdb;

        $is_hpos    = $this->is_hpos_enabled();
        $meta_table = $is_hpos ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;

        $sql  = $wpdb->prepare(
            "SELECT DISTINCT meta_value
             FROM {$meta_table}
             WHERE meta_key = %s AND meta_value <> ''
             ORDER BY meta_value ASC
             LIMIT 500",
            'explm_parcel_status'
        );
        $rows = $wpdb->get_col($sql);
        if (!is_array($rows)) return [];

        $rows = array_map('trim', $rows);
        $rows = array_filter($rows, static fn($v) => $v !== '');
        $rows = array_values(array_unique($rows));

        return $rows;
    }

    private function is_hpos_enabled(): bool {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    public function enqueue_orders_filter_assets() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;

    $on_orders = (
        $screen->id === 'edit-shop_order' ||
        strpos($screen->id, 'wc-orders') !== false ||
        strpos($screen->id, 'woocommerce_page_wc-orders') !== false
    );
    if (!$on_orders) return;

    // WooCommerce admin + njihov Select2 init
    wp_enqueue_style('woocommerce_admin_styles');
    wp_enqueue_script('woocommerce_admin');
    wp_enqueue_script('wc-enhanced-select');
}

}

function explm_initialize_parcel_statuses() {
    new ExplmParcelStatuses();
}
add_action('plugins_loaded', 'explm_initialize_parcel_statuses');