<?php

/**
 * Plugin Name: Express Label Maker
 * Plugin URI: https://expresslabelmaker.com/
 * Description: A custom WooCommerce delivery plugin that will make your life easier.
 * Tags: delivery, label, dpd
 * Version: 1.0.0
 * Author: Emedia
 * Author URI: https://emedia.hr/
 * Tested up to: 6.2.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('DeliveryMaster')) {

    class DeliveryMaster
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'dm_add_options_page'));
            add_action('admin_init', array($this, 'dm_register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'dm_enqueue_scripts'));
            add_action('woocommerce_admin_order_actions_end', array($this, 'dm_order_column_content'));
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'dm_add_icon_to_order_data_column'));
            add_action('plugins_loaded', array($this, 'my_plugin_load_textdomain'));
        }

        public function dm_add_options_page()
        {
            add_submenu_page(
                'woocommerce',
                'DeliveryMaster Settings',
                'DeliveryMaster',
                'manage_options',
                'deliverymaster',
                array($this, 'dm_options_page_content')
            );
        }

        public function my_plugin_load_textdomain()
        {
            $plugin_rel_path = basename(dirname(__FILE__)) . '/languages/';
            $languages_dir = WP_PLUGIN_DIR . '/' . $plugin_rel_path;

            $locale = get_locale();

            $mo_file = $languages_dir . '/' . 'deliverymaster-' . $locale . '.mo';

            if (file_exists($mo_file)) {
                // Učitajte .mo datoteku za trenutni jezik
                return load_textdomain('delivery-master', $mo_file);
            } else {
                return false;
            }
        }

        public function dm_register_settings()
        {
            register_setting('dm_options_group', 'dm_username');
            register_setting('dm_options_group', 'dm_password');
        }

        public function dm_options_page_content()
        {
?>
            <div class="wrap">
                <h1>DeliveryMaster Settings</h1>
                <form id="dm-settings-form" method="post" action="options.php">
                    <?php settings_fields('dm_options_group'); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Username:</th>
                            <td><input type="text" name="dm_username" value="<?php echo esc_attr(get_option('dm_username')); ?>" autocomplete="username" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Password:</th>
                            <td><input type="password" name="dm_password" value="<?php echo esc_attr(get_option('dm_password')); ?>" autocomplete="current-password" /></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php
        }

        public function dm_get_saved_options()
        {
            $options = array(
                'username' => get_option('dm_username'),
                'password' => get_option('dm_password')
            );

            return $options;
        }

        public function dm_enqueue_scripts($hook)
        {
            if ('edit.php' !== $hook || !isset($_GET['post_type']) || 'shop_order' !== $_GET['post_type']) {
                if ('post.php' !== $hook || !isset($_GET['post']) || 'shop_order' !== get_post_type($_GET['post'])) {
                    return;
                }
            }
            wp_enqueue_style('dm_admin_css', plugin_dir_url(__FILE__) . 'css/admin.css');
            wp_enqueue_script('dm_admin_js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '1.0.0', true);

            // Localize the script with your saved options
            $options = $this->dm_get_saved_options();
            wp_localize_script('dm_admin_js', 'dm_options', $options);

            wp_localize_script('dm_admin_js', 'dm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('dm_nonce'),
            ));
        }

        public function dm_add_icon_to_order_data_column($order)
        {
            $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

            // Display the icon
            $icon_url = plugin_dir_url(__FILE__) . 'assets/dpd-logo.png';
        ?>
            <script>
                jQuery(document).ready(function($) {
                    var iconContainer = $('<div class="dm_icon_container" style="margin-top: 20px;"><a href="#" class="dm_open_modal" data-order-id="<?php echo esc_attr($order_id); ?>"><img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr__('Open Modal', 'delivery-master'); ?>" style="max-width: 30px; height: auto; cursor: pointer;" /></a></div>');
                    $('.order_data_column:last-child').append(iconContainer);
                });
            </script>
        <?php
        }


        public function dm_order_column_content($order)
        {
            $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

            $icon_url = plugin_dir_url(__FILE__) . 'assets/dpd-logo.png';
            echo '<a href="#" class="dm_open_modal button" data-order-id="' . esc_attr($order_id) . '"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr__('Open Modal', 'delivery-master') . '" class="dpd-action-icon"" /></a>';
        }
    }

    new DeliveryMaster();
}

function dm_show_confirm_modal()
{
    check_ajax_referer('dm_nonce', 'security');
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
        ?>
        <div class="dm_loading_panel">
            <div class="dm_spinner"></div>
        </div>
        <div class="dm_modal_wrapper">
            <div class="dm_modal">
                <div class="dm_modal_header">
                    <h2 style="margin-top: 0;"><?php esc_html_e('Order Details', 'delivery-master'); ?> #<?php echo esc_attr($order_data['id']); ?></h2>
                    <button class="dm_close_button dm_cancel_action">&times;</button>
                </div>
                <div class="dm-error" style="display: none"></div>
                <form id="dm_order_details_form">
                    <div class="dm_form_columns">
                        <!-- Customer's Name -->
                        <label class="labels"><?php esc_html_e("Customer's Name:", 'delivery-master'); ?>
                            <input type="text" name="customer_name" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                        </label>
                        <!-- Customer's Address -->
                        <label class="labels"><?php esc_html_e("Customer's Address:", 'delivery-master'); ?>
                            <input type="text" name="customer_address" value="<?php echo esc_attr(trim($address_without_house_number)); ?>">
                        </label>
                        <!-- House Number -->
                        <label class="labels"><?php esc_html_e('House Number:', 'delivery-master'); ?>
                            <input type="text" name="house_number" value="<?php echo esc_attr($house_number); ?>">
                        </label>
                        <!-- City -->
                        <label class="labels"><?php esc_html_e('City:', 'delivery-master'); ?>
                            <input type="text" name="city" value="<?php echo esc_attr($shipping['city']); ?>">
                        </label>
                        <!-- ZIP Code -->
                        <label class="labels"><?php esc_html_e('ZIP Code:', 'delivery-master'); ?>
                            <input type="text" name="zip_code" value="<?php echo esc_attr($shipping['postcode']); ?>">
                        </label>
                        <!-- Country -->
                        <label class="labels"><?php esc_html_e('Country:', 'delivery-master'); ?>
                            <input type="text" name="country" value="<?php echo esc_attr($shipping['country']); ?>">
                        </label>
                        <!-- Contact Person -->
                        <label class="labels"><?php esc_html_e('Contact Person:', 'delivery-master'); ?>
                            <input type="text" name="contact_person" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                        </label>
                        <!-- Phone -->
                        <label class="labels"><?php esc_html_e('Phone:', 'delivery-master'); ?>
                            <input type="text" name="phone" value="<?php echo esc_attr($billing['phone']); ?>">
                        </label>
                        <!-- Email -->
                        <label class="labels"><?php esc_html_e('Email:', 'delivery-master'); ?>
                            <input type="email" name="email" value="<?php echo esc_attr($billing['email']); ?>">
                        </label>
                    </div>
                    <div class="dm_form_columns">
                        <!-- Reference -->
                        <label class="labels"><?php esc_html_e('Reference:', 'delivery-master'); ?>
                            <input type="text" name="reference" value="<?php echo esc_attr($order_data['id']); ?>">
                        </label>
                        <!--Payment Method -->
                        <label class="labels"><?php esc_html_e('Payment Method:', 'delivery-master'); ?>
                            <input type="text" name="payment_method" value="<?php echo esc_attr($payment_method); ?>">
                        </label>
                        <!--Payment -->
                        <label class="labels"><?php esc_html_e('Payment:', 'delivery-master'); ?>
                            <div class="payment">
                                <input type="radio" name="parcel_type" value="cod" id="x-cod" <?php $payment_method === 'cod' ? print_r('checked') : '' ?>>
                                <label for="x-cod" style="padding-right:5px;">COD</label>
                                <input type="radio" name="parcel_type" value="classic" id="x-classic" <?php $payment_method != 'cod' ? print_r('checked') : '' ?>>
                                <label for="x-classic">Classic</label>
                            </div>
                        </label>
                        <!-- Collection Date (Order Date) -->
                        <label class="labels"><?php esc_html_e('Collection Date (Order Date):', 'delivery-master'); ?>
                            <input type="date" name="collection_date" value="<?php echo esc_attr($order_date); ?>">
                        </label>
                        <!-- Weight -->
                        <label class="labels"><?php esc_html_e('Weight:', 'delivery-master'); ?>
                            <input type="text" name="weight" value="<?php echo esc_attr($weight); ?>">
                        </label>
                        <!-- Package Number -->
                        <label class="labels"><?php esc_html_e('Package Number:', 'delivery-master'); ?>
                            <input type="text" name="package_number" value="<?php echo esc_attr($package_number); ?>">
                        </label>
                        <!-- Cash on Delivery Amount -->
                        <label class="labels"><?php esc_html_e('Cash on Delivery Amount:', 'delivery-master'); ?>
                            <input type="text" name="cod_amount" value="<?php echo esc_attr($order_total); ?>">
                        </label>
                        <!-- Note -->
                        <label class="labels"><?php esc_html_e('Note:', 'delivery-master'); ?>
                            <textarea name="note"><?php echo esc_textarea($order_data['customer_note']); ?></textarea>
                        </label>
                </form>
                <div class="dm_modal_actions">
                    <button class="button button-primary dm_confirm_action"><?php esc_html_e('Print', 'delivery-master'); ?></button>
                    <button class="button dm_cancel_action"><?php esc_html_e('Cancel', 'delivery-master'); ?></button>
                </div>
            </div>
        </div>
<?php
        $output = ob_get_clean();
        wp_send_json_success($output);
    } else {
        wp_send_json_error('Order not found.');
    }
    wp_die();
}
add_action('wp_ajax_dm_show_confirm_modal', 'dm_show_confirm_modal');

//update adresnice

add_action('wp_ajax_dm_update_adresnica', 'dm_update_adresnica');
add_action('wp_ajax_nopriv_dm_update_adresnica', 'dm_update_adresnica');

function dm_update_adresnica()
{
    check_ajax_referer('dm_nonce', 'security');

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $pl_number = isset($_POST['pl_number']) ? sanitize_text_field($_POST['pl_number']) : '';

    if ($order_id > 0 && !empty($pl_number)) {
        update_post_meta($order_id, 'x_parcel_number', $pl_number);
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid order ID or pl_number.');
    }

    wp_die();
}


add_action('wp_ajax_dm_update_parcel_status', 'dm_update_parcel_status');
add_action('wp_ajax_nopriv_dm_update_parcel_status', 'dm_update_parcel_status');

function dm_update_parcel_status()
{
    check_ajax_referer('dm_nonce', 'security');

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $pl_status = isset($_POST['pl_status']) ? sanitize_text_field($_POST['pl_status']) : '';

    if ($order_id > 0 && !empty($pl_status)) {
        update_post_meta($order_id, 'x_parcel_status', $pl_status);
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid order ID or pl_status.');
    }

    wp_die();
}

// Add custom column to the orders table
add_filter('manage_edit-shop_order_columns', 'add_custom_order_column');
function add_custom_order_column($columns)
{
    $columns['dm_parcel_status'] = __('Parcel status', 'delivery-master');
    return $columns;
}

add_action('manage_shop_order_posts_custom_column', 'display_custom_order_meta_data');
function display_custom_order_meta_data($column)
{
    global $post;

    if ($column === 'dm_parcel_status') {
        $order = wc_get_order($post->ID);
        $custom_meta_data = $order->get_meta('x_parcel_status');
        echo '<span>' . $custom_meta_data . '</span>';
    }
}

add_action('wp_ajax_get_orders', 'get_orders');
add_action('wp_ajax_nopriv_get_orders', 'get_orders');

function get_orders()
{
    $orders = wc_get_orders(array(
        'limit' => $_POST['limit'],
        'offset' => $_POST['offset']
    ));

    $response = array();
    foreach ($orders as $order) {
        $order = wc_get_order($order->get_id());
        $pl_number = $order->get_meta('x_parcel_number');

        $response[] = array(
            'order_id' => $order->get_id(),
            'pl_number' => $pl_number
        );
    }

    wp_send_json_success($response);
}


//add custom bulk action dpd

add_filter('bulk_actions-edit-shop_order', 'add_dpd_print_label_bulk_action', 10, 1);

function add_dpd_print_label_bulk_action($actions)
{
    $actions['dpd_print_label'] = __('DPD Print Label', 'textdomain');
    return $actions;
}

add_filter('handle_bulk_actions-edit-shop_order', 'handle_dpd_print_label_bulk_action', 10, 3);

function handle_dpd_print_label_bulk_action($redirect_to, $action, $post_ids)
{
    if ($action !== 'dpd_print_label')
        return $redirect_to;

    $username = get_option('dm_username');
    $password = get_option('dm_password');

    $orders_data = array();

    $pl_numbers = array();

    foreach ($post_ids as $order_id) {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $billing = $order_data['billing'];
        $shipping = $order_data['shipping'];
        $order_date = $order_data['date_created']->date('Y-m-d');
        $payment_method = $order->get_payment_method();
        $order_total = $order->get_total();
        $weight = 2;
        $package_number = 1;
        $parcel_type = $payment_method === 'cod' ? 'D-COD' : 'D';

        preg_match('/\d[\w\s-]*$/', $shipping['address_1'], $house_number);
        $house_number = isset($house_number[0]) ? $house_number[0] : '';

        $address_without_house_number = preg_replace('/\d[\w\s-]*$/', '', $shipping['address_1']);

        $orders_data = array(
            'reference' => $order_id,
            'customer_name' => $shipping['first_name'] . ' ' . $shipping['last_name'],
            'customer_address' => $address_without_house_number,
            'house_number' => $house_number,
            'city' => $shipping['city'],
            'zip_code' => $shipping['postcode'],
            'country' => $shipping['country'],
            'contact_person' => $shipping['first_name'] . ' ' . $shipping['last_name'],
            'phone' => $billing['phone'],
            'email' => $billing['email'],
            'payment_method' => $payment_method,
            'order_date' => $order_date,
            'weight' => $weight,
            'package_number' => $package_number,
            'cod' => $order_total,
            'parcel_type' => $parcel_type,
            'note' => $order_data['customer_note'],
        );

        $response = wp_remote_post("https://easyship.hr/api/parcel/parcel_import?username=$username&password=$password&cod_amount=$order_total&name1=" . $shipping['first_name'] . ' ' . $shipping['last_name'] . "&street=" . $orders_data['customer_address'] . "&rPropNum=" . $orders_data['house_number'] . "&city=" . $orders_data['city'] . "&country=" . $orders_data['country'] . "&pcode=" . $orders_data['zip_code'] . "&email=" . $orders_data['email'] . "&phone=" . $orders_data['phone'] . "&sender_remark=" . $orders_data['note'] . "&weight=" . $orders_data['weight'] . "&order_number=" . $orders_data['reference'] . "&cod_purpose=" . $orders_data['reference'] . "&parcel_type=$parcel_type&num_of_parcel=" . $orders_data['package_number'], array());

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $pl_numbers[] = json_decode($response['body'], true)['pl_number'][0];
            update_post_meta($order_id, 'x_parcel_number', 'HR-DPD-' . json_decode($response['body'], true)['pl_number'][0]);
        } else {
        }
    }
    //wp_die('<pre>--' . implode(",", $pl_numbers) . '---' . print_r($pl_numbers, true) . '</pre>');

    $label_response = wp_remote_post(
        "https://easyship.hr/api/parcel/parcel_print?username=$username&password=$password&parcels=" . implode(",", $pl_numbers),
        array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => array(),
            'cookies'     => array(),
            'xhrFields'   => array(
                'responseType' => 'blob',
            ),
        )
    );

    if (!is_wp_error($label_response) && wp_remote_retrieve_response_code($label_response) === 200) {
        $response_body = wp_remote_retrieve_body($label_response);

        header("Content-type: application/pdf");
        header('Content-disposition: attachment; filename="DPD-Label.pdf"');
        header("Content-Length: " . strlen($response_body));

        echo $response_body;
        exit;
    }
    //wp_die('<pre>--' . print_r(json_decode($pl_numbers[0]['body'], true)['pl_number'][0]) .'---'. print_r($pl_numbers[0]['body']->pl_number) .'---'. print_r($pl_numbers, true) . '</pre>');

    return $redirect_to;
}
