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
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . 'dpd');

        return [
            'url' => EXPLM_API_BASE_URL . "api/v1/{$saved_country}/dpd/get/parcel-status",
            'user' => $user_data,
            'parcel_number' => $pl_number
        ];
    }

    public function overseas_parcels($pl_number) {
        $saved_country = get_option("explm_country_option", '');
        $userObj = new ExplmUser();
        $user_data = $userObj->getData($saved_country . 'overseas');

        return [
            'url' => EXPLM_API_BASE_URL . "api/v1/{$saved_country}/overseas/get/parcel-status",
            'user' => $user_data,
            'parcel_number' => $pl_number
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