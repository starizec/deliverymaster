<?php 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>

<div class="explm-loading-panel">
    <div class="explm-spinner"></div>
</div>
<div class="explm-modal-wrapper">
    <div class="explm-modal">
        <div class="explm-modal-header">
            <h2 style="margin-top: 0;">
                <?php esc_html_e('Order Details', 'express-label-maker'); ?> #
                <?php echo esc_attr($order_data['id']); ?>
            </h2>
            <button class="explm-close-button explm-cancel-action">&times;</button>
        </div>
        <div class="explm-error" style="display: none"></div>
        <form id="explm-order-details-form">
            <div class="explm-form-columns">
                <!-- Customer's Name -->
                <label class="explm-labels">
                    <?php esc_html_e("Customer's Name:", 'express-label-maker'); ?>
                    <input type="text" name="customer_name" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Customer's Address -->
                <label class="explm-labels">
                    <?php esc_html_e("Customer's Address:", 'express-label-maker'); ?>
                    <input type="text" name="customer_address" value="<?php echo esc_attr(trim($address_without_house_number)); ?>">
                </label>
                <!-- House Number -->
                <label class="explm-labels">
                    <?php esc_html_e('House Number:', 'express-label-maker'); ?>
                    <input type="text" name="house_number" value="<?php echo esc_attr($house_number); ?>">
                </label>
                <!-- City -->
                <label class="explm-labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="city" value="<?php echo esc_attr($shipping['city']); ?>">
                </label>
                <!-- ZIP Code -->
                <label class="explm-labels">
                    <?php esc_html_e('ZIP Code:', 'express-label-maker'); ?>
                    <input type="text" name="zip_code" value="<?php echo esc_attr($shipping['postcode']); ?>">
                </label>
                <!-- Country -->
                <label class="explm-labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <input type="text" name="country" value="<?php echo esc_attr($shipping['country']); ?>">
                </label>
                <!-- Contact Person -->
                <label class="explm-labels">
                    <?php esc_html_e('Contact Person:', 'express-label-maker'); ?>
                    <input type="text" name="contact_person" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Phone -->
                <label class="explm-labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="phone" value="<?php echo esc_attr($billing['phone']); ?>">
                </label>
                <!-- Email -->
                <label class="explm-labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="email" value="<?php echo esc_attr($billing['email']); ?>">
                </label>
            </div>

            <div class="explm-form-columns">
                <!-- Reference -->
                <label class="explm-labels">
                    <?php esc_html_e('Reference:', 'express-label-maker'); ?>
                    <input type="text" name="reference" value="<?php echo esc_attr($order_data['id']); ?>">
                </label>
                <!--Payment Method -->
                <label class="explm-labels">
                    <?php esc_html_e('Payment Method:', 'express-label-maker'); ?>
                    <input type="text" name="payment_method" value="<?php echo esc_attr($payment_method); ?>" disabled>
                </label>
                <!--Payment -->
                <label class="explm-labels">
                    <?php esc_html_e('Payment:', 'express-label-maker'); ?>
                    <div class="payment">
                    <input type="radio" name="parcel_type" value="cod" id="x-cod" <?php echo $payment_method === 'cod' ? 'checked' : ''; ?>>
                        <label for="x-cod" style="padding-right:5px;">COD</label>
                        <input type="radio" name="parcel_type" value="classic" id="x-classic" <?php echo $payment_method != 'cod' ? 'checked' : '' ?>>
                        <label for="x-classic">Classic</label>
                    </div>
                </label>
                <!-- Collection Date (Order Date) -->
                <label class="explm-labels">
                    <?php esc_html_e('Collection Date (Order Date):', 'express-label-maker'); ?>
                    <input type="date" name="collection_date" value="<?php echo esc_attr($order_date); ?>">
                </label>
                <!-- Weight -->
                <label class="explm-labels">
                    <?php esc_html_e('Weight:', 'express-label-maker'); ?>
                    <input type="text" name="weight" value="<?php echo esc_attr($weight); ?>">
                </label>
                <!-- Package Number -->
                <label class="explm-labels">
                    <?php esc_html_e('Package Number:', 'express-label-maker'); ?>
                    <input type="text" name="package_number" value="<?php echo esc_attr($package_number); ?>">
                </label>
                <!-- Cash on Delivery Amount -->
                <label class="explm-labels">
                    <?php esc_html_e('Cash on Delivery Amount:', 'express-label-maker'); ?>
                    <input type="text" name="cod_amount" id="cod_amount" value="">
                </label>
                <!-- Order Total -->
                <label class="explm-labels">
                    <?php esc_html_e('Order Total:', 'express-label-maker'); ?>
                    <input type="text" name="order_total" value="<?php echo esc_attr($order_total); ?>">
                </label>
                <!-- Note -->
                <label class="explm-labels">
                    <?php esc_html_e('Note:', 'express-label-maker'); ?>
                    <textarea name="note"><?php echo esc_textarea($order_data['customer_note']); ?></textarea>
                </label>
            </div>
            
            <div class="explm-form-columns">

            <?php
                $dpd_parcel_locker_location_id = '';
                $overseas_parcel_locker_location_id = '';    
                        
            //DODATI KURIRE

                switch ($courier) {
                    case 'dpd':
                    $dpd_parcel_locker_location_id = ExplmLabelMaker::get_order_meta($order_data['id'], 'dpd_parcel_locker_location_id', true);
                    $dpd_parcel_locker_name = ExplmLabelMaker::get_order_meta($order_data['id'], 'dpd_parcel_locker_name', true);
            ?>

                <!-- Parcel Locker -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker_name" value="<?php echo esc_attr($dpd_parcel_locker_name); ?>">
                </label>

                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker ID:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker" value="<?php echo esc_attr($dpd_parcel_locker_location_id); ?>">
                </label>
                

                <?php 
                    break;

                    case 'overseas':
                    $overseas_parcel_locker_location_id = ExplmLabelMaker::get_order_meta($order_data['id'], 'overseas_parcel_locker_location_id', true);
                    $overseas_parcel_locker_name = ExplmLabelMaker::get_order_meta($order_data['id'], 'overseas_parcel_locker_name', true); 
                ?>
              
                <!-- Parcel Locker -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker_name" value="<?php echo esc_attr($overseas_parcel_locker_name); ?>">
                </label>

                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker ID:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker" value="<?php echo esc_attr($overseas_parcel_locker_location_id); ?>">
                </label>
                
                <?php
                break;
                case 'hp';

                $hp_notifications = explode(',', get_option('explm_hp_delivery_additional_services', '32,33'));
                $hp_delivery_service = get_option('explm_hp_delivery_service', '');
                $hp_parcel_size = get_option('explm_hp_base_parcel_size', '');
                $hp_insured_value = get_option('explm_hp_insured_value', '');
                $hp_locker_id = ExplmLabelMaker::get_order_meta($order_data['id'], 'hp_parcel_locker_location_id', true);
                $hp_locker_type = ExplmLabelMaker::get_order_meta($order_data['id'], 'hp_parcel_locker_type', true);
                $hp_parcel_locker_name = ExplmLabelMaker::get_order_meta($order_data['id'], 'hp_parcel_locker_name', true);
                ?>

                <!-- Parcel Locker Name -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker_name" value="<?php echo esc_attr($hp_parcel_locker_name); ?>">
                </label>

                <!-- Parcel Locker ID -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker ID:', 'express-label-maker'); ?>
                    <input type="text" name="hp_parcel_locker_location_id" value="<?php echo esc_attr($hp_locker_id); ?>">
                </label>

                <!-- Parcel Locker Type -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker Type:', 'express-label-maker'); ?>
                    <input type="text" name="hp_parcel_locker_type" value="<?php echo esc_attr($hp_locker_type); ?>">
                </label>

                <!-- Recipient Notifications -->
                <label class="explm-labels">
                    <?php esc_html_e('Recipient Notifications:', 'express-label-maker'); ?>
                    <div class="notification-options">
                        <?php
                        $notif_options = [32 => 'Email', 30 => 'SMS'];
                        foreach ($notif_options as $id => $label) {
                            ?>
                            <label style="margin-right: 15px;">
                                <input type="checkbox" name="delivery_additional_services[]" value="<?php echo esc_attr($id); ?>" <?php checked(in_array((string)$id, $hp_notifications)); ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php } ?>
                    </div>
                </label>

                <!-- Delivery Service -->
                <label class="explm-labels">
                    <?php esc_html_e('Delivery Service:', 'express-label-maker'); ?>
                    <select name="delivery_service">
                        <?php
                        $services = [
                            26 => 'Paket 24 D+1',
                            29 => 'Paket 24 D+2',
                            32 => 'Paket 24 D+3',
                            38 => 'Paket 24 D+4',
                            39 => 'EasyReturn D+3 (1st option)',
                            40 => 'EasyReturn D+3 (2nd option)',
                            46 => 'Pallet shipment D+5',
                        ];
                        foreach ($services as $id => $label) {
                            echo '<option value="' . esc_attr($id) . '" ' . selected($hp_delivery_service, $id, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </label>

                <!-- Parcel Size -->
                <label class="explm-labels">
                    <?php esc_html_e('Base parcel size (valid only for parcel lockers):', 'express-label-maker'); ?>
                    <select name="parcel_size">
                        <?php
                        $sizes = [
                            'X' => 'XS – Paket veličine XS',
                            'S' => 'S – Paket veličine S',
                            'M' => 'M – Paket veličine M',
                            'L' => 'L – Paket veličine L',
                        ];
                        foreach ($sizes as $key => $label) {
                            echo '<option value="' . esc_attr($key) . '" ' . selected($hp_parcel_size, $key, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </label>

                <!-- Insured Shipment -->
                <label class="explm-labels">
                    <?php esc_html_e('Insured shipment value:', 'express-label-maker'); ?>
                    <input type="checkbox" name="insured_value" value="1" <?php checked($hp_insured_value, '1'); ?>>
                </label>

                <?php 
                    break;

                    default: 
                ?>
                <!-- Error  -->
                        <p><?php esc_html_e('Invalid courier selected.', 'express-label-maker'); ?></p>
                <?php 
                        break;
                } 
                ?>

                <!-- Hidden courier for api -->
                <input type="hidden" id="hiddenCourier" value="" />
                <input type="hidden" id="hiddenOrderId" value="<?php echo esc_attr($order_data['id']); ?>" />
                <input type="hidden" id="dpdParcelLockerId" value="<?php echo esc_attr($dpd_parcel_locker_location_id); ?>">
                <input type="hidden" id="overseasParcelLockerId" value="<?php echo esc_attr($overseas_parcel_locker_location_id); ?>">
                <input type="hidden" name="hiddenCurrency" value="<?php echo esc_attr($order->get_currency()); ?>">
        </form>
        <div class="explm-modal-actions">
            <button class="button button-primary explm_confirm_action">
                <?php esc_html_e('Print', 'express-label-maker'); ?>
            </button>
            <button class="button explm-cancel-action">
                <?php esc_html_e('Cancel', 'express-label-maker'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function setCodAmount() {
        if ($('#x-cod').is(':checked')) {
            $('#cod_amount').val('<?php echo esc_js($order_total); ?>');
        } else {
            $('#cod_amount').val('');
        }
    }

    setCodAmount();

    $('input[name="parcel_type"]').change(function() {
        setCodAmount();
    });
});
</script>