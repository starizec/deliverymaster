<?php

class userStatusData 
{
    public function getUserStatusData($funcName, $pl_number = '') {
        if(method_exists($this, $funcName)){
            return $this->$funcName($pl_number);
        } else {
            return array();
        }
    }

    //DODATI KURIRE

    public function dpd_parcels($pl_number) {
        $saved_country = get_option("elm_country_option", '');
        $url = "https://easyship." . $saved_country . "/api/parcel/parcel_status?secret=FcJyN7vU7WKPtUh7m1bx&parcel_number=" . $pl_number;
        return [
            'url' => $url
        ];
    }
    public function overseas_parcels($pl_number) {
        $saved_country = get_option("elm_country_option", '');
        $saved_api_key = get_option('elm_overseas_api_key_option', '');
        $url = "https://apitest.overseas." . $saved_country . "/shipmentbyid?apikey=" . $saved_api_key . "&shipmentid=" . $pl_number;

        return [
            'url' => $url,
        ];
    }
}

function initialize_elm_user_status_data()
{
    return new userStatusData();
}
add_action('plugins_loaded', 'initialize_elm_user_status_data');