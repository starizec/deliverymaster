<?php
if (!defined('ABSPATH')) {
    exit;
}

class ExplmParcelLockers {
    private $dpd_lockers = array();          // DPD
    private $overseas_lockers = array();   // Overseas

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'explm_enqueue_scripts'));
        add_action('woocommerce_after_shipping_rate', array($this, 'explm_add_parcel_locker_button'), 10, 2);
        add_action('wp_ajax_get_dpd_parcel_lockers', array($this, 'explm_get_parcel_lockers'));
        add_action('wp_ajax_nopriv_get_dpd_parcel_lockers', array($this, 'explm_get_parcel_lockers'));
        add_action('wp_ajax_get_overseas_parcel_lockers', array($this, 'explm_get_overseas_parcel_lockers'));
        add_action('wp_ajax_nopriv_get_overseas_parcel_lockers', array($this, 'explm_get_overseas_parcel_lockers'));
        add_action('woocommerce_checkout_create_order', array($this, 'explm_save_parcel_locker_to_order'), 20, 2);
        add_filter('woocommerce_checkout_posted_data', array($this, 'explm_include_parcel_locker_data'));
        add_action('woocommerce_checkout_process', array($this, 'explm_validate_parcel_locker_selection'));

        // Overseas Cron

        if (!wp_next_scheduled('explm_update_overseas_parcelshops_cron')) {
            wp_schedule_event(time(), 'daily', 'explm_update_overseas_parcelshops_cron');
        }
        add_action('explm_update_overseas_parcelshops_cron', array($this, 'explm_update_overseas_parcelshops_cron_callback'));

         // DPD Cron

         if (!wp_next_scheduled('explm_update_dpd_parcelshops_cron')) {
            wp_schedule_event(time(), 'daily', 'explm_update_dpd_parcelshops_cron');
        }
        add_action('explm_update_dpd_parcelshops_cron', array($this, 'explm_update_dpd_parcelshops_cron_callback'));    
    }
    
        // Overseas
    public function explm_update_overseas_parcelshops_cron_callback() {
        $api_key = get_option('explm_overseas_api_key_option', '');
        $enable_paketomat = get_option('explm_overseas_enable_pickup', '');
        $shipping_method = get_option('explm_overseas_pickup_shipping_method', '');
    
        if ( empty($api_key) || $enable_paketomat !== '1' || empty($shipping_method) ) {
            return;
        }
    
        $saved_country = get_option("explm_country_option", '');
        $courier = 'overseas';
        $api_url = "https://expresslabelmaker.com/api/v1/{$saved_country}/{$courier}/delivery-locations";
    
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . $courier);
    
        $body = array(
            'user' => $user_data
        );
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );
    
        $response = wp_remote_request($api_url, $args);
    
        if (is_wp_error($response)) {
            error_log('ParcelShops API error: ' . $response->get_error_message());
            return;
        }
    
        $body = wp_remote_retrieve_body($response);
    
        $data = json_decode($body, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ParcelShops API JSON error: ' . json_last_error_msg());
            return;
        }
    
        if (isset($data['data']['geojson'])) {
            $file = plugin_dir_path(__FILE__) . '../json/overseas-parcelshops.geojson';
            file_put_contents($file, wp_json_encode($data['data']['geojson']));
        } else {
            error_log('ParcelShops API missing geojson: ' . print_r($data, true));
        }
    }      

    // Overseas
    private function load_overseas_lockers_file() {
        $this->overseas_lockers = array();
        $file = plugin_dir_path(__FILE__) . '../json/overseas-parcelshops.geojson';
        if ( file_exists( $file ) ) {
            $geo_json = file_get_contents( $file );
            $data = json_decode( $geo_json, true );
    
            if ( json_last_error() === JSON_ERROR_NONE && isset( $data['features'] ) ) {
                foreach ( $data['features'] as $feature ) {
                    if ( isset( $feature['geometry']['coordinates'] ) && isset( $feature['properties'] ) ) {
                        $props = $feature['properties'];
                        $this->overseas_lockers[] = array(
                            'location_id'           => $props['location_id'] ?? '',
                            'name'         => $props['name'] ?? '',
                            'address'      => trim(
                                ($props['street'] ?? '') . ' ' .
                                ($props['house_number'] ?? '') . ', ' .
                                ($props['postal_code'] ?? '') . ' ' .
                                ($props['place'] ?? '')
                            ),
                            'lat'          => (float)($feature['geometry']['coordinates'][1] ?? 0),
                            'lng'          => (float)($feature['geometry']['coordinates'][0] ?? 0),
                            'street'       => $props['street'] ?? '',
                            'house_number' => $props['house_number'] ?? '',
                            'postal_code'  => $props['postal_code'] ?? '',
                            'city'         => $props['place'] ?? ''
                        );
                    }
                }
            }
        }
    }    

    // DPD

    public function explm_update_dpd_parcelshops_cron_callback() {
        $saved_dpd_username = get_option('explm_dpd_username_option', '');
        $saved_dpd_password = get_option('explm_dpd_password_option', '');
        $enable_paketomat = get_option('explm_dpd_enable_pickup', '');
        $shipping_method = get_option('explm_dpd_pickup_shipping_method', '');
    
        if ( empty($saved_dpd_username) || empty($saved_dpd_password) || $enable_paketomat !== '1' || empty($shipping_method) ) {
            return;
        }
    
        $saved_country = get_option("explm_country_option", '');
        $courier = 'dpd';
        $api_url = "https://expresslabelmaker.com/api/v1/{$saved_country}/{$courier}/delivery-locations";
    
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . $courier);
    
        $body = array(
            'user' => $user_data
        );
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );
    
        $response = wp_remote_request($api_url, $args);
    
        if (is_wp_error($response)) {
            error_log('DPD ParcelShops API error: ' . $response->get_error_message());
            return;
        }
    
        $body = wp_remote_retrieve_body($response);
    
        $data = json_decode($body, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('DPD ParcelShops API JSON error: ' . json_last_error_msg());
            return;
        }
    
        if (isset($data['data']['geojson'])) {
            $file = plugin_dir_path(__FILE__) . '../json/dpd-parcelshops.geojson';
            file_put_contents($file, wp_json_encode($data['data']['geojson']));
        } else {
            error_log('DPD ParcelShops API missing geojson: ' . print_r($data, true));
        }
    }
    
    private function load_dpd_parcel_lockers() {
        $this->dpd_lockers = array();
        $file = plugin_dir_path(__FILE__) . '../json/dpd-parcelshops.geojson';
        if ( file_exists( $file ) ) {
            $geo_json = file_get_contents( $file );
            $data = json_decode( $geo_json, true );
    
            if ( json_last_error() === JSON_ERROR_NONE && isset( $data['features'] ) ) {
                foreach ( $data['features'] as $feature ) {
                    if ( isset( $feature['geometry']['coordinates'] ) && isset( $feature['properties'] ) ) {
                        $props = $feature['properties'];
                        $this->dpd_lockers[] = array(
                            'location_id'   => $props['location_id'] ?? '',
                            'name'          => $props['name'] ?? '',
                            'address'       => trim(
                                ($props['street'] ?? '') . ' ' .
                                ($props['house_number'] ?? '') . ', ' .
                                ($props['postal_code'] ?? '') . ' ' .
                                ($props['place'] ?? '')
                            ),
                            'lat'          => (float)($feature['geometry']['coordinates'][1] ?? 0),
                            'lng'          => (float)($feature['geometry']['coordinates'][0] ?? 0),
                            'street'        => $props['street'] ?? '',
                            'house_number'  => $props['house_number'] ?? '',
                            'postal_code'   => $props['postal_code'] ?? '',
                            'city'          => $props['place'] ?? ''
                        );
                    }
                }
            }
        }
    }    

    public function explm_include_parcel_locker_data($data) {
        $fields = array(
            'dpd_parcel_locker_location_id',
            'dpd_parcel_locker_name',
            'dpd_parcel_locker_address',
            'dpd_parcel_locker_street',
            'dpd_parcel_locker_house_number',
            'dpd_parcel_locker_postal_code',
            'dpd_parcel_locker_city',
            'overseas_parcel_locker_location_id',
            'overseas_parcel_locker_name',
            'overseas_parcel_locker_address',
            'overseas_parcel_locker_street',
            'overseas_parcel_locker_house_number',
            'overseas_parcel_locker_postal_code',
            'overseas_parcel_locker_city'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        return $data;
    }

    public function explm_enqueue_scripts() {
        if (is_checkout()) {    

            $plugin_version = ExplmLabelMaker::get_plugin_version();

            // CSS
            wp_enqueue_style('leaflet-css', plugin_dir_url(__FILE__) . '../css/vendor/leaflet.css', array(), '1.7.1');
            wp_enqueue_style('markercluster-css', plugin_dir_url(__FILE__) . '../css/vendor/markercluster.css', array(), '1.4.1');
            wp_enqueue_style('markercluster-default-css', plugin_dir_url(__FILE__) . '../css/vendor/markercluster.default.css', array(), '1.4.1');
            wp_enqueue_style('parcel-lockers-css', plugin_dir_url(__FILE__) . '../css/parcel-lockers.css', array(), $plugin_version);

            // JS
            wp_enqueue_script('leaflet-js', plugin_dir_url(__FILE__) . '../js/vendor/leaflet.js', array(), '1.7.1', true);
            wp_enqueue_script('markercluster-js', plugin_dir_url(__FILE__) . '../js/vendor/leaflet.markercluster.js', array('leaflet-js'), '1.4.1', true);
            wp_enqueue_script('parcel-lockers-js', plugin_dir_url(__FILE__) . '../js/parcel-lockers.js', array('jquery', 'leaflet-js', 'markercluster-js'), $plugin_version, true);
            
            // DPD
            wp_localize_script('parcel-lockers-js', 'dpd_parcel_lockers_vars', array(
                'ajax_url'    => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('dpd_parcel_lockers_nonce'),
                'default_lat' => !empty($this->dpd_lockers) ? $this->dpd_lockers[0]['lat'] : '45.8150',
                'default_lng' => !empty($this->dpd_lockers) ? $this->dpd_lockers[0]['lng'] : '15.9819'
            ));
            
            // Overseas
            wp_localize_script('parcel-lockers-js', 'overseas_parcel_lockers_vars', array(
                'ajax_url'    => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('overseas_parcel_lockers_nonce'),
                'default_lat' => !empty($this->overseas_lockers) ? $this->overseas_lockers[0]['lat'] : '45.8150',
                'default_lng' => !empty($this->overseas_lockers) ? $this->overseas_lockers[0]['lng'] : '15.9819'
            ));

            wp_localize_script('parcel-lockers-js', 'parcel_locker_i18n', array(
                'loading'             => __('Loading...', 'express-label-maker'),
                'choose_locker'       => __('Choose parcel locker', 'express-label-maker'),
                'no_lockers'          => __('There are no parcel lockers to display.', 'express-label-maker'),
                'search_placeholder'  => __('Search parcel lockers...', 'express-label-maker'),
                'selected_locker'     => __('Selected parcel locker', 'express-label-maker'),
                'clear'               => __('Delete parcel locker', 'express-label-maker'),
                'no_parcel_lockers'   => __('There are no parcel lockers to display...', 'express-label-maker'),
            ));
        }
    }
    
    // DPD OVERSEAS
    public function explm_add_parcel_locker_button($method, $index) {
        if (!is_checkout()) {
            return;
        }
        
        $dpd_enabled = get_option('explm_dpd_enable_pickup', '');
        $dpd_shipping_method = get_option('explm_dpd_pickup_shipping_method', '');
        
        $overseas_enabled = get_option('explm_overseas_enable_pickup', '');
        $overseas_shipping_method = get_option('explm_overseas_pickup_shipping_method', '');
        
        $current_method_id = str_replace(":", "-", $method->id);
        
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_method = isset($chosen_methods[0]) ? str_replace(":", "-", $chosen_methods[0]) : '';
        
        $dpd_shipping_method = str_replace(":", "-", $dpd_shipping_method);
        $overseas_shipping_method = str_replace(":", "-", $overseas_shipping_method);
        
        if ($dpd_enabled === '1' && 
            $current_method_id === $dpd_shipping_method && 
            $current_method_id === $chosen_method) {
            
            // DPD
            echo '<div class="dpd-parcel-locker-container">';
            echo '<button type="button" class="button alt" id="select-dpd-parcel-locker">' . __('Choose parcel locker', 'express-label-maker') . '</button>';
            echo '<input type="hidden" name="dpd_parcel_locker_location_id" id="dpd_parcel_locker_location_id" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_name" id="dpd_parcel_locker_name" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_address" id="dpd_parcel_locker_address" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_street" id="dpd_parcel_locker_street" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_house_number" id="dpd_parcel_locker_house_number" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_postal_code" id="dpd_parcel_locker_postal_code" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_city" id="dpd_parcel_locker_city" value="">';
            echo '<div id="selected-dpd-parcel-locker-info" style="display:none; margin-top:10px;"></div>';
            echo '<button type="button" id="clear-dpd-parcel-locker" class="button" style="display:none;">' . __('Delete parcel locker', 'express-label-maker') . '</button>';
            echo '</div>';
            
        } elseif ($overseas_enabled === '1' && 
                  $current_method_id === $overseas_shipping_method && 
                  $current_method_id === $chosen_method) {
            
            // Overseas
            echo '<div class="overseas-parcel-locker-container">';
            echo '<button type="button" class="button alt" id="select-overseas-parcel-locker">' . __('Choose parcel locker', 'express-label-maker') . '</button>';
            echo '<input type="hidden" name="overseas_parcel_locker_location_id" id="overseas_parcel_locker_location_id" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_name" id="overseas_parcel_locker_name" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_address" id="overseas_parcel_locker_address" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_street" id="overseas_parcel_locker_street" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_house_number" id="overseas_parcel_locker_house_number" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_postal_code" id="overseas_parcel_locker_postal_code" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_city" id="overseas_parcel_locker_city" value="">';
            echo '<div id="selected-overseas-parcel-locker-info" style="display:none; margin-top:10px;"></div>';
            echo '<button type="button" id="clear-overseas-parcel-locker" class="button" style="display:none;">' . __('Delete parcel locker', 'express-label-maker') . '</button>';
            echo '</div>';
        }
    }
    
    // DPD
    public function explm_get_parcel_lockers() {
        check_ajax_referer('dpd_parcel_lockers_nonce', 'nonce');

        if (empty($this->dpd_lockers)) {
            $this->load_dpd_parcel_lockers();
        }

        wp_send_json_success(array(
            'lockers' => $this->dpd_lockers
        ));
    }

    // OVERSEAS
    public function explm_get_overseas_parcel_lockers() {
        check_ajax_referer('overseas_parcel_lockers_nonce', 'nonce');

        if (empty($this->overseas_lockers)) {
            $this->load_overseas_lockers_file();
        }

        wp_send_json_success(array(
            'lockers' => $this->overseas_lockers
        ));
    }

    
    // DPD OVERSEAS
    public function explm_save_parcel_locker_to_order($order, $data) {
        if (!empty($_POST['dpd_parcel_locker_location_id'])) {
            $locker_data = array(
                'dpd_parcel_locker_location_id'   => sanitize_text_field($_POST['dpd_parcel_locker_location_id']),
                'dpd_parcel_locker_name'          => sanitize_text_field($_POST['dpd_parcel_locker_name']),
                'dpd_parcel_locker_address'       => sanitize_text_field($_POST['dpd_parcel_locker_address']),
                'dpd_parcel_locker_street'        => sanitize_text_field($_POST['dpd_parcel_locker_street']),
                'dpd_parcel_locker_house_number'  => sanitize_text_field($_POST['dpd_parcel_locker_house_number']),
                'dpd_parcel_locker_postal_code'   => sanitize_text_field($_POST['dpd_parcel_locker_postal_code']),
                'dpd_parcel_locker_city'          => sanitize_text_field($_POST['dpd_parcel_locker_city']),
            );
    
            foreach ($locker_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }
    
            $order->set_shipping_address_1($locker_data['dpd_parcel_locker_street'] . ' ' . $locker_data['dpd_parcel_locker_house_number']);
            $order->set_shipping_postcode($locker_data['dpd_parcel_locker_postal_code']);
            $order->set_shipping_city($locker_data['dpd_parcel_locker_city']);
    
        } elseif (!empty($_POST['overseas_parcel_locker_location_id'])) {
            $locker_data = array(
                'overseas_parcel_locker_location_id'   => sanitize_text_field($_POST['overseas_parcel_locker_location_id']),
                'overseas_parcel_locker_name'          => sanitize_text_field($_POST['overseas_parcel_locker_name']),
                'overseas_parcel_locker_address'       => sanitize_text_field($_POST['overseas_parcel_locker_address']),
                'overseas_parcel_locker_street'        => sanitize_text_field($_POST['overseas_parcel_locker_street']),
                'overseas_parcel_locker_house_number'  => sanitize_text_field($_POST['overseas_parcel_locker_house_number']),
                'overseas_parcel_locker_postal_code'   => sanitize_text_field($_POST['overseas_parcel_locker_postal_code']),
                'overseas_parcel_locker_city'          => sanitize_text_field($_POST['overseas_parcel_locker_city']),
            );
    
            foreach ($locker_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }
    
            $order->set_shipping_address_1($locker_data['overseas_parcel_locker_street'] . ' ' . $locker_data['overseas_parcel_locker_house_number']);
            $order->set_shipping_postcode($locker_data['overseas_parcel_locker_postal_code']);
            $order->set_shipping_city($locker_data['overseas_parcel_locker_city']);
        }
    }    

    public function explm_validate_parcel_locker_selection() {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_method = isset($chosen_methods[0]) ? str_replace(":", "-", $chosen_methods[0]) : '';

        // DPD
        $dpd_enabled = get_option('explm_dpd_enable_pickup', '');
        $dpd_shipping_method = get_option('explm_dpd_pickup_shipping_method', '');
        $dpd_shipping_method = str_replace(":", "-", $dpd_shipping_method);

        if ($dpd_enabled === '1' && $chosen_method === $dpd_shipping_method) {
            if (empty($_POST['dpd_parcel_locker_location_id'])) {
                wc_add_notice(esc_html__('Please select a parcel machine for DPD delivery.', 'express-label-maker'), 'error');
            }
        }
        
        // Overseas
        $overseas_enabled = get_option('explm_overseas_enable_pickup', '');
        $overseas_shipping_method = get_option('explm_overseas_pickup_shipping_method', '');
        $overseas_shipping_method = str_replace(":", "-", $overseas_shipping_method);
        
        if ($overseas_enabled === '1' && $chosen_method === $overseas_shipping_method) {
            if (empty($_POST['overseas_parcel_locker_location_id'])) {
                wc_add_notice(esc_html__('Please select a parcel locker for Overseas delivery.', 'express-label-maker'), 'error');
            }
        }
    }

}

new ExplmParcelLockers();