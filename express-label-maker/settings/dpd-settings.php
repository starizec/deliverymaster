<?php

function dpd_tab_content()
{
    if (isset($_POST['elm_dpd_nonce']) && wp_verify_nonce($_POST['elm_dpd_nonce'], 'elm_save_dpd_settings')) {
        $username = isset($_POST['elm_dpd_username']) ? sanitize_text_field($_POST['elm_dpd_username']) : '';
        $password = isset($_POST['elm_dpd_password']) ? sanitize_text_field($_POST['elm_dpd_password']) : '';


        if (!empty($username) && !empty($password)) {
            update_option('elm_dpd_username_option', $username);
            update_option('elm_dpd_password_option', $password);
            echo '<div class="updated"><p>' . __('DPD settings saved.', 'express-label-maker') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . __('Both Username and Password are required.', 'express-label-maker') . '</p></div>';
        }
    }

    $saved_username = get_option('elm_dpd_username_option', '');
    $saved_password = get_option('elm_dpd_password_option', '');

    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="elm_dpd_username">' . __('Username', 'express-label-maker') . '</label></th>';
    echo '<td><input name="elm_dpd_username" type="text" id="elm_dpd_username" value="' . esc_attr($saved_username) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="elm_dpd_password">' . __('Password', 'express-label-maker') . '</label></th>';
    echo '<td><input name="elm_dpd_password" type="password" id="elm_dpd_password" value="' . esc_attr($saved_password) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Save Changes', 'express-label-maker') . '">';
    echo '</p>';
    wp_nonce_field('elm_save_dpd_settings', 'elm_dpd_nonce');
    echo '</form>';
}