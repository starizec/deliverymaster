<?php
//DODATI KURIRE
class Couriers {
    public $courier_icons = array(
        'dpd' => array(
            'url' => 'assets/dpd-logo.png',
            'alt' => 'DPD Logo',
            'ajax_action' => 'elm_show_confirm_modal',
            'button_text' => 'DPD Print',
        ),
        'overseas' => array(
            'url' => 'assets/overseas-logo.png',
            'alt' => 'Overseas Logo',
            'ajax_action' => 'elm_show_confirm_modal',
            'button_text' => 'Overseas Print',
        ),
    );

    public function get_courier_icons() {
        $icons = $this->courier_icons;
        $available_icons = array();

        // Provjera za DPD
        $saved_username = get_option('elm_dpd_username_option', '');
        $saved_password = get_option('elm_dpd_password_option', '');
        if (!empty($saved_username) && !empty($saved_password)) {
            $available_icons['dpd'] = $icons['dpd'];
        }

        // Provjera za Overseas
        $saved_api_key = get_option('elm_overseas_api_key_option', '');
        if (!empty($saved_api_key)) {
            $available_icons['overseas'] = $icons['overseas'];
        }

        return $available_icons;
    }
}

?>
