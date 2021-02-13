<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/*
 * Return the Stripe API credentials
 *
 */
if( !function_exists( 'pms_get_stripe_api_credentials' ) ) {

    function pms_get_stripe_api_credentials() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $pms_settings = get_option( 'pms_settings', array() );
            $pms_settings = ( !empty( $pms_settings['payments']['gateways']['stripe'] ) ? $pms_settings['payments']['gateways']['stripe'] : '' );
        } else {
            $pms_settings = get_option( 'pms_payments_settings', array() );
            $pms_settings = ( !empty( $pms_settings['gateways']['stripe'] ) ? $pms_settings['gateways']['stripe'] : '' );
        }

        if( empty( $pms_settings ) )
            return false;

        if( pms_is_payment_test_mode() )
            $sandbox_prefix = 'test_';
        else
            $sandbox_prefix = '';

        $api_credentials = array(
            'secret_key'      => $pms_settings[$sandbox_prefix . 'api_secret_key'],
            'publishable_key' => $pms_settings[$sandbox_prefix . 'api_publishable_key']
        );

        $api_credentials = array_map( 'trim', $api_credentials );

        if( count( array_filter($api_credentials) ) == count($api_credentials) )
            return $api_credentials;
        else
            return false;

    }

}


/*
 * Checks whether the value of the payment profile id matches the subscription ids
 * in Stripe
 *
 * @param string $payment_profile_id
 *
 */
function pms_is_stripe_payment_profile_id( $payment_profile_id ) {

    if( strpos( $payment_profile_id, 'sub_' ) !== false )
        return true;
    else
        return false;

}

function pms_get_payment_by_transaction_id( $intent_id ){

    global $wpdb;

    $result = $wpdb->get_row( "SELECT id FROM {$wpdb->prefix}pms_payments WHERE transaction_id = '{$intent_id}'", ARRAY_A );

    if( ! is_null( $result ) )
        $result = new PMS_Payment( $result['id'] );

    return $result;

}

function pms_get_active_stripe_gateway(){

    $settings = get_option( 'pms_payments_settings', array() );

    if( !isset( $settings['active_pay_gates'] ) )
        return false;

    $active_gateway = false;

    foreach( $settings['active_pay_gates'] as $gateway_slug ){
        if( strpos( $gateway_slug, 'stripe' ) !== false )
            $active_gateway = $gateway_slug;
    }

    return $active_gateway;

}

function pms_stripe_check_filter_from_class_exists( $hook, $className, $methodName ){
    global $wp_filter;

    if( !isset( $wp_filter[$hook] ) )
        return false;

    foreach( $wp_filter[$hook] as $priority => $realhook ){

        foreach( $realhook as $hook_k => $hook_v ){

            if( is_array( $hook_v['function'] ) ){

                if( isset( $hook_v['function'][0], $hook_v['function'][1] ) && get_class( $hook_v['function'][0] ) == $className && $hook_v['function'][1] == $methodName ) {

                    return true;

                }
            }

        }

    }

    return false;
}
