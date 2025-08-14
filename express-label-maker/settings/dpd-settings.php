<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function explm_dpd_tab_content() {

    // HANDLE SAVE/DELETE (single form)
    if ( isset($_POST['explm_dpd_nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['explm_dpd_nonce']) ), 'explm_save_dpd_settings') ) {

        // DELETE – remove ALL related options (DPD + Sender)
        if ( isset($_POST['delete_dpd_account']) ) {
            $delete_keys = [
                // DPD auth/settings
                'explm_dpd_username_option',
                'explm_dpd_password_option',
                'explm_dpd_service_type_option',
                'explm_dpd_enable_pickup',
                'explm_dpd_pickup_shipping_method',
                'explm_dpd_customer_note',
                // Sender info
                'explm_dpd_company_or_personal_name',
                'explm_dpd_contact_person',
                'explm_dpd_street',
                'explm_dpd_property_number',
                'explm_dpd_city',
                'explm_dpd_postal_code',
                'explm_dpd_phone',
                'explm_dpd_email',
                'explm_dpd_country',
            ];
            foreach ($delete_keys as $k) {
                delete_option($k);
            }
            echo '<div class="updated"><p>' . esc_html__('DPD settings deleted.', 'express-label-maker') . '</p></div>';

        // SAVE – store everything from both sections
        } else {
            // DPD settings
            $username         = isset($_POST['explm_dpd_username'])         ? sanitize_text_field( wp_unslash($_POST['explm_dpd_username']) ) : '';
            $password         = isset($_POST['explm_dpd_password'])         ? sanitize_text_field( wp_unslash($_POST['explm_dpd_password']) ) : '';
            $service_type     = isset($_POST['explm_dpd_service_type'])     ? sanitize_text_field( wp_unslash($_POST['explm_dpd_service_type']) ) : '';
            $enable_paketomat = isset($_POST['enable_paketomat'])          ? '1' : '';
            $shipping_method  = isset($_POST['explm_dpd_shipping_method'])  ? sanitize_text_field( wp_unslash($_POST['explm_dpd_shipping_method']) ) : '';
            $customer_note    = isset($_POST['explm_dpd_customer_note'])    ? sanitize_textarea_field( wp_unslash($_POST['explm_dpd_customer_note']) ) : '';

            if ( $enable_paketomat !== '1' ) {
                $shipping_method = '';
            }

            // Minimal validation za auth + service
            if ( $username && $password && $service_type ) {

                // Sender info (drugi stupac forme)
                $company_or_personal_name = isset($_POST['company_or_personal_name']) ? sanitize_text_field(wp_unslash($_POST['company_or_personal_name'])) : '';
                $contact_person           = isset($_POST['contact_person'])           ? sanitize_text_field(wp_unslash($_POST['contact_person'])) : '';
                $street                   = isset($_POST['street'])                   ? sanitize_text_field(wp_unslash($_POST['street'])) : '';
                $property_number          = isset($_POST['property_number'])          ? sanitize_text_field(wp_unslash($_POST['property_number'])) : '';
                $city                     = isset($_POST['city'])                     ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
                $postal_code              = isset($_POST['postal_code'])              ? sanitize_text_field(wp_unslash($_POST['postal_code'])) : '';
                $phone                    = isset($_POST['phone'])                    ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
                $email                    = isset($_POST['email'])                    ? sanitize_email( wp_unslash($_POST['email']) ) : '';
                $country                  = isset($_POST['collection_country'])       ? sanitize_text_field(wp_unslash($_POST['collection_country'])) : '';

                // Backend validacija svih polja
                $required_sender_fields = [
                    'Company or personal name' => $company_or_personal_name,
                    'Contact person'           => $contact_person,
                    'Street'                   => $street,
                    'Property number'          => $property_number,
                    'City'                     => $city,
                    'Postal Code'              => $postal_code,
                    'Phone'                    => $phone,
                    'Email'                    => $email,
                    'Country'                  => $country,
                ];

                foreach ( $required_sender_fields as $label => $value ) {
                    if ( empty($value) ) {
                        echo '<div class="error"><p>' . sprintf(
                            esc_html__('%s is required.', 'express-label-maker'),
                            esc_html($label)
                        ) . '</p></div>';
                        return; // prekida spremanje
                    }
                }

                // DPD settings spremanje
                update_option('explm_dpd_username_option', $username);
                update_option('explm_dpd_password_option', $password);
                update_option('explm_dpd_service_type_option', $service_type);
                update_option('explm_dpd_enable_pickup', $enable_paketomat);
                update_option('explm_dpd_pickup_shipping_method', $shipping_method);
                update_option('explm_dpd_customer_note', $customer_note);

                // Spremi sender info
                update_option('explm_dpd_company_or_personal_name', $company_or_personal_name);
                update_option('explm_dpd_contact_person',           $contact_person);
                update_option('explm_dpd_street',                   $street);
                update_option('explm_dpd_property_number',          $property_number);
                update_option('explm_dpd_city',                     $city);
                update_option('explm_dpd_postal_code',              $postal_code);
                update_option('explm_dpd_phone',                    $phone);
                update_option('explm_dpd_email',                    $email);
                update_option('explm_dpd_country',                  $country);

                // Ako je uključeno i postoji metoda, osvježi lokacije
                if ( '1' === $enable_paketomat && !empty($shipping_method) && class_exists('ExplmParcelLockers') ) {
                    (new ExplmParcelLockers())->explm_update_dpd_parcelshops_cron_callback();
                }

                echo '<div class="updated"><p>' . esc_html__('DPD settings saved.', 'express-label-maker') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Username, Password, and Delivery Service are required.', 'express-label-maker') . '</p></div>';
            }
        }
    }

    // ----- VIEW -----
    $saved_country = strtoupper(get_option('explm_country_option', ''));
    $saved_enable  = get_option('explm_dpd_enable_pickup', '');
    $saved_method  = get_option('explm_dpd_pickup_shipping_method', '');
    $customer_note = get_option('explm_dpd_customer_note', '');

    $saved_service = get_option('explm_dpd_service_type_option', '');
    $service_types = [
        'B2B'  => 'B2B',
        'B2C'  => 'B2C',
        'SWAP' => 'SWAP',
        'TYRE' => 'TYRE',
        'PAL'  => 'PAL',
        'Ship-From-Shop' => 'Ship-From-Shop',
    ];
    if ( empty($saved_service) || !isset($service_types[$saved_service]) ) {
        $saved_service = 'B2C';
    }

    $shipping_methods = explm_get_active_shipping_methods();

    echo '<form method="post" action="">'; // JEDAN FORM za sve
    echo '<div style="display:flex;flex-wrap:wrap;gap:20px;">';

    // LEFT COLUMN – DPD settings
    echo '<div style="flex:1 1 420px;min-width:320px">';
    echo '<h3>' . esc_html__('DPD Settings', 'express-label-maker') . '</h3>';
    echo '<table class="form-table">';

    echo '<tr><th scope="row"><label for="explm_dpd_username">' . esc_html__('Username', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('Use your Easyship account username.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><input name="explm_dpd_username" type="text" id="explm_dpd_username" value="' . esc_attr(get_option('explm_dpd_username_option', '')) . '" class="regular-text" required autocomplete="username"></td></tr>';

    echo '<tr><th scope="row"><label for="explm_dpd_password">' . esc_html__('Password', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('Use your Easyship account password.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><input name="explm_dpd_password" type="password" id="explm_dpd_password" value="' . esc_attr(get_option('explm_dpd_password_option', '')) . '" class="regular-text" required autocomplete="current-password"></td></tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="enable_paketomat">' . esc_html__('Pickup station', 'express-label-maker') . '</label></th>';
    echo '<td><input type="checkbox" name="enable_paketomat" id="enable_paketomat" value="1" ' . checked($saved_enable, '1', false) . '></td>';
    echo '</tr>';

    echo '<tr id="paketomat_shipping_method_row">';
    echo '<th scope="row"><label for="explm_dpd_shipping_method">' . esc_html__('Pickup station delivery method', 'express-label-maker') . '</label></th>';
    echo '<td><select name="explm_dpd_shipping_method" id="explm_dpd_shipping_method">';

    if ( !empty($shipping_methods) ) {
        foreach ( $shipping_methods as $key => $method_obj ) {
            $title = !empty($method_obj->settings['title'])
                ? $method_obj->settings['title']
                : $method_obj->get_title();
            echo '<option value="' . esc_attr($key) . '" ' . selected($saved_method, $key, false) . '>' . esc_html($title) . '</option>';
        }
    }

    echo '</select></td></tr>';


    echo '<tr><th scope="row"><label for="explm_dpd_service_type">' . esc_html__('Delivery Service', 'express-label-maker') . '</label></th>';
    echo '<td><select name="explm_dpd_service_type" id="explm_dpd_service_type" required>';
    foreach ($service_types as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($saved_service, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></td></tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="explm_dpd_customer_note">' . esc_html__('Customer Note', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('If you enter a note here, it will override the customer\'s note on the shipping label.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><textarea name="explm_dpd_customer_note" id="explm_dpd_customer_note" rows="3" cols="40" maxlength="50">' . esc_textarea($customer_note) . '</textarea></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    // RIGHT COLUMN – Sender info
    $saved_country_upper = strtoupper(get_option('explm_country_option', ''));
    echo '<div style="flex:1 1 420px;min-width:320px">';
    echo '<h3>' . esc_html__('Sender information', 'express-label-maker') . '</h3>';
    echo '<table class="form-table">';

    $fields = [
        'company_or_personal_name' => esc_html__('Company or personal name', 'express-label-maker'),
        'contact_person'           => esc_html__('Contact person', 'express-label-maker'),
        'street'                   => esc_html__('Street', 'express-label-maker'),
        'property_number'          => esc_html__('Property number', 'express-label-maker'),
        'city'                     => esc_html__('City', 'express-label-maker'),
        'country'                  => esc_html__('Country', 'express-label-maker'),
        'postal_code'              => esc_html__('Postal Code', 'express-label-maker'),
        'phone'                    => esc_html__('Phone', 'express-label-maker'),
        'email'                    => esc_html__('Email', 'express-label-maker'),
    ];

foreach ($fields as $field_name => $label) {
    $saved_value = get_option('explm_dpd_' . $field_name, '');
    echo '<tr>';
    echo '<th scope="row"><label for="' . esc_attr($field_name) . '">' . esc_html($label) . '</label></th>';

    if ($field_name === 'country') {
        echo '<td><select name="collection_country" id="collection_country" required>';
            $countries = [
                'AT' => esc_html__('Austria', 'express-label-maker'),
                'BE' => esc_html__('Belgium', 'express-label-maker'),
                'BG' => esc_html__('Bulgaria', 'express-label-maker'),
                'HR' => esc_html__('Croatia', 'express-label-maker'),
                'CZ' => esc_html__('Czechia', 'express-label-maker'),
                'DK' => esc_html__('Denmark', 'express-label-maker'),
                'EE' => esc_html__('Estonia', 'express-label-maker'),
                'FI' => esc_html__('Finland', 'express-label-maker'),
                'FR' => esc_html__('France', 'express-label-maker'),
                'DE' => esc_html__('Germany', 'express-label-maker'),
                'HU' => esc_html__('Hungary', 'express-label-maker'),
                'IE' => esc_html__('Ireland', 'express-label-maker'),
                'IT' => esc_html__('Italy', 'express-label-maker'),
                'LV' => esc_html__('Latvia', 'express-label-maker'),
                'LT' => esc_html__('Lithuania', 'express-label-maker'),
                'LU' => esc_html__('Luxembourg', 'express-label-maker'),
                'NL' => esc_html__('Netherlands', 'express-label-maker'),
                'PL' => esc_html__('Poland', 'express-label-maker'),
                'PT' => esc_html__('Portugal', 'express-label-maker'),
                'RO' => esc_html__('Romania', 'express-label-maker'),
                'RS' => esc_html__('Serbia', 'express-label-maker'),
                'SK' => esc_html__('Slovakia', 'express-label-maker'),
                'SI' => esc_html__('Slovenia', 'express-label-maker'),
                'ES' => esc_html__('Spain', 'express-label-maker'),
                'SE' => esc_html__('Sweden', 'express-label-maker'),
                'CH' => esc_html__('Switzerland', 'express-label-maker'),
            ];
                    $selected_value = $saved_value ? $saved_value : $saved_country_upper;
        foreach ($countries as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($selected_value, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select></td>';
    } else {
        $input_type = ($field_name === 'email') ? 'email' : 'text';
        echo '<td><input name="' . esc_attr($field_name) . '" type="' . esc_attr($input_type) . '" id="' . esc_attr($field_name) . '" value="' . esc_attr($saved_value) . '" class="regular-text" required></td>';
    }
    echo '</tr>';
}
    echo '</table>';
    echo '</div>'; // end right

    // ACTIONS (shared)
    echo '<div style="flex-basis:100%">';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit-dpd-settings" class="button button-primary" value="' . esc_attr__('Save Changes', 'express-label-maker') . '"> ';
    echo '<button type="submit" name="delete_dpd_account" class="button" style="background-color: transparent; color: red; border: 1px solid red; margin-left: 10px;" onclick="return confirm(\'' . esc_js(__('Delete all DPD settings? This cannot be undone.', 'express-label-maker')) . '\');">' . esc_html__('Delete Account', 'express-label-maker') . '</button>';
    echo '</p>';
    echo '</div>';

    wp_nonce_field('explm_save_dpd_settings', 'explm_dpd_nonce');
    echo '</div>'; // outer flex
    echo '</form>';
}

/**
 * Aktivne shipping metode
 *
 * @return array
 */
function explm_get_active_shipping_methods() {
    $active_methods = array();

    $global_zone = WC_Shipping_Zones::get_zone_by('zone_id', 0);
    if ( $global_zone ) {
        foreach ( $global_zone->get_shipping_methods() as $method ) {
            if ( 'yes' === $method->enabled ) {
                $key = $method->id . '-' . $method->instance_id;
                $active_methods[ $key ] = $method;
            }
        }
    }

    $zones = WC_Shipping_Zones::get_zones();
    foreach ( $zones as $zone_data ) {
        $zone = new WC_Shipping_Zone($zone_data['id']);
        foreach ( $zone->get_shipping_methods() as $method ) {
            if ( 'yes' === $method->enabled ) {
                $key = $method->id . '-' . $method->instance_id;
                $active_methods[ $key ] = $method;
            }
        }
    }

    return $active_methods;
}