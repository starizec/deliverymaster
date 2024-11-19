<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function explm_overseas_tab_content() {
    if (isset($_POST['delete_overseas_api_key']) && isset($_POST['explm_overseas_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['explm_overseas_nonce'])), 'explm_save_overseas_settings')) {
        delete_option('explm_overseas_api_key_option');
        echo '<div class="updated"><p>' . esc_html__('API key deleted.', 'express-label-maker') . '</p></div>';
    }
    elseif (isset($_POST['explm_overseas_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['explm_overseas_nonce'])), 'explm_save_overseas_settings')) {
        $api_key = isset($_POST['explm_overseas_api_key']) ? sanitize_text_field(wp_unslash($_POST['explm_overseas_api_key'])) : '';
        if (!empty($api_key)) {
            update_option('explm_overseas_api_key_option', $api_key);
            echo '<div class="updated"><p>' . esc_html__('API key saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('API key is required.', 'express-label-maker') . '</p></div>';
        }
    }

    $saved_api_key = get_option('explm_overseas_api_key_option', '');
    $saved_country = strtoupper(get_option('explm_country_option', ''));

    echo '<div style="display:block;">';
    echo '<div style="float: left; width: 48%; padding-right: 2%;">';
    echo '<h3>' . esc_html__('Overseas API Settings', 'express-label-maker') . '</h3>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="explm_overseas_api_key">' . esc_html__('API Key', 'express-label-maker') . '</label></th>';
    echo '<td><input name="explm_overseas_api_key" type="text" id="explm_overseas_api_key" value="' . esc_attr($saved_api_key) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit-overseas-settings" class="button button-primary" value="' . esc_attr__('Save Changes', 'express-label-maker') . '">';
    echo '<input type="submit" name="delete_overseas_api_key" class="button" value="' . esc_attr__('Delete API Key', 'express-label-maker') . '" style="background-color: transparent; color: red; border: 1px solid red; margin-left: 10px;">';
    echo '</p>';
    wp_nonce_field('explm_save_overseas_settings', 'explm_overseas_nonce');
    echo '</form>';
    echo '</div>';
    echo '</div>';

    if (isset($_POST['explm_collection_request_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['explm_collection_request_nonce'])), 'explm_save_collection_request_settings')) {
        $company_or_personal_name = isset($_POST['company_or_personal_name']) ? sanitize_text_field(wp_unslash($_POST['company_or_personal_name'])) : '';
        $contact_person = isset($_POST['contact_person']) ? sanitize_text_field(wp_unslash($_POST['contact_person'])) : '';
        $street = isset($_POST['street']) ? sanitize_text_field(wp_unslash($_POST['street'])) : '';
        $property_number = isset($_POST['property_number']) ? sanitize_text_field(wp_unslash($_POST['property_number'])) : '';
        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field(wp_unslash($_POST['postal_code'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $country = isset($_POST['collection_country']) ? sanitize_text_field(wp_unslash($_POST['collection_country'])) : '';

        if ($company_or_personal_name && $contact_person && $street && $property_number && $city && $postal_code && $phone && $email) {
            update_option('explm_overseas_company_or_personal_name', $company_or_personal_name);
            update_option('explm_overseas_contact_person', $contact_person);
            update_option('explm_overseas_street', $street);
            update_option('explm_overseas_property_number', $property_number);
            update_option('explm_overseas_city', $city);
            update_option('explm_overseas_postal_code', $postal_code);
            update_option('explm_overseas_phone', $phone);
            update_option('explm_overseas_email', $email);
            update_option('explm_overseas_country', $country);
            echo '<div style="display: flex;" class="updated"><p>' . esc_html__('Collection Request Data saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div style="display: flex;" class="error"><p>' . esc_html__('All fields are required.', 'express-label-maker') . '</p></div>';
        }
    }

    echo '<div style="float: right; width: 48%;">';
    echo '<h3>' . esc_html__('Collection Request Data', 'express-label-maker') . '</h3>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';

    $fields = [
        'company_or_personal_name' => esc_html__('Company or personal name', 'express-label-maker'),
        'contact_person' => esc_html__('Contact person', 'express-label-maker'),
        'street' => esc_html__('Street', 'express-label-maker'),
        'property_number' => esc_html__('Property number', 'express-label-maker'),
        'city' => esc_html__('City', 'express-label-maker'),
        'country' => esc_html__('Country', 'express-label-maker'),
        'postal_code' => esc_html__('Postal Code', 'express-label-maker'),
        'phone' => esc_html__('Phone', 'express-label-maker'),
        'email' => esc_html__('Email', 'express-label-maker'),
    ];

    foreach ($fields as $field_name => $label) {
        $saved_value = get_option('explm_overseas_' . $field_name, '');
    
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($field_name) . '">' . esc_html($label) . '</label></th>';
        
        if ($field_name === 'country') {
            echo '<td>';
            echo '<select name="collection_country" id="collection_country" required>';
            
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
            
            foreach ($countries as $code => $name) {
                $selected_value = $saved_value ? $saved_value : $saved_country;
                echo '<option value="' . esc_attr($code) . '" ' . selected($selected_value, $code, false) . '>' . esc_html($name) . '</option>';
            }                    
            
            echo '</select>';
            echo '</td>';
        } else {
            $input_type = $field_name === 'email' ? 'email' : 'text';
            echo '<td><input name="' . esc_attr($field_name) . '" type="' . esc_attr($input_type) . '" id="' . esc_attr($field_name) . '" value="' . esc_attr($saved_value) . '" class="regular-text" required></td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit-overseas-collection-request" class="button button-primary" value="' . esc_attr__('Save Changes', 'express-label-maker') . '">';
    echo '</p>';
    wp_nonce_field('explm_save_collection_request_settings', 'explm_collection_request_nonce');
    echo '</form>';
    echo '</div>';
    echo '</div>';
}