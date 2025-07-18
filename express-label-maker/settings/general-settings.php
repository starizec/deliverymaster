<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function explm_settings_tab_content()
{
    if (isset($_POST['delete_all_labels'])) {
        $delete_dir = wp_upload_dir();
        $dir_path = $delete_dir['basedir'] . '/elm-labels/';
    
        if (is_dir($dir_path)) {
            $files = glob($dir_path . '*');
    
            foreach ($files as $file) {
                if (is_file($file)) {
                    wp_delete_file($file);
                }
            }
        }
    
        $orders = wc_get_orders([
            'status' => 'any',
            'limit' => -1, 
            'return' => 'ids',
        ]);
    
        foreach ($orders as $order_id) {
            $existing_pdf_url_route = ExplmLabelMaker::get_order_meta($order_id, 'explm_route_labels');
    
            if (!empty($existing_pdf_url_route)) {
                ExplmLabelMaker::update_order_meta($order_id, 'explm_route_labels', '');
            }
        }
    
        echo '<div class="updated"><p>' . esc_html__('All labels deleted.', 'express-label-maker') . '</p></div>';
    }         

    if (isset($_POST['explm_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['explm_settings_nonce'])), 'explm_save_settings')) {
        $save_pdf = isset($_POST['explm_save_pdf_on_server']) ? 'true' : 'false';
        update_option('explm_save_pdf_on_server_option', $save_pdf);

        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'express-label-maker') . '</p></div>';
    }

    $save_pdf_on_server = get_option('explm_save_pdf_on_server_option', 'true');

    echo '<div style="display:flex;flex-wrap:wrap;gap:20px;">';
    echo '<div style="flex: 1 1 auto;">';
    echo '<table class="form-table delete-form-table">';
    echo '<tr>';
    echo '<th scope="row"><label>' . esc_html__('Delete all labels from server', 'express-label-maker') . '</label></th>';
    echo '<td>';
    echo '<form method="post" action="" onsubmit="return confirm(\'' . esc_js(__('Are you sure you want to delete all labels?', 'express-label-maker')) . '\');">';
    echo '<input type="submit" name="delete_all_labels" value="' . esc_attr__('Delete All', 'express-label-maker') . '" class="button button-delete">';
    echo '</form>';        
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="explm_save_pdf_on_server">' . esc_html__('Saving PDF labels to your server', 'express-label-maker') . '</label></th>';
    echo '<td><input name="explm_save_pdf_on_server" type="checkbox" id="explm_save_pdf_on_server"' . ($save_pdf_on_server == 'true' ? ' checked' : '') . ' value="true"></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr__('Save Changes', 'express-label-maker') . '">';
    echo '</p>';
    wp_nonce_field('explm_save_settings', 'explm_settings_nonce');
    echo '</form>';
    echo '</div>';
    echo '</div>';
}