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
                    <input type="text" name="collection_company_or_personal_name" value="<?php echo esc_attr(get_option('explm_hp_company_or_personal_name', '')); ?>">
                </label>
                <!-- Contact person -->
                <label class="explm-labels">
                    <?php esc_html_e('Contact person:', 'express-label-maker'); ?>
                    <input type="text" name="collection_contact_person" value="<?php echo esc_attr(get_option('explm_hp_contact_person', '')); ?>">
                </label>
                <!-- Street -->
                <label class="explm-labels">
                    <?php esc_html_e('Street:', 'express-label-maker'); ?>
                    <input type="text" name="collection_street" value="<?php echo esc_attr(get_option('explm_hp_street', '')); ?>">
                </label>
                <!-- Property number -->
                <label class="explm-labels">
                    <?php esc_html_e('Property number:', 'express-label-maker'); ?>
                    <input type="text" name="collection_property_number" value="<?php echo esc_attr(get_option('explm_hp_property_number', '')); ?>">
                </label>
                <!-- City -->
                <label class="explm-labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="collection_city" value="<?php echo esc_attr(get_option('explm_hp_city', '')); ?>">
                </label>
                <!-- Country -->
                <label class="explm-labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <select name="collection_country">
                        <option value="AT" <?php selected(get_option('explm_hp_country'), 'AT'); ?>><?php echo esc_html__('Austria', 'express-label-maker'); ?></option>
                        <option value="BE" <?php selected(get_option('explm_hp_country'), 'BE'); ?>><?php echo esc_html__('Belgium', 'express-label-maker'); ?></option>
                        <option value="BG" <?php selected(get_option('explm_hp_country'), 'BG'); ?>><?php echo esc_html__('Bulgaria', 'express-label-maker'); ?></option>
                        <option value="HR" <?php selected(get_option('explm_hp_country'), 'HR'); ?>><?php echo esc_html__('Croatia', 'express-label-maker'); ?></option>
                        <option value="CZ" <?php selected(get_option('explm_hp_country'), 'CZ'); ?>><?php echo esc_html__('Czechia', 'express-label-maker'); ?></option>
                        <option value="DK" <?php selected(get_option('explm_hp_country'), 'DK'); ?>><?php echo esc_html__('Denmark', 'express-label-maker'); ?></option>
                        <option value="EE" <?php selected(get_option('explm_hp_country'), 'EE'); ?>><?php echo esc_html__('Estonia', 'express-label-maker'); ?></option>
                        <option value="FI" <?php selected(get_option('explm_hp_country'), 'FI'); ?>><?php echo esc_html__('Finland', 'express-label-maker'); ?></option>
                        <option value="FR" <?php selected(get_option('explm_hp_country'), 'FR'); ?>><?php echo esc_html__('France', 'express-label-maker'); ?></option>
                        <option value="DE" <?php selected(get_option('explm_hp_country'), 'DE'); ?>><?php echo esc_html__('Germany', 'express-label-maker'); ?></option>
                        <option value="HU" <?php selected(get_option('explm_hp_country'), 'HU'); ?>><?php echo esc_html__('Hungary', 'express-label-maker'); ?></option>
                        <option value="IE" <?php selected(get_option('explm_hp_country'), 'IE'); ?>><?php echo esc_html__('Ireland', 'express-label-maker'); ?></option>
                        <option value="IT" <?php selected(get_option('explm_hp_country'), 'IT'); ?>><?php echo esc_html__('Italy', 'express-label-maker'); ?></option>
                        <option value="LV" <?php selected(get_option('explm_hp_country'), 'LV'); ?>><?php echo esc_html__('Latvia', 'express-label-maker'); ?></option>
                        <option value="LT" <?php selected(get_option('explm_hp_country'), 'LT'); ?>><?php echo esc_html__('Lithuania', 'express-label-maker'); ?></option>
                        <option value="LU" <?php selected(get_option('explm_hp_country'), 'LU'); ?>><?php echo esc_html__('Luxembourg', 'express-label-maker'); ?></option>
                        <option value="NL" <?php selected(get_option('explm_hp_country'), 'NL'); ?>><?php echo esc_html__('Netherlands', 'express-label-maker'); ?></option>
                        <option value="PL" <?php selected(get_option('explm_hp_country'), 'PL'); ?>><?php echo esc_html__('Poland', 'express-label-maker'); ?></option>
                        <option value="PT" <?php selected(get_option('explm_hp_country'), 'PT'); ?>><?php echo esc_html__('Portugal', 'express-label-maker'); ?></option>
                        <option value="RO" <?php selected(get_option('explm_hp_country'), 'RO'); ?>><?php echo esc_html__('Romania', 'express-label-maker'); ?></option>
                        <option value="RS" <?php selected(get_option('explm_hp_country'), 'RS'); ?>><?php echo esc_html__('Serbia', 'express-label-maker'); ?></option>
                        <option value="SK" <?php selected(get_option('explm_hp_country'), 'SK'); ?>><?php echo esc_html__('Slovakia', 'express-label-maker'); ?></option>
                        <option value="SI" <?php selected(get_option('explm_hp_country'), 'SI'); ?>><?php echo esc_html__('Slovenia', 'express-label-maker'); ?></option>
                        <option value="ES" <?php selected(get_option('explm_hp_country'), 'ES'); ?>><?php echo esc_html__('Spain', 'express-label-maker'); ?></option>
                        <option value="SE" <?php selected(get_option('explm_hp_country'), 'SE'); ?>><?php echo esc_html__('Sweden', 'express-label-maker'); ?></option>
                        <option value="CH" <?php selected(get_option('explm_hp_country'), 'CH'); ?>><?php echo esc_html__('Switzerland', 'express-label-maker'); ?></option>
                    </select>
                </label>
                <!-- Postal Code -->
                <label class="explm-labels">
                    <?php esc_html_e('Postal Code:', 'express-label-maker'); ?>
                    <input type="text" name="collection_postal_code" value="<?php echo esc_attr(get_option('explm_hp_postal_code', '')); ?>">
                </label>
                <!-- Phone -->
                <label class="explm-labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="collection_phone" value="<?php echo esc_attr(get_option('explm_hp_phone', '')); ?>">
                </label>
                <!-- Email -->
                <label class="explm-labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="collection_email" value="<?php echo esc_attr(get_option('explm_hp_email', '')); ?>">
                </label>
                    <!-- Hidden courier for api -->
                <input type="hidden" id="hiddenCountry" value="<?php echo esc_attr(get_option('explm_country_option', '')); ?>" />
                <input type="hidden" id="hiddenOrderId" value="<?php echo esc_attr($order_data['id']); ?>" />
                <input type="hidden" id="hiddenCollectionCourier" value="hp" />
                </div>
                <div class="explm-form-columns">      
                <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Courier', 'express-label-maker'); ?></h3>          
                 <!-- Info for courier -->
                 <label class="explm-labels">
                    <?php esc_html_e('Info for courier:', 'express-label-maker'); ?>
                    <textarea name="collection_info_for_courier"></textarea>
                </label>

                <?php
                $hp_notifications    = explode(',', get_option('explm_hp_delivery_additional_services', '32,33'));
                $hp_delivery_service = get_option('explm_hp_delivery_service', '');
                $hp_parcel_size      = get_option('explm_hp_base_parcel_size', '');
                $hp_insured_value    = get_option('explm_hp_insured_value', '');
                ?>

        <!-- Recipient Notifications -->
            <label class="explm-labels">
                <?php esc_html_e('Recipient Notifications:', 'express-label-maker'); ?>
                <div class="notification-options">
                    <?php
                    $notif_options = [32 => 'Email', 30 => 'SMS'];
                    foreach ($notif_options as $id => $label) {
                        ?>
                        <label style="margin-right: 15px;">
                            <input
                                type="checkbox"
                                name="delivery_additional_services[]"
                                value="<?php echo esc_attr($id); ?>"
                                <?php checked(in_array((string)$id, $hp_notifications)); ?>
                            />
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php
                    }
                    ?>
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
                        printf(
                            '<option value="%1$s" %2$s>%3$s</option>',
                            esc_attr($id),
                            selected($hp_delivery_service, $id, false),
                            esc_html($label)
                        );
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
                        printf(
                            '<option value="%1$s" %2$s>%3$s</option>',
                            esc_attr($key),
                            selected($hp_parcel_size, $key, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>
            </label>

            <!-- Insured Shipment -->
            <label class="explm-labels">
                <?php esc_html_e('Insured shipment value:', 'express-label-maker'); ?>
                <input
                    type="checkbox"
                    name="insured_value"
                    value="1"
                    <?php checked($hp_insured_value, '1'); ?>
                />
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