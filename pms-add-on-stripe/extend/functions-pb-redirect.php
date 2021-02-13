<?php

/**
 * Profile Builder compatibility functions for redirects
 *
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/*
 * This is the redirect for Stripe
 * If PB doesn't have any redirects in place, we're going to redirect to the PMS default success page
 *
 */
function pms_stripe_pb_register_redirect_plugins_loaded() {

    /**
     * Change PB's ( until PB version 2.5.5 ) default success message with a custom one when a payment has been made
     *
     * This function is compatible with Profile Builder until version 2.5.5. In version 2.5.6 of Profile Builder
     * a refactoring for the redirects has been made and some hooks have been removed / modified, one of them being
     * the "wppb_register_redirect" filter, making this callback incompatible with newer versions of PB
     *
     */
    if( ! function_exists( 'wppb_build_redirect' ) ) {

        function pms_stripe_pb_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'stripe' ) )
                return $redirect_link;

            if ( empty( $redirect_link ) ) {

                if ( function_exists( 'pms_get_register_success_url' ) )
                    $url = pms_get_register_success_url();
                else {
                    $pms_settings = get_option('pms_settings');
                    $url = ( isset( $pms_settings['general']['register_success_page'] ) && $pms_settings['general']['register_success_page'] != -1 ? get_permalink( trim( $pms_settings['general']['register_success_page'] ) ) : '' );
                }

                if( empty( $url ) )
                    return '';

                $message = sprintf( __( 'You will soon be redirected automatically. If you see this page for more than 5 seconds, please click <a href="%1$s">here</a>', 'paid-member-subscriptions' ), $url );

                $redirect_link = sprintf(
                    '<p class="redirect_message">%1$s <meta http-equiv="Refresh" content="5;url=%2$s" /></p>',
                    $message,
                    $url
                );

                return $redirect_link;
            }

            return $redirect_link;

        }
        add_filter( 'wppb_register_redirect', 'pms_stripe_pb_register_redirect_link', 100 );

    }


    /**
     * Change PB's ( PB version 2.5.6 and higher ) default success message with a custom one when a payment has been made
     *
     */
    if( function_exists( 'wppb_build_redirect' ) ) {

        function pms_stripe_pb_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'stripe' ) )
                return $redirect_link;

            if ( empty( $redirect_link ) ) {

                if ( function_exists( 'pms_get_register_success_url' ) )
                    $url = pms_get_register_success_url();
                else {
                    $pms_settings = get_option('pms_settings');
                    $url = ( isset( $pms_settings['general']['register_success_page'] ) && $pms_settings['general']['register_success_page'] != -1 ? get_permalink( trim( $pms_settings['general']['register_success_page'] ) ) : '' );
                }

                return $url;
            }

            return $redirect_link;

        }
        add_filter( 'wppb_register_redirect', 'pms_stripe_pb_register_redirect_link', 100 );

    }

}
add_action( 'plugins_loaded', 'pms_stripe_pb_register_redirect_plugins_loaded', 11 );
