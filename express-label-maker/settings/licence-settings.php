<?php
function licence_tab_content()

    {       

        if (isset($_POST['elm_settings_nonce']) && wp_verify_nonce($_POST['elm_settings_nonce'], 'elm_save_settings')) {
            $email = isset($_POST['elm_email']) ? sanitize_email($_POST['elm_email']) : '';
            $licence_key = isset($_POST['elm_licence_key']) ? sanitize_text_field($_POST['elm_licence_key']) : '';
            $country = isset($_POST['elm_country']) ? sanitize_text_field($_POST['elm_country']) : '';
    
            if (!empty($email)) {
                update_option('elm_email_option', $email);
                if (!empty($licence_key)) {
                    update_option('elm_licence_option', $licence_key);
                }
                if (!empty($country)) {
                    update_option('elm_country_option', $country);
                }
                echo '<div class="updated"><p>' . __('Settings saved.', 'express-label-maker') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . __('Email is required.', 'express-label-maker') . '</p></div>';
            }
        }
        
        $saved_email = get_option('elm_email_option', '');
        $saved_licence_key = get_option('elm_licence_option', '');
        $saved_country = get_option('elm_country_option', '');

        echo '<div style="display:block;">';
        echo '<div style="float: left; width: 48%; padding-right: 2%;">';
        echo '<form method="post" action="">';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_email">' . __('Email*', 'express-label-maker') . '</label></th>';
        echo '<td><input name="elm_email" type="email" id="elm_email" value="' . esc_attr($saved_email) . '" class="regular-text" required></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_licence_key">' . __('Licence*', 'express-label-maker') . '</label></th>';
        echo '<td><input name="elm_licence_key" type="text" id="elm_licence_key" value="' . esc_attr($saved_licence_key) . '" class="regular-text" placeholder="' . __( 'Your licence key or click Start Trial', 'express-label-maker' ) . '">';
        echo '<button id="start-trial-btn" class="button elm-start-trial-btn" style="display:none;margin-left:15px;">' . __('Start Trial', 'express-label-maker') . '</button></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="elm_country">' . __('Country*', 'express-label-maker') . '</label></th>';
        echo '<td>';
        echo '<select name="elm_country" id="elm_country">';
        echo '<option value="hr"' . selected($saved_country, 'hr', true) . '>' . __('Croatia', 'express-label-maker') . '</option>';
        echo '<option value="si"' . selected($saved_country, 'si', false) . '>' . __('Slovenia', 'express-label-maker') . '</option>';
        echo '</select>'; 
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="elm_submit_btn" class="button button-primary" value="' . __('Save Changes', 'express-label-maker') . '">';
        echo '</p>';
        wp_nonce_field('elm_save_settings', 'elm_settings_nonce');
        echo '</form>';

        echo '<div style="margin-top: 50px;">';
        echo '<table class="form-table">';
        echo '<h3>' . __('Licence status', 'express-label-maker') . '</h3>';
        echo '<tr>';
        echo '<th scope="row" style="width: 100px;"><label>' . __('Valid from', 'express-label-maker') . '</label></th>';
        echo '<td><input type="text" readonly value="" class="regular-text elm_licence_inputs" id="elm_valid_from"></td>';
        echo '<th scope="row" style="padding-left: 2%;width: 100px;"><label>' . __('Label limit', 'express-label-maker') . '</label></th>';
        echo '<td><input type="text" readonly value="" class="regular-text elm_licence_inputs" id="elm_usage_limit"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row" style="width: 100px;"><label>' . __('Valid to', 'express-label-maker') . '</label></th>';
        echo '<td><input type="text" readonly value="" class="regular-text elm_licence_inputs" id="elm_valid_until"></td>';
        echo '<th scope="row" style="padding-left: 2%;width: 100px;"><label>' . __('Used', 'express-label-maker') . '</label></th>';
        echo '<td><input type="text" readonly value="" class="regular-text elm_licence_inputs" id="elm_usage"></td>';
        echo '</tr>';
        echo '</table>';
        echo '</div>';

        echo '</div>';

        // upute
/*         echo '<div style="float: right; width: 48%;">';
        echo '<h3>' . __('Plugin Instructions', 'express-label-maker') . '</h3>';
        echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum.</p>';
        echo '<button id="buyNowBtn" class="button">' . __('BUY NOW', 'express-label-maker') . '</button>';
        echo '</div>';
        echo '</div>'; */
        
        // trenutna domena
/*         echo '<script type="text/javascript">
                document.getElementById("buyNowBtn").addEventListener("click", function(){
                    var currentDomain = window.location.hostname;
                    console.log(currentDomain);
                });
            </script>'; */

     
            echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var emailInput = document.getElementById("elm_email");
                var licenceKeyInput = document.getElementById("elm_licence_key");
                var countrySelect = document.getElementById("elm_country");
                var startTrialButton = document.getElementById("start-trial-btn");
                var submitButton = document.getElementById("elm_submit_btn");
        
                function toggleStartTrialButton() {
                    startTrialButton.style.display = licenceKeyInput.value.trim() === "" ? "inline-block" : "none";
                }
        
                function toggleSubmitButton() {
                    submitButton.disabled = emailInput.value.trim() === "" || licenceKeyInput.value.trim() === "" || countrySelect.value.trim() === "";
                }
        
                toggleStartTrialButton();
                toggleSubmitButton();
        
                licenceKeyInput.addEventListener("input", function() {
                    toggleStartTrialButton();
                    toggleSubmitButton();
                });
        
                emailInput.addEventListener("input", toggleSubmitButton);
                countrySelect.addEventListener("change", toggleSubmitButton);
            });        
        </script>';
}