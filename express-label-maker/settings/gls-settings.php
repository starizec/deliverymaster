<?php
if (!defined('ABSPATH')) { exit; }

function explm_gls_tab_content() {

    // ----- HANDLE SAVE / DELETE (single form) -----
    if ( isset($_POST['explm_gls_nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['explm_gls_nonce']) ), 'explm_save_gls_settings') ) {

        // DELETE – obriši SVE povezane opcije (GLS + Sender)
        if ( isset($_POST['delete_gls_account']) ) {
            $delete_keys = [
                // GLS auth/settings
                'explm_gls_username_option',
                'explm_gls_password_option',
                'explm_gls_client_number_option',
                'explm_gls_enable_pickup',
                'explm_gls_pickup_shipping_method',
                'explm_gls_delivery_additional_services',
                'explm_gls_printer_type',
                'explm_gls_print_position',
                'explm_gls_customer_note', // ako postoji
                // Sender info
                'explm_gls_company_or_personal_name',
                'explm_gls_contact_person',
                'explm_gls_street',
                'explm_gls_property_number',
                'explm_gls_city',
                'explm_gls_postal_code',
                'explm_gls_phone',
                'explm_gls_email',
                'explm_gls_country',
            ];
            foreach ($delete_keys as $k) { delete_option($k); }

            echo '<div class="updated"><p>' . esc_html__('GLS settings deleted.', 'express-label-maker') . '</p></div>';

        } else {
            // SAVE – spremi sve iz oba stupca
            $username         = isset($_POST['explm_gls_username']) ? sanitize_text_field(wp_unslash($_POST['explm_gls_username'])) : '';
            $password         = isset($_POST['explm_gls_password']) ? sanitize_text_field(wp_unslash($_POST['explm_gls_password'])) : '';
            $client_number    = isset($_POST['explm_gls_client_number']) ? sanitize_text_field(wp_unslash($_POST['explm_gls_client_number'])) : '';

            $enable_paketomat = isset($_POST['enable_paketomat']) ? '1' : '';
            $shipping_method  = isset($_POST['explm_gls_shipping_method']) ? sanitize_text_field(wp_unslash($_POST['explm_gls_shipping_method'])) : '';
            if ($enable_paketomat !== '1') {
                $shipping_method = '';
            }

            $customer_note    = isset($_POST['explm_gls_customer_note']) ? sanitize_textarea_field(wp_unslash($_POST['explm_gls_customer_note'])) : '';

            // dodatne usluge
            $notif_selected   = isset($_POST['delivery_additional_services']) ? array_map('sanitize_text_field', (array) $_POST['delivery_additional_services']) : [];
            $notif_str        = implode(',', $notif_selected);

            // printer + pozicija
            $printer_type     = isset($_POST['explm_gls_printer_type']) ? sanitize_text_field($_POST['explm_gls_printer_type']) : '';
            $print_position   = isset($_POST['explm_gls_print_position']) ? sanitize_text_field($_POST['explm_gls_print_position']) : '';

            // Sender info (SVE OBAVEZNO)
            $company_or_personal_name = isset($_POST['company_or_personal_name']) ? sanitize_text_field(wp_unslash($_POST['company_or_personal_name'])) : '';
            $contact_person           = isset($_POST['contact_person']) ? sanitize_text_field(wp_unslash($_POST['contact_person'])) : '';
            $street                   = isset($_POST['street']) ? sanitize_text_field(wp_unslash($_POST['street'])) : '';
            $property_number          = isset($_POST['property_number']) ? sanitize_text_field(wp_unslash($_POST['property_number'])) : '';
            $city                     = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
            $postal_code              = isset($_POST['postal_code']) ? sanitize_text_field(wp_unslash($_POST['postal_code'])) : '';
            $phone                    = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
            $email                    = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            $country                  = isset($_POST['collection_country']) ? sanitize_text_field(wp_unslash($_POST['collection_country'])) : '';

            // Backend validacija
            $errors = [];
            if (empty($username) || empty($password) || empty($client_number)) {
                $errors[] = esc_html__('Username, Password and Client number are required.', 'express-label-maker');
            }
            $required_sender_fields = [
                __('Company or personal name', 'express-label-maker') => $company_or_personal_name,
                __('Contact person', 'express-label-maker')           => $contact_person,
                __('Street', 'express-label-maker')                   => $street,
                __('Property number', 'express-label-maker')          => $property_number,
                __('City', 'express-label-maker')                     => $city,
                __('Postal Code', 'express-label-maker')              => $postal_code,
                __('Phone', 'express-label-maker')                    => $phone,
                __('Email', 'express-label-maker')                    => $email,
                __('Country', 'express-label-maker')                  => $country,
            ];
            foreach ($required_sender_fields as $label => $val) {
                if (empty($val)) {
                    $errors[] = sprintf( esc_html__('%s is required.', 'express-label-maker'), $label );
                }
            }

            if (!empty($errors)) {
                foreach ($errors as $msg) {
                    echo '<div class="error"><p>' . esc_html($msg) . '</p></div>';
                }
            } else {
                // Spremi GLS settings
                update_option('explm_gls_username_option', $username);
                update_option('explm_gls_password_option', $password);
                update_option('explm_gls_client_number_option', $client_number);
                update_option('explm_gls_enable_pickup', $enable_paketomat);
                update_option('explm_gls_pickup_shipping_method', $shipping_method);
                update_option('explm_gls_customer_note', $customer_note);
                update_option('explm_gls_delivery_additional_services', $notif_str);
                update_option('explm_gls_printer_type', $printer_type);
                update_option('explm_gls_print_position', $print_position);

                // Spremi Sender info
                update_option('explm_gls_company_or_personal_name', $company_or_personal_name);
                update_option('explm_gls_contact_person', $contact_person);
                update_option('explm_gls_street', $street);
                update_option('explm_gls_property_number', $property_number);
                update_option('explm_gls_city', $city);
                update_option('explm_gls_postal_code', $postal_code);
                update_option('explm_gls_phone', $phone);
                update_option('explm_gls_email', $email);
                update_option('explm_gls_country', $country);

                // Ako je uključen paketomat i postoji metoda – refresh parcel-lockera
                if ('1' === $enable_paketomat && !empty($shipping_method) && class_exists('ExplmParcelLockers')) {
                    (new ExplmParcelLockers())->explm_update_gls_parcelshops_cron_callback();
                }

                echo '<div class="updated"><p>' . esc_html__('GLS settings saved.', 'express-label-maker') . '</p></div>';
            }
        }
    }

    // ----- VIEW -----
    $saved_country_up = strtoupper(get_option('explm_country_option', ''));
    $saved_enable     = get_option('explm_gls_enable_pickup', '');
    $saved_method     = get_option('explm_gls_pickup_shipping_method', '');
    $customer_note    = get_option('explm_gls_customer_note', '');
    $saved_notifs     = explode(',', get_option('explm_gls_delivery_additional_services', 'INS,FDS,FSS'));
    $saved_printer    = get_option('explm_gls_printer_type', '');
    $saved_position   = get_option('explm_gls_print_position', '');

    // aktivne metode (helper funkcija postoji u drugom file-u)
    $shipping_methods = function_exists('explm_get_active_shipping_methods') ? explm_get_active_shipping_methods() : [];

    echo '<form method="post" action="">'; // SINGLE FORM
    echo '<div style="display:flex;flex-wrap:wrap;gap:20px;">';

    // LEFT: GLS settings
    echo '<div style="flex:1 1 420px;min-width:320px">';
    echo '<h3>' . esc_html__('GLS Settings', 'express-label-maker') . '</h3>';
    echo '<table class="form-table">';

    // username
    echo '<tr><th scope="row"><label for="explm_gls_username">' . esc_html__('Username', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('Use the Username from your GLS administration interface.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><input name="explm_gls_username" type="text" id="explm_gls_username" value="' . esc_attr(get_option('explm_gls_username_option', '')) . '" class="regular-text" required autocomplete="username"></td></tr>';

    // password
    echo '<tr><th scope="row"><label for="explm_gls_password">' . esc_html__('Password', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('Use the Password from your GLS administration interface.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><input name="explm_gls_password" type="password" id="explm_gls_password" value="' . esc_attr(get_option('explm_gls_password_option', '')) . '" class="regular-text" required autocomplete="current-password"></td></tr>';

    // client number
    echo '<tr><th scope="row"><label for="explm_gls_client_number">' . esc_html__('Client number', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('Use the Client number from your GLS administration interface.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><input name="explm_gls_client_number" type="text" id="explm_gls_client_number" value="' . esc_attr(get_option('explm_gls_client_number_option', '')) . '" class="regular-text" required></td></tr>';

    // pickup
    echo '<tr><th scope="row"><label for="enable_paketomat">' . esc_html__('Pickup station', 'express-label-maker') . '</label></th>';
    echo '<td><input type="checkbox" name="enable_paketomat" id="enable_paketomat" value="1" ' . checked($saved_enable, '1', false) . '></td></tr>';

    // shipping method
    echo '<tr id="paketomat_shipping_method_row">';
    echo '<th scope="row"><label for="explm_gls_shipping_method">' . esc_html__('Pickup station delivery method', 'express-label-maker') . '</label></th>';
    echo '<td><select name="explm_gls_shipping_method" id="explm_gls_shipping_method">';
    if (!empty($shipping_methods)) {
        foreach ($shipping_methods as $key => $method_obj) {
            $title = !empty($method_obj->settings['title']) ? $method_obj->settings['title'] : $method_obj->get_title();
            echo '<option value="' . esc_attr($key) . '" ' . selected($saved_method, $key, false) . '>' . esc_html($title) . '</option>';
        }
    }
    echo '</select></td></tr>';

    // dodatne usluge
    $notif_options = [
        'INS' => __('Insured shipment value', 'express-label-maker'),
        'FDS' => __('Email notification to recipient', 'express-label-maker'),
        'FSS' => __('SMS notification to recipient', 'express-label-maker'),
    ];
    echo '<tr><th scope="row">' . esc_html__('Additional services', 'express-label-maker') . '</th><td>';
    foreach ($notif_options as $id => $label) {
        echo '<label style="margin-right: 15px;">';
        echo '<input type="checkbox" name="delivery_additional_services[]" value="' . esc_attr($id) . '" ' . (in_array((string)$id, $saved_notifs, true) ? 'checked' : '') . '> ';
        echo esc_html($label);
        echo '</label>';
    }
    echo '</td></tr>';

    // printer
    $printer_types = [
        'A4_2x2' => 'A4_2x2',
        'A4_4x1' => 'A4_4x1',
        'Connect' => 'Connect',
        'Thermo' => 'Thermo',
        'ThermoZPL' => 'ThermoZPL',
    ];
    echo '<tr><th scope="row"><label for="explm_gls_printer_type">' . esc_html__('Printer type', 'express-label-maker') . '</label></th>';
    echo '<td><select name="explm_gls_printer_type" id="explm_gls_printer_type" required>';
    foreach ($printer_types as $id => $label) {
        echo '<option value="' . esc_attr($id) . '" ' . selected($saved_printer, $id, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></td></tr>';

    // print position
    $print_positions = ['1'=>'1','2'=>'2','3'=>'3','4'=>'4'];
    echo '<tr><th scope="row"><label for="explm_gls_print_position">' . esc_html__('Print position (Accepted only for A4-Format)', 'express-label-maker') . '</label></th>';
    echo '<td><select name="explm_gls_print_position" id="explm_gls_print_position">';
    foreach ($print_positions as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($saved_position, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></td></tr>';

    // customer note (opcionalno – ostavljeno, ali nije required)
    echo '<tr><th scope="row"><label for="explm_gls_customer_note">' . esc_html__('Customer Note', 'express-label-maker') . ' ';
    echo '<span style="cursor:help;" title="' . esc_attr__('If you enter a note here, it will override the customer\'s note on the shipping label.', 'express-label-maker') . '">ℹ️</span>';
    echo '</label></th>';
    echo '<td><textarea name="explm_gls_customer_note" id="explm_gls_customer_note" rows="3" cols="40" maxlength="99">' . esc_textarea($customer_note) . '</textarea></td></tr>';

    echo '</table>';
    echo '</div>';

    // RIGHT: Sender info (ALL required)
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
        $saved_value = get_option('explm_gls_' . $field_name, '');
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
                'CH' => esc_html__('Switzerland', 'express-label-maker')
            ];
            $selected_value = $saved_value ? $saved_value : $saved_country_up;
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

    // ACTIONS
    echo '<div style="flex-basis:100%">';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit-gls-settings" class="button button-primary" value="' . esc_attr__('Save Changes', 'express-label-maker') . '"> ';
    echo '<button type="submit" name="delete_gls_account" class="button" style="background-color:transparent;color:red;border:1px solid red;margin-left:10px;" onclick="return confirm(\'' . esc_js(__('Delete all GLS settings? This cannot be undone.', 'express-label-maker')) . '\');">' . esc_html__('Delete Account', 'express-label-maker') . '</button>';
    echo '</p>';
    echo '</div>';

    wp_nonce_field('explm_save_gls_settings', 'explm_gls_nonce');
    echo '</div>'; // outer flex
    echo '</form>';
}