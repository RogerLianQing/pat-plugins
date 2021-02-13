<?php

/*
 * Profile Builder compatibility functions for redirects
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/*
 * Handle the redirect to PayPal from the saved transient from the Express Checkout page
 * Also, we change the default
 *
 */
function pms_pb_paypal_express_payment_redirect_link() {

    if( ! isset( $_GET['pmstkn'] ) || ! wp_verify_nonce( $_GET['pmstkn'], 'pms_payment_redirect_link') )
        return;

    $redirect_to = '';

    // If payment_id is present
    if ( ! empty( $_GET['pms_payment_id'] ) ) {

        $payment_id = (int)$_GET['pms_payment_id'];

        $redirect_to = get_transient( 'pms_pb_pp_redirect_' . $payment_id );

    }

    // If user_id and subscription_plan_id are present
    if ( ! empty( $_GET['pms_user_id'] ) && ! empty( $_GET['pms_subscription_plan_id'] ) ) {

        $user_id = (int)$_GET['pms_user_id'];
        $subscription_plan_id = (int)$_GET['pms_subscription_plan_id'];

        $redirect_to = get_transient( 'pms_pb_pp_redirect_' . $user_id . '_' . $subscription_plan_id );

    }

    // Redirect
    if( ! empty( $redirect_to ) ) {

        header( 'Location:' . $redirect_to );
        exit;

    }

}
add_action( 'init', 'pms_pb_paypal_express_payment_redirect_link' );


/*
 * Because redirects happen later and are handled with JS we will save the PayPal link in a transient
 * for security reasons. In the end we will refresh the current page and handle the redirect to PayPal on init
 * with the value we save in this transient
 *
 */
function pms_pb_before_express_checkout_redirect( $redirect = '', $member_subscription_id = 0, $payment_id = 0 ) {

    if( ! empty( $payment_id ) )
        set_transient( 'pms_pb_pp_redirect_' . $payment_id, $redirect, DAY_IN_SECONDS );

    elseif( ! empty( $member_subscription_id ) && function_exists( 'pms_get_member_subscription' ) ) {

        $subscription = pms_get_member_subscription( $member_subscription_id );

        if( ! is_null( $subscription ) ) {

            set_transient( 'pms_pb_pp_redirect_' . $subscription->user_id . '_' . $subscription->subscription_plan_id, $redirect, DAY_IN_SECONDS );

        }

    }

}
add_action( 'pms_set_express_checkout_redirect', 'pms_pb_before_express_checkout_redirect', 99, 3 );


/*
 * This is the redirect for PayPal Pro
 * If PB doesn't have any redirects in place, we're going to redirect to the PMS default success page
 *
 */
function pms_pb_paypal_pro_register_redirect_plugins_loaded() {

    /**
     * Change PB's ( until PB version 2.5.5 ) default success message with a custom one when a payment has been made
     *
     * This function is compatible with Profile Builder until version 2.5.5. In version 2.5.6 of Profile Builder
     * a refactoring for the redirects has been made and some hooks have been removed / modified, one of them being
     * the "wppb_register_redirect" filter, making this callback incompatible with newer versions of PB
     *
     */
    if( ! function_exists( 'wppb_build_redirect' ) ) {

        function pms_paypal_pro_pb_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_pro' ) )
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
        add_filter( 'wppb_register_redirect', 'pms_paypal_pro_pb_register_redirect_link', 100 );

    }


    /**
     * Change PB's ( PB version 2.5.6 and higher ) default success message with a custom one when a payment has been made
     *
     */
    if( function_exists( 'wppb_build_redirect' ) ) {

        function pms_pb_paypal_pro_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_pro' ) )
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

                return $url;
            }

            return $redirect_link;

        }
        add_filter( 'wppb_register_redirect', 'pms_pb_paypal_pro_register_redirect_link', 100 );

    }

}
add_action( 'plugins_loaded', 'pms_pb_paypal_pro_register_redirect_plugins_loaded', 11 );



function pms_pb_paypal_express_register_redirect_plugins_loaded() {

    /**
     * Change PB's ( until PB version 2.5.5 ) default success message with a custom one when a payment has been made
     *
     * This function is compatible with Profile Builder until version 2.5.5. In version 2.5.6 of Profile Builder
     * a refactoring for the redirects has been made and some hooks have been removed / modified, one of them being
     * the "wppb_register_redirect" filter, making this callback incompatible with newer versions of PB
     *
     */
    if( ! function_exists( 'wppb_build_redirect' ) ) {

        /*
         * This is the redirect for PayPal Express that redirects the user to PayPal
         *
         */
        function pms_paypal_express_pb_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_express' ) )
                return $redirect_link;

            // Scrap the redirect URL from the whole redirect message
            $link = pms_pb_scrap_register_redirect_link( $redirect_link );

            if ( empty( $redirect_link ) || !empty($link) ) {

                // save in transient
                set_transient('pms_pb_pp_redirect_back_' . $pms_gateway_data['payment_id'], $link, DAY_IN_SECONDS );

                $redirect_link = sprintf(
                    '<p class="redirect_message">%1$s <meta http-equiv="Refresh" content="3;url=%2$s" /></p>',
                    __( 'You will soon be redirected to complete the payment.', 'paid-member-subscriptions' ),
                    wp_nonce_url( add_query_arg( array( 'pms_payment_id' => $pms_gateway_data['payment_id'] ), pms_get_current_page_url() ), 'pms_payment_redirect_link', 'pmstkn' )
                );

                return $redirect_link;
            }

            return $redirect_link;

        }
        add_filter( 'wppb_register_redirect', 'pms_paypal_express_pb_register_redirect_link', 100 );

    }


    /**
     * Change PB's ( PB version 2.5.6 and higher ) default success message with a custom one when a payment has been made
     *
     */
    if( function_exists( 'wppb_build_redirect' ) ) {

        /**
         * Change the redirect link
         *
         */
        function pms_pb_paypal_express_register_redirect_link( $redirect_link ) {

            global $pms_gateway_data;

            if( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_express' )
                return $redirect_link;

            // Save the redirect link in a transient
            if( ! empty( $pms_gateway_data['payment_id'] ) ) {

                set_transient( 'pms_pb_pp_redirect_back_' . $pms_gateway_data['payment_id'], $redirect_link, DAY_IN_SECONDS );
                $redirect_link = wp_nonce_url( add_query_arg( array( 'pms_payment_id' => $pms_gateway_data['payment_id'] ), pms_get_current_page_url() ), 'pms_payment_redirect_link', 'pmstkn' );

            } else {

                if( ! empty( $pms_gateway_data['user_id'] ) && ! empty( $pms_gateway_data['subscription_plan_id'] ) ) {

                    set_transient( 'pms_pb_pp_redirect_back_' . $pms_gateway_data['user_id'] . '_' . $pms_gateway_data['subscription_plan_id'], $redirect_link, DAY_IN_SECONDS );
                    $redirect_link = wp_nonce_url( add_query_arg( array( 'pms_user_id' => ( ! empty( $pms_gateway_data['user_id'] ) ? $pms_gateway_data['user_id'] : '' ), 'pms_subscription_plan_id' => ( ! empty( $pms_gateway_data['subscription_plan_id'] ) ? $pms_gateway_data['subscription_plan_id'] : '' ) ), pms_get_current_page_url() ), 'pms_payment_redirect_link', 'pmstkn' );

                }

            }

            return $redirect_link;

        }
        add_filter( 'wppb_register_redirect', 'pms_pb_paypal_express_register_redirect_link', 100 );

        /**
         * Remove PB's default redirect message, but keep the refresh meta element
         *
         */
        function pms_pb_paypal_express_remove_redirect_message( $message, $redirect_url, $redirect_delay, $redirect_url_href, $redirect_type, $form_args ) {

            global $pms_gateway_data;

            if( ! isset( $pms_gateway_data['payment_id'] ) || isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_express' )
                return $message;

                //we are doing a <meta> tag redirect below
                //if thie number is not set in front of the URL under the content attribute certain browsers do not redirect
                if ( empty( $redirect_delay ) || !is_numeric( $redirect_delay ) )
                    $redirect_delay = 0;

                /**
                 * Add a parameter to the redirect URL if autologin is enabled for this form
                 *
                 * @since 1.3.9
                 */
                if( isset( $form_args['login_after_register'] ) && $form_args['login_after_register'] == 'Yes' )
                    $redirect_url = add_query_arg( 'pms_autologin_before_redirect', 'true', $redirect_url );

                $message = '<meta http-equiv="Refresh" content="'. $redirect_delay .';url='. $redirect_url .'" />';

                $message .= '<p>' . __( 'You are being redirected to PayPal to complete the payment...', 'paid-member-subscriptions' ) . '<br>';
                $message .= sprintf( __( '%sClick here%s to go now.', 'paid-member-subscriptions' ), '<a href="'.$redirect_url.'">', '</a>' ) . '</p>';

                return $message;

        }
        add_filter( 'wppb_redirect_message_before_returning', 'pms_pb_paypal_express_remove_redirect_message', 10, 6 );

    }

}
add_action( 'plugins_loaded', 'pms_pb_paypal_express_register_redirect_plugins_loaded', 11 );
