<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function explm_licence_tab_content()
{       
    wp_enqueue_script('explm_admin_js', plugin_dir_url(__FILE__) . 'js/elm.js', array('jquery'), '1.0.1', true);

    if (isset($_POST['explm_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['explm_settings_nonce'])), 'explm_save_settings')) {
        $email = isset($_POST['explm_email']) ? sanitize_email(wp_unslash($_POST['explm_email'])) : '';
        $licence_key = isset($_POST['explm_licence_key']) ? sanitize_text_field(wp_unslash($_POST['explm_licence_key'])) : '';
        $country = isset($_POST['explm_country']) ? sanitize_text_field(wp_unslash($_POST['explm_country'])) : '';

        if (!empty($email)) {
            update_option('explm_email_option', $email);
            if (!empty($licence_key)) {
                update_option('explm_licence_option', $licence_key);
            }
            if (!empty($country)) {
                update_option('explm_country_option', $country);
            }
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Email is required.', 'express-label-maker') . '</p></div>';
        }
    }
    
    $saved_email = get_option('explm_email_option', '');
    $saved_licence_key = get_option('explm_licence_option', '');
    $saved_country = get_option('explm_country_option', '');

    echo '<div style="display:flex;flex-wrap:wrap;">';
    echo '<div>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="explm_email">' . esc_html__('Email*', 'express-label-maker') . '</label></th>';
    echo '<td><input name="explm_email" type="email" id="explm_email" value="' . esc_attr($saved_email) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="explm_licence_key">' . esc_html__('Licence*', 'express-label-maker') . '</label></th>';
    echo '<td><input name="explm_licence_key" type="text" id="explm_licence_key" value="' . esc_attr($saved_licence_key) . '" class="regular-text" placeholder="' . esc_html__('Your licence key or click Start Trial', 'express-label-maker') . '">';
    echo '<button id="start-trial-btn" class="button explm-start-trial-btn" style="display:none;margin-left:15px;">' . esc_html__('Start Trial', 'express-label-maker') . '</button></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="explm_country">' . esc_html__('Country*', 'express-label-maker') . '</label></th>';
    echo '<td>';
    echo '<select name="explm_country" id="explm_country">';
    echo '<option value="hr"' . selected($saved_country, 'hr', true) . '>' . esc_html__('Croatia', 'express-label-maker') . '</option>';
    echo '<option value="si"' . selected($saved_country, 'si', false) . '>' . esc_html__('Slovenia', 'express-label-maker') . '</option>';
    echo '</select>'; 
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="explm_submit_btn" class="button button-primary" value="' . esc_html__('Save Changes', 'express-label-maker') . '">';
    echo '</p>';
    wp_nonce_field('explm_save_settings', 'explm_settings_nonce');
    echo '</form>';

    echo '<div style="margin-top: 50px;">';
    echo '<table class="form-table">';
    echo '<h3>' . esc_html__('Licence status', 'express-label-maker') . '</h3>';
    echo '<tr>';
    echo '<th scope="row" style="width: 100px;"><label>' . esc_html__('Valid from', 'express-label-maker') . '</label></th>';
    echo '<td><input type="text" readonly value="" class="regular-text explm-licence-inputs" id="explm_valid_from"></td>';
    echo '<th scope="row" style="padding-left: 2%;width: 100px;"><label>' . esc_html__('Label limit', 'express-label-maker') . '</label></th>';
    echo '<td><input type="text" readonly value="" class="regular-text explm-licence-inputs" id="explm_usage_limit"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row" style="width: 100px;"><label>' . esc_html__('Valid to', 'express-label-maker') . '</label></th>';
    echo '<td><input type="text" readonly value="" class="regular-text explm-licence-inputs" id="explm_valid_until"></td>';
    echo '<th scope="row" style="padding-left: 2%;width: 100px;"><label>' . esc_html__('Used', 'express-label-maker') . '</label></th>';
    echo '<td><input type="text" readonly value="" class="regular-text explm-licence-inputs" id="explm_usage"></td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    $plugin_version = ExplmLabelMaker::get_plugin_version();

    echo '<div style="margin-top:20px;"> v' . esc_html( $plugin_version ) . '</div>';
    echo '</div>';

    $payment_url = 'https://expresslabelmaker.com/hr/payment/' . ( $saved_licence_key ? rawurlencode( $saved_licence_key ) : '' );

    echo '<div class="explm-licence-side">';
    echo '<div class="explm-licence-payment">';
    echo '<h3>' . esc_html__( 'Purchase or Renew Licence', 'express-label-maker' ) . '</h3>';
    echo '<p>' . esc_html__( 'If you would like to purchase the plugin or renew your licence, click the button below to go to the secure payment page.', 'express-label-maker' ) . '</p>';
    echo '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $payment_url ) . '">'
            . esc_html__( 'Go to Payment Page', 'express-label-maker' ) .
        '</a>';
    echo '</div>';
    echo '</div>';
}