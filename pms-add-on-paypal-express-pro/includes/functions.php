<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/*
 * Add PayPal Express Checkout to the payment gateways array
 *
 */
function pms_payment_gateways_paypal_express( $payment_gateways ) {

    $payment_gateways['paypal_express'] = array(
        'display_name_user' => 'PayPal',
        'display_name_admin'=> 'PayPal Express Checkout',
        'class_name'        => 'PMS_Payment_Gateway_PayPal_Express'
    );

    return $payment_gateways;

}
add_filter( 'pms_payment_gateways', 'pms_payment_gateways_paypal_express' );


/*
 * Add PayPal Pro to the payment gateways array
 *
 */
function pms_payment_gateways_paypal_pro( $payment_gateways ) {

    $payment_gateways['paypal_pro'] = array(
        'display_name_user' => __( 'Credit / Debit Card', 'paid-member-subscriptions' ),
        'display_name_admin'=> 'PayPal Payments Pro',
        'class_name'        => 'PMS_Payment_Gateway_PayPal_Pro'
    );

    return $payment_gateways;

}
add_filter( 'pms_payment_gateways', 'pms_payment_gateways_paypal_pro' );


/*
 * Add data-type="credit_card" attribute to the pay_gate hidden and radio input for PayPal Pro
 *
 */
function pms_payment_gateway_input_data_type_paypal_pro( $value, $payment_gateway ) {

    if( $payment_gateway == 'paypal_pro' ) {
        $value = str_replace( '/>', 'data-type="credit_card" />', $value );
    }

    return $value;

}
add_filter( 'pms_output_payment_gateway_input_radio', 'pms_payment_gateway_input_data_type_paypal_pro', 10, 2 );
add_filter( 'pms_output_payment_gateway_input_hidden', 'pms_payment_gateway_input_data_type_paypal_pro', 10, 2 );


/*
 * Add payment types for PayPal Express Checkout
 */
function pms_payment_types_paypal_express( $types ) {

    $types['recurring_payment_profile_created'] = __( 'PayPal Recurring Initial Payment', 'paid-member-subscriptions' );
    $types['expresscheckout']                   = __( 'PayPal Express - Checkout Payment', 'paid-member-subscriptions' );
    $types['recurring_payment']                 = __( 'PayPal Recurring Payment', 'paid-member-subscriptions' );
    $types['web_accept_paypal_pro']             = __( 'PayPal Pro - Direct Payment', 'paid-member-subscriptions');

    return $types;

}
add_filter( 'pms_payment_types', 'pms_payment_types_paypal_express' );


/*
* Function that validates the entered credit card number
*
* @returns : false is cc invalid, card type if cc is valid
*
*/

function pms_validate_cc_number($number) {

    // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
    $number=preg_replace('/\D/', '', $number);

    /* Validate; return value is card type if valid. */
    $card_type = "";
    $card_regexes = array(
        "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
        "/^5[12345]\d{14}$/"       => "mastercard",
        "/^3[47]\d{13}$/"          => "amex",
        "/^6011\d{12}$/"           => "discover",
        "/^30[012345]\d{11}$/"     => "diners",
        "/^3[68]\d{12}$/"          => "diners",
    );

    foreach ($card_regexes as $regex => $type) {
        if (preg_match($regex, $number)) {
            $card_type = $type;
            break;
        }
    }

    if (!$card_type) {
        return false;
    }

    /*  mod 10 checksum algorithm  */
    $revcode = strrev($number);
    $checksum = 0;

    for ($i = 0; $i < strlen($revcode); $i++) {

        $current_num = intval($revcode[$i]);
        if($i & 1) {  /* Odd  position */
            $current_num *= 2;
        }

        /* Split digits and add. */
        $checksum += $current_num % 10;
        if ($current_num >  9) {
            $checksum += 1;
        }
    }

    if ($checksum % 10 == 0) {
        return $card_type;
    } else {
        return false;
    }

}



/*
 * Function that adds the recurring info to the payment data
 *
 */
if( !function_exists( 'pms_recurring_register_payment_data' ) ) {

    function pms_recurring_register_payment_data( $payment_data, $payments_settings ) {

        // Unlimited plans cannot be recurring
        if( $payment_data['user_data']['subscription']->duration == 0 )
            return $payment_data;

        // Handle recurring
        if( (isset( $_POST['pms_recurring'] ) && $_POST['pms_recurring'] == 1) || ( isset( $payments_settings['recurring'] ) && $payments_settings['recurring'] == 2 ) ) {
            $payment_data['recurring'] = 1;
        } else {
            $payment_data['recurring'] = 0;
        }

        return $payment_data;

    }
    //add_filter( 'pms_register_payment_data', 'pms_recurring_register_payment_data', 10, 2 );

}


/**
 * Returns an array with the API username, API password and API signature of the PayPal business account
 * if they all exist, if not will return false
 *
 * @return mixed array or bool false
 *
 */
if( !function_exists( 'pms_get_paypal_api_credentials' ) ) {

    function pms_get_paypal_api_credentials() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $pms_settings = get_option( 'pms_settings' );

            if ( !isset( $pms_settings['payments']['gateways']['paypal'] ) )
                return false;

            $pms_settings = $pms_settings['payments']['gateways']['paypal'];
        } else {
            $pms_settings = get_option( 'pms_payments_settings' );

            if ( !isset( $pms_settings['gateways']['paypal'] ) )
                return false;

            $pms_settings = $pms_settings['gateways']['paypal'];
        }

        if ( empty( $pms_settings ) )
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
 * Display a warning to the administrators if the API credentials are missing in the
 * register page
 *
 */
if( !function_exists( 'pms_paypal_api_credentials_admin_warning' ) ) {

    function pms_paypal_api_credentials_admin_warning() {

        if( !current_user_can( 'manage_options' ) )
            return;

        $are_active = array_intersect( array( 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

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

        $are_active = array_intersect( array( 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

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
 * @param int $user_id
 * @param int $subscription_plan_id
 *
 * @return mixed string | null
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
 * Returns the member subscription details given the PayPal payment profile id
 *
 * @param string $payment_profile_id
 *
 * @return mixed array | null
 *
 */
if( !function_exists('pms_get_member_subscription_by_payment_profile_id') ) {

    function pms_get_member_subscription_by_payment_profile_id( $payment_profile_id = '' ) {

        if( empty( $payment_profile_id ) )
            return null;

        $payment_profile_id = sanitize_text_field( $payment_profile_id );

        global $wpdb;

        $result = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}pms_member_subscriptions WHERE payment_profile_id LIKE {$payment_profile_id}", ARRAY_A );

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
 * Function that outputs the automatic renewal option in the front-end for the user/customer to see
 *
 */
if( !function_exists( 'pms_ppsrp_renewal_option' ) && !function_exists( 'pms_renewal_option_field' ) ) {

    function pms_renewal_option_field( $output, $include, $exclude_id_group, $member, $pms_settings ) {

        // Get all subscription plans
        if( empty( $include ) )
            $subscription_plans = pms_get_subscription_plans();
        else {
            if( !is_object( $include[0] ) )
                $subscription_plans = pms_get_subscription_plans( true, $include );
            else
                $subscription_plans = $include;
        }

        // Calculate the amount for all subscription plans
        $amount = 0;
        foreach( $subscription_plans as $subscription_plan ) {
            $amount += $subscription_plan->price;
        }

        if( !$member && isset( $pms_settings['payments']['recurring'] ) && $pms_settings['payments']['recurring'] == 1 && $amount != 0 ) {

            $output .= '<div class="pms-subscription-plan-auto-renew">';
                $output .= '<label><input name="pms_recurring" type="checkbox" value="1" ' . ( isset( $_REQUEST['pms_recurring'] ) ? 'checked="checked"' : '' ) . ' />' . apply_filters( 'pms_auto_renew_label', __( 'Automatically renew subscription', 'paid-member-subscriptions' ) ) . '</label>';
            $output .= '</div>';

        }

        return apply_filters( 'pms_renewal_option_field', $output );

    }
    add_filter( 'pms_output_subscription_plans', 'pms_renewal_option_field', 20, 5 );

}


/*
 * Check for PayPal Standard Recurring Payments Add-On to see if it is activated
 * For recurring payments on cancellation we need to cancel the subscription in PayPal as well,
 * and the recurring add-on also handles this operations
 *
 * As we don't want any conflicts
 *
 */
function pms_check_paypal_confirm_cancel_subscription_hooks() {

    $active_plugins = get_option( 'active_plugins' );
    $found          = false;


    // Search for standard recurring add-on
    foreach( $active_plugins as $active_plugin ) {

        if( strpos( $active_plugin, 'pms-add-on-paypal-standard-recurring-payments' ) !== false )
            $found = true;

    }

    if( $found )
        remove_filter( 'pms_confirm_cancel_subscription', 'pms_ppsrp_confirm_cancel_subscription', 10 );

}
add_action( 'init', 'pms_check_paypal_confirm_cancel_subscription_hooks' );


/*
 * Hooks to 'pms_confirm_cancel_subscription' from PMS to change the default value provided
 * Makes an api call to PayPal to cancel the subscription, if is successful returns true,
 * but if not returns an array with 'error'
 *
 * @param bool $confirmation
 * @param int $user_id
 * @param int $subscription_plan_id
 *
 * @return mixed    - bool true if successful, array if not
 *
 */
if( !function_exists( 'pms_paypal_confirm_cancel_subscription' ) ) {

    function pms_paypal_confirm_cancel_subscription( $confirmation, $user_id, $subscription_plan_id ) {

        // Get payment_profile_id
        $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

        // Continue only if the profile id is a PayPal one
        if( !pms_is_paypal_payment_profile_id($payment_profile_id) )
            return $confirmation;

        // Instantiate the payment gateway with data
        $payment_data = array(
            'user_data' => array(
                'user_id'       => $user_id,
                'subscription'  => pms_get_subscription_plan( $subscription_plan_id )
            )
        );

        $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

        // Cancel the subscription and return the value
        $confirmation = $paypal_express->process_cancel_subscription( $payment_profile_id, 'Subscription canceled by user from [pms-account].' );

        if( !$confirmation )
            $confirmation = array( 'error' => $paypal_express->get_cancel_subscription_error() );

        return $confirmation;

    }
    add_filter( 'pms_confirm_cancel_subscription', 'pms_paypal_confirm_cancel_subscription', 10, 3 );

}


/*
 * Hook to 'pms_paypal_express_before_upgrade_subscription' to cancel the active subscription
 * from PayPal
 *
 */
if( !function_exists( 'pms_paypal_cancel_subscription_before_upgrade' ) ) {

    function pms_paypal_cancel_subscription_before_upgrade( $member_subscription_id, $payment_data, $post_data ) {

        $user_id              = $payment_data['user_id'];
        $subscription_plan_id = $member_subscription_id;

        // Get payment_profile_id
        $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

        if( empty($payment_profile_id) || !pms_is_paypal_payment_profile_id($payment_profile_id) )
            return;

        // Instantiate the payment gateway with data
        $payment_data = array(
            'user_data' => array(
                'user_id'       => $user_id,
                'subscription'  => pms_get_subscription_plan( $subscription_plan_id )
            )
        );

        $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

        // Cancel the subscription and return the value
        $confirmation = $paypal_express->process_cancel_subscription( $payment_profile_id, 'Subscription canceled because user upgraded to another one.' );

        // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
        if( !$confirmation  && wp_get_schedule( 'pms_api_retry_cancel_paypal_subscription', array( $user_id, $subscription_plan_id ) ) == false )
            wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_retry_cancel_paypal_subscription', array( $user_id, $subscription_plan_id ) );

    }
    add_action( 'pms_paypal_express_before_upgrade_subscription', 'pms_paypal_cancel_subscription_before_upgrade', 10, 3 );

}

/**
 * Cancel PayPal subscription when an admin deletes the subscription from the back-end
 * @param  int   $subscription_id ID of the subscription that was just deleted
 * @param  array $data            Subscription data before deletion
 * @return void
 */
function pms_paypal_cancel_subscription_on_admin_deletion( $subscription_id, $data ){

    if( !is_admin() )
        return;

    if( empty( $data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $data['payment_profile_id'] ) )
        return;

    // Instantiate the payment gateway with data
    $payment_data = array(
        'user_data' => array(
            'user_id'       => $data['user_id'],
            'subscription'  => pms_get_subscription_plan( $data['subscription_plan_id'] )
        )
    );

    $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

    // Cancel the subscription
    $paypal_express->process_cancel_subscription( $data['payment_profile_id'], 'Subscription canceled because an admin deleted the members subscription.' );

}
add_action( 'pms_member_subscription_delete', 'pms_paypal_cancel_subscription_on_admin_deletion', 10, 2 );

/**
 * Cancel PayPal subscription when the subscription is upgraded to a PMS PSP gateway
 *
 * @param  object $old_subscription        ID of the subscription that was just deleted
 * @param  object $payment                 New payment object
 * @param  array  $new_subscription_data   New subscription data
 * @return void
 */
function pms_paypal_cancel_subscription_on_psp_upgrade( $old_subscription, $payment, $new_subscription_data ){

    if( empty( $old_subscription->id ) || empty( $old_subscription->payment_profile_id ) || !pms_is_paypal_payment_profile_id( $old_subscription->payment_profile_id ) )
        return;

    // Instantiate the payment gateway with data
    $payment_data = array(
        'user_data' => array(
            'user_id'       => $old_subscription->user_id,
            'subscription'  => pms_get_subscription_plan( $old_subscription->subscription_plan_id )
        )
    );

    $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

    // Cancel the subscription and return the value
    $confirmation = $paypal_express->process_cancel_subscription( $old_subscription->payment_profile_id, 'Subscription canceled because user upgraded to another one.' );

    // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
    if( !$confirmation  && wp_get_schedule( 'pms_api_retry_cancel_paypal_subscription', array( $old_subscription->user_id, $old_subscription->subscription_plan_id ) ) == false )
        wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_retry_cancel_paypal_subscription', array( $old_subscription->user_id, $old_subscription->subscription_plan_id ) );

}
add_action( 'pms_psp_before_upgrade_subscription', 'pms_paypal_cancel_subscription_on_psp_upgrade', 20, 3);

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
function pms_paypal_cancel_subscription_on_api_subscription_cancelation( $id, $data, $old_data ){

    if( !is_admin() )
        return;

    if( empty( $old_data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $old_data['payment_profile_id'] ) )
        return;

    if( !empty( $data['status'] ) && $data['status'] == 'canceled' && $data['status'] != $old_data ) {

        // Instantiate the payment gateway with data
        $payment_data = array(
            'user_data' => array(
            'user_id'       => $old_data['user_id'],
            'subscription'  => pms_get_subscription_plan( $old_data['subscription_plan_id'] )
            )
        );

        $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

        // Cancel the subscription
        $paypal_express->process_cancel_subscription( $old_data['payment_profile_id'], 'Subscription canceled because an admin canceled the members subscription.' );

    }

}
add_action( 'pms_member_subscription_update', 'pms_paypal_cancel_subscription_on_api_subscription_cancelation', 20, 3 );

/*
 * Cron job that executes if a subscription did not get cancelled successfully
 * It will fire one every hour until the subscription gets cancelled
 *
 */
if( !function_exists( 'pms_api_retry_cancel_paypal_subscription' ) ) {

    function pms_api_retry_cancel_paypal_subscription( $user_id, $subscription_plan_id ) {

        // Get payment_profile_id
        $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

        if( empty($payment_profile_id) || !pms_is_paypal_payment_profile_id($payment_profile_id) )
            return;

        // Instantiate the payment gateway with data
        $payment_data = array(
            'user_data' => array(
                'user_id'       => $user_id,
                'subscription'  => pms_get_subscription_plan( $subscription_plan_id )
            )
        );

        $paypal_express = pms_get_payment_gateway( 'paypal_express', $payment_data );

        // Cancel the subscription and return the value
        $confirmation = $paypal_express->process_cancel_subscription( $payment_profile_id, 'Retry cancel subscription request.' );
        $error        = $paypal_express->get_cancel_subscription_error();

        // If all is good clear the schedule
        if( $confirmation && !empty($error) ) {

            // Removed information
            if( isset( $api_failed_attempts[$user_id][$subscription_plan_id] ) )
                unset( $api_failed_attempts[$user_id][$subscription_plan_id] );

            // Clear schedule if it exists
            if( wp_get_schedule( 'pms_api_retry_cancel_paypal_subscription', array( $user_id, $subscription_plan_id ) ) )
                wp_clear_scheduled_hook( 'pms_api_retry_cancel_paypal_subscription', array( $user_id, $subscription_plan_id ) );

            update_option( 'pms_api_failed_attempts', $api_failed_attempts );


            do_action( 'pms_api_cancel_paypal_subscription_upgrade_successful', $user_id, $subscription_plan_id, 'update', $confirmation, $error );

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

            update_option( 'pms_api_failed_attempts', $api_failed_attempts );


            do_action( 'pms_api_cancel_paypal_subscription_upgrade_unsuccessful', $user_id, $subscription_plan_id, 'update', $confirmation, $error );

        }


    }
    add_action( 'pms_api_retry_cancel_paypal_subscription', 'pms_api_retry_cancel_paypal_subscription', 10, 2 );

}

/**
 * Add custom log messages for the PayPal Express and Pro gateways
 *
 */
function pms_ppexpro_payment_logs_system_error_messages( $message, $log ) {

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch( $log['type'] ) {
        case 'paypal_user_returned':
            $message = __( 'User returned back to website from <strong>PayPal</strong>.', 'paid-member-subscriptions' );
            break;
        case 'paypal_confirm_form_submitted':
            $message = __( 'Payment confirmation form submitted by user.', 'paid-member-subscriptions' );
            break;
        case 'paypal_checkout_token_error':
            $message = sprintf( __( 'PayPal couldn\'t generate the token. <strong>Error %s</strong>: %s', 'paid-member-subscriptions' ), $log['error_code'], $log['data']['message'] );
            break;
        case 'paypal_checkout_details_error':
            $message = sprintf( __( 'Couldn\'t receive checkout details from PayPal. <strong>Error %s</strong>: %s', 'paid-member-subscriptions' ), $log['error_code'], $log['data']['message'] );
            break;
        case 'paypal_recurring_initial_payment_error':
            $message = __( 'Recurring initial payment could not be completed successfully.', 'paid-member-subscriptions' );
            break;
        case 'paypal_recurring_payment_error':
            $message = __( 'Something went wrong and the gateway couldn\'t process the payment.', 'paid-member-subscriptions' );
            break;
        case 'paypal_api_error':
            $message = __( 'PayPal request failed. Please try again.', 'paid-member-subscriptions' );
            break;
        case 'paypal_txid_received':
            $message = __( 'Received Transaction ID from PayPal.', 'paid-member-subscriptions' );
            break;
        case 'paypal_billing_agreement_rejected':
            $message = __( 'User did not accept the <strong>Billing Agreement</strong> on the <strong>PayPal Checkout</strong>.', 'paid-member-subscriptions' );
            break;
        case 'paypal_rtexpress_charging_user':
            $message = __( 'Attempting to charge user based on the <strong>PayPal Checkout</strong>.', 'paid-member-subscriptions' );
            break;
    }

    return wp_kses( $message, $kses_args );

}
add_filter( 'pms_payment_logs_system_error_messages', 'pms_ppexpro_payment_logs_system_error_messages', 10, 2 );

/**
 * Adds error data to the failed payment message, if available.
 *
 * @param  string  $output       Default error message.
 * @param  boolean $is_register  Equals to 1 if checkout was initiated from the registration form.
 * @param  int     $payment_id   ID of the payment associated with the error.
 * @return string
 */
function pms_ppexpro_error_message( $output, $is_register, $payment_id ) {

    if ( empty( $payment_id ) )
        return $output;

    $payment = new PMS_Payment( $_GET['pms_payment_id'] );

    if ( isset( $payment->payment_gateway ) && $payment->payment_gateway != 'paypal_pro' && $payment->payment_gateway != 'paypal_express' )
        return $output;

    if ( empty( $payment->id ) || empty( $payment->logs ) )
        return $output;

    $response = array();

    foreach( $payment->logs as $log ) {
        if ( !empty( $log['type'] ) && $log['type'] == 'payment_failed' && isset( $log['data']['response'] ) ) {
            $response = $log['data']['response'];
            break;
        }
    }

    $response = pms_ppexpro_parse_paypal_response( $response );

    if ( !$response )
        return $output;

    ob_start(); ?>

    <div class="pms-payment-error">
        <p>
            <?php _e( 'The payment gateway is reporting the following error:', 'paid-member-subscriptions' ); ?>
            <span class="pms-payment-error__message">
                <?php echo $response['L_ERRORCODE0'] . ': ' . $response['L_LONGMESSAGE0']; ?>
            </span>
        </p>
        <p>
            <?php echo pms_payment_error_message_retry( $is_register, $payment_id ); ?>
        </p>
    </div>

    <?php

    $output = ob_get_contents();

    ob_end_clean();

    return $output;
}
add_filter( 'pms_payment_error_message', 'pms_ppexpro_error_message', 20, 3 );

/**
 * Parse PayPal response and return an array with the keys we are looking for
 *
 */
function pms_ppexpro_parse_paypal_response( $response ) {

    if ( empty( $response ) || empty( $response['L_ERRORCODE0'] ) || empty( $response['L_LONGMESSAGE0'] ) )
        return false;

    $keys = array( 'L_ERRORCODE0' => '', 'L_LONGMESSAGE0' => '' );

    return array_intersect_key( $response, $keys );

}

add_filter( 'pms_payment_logs_modal_header_content', 'pms_ppexpro_payment_logs_modal_header_content', 20, 3 );
function pms_ppexpro_payment_logs_modal_header_content( $content, $log, $payment_id ) {
    if ( empty( $payment_id ) || ( isset( $log['type'] ) && $log['type'] != 'payment_failed' ) )
        return $content;

    $payment = pms_get_payment( $payment_id );

    if ( empty( $payment->id ) || !in_array( $payment->payment_gateway, array( 'paypal_pro', 'paypal_express' ) ) )
        return $content;

    ob_start(); ?>

        <h2><?php _e( 'Payment Gateway Message', 'paid-member-subscriptions' ); ?></h2>

        <p>
            <strong><?php _e( 'Error code:', 'paid-member-subscriptions' ); ?> </strong>
            <?php echo $log['error_code']; ?>
        </p>

        <p>
            <strong><?php _e( 'Message:', 'paid-member-subscriptions' ); ?> </strong>
            <?php echo $log['data']['message']; ?>
        </p>

        <p>
            <strong><?php _e( 'More info:', 'paid-member-subscriptions' ); ?> </strong>
            <a href="https://developer.paypal.com/docs/classic/api/errors/" target="_blank">https://developer.paypal.com/docs/classic/api/errors/</a>
        </p>

    <?php
    $output = ob_get_clean();

    return $output;
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

add_filter( 'pms_subscription_logs_system_error_messages', 'pms_ppex_add_subscription_log_messages', 20, 2 );
function pms_ppex_add_subscription_log_messages( $message, $log ){
    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'paypal_subscription_setup':
            $message = __( 'Subscription setup successfully with PayPal.', 'paid-member-subscriptions' );
            break;
    }

    return $message;
}
