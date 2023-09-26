<?php
function settings_tab_content()
    {

        if (isset($_POST['delete_all_labels'])) {
            $delete_dir = wp_upload_dir();
            $dir_path = $delete_dir['basedir'] . '/elm-labels/';
        
            if (is_dir($dir_path)) {
                $files = glob($dir_path . '*');
        
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        
            $orders = wc_get_orders(['status' => 'any']);
        
            foreach ($orders as $order) {
                $order_id = $order->get_id();
                $existing_pdf_url_route = get_post_meta($order_id, 'elm_route_labels', true);
        
                if (!empty($existing_pdf_url_route)) {
                    delete_post_meta($order_id, 'elm_route_labels');
                }
            }
        
            echo '<div class="updated"><p>' . __('All labels deleted.', 'express-label-maker') . '</p></div>';
        }         

        if (isset($_POST['elm_settings_nonce']) && wp_verify_nonce($_POST['elm_settings_nonce'], 'elm_save_settings')) {

            $save_pdf = isset($_POST['elm_save_pdf_on_server']) ? 'true' : 'false';
            update_option('elm_save_pdf_on_server_option', $save_pdf);

            echo '<div class="updated"><p>' . __('Settings saved.', 'express-label-maker') . '</p></div>';
        }

        $save_pdf_on_server = get_option('elm_save_pdf_on_server_option', 'false');

        echo '<div style="display:block;">';
        echo '<div style="float: left; width: 48%; padding-right: 2%;">';
        echo '<table class="form-table delete-form-table">';
        echo '<tr>';
        echo '<th scope="row"><label>' . __('Delete all labels from server', 'express-label-maker') . '</label></th>';
        echo '<td>';
        echo '<form method="post" action="" onsubmit="return confirm(\'' . esc_js( __( 'Are you sure you want to delete all labels?', 'express-label-maker' ) ) . '\');">';
        echo '<input type="submit" name="delete_all_labels" value="' . esc_attr( __( 'Delete All', 'express-label-maker' ) ) . '" class="button button-delete">';
        echo '</form>';        
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<form method="post" action="">';
        echo '<table class="form-table">';
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

        echo '<div style="float: right; width: 48%;">';

        echo '</div>';
        echo '</div>';
}