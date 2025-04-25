<?php

/**
 * Plugin Name: Express Label Maker
 * Plugin URI: https://expresslabelmaker.com/
 * Description: Print shipping labels and track parcels for multiple couriers directly from WooCommerce.
 * Tags: woocommerce, shipping, label printing, DPD, Overseas
 * Version: 1.25115
 * Author: expresslabelmaker
 * Tested up to: 6.8
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once 'settings/general-settings.php';
require_once 'settings/licence-settings.php';
require_once 'settings/dpd-settings.php';
require_once 'settings/overseas-settings.php';
require_once 'settings/user-data.php';
require_once 'settings/licence.php';
require_once 'settings/cron.php';
require_once 'couriers/print-label.php';
require_once 'couriers/print-labels.php';
require_once 'couriers/all-couriers.php';
require_once 'couriers/user-status-data.php';
require_once 'couriers/parcel-statuses.php';
require_once 'couriers/collection-request.php';
require_once 'couriers/parcel-lockers.php';

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
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'display_custom_order_meta_data'], 20, 2);
        add_action('add_meta_boxes_woocommerce_page_wc-orders', array($this, 'explm_add_custom_meta_box'));
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'explm_add_status_column_header'), 20);
        // Legacy mode
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_dpd_print_label_bulk_action'));
        add_filter('bulk_actions-edit-shop_order', array($this, 'explm_add_overseas_print_label_bulk_action'));
        add_action('manage_shop_order_posts_custom_column', [$this, 'display_custom_order_meta_data'], 20, 2);
        add_action('add_meta_boxes', array($this, 'explm_add_custom_meta_box'));
        add_filter('manage_edit-shop_order_columns', array($this, 'explm_add_status_column_header'), 20);
        
        $this->couriers = new ExpmlCouriers();
    }

    public function explm_enqueue_scripts($hook)
    {
        wp_enqueue_style('explm_admin_css', plugin_dir_url(__FILE__) . 'css/elm.css', array(), '1.0.1');
        wp_enqueue_script('explm_admin_js', plugin_dir_url(__FILE__) . 'js/elm.js', array('jquery'), '1.0.1', true);
    
        $email = get_option('explm_email_option', '');
        $licence = get_option('explm_licence_option', '');
        $saved_service_type = get_option('explm_dpd_service_type_option', '');
    
        wp_localize_script(
            'explm_admin_js',
            'explm_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('explm_nonce'),
                'email' => $email,
                'licence' => $licence,
                'serviceType' => $saved_service_type,
                'savedLabelTime' => esc_html__('%1$d minutes of your life just came back. That’s %2$d h and %3$d min you didn’t spend typing shipping labels. Your keyboard thanks you.', 'express-label-maker')
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
        echo '</nav>';

        if ($tab == 'licence') {
            explm_licence_tab_content();
        } else if ($tab == 'settings') {
            explm_settings_tab_content();
        } elseif ($tab == 'dpd') {
            explm_dpd_tab_content();
        } elseif ($tab == 'overseas') {
            explm_overseas_tab_content();
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
        $overseas_parcels_value = $order->get_meta($meta_key_overseas);
        $overseas_parcel_link = null;
        
        if ($overseas_parcels_value && $saved_api_key) {
            $pl_number_parts = array_filter(explode(',', $overseas_parcels_value));
            $first_value = trim(end($pl_number_parts));
            if ($first_value) {
                $overseas_parcel_link = 'https://is.overseas.hr/tracking/?trackingid=' . esc_attr($first_value);
            }
        }
        ?>
    
        <div class="explm_custom_order_metabox_content">
            <h4 class="explm_custom_order_metabox_title"><?php echo esc_html__('Print label', 'express-label-maker'); ?></h4>
            <div class="explm_custom_order_wrapper">
                <?php foreach ($courier_icons as $courier => $icon): ?>
                    <div class="explm_icon_container">
                        <a href="#" class="explm_open_modal button button-primary explm_open_modal_order" 
                           data-order-id="<?php echo esc_attr($order_id); ?>" 
                           data-courier="<?php echo esc_attr($courier); ?>">
                            <?php echo esc_html($icon['button_text']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
    
            <?php if (!empty(array_filter($pdf_urls))) : ?>
                <div class="explm_custom_order_pdf_wrapper">
                    <h4 class="explm_custom_order_metabox_title"><?php echo esc_html__('Labels', 'express-label-maker'); ?></h4>
                    <?php foreach ($pdf_urls as $pdf_url): ?>
                        <?php if (!empty(trim($pdf_url))) : ?>
                            <a href="<?php echo esc_url(trim($pdf_url)); ?>" target="_blank" class="explm_pdf_link">
                                <?php echo esc_html(basename($pdf_url)); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    
            <div class="explm_custom_order_buttons">
                <?php if ($dpd_parcel_link || $overseas_parcel_link): ?>
                    <h4 class="explm_custom_order_metabox_title"><?php echo esc_html__('Stack and trace', 'express-label-maker'); ?></h4>
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
                    <?php if ($dpd_condition): ?>
                        <h4 class="explm_custom_order_metabox_title"><?php echo esc_html__('Collection request', 'express-label-maker'); ?></h4>
                        <div class="explm_collection_request_button">
                            <button data-order-id="<?php echo esc_attr($order_id); ?>" id="explm_collection_request" class="button button-primary">
                                <?php echo esc_html__('DPD Collection request', 'express-label-maker'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
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
            echo '<a href="#" class="explm_open_modal button" data-order-id="' . esc_attr($order_id) . '" data-courier="' . esc_attr($courier) . '"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($icon['alt']) . '" class="' . esc_attr($courier) . '-action-icon" /></a>';
        }
    }

    public function display_custom_order_meta_data($column, $order) {
        // For backward compatibility, handle cases where $order might not be passed
        if (!is_a($order, 'WC_Order')) {
            global $post;
            if (!$post) {
                return;
            }
            $order = wc_get_order($post->ID);
        }
    
        if (!$order) {
            return;
        }
    
        if ($column === 'explm_parcel_status') {
            $custom_meta_data = $order->get_meta('explm_parcel_status');
            if (empty($custom_meta_data)) {
                return;
            }
            
            if (strlen($custom_meta_data) > 30) {
                echo '<span title="' . esc_attr($custom_meta_data) . '">' . esc_html(substr($custom_meta_data, 0, 30)) . '...</span>';
            } else {
                echo '<span title="' . esc_attr($custom_meta_data) . '">' . esc_html($custom_meta_data) . '</span>';
            }
        }
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
            $weight = 2;
            $package_number = 1;

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

        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            $billing = $order_data['billing'];
            $shipping = $order_data['shipping'];
            $order_date = $order_data['date_created']->date('Y-m-d');
            $payment_method = $order->get_payment_method();
            $order_total = $order->get_total();
            $weight = 2;
            $package_number = 1;

            preg_match('/\d[\w\s-]*$/', $shipping['address_1'], $house_number);
            $house_number = isset($house_number[0]) ? $house_number[0] : '';

            $address_without_house_number = preg_replace('/\d[\w\s-]*$/', '', $shipping['address_1']);

            ob_start();
            include('forms/express-label-maker-collection-form.php');
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
    
}

function explm_initialize_express_label_maker() {
    new ExplmLabelMaker();
}
add_action('plugins_loaded', 'explm_initialize_express_label_maker');

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});