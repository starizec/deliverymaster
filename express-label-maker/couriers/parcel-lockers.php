<?php
if (!defined('ABSPATH')) {
    exit;
}

class ExplmParcelLockers {
    private $lockers = array();          // DPD
    private $overseas_lockers = array();   // Overseas

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_after_shipping_rate', array($this, 'add_parcel_locker_button'), 10, 2);
        add_action('wp_ajax_get_dpd_parcel_lockers', array($this, 'get_parcel_lockers'));
        add_action('wp_ajax_nopriv_get_dpd_parcel_lockers', array($this, 'get_parcel_lockers'));
        add_action('wp_ajax_get_overseas_parcel_lockers', array($this, 'get_overseas_parcel_lockers'));
        add_action('wp_ajax_nopriv_get_overseas_parcel_lockers', array($this, 'get_overseas_parcel_lockers'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_parcel_locker_to_order'));
        add_filter('woocommerce_checkout_posted_data', array($this, 'include_parcel_locker_data'));
        add_action('woocommerce_checkout_process', array($this, 'validate_parcel_locker_selection'));
        
        $this->load_parcel_lockers();
        $this->load_overseas_lockers_file();

        if ( ! wp_next_scheduled( 'update_overseas_parcelshops_cron' ) ) {
            wp_schedule_event( time(), 'every_four_hours', 'update_overseas_parcelshops_cron' );
        }
        add_action( 'update_overseas_parcelshops_cron', array( $this, 'update_overseas_parcelshops_cron_callback' ) );
    }
    
    public function update_overseas_parcelshops_cron_callback() {
        $api_key = get_option('explm_overseas_api_key_option', '');
        $enable_paketomat = get_option('explm_overseas_enable_pickup', '');
        $shipping_method = get_option('explm_overseas_pickup_shipping_method', '');
        
        if ( empty($api_key) || empty($enable_paketomat) || empty($shipping_method) ) {
            error_log('Overseas ParcelShops update skipped: required settings missing.');
            return;
        }
        
        $this->fetch_overseas_parcelshops();
        
        $features = array();
        foreach ( $this->overseas_lockers as $locker ) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        floatval($locker['lng']),
                        floatval($locker['lat'])
                    )
                ),
                'properties' => array(
                    'id' => $locker['id'],
                    'name' => $locker['name'],
                    'address' => $locker['address'],
                    'street' => $locker['street'],
                    'house_number' => $locker['house_number'],
                    'postal_code' => $locker['postal_code'],
                    'city' => $locker['city']
                )
            );
        }
        $geojson = array(
            'type' => 'FeatureCollection',
            'features' => $features
        );
        $file = plugin_dir_path( __FILE__ ) . '../js/overseas-paketomati.geojson';
        file_put_contents( $file, wp_json_encode( $geojson ) );
    }    

    function fetch_overseas_parcelshops() {
        $saved_country = get_option("explm_country_option", '');
        $api_key = get_option("explm_overseas_api_key_option", '');
        $api_url = "https://api.overseas." . $saved_country . "/parcelshops?apikey=" . $api_key;
    
        $response = wp_remote_get($api_url, array(
            'timeout' => 120
        ));        
    
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
    
        if (isset($data['status']) && $data['status'] === 0 && isset($data['data'])) {
            foreach ($data['data'] as $parcel) {
                if (!isset($parcel['GeoLat']) || empty($parcel['GeoLat']) ||
                    !isset($parcel['GeoLong']) || empty($parcel['GeoLong'])) {
                    continue;
                }
                $lat = $parcel['GeoLat'];
                $lng = $parcel['GeoLong'];
                
                $this->overseas_lockers[] = array(
                    'id'          => isset($parcel['CenterID']) ? $parcel['CenterID'] : '',
                    'name'        => isset($parcel['Address']['Name']) ? $parcel['Address']['Name'] : '',
                    'address'     => (isset($parcel['Address']['Street'], $parcel['Address']['HouseNumber'], $parcel['Address']['ZipCode'], $parcel['Address']['Place']))
                                     ? $parcel['Address']['Street'] . ' ' . $parcel['Address']['HouseNumber'] . ', ' . 
                                       $parcel['Address']['ZipCode'] . ' ' . $parcel['Address']['Place']
                                     : '',
                    'lat'         => $lat,
                    'lng'         => $lng,
                    'street'      => isset($parcel['Address']['Street']) ? $parcel['Address']['Street'] : '',
                    'house_number'=> isset($parcel['Address']['HouseNumber']) ? $parcel['Address']['HouseNumber'] : '',
                    'postal_code' => isset($parcel['Address']['ZipCode']) ? $parcel['Address']['ZipCode'] : '',
                    'city'        => isset($parcel['Address']['Place']) ? $parcel['Address']['Place'] : ''
                );
            }
        } else {
            error_log('ParcelShops API returned an error or invalid status: ' . print_r($data, true));
        }
    }

    // Overseas
    private function load_overseas_lockers_file() {
        $file = plugin_dir_path(__FILE__) . '../js/overseas-paketomati.geojson';
        if ( file_exists( $file ) ) {
            $geo_json = file_get_contents( $file );
            $data = json_decode( $geo_json, true );
            if ( json_last_error() === JSON_ERROR_NONE && isset( $data['features'] ) ) {
                foreach ( $data['features'] as $feature ) {
                    if ( isset( $feature['geometry']['coordinates'] ) && isset( $feature['properties'] ) ) {
                        $this->overseas_lockers[] = array(
                            'id'           => $feature['properties']['id'],
                            'name'         => $feature['properties']['name'],
                            'address'      => $feature['properties']['address'],
                            'lat'          => (float)$feature['geometry']['coordinates'][1],
                            'lng'          => (float)$feature['geometry']['coordinates'][0],
                            'street'       => $feature['properties']['street'],
                            'house_number' => $feature['properties']['house_number'],
                            'postal_code'  => $feature['properties']['postal_code'],
                            'city'         => $feature['properties']['city']
                        );
                    }
                }
            }
        } else {
            $this->fetch_overseas_parcelshops();
        }
    }

    // DPD
    private function load_parcel_lockers() {
        $geo_json_file = plugin_dir_path(__FILE__) . '../js/dpd-paketomati.geojson';
        
        if (file_exists($geo_json_file)) {
            $geo_json = file_get_contents($geo_json_file);
            $data = json_decode($geo_json, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (isset($feature['geometry']['coordinates']) && isset($feature['properties'])) {
                        $this->lockers[] = array(
                            'id'           => md5(serialize($feature['geometry']['coordinates'])),
                            'name'         => $feature['properties']['Naziv lokacije'],
                            'address'      => $feature['properties']['Ulica'] . ' ' . $feature['properties']['Kućni broj'] . ', ' . 
                                              $feature['properties']['Poštanski broj'] . ' ' . $feature['properties']['Grad'],
                            'lat'          => $feature['geometry']['coordinates'][1],
                            'lng'          => $feature['geometry']['coordinates'][0],
                            'street'       => $feature['properties']['Ulica'],
                            'house_number' => $feature['properties']['Kućni broj'],
                            'postal_code'  => $feature['properties']['Poštanski broj'],
                            'city'         => $feature['properties']['Grad']
                        );
                    }
                }
            }
        }
    }

    /**
     * Uključuje polja paketomata (za DPD i Overseas) u checkout postanje podataka.
     */
    public function include_parcel_locker_data($data) {
        $fields = array(
            'dpd_parcel_locker',
            'dpd_parcel_locker_name',
            'dpd_parcel_locker_address',
            'dpd_parcel_locker_street',
            'dpd_parcel_locker_house_number',
            'dpd_parcel_locker_postal_code',
            'dpd_parcel_locker_city',
            'overseas_parcel_locker',
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

    public function enqueue_scripts() {
        if (is_checkout()) {
            wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
            wp_enqueue_style('markercluster-css', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css');
            wp_enqueue_style('markercluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css');
            wp_enqueue_style('parcel-lockers-css', plugin_dir_url(__FILE__) . '../css/parcel-lockers.css');
            
            wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
            wp_enqueue_script('markercluster-js', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', array('leaflet-js'), '1.4.1', true);
            wp_enqueue_script('parcel-lockers-js', plugin_dir_url(__FILE__) . '../js/parcel-lockers.js', array('jquery', 'leaflet-js', 'markercluster-js'), '1.0.0', true);
            
            // DPD
            wp_localize_script('parcel-lockers-js', 'dpd_parcel_lockers_vars', array(
                'ajax_url'    => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('dpd_parcel_lockers_nonce'),
                'default_lat' => !empty($this->lockers) ? $this->lockers[0]['lat'] : '45.8150',
                'default_lng' => !empty($this->lockers) ? $this->lockers[0]['lng'] : '15.9819'
            ));
            
            // Overseas
            wp_localize_script('parcel-lockers-js', 'overseas_parcel_lockers_vars', array(
                'ajax_url'    => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('overseas_parcel_lockers_nonce'),
                'default_lat' => !empty($this->overseas_lockers) ? $this->overseas_lockers[0]['lat'] : '45.8150',
                'default_lng' => !empty($this->overseas_lockers) ? $this->overseas_lockers[0]['lng'] : '15.9819'
            ));
        }
    }
    
    // DPD OVERSEAS
    public function add_parcel_locker_button($method, $index) {
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
            echo '<button type="button" class="button alt" id="select-dpd-parcel-locker">' . __('Odaberite paketomat', 'express-label-maker') . '</button>';
            echo '<input type="hidden" name="dpd_parcel_locker" id="dpd_parcel_locker" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_name" id="dpd_parcel_locker_name" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_address" id="dpd_parcel_locker_address" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_street" id="dpd_parcel_locker_street" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_house_number" id="dpd_parcel_locker_house_number" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_postal_code" id="dpd_parcel_locker_postal_code" value="">';
            echo '<input type="hidden" name="dpd_parcel_locker_city" id="dpd_parcel_locker_city" value="">';
            echo '<div id="selected-dpd-parcel-locker-info" style="display:none; margin-top:10px;"></div>';
            echo '<button type="button" id="clear-dpd-parcel-locker" class="button" style="display:none;">Obriši paketomat</button>';
            echo '</div>';
            
        } elseif ($overseas_enabled === '1' && 
                  $current_method_id === $overseas_shipping_method && 
                  $current_method_id === $chosen_method) {
            
            // Overseas
            echo '<div class="overseas-parcel-locker-container">';
            echo '<button type="button" class="button alt" id="select-overseas-parcel-locker">' . __('Odaberite paketomat', 'express-label-maker') . '</button>';
            echo '<input type="hidden" name="overseas_parcel_locker" id="overseas_parcel_locker" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_name" id="overseas_parcel_locker_name" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_address" id="overseas_parcel_locker_address" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_street" id="overseas_parcel_locker_street" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_house_number" id="overseas_parcel_locker_house_number" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_postal_code" id="overseas_parcel_locker_postal_code" value="">';
            echo '<input type="hidden" name="overseas_parcel_locker_city" id="overseas_parcel_locker_city" value="">';
            echo '<div id="selected-overseas-parcel-locker-info" style="display:none; margin-top:10px;"></div>';
            echo '<button type="button" id="clear-overseas-parcel-locker" class="button" style="display:none;">Obriši paketomat</button>';
            echo '</div>';
        }
    }
    
    // DPD
    public function get_parcel_lockers() {
        check_ajax_referer('dpd_parcel_lockers_nonce', 'nonce');
        wp_send_json_success(array(
            'lockers' => $this->lockers
        ));
    }
    
    // OVERSEAS
    public function get_overseas_parcel_lockers() {
        check_ajax_referer('overseas_parcel_lockers_nonce', 'nonce');
        wp_send_json_success(array(
            'lockers' => $this->overseas_lockers
        ));
    }
    
    // DPD OVERSEAS
    public function save_parcel_locker_to_order($order_id) {
        error_log('Parcel Locker - POST data: ' . print_r($_POST, true));
        
        if (!empty($_POST['dpd_parcel_locker'])) {
            // DPD
            $locker_data = array(
                'dpd_parcel_locker_id'         => sanitize_text_field($_POST['dpd_parcel_locker']),
                'dpd_parcel_locker_name'       => sanitize_text_field($_POST['dpd_parcel_locker_name']),
                'dpd_parcel_locker_address'    => sanitize_text_field($_POST['dpd_parcel_locker_address']),
                'dpd_parcel_locker_street'     => sanitize_text_field($_POST['dpd_parcel_locker_street']),
                'dpd_parcel_locker_house_number' => sanitize_text_field($_POST['dpd_parcel_locker_house_number']),
                'dpd_parcel_locker_postal_code'=> sanitize_text_field($_POST['dpd_parcel_locker_postal_code']),
                'dpd_parcel_locker_city'       => sanitize_text_field($_POST['dpd_parcel_locker_city'])
            );
            
            foreach ($locker_data as $key => $value) {
                update_post_meta($order_id, $key, $value);
                error_log("Saved $key: $value");
            }
            
            $order = wc_get_order($order_id);
            $shipping_address_1 = $locker_data['dpd_parcel_locker_street'] . ' ' . $locker_data['dpd_parcel_locker_house_number'];
            $shipping_city      = $locker_data['dpd_parcel_locker_city'];
            $shipping_postcode  = $locker_data['dpd_parcel_locker_postal_code'];
            
            $order->set_shipping_address_1($shipping_address_1);
            $order->set_shipping_city($shipping_city);
            $order->set_shipping_postcode($shipping_postcode);
            $order->save();
            error_log("Order shipping address updated (DPD): $shipping_address_1, $shipping_postcode $shipping_city");
        } elseif (!empty($_POST['overseas_parcel_locker'])) {
            // Overseas
            $locker_data = array(
                'overseas_parcel_locker_id'         => sanitize_text_field($_POST['overseas_parcel_locker']),
                'overseas_parcel_locker_name'       => sanitize_text_field($_POST['overseas_parcel_locker_name']),
                'overseas_parcel_locker_address'    => sanitize_text_field($_POST['overseas_parcel_locker_address']),
                'overseas_parcel_locker_street'     => sanitize_text_field($_POST['overseas_parcel_locker_street']),
                'overseas_parcel_locker_house_number' => sanitize_text_field($_POST['overseas_parcel_locker_house_number']),
                'overseas_parcel_locker_postal_code'=> sanitize_text_field($_POST['overseas_parcel_locker_postal_code']),
                'overseas_parcel_locker_city'       => sanitize_text_field($_POST['overseas_parcel_locker_city'])
            );
            
            foreach ($locker_data as $key => $value) {
                update_post_meta($order_id, $key, $value);
                error_log("Saved $key: $value");
            }
            
            $order = wc_get_order($order_id);
            $shipping_address_1 = $locker_data['overseas_parcel_locker_street'] . ' ' . $locker_data['overseas_parcel_locker_house_number'];
            $shipping_city      = $locker_data['overseas_parcel_locker_city'];
            $shipping_postcode  = $locker_data['overseas_parcel_locker_postal_code'];
            
            $order->set_shipping_address_1($shipping_address_1);
            $order->set_shipping_city($shipping_city);
            $order->set_shipping_postcode($shipping_postcode);
            $order->save();
            error_log("Order shipping address updated (Overseas): $shipping_address_1, $shipping_postcode $shipping_city");
        } else {
            error_log('Parcel Locker - No locker data found in POST');
        }
    }

    public function validate_parcel_locker_selection() {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_method = isset($chosen_methods[0]) ? str_replace(":", "-", $chosen_methods[0]) : '';

        // DPD
        $dpd_enabled = get_option('explm_dpd_enable_pickup', '');
        $dpd_shipping_method = get_option('explm_dpd_pickup_shipping_method', '');
        $dpd_shipping_method = str_replace(":", "-", $dpd_shipping_method);

        if ($dpd_enabled === '1' && $chosen_method === $dpd_shipping_method) {
            if (empty($_POST['dpd_parcel_locker'])) {
                wc_add_notice(esc_html__('Molimo odaberite paketomat za DPD dostavu.', 'express-label-maker'), 'error');
            }
        }
        
        // Overseas
        $overseas_enabled = get_option('explm_overseas_enable_pickup', '');
        $overseas_shipping_method = get_option('explm_overseas_pickup_shipping_method', '');
        $overseas_shipping_method = str_replace(":", "-", $overseas_shipping_method);
        
        if ($overseas_enabled === '1' && $chosen_method === $overseas_shipping_method) {
            if (empty($_POST['overseas_parcel_locker'])) {
                wc_add_notice(esc_html__('Molimo odaberite paketomat za Overseas dostavu.', 'express-label-maker'), 'error');
            }
        }
    }

}

new ExplmParcelLockers();