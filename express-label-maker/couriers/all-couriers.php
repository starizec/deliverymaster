<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//DODATI KURIRE
class ExpmlCouriers {
    public $courier_icons = array(
        'dpd' => array(
            'url' => 'assets/dpd-logo.png',
            'alt' => 'DPD Logo',
            'ajax_action' => 'explm_show_confirm_modal',
            'button_text' => 'DPD Print',
        ),
        'overseas' => array(
            'url' => 'assets/overseas-logo.png',
            'alt' => 'Overseas Logo',
            'ajax_action' => 'explm_show_confirm_modal',
            'button_text' => 'Overseas Print',
        ),
        'hp' => array(
            'url' => 'assets/hp-logo.png',
            'alt' => 'HP Logo',
            'ajax_action' => 'explm_show_confirm_modal',
            'button_text' => 'HP Print',
        ),
    );

    public function get_courier_icons() {
        $icons = $this->courier_icons;
        $available_icons = array();

        // Provjera za DPD
        $saved_username = get_option('explm_dpd_username_option', '');
        $saved_password = get_option('explm_dpd_password_option', '');
        if (!empty($saved_username) && !empty($saved_password)) {
            $available_icons['dpd'] = $icons['dpd'];
        }

        // Provjera za Overseas
        $saved_api_key = get_option('explm_overseas_api_key_option', '');
        if (!empty($saved_api_key)) {
            $available_icons['overseas'] = $icons['overseas'];
        }

        // Provjera za HP
        $saved_username = get_option('explm_hp_username_option', '');
        $saved_password = get_option('explm_hp_password_option', '');
        if (!empty($saved_username) && !empty($saved_password)) {
            $available_icons['hp'] = $icons['hp'];
        }

        return $available_icons;
    }
}