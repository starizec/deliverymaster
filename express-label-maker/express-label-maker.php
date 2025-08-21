<?php

/**
 * Plugin Name: Express Label Maker
 * Plugin URI: https://expresslabelmaker.com/
 * Description: Print shipping labels and track parcels for multiple couriers directly from WooCommerce.
 * Tags: woocommerce, shipping, label printing, DPD, Overseas, Hrvatska pošta, GLS
 * Version: 25.8.22.1
 * Author: expresslabelmaker
 * Tested up to: 6.8
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: express-label-maker
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('EXPLM_API_BASE_URL', 'https://test.expresslabelmaker.com/'); 

foreach ([
    'settings/general-settings.php',
    'settings/licence-settings.php',
    'settings/dpd-settings.php',
    'settings/overseas-settings.php',
    'settings/hp-settings.php',
    'settings/gls-settings.php',
    'settings/user-data.php',
    'settings/licence.php',
    'couriers/print-label.php',
    'couriers/print-labels.php',
    'couriers/all-couriers.php',
    'couriers/user-status-data.php',
    'couriers/parcel-statuses.php',
    'couriers/collection-request.php',
    'couriers/parcel-lockers.php',
] as $file) {
    require_once plugin_dir_path(__FILE__) . $file;
}

class ExplmLabelMaker
{
    protected $couriers;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'explm_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('woocommerce_admin_order_actions_end', array($this, 'explm_order_column_content'));
        add_action('wp_ajax_explm_show_confirm_modal', array($this, 'explm_show_confirm_modal'));
        add_action('wp_ajax_explm_show_collection_modal', array($this, 'explm_show_collection_modal'));
        add_action('plugins_loaded', array($this, 'explm_plugin_load_textdomain'), 30);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'explm_add_plugin_settings_link'));

        //HPOS
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'explm_add_dpd_print_label_bulk_action'));
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'explm_add_overseas_print_label_bulk_action'));
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'explm_add_hp_print_label_bulk_action'));
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'explm_add_gls_print_label_bulk_action'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'display_custom_order_meta_data'], 20, 2);
        add_action('add_meta_boxes_woocommerce_page_wc-orders', array($this, 'explm_add_custom_meta_box'));
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'explm_add_status_column_header'), 20);
        // Legacy mode
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_dpd_print_label_bulk_action'));
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_overseas_print_label_bulk_action'));
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_hp_print_label_bulk_action'));
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_gls_print_label_bulk_action'));
        add_action('manage_shop_order_posts_custom_column', [$this, 'display_custom_order_meta_data'], 20, 2);
        add_action('add_meta_boxes', array($this, 'explm_add_custom_meta_box'));
        add_filter('manage_edit-shop_order_columns', array($this, 'explm_add_status_column_header'), 20);
        
        $this->couriers = new ExpmlCouriers();
    }

    public function explm_enqueue_scripts($hook) {
        $plugin_version = self::get_plugin_version();

        wp_enqueue_style('explm_admin_css', plugin_dir_url(__FILE__) . 'css/elm.css', array(), $plugin_version);
        wp_enqueue_script('sweetalert2', plugin_dir_url(__FILE__) . 'js/vendor/sweetalert2.all.min.js', array(), '11.0.0', true);
    
        wp_enqueue_script('explm_admin_js', plugin_dir_url(__FILE__) . 'js/elm.js', array('jquery'), $plugin_version, true);
        wp_enqueue_script('explm_print_label_js', plugin_dir_url(__FILE__) . 'js/print-label.js', array('jquery', 'sweetalert2'), $plugin_version, true);
        wp_enqueue_script('explm_print_labels_js', plugin_dir_url(__FILE__) . 'js/print-labels.js', array('jquery', 'sweetalert2'), $plugin_version, true);
        wp_enqueue_script('explm_licence_settings_js', plugin_dir_url(__FILE__) . 'js/licence-settings.js', array('jquery', 'sweetalert2'), $plugin_version, true);
        wp_enqueue_script('explm_collection_request_js', plugin_dir_url(__FILE__) . 'js/collection-request.js', array('jquery', 'sweetalert2'), $plugin_version, true);
    
        wp_localize_script(
            'explm_admin_js',
            'explm_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('explm_nonce'),
                'email' => get_option('explm_email_option', ''),
                'licence' => get_option('explm_licence_option', ''),
                'serviceType' => get_option('explm_dpd_service_type_option', ''),
                'savedLabelTime' => esc_html__('%1$d minutes of your life just came back. That’s %2$d h and %3$d min you didn’t spend typing shipping labels. Your keyboard thanks you.', 'express-label-maker'),
                'dpd_note' => get_option('explm_dpd_customer_note', ''),
                'overseas_note' => get_option('explm_overseas_customer_note', ''),
                'hp_note' => get_option('explm_hp_customer_note', ''),
                'hp_sender_name' => get_option('explm_hp_company_or_personal_name', ''),
                'hp_sender_phone' => get_option('explm_hp_phone', ''),
                'hp_sender_email' => get_option('explm_hp_email', ''),
                'hp_sender_street' => get_option('explm_hp_street', ''),
                'hp_sender_number' => get_option('explm_hp_property_number', ''),
                'hp_sender_city' => get_option('explm_hp_city', ''),
                'hp_sender_postcode' => get_option('explm_hp_postal_code', ''),
                'hp_sender_country' => get_option('explm_hp_country', ''),
                'gls_note' => get_option('explm_gls_customer_note', ''),
                'gls_sender_name' => get_option('explm_gls_company_or_personal_name', ''),
                'gls_sender_phone' => get_option('explm_gls_phone', ''),
                'gls_sender_email' => get_option('explm_gls_email', ''),
                'gls_sender_street' => get_option('explm_gls_street', ''),
                'gls_sender_number' => get_option('explm_gls_property_number', ''),
                'gls_sender_city' => get_option('explm_gls_city', ''),
                'gls_sender_postcode' => get_option('explm_gls_postal_code', ''),
                'gls_sender_country' => get_option('explm_gls_country', ''),
                'dpd_sender_name' => get_option('explm_dpd_company_or_personal_name', ''),
                'dpd_sender_phone' => get_option('explm_dpd_phone', ''),
                'dpd_sender_email' => get_option('explm_dpd_email', ''),
                'dpd_sender_street' => get_option('explm_dpd_street', ''),
                'dpd_sender_number' => get_option('explm_dpd_property_number', ''),
                'dpd_sender_city' => get_option('explm_dpd_city', ''),
                'dpd_sender_postcode' => get_option('explm_dpd_postal_code', ''),
                'dpd_sender_country' => get_option('explm_dpd_country', ''),
                'overseas_sender_name' => get_option('explm_overseas_company_or_personal_name', ''),
                'overseas_sender_phone' => get_option('explm_overseas_phone', ''),
                'overseas_sender_email' => get_option('explm_overseas_email', ''),
                'overseas_sender_street' => get_option('explm_overseas_street', ''),
                'overseas_sender_number' => get_option('explm_overseas_property_number', ''),
                'overseas_sender_city' => get_option('explm_overseas_city', ''),
                'overseas_sender_postcode' => get_option('explm_overseas_postal_code', ''),
                'overseas_sender_country' => get_option('explm_overseas_country', ''),
            )
        );
    }    

    // Translation
    public function explm_plugin_load_textdomain() {
        load_plugin_textdomain('express-label-maker', false, plugin_basename(dirname(__FILE__)) . '/languages/');
    }    

    // Settings, Menu
    public function explm_add_plugin_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=express_label_maker&tab=licence') . '">' . esc_html__('Settings', 'express-label-maker') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }    

    public function add_submenu_page()
    {
        add_submenu_page(
            'woocommerce',
            esc_html__('Express Label Maker', 'express-label-maker'),
            esc_html__('Express Label Maker', 'express-label-maker'),
            'manage_woocommerce',
            'express_label_maker',
            array($this, 'submenu_page_content')
        );
    }

    public function submenu_page_content() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'licence';
    
        echo '<div class="wrap">';
        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
        echo '<a href="?page=express_label_maker&tab=licence" class="nav-tab ' . esc_attr($tab == 'licence' ? 'nav-tab-active' : '') . '">' . esc_html__('Licence', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=settings" class="nav-tab ' . esc_attr($tab == 'settings' ? 'nav-tab-active' : '') . '">' . esc_html__('Settings', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=dpd" class="nav-tab ' . esc_attr($tab == 'dpd' ? 'nav-tab-active' : '') . '">' . esc_html__('DPD', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=overseas" class="nav-tab ' . esc_attr($tab == 'overseas' ? 'nav-tab-active' : '') . '">' . esc_html__('Overseas', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=hp" class="nav-tab ' . esc_attr($tab == 'hp' ? 'nav-tab-active' : '') . '">' . esc_html__('HP', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=gls" class="nav-tab ' . esc_attr($tab == 'gls' ? 'nav-tab-active' : '') . '">' . esc_html__('GLS', 'express-label-maker') . '</a>';
        echo '</nav>';

        if ($tab == 'licence') {
            explm_licence_tab_content();
        } else if ($tab == 'settings') {
            explm_settings_tab_content();
        } elseif ($tab == 'dpd') {
            explm_dpd_tab_content();
        } elseif ($tab == 'overseas') {
            explm_overseas_tab_content();
        } elseif ($tab == 'hp') {
            explm_hp_tab_content();
        } elseif ($tab == 'gls') {
            explm_gls_tab_content();
        }
    
        echo '</div>';
    }

    public function explm_add_custom_meta_box() {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') 
            ? wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
                ? 'woocommerce_page_wc-orders'
                : 'shop_order'
            : 'shop_order';
    
        add_meta_box(
            'explm_custom_order_metabox',
            esc_html__('Express Label Maker', 'express-label-maker'),
            array($this, 'explm_display_custom_meta_box_content'),
            $screen,
            'side',
            'default'
        );
    }

    public function explm_add_dpd_print_label_bulk_action($actions)
    {
        $saved_dpd_username = get_option('explm_dpd_username_option', '');
        $saved_dpd_password = get_option('explm_dpd_password_option', '');
        if (!empty($saved_dpd_username) && !empty($saved_dpd_password)) {
            $actions['explm_dpd_print_label'] = esc_html__('DPD Print Label', 'express-label-maker');
        }
        return $actions;
    }

    public function explm_add_overseas_print_label_bulk_action($actions)
    {
        $saved_api_key = get_option('explm_overseas_api_key_option', '');
        if (!empty($saved_api_key)) {
            $actions['explm_overseas_print_label'] = esc_html__('Overseas Print Label', 'express-label-maker');
        }
        return $actions;
    }

    public function explm_add_hp_print_label_bulk_action($actions)
    {
        $saved_hp_username = get_option('explm_hp_username_option', '');
        $saved_hp_password = get_option('explm_hp_password_option', '');
        if (!empty($saved_hp_username) && !empty($saved_hp_password)) {
            $actions['explm_hp_print_label'] = esc_html__('HP Print Label', 'express-label-maker');
        }
        return $actions;
    }

        public function explm_add_gls_print_label_bulk_action($actions)
    {
        $saved_gls_username = get_option('explm_gls_username_option', '');
        $saved_gls_password = get_option('explm_gls_password_option', '');
        $saved_gls_client_number = get_option('explm_gls_client_number_option', '');
        if (!empty($saved_gls_username) && !empty($saved_gls_password) && !empty($saved_gls_client_number)) {
            $actions['explm_gls_print_label'] = esc_html__('GLS Print Label', 'express-label-maker');
        }
        return $actions;
    }

    public function explm_display_custom_meta_box_content($post_or_order_object) {
        $order = is_a($post_or_order_object, 'WC_Order') ? $post_or_order_object : wc_get_order($post_or_order_object->ID);
        
        if (!$order) {
            return;
        }
    
        $order_id = $order->get_id();
        $courier_icons = $this->couriers->get_courier_icons();
    
        // Get meta using order object methods (HPOS-compatible)
        $pdf_routes = $order->get_meta('explm_route_labels');
        $pdf_urls = $pdf_routes ? explode(',', $pdf_routes) : [];
    
        $saved_country = get_option("explm_country_option", '');
        $saved_dpd_username = get_option('explm_dpd_username_option', '');
        $saved_dpd_password = get_option('explm_dpd_password_option', '');
        $dpd_condition = !empty($saved_dpd_username) && !empty($saved_dpd_password);
    
        // DPD parcel link generation
        $meta_key_dpd = $saved_country . '_dpd_parcels';
        $dpd_parcels_value = $order->get_meta($meta_key_dpd);
        $dpd_parcel_link = null;
        
        if ($dpd_parcels_value && $dpd_condition) {
            $pl_number_parts = array_filter(explode(',', $dpd_parcels_value));
            $first_value = trim(end($pl_number_parts));
            if ($first_value) {
                $dpd_parcel_link = 'https://www.dpdgroup.com/' . esc_attr($saved_country) . '/mydpd/my-parcels/incoming?parcelNumber=' . esc_attr($first_value);
            }
        }
    
        // Overseas parcel link generation
        $saved_api_key = get_option('explm_overseas_api_key_option', '');
        $meta_key_overseas = $saved_country . '_overseas_parcels';
        $cargo_id = $order->get_meta('overseas_cargo_id');
        $overseas_parcel_link = null;
        $overseas_condition = !empty($saved_api_key);

        if ($cargo_id && $saved_api_key) {
            $overseas_parcel_link = 'https://is.overseas.hr/tracking/?trackingid=' . esc_attr($cargo_id);
        }

        // HP parcel link generation
        $saved_hp_username = get_option('explm_hp_username_option', '');
        $saved_hp_password = get_option('explm_hp_password_option', '');
        $hp_condition = !empty($saved_hp_username) && !empty($saved_hp_password);
        $meta_key_hp = $saved_country . '_hp_parcels';
        $hp_parcels_value = $order->get_meta($meta_key_hp);
        $hp_parcel_link = null;
        
        if ($hp_parcels_value && $hp_condition) {
            $pl_number_parts = array_filter(explode(',', $hp_parcels_value));
            $first_value = trim(end($pl_number_parts));
            if ($first_value) {
                $hp_parcel_link = 'https://posiljka.posta.hr/' . esc_attr($saved_country) . '/tracking/trackingdata?barcode=' . esc_attr($first_value);
            }
        }

        // GLS parcel link generation
        $saved_gls_username = get_option('explm_gls_username_option', '');
        $saved_gls_password = get_option('explm_gls_password_option', '');
        $saved_gls_client_number = get_option('explm_gls_client_number_option', '');
        $gls_condition = !empty($saved_gls_username) && !empty($saved_gls_password) && !empty($saved_gls_client_number);
        $meta_key_gls = $saved_country . '_gls_parcels';
        $gls_parcels_value = $order->get_meta($meta_key_gls);
        $gls_parcel_link = null;
        
        if ($gls_parcels_value && $gls_condition) {
                    $pl_number_parts = array_filter(explode(',', $gls_parcels_value));
                    $first_value = trim(end($pl_number_parts));
                    if ($first_value) { //ISPRAVITI ZA DRUGE DRŽAVE
                        $gls_parcel_link = 'https://gls-group.com/' . esc_attr(strtoupper($saved_country)) . '/' . esc_attr($saved_country) . '/pracenje-posiljke/?match=' . esc_attr($first_value);
                    }
                }
        ?>
    
        <div class="explm_custom_order_metabox_content">
            <h4 class="explm-custom-order-metabox-title"><?php echo esc_html__('Print label', 'express-label-maker'); ?></h4>
            <div class="explm-custom-order-wrapper">
                <?php foreach ($courier_icons as $courier => $icon): ?>
                    <div class="explm-icon-container">
                        <a href="#" class="explm-open-modal button button-primary explm-open-modal-order" 
                           data-order-id="<?php echo esc_attr($order_id); ?>" 
                           data-courier="<?php echo esc_attr($courier); ?>">
                            <?php echo esc_html($icon['button_text']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
    
            <?php if (!empty(array_filter($pdf_urls))) : ?>
                <div class="explm-custom-order-pdf-wrapper">
                    <h4 class="explm-custom-order-metabox-title"><?php echo esc_html__('Labels', 'express-label-maker'); ?></h4>
                    <?php foreach ($pdf_urls as $pdf_url): ?>
                        <?php if (!empty(trim($pdf_url))) : ?>
                            <a href="<?php echo esc_url(trim($pdf_url)); ?>" target="_blank" class="explm_pdf_link">
                                <?php echo esc_html(basename($pdf_url)); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    
            <div class="explm-custom-order-buttons">
                <?php if ($dpd_parcel_link || $overseas_parcel_link || $hp_parcel_link || $gls_parcel_link): ?>
                    <h4 class="explm-custom-order-metabox-title"><?php echo esc_html__('Stack and trace', 'express-label-maker'); ?></h4>
                    <?php if ($dpd_parcel_link): ?>
                        <div class="explm_stack_and_trace_button">
                            <a href="<?php echo esc_url($dpd_parcel_link); ?>" target="_blank" class="button button-secondary">
                                <?php echo esc_html__('DPD Stack and trace', 'express-label-maker'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($overseas_parcel_link): ?>
                        <div class="explm_stack_and_trace_button">
                            <a href="<?php echo esc_url($overseas_parcel_link); ?>" target="_blank" class="button button-secondary">
                                <?php echo esc_html__('Overseas Stack and Trace', 'express-label-maker'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($hp_parcel_link): ?>
                        <div class="explm_stack_and_trace_button">
                            <a href="<?php echo esc_url($hp_parcel_link); ?>" target="_blank" class="button button-secondary">
                                <?php echo esc_html__('HP Stack and Trace', 'express-label-maker'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($gls_parcel_link): ?>
                        <div class="explm_stack_and_trace_button">
                            <a href="<?php echo esc_url($gls_parcel_link); ?>" target="_blank" class="button button-secondary">
                                <?php echo esc_html__('GLS Stack and Trace', 'express-label-maker'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($dpd_condition || $overseas_condition || $hp_condition || $gls_condition): ?>
                        <h4 class="explm-custom-order-metabox-title">
                            <?php echo esc_html__('Collection request', 'express-label-maker'); ?>
                        </h4>
                        <div class="explm_collection_request_buttons">
                            <?php if ($dpd_condition): ?>
                                <button
                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                    data-courier="dpd"
                                    id="explm_collection_request_dpd"
                                    class="button button-primary explm_collection_request_btn">
                                    <?php echo esc_html__('DPD Collection request', 'express-label-maker'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($overseas_condition): ?>
                                <button
                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                    data-courier="overseas"
                                    id="explm_collection_request_overseas"
                                    class="button button-primary explm_collection_request_btn">
                                    <?php echo esc_html__('Overseas Collection request', 'express-label-maker'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($hp_condition): ?>
                                <button
                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                    data-courier="hp"
                                    id="explm_collection_request_hp"
                                    class="button button-primary explm_collection_request_btn">
                                    <?php echo esc_html__('HP Collection request', 'express-label-maker'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($gls_condition): ?>
                                <button
                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                    data-courier="gls"
                                    id="explm_collection_request_gls"
                                    class="button button-primary explm_collection_request_btn">
                                    <?php echo esc_html__('GLS Collection request', 'express-label-maker'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function explm_add_status_column_header($columns) {
        $new_columns = array();
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
        }
        $new_columns['explm_parcel_status'] = esc_html__('Package status', 'express-label-maker');
        return $new_columns;
    }

    public function explm_order_column_content($order) {
        $order_id = $order->get_id();

        if (!$order_id) {
            return;
        }
    
        $courier_icons = $this->couriers->get_courier_icons();

        foreach ($courier_icons as $courier => $icon) {
            $icon_url = plugin_dir_url(__FILE__) . $icon['url'];
            echo '<a href="#" class="explm-open-modal button" data-order-id="' . esc_attr($order_id) . '" data-courier="' . esc_attr($courier) . '"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($icon['alt']) . '" class="explm-' . esc_attr($courier) . '-action-icon" /></a>';
        }
    }

    public function display_custom_order_meta_data($column, $order) {

        if (!is_a($order, 'WC_Order')) {
            $order = wc_get_order(is_numeric($order) ? $order : get_the_ID());
        }
        if (!$order) return;

        if ($column !== 'explm_parcel_status') return;

        $status = (string) $order->get_meta('explm_parcel_status');
        $color  = (string) $order->get_meta('explm_parcel_status_color');
        $date   = (string) $order->get_meta('explm_parcel_status_date');

        if ($status === '' && $color === '') { 
            echo esc_html( __('Status not available', 'express-label-maker') );
            return;
        }

        $title = $status . ($date ? ' (' . $date . ')' : '');

        if (function_exists('mb_strlen') && mb_strlen($status) > 30) {
            $display = mb_substr($status, 0, 30) . '...';
        } else if (strlen($status) > 30) {
            $display = substr($status, 0, 30) . '...';
        } else {
            $display = $status;
        }

        $style = $color !== '' 
            ? 'background:' . esc_attr($color) . ';padding:2px 6px;border-radius:3px;display:inline-block'
            : '';

        echo '<span class="explm-package-status order-status" title="' . esc_attr($title) . '" style="' . $style . '">' . esc_html($display) . '</span>';
    }


    public function explm_show_confirm_modal() {
        check_ajax_referer('explm_nonce', 'security');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $courier = isset($_POST['courier']) ? sanitize_text_field(wp_unslash($_POST['courier'])) : '';

        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            $billing = $order_data['billing'];
            $shipping = $order_data['shipping'];
            $order_date = $order_data['date_created']->date('Y-m-d');
            $payment_method = $order->get_payment_method();
            $order_total = $order->get_total();
            $package_number = 1;

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

            ob_start();
            include('forms/express-label-maker-form.php');
            $output = ob_get_clean();
            wp_send_json_success($output);
        } else {
            wp_send_json_error(__('Order not found.', 'express-label-maker'));
        }
        wp_die();
    }

    public function explm_show_collection_modal() {
        check_ajax_referer('explm_nonce', 'security');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $courier  = isset($_POST['courier']) ? sanitize_text_field($_POST['courier']) : '';

        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            $billing = $order_data['billing'];
            $shipping = $order_data['shipping'];
            $order_date = $order_data['date_created']->date('Y-m-d');
            $payment_method = $order->get_payment_method();
            $order_total = $order->get_total();
            $package_number = 1;

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

            ob_start();
            switch ($courier) {
            case 'dpd':
                include plugin_dir_path(__FILE__) . 'forms/express-label-maker-collection-form-dpd.php';
                break;

            case 'overseas':
                include plugin_dir_path(__FILE__) . 'forms/express-label-maker-collection-form-overseas.php';
                break;

            case 'hp':
                include plugin_dir_path(__FILE__) . 'forms/express-label-maker-collection-form-hp.php';
                break;

            case 'gls':
                include plugin_dir_path(__FILE__) . 'forms/express-label-maker-collection-form-gls.php';
                break;
        }
            $output = ob_get_clean();
            wp_send_json_success($output);
        } else {
            wp_send_json_error(__('Order not found.', 'express-label-maker'));
        }
        wp_die();
    }

    public static function get_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Order not found - ID ' . $order_id);
            }
            return false;
        }
        
        return $order;
    }

    public static function update_order_meta($order_id, $meta_key, $meta_value) {
        $order = self::get_order($order_id);
        if (!$order) return false;

        $order->update_meta_data($meta_key, $meta_value);
        return (bool) $order->save();
    }

    public static function get_order_meta($order_id, $meta_key, $single = true) {
        $order = self::get_order($order_id);
        return $order ? $order->get_meta($meta_key, $single) : false;
    }

    public static function get_plugin_version() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    
        $plugin_file = plugin_dir_path(__FILE__) . 'express-label-maker.php';
        $plugin_data = get_plugin_data($plugin_file);
    
        return isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    }
    
}

function explm_initialize_express_label_maker(): void {
    if (get_transient('explm_force_update_parcelshops')) {
        delete_transient('explm_force_update_parcelshops');
        $locker_handler = new ExplmParcelLockers();
        $locker_handler->explm_update_hp_parcelshops_cron_callback();
        $locker_handler->explm_update_dpd_parcelshops_cron_callback();
        $locker_handler->explm_update_overseas_parcelshops_cron_callback();
        $locker_handler->explm_update_gls_parcelshops_cron_callback();
    }
    new ExplmLabelMaker();
}
add_action('plugins_loaded', 'explm_initialize_express_label_maker');

// Prikaz parcel shop info u adminu ispod adrese dostave
add_action('woocommerce_admin_order_data_after_shipping_address', function($order){
    $locker = $order->get_meta('parcel_locker_formatted');
    if ($locker) {
        echo '<p><strong>' . esc_html__('Parcel Locker:', 'express-label-maker') . '</strong> ' . esc_html($locker) . '</p>';
    }
}, 10, 1);

// Prikaz parcel shop info u mailu
add_filter('woocommerce_email_order_meta_fields', function($fields, $sent_to_admin, $order) {
    $locker = $order->get_meta('parcel_locker_formatted');
    if ($locker) {
        $fields['parcel_locker'] = array(
            'label' => __('Parcel Locker', 'express-label-maker'),
            'value' => $locker,
        );
    }
    return $fields;
}, 10, 3);

// Prikaz parcel shop info na thankyou
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $locker = $order->get_meta('parcel_locker_formatted');
    if ($locker) {
        echo '<p><strong>' . esc_html__('Parcel Locker:', 'express-label-maker') . '</strong> ' . esc_html($locker) . '</p>';
    }
}, 20);

add_action('before_woocommerce_init', static function() {
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

register_activation_hook(__FILE__, static function() {
    set_transient('explm_force_update_parcelshops', true, 60);
});

register_deactivation_hook(__FILE__, static function() {
    foreach ([
        'explm_update_overseas_parcelshops_cron',
        'explm_update_dpd_parcelshops_cron',
        'explm_update_hp_parcelshops_cron',
        'explm_update_gls_parcelshops_cron'
    ] as $hook) {
        wp_clear_scheduled_hook($hook);
    }
});