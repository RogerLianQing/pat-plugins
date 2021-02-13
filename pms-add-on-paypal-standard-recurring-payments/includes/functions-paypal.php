<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Add extra supported features to the payment gateway
 *
 * @param array $supports
 *
 * @return array
 *
 */
function pms_ppsrp_add_payment_gateway_supports( $supports = array() ) {

    $supports[] = 'recurring_payments';

    return $supports;

}
add_filter( 'pms_payment_gateway_paypal_standard_supports', 'pms_ppsrp_add_payment_gateway_supports' );


/**
 * Add PayPal subscription payment to the types of payments
 *
 * @param array $types
 *
 * @return array
 *
 */
function pms_ppsrp_add_payment_types( $types = array() ) {

    $types['subscr_payment'] = __( 'PayPal Standard - Subscription Payment', 'paid-member-subscriptions' );

    return $types;

}
add_filter( 'pms_payment_types', 'pms_ppsrp_add_payment_types' );


/*
 * Modify the default payment type in PayPal Standard that is being saved in the database
 *
 */
function pms_ppsrp_change_payment_type( $payment_type, $gateway_object, $settings ) {

    if( $gateway_object->recurring == 1 )
        return 'subscr_payment';

    return $payment_type;

}
add_filter( 'pms_paypal_standard_payment_type', 'pms_ppsrp_change_payment_type', 10, 3 );


/*
 * Function that adds the recurring info to the payment data
 *
 */
function pms_ppsrp_register_payment_data( $payment_data, $payments_settings ) {

    // Unlimited plans cannot be recurring
    if( $payment_data['user_data']['subscription']->duration == 0 )
        return $payment_data;

    if( (isset( $_POST['pms_recurring'] ) && $_POST['pms_recurring'] == 1) || ( isset( $payments_settings['recurring'] ) && $payments_settings['recurring'] == 2 ) ) {
        $payment_data['recurring'] = 1;
    } else {
        $payment_data['recurring'] = 0;
    }

    return $payment_data;

}
//add_filter( 'pms_register_payment_data', 'pms_ppsrp_register_payment_data', 10, 2 );


/*
 * Returns an array with the API username, API password and API signature of the PayPal business account
 * if they all exist, if not will return false
 *
 * @return mixed array or bool false
 *
 */
if( !function_exists( 'pms_get_paypal_api_credentials' ) ) {

    function pms_get_paypal_api_credentials() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $pms_settings = get_option( 'pms_settings', false );
            $pms_settings = isset( $pms_settings['payments'] ) && isset( $pms_settings['payments']['gateways'] ) && isset( $pms_settings['payments']['gateways']['paypal'] ) ? $pms_settings['payments']['gateways']['paypal'] : false;
        } else {
            $pms_settings = get_option( 'pms_payments_settings', false );
            $pms_settings = isset( $pms_settings['gateways'] ) && isset( $pms_settings['gateways']['paypal'] ) ? $pms_settings['gateways']['paypal'] : false;
        }

        if( empty( $pms_settings ) )
            return false;

        if( pms_is_payment_test_mode() )
            $sandbox_prefix = 'test_';
        else
            $sandbox_prefix = '';

        $api_credentials = array(
            'username'  => $pms_settings[$sandbox_prefix . 'api_username'],
            'password'  => $pms_settings[$sandbox_prefix . 'api_password'],
            'signature' => $pms_settings[$sandbox_prefix . 'api_signature']
        );

        $api_credentials = array_map( 'trim', $api_credentials );

        if( count( array_filter($api_credentials) ) == count($api_credentials) )
            return $api_credentials;
        else
            return false;

    }
}


/*
 * Adds the value of the payment_profile_id received from the payment gateway in the database to a
 * users subscription information
 *
 */
if( !function_exists('pms_member_add_payment_profile_id') ) {
    function pms_member_add_payment_profile_id( $user_id = 0, $subscription_plan_id = 0, $payment_profile_id = '' ) {

        if( empty($user_id) || empty($subscription_plan_id) || empty($payment_profile_id) )
            return false;

        global $wpdb;

        $result = $wpdb->update( $wpdb->prefix . 'pms_member_subscriptions', array( 'payment_profile_id' => $payment_profile_id ), array( 'user_id' => $user_id, 'subscription_plan_id' => $subscription_plan_id ) );

        if( $result === false )
            return false;
        else
            return true;
    }
}


/*
 * Returns the value of the payment_profile_id of a member subscription if it exists
 *
 */
if( !function_exists('pms_member_get_payment_profile_id') ) {
    function pms_member_get_payment_profile_id( $user_id = 0, $subscription_plan_id = 0 ) {

        if( empty($user_id) || empty($subscription_plan_id) )
            return NULL;

        global $wpdb;

        $result = $wpdb->get_var( "SELECT payment_profile_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE user_id = {$user_id} AND subscription_plan_id = {$subscription_plan_id}" );

        // In case we do not find it, it could be located in the api failed canceling
        // errors
        if( is_null($result) ) {

            $api_failed_attempts = get_option( 'pms_api_failed_attempts', array() );

            if( isset( $api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id'] ) )
                $result = $api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id'];

        }

        return $result;

    }
}


/*
 * Checks to see if the payment profile id provided is one supported by
 * PayPal
 *
 * @param string $payment_profile_id
 *
 * @return bool
 *
 */
if( !function_exists('pms_is_paypal_payment_profile_id') ) {

    function pms_is_paypal_payment_profile_id( $payment_profile_id = '' ) {

        if( strpos( $payment_profile_id, 'I-' ) !== false )
            return true;
        else
            return false;

    }

}


/*
 * Function modifies the PayPal arguments so that instead of a direct payment,
 * we create a subscription for the user
 *
 */
function pms_ppsrp_paypal_args( $args, $gateway_object, $settings ) {

    // Return if the recurring option is not set or if the subscription plan
    // does not have a duration set
    if( $gateway_object->recurring != 1 || $gateway_object->subscription_plan->duration == 0 )
        return $args;

    // Modify PayPal args
    unset( $args['amount'] );

    // Add first trial period
    if( !is_null( $gateway_object->sign_up_amount ) ) {

        $args['a1'] = $gateway_object->sign_up_amount;
        $args['p1'] = $gateway_object->subscription_plan->duration;

        switch( $gateway_object->subscription_plan->duration_unit ) {

            case 'day':
                $args['t1'] = 'D';
                break;

            case 'week':
                $args['t1'] = 'W';
                break;

            case 'month':
                $args['t1'] = 'M';
                break;

            case 'year':
                $args['t1'] = 'Y';
                break;

        }

    }

    $args['cmd']    = '_xclick-subscriptions';
    $args['src']    = 1;
    $args['sra']    = 1;
    $args['a3']     = $gateway_object->amount;
    $args['p3']     = $gateway_object->subscription_plan->duration;

    switch( $gateway_object->subscription_plan->duration_unit ) {

        case 'day':
            $args['t3'] = 'D';
            break;

        case 'week':
            $args['t3'] = 'W';
            break;

        case 'month':
            $args['t3'] = 'M';
            break;

        case 'year':
            $args['t3'] = 'Y';
            break;

    }

    if ( isset( $gateway_object->sign_up_amount ) && $gateway_object->sign_up_amount == 0 ) {

        $payment_data = array(
            'user_id'         => $gateway_object->user_id,
            'subscription_id' => $gateway_object->subscription_data['subscription_plan_id']
        );

        pms_ppsrp_update_member_subscription_data( $payment_data, array() );

    }

    return $args;
}
add_filter( 'pms_paypal_standard_args', 'pms_ppsrp_paypal_args', 10, 3 );


/*
 * Function that processes the IPN sent by PayPal
 *
 */
function pms_ppsrp_ipn_listener( $payment_data, $post_data ) {

    $payment              = pms_get_payment( $payment_data['payment_id'] );
    $current_subscription = pms_get_current_subscription_from_tier( $payment_data['user_id'], $payment_data['subscription_id'] );

    if( $payment_data['type'] == 'subscr_payment' ) {

        if ( $payment_data['status'] == 'completed' ) {

            /*
             * Handle payment related information
             *
             * Website payment is not completed, so the IPN is for the current payment, letting us know what happened
             */
            if ( $payment->status != 'completed' ) {

                if ( method_exists( $payment, 'log_data') )
                    $payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );

                $payment->update(
                    array(
                        'status'         => $payment_data['status'],
                        'transaction_id' => $payment_data['transaction_id'],
                        'date'           => date( 'Y-m-d H:i:s', strtotime( $payment_data['date'] ) )
                    )
                );

                if( function_exists( 'pms_add_member_subscription_log' ) && !empty ( $current_subscription->id ) ){
                    pms_add_member_subscription_log( $current_subscription->id, 'paypal_subscription_setup' );
                    pms_add_member_subscription_log( $current_subscription->id, 'subscription_activated' );
                }

                /*
                 * Handle member related information
                 */
                pms_ppsrp_update_member_subscription_data( $payment_data, $post_data );

            }

            // Website payment is completed, so this IPN is for a new payment that we need to register
            if ( $payment->status == 'completed' && $payment->transaction_id != $payment_data['transaction_id'] && $payment->date != date( 'Y-m-d H:i:s', strtotime( $payment_data['date'] ) ) ) {

                if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.9.5' , '>=') ) {
                    // Make sure we don't have another payment with this transaction id
                    $old_payments = pms_get_payments( array( 'transaction_id' => $payment_data['transaction_id'] ) );

                    if( !empty( $old_payments ) && !empty( $old_payments[0]->transaction_id ) )
                        return;
                }


                $new_payment = new PMS_Payment();

                $new_payment->insert( array(
                    'user_id'              => $payment_data['user_id'],
                    'amount'               => $payment_data['amount'],
                    'subscription_plan_id' => $payment_data['subscription_id'],
                    'status'               => $payment_data['status'],
                    'payment_gateway'      => 'paypal_standard'
                ) );

                if ( method_exists( $new_payment, 'log_data' ) ){
                    $new_payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );
                    $new_payment->log_data( 'new_payment', array( 'user' => -1, 'data' => $payment_data ) );
                }

                $new_payment->update( array( 'type' => $payment_data['type'], 'transaction_id' => $payment_data['transaction_id'] ) );

                if( function_exists( 'pms_add_member_subscription_log' ) && !empty ( $current_subscription->id ) )
                    pms_add_member_subscription_log( $current_subscription->id, 'subscription_renewed_automatically' );

                /*
                 * Handle member related information
                 */
                pms_ppsrp_update_member_subscription_data( $payment_data, $post_data );

            }

        }

    } elseif( $payment_data['type'] == 'subscr_signup' ) {

        if( !isset( $post_data['amount1'] ) )
            return;

        if ( method_exists( $payment, 'log_data' ) )
            $payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );

        $post_data['amount1'] = (float)$post_data['amount1'];

        if( isset( $post_data['amount1'] ) && empty( $post_data['amount1'] ) ) {

            /*
             * Handle payment related information
             */
            if ( $payment->status != 'completed' )
                $payment->update( array('status' => 'completed', 'transaction_id' => '-', 'date' => date( 'Y-m-d H:i:s', strtotime( $post_data['subscr_date'] ) ) ) );

            /*
             * Handle member related information
             */
            pms_ppsrp_update_member_subscription_data($payment_data, $post_data);

        }

    } elseif( $payment_data['type'] == 'subscr_cancel' ) {

        $member = pms_get_member( $payment_data['user_id'] );
        $member_subscription = $member->get_subscription( $payment_data['subscription_id'] );

        if( $member_subscription['status'] != 'canceled' ){
            $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $member_subscription['expiration_date'], 'canceled' );

            if( function_exists( 'pms_add_member_subscription_log' ) && !empty( $current_subscription->id ) )
                pms_add_member_subscription_log( $current_subscription->id, 'gateway_subscription_canceled' );
        }

    }

}
add_action( 'pms_paypal_ipn_listener_verified', 'pms_ppsrp_ipn_listener', 10, 2 );


/*
 * Updates the member data
 *
 */
function pms_ppsrp_update_member_subscription_data( $payment_data, $post_data ) {

    if( empty( $payment_data ) || !is_array( $payment_data ) )
        return;

    if( empty( $post_data ) )
        $post_data = $_POST;

    // Get all member subscriptions
    $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $payment_data['user_id'] ) );

    foreach( $member_subscriptions as $member_subscription ) {

        if( $member_subscription->subscription_plan_id != $payment_data['subscription_id'] )
            continue;

        $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

        // If subscription is pending it is a new one
        if( $member_subscription->status == 'pending' ) {
            $member_subscription_expiration_date = pms_sanitize_date( $subscription_plan->get_expiration_date() ) . ' 23:59:59';

        // This is an old subscription
        } else {

            if( strtotime( $member_subscription->expiration_date ) < time() || $subscription_plan->duration === 0 )
                $member_subscription_expiration_date = pms_sanitize_date( $subscription_plan->get_expiration_date() ) . ' 23:59:59';
            else
                $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );

        }

        // Update subscription
        $member_subscription->update( array(
            'expiration_date'       => $member_subscription_expiration_date,
            'status'                => 'active',
            'payment_profile_id'    => ( ! empty( $post_data['subscr_id'] ) ? $post_data['subscr_id'] : '' ),
            'payment_gateway'       => 'paypal_standard',
            // reset custom schedule
            'billing_amount'        => '',
            'billing_duration'      => '',
            'billing_duration_unit' => '',
            'billing_next_payment'  => ''
        ));

        do_action( 'pms_paypal_subscr_payment_after_subscription_activation', $member_subscription, $payment_data, $post_data );

        break;
    }


    /*
     * If the subscription plan id sent by the IPN is not found in the members subscriptions
     * then it could be an update to an existing one
     *
     * If one of the member subscriptions is in the same group as the payment subscription id,
     * the payment subscription id is an upgrade to the member subscription one
     *
     */
     $current_subscription = pms_get_current_subscription_from_tier( $payment_data['user_id'], $payment_data['subscription_id'] );

     if( !empty( $current_subscription ) && $current_subscription->subscription_plan_id != $payment_data['subscription_id'] ) {

         do_action( 'pms_paypal_subscr_payment_before_upgrade_subscription', $current_subscription->subscription_plan_id, $payment_data, $post_data );

         $old_plan_id = $current_subscription->subscription_plan_id;

         $new_subscription_plan = pms_get_subscription_plan( $payment_data['subscription_id'] );

         $subscription_data = array(
             'user_id'              => $payment_data['user_id'],
             'subscription_plan_id' => $new_subscription_plan->id,
             'start_date'           => date( 'Y-m-d H:i:s' ),
             'expiration_date'      => pms_sanitize_date( $new_subscription_plan->get_expiration_date() ) . ' 23:59:59',
             'status'               => 'active',
             'payment_profile_id'   => ( ! empty( $post_data['subscr_id'] ) ? $post_data['subscr_id'] : '' ),
             'payment_gateway'       => 'paypal_standard',
             // reset custom schedule
             'billing_amount'        => '',
             'billing_duration'      => '',
             'billing_duration_unit' => '',
             'billing_next_payment'  => ''
         );

         $current_subscription->update( $subscription_data );

         if( function_exists( 'pms_add_member_subscription_log' ) )
            pms_add_member_subscription_log( $current_subscription->id, 'subscription_upgrade_success', array( 'old_plan' => $old_plan_id, 'new_plan' => $new_subscription_plan->id ) );

         do_action( 'pms_paypal_subscr_payment_after_upgrade_subscription', $new_subscription_plan->id, $payment_data, $post_data );
     }

}


/*
 * Makes an API call to PayPal to change the status of a subscription profile
 * to cancel
 *
 * @param string $payment_profile_id - profile that we want to cancel
 * @param string $action - whether we call this action when cancelling a subscription or when upgrading one
 *
 */
function pms_api_cancel_paypal_subscription( $payment_profile_id, $action = 'cancel', $cancel_reason = '' ) {

    $confirmation = false;
    $error        = NULL;

    // Get API credentials and check if they are complete
    $api_credentials = pms_get_paypal_api_credentials();

    if( !$api_credentials ){
        $error = __( 'PayPal API credentials are missing or are incomplete', 'paid-member-subscriptions' );
        return array( 'error' => $error );
    }

    // Get payment_profile_id
    if( empty( $payment_profile_id ) ){
        $error = __( 'Payment profile ID is empty, nothing to cancel.', 'paid-member-subscriptions' );
        return array( 'error' => $error );
    }

    // Set API endpoint
    if( pms_is_payment_test_mode() )
        $api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
    else
        $api_endpoint = 'https://api-3t.paypal.com/nvp';

    //PayPal API arguments
    $args = array(
        'USER'      => $api_credentials['username'],
        'PWD'       => $api_credentials['password'],
        'SIGNATURE' => $api_credentials['signature'],
        'VERSION'   => '76.0',
        'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
        'PROFILEID' => $payment_profile_id,
        'ACTION'    => 'Cancel'
    );

    if( !empty( $cancel_reason ) )
        $args['NOTE'] = $cancel_reason;

    $request   = wp_remote_post( $api_endpoint, array( 'body' => $args, 'timeout' => 30 ) );
    $body      = wp_remote_retrieve_body( $request );


    // Error handling
    if( is_wp_error( $request ) ) {

        $error = $request->get_error_message();

    } else {

        if( is_string( $body ) )
            wp_parse_str( $body, $body );

        if( !isset($request['response']) || empty( $request['response'] ) )
            $error = __( 'No request response received.', 'paid-member-subscriptions' );
        else {

            if( isset( $request['response']['code'] ) && (int)$request['response']['code'] != 200 )
                $error = $request['response']['code'] . ( isset( $request['response']['message'] ) ? ' : ' . $request['response']['message'] : '' );

        }

        if( isset( $body['L_LONGMESSAGE0'] ) )
            $error = $body['L_LONGMESSAGE0'];

        if( isset( $body['ACK'] ) && strtolower( $body['ACK'] ) === 'success' )
            $confirmation = true;

    }

    $subscription = pms_ppsrp_get_subscription_by_payment_profile( $payment_profile_id );

    if( !empty( $subscription ) )
        do_action( 'pms_api_cancel_paypal_subscription_before_return', $subscription->user_id, $subscription->subscription_plan_id, $action, $confirmation, $error );


    // If all is good return true, if not return the error
    if( $confirmation && is_null($error) )
        return true;
    else
        return array( 'error' => $error );


}
add_action( 'pms_api_cancel_paypal_subscription', 'pms_api_cancel_paypal_subscription', 10, 3 );


/*
 * Cancels the existing PayPal subscription of the member,
 * but if for some reason the cancellation did not happen a cron job is added to
 * try to cancel the subscription once every hour
 *
 * @param int $member_subscription_id
 * @param array $payment_data
 * @param array $post_data
 *
 */
function pms_cancel_paypal_subscription_before_upgrade( $member_subscription_id, $payment_data, $post_data ) {

    $user_id              = $payment_data['user_id'];
    $subscription_plan_id = $member_subscription_id;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( empty( $payment_profile_id ) || !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return;

    // Execute a profile cancellation api call to PayPal
    $cancel_result = pms_api_cancel_paypal_subscription( $payment_profile_id, 'upgrade', 'Subscription canceled because user upgraded to another one.');

    // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
    if( isset( $cancel_result['error'] ) && wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'upgrade' ) ) == false && pms_get_paypal_api_credentials() != false )
        wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'upgrade' ) );

}
add_action( 'pms_paypal_web_accept_before_upgrade_subscription', 'pms_cancel_paypal_subscription_before_upgrade', 10, 3 );
add_action( 'pms_paypal_subscr_payment_before_upgrade_subscription', 'pms_cancel_paypal_subscription_before_upgrade', 10, 3 );


/*
 * Hooks to 'pms_api_cancel_paypal_subscription_before_return' action to clear the scheduled cron job,
 * if successfully cancelled the payment profile in PayPal
 *
 */
function pms_api_cancel_paypal_subscription_upgrade_action( $user_id, $subscription_plan_id, $action, $confirmation, $error ) {

    if( !in_array( $action, array( 'upgrade', 'renew' ) ) )
        return;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    // Get failed attempts
    $api_failed_attempts = get_option( 'pms_api_failed_attempts', array() );

    if( !is_array( $api_failed_attempts ) )
        $api_failed_attempts = array();

    // If all is good clear the schedule
    if( $confirmation && is_null($error) ) {

        // Removed information
        if( isset( $api_failed_attempts[$user_id][$subscription_plan_id] ) )
            unset( $api_failed_attempts[$user_id][$subscription_plan_id] );

        // Clear schedule if it exists
        if( wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, $action ) ) )
            wp_clear_scheduled_hook( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, $action ) );

        update_option( 'pms_api_failed_attempts', $api_failed_attempts, false );

        do_action( 'pms_api_cancel_paypal_subscription_upgrade_successful', $user_id, $subscription_plan_id, $action, $confirmation, $error );

    } else {

        // Add the retry to the list
        $api_failed_attempts[$user_id][$subscription_plan_id]['retries'][] = array(
            'time'  => time(),
            'error' => $error
        );

        // Increment retry count
        if( !isset($api_failed_attempts[$user_id][$subscription_plan_id]['retry_count']) )
            $api_failed_attempts[$user_id][$subscription_plan_id]['retry_count'] = 1;
        else
            $api_failed_attempts[$user_id][$subscription_plan_id]['retry_count']++;

        // Add the payment profile id
        if( !isset($api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id']) )
            $api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id'] = $payment_profile_id;

        update_option( 'pms_api_failed_attempts', $api_failed_attempts, false );


        do_action( 'pms_api_cancel_paypal_subscription_upgrade_unsuccessful', $user_id, $subscription_plan_id, $action, $confirmation, $error );

    }

}
add_action( 'pms_api_cancel_paypal_subscription_before_return', 'pms_api_cancel_paypal_subscription_upgrade_action', 10, 5 );


/*
 * Hooks to 'pms_confirm_cancel_subscription' from PMS to change the default value provided
 * Makes an api call to PayPal to cancel the subscription, if is successful returns returns true,
 * but if not returns an array with 'error'
 *
 * @param $confirmation
 * @param int $user_id
 * @param int $subscription_plan_id
 *
 * @return mixed    - bool true if successful, array if not
 *
 */
function pms_ppsrp_confirm_cancel_subscription( $confirmation, $user_id, $subscription_plan_id ) {

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return $confirmation;

    return pms_api_cancel_paypal_subscription( $payment_profile_id, 'cancel', 'Subscription canceled by user from [pms-account].' );

}
add_filter( 'pms_confirm_cancel_subscription', 'pms_ppsrp_confirm_cancel_subscription', 10, 3 );


/*
 * Hide the Renew subscription button from the account shortcode for each
 * member subscription that has a payment profile id saved and is not canceled or expired
 *
 * @param $output               - the current output for the renew button
 * @param $subscription_plan
 * @param $member_subscription
 * @param $user_id              - the member user id
 *
 * @return string
 *
 */
function pms_ppsrp_output_subscription_plan_action_renewal( $output, $subscription_plan, $member_subscription, $user_id ) {
    // If subscription is Canceled or Expired, show the renew button
    if ( in_array( $member_subscription['status'], array( 'canceled', 'expired' ) ) )
        return $output;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $member_subscription['subscription_plan_id'] );

    if( $payment_profile_id )
        return '';

    return $output;
}
add_filter( 'pms_output_subscription_plan_action_renewal', 'pms_ppsrp_output_subscription_plan_action_renewal', 10, 4 );

add_action( 'pms_process_checkout_validations', 'pms_ppsrp_cancel_subscription_before_renew' );
function pms_ppsrp_cancel_subscription_before_renew() {

    $form_location = PMS_Form_Handler::get_request_form_location();

    if ( $form_location != 'renew_subscription' )
        return;

    $user_data            = PMS_Form_Handler::get_request_member_data();
    $user_id              = $user_data['user_id'];
    $subscription_plan_id = $user_data['subscriptions'][0];

    if ( $user_id == 0 || empty( $subscription_plan_id ) )
        return;

    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( empty( $payment_profile_id ) || !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return;

    // Execute a profile cancellation api call to PayPal
    $cancel_result = pms_api_cancel_paypal_subscription( $payment_profile_id, 'renew', 'Subscription canceled because user manually renewed his subscription.' );

    // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
    if( isset( $cancel_result['error'] ) && wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'renew' ) ) == false && pms_get_paypal_api_credentials() != false )
        wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'renew' ) );
}

/**
 * Cancel PayPal subscription when an admin deletes the subscription from the back-end
 * @param  int   $subscription_id ID of the subscription that was just deleted
 * @param  array $data            Subscription data before deletion
 * @return void
 */
function pms_ppsrp_cancel_subscription_on_admin_deletion( $subscription_id, $data ){

    if( !is_admin() )
        return;

    if( empty( $data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $data['payment_profile_id'] ) )
        return;

    // Execute a profile cancellation api call to PayPal
    pms_api_cancel_paypal_subscription( $data['payment_profile_id'], 'cancel', 'Subscription canceled because an admin deleted the members subscription.' );

}
add_action( 'pms_member_subscription_delete', 'pms_ppsrp_cancel_subscription_on_admin_deletion', 10, 2 );

/**
 * Cancel PayPal subscription when the status is changed to Canceled while in the back-end interface
 * This usually means the user was deleted from the website, but it could also mean an admin changed
 * the status to canceled from the back-end interface
 *
 * @param  int   $subscription_id ID of the subscription that was just edited
 * @param  array $data            Subscription data that was changed
 * @param  array $old_data        Old Subscription data
 * @return void
 */
function pms_ppsrp_cancel_subscription_on_api_subscription_cancelation( $id, $data, $old_data ){

    if( !is_admin() )
        return;

    if( empty( $old_data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $old_data['payment_profile_id'] ) )
        return;

    if( !empty( $data['status'] ) && $data['status'] == 'canceled' && $data['status'] != $old_data )
        pms_api_cancel_paypal_subscription( $old_data['payment_profile_id'], 'Subscription canceled because an admin deleted the user from the website.' );

}
add_action( 'pms_member_subscription_update', 'pms_ppsrp_cancel_subscription_on_api_subscription_cancelation', 20, 3 );

/*
 * Display a warning to the administrators if the API credentials are missing in the
 * register page
 *
 */
if( !function_exists( 'pms_paypal_api_credentials_admin_warning' ) ) {

    function pms_paypal_api_credentials_admin_warning() {

        if( !current_user_can( 'manage_options' ) )
            return;

        $are_active = array_intersect( array( 'paypal_standard' ), pms_get_active_payment_gateways() );

        if( pms_get_paypal_api_credentials() == false && !empty( $are_active ) ) {

            echo '<div class="pms-warning-message-wrapper">';
                echo '<p>' . sprintf( __( 'Your <strong>PayPal API credentials</strong> are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' ) . '</p>';
                echo '<p><em>' . __( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
            echo '</div>';

        }

    }
    add_action( 'pms_register_form_top', 'pms_paypal_api_credentials_admin_warning' );
    add_action( 'pms_new_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
    add_action( 'pms_upgrade_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
    add_action( 'pms_renew_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
    add_action( 'pms_retry_payment_form_top', 'pms_paypal_api_credentials_admin_warning' );

}

if( !function_exists( 'pms_wppb_paypal_api_credentials_admin_warning' ) ) {

    function pms_wppb_paypal_api_credentials_admin_warning() {

        if( !current_user_can( 'manage_options' ) )
            return;

        $fields = get_option( 'wppb_manage_fields' );

        if ( empty( $fields ) )
            return;

        $are_active = array_intersect( array( 'paypal_standard' ), pms_get_active_payment_gateways() );

        foreach( $fields as $field ) {
            if ( $field['field'] == 'Subscription Plans' && !empty( $are_active ) && pms_get_paypal_api_credentials() === false ) {
                echo '<div class="pms-warning-message-wrapper">';
                    echo '<p>' . sprintf( __( 'Your <strong>PayPal API credentials</strong> are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' ) . '</p>';
                    echo '<p><em>' . __( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
                echo '</div>';

                break;
            }
        }

    }
    add_action( 'wppb_before_register_fields', 'pms_wppb_paypal_api_credentials_admin_warning' );
}

/**
 * Added for backwards compatibility. Will be removed in the future.
 */
if( !function_exists( 'pms_get_current_subscription_from_tier' ) ) {

    function pms_get_current_subscription_from_tier( $user_id, $plan_id ) {

        if( empty( $user_id ) || empty( $plan_id ) )
            return false;

        $subscription_plans_tier = pms_get_subscription_plans_group( $plan_id );
        $possible_values         = array();

        foreach( $subscription_plans_tier as $plan )
            $possible_values[] = $plan->id;

        $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

        if( empty( $subscriptions ) )
            return false;

        foreach( $subscriptions as $subscription ){
            if( in_array( $subscription->subscription_plan_id, $possible_values ) )
                return $subscription;
        }

        return false;

    }

}

add_filter( 'pms_subscription_logs_system_error_messages', 'pms_paypal_add_subscription_log_messages', 20, 2 );
function pms_paypal_add_subscription_log_messages( $message, $log ){
    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'paypal_subscription_setup':
            $message = __( 'Subscription setup successfully with PayPal.', 'paid-member-subscriptions' );
            break;
    }

    return $message;

}

function pms_ppsrp_get_subscription_by_payment_profile( $payment_profile_id ) {

    global $wpdb;

    $result = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pms_member_subscriptions WHERE payment_profile_id = '{$payment_profile_id}'" );

    if( !empty( $result ) )
        return pms_get_member_subscription( $result );

    return false;

}
