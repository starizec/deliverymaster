<?php

/**
 * Plugin Name: Express Label Maker
 * Plugin URI: https://expresslabelmaker.com/
 * Description: Turn your WordPress into a central shipping station with the 'Express Label Maker' plugin. Making stickers has never been easier!
 * Tags: delivery, label, dpd
 * Version: 1.0.0
 * Author: Emedia
 * Author URI: https://emedia.hr/
 * Tested up to: 6.2.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once 'settings/general-settings.php';
require_once 'settings/licence-settings.php';
require_once 'settings/dpd-settings.php';
require_once 'settings/user-data.php';
require_once 'settings/licence.php';
require_once 'couriers/print-label.php';
require_once 'couriers/print-labels.php';
require_once 'couriers/all-couriers.php';
require_once 'couriers/user-status-data.php';
require_once 'couriers/parcel-statuses.php';
require_once 'couriers/collection-request.php';

class ExpressLabelMaker
{
    protected $couriers;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'elm_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('woocommerce_admin_order_actions_end', array($this, 'elm_order_column_content'));
        /* add_action('woocommerce_admin_order_data_after_order_details', array($this, 'elm_add_icon_to_order_data_column')); */
        /* add_action('woocommerce_admin_order_data_after_order_details', array($this, 'elm_add_pdf_to_order_column')); */
        add_action('wp_ajax_elm_show_confirm_modal', array($this, 'elm_show_confirm_modal'));
        add_action('wp_ajax_elm_show_collection_modal', array($this, 'elm_show_collection_modal'));
        add_filter('manage_edit-shop_order_columns', array($this, 'elm_add_status_column_header'), 20);
        add_filter('bulk_actions-edit-shop_order', array($this, 'elm_add_dpd_print_label_bulk_action'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_custom_order_meta_data'));
        add_action('add_meta_boxes', array($this, 'elm_add_custom_meta_box'));
        add_action('plugins_loaded', array($this, 'elm_plugin_load_textdomain'), 30);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'elm_add_plugin_settings_link'));
        $this->couriers = new Couriers();
    }

    public function elm_enqueue_scripts($hook)
    {
        wp_enqueue_style('elm_admin_css', plugin_dir_url(__FILE__) . 'css/elm.css');
        wp_enqueue_script('elm_admin_js', plugin_dir_url(__FILE__) . 'js/elm.js', array('jquery'), null, true);
        wp_localize_script(
            'elm_admin_js',
            'elm_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('elm_nonce'),
            )
        );
    }

    public function elm_plugin_load_textdomain() {
        load_plugin_textdomain('express-label-maker', false, plugin_basename(dirname(__FILE__)) . '/languages/');
    }    

    public function elm_add_plugin_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=express_label_maker') . '">' . __('Settings', 'express-label-maker') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }    

    public function add_submenu_page()
    {
        add_submenu_page(
            'woocommerce',
            __('Express Label Maker', 'express-label-maker'),
            __('Express Label Maker', 'express-label-maker'),
            'manage_woocommerce',
            'express_label_maker',
            array($this, 'submenu_page_content')
        );
    }

    public function submenu_page_content() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'licence';
    
        echo '<div class="wrap">';
        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
        echo '<a href="?page=express_label_maker&tab=licence" class="nav-tab ' . ($tab == 'licence' ? 'nav-tab-active' : '') . '">' . __('Licence', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=settings" class="nav-tab ' . ($tab == 'settings' ? 'nav-tab-active' : '') . '">' . __('Settings', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=dpd" class="nav-tab ' . ($tab == 'dpd' ? 'nav-tab-active' : '') . '">' . __('DPD', 'express-label-maker') . '</a>';
        echo '</nav>';
    
        if ($tab == 'licence') {
            licence_tab_content();
        } else if ($tab == 'settings') {
            settings_tab_content();
        } elseif ($tab == 'dpd') {
            dpd_tab_content();
        }
    
        echo '</div>';
    }

    public function elm_add_dpd_print_label_bulk_action($actions)
    {
        $actions['elm_dpd_print_label'] = __('DPD Print Label', 'textdomain');
        return $actions;
    }

    public function elm_add_status_column_header($columns) {
        $new_columns = array();
    
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
        }
        
        $new_columns['elm_parcel_status'] = __('Package status', 'express-label-maker');
        
        return $new_columns;
    }

    public function elm_add_custom_meta_box() {
        add_meta_box(
            'elm_custom_order_metabox',              // Unique ID
            __('Express Label Maker', 'express-label-maker'),  // Title
            array($this, 'elm_display_custom_meta_box_content'), // Callback function
            'shop_order',                           // Admin page (or post type)
            'side',                                 // Context (normal, advanced, or side)
            'default'                               // Priority
        );
    }
    
    public function elm_display_custom_meta_box_content($post) {
        $order = wc_get_order($post->ID);
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $courier_icons = $this->couriers->get_courier_icons();
    
        // Get the PDF URLs for this order
        $pdf_routes = get_post_meta($order_id, 'elm_route_labels', true);
        $pdf_urls = explode(',', $pdf_routes);
        
        ?>
        <div class="elm_custom_order_metabox_content">
            <h4 class="elm_custom_order_metabox_title"><?php echo __('Print label', 'express-label-maker'); ?></h4>
            <div class="elm_custom_order_wrapper">
                <?php foreach ($courier_icons as $courier => $icon) :
                    $icon_url = plugin_dir_url(__FILE__) . $icon['url'];
                ?>
                    <div class="elm_icon_container">
                        <a href="#" class="elm_open_modal" data-order-id="<?php echo esc_attr($order_id); ?>" data-courier="<?php echo $courier; ?>">
                            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($icon['alt'], 'express-label-maker'); ?>" style="max-width: 30px; height: auto; cursor: pointer;" />
                        </a>
                    </div>
                <?php endforeach; ?>
                <div class="elm_collection_request_button">
                    <button data-order-id="<?php echo esc_attr($order_id); ?>" id="elm_collection_request" class="button button-primary"><?php echo __('Collection request', 'express-label-maker'); ?></button>
                </div>
            </div>
            <div class="elm_custom_order_pdf_wrapper">
            <h4 class="elm_custom_order_metabox_title"><?php echo __('Labels', 'express-label-maker'); ?></h4>
                <?php foreach ($pdf_urls as $pdf_url): ?>
                    <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="elm_pdf_link"><?php echo __(basename($pdf_url)); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }    
        

    public function elm_order_column_content($order)
    {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

        $courier_icons = $this->couriers->get_courier_icons();

        foreach ($courier_icons as $courier => $icon) {
            $icon_url = plugin_dir_url(__FILE__) . $icon['url'];
            echo '<a href="#" class="elm_open_modal button" data-order-id="' . esc_attr($order_id) . '" data-courier="' . $courier . '"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($icon['alt'], 'express-label-maker') . '" class="' . esc_attr($courier) . '-action-icon" /></a>';
        }
    }

/*     public function elm_add_icon_to_order_data_column($order) {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        
        $courier_icons = $this->couriers->get_courier_icons();
    ?>
        <script>
            jQuery(document).ready(function($) {
                <?php foreach ($courier_icons as $courier => $icon) :
                    $icon_url = plugin_dir_url(__FILE__) . $icon['url'];
                ?>
                    var iconContainer = $('<div class="elm_icon_container" style="margin-top: 20px;"><a href="#" class="elm_open_modal" data-order-id="<?php echo esc_attr($order_id); ?>" data-courier="<?php echo $courier; ?>"><img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" style="max-width: 30px; height: auto; cursor: pointer;" /></a></div>');
                    iconContainer.appendTo('.order_data_column:first-child');
                <?php endforeach; ?>
            });
        </script>
    <?php
    } */

   /*  public function elm_add_pdf_to_order_column($order) {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $courier_icons = $this->couriers->get_courier_icons();
        
        $upload_dir = wp_upload_dir();
        $dir_path = $upload_dir['basedir'];
        $url_base = $upload_dir['baseurl'];
    
        $meta_keys = get_post_custom_keys($order_id);
        $relevant_meta_key = null;
        foreach ($meta_keys as $key) {
            if (strpos($key, '_parcels') !== false) {
                $relevant_meta_key = $key;
                break;
            }
        }
    
        if ($relevant_meta_key) {
            $adresnica_values = get_post_meta($order_id, $relevant_meta_key, true);
            $labels = explode(',', $adresnica_values);
        } else {
            $labels = array();
        }
    
        $pdf_routes = get_post_meta($order_id, 'elm_route_labels', true);
        $pdf_urls = explode(',', $pdf_routes);
    
        ?>
        <script>
            jQuery(document).ready(function($) {
                var pdfContainer = $('<div class="elm_pdf_container"></div>');
    
                <?php foreach ($pdf_urls as $pdf_url): ?>
                    if (pdfContainer.find('a[href="<?php echo esc_url($pdf_url); ?>"]').length === 0) {
                        var pdfLink = $('<a href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php echo (basename($pdf_url)); ?></a>');
                        pdfLink.appendTo(pdfContainer);
                    }
                <?php endforeach; ?>
    
                pdfContainer.appendTo('.order_data_column:first-child');
            });
        </script>
        <?php
    }
 */
    function display_custom_order_meta_data($column)
    {
        global $post;

        if ($column === 'elm_parcel_status') {
            $order = wc_get_order($post->ID);
            $custom_meta_data = $order->get_meta('elm_parcel_status');
            echo '<span>' . $custom_meta_data . '</span>';
        }
    }
    

    public function elm_show_confirm_modal()
    {
        check_ajax_referer('elm_nonce', 'security');

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

            include('forms/express-label-maker-form.php');
        ?>
        
<?php

            $output = ob_get_clean();
            wp_send_json_success($output);
        } else {
            wp_send_json_error( __( 'Order not found.', 'express-label-maker' ) );
        }
        wp_die();
    }

    public function elm_show_collection_modal()
    {
        check_ajax_referer('elm_nonce', 'security');

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
        ?>
        
<?php

            $output = ob_get_clean();
            wp_send_json_success($output);
        } else {
            wp_send_json_error( __( 'Order not found.', 'express-label-maker' ) );
        }
        wp_die();
    }
}

function elm_initialize_express_label_maker() {
    new ExpressLabelMaker();
}
add_action('plugins_loaded', 'elm_initialize_express_label_maker');