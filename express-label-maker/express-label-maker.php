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

require_once 'settings/general-settings.php';
require_once 'settings/dpd-settings.php';
require_once 'settings/user-data.php';
require_once 'couriers/print-label.php';
require_once 'couriers/all-couriers.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExpressLabelMaker
{
    protected $couriers;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'elm_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        add_action('woocommerce_admin_order_actions_end', array($this, 'elm_order_column_content'));
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'elm_add_icon_to_order_data_column'));
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'elm_add_pdf_to_order_column'));
        add_action('wp_ajax_elm_show_confirm_modal', array($this, 'elm_show_confirm_modal'));
        $this->couriers = new Couriers();
    }

    public function elm_enqueue_scripts($hook)
    {
        /* $on_shop_order_page = (('edit.php' === $hook && isset($_GET['post_type']) && 'shop_order' === $_GET['post_type']) ||
            ('post.php' === $hook && isset($_GET['post']) && 'shop_order' === get_post_type($_GET['post'])));

        $on_plugin_page = strpos($hook, 'express_label_maker') !== false;

        if (!$on_shop_order_page && !$on_plugin_page) {
            return;
        } */
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


    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('express-label-maker', false, basename(dirname(__FILE__)) . '/languages/');
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
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
    
        echo '<div class="wrap">';
        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
        echo '<a href="?page=express_label_maker&tab=settings" class="nav-tab ' . ($tab == 'settings' ? 'nav-tab-active' : '') . '">' . __('Settings', 'express-label-maker') . '</a>';
        echo '<a href="?page=express_label_maker&tab=dpd" class="nav-tab ' . ($tab == 'dpd' ? 'nav-tab-active' : '') . '">' . __('DPD', 'express-label-maker') . '</a>';
        echo '</nav>';
    
        if ($tab == 'settings') {
            settings_tab_content();
        } elseif ($tab == 'dpd') {
            dpd_tab_content();
        }
    
        echo '</div>';
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

    public function elm_add_icon_to_order_data_column($order) {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        
        $courier_icons = $this->couriers->get_courier_icons();
    ?>
        <script>
            jQuery(document).ready(function($) {
                <?php foreach ($courier_icons as $courier => $icon) :
                    $icon_url = plugin_dir_url(__FILE__) . $icon['url'];
                ?>
                    var iconContainer = $('<div class="elm_icon_container" style="margin-top: 20px;"><a href="#" class="elm_open_modal" data-order-id="<?php echo esc_attr($order_id); ?>" data-courier="<?php echo $courier; ?>"><img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" style="max-width: 30px; height: auto; cursor: pointer;" /></a></div>');
                    iconContainer.appendTo('.order_data_column:last-child');
                <?php endforeach; ?>
            });
        </script>
    <?php
    }

    public function elm_add_pdf_to_order_column($order) {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $courier_icons = $this->couriers->get_courier_icons();
        
        $upload_dir = wp_upload_dir();
        $dir_path = $upload_dir['basedir'];
        $url_base = $upload_dir['baseurl'];
    
        $meta_keys = get_post_custom_keys($order_id);
        $relevant_meta_key = null;
        foreach ($meta_keys as $key) {
            if (strpos($key, '_adresnica') !== false) {
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
    
        ?>
        <script>
            jQuery(document).ready(function($) {
                var pdfContainer = $('<div class="elm_pdf_container"></div>');
                
                <?php
                foreach ($courier_icons as $courier => $icon) :
                foreach ($labels as $label) :
                    $file_pattern = $courier . "-*" . $label . "*.pdf"; 
                    $matching_files = glob($dir_path . '/*/*/' . $file_pattern);
                    foreach ($matching_files as $file_path) {
                        $actual_file_name = basename($file_path);
                        $file_url = $url_base . '/' . substr($file_path, strlen($dir_path) + 1);
                    ?>
                        if(pdfContainer.find('a[href="<?php echo esc_url($file_url); ?>"]').length === 0) {
                            var labelLink = $('<a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($actual_file_name); ?></a>');
                            labelLink.appendTo(pdfContainer);
                        }
                    <?php } ?>
                <?php endforeach; endforeach;?>
    
                pdfContainer.appendTo('.order_data_column:last-child');
            });
        </script>
        <?php
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
}

new ExpressLabelMaker();
