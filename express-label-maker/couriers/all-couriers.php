<?php

class Couriers {
    public $courier_icons = array(
        'dpd' => array(
            'url' => 'assets/dpd-logo.png',
            'alt' => 'DPD Logo',
            'ajax_action' => 'elm_show_confirm_modal'
        ),
    );

    public function get_courier_icons() {
        return $this->courier_icons;
    }
}
?>
