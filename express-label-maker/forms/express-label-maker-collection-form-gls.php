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
            <img src="<?php echo esc_url( plugins_url( 'assets/gls-logo.png', dirname(__DIR__) . '/express-label-maker.php' ) ); ?>"
            alt="<?php esc_attr_e('GLS Logo', 'express-label-maker'); ?>"
            style="height:30px;width:30px;vertical-align:middle;" />
            <h2 style="margin-top: 0;">
                <?php esc_html_e('Order Details', 'express-label-maker'); ?> #
                <?php echo esc_attr($order_data['id']); ?>
            </h2>
            <button class="explm-close-button explm-cancel-action">&times;</button>
        </div>
        <div class="explm-error" style="display: none"></div>
        <form id="explm-collection-order-details-form">
            <div class="explm-form-columns">
                <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Pickup address', 'express-label-maker'); ?></h3>
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
                 <!-- Info for sender -->
                <label class="explm-labels">
                    <?php esc_html_e('Info for sender:', 'express-label-maker'); ?>
                    <textarea name="collection_info_for_sender"></textarea>
                </label>
            </div>
            <div class="explm-form-columns">
            <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Delivery address', 'express-label-maker'); ?></h3>

            <!-- Company or personal name -->
            <label class="explm-labels">
                    <?php esc_html_e('Company or personal name:', 'express-label-maker'); ?>
                    <input type="text" name="collection_company_or_personal_name" value="<?php echo esc_attr(get_option('explm_gls_company_or_personal_name', '')); ?>">
                </label>
                <!-- Contact person -->
                <label class="explm-labels">
                    <?php esc_html_e('Contact person:', 'express-label-maker'); ?>
                    <input type="text" name="collection_contact_person" value="<?php echo esc_attr(get_option('explm_gls_contact_person', '')); ?>">
                </label>
                <!-- Street -->
                <label class="explm-labels">
                    <?php esc_html_e('Street:', 'express-label-maker'); ?>
                    <input type="text" name="collection_street" value="<?php echo esc_attr(get_option('explm_gls_street', '')); ?>">
                </label>
                <!-- Property number -->
                <label class="explm-labels">
                    <?php esc_html_e('Property number:', 'express-label-maker'); ?>
                    <input type="text" name="collection_property_number" value="<?php echo esc_attr(get_option('explm_gls_property_number', '')); ?>">
                </label>
                <!-- City -->
                <label class="explm-labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="collection_city" value="<?php echo esc_attr(get_option('explm_gls_city', '')); ?>">
                </label>
                <!-- Country -->
                <label class="explm-labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <select name="collection_country">
                        <option value="AT" <?php selected(get_option('explm_gls_country'), 'AT'); ?>><?php echo esc_html__('Austria', 'express-label-maker'); ?></option>
                        <option value="BE" <?php selected(get_option('explm_gls_country'), 'BE'); ?>><?php echo esc_html__('Belgium', 'express-label-maker'); ?></option>
                        <option value="BG" <?php selected(get_option('explm_gls_country'), 'BG'); ?>><?php echo esc_html__('Bulgaria', 'express-label-maker'); ?></option>
                        <option value="HR" <?php selected(get_option('explm_gls_country'), 'HR'); ?>><?php echo esc_html__('Croatia', 'express-label-maker'); ?></option>
                        <option value="CZ" <?php selected(get_option('explm_gls_country'), 'CZ'); ?>><?php echo esc_html__('Czechia', 'express-label-maker'); ?></option>
                        <option value="DK" <?php selected(get_option('explm_gls_country'), 'DK'); ?>><?php echo esc_html__('Denmark', 'express-label-maker'); ?></option>
                        <option value="EE" <?php selected(get_option('explm_gls_country'), 'EE'); ?>><?php echo esc_html__('Estonia', 'express-label-maker'); ?></option>
                        <option value="FI" <?php selected(get_option('explm_gls_country'), 'FI'); ?>><?php echo esc_html__('Finland', 'express-label-maker'); ?></option>
                        <option value="FR" <?php selected(get_option('explm_gls_country'), 'FR'); ?>><?php echo esc_html__('France', 'express-label-maker'); ?></option>
                        <option value="DE" <?php selected(get_option('explm_gls_country'), 'DE'); ?>><?php echo esc_html__('Germany', 'express-label-maker'); ?></option>
                        <option value="HU" <?php selected(get_option('explm_gls_country'), 'HU'); ?>><?php echo esc_html__('Hungary', 'express-label-maker'); ?></option>
                        <option value="IE" <?php selected(get_option('explm_gls_country'), 'IE'); ?>><?php echo esc_html__('Ireland', 'express-label-maker'); ?></option>
                        <option value="IT" <?php selected(get_option('explm_gls_country'), 'IT'); ?>><?php echo esc_html__('Italy', 'express-label-maker'); ?></option>
                        <option value="LV" <?php selected(get_option('explm_gls_country'), 'LV'); ?>><?php echo esc_html__('Latvia', 'express-label-maker'); ?></option>
                        <option value="LT" <?php selected(get_option('explm_gls_country'), 'LT'); ?>><?php echo esc_html__('Lithuania', 'express-label-maker'); ?></option>
                        <option value="LU" <?php selected(get_option('explm_gls_country'), 'LU'); ?>><?php echo esc_html__('Luxembourg', 'express-label-maker'); ?></option>
                        <option value="NL" <?php selected(get_option('explm_gls_country'), 'NL'); ?>><?php echo esc_html__('Netherlands', 'express-label-maker'); ?></option>
                        <option value="PL" <?php selected(get_option('explm_gls_country'), 'PL'); ?>><?php echo esc_html__('Poland', 'express-label-maker'); ?></option>
                        <option value="PT" <?php selected(get_option('explm_gls_country'), 'PT'); ?>><?php echo esc_html__('Portugal', 'express-label-maker'); ?></option>
                        <option value="RO" <?php selected(get_option('explm_gls_country'), 'RO'); ?>><?php echo esc_html__('Romania', 'express-label-maker'); ?></option>
                        <option value="RS" <?php selected(get_option('explm_gls_country'), 'RS'); ?>><?php echo esc_html__('Serbia', 'express-label-maker'); ?></option>
                        <option value="SK" <?php selected(get_option('explm_gls_country'), 'SK'); ?>><?php echo esc_html__('Slovakia', 'express-label-maker'); ?></option>
                        <option value="SI" <?php selected(get_option('explm_gls_country'), 'SI'); ?>><?php echo esc_html__('Slovenia', 'express-label-maker'); ?></option>
                        <option value="ES" <?php selected(get_option('explm_gls_country'), 'ES'); ?>><?php echo esc_html__('Spain', 'express-label-maker'); ?></option>
                        <option value="SE" <?php selected(get_option('explm_gls_country'), 'SE'); ?>><?php echo esc_html__('Sweden', 'express-label-maker'); ?></option>
                        <option value="CH" <?php selected(get_option('explm_gls_country'), 'CH'); ?>><?php echo esc_html__('Switzerland', 'express-label-maker'); ?></option>
                    </select>
                </label>
                <!-- Postal Code -->
                <label class="explm-labels">
                    <?php esc_html_e('Postal Code:', 'express-label-maker'); ?>
                    <input type="text" name="collection_postal_code" value="<?php echo esc_attr(get_option('explm_gls_postal_code', '')); ?>">
                </label>
                <!-- Phone -->
                <label class="explm-labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="collection_phone" value="<?php echo esc_attr(get_option('explm_gls_phone', '')); ?>">
                </label>
                <!-- Email -->
                <label class="explm-labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="collection_email" value="<?php echo esc_attr(get_option('explm_gls_email', '')); ?>">
                </label>
                    <!-- Hidden courier for api -->
                <input type="hidden" id="hiddenCountry" value="<?php echo esc_attr(get_option('explm_country_option', '')); ?>" />
                <input type="hidden" id="hiddenOrderId" value="<?php echo esc_attr($order_data['id']); ?>" />
                <input type="hidden" id="hiddenCollectionCourier" value="gls" />
                </div>
                <div class="explm-form-columns">      
                <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Courier', 'express-label-maker'); ?></h3>    
                
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

                 <!-- Info for courier -->
                 <label class="explm-labels">
                    <?php esc_html_e('Info for courier:', 'express-label-maker'); ?>
                    <textarea name="collection_info_for_courier"></textarea>
                </label>

            <?php
                $gls_notifications = explode(',', get_option('explm_gls_delivery_additional_services', '32,33'));
                $gls_printer_type = get_option('explm_gls_printer_type', '');
                $gls_print_position = get_option('explm_gls_print_position', '');
                $gls_locker_id = ExplmLabelMaker::get_order_meta($order_data['id'], 'gls_parcel_locker_location_id', true);
                $gls_locker_type = ExplmLabelMaker::get_order_meta($order_data['id'], 'gls_parcel_locker_type', true);
                $gls_parcel_locker_name = ExplmLabelMaker::get_order_meta($order_data['id'], 'gls_parcel_locker_name', true);
                ?>

                <!-- Parcel Locker Name -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker:', 'express-label-maker'); ?>
                    <input type="text" name="parcel_locker_name" value="<?php echo esc_attr($gls_parcel_locker_name); ?>">
                </label>

                <!-- Parcel Locker ID -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker ID:', 'express-label-maker'); ?>
                    <input type="text" name="gls_parcel_locker_location_id" value="<?php echo esc_attr($gls_locker_id); ?>">
                </label>

                <!-- Parcel Locker Type -->
                <label class="explm-labels">
                    <?php esc_html_e('Parcel Locker Type:', 'express-label-maker'); ?>
                    <input type="text" name="gls_parcel_locker_type" value="<?php echo esc_attr($gls_locker_type); ?>">
                </label>

                <!-- Additional services -->
                <label class="explm-labels">
                    <?php esc_html_e('Additional services:', 'express-label-maker'); ?>
                    <div class="additional-services-options">
                        <?php
                        $notif_options = [
                            'INS' => esc_html__('Shipment insurance', 'express-label-maker'),
                            'FDS' => esc_html__('Email notification to recipient', 'express-label-maker'),
                            'FSS' => esc_html__('SMS notification to recipient', 'express-label-maker'),
                        ];

                        foreach ($notif_options as $id => $label) {
                            ?>
                            <label style="margin-right: 15px; display:block;">
                                <input type="checkbox" name="delivery_additional_services[]" value="<?php echo esc_attr($id); ?>" <?php checked(in_array((string)$id, $gls_notifications)); ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php } ?>
                    </div>
                </label>

                <!-- Printer type -->
                <label class="explm-labels">
                    <?php esc_html_e('Printer type:', 'express-label-maker'); ?>
                    <select name="printer_type">
                        <?php
                        $services = [
                            'A4_2x2' => 'A4_2x2',
                            'A4_4x1' => 'A4_4x1',
                            'Connect' => 'Connect',
                            'Thermo' => 'Thermo',
                            'ThermoZPL' => 'ThermoZPL',
                        ];
                        foreach ($services as $id => $label) {
                            echo '<option value="' . esc_attr($id) . '" ' . selected($gls_printer_type, $id, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </label>

                <!-- Print position -->
                <label class="explm-labels">
                    <?php esc_html_e('Print position (Accepted only for A4-Format):', 'express-label-maker'); ?>
                    <select name="print_position">
                        <?php
                        $print_positions = [
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                        ];
                        foreach ($print_positions as $key => $label) {
                            echo '<option value="' . esc_attr($key) . '" ' . selected($gls_print_position, $key, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </label>
        </form>
        <div class="explm-modal-actions">
            <button class="button button-primary explm_confirm_collection_action">
                <?php esc_html_e('Send request', 'express-label-maker'); ?>
            </button>
            <button class="button explm-cancel-action">
                <?php esc_html_e('Cancel', 'express-label-maker'); ?>
            </button>
    </div>
</div>