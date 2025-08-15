<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmUserStatusData 
{
    public function explm_getUserStatusData($funcName, $pl_number = '') {
        if(method_exists($this, $funcName)){
            return $this->$funcName($pl_number);
        } else {
            return array();
        }
    }

    //DODATI KURIRE

    public function dpd_parcels($pl_number) {
        $saved_country = get_option("explm_country_option", '');
        $url = "https://easyship." . $saved_country . "/api/parcel/parcel_status?secret=FcJyN7vU7WKPtUh7m1bx&parcel_number=" . $pl_number;
        return [
            'url' => $url
        ];
    }
    public function overseas_parcels($pl_number) {
        $saved_country = get_option("explm_country_option", '');
        $saved_api_key = get_option('explm_overseas_api_key_option', '');
        $url = "https://api.overseas." . $saved_country . "/shipmentbyid?apikey=" . $saved_api_key . "&shipmentid=" . $pl_number;

        return [
            'url' => $url,
        ];
    }
    public function hp_parcels($pl_number) {
        $saved_country = get_option("explm_country_option", '');
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . 'hp');

        return [
            'url' => EXPLM_API_BASE_URL . "api/v1/{$saved_country}/hp/get/parcel-status",
            'user' => $user_data,
            'parcel_number' => $pl_number
        ];
    }
    public function gls_parcels($pl_number) {
        $saved_country = get_option("explm_country_option", '');
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . 'gls');

        return [
            'url' => EXPLM_API_BASE_URL . "api/v1/{$saved_country}/gls/get/parcel-status",
            'user' => $user_data,
            'parcel_number' => $pl_number
        ];
    }
}

function explm_initialize_user_status_data()
{
    return new ExplmUserStatusData();
}
add_action('plugins_loaded', 'explm_initialize_user_status_data');