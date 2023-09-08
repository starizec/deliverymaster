<?php
function settings_tab_content()
    {

        if (isset($_POST['elm_settings_nonce']) && wp_verify_nonce($_POST['elm_settings_nonce'], 'elm_save_settings')) {
            $email = isset($_POST['elm_email']) ? sanitize_email($_POST['elm_email']) : '';
            $activation_key = isset($_POST['elm_activation_key']) ? sanitize_text_field($_POST['elm_activation_key']) : '';
            $country = isset($_POST['elm_country']) ? sanitize_text_field($_POST['elm_country']) : '';
            if (!empty($country)) {
                update_option('elm_country_option', $country);
            }
            $save_pdf = isset($_POST['elm_save_pdf_on_server']) ? 'true' : 'false';
            update_option('elm_save_pdf_on_server_option', $save_pdf);


            if (!empty($email)) {
                update_option('elm_email_option', $email);
                if (!empty($activation_key)) {
                    update_option('elm_activation_key_option', $activation_key);
                }
                echo '<div class="updated"><p>' . __('Settings saved.', 'express-label-maker') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . __('Email is required.', 'express-label-maker') . '</p></div>';
            }
        }

        $saved_email = get_option('elm_email_option', '');
        $saved_activation_key = get_option('elm_activation_key_option', '');
        $saved_country = get_option('elm_country_option', '');
        $save_pdf_on_server = get_option('elm_save_pdf_on_server_option', 'false');

        echo '<div style="display:block;">';
        echo '<div style="float: left; width: 48%; padding-right: 2%;">';
        echo '<form method="post" action="">';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_email">' . __('Email', 'express-label-maker') . '</label></th>';
        echo '<td><input name="elm_email" type="email" id="elm_email" value="' . esc_attr($saved_email) . '" class="regular-text" required></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_activation_key">' . __('Activation Key', 'express-label-maker') . '</label></th>';
        echo '<td><input name="elm_activation_key" type="text" id="elm_activation_key" value="' . esc_attr($saved_activation_key) . '" class="regular-text"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_country">' . __('Country', 'express-label-maker') . '</label></th>';
        echo '<td>';
        echo '<select name="elm_country" id="elm_country">';
        echo '<option value="hr"' . selected($saved_country, 'hr', false) . '>' . __('Croatia', 'express-label-maker') . '</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_save_pdf_on_server">' . __('Saving PDF labels to your server', 'express-label-maker') . '</label></th>';
        echo '<td><input name="elm_save_pdf_on_server" type="checkbox" id="elm_save_pdf_on_server"' . ($save_pdf_on_server == 'true' ? ' checked' : '') . ' value="true"></td>';
        echo '</tr>';
        echo '</table>';
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Save Changes', 'express-label-maker') . '">';
        echo '</p>';
        wp_nonce_field('elm_save_settings', 'elm_settings_nonce');
        echo '</form>';
        echo '</div>';

        // upute
        echo '<div style="float: right; width: 48%;">';
        echo '<h2>' . __('Plugin Instructions', 'express-label-maker') . '</h2>';
        echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum.</p>';
        echo '<button id="buyNowBtn" class="button">' . __('BUY NOW', 'express-label-maker') . '</button>';
        echo '</div>';
        echo '</div>';

        // trenutna domena
        echo '<script type="text/javascript">
        document.getElementById("buyNowBtn").addEventListener("click", function(){
            var currentDomain = window.location.hostname;
            console.log(currentDomain);
        });
    </script>';
    }