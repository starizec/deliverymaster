<?php

class ElmPrintLabel
{

    public function __construct()
    {
        add_action('wp_ajax_elm_print_label', array($this, 'elm_print_label'));
    }

    function elm_print_label() {
        
        check_ajax_referer('elm_nonce', 'security');

        $courier = $_POST['chosenCourier'];
        $saved_country = get_option("elm_country_option", '');
        $order_id = $_POST['orderId'];
    
        $userObj = new user();
        $user_data = $userObj->getData($saved_country.$courier);

        $parcel_data = $_POST['parcel'];
    
        $body = array(
            "user" => $user_data,
            "parcel" => $parcel_data
        );
    
        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body)
        );
    
        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/' . $saved_country . '/' . $courier . '/create/label', $args);
    
        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

            $body_response = json_decode(wp_remote_retrieve_body($response), true);

            if ($response['response']['code'] != '201') {
                error_log(print_r($body_response, true));
                $error_id = $body_response['errors'][0]['error_id'];
                $error_message = $body_response['errors'][0]['error_details'];
                wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
            }

                $decoded_data = base64_decode($body_response['data']['label']);           
                $meta_key = $saved_country . "_" . $courier . "_parcels";
                $existing_meta_value = get_post_meta($order_id, $meta_key, true);
                $parcel_value = isset($body_response['data']['parcels']) ? $body_response['data']['parcels'] : 'unknown';
            
                if (!empty($existing_meta_value)) {
                    $new_meta_value = $existing_meta_value . "," . $parcel_value;
                } else {
                    $new_meta_value = $parcel_value;
                }
            
                update_post_meta($order_id, $meta_key, $new_meta_value);
            
                // novi način izračunavanja putanje i URL-a
                $timestamp = date('dmy');
                $file_name_new = uniqid('', true)."-$courier-$timestamp.pdf";
            
                $upload_dir = wp_upload_dir();
                $labels_dir = $upload_dir['basedir'] . '/elm-labels';  // nova putanja
                $file_path = $labels_dir . '/' . $file_name_new;

                if (!file_exists($labels_dir)) {
                    mkdir($labels_dir, 0755, true);
                }
                
                if (get_option('elm_save_pdf_on_server_option', 'false') == 'true') {
                    file_put_contents($file_path, $decoded_data);
            
                    $pdf_url_route = $upload_dir['baseurl'] . '/elm-labels/' . $file_name_new;
                    
                    // dohvati trenutnu URL rutu
                    $existing_pdf_url_route = get_post_meta($order_id, 'elm_route_labels', true);
                    
                    // ako postoji prethodna URL ruta, dodaj novu s zarezom
                    if (!empty($existing_pdf_url_route)) {
                        $pdf_url_route_to_store = $existing_pdf_url_route . ',' . $pdf_url_route;
                    } else {
                        $pdf_url_route_to_store = $pdf_url_route;
                    }
                    
                    update_post_meta($order_id, 'elm_route_labels', $pdf_url_route_to_store);
                    
                    wp_send_json_success(array(
                        'file_path' => $pdf_url_route,
                        'file_name' => $file_name_new,
                        'parcel_number' => $parcel_value
                    ));
                }
                    wp_send_json_success(array(
                        'pdf_data' => base64_encode($decoded_data),
                        'file_name' => $file_name_new,
                        'parcel_number' => $parcel_value
                    ));
                }
            }

function initialize_elm_print_label()
{
    new ElmPrintLabel();
}
add_action('plugins_loaded', 'initialize_elm_print_label');