<?php
/**
 * Plugin Name: DeliveryMaster
 * Plugin URI: https://emedia.hr/
 * Description: A custom WooCommerce delivery plugin that will make your life easier.
 * Version: 1.0.0
 * Author: Emedia
 * Author URI: https://emedia.hr/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'DeliveryMaster' ) ) {

    class DeliveryMaster {

        public function __construct() {
            add_action( 'admin_menu', array( $this, 'dm_add_options_page' ) );
            add_action( 'admin_init', array( $this, 'dm_register_settings' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'dm_enqueue_scripts' ) );
            add_filter( 'manage_edit-shop_order_columns', array( $this, 'dm_add_order_column' ) );
            add_action( 'manage_shop_order_posts_custom_column', array( $this, 'dm_order_column_content' ) );
            add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'dm_add_icon_to_order_data_column' ) );
        }

        public function dm_add_options_page() {
            add_submenu_page(
                'woocommerce',
                'DeliveryMaster Settings',
                'DeliveryMaster',
                'manage_options',
                'deliverymaster',
                array( $this, 'dm_options_page_content' )
            );
        }
        

        public function dm_register_settings() {
            register_setting( 'dm_options_group', 'dm_username' );
            register_setting( 'dm_options_group', 'dm_password' );
        }

        public function dm_options_page_content() {
            ?>
            <div class="wrap">
                <h1>DeliveryMaster Settings</h1>
                <form id="dm-settings-form" method="post" action="options.php">
                    <?php settings_fields( 'dm_options_group' ); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Username:</th>
                            <td><input type="text" name="dm_username" value="<?php echo esc_attr( get_option( 'dm_username' ) ); ?>" autocomplete="username" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Password:</th>
                            <td><input type="password" name="dm_password" value="<?php echo esc_attr( get_option( 'dm_password' ) ); ?>" autocomplete="current-password" /></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
        
        public function dm_get_saved_options() {
            $options = array(
                'username' => get_option('dm_username'),
                'password' => get_option('dm_password')
            );
        
            return $options;
        }

        public function dm_enqueue_scripts($hook) {
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

        public function dm_add_icon_to_order_data_column( $order ) {
            $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
        
            // Display the icon
            $icon_url = plugin_dir_url( __FILE__ ) . 'assets/dpd-logo.png';
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    var iconContainer = $('<div class="dm_icon_container" style="margin-top: 20px;"><a href="#" class="dm_open_modal" data-order-id="<?php echo esc_attr( $order_id ); ?>"><img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr__( 'Open Modal', 'delivery-master' ); ?>" style="max-width: 30px; height: auto; cursor: pointer;" /></a></div>');
                    $('.order_data_column:last-child').append(iconContainer);
                });
            </script>
            <?php
        }
        

        public function dm_add_order_column( $columns ) {
            $new_columns = array();

            foreach ( $columns as $column_name => $column_info ) {
                $new_columns[ $column_name ] = $column_info;

                if ( 'order_total' === $column_name ) {
                    $new_columns['delivery_master'] = __( 'Print label', 'delivery-master' );
                }
            }

            return $new_columns;
        }

        public function dm_order_column_content( $column ) {
            global $post;
        
            if ( 'delivery_master' === $column ) {
                $icon_url = plugin_dir_url( __FILE__ ) . 'assets/dpd-logo.png';
                echo '<a href="#" class="dm_open_modal" data-order-id="' . esc_attr( $post->ID ) . '"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr__( 'Open Modal', 'delivery-master' ) . '" style="max-width: 30px; height: auto; cursor: pointer;" /></a>';
            }
        }            
    }

    new DeliveryMaster();
}

function dm_show_confirm_modal() {
    check_ajax_referer( 'dm_nonce', 'security' );
    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;

    if ( $order_id > 0 ) {
        $order = wc_get_order( $order_id );
        $order_data = $order->get_data();
        $billing = $order_data['billing'];
        $shipping = $order_data['shipping'];
        $order_date = $order_data['date_created']->date( 'Y-m-d' );
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
        <h2 style="margin-top: 0;"><?php esc_html_e( 'Order Details', 'delivery-master' ); ?></h2>
        <form id="dm_order_details_form">
            <div class="dm_form_columns">
                 <!-- Reference -->
                <label class="labels"><?php esc_html_e( 'Reference:', 'delivery-master' ); ?>
                    <input type="text" name="reference" value="<?php echo esc_attr( $order_data['id'] ); ?>">
                </label>
                  <!-- Customer's Name -->
                <label class="labels"><?php esc_html_e( "Customer's Name:", 'delivery-master' ); ?>
                    <input type="text" name="customer_name" value="<?php echo esc_attr( $billing['first_name'] . ' ' . $billing['last_name'] ); ?>">
                </label>
                <!-- Customer's Address -->
                <label class="labels"><?php esc_html_e( "Customer's Address:", 'delivery-master' ); ?>
                    <input type="text" name="customer_address" value="<?php echo esc_attr(trim($address_without_house_number)); ?>">
                </label>
                <!-- House Number -->
                <label class="labels"><?php esc_html_e( 'House Number:', 'delivery-master' ); ?>
                    <input type="text" name="house_number" value="<?php echo esc_attr( $house_number ); ?>">
                </label>
                <!-- City -->
                <label class="labels"><?php esc_html_e( 'City:', 'delivery-master' ); ?>
                    <input type="text" name="city" value="<?php echo esc_attr( $shipping['city'] ); ?>">
                </label>
                <!-- ZIP Code -->
                <label class="labels"><?php esc_html_e( 'ZIP Code:', 'delivery-master' ); ?>
                    <input type="text" name="zip_code" value="<?php echo esc_attr( $shipping['postcode'] ); ?>">
                </label>
                <!-- Country -->
                <label class="labels"><?php esc_html_e( 'Country:', 'delivery-master' ); ?>
                    <input type="text" name="country" value="<?php echo esc_attr( $shipping['country'] ); ?>">
                </label>
                <!-- Contact Person -->
                <label class="labels"><?php esc_html_e( 'Contact Person:', 'delivery-master' ); ?>
                    <input type="text" name="contact_person" value="<?php echo esc_attr( $billing['first_name'] . ' ' . $billing['last_name'] ); ?>">
                </label>
                <!-- Phone -->
                <label class="labels"><?php esc_html_e( 'Phone:', 'delivery-master' ); ?>
                    <input type="text" name="phone" value="<?php echo esc_attr( $billing['phone'] ); ?>">
                </label>
                <!-- Email -->
                <label class="labels"><?php esc_html_e( 'Email:', 'delivery-master' ); ?>
                    <input type="email" name="email" value="<?php echo esc_attr( $billing['email'] ); ?>">
                </label>
            </div>
            <div class="dm_form_columns">
                <!--Payment Method -->
                <label class="labels"><?php esc_html_e( 'Payment Method:', 'delivery-master' ); ?>
                <input type="text" name="payment_method" value="<?php echo esc_attr( $payment_method ); ?>">
                </label>
                <!-- Collection Date (Order Date) -->
                <label class="labels"><?php esc_html_e( 'Collection Date (Order Date):', 'delivery-master' ); ?>
                    <input type="date" name="collection_date" value="<?php echo esc_attr( $order_date ); ?>">
                </label>
                <!-- Weight -->
                <label class="labels"><?php esc_html_e( 'Weight:', 'delivery-master' ); ?>
                    <input type="text" name="weight" value="<?php echo esc_attr( $weight ); ?>">
                </label>
                <!-- Package Number -->
                <label class="labels"><?php esc_html_e( 'Package Number:', 'delivery-master' ); ?>
                    <input type="text" name="package_number" value="<?php echo esc_attr( $package_number ); ?>">
                </label>
                <?php if ( $payment_method === 'cod' ) : ?>
                <!-- Cash on Delivery Amount -->
                <label class="labels"><?php esc_html_e( 'Cash on Delivery Amount:', 'delivery-master' ); ?>
                    <input type="text" name="cod_amount" value="<?php echo esc_attr( $order_total ); ?>">
                </label>
                <?php endif; ?>
                <!-- Note -->
                <label class="labels"><?php esc_html_e( 'Note:', 'delivery-master' ); ?>
                    <textarea name="note"><?php echo esc_textarea( $order_data['customer_note'] ); ?></textarea>
                </label>
        </form>
        <div class="dm_modal_actions">
            <button class="button button-primary dm_confirm_action"><?php esc_html_e( 'Print', 'delivery-master' ); ?></button>
            <button class="button dm_cancel_action"><?php esc_html_e( 'Cancel', 'delivery-master' ); ?></button>
        </div>
    </div>
</div>
    <?php
        $output = ob_get_clean();
        wp_send_json_success( $output );
    } else {
        wp_send_json_error( 'Order not found.' );
    }
    wp_die();
}
add_action( 'wp_ajax_dm_show_confirm_modal', 'dm_show_confirm_modal' );

//add custom bulk action dpd

add_filter('bulk_actions-edit-shop_order', 'add_dpd_print_label_bulk_action', 20, 1);

function add_dpd_print_label_bulk_action($actions) {
    $actions['dpd_print_label'] = __('DPD Print Label', 'text_domain');
    return $actions;
}
