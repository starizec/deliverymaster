<?php

function overseas_tab_content() {
    if (isset($_POST['delete_overseas_api_key']) && wp_verify_nonce($_POST['elm_overseas_nonce'], 'elm_save_overseas_settings')) {
        delete_option('elm_overseas_api_key_option');
        echo '<div class="updated"><p>' . __('API key deleted.', 'express-label-maker') . '</p></div>';
    }
    elseif (isset($_POST['elm_overseas_nonce']) && wp_verify_nonce($_POST['elm_overseas_nonce'], 'elm_save_overseas_settings')) {
        $api_key = isset($_POST['elm_overseas_api_key']) ? sanitize_text_field($_POST['elm_overseas_api_key']) : '';
        if (!empty($api_key)) {
            update_option('elm_overseas_api_key_option', $api_key);
            echo '<div class="updated"><p>' . __('API key saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . __('API key is required.', 'express-label-maker') . '</p></div>';
        }
    }

    $saved_api_key = get_option('elm_overseas_api_key_option', '');

    echo '<div style="display:block;">';
    echo '<div style="float: left; width: 48%; padding-right: 2%;">';
    echo '<h3>' . __('Overseas API Settings', 'express-label-maker') . '</h3>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="elm_overseas_api_key">' . __('API Key', 'express-label-maker') . '</label></th>';
    echo '<td><input name="elm_overseas_api_key" type="text" id="elm_overseas_api_key" value="' . esc_attr($saved_api_key) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit-overseas-settings" class="button button-primary" value="' . __('Save Changes', 'express-label-maker') . '">';
    echo '<input type="submit" name="delete_overseas_api_key" class="button" value="' . __('Delete API Key', 'express-label-maker') . '" style="background-color: transparent; color: red; border: 1px solid red; margin-left: 10px;">';
    echo '</p>';
    wp_nonce_field('elm_save_overseas_settings', 'elm_overseas_nonce');
    echo '</form>';
    echo '</div>';
    echo '</div>';

    if (isset($_POST['elm_collection_request_nonce']) && wp_verify_nonce($_POST['elm_collection_request_nonce'], 'elm_save_collection_request_settings')) {
        $company_or_personal_name = isset($_POST['company_or_personal_name']) ? sanitize_text_field($_POST['company_or_personal_name']) : '';
        $contact_person = isset($_POST['contact_person']) ? sanitize_text_field($_POST['contact_person']) : '';
        $street = isset($_POST['street']) ? sanitize_text_field($_POST['street']) : '';
        $property_number = isset($_POST['property_number']) ? sanitize_text_field($_POST['property_number']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $country = isset($_POST['collection_country']) ? sanitize_text_field($_POST['collection_country']) : '';

        if ($company_or_personal_name && $contact_person && $street && $property_number && $city && $postal_code && $phone && $email) {
            update_option('elm_overseas_company_or_personal_name', $company_or_personal_name);
            update_option('elm_overseas_contact_person', $contact_person);
            update_option('elm_overseas_street', $street);
            update_option('elm_overseas_property_number', $property_number);
            update_option('elm_overseas_city', $city);
            update_option('elm_overseas_postal_code', $postal_code);
            update_option('elm_overseas_phone', $phone);
            update_option('elm_overseas_email', $email);
            update_option('elm_overseas_country', $country);
            echo '<div style="display: flex;" class="updated"><p>' . __('Collection Request Data saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div style="display: flex;" class="error"><p>' . __('All fields are required.', 'express-label-maker') . '</p></div>';
        }
    }
            echo '<div style="float: right; width: 48%;">';
            echo '<h3>' . __('Collection Request Data', 'express-label-maker') . '</h3>';
            echo '<form method="post" action="">';
            echo '<table class="form-table">';

            $fields = [
                'company_or_personal_name' => __('Company or personal name', 'express-label-maker'),
                'contact_person' => __('Contact person', 'express-label-maker'),
                'street' => __('Street', 'express-label-maker'),
                'property_number' => __('Property number', 'express-label-maker'),
                'city' => __('City', 'express-label-maker'),
                'country' => __('Country', 'express-label-maker'),
                'postal_code' => __('Postal Code', 'express-label-maker'),
                'phone' => __('Phone', 'express-label-maker'),
                'email' => __('Email', 'express-label-maker'),
            ];

            foreach ($fields as $field_name => $label) {
                $saved_value = get_option('elm_overseas_' . $field_name, '');
            
                echo '<tr>';
                echo '<th scope="row"><label for="' . $field_name . '">' . $label . '</label></th>';
                
                if ($field_name === 'country') {
                    echo '<td>';
                    echo '<select name="collection_country" id="collection_country" required>';
                    
                    $countries = [ 
                        'AT' => __('Austria', 'express-label-maker'),
                        'BE' => __('Belgium', 'express-label-maker'),
                        'BG' => __('Bulgaria', 'express-label-maker'),
                        'HR' => __('Croatia', 'express-label-maker'),
                        'CZ' => __('Czechia', 'express-label-maker'),
                        'DK' => __('Denmark', 'express-label-maker'),
                        'EE' => __('Estonia', 'express-label-maker'),
                        'FI' => __('Finland', 'express-label-maker'),
                        'FR' => __('France', 'express-label-maker'),
                        'DE' => __('Germany', 'express-label-maker'),
                        'HU' => __('Hungary', 'express-label-maker'),
                        'IE' => __('Ireland', 'express-label-maker'),
                        'IT' => __('Italy', 'express-label-maker'),
                        'LV' => __('Latvia', 'express-label-maker'),
                        'LT' => __('Lithuania', 'express-label-maker'),
                        'LU' => __('Luxembourg', 'express-label-maker'),
                        'NL' => __('Netherlands', 'express-label-maker'),
                        'PL' => __('Poland', 'express-label-maker'),
                        'PT' => __('Portugal', 'express-label-maker'),
                        'RO' => __('Romania', 'express-label-maker'),
                        'RS' => __('Serbia', 'express-label-maker'),
                        'SK' => __('Slovakia', 'express-label-maker'),
                        'SI' => __('Slovenia', 'express-label-maker'),
                        'ES' => __('Spain', 'express-label-maker'),
                        'SE' => __('Sweden', 'express-label-maker'),
                        'CH' => __('Switzerland', 'express-label-maker')
                    ];
                    
                    foreach ($countries as $code => $name) {
                        $selected_value = $saved_value ? $saved_value : $saved_country;
                        echo '<option value="' . esc_attr($code) . '" ' . selected($selected_value, $code, false) . '>' . esc_html($name) . '</option>';
                    }                    
                    
                    echo '</select>';
                    echo '</td>';
                } else {
                    $input_type = $field_name === 'email' ? 'email' : 'text';
                    echo '<td><input name="' . esc_attr($field_name) . '" type="' . $input_type . '" id="' . esc_attr($field_name) . '" value="' . esc_attr($saved_value) . '" class="regular-text" required></td>';
                }
                echo '</tr>';
            }

            echo '</table>';
            echo '<p class="submit">';
            echo '<input type="submit" name="submit" id="submit-overseas-collection-request" class="button button-primary" value="' . __('Save Changes', 'express-label-maker') . '">';
            echo '</p>';
            wp_nonce_field('elm_save_collection_request_settings', 'elm_collection_request_nonce');
            echo '</form>';
            echo '</div>';
            echo '</div>';
}