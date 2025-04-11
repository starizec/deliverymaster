<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function explm_custom_cron_schedules( $schedules ) {
    $schedules['every_four_hours'] = array(
        'interval' => 14400,
        'display'  => esc_html__( 'Every 4 Hours', 'express-label-maker' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'explm_custom_cron_schedules' );

function explm_schedule_cron_event() {
    if ( ! wp_next_scheduled( 'update_overseas_parcelshops_cron' ) ) {
        wp_schedule_event( time(), 'every_four_hours', 'update_overseas_parcelshops_cron' );
    }
}
register_activation_hook( __FILE__, 'explm_schedule_cron_event' );

function explm_unschedule_cron_event() {
    $timestamp = wp_next_scheduled( 'update_overseas_parcelshops_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'update_overseas_parcelshops_cron' );
    }
}
register_deactivation_hook( __FILE__, 'explm_unschedule_cron_event' );

function update_overseas_parcelshops_callback() {
    if ( class_exists( 'ExplmParcelLockers' ) ) {
        $parcel_lockers = new ExplmParcelLockers();
        if ( method_exists( $parcel_lockers, 'update_overseas_parcelshops_cron_callback' ) ) {
            $parcel_lockers->update_overseas_parcelshops_cron_callback();
        }
    }
}
add_action( 'update_overseas_parcelshops_cron', 'update_overseas_parcelshops_callback' );