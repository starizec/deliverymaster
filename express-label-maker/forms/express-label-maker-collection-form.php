<div class="elm_loading_panel">
    <div class="elm_spinner"></div>
</div>
<div class="elm_modal_wrapper">
    <div class="elm_modal">
        <div class="elm_modal_header">
            <h2 style="margin-top: 0;">
                <?php esc_html_e('Order Details', 'express-label-maker'); ?> #
                <?php echo esc_attr($order_data['id']); ?>
            </h2>
            <button class="elm_close_button elm_cancel_action">&times;</button>
        </div>
        <div class="elm-error" style="display: none"></div>
        <form id="elm_collection_order_details_form">
            <div class="elm_form_columns">
                <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Pickup address', 'express-label-maker'); ?></h3>
                <!-- Customer's Name -->
                <label class="labels">
                    <?php esc_html_e("Customer's Name:", 'express-label-maker'); ?>
                    <input type="text" name="customer_name" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Customer's Address -->
                <label class="labels">
                    <?php esc_html_e("Customer's Address:", 'express-label-maker'); ?>
                    <input type="text" name="customer_address" value="<?php echo esc_attr(trim($address_without_house_number)); ?>">
                </label>
                <!-- House Number -->
                <label class="labels">
                    <?php esc_html_e('House Number:', 'express-label-maker'); ?>
                    <input type="text" name="house_number" value="<?php echo esc_attr($house_number); ?>">
                </label>
                <!-- City -->
                <label class="labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="city" value="<?php echo esc_attr($shipping['city']); ?>">
                </label>
                <!-- ZIP Code -->
                <label class="labels">
                    <?php esc_html_e('ZIP Code:', 'express-label-maker'); ?>
                    <input type="text" name="zip_code" value="<?php echo esc_attr($shipping['postcode']); ?>">
                </label>
                <!-- Country -->
                <label class="labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <input type="text" name="country" value="<?php echo esc_attr($shipping['country']); ?>">
                </label>
                <!-- Contact Person -->
                <label class="labels">
                    <?php esc_html_e('Contact Person:', 'express-label-maker'); ?>
                    <input type="text" name="contact_person" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Phone -->
                <label class="labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="phone" value="<?php echo esc_attr($billing['phone']); ?>">
                </label>
                <!-- Email -->
                <label class="labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="email" value="<?php echo esc_attr($billing['email']); ?>">
                </label>
                 <!-- Info for sender -->
                <label class="labels">
                    <?php esc_html_e('Info for sender:', 'express-label-maker'); ?>
                    <textarea name="collection_info_for_sender"></textarea>
                </label>
            </div>
            <div class="elm_form_columns">
            <h3 style="margin: 0 0 5px 0;"><?php esc_html_e('Delivery address', 'express-label-maker'); ?></h3>

            <!-- Company or personal name -->
            <label class="labels">
                    <?php esc_html_e('Company or personal name:', 'express-label-maker'); ?>
                    <input type="text" name="collection_company_or_personal_name" value="<?php echo esc_attr(get_option('elm_dpd_company_or_personal_name', '')); ?>">
                </label>
                <!-- Contact person -->
                <label class="labels">
                    <?php esc_html_e('Contact person:', 'express-label-maker'); ?>
                    <input type="text" name="collection_contact_person" value="<?php echo esc_attr(get_option('elm_dpd_contact_person', '')); ?>">
                </label>
                <!-- Street -->
                <label class="labels">
                    <?php esc_html_e('Street:', 'express-label-maker'); ?>
                    <input type="text" name="collection_street" value="<?php echo esc_attr(get_option('elm_dpd_street', '')); ?>">
                </label>
                <!-- Property number -->
                <label class="labels">
                    <?php esc_html_e('Property number:', 'express-label-maker'); ?>
                    <input type="text" name="collection_property_number" value="<?php echo esc_attr(get_option('elm_dpd_property_number', '')); ?>">
                </label>
                <!-- City -->
                <label class="labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="collection_city" value="<?php echo esc_attr(get_option('elm_dpd_city', '')); ?>">
                </label>
                <!-- Country -->
                <label class="labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <select name="collection_country">
                        <option value="AT" <?php selected(get_option('elm_dpd_country'), 'AT'); ?>><?php echo esc_html__('Austria', 'express-label-maker'); ?></option>
                        <option value="BE" <?php selected(get_option('elm_dpd_country'), 'BE'); ?>><?php echo esc_html__('Belgium', 'express-label-maker'); ?></option>
                        <option value="BG" <?php selected(get_option('elm_dpd_country'), 'BG'); ?>><?php echo esc_html__('Bulgaria', 'express-label-maker'); ?></option>
                        <option value="HR" <?php selected(get_option('elm_dpd_country'), 'HR'); ?>><?php echo esc_html__('Croatia', 'express-label-maker'); ?></option>
                        <option value="CZ" <?php selected(get_option('elm_dpd_country'), 'CZ'); ?>><?php echo esc_html__('Czechia', 'express-label-maker'); ?></option>
                        <option value="DK" <?php selected(get_option('elm_dpd_country'), 'DK'); ?>><?php echo esc_html__('Denmark', 'express-label-maker'); ?></option>
                        <option value="EE" <?php selected(get_option('elm_dpd_country'), 'EE'); ?>><?php echo esc_html__('Estonia', 'express-label-maker'); ?></option>
                        <option value="FI" <?php selected(get_option('elm_dpd_country'), 'FI'); ?>><?php echo esc_html__('Finland', 'express-label-maker'); ?></option>
                        <option value="FR" <?php selected(get_option('elm_dpd_country'), 'FR'); ?>><?php echo esc_html__('France', 'express-label-maker'); ?></option>
                        <option value="DE" <?php selected(get_option('elm_dpd_country'), 'DE'); ?>><?php echo esc_html__('Germany', 'express-label-maker'); ?></option>
                        <option value="HU" <?php selected(get_option('elm_dpd_country'), 'HU'); ?>><?php echo esc_html__('Hungary', 'express-label-maker'); ?></option>
                        <option value="IE" <?php selected(get_option('elm_dpd_country'), 'IE'); ?>><?php echo esc_html__('Ireland', 'express-label-maker'); ?></option>
                        <option value="IT" <?php selected(get_option('elm_dpd_country'), 'IT'); ?>><?php echo esc_html__('Italy', 'express-label-maker'); ?></option>
                        <option value="LV" <?php selected(get_option('elm_dpd_country'), 'LV'); ?>><?php echo esc_html__('Latvia', 'express-label-maker'); ?></option>
                        <option value="LT" <?php selected(get_option('elm_dpd_country'), 'LT'); ?>><?php echo esc_html__('Lithuania', 'express-label-maker'); ?></option>
                        <option value="LU" <?php selected(get_option('elm_dpd_country'), 'LU'); ?>><?php echo esc_html__('Luxembourg', 'express-label-maker'); ?></option>
                        <option value="NL" <?php selected(get_option('elm_dpd_country'), 'NL'); ?>><?php echo esc_html__('Netherlands', 'express-label-maker'); ?></option>
                        <option value="PL" <?php selected(get_option('elm_dpd_country'), 'PL'); ?>><?php echo esc_html__('Poland', 'express-label-maker'); ?></option>
                        <option value="PT" <?php selected(get_option('elm_dpd_country'), 'PT'); ?>><?php echo esc_html__('Portugal', 'express-label-maker'); ?></option>
                        <option value="RO" <?php selected(get_option('elm_dpd_country'), 'RO'); ?>><?php echo esc_html__('Romania', 'express-label-maker'); ?></option>
                        <option value="RS" <?php selected(get_option('elm_dpd_country'), 'RS'); ?>><?php echo esc_html__('Serbia', 'express-label-maker'); ?></option>
                        <option value="SK" <?php selected(get_option('elm_dpd_country'), 'SK'); ?>><?php echo esc_html__('Slovakia', 'express-label-maker'); ?></option>
                        <option value="SI" <?php selected(get_option('elm_dpd_country'), 'SI'); ?>><?php echo esc_html__('Slovenia', 'express-label-maker'); ?></option>
                        <option value="ES" <?php selected(get_option('elm_dpd_country'), 'ES'); ?>><?php echo esc_html__('Spain', 'express-label-maker'); ?></option>
                        <option value="SE" <?php selected(get_option('elm_dpd_country'), 'SE'); ?>><?php echo esc_html__('Sweden', 'express-label-maker'); ?></option>
                        <option value="CH" <?php selected(get_option('elm_dpd_country'), 'CH'); ?>><?php echo esc_html__('Switzerland', 'express-label-maker'); ?></option>
                    </select>
                </label>
                <!-- Postal Code -->
                <label class="labels">
                    <?php esc_html_e('Postal Code:', 'express-label-maker'); ?>
                    <input type="text" name="collection_postal_code" value="<?php echo esc_attr(get_option('elm_dpd_postal_code', '')); ?>">
                </label>
                <!-- Phone -->
                <label class="labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="collection_phone" value="<?php echo esc_attr(get_option('elm_dpd_phone', '')); ?>">
                </label>
                <!-- Email -->
                <label class="labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="collection_email" value="<?php echo esc_attr(get_option('elm_dpd_email', '')); ?>">
                </label>
                 <!-- Pickup Date -->
                <label class="labels">
                    <?php esc_html_e('Pickup Date:', 'express-label-maker'); ?>
                    <input type="date" name="collection_pickup_date" value="<?php echo esc_attr(gmdate('Y-m-d', strtotime('+1 day'))); ?>">
                </label>
                <!-- Courier selection -->
                <label class="labels">
                    <?php esc_html_e('Select Courier:', 'express-label-maker'); ?>
                    <select id="collection_courier" name="collection_courier_selection">
                        <option value="dpd">DPD</option>
                    </select>
                </label>
                 <!-- Info for courier -->
                 <label class="labels">
                    <?php esc_html_e('Info for courier:', 'express-label-maker'); ?>
                    <textarea name="collection_info_for_courier"></textarea>
                </label>
                    <!-- Hidden courier for api -->
                <input type="hidden" id="hiddenCountry" value="<?php echo esc_attr(get_option('elm_country_option', '')); ?>" />
                <input type="hidden" id="hiddenOrderId" value="<?php echo esc_attr($order_data['id']); ?>" />
        </form>
        <div class="elm_modal_actions">
            <button class="button button-primary elm_confirm_collection_action">
                <?php esc_html_e('Send request', 'express-label-maker'); ?>
            </button>
            <button class="button elm_cancel_action">
                <?php esc_html_e('Cancel', 'express-label-maker'); ?>
            </button>
        </div>
    </div>
</div>