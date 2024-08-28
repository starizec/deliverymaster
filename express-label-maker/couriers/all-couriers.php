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
        return $this->courier_icons;
    }
}
?>
