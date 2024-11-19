<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExplmLicence
{
    public function __construct()
    {
        add_action('wp_ajax_explm_start_trial', array($this, 'explm_start_trial'));
        add_action('wp_ajax_explm_licence_check', array($this, 'explm_licence_check'));
    }

    function explm_start_trial()
    {
        check_ajax_referer('explm_nonce', 'security');

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $domain = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : '';
        $licence = isset($_POST['licence']) ? sanitize_text_field(wp_unslash($_POST['licence'])) : '';

        $body = array(
            "user" => array(
                "email" => $email,
                "domain" => $domain,
                "licence" => $licence
            )
        );

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );

        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/licence/start-trial', $args);

        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($response['response']['code'] != '201') {
            $error_id = $body_response['errors'][0]['error_id'];
            $error_message = $body_response['errors'][0]['error_details'];
            wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
        }

        update_option('explm_email_option', $body_response['email']);
        update_option('explm_licence_option', $body_response['licence']);

        wp_send_json_success(array(
            'email' => $body_response['email'],
            'licence' => $body_response['licence'],
        ));
    }

    function explm_licence_check()
    {
        check_ajax_referer('explm_nonce', 'security');

        $email = get_option('explm_email_option', '');
        $domain = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : '';
        $licence = get_option('explm_licence_option', '');

        $body = array(
            "user" => array(
                "email" => $email,
                "domain" => $domain,
                "licence" => $licence
            )
        );

        $args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 120
        );

        $response = wp_remote_request('https://expresslabelmaker.com/api/v1/licence/check', $args);

        if (is_wp_error($response)) {
            wp_send_json_error(array('error_id' => null, 'error_message' => $response->get_error_message()));
        }

        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($response['response']['code'] != '201') {
            $error_id = $body_response['errors'][0]['error_id'];
            $error_message = $body_response['errors'][0]['error_details'];
            wp_send_json_error(array('error_id' => $error_id, 'error_message' => $error_message));
        }

        wp_send_json_success(array(
            'valid_from' => $body_response['valid_from'],
            'valid_until' => $body_response['valid_until'],
            'usage' => $body_response['usage'],
            'usage_limit' => $body_response['usage_limit'],
        ));
    }
}

function explm_initialize_start_trial()
{
    new ExplmLicence();
}
add_action('plugins_loaded', 'explm_initialize_start_trial');