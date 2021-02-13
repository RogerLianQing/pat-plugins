<?php
/**
 * Plugin Name: Paid Member Subscriptions - Discount Codes Add-on
 * Plugin URI: https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/
 * Description: Easily create discount codes for Paid Member Subscriptions plugin.
 * Version: 1.3.9
 * Author: Cozmoslabs, Adrian Spiac
 * Author URI: https://www.cozmoslabs.com/
 * Text Domain: paid-member-subscriptions
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2018 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

define( 'PMS_DC_VERSION', '1.3.9' );

/**
 * Include the files needed
 *
 */

// Discount code object class
if( file_exists( plugin_dir_path( __FILE__ ). 'functions-discount.php' ) )
    include_once( plugin_dir_path( __FILE__ ) . 'functions-discount.php' );

if( file_exists( plugin_dir_path( __FILE__ ). 'class-discount-code.php' ) )
    include_once( plugin_dir_path( __FILE__ ) . 'class-discount-code.php' );

// Discount Codes custom post type
if( file_exists( plugin_dir_path( __FILE__ ) . 'class-admin-discount-codes.php' ) )
    include_once( plugin_dir_path( __FILE__ ). 'class-admin-discount-codes.php' );

// Meta box for discount codes cpt
if( file_exists( plugin_dir_path( __FILE__ ) . 'class-metabox-discount-codes-details.php' ) )
    include_once( plugin_dir_path( __FILE__ ) . 'class-metabox-discount-codes-details.php' );


/**
 * Adding Admin scripts
 *
 */
function pms_dc_add_admin_scripts(){

    // If the file exists where it should be, enqueue it
    if( file_exists( plugin_dir_path( __FILE__ ) . 'assets/js/cpt-discount-codes.js' ) )
        wp_enqueue_script( 'pms-discount-codes-js', plugin_dir_url( __FILE__ ) . 'assets/js/cpt-discount-codes.js', array( 'jquery','jquery-ui-datepicker' ), PMS_VERSION );

    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css', array(), PMS_VERSION );

    // add back-end css for Discount Codes cpt
    wp_enqueue_style( 'pms-dc-style-back-end', plugin_dir_url( __FILE__ ) . 'assets/css/style-back-end.css' );

}
add_action('pms_cpt_enqueue_admin_scripts_pms-discount-codes','pms_dc_add_admin_scripts');


/**
 * Adding Front-end scripts
 *
 */
function pms_dc_add_frontend_scripts(){

    if( file_exists( plugin_dir_path( __FILE__ ) . 'assets/js/frontend-discount-code.js' ) ) {

        wp_enqueue_script('pms-frontend-discount-code-js', plugin_dir_url(__FILE__) . 'assets/js/frontend-discount-code.js', array('jquery'), PMS_VERSION );

        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        wp_localize_script( 'pms-frontend-discount-code-js', 'pms_discount_object',
            array(
                'ajax_url'                 => admin_url( 'admin-ajax.php' ),
                'discounted_subscriptions' => json_encode( pms_dc_get_discounted_subscriptions(), JSON_FORCE_OBJECT ),
            )
        );
    }

    // add front-end CSS for discount code box
    if ( file_exists( plugin_dir_path(__FILE__) . 'assets/css/style-front-end.css') ) {
        wp_enqueue_style('pms-dc-style-front-end', plugin_dir_url(__FILE__). 'assets/css/style-front-end.css' );
    }


}
add_action('wp_enqueue_scripts','pms_dc_add_frontend_scripts');


/**
 * Positioning the Discount Codes label under Payments in PMS submenu
 *
 */
function pms_dc_submenu_order( $menu_order){
    global $submenu;

    if ( isset($submenu['paid-member-subscriptions']) ) {

        foreach ($submenu['paid-member-subscriptions'] as $key => $value) {
            if ($value[2] == 'edit.php?post_type=pms-discount-codes') $discounts_key = $key;
            if ($value[2] == 'pms-payments-page') $payments_key = $key;
        }

        if (isset($payments_key) && isset($discounts_key)) {
            $discounts_value = $submenu['paid-member-subscriptions'][$discounts_key];

            if ($payments_key > $discounts_key) $payments_key--;
            unset($submenu['paid-member-subscriptions'][$discounts_key]);

            $array1 = array_slice($submenu['paid-member-subscriptions'], 0, $payments_key);
            $array2 = array_slice($submenu['paid-member-subscriptions'], $payments_key);
            array_push($array1, $discounts_value);

            $submenu['paid-member-subscriptions'] = array_merge($array1, $array2);

        }
    }

    return $menu_order;

}
add_filter('custom_menu_order','pms_dc_submenu_order');


/**
 * Output discount code box on the front-end
 *
 * */
function pms_dc_output_discount_box( $output, $include, $exclude_id_group, $member, $pms_settings, $subscription_plans ){

    // Don't display the discount field on account pages
    if( !empty( $member ) )
        return $output;

    if( empty( $subscription_plans ) )
        return $output;

    // Calculate the total price of the subscription plans
    $total_price = 0;
    foreach( $subscription_plans as $subscription_plan ) {
        $total_price += (int)$subscription_plan->price;
        $total_price += (int)$subscription_plan->sign_up_fee;
    }


    // Return the discount code field only if we have paid plans
    if( $total_price !== 0 ) {
        $discount_output  = '<div id="pms-subscription-plans-discount">';
        $discount_output .= '<label for="pms_subscription_plans_discount">' . apply_filters('pms_form_label_discount_code', __('Discount Code: ', 'paid-member-subscriptions')) . '</label>';
        $discount_output .= '<input id="pms_subscription_plans_discount_code" name="discount_code" placeholder="' . apply_filters( 'pms_form_input_placeholder_discount_code', __( 'Enter discount', 'paid-member-subscriptions' ) ) . '" type="text" value="' . ( !empty( $_POST['discount_code'] ) ? esc_attr( $_POST['discount_code'] ) : '' ) . '" />';
        $discount_output .= '<input id="pms-apply-discount" class="pms-submit button" type="submit" value="' . apply_filters( 'pms_form_submit_discount_code', __( 'Apply', 'paid-member-subscriptions' ) ) . '">';
        $discount_output .= '</span>';
        $discount_output .= '</div>';

        $message_output  = '<div id="pms-subscription-plans-discount-messages-wrapper">';
            $message_output .= '<div id="pms-subscription-plans-discount-messages" ' . (pms_errors()->get_error_message('discount_error') ? 'class="pms-discount-error"' : '') . '>';
            $message_output .= pms_errors()->get_error_message('discount_error');
            $message_output .= '</div>';

            $message_output .= '<div id="pms-subscription-plans-discount-messages-loading">';
            $message_output .= __( 'Applying discount code. Please wait...', 'paid-member-subscriptions' );
            $message_output .= '</div>';
        $message_output .= '</div>';

        $output .= $discount_output . $message_output;
    }

    return $output;
}
add_filter('pms_output_subscription_plans', 'pms_dc_output_discount_box', 25, 6 );


/**
 * Function that returns the front-end discount code errors or success message
 *
 */
function pms_dc_output_apply_discount_message() {

    $response     = array(); // initialize response
    $code         = '';
    $subscription = '';
    $user_checked_auto_renew = false;
    $pwyw_price = '';

    // Clean-up and setup data
    if( !empty( $_POST['code'] ) )
        $code = sanitize_text_field( $_POST['code'] );

    if( !empty( $_POST['subscription'] ) )
        $subscription = (int)trim( $_POST['subscription'] );

    // User checked the auto-renew checkbox
    if( !empty( $_POST['recurring'] ) )
        $user_checked_auto_renew = true;

    // Pay What You Want Pricing is enabled for the selected plan
    if ( !empty( $_POST['pwyw_price'] ) )
        $pwyw_price = sanitize_text_field( $_POST['pwyw_price'] );

    // Assemble the response
    if ( !empty( $code ) && !empty( $subscription ) ) {

        $error = pms_dc_get_discount_error( $code, $subscription );

        // Setup user message
        if( ! empty( $error ) )
            $response['error']['message'] = $error;
        else
            $response['success']['message'] = pms_dc_apply_discount_success_message( $code, $subscription, $user_checked_auto_renew, $pwyw_price );

        // Determine wether the discount code is a partial discount or a full discount
        $response['is_full_discount'] = pms_dc_check_is_full_discount( $code, $subscription, $user_checked_auto_renew, $pwyw_price );

        // Add new price to response
        $plan          = pms_get_subscription_plan( $subscription );
        $form_location = PMS_Form_Handler::get_request_form_location();
        $amount        = (float)$plan->price;

        if ( in_array( $form_location, array( 'register', 'new_subscription' ) ) && !empty( $plan->sign_up_fee ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {
            // Check if there is a Free Trial period
            if ( !empty( $plan->trial_duration ) )
                $amount = $plan->sign_up_fee;
            else
                $amount += (float)$plan->sign_up_fee;
        }

        $response['discounted_price'] = pms_calculate_discounted_amount( $amount, pms_get_discount_by_code( $code ) );

        wp_send_json($response);
    }

}
add_action( 'wp_ajax_pms_discount_code', 'pms_dc_output_apply_discount_message' );
add_action( 'wp_ajax_nopriv_pms_discount_code', 'pms_dc_output_apply_discount_message' );


/**
 * Function that returns the success message and the billing amount when the discount was successfully applied
 *
 * @param string $code - The entered discount code
 * @param string $subscription - Subscription plan id
 * @param bool $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 * @param string $pwyw_price - The price entered by the user if the selected subscription has Pay What You Want pricing enabled
 * @return string
 */
function pms_dc_apply_discount_success_message( $code, $subscription, $user_checked_auto_renew, $pwyw_price = '') {

    if ( empty( $code ) || empty( $subscription ) )
        return;

    //Determine form location
    $form_location = PMS_Form_Handler::get_request_form_location();

    //Get Discount object
    $discount = pms_get_discount_by_code( $code );

    // Get Subscription plan object
    $subscription_plan = pms_get_subscription_plan( $subscription );

    // Get currency symbol
    $currency_symbol = pms_get_currency_symbol( pms_get_active_currency() );

    // Check if subscription payment will be recurring
    $is_recurring = pms_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew );

    // If Pay What You Want pricing is enabled for this subscription plan, and the user entered a price, modify subscription price
    if ( $pwyw_price !== '' )
        $subscription_plan->price = (float)$pwyw_price;

    $initial_payment = (float)$subscription_plan->price;

    // Take into account the Sign-up Fee as well
    if ( in_array( $form_location, array( 'register', 'new_subscription' ) ) && !empty( $subscription_plan->sign_up_fee ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {
        // Check if there is a Free Trial period
        if ( !empty( $subscription_plan->trial_duration ) )
            $initial_payment = $subscription_plan->sign_up_fee;
        else
            $initial_payment += (float)$subscription_plan->sign_up_fee;
    }

    $initial_payment = pms_calculate_discounted_amount( $initial_payment, $discount );

    if ( $is_recurring ) {

        $recurring_payment = (float)$subscription_plan->price;

        // Check if we need to apply discount to recurring payments as well
        if ( !empty( $discount->recurring_payments ) )
            $recurring_payment = pms_calculate_discounted_amount( $subscription_plan->price, $discount );

    }

    if ( in_array( $form_location, array( 'register', 'new_subscription' ) ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) ) {

        if( $subscription_plan->trial_duration > 0) {
            switch ($subscription_plan->trial_duration_unit) {
                case 'day':
                    $trial_duration = sprintf( _n( '%s day', '%s days', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'week':
                    $trial_duration = sprintf( _n( '%s week', '%s weeks', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'month':
                    $trial_duration = sprintf( _n( '%s month', '%s months', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'year':
                    $trial_duration = sprintf( _n( '%s year', '%s years', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
            }
        }
    }

    if( $subscription_plan->duration > 0) {

        switch ($subscription_plan->duration_unit) {
            case 'day':
                $duration = sprintf( _n( 'day', '%s days', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'week':
                $duration = sprintf( _n( 'week', '%s weeks', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'month':
                $duration = sprintf( _n( 'month', '%s months', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'year':
                $duration = sprintf( _n( 'year', '%s years', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
        }
    }

    // Set currency position according to the PMS Settings page
    $initial_payment_price = pms_format_price( $initial_payment, pms_get_active_currency() );
    $after_trial_payment   = pms_format_price( (float)$subscription_plan->price, pms_get_active_currency() );

    // If both trial and sign-up fees are added, add discount to both initial payment and after trial payment
    if( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) )
        $after_trial_payment = pms_format_price( pms_calculate_discounted_amount( (float)$subscription_plan->price, $discount ), pms_get_active_currency() );

    /**
     * Start building the response
     */
    $response = __( 'Discount successfully applied! ', 'paid-member-subscriptions' );

    if ( $is_recurring && $recurring_payment != 0 ) {

        $recurring_payment_price = pms_format_price( $recurring_payment, pms_get_active_currency() );

        if ( !empty( $trial_duration ) && empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged after %1$s is %2$s, then %3$s every %4$s.', 'paid-member-subscriptions' ), $trial_duration, $initial_payment_price, $recurring_payment_price, $duration );
        else if ( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged now is %1$s, then after %2$s %3$s every %4$s.', 'paid-member-subscriptions' ), $initial_payment_price, $trial_duration, $recurring_payment_price, $duration );
        else {

            if ( $initial_payment == $recurring_payment )
                $response .= sprintf( __( 'Amount to be charged is %1$s every %2$s.', 'paid-member-subscriptions' ), $initial_payment_price, $duration );
            else
                $response .= sprintf( __( 'Amount to be charged now is %1$s, then %2$s every %3$s.', 'paid-member-subscriptions' ), $initial_payment_price, $recurring_payment_price, $duration );
        }

    } else {

        if ( !empty( $trial_duration ) && empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged after %1$s is %2$s.', 'paid-member-subscriptions' ), $trial_duration, $initial_payment_price );
        else if ( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged now is %1$s, then after %2$s %3$s.', 'paid-member-subscriptions' ), $initial_payment_price, $trial_duration, $after_trial_payment );
        else
            $response .= sprintf( __( 'Amount to be charged is %s.', 'paid-member-subscriptions' ), $initial_payment_price );

    }

    /**
     * Filter discount applied successfully message.
     *
     * @param string $code The entered discount code
     * @param string $subscription The subscription plan id
     * @param string $pwyw_price The Pay What You Want price entered by the user, if enabled
     */
    return apply_filters('pms_dc_apply_discount_success_message', $response, $code, $subscription, $pwyw_price );
}



/**
 * Determines whether the discount
 *
 * @param string $code
 * @param int    $subscription_plan_id
 * @param bool   $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 * @param string $pwyw_price - Is set when Pay What You Want pricing is enabled and the user enters an amount
 *
 * @return bool
 *
 */
function pms_dc_check_is_full_discount( $code = '', $subscription_plan_id = 0, $user_checked_auto_renew = false, $pwyw_price = '' ) {

    if( empty( $code ) )
        return false;

    if( empty( $subscription_plan_id ) )
        return false;

    $discount_code     = pms_get_discount_by_code( $code );
    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

    $checkout_is_recurring = pms_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew );

    // If Pay What You Want price is set, modify subscription price
    if ( $pwyw_price !== '' ){
        $subscription_plan->price = (float)$pwyw_price;
    }

    $discounted_amount = pms_calculate_discounted_amount( $subscription_plan->price, $discount_code );

    // If the checkout creates a subscription with recurring payments
    if( $checkout_is_recurring ) {

        if( ! empty( $discount_code->recurring_payments ) ) {

            if( $discounted_amount == 0 )
                return true;

        }

    }

    // If the checkout doesn't create a subscription with recurring payments
    if( ! $checkout_is_recurring ) {

        if( $discounted_amount == 0 && empty( $subscription_plan->sign_up_fee ) )
            return true;

    }

    return false;

}


/**
 * Function that checks if a given subscription is recurring, taking into consideration also if the user checked the "Automatically renew subscription" checkbox
 *
 * @param PMS_Subscription_Plan $subscription_plan - The subscription plan object
 * @param bool                  $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 *
 * @return bool
 *
 */
function pms_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew ){

    // Subscription plan is never ending
    if( empty( $subscription_plan->duration ) )
        return false;

    // Subscription plan has options: always recurring
    if( $subscription_plan->recurring == 2 )
        return true;

    // Subscription plan has option: never recurring
    if( $subscription_plan->recurring == 3 )
        return false;

    // Subscription plan has options: customer opts in
    if( $subscription_plan->recurring == 1 )
       return $user_checked_auto_renew;


    // Subscription plan has option: settings default
    if( empty( $subscription_plan->recurring ) ) {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $settings           = get_option( 'pms_settings', array() );
            $settings_recurring = empty( $settings['payments']['recurring'] ) ? 0 : (int)$settings['payments']['recurring'];
        } else {
            $settings           = get_option( 'pms_payments_settings', array() );
            $settings_recurring = empty( $settings['recurring'] ) ? 0 : (int)$settings['recurring'];
        }

        if( empty( $settings_recurring ) )
            return false;

        // Settings has option: always recurring
        if( $settings_recurring == 2 )
            return true;

        // Settings has option: never recurring
        if( $settings_recurring == 3 )
            return false;

        // Settings has option: customer opts in
        if( $settings_recurring == 1 )
            return $user_checked_auto_renew;

    }

}

/**
 * Function that checks for and returns the discount errors
 * @param string $code The discount code entered
 * @param string $subscription The subscription plan ID
 * @return string
 */
function pms_dc_get_discount_error( $code, $subscription ){

    if ( !empty($code) ) {
        // Get all the discount data
        $discount_meta = PMS_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );

        if ( !empty($discount_meta) ) { //discount is active

            $discount_subscriptions = array();
            if (!empty($discount_meta['pms_discount_subscriptions']))
                $discount_subscriptions = explode( ',' , $discount_meta['pms_discount_subscriptions'][0] );

            if ( empty($subscription) )
                return __('Please select a subscription plan and try again.', 'paid-member-subscriptions');

            if ( !in_array( $subscription, $discount_subscriptions) ) {
                //discount not valid for this subscription
                return __('The discount is not valid for this subscription plan.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_start_date'][0]) && (strtotime($discount_meta['pms_discount_start_date'][0]) > time()) ) {
                //start date is in the future
                return __('The discount code you entered is not active yet.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_expiration_date'][0]) && (strtotime($discount_meta['pms_discount_expiration_date'][0]) <= time()) ) {
                //expiration date has passed
                return __('The discount code you entered has expired.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_max_uses'][0]) && isset($discount_meta['pms_discount_uses'][0]) && ( $discount_meta['pms_discount_max_uses'][0] <= $discount_meta['pms_discount_uses'][0]) ) {
                //all uses for this discount have been consumed
                return __('The discount code maximum uses have been reached.', 'paid-member-subscriptions');
            }

            if ( !isset($discount_meta['pms_discount_max_uses_per_user'][0]) ) {
                // set default value for discounts created before this option was added
                $discount_meta['pms_discount_max_uses_per_user'][0] = 1;
            }

            if ( $discount_meta['pms_discount_max_uses_per_user'][0] != 0 && $discount_meta['pms_discount_max_uses_per_user'][0] <= pms_dc_get_discount_uses_per_user($code) ) {
                // the maximum discount uses for this user have been reached
                return __('The discount code maximum uses for this user have been reached.', 'paid-member-subscriptions');
            }

            /**
             * Hook for adding custom validation for discount codes
             *
             * @param string $error Error message that will be returned
             * @param string $code The discount code entered
             * @param string $subscription The subscription plan ID
             * @param array $discount_meta The discount code details
             * @return string
             */
            $extra_validations = apply_filters( 'pms_dc_get_discount_error', '', $code, $subscription, $discount_meta );

            if ( !empty( $extra_validations ) )
                return $extra_validations;

        }
        else {
            // Entered discount code was not found or is inactive
            return __('The discount code you entered is invalid.', 'paid-member-subscriptions');
            }
    }
    return '';
}

/**
 * Returns the number of times the current user has used this discount code
 *
 */
function pms_dc_get_discount_uses_per_user( $code ){
    $user_discount_uses = 0;
    $user_id = 0;

    // When trying to use the discount more than once, the user should be logged in ( for renewal, retrying the payment, upgrading, buying a different subscription )
    if ( is_user_logged_in() ){
        $user_id = get_current_user_id();
    }

    if ( !empty($user_id) ){

        $meta = get_user_meta( $user_id, 'pms_discount_uses_per_user_'.$code, true );

        if ( !empty( $meta ) )
            $user_discount_uses = (int)$meta;

    }

    return $user_discount_uses;
}

/**
 * Validates the discount code on the different form
 *
 */
function pms_dc_add_form_discount_error(){

    if ( !empty($_POST['discount_code']) && !empty($_POST['subscription_plans']) ) {

        $code                 = sanitize_text_field( trim( $_POST['discount_code'] ) );
        $subscription_plan_id = (int)$_POST['subscription_plans'];

        $error = pms_dc_get_discount_error( $code, $subscription_plan_id );

        if ( !empty($error) ) {
            pms_errors()->add('discount_error', $error);
        }
    }
}
add_action( 'pms_register_form_validation',                   'pms_dc_add_form_discount_error' );
add_action( 'pms_new_subscription_form_validation',           'pms_dc_add_form_discount_error' );
add_action( 'pms_upgrade_subscription_form_validation',       'pms_dc_add_form_discount_error' );
add_action( 'pms_renew_subscription_form_validation',         'pms_dc_add_form_discount_error' );
add_action( 'pms_retry_payment_subscription_form_validation', 'pms_dc_add_form_discount_error' );
add_action( 'pms_ec_process_checkout_validations',            'pms_dc_add_form_discount_error' );

function pms_dc_add_pbform_discount_error() {
    if ( !empty($_POST['discount_code']) && !empty($_POST['subscription_plans']) ) {
        $code                 = sanitize_text_field( trim( $_POST['discount_code'] ) );
        $subscription_plan_id = (int)$_POST['subscription_plans'];

        $error = pms_dc_get_discount_error( $code, $subscription_plan_id );

        if ( !empty($error) )
            return $error;
    }
}
add_filter( 'wppb_check_form_field_subscription-plans', 'pms_dc_add_pbform_discount_error', 20, 4 );

/**
 * Checks to see if the checkout has a full discount applied and handles the validations
 * for this case.
 *
 * In case there is a full discount the "pay_gate" element is not sent in the $_POST. This case is similar
 * for free plans. If the "pay_gate" elements is missing Paid Member Subscriptions does some validations
 * to see if the selected subscription plan is free. If it is not, it adds some errors.
 *
 * In the case of a full discount the errors will be present, because this validations is done very early in the
 * execution. We will remove this errors if the discount is a full one.
 *
 */
function pms_dc_process_checkout_validation_payment_gateway() {

    if( ! empty( $_POST['pay_gate'] ) )
        return;

    if ( empty( $_POST['discount_code'] ) )
        return;

    $payment_gateway_errors = pms_errors()->get_error_message( 'payment_gateway' );

    if( empty( $payment_gateway_errors ) )
        return;

    $code          = sanitize_text_field( $_POST['discount_code'] );
    $discount_code = pms_get_discount_by_code( $code );

    if( false == $discount_code )
        return;

    // User checked auto-renew checkbox on checkout
    $user_checked_auto_renew = ( ! empty( $_POST['recurring'] ) ? true : false );

    // Get selected subscription plan id
    $subscription_plan_id    = ( ! empty( $_POST['subscription_plans'] ) ? (int)$_POST['subscription_plans'] : 0 );

    // Check if is full discount applied
    $is_full_discount = pms_dc_check_is_full_discount( $code, $subscription_plan_id, $user_checked_auto_renew );

    // If the discount is full, remove the errors for the payment gateways
    if( $is_full_discount )
        pms_errors()->remove( 'payment_gateway' );

}
add_action( 'pms_process_checkout_validations', 'pms_dc_process_checkout_validation_payment_gateway' );


/**
 * Function that returns payment data after applying the discount code (if there are no discount errors)
 *
 *
 */
function pms_dc_register_payment_data_after_discount( $payment_data, $payments_settings ) {

    if ( empty( $_POST['discount_code'] ) )
        return $payment_data;

    $discount = pms_get_discount_by_code( $_POST['discount_code'] );

    if( false == $discount )
        return $payment_data;

    $subscription_plan_id = (int)$_POST['subscription_plans'];

    $error = pms_dc_get_discount_error( $discount->code, $subscription_plan_id );

    if ( !empty( $error ) )
        return $payment_data;

    $payment_data['sign_up_amount'] = pms_calculate_discounted_amount( $payment_data['amount'], $discount );

    if( false == $payment_data['recurring'] )
        $payment_data['amount'] = $payment_data['sign_up_amount'];

    if( true == $payment_data['recurring'] && ! empty( $discount->recurring_payments ) )
        $payment_data['amount'] = $payment_data['sign_up_amount'];


    // Save corresponding discount code for the payment in the db
    if ( class_exists( 'PMS_Payment' ) ) {

        /**
         * Add the discount code to the payment_data
         *
         */
        $payment_data['discount_code'] = $discount->code;

        $payment = pms_get_payment(isset($payment_data['payment_id']) ? $payment_data['payment_id'] : 0);

        $payment->update(array('discount_code' => $discount->code));

        // Update payment amount if it was discounted
        if ( !is_null($payment_data['sign_up_amount']) ) {

            $data = array(
                'amount' => $payment_data['sign_up_amount'],
                'status' => ($payment_data['sign_up_amount'] == 0 ? 'completed' : $payment->status)
            );

            $payment->update($data);

        }

    }

    return $payment_data;

}
add_filter( 'pms_register_payment_data', 'pms_dc_register_payment_data_after_discount', 20, 2 ); //has a later execution so we can discount the Pay What You Want pricing as well


/**
 * Modifies the billing amount on the checkout subscription data to the discounted value
 *
 * @param array $subscription_data
 * @param array $checkout_data
 *
 * @return array
 *
 */
function pms_dc_modify_subscription_data_billing_amount( $subscription_data = array(), $checkout_data = array() ) {

    if ( empty( $_POST['discount_code'] ) )
        return $subscription_data;

    if( empty( $subscription_data ) )
        return array();

    if( ! $checkout_data['is_recurring'] )
        return $subscription_data;

    // Get discount
    $discount = pms_get_discount_by_code( $_POST['discount_code'] );

    if( false == $discount )
        return $subscription_data;

    if( empty( $discount->recurring_payments ) )
        return $subscription_data;

    /**
     * If the subscription has a set billing amount, calculate the discounted price from it
     * and modify the billing amount with the discounted one
     *
     */
    if( ! empty( $subscription_data['payment_gateway'] ) && ! empty( $subscription_data['billing_amount'] ) ) {

        $discounted_amount = pms_calculate_discounted_amount( $subscription_data['billing_amount'], $discount );

        $subscription_data['billing_amount'] = $discounted_amount;

    /**
     * If the subscription does not have a billing amount set, calculate if based on the attached
     * subscription plan's price
     *
     */
    } else {

        $subscription_plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );

        $discounted_amount = pms_calculate_discounted_amount( $subscription_plan->price, $discount );

    }

    /**
     * If the recurring discounted amount is zero (full discount), it means basically that
     * no payments should be made for this subscription and it should be set as unlimited
     *
     */
    if( $discounted_amount == 0 ) {

        $subscription_data['expiration_date'] = '';
        $subscription_data['status']          = 'active';

    }

    return $subscription_data;

}
add_filter( 'pms_process_checkout_subscription_data', 'pms_dc_modify_subscription_data_billing_amount', 20, 2 ); //has a later execution so we can discount the Pay What You Want pricing as well

/**
 * Function that saves discount id inside _pms_member_subscriptionmeta table.
 * This is done when the discount code is to be applied only to the first payment of a recurring subscription with a free trial.
 * In this case we save the discount so we can apply it when the cron job triggers the first payment (after the free trial has ended)
 *
 * See 'pms_cron_process_member_subscriptions_payments'.
 *
 * @param object $subscription
 * @param  array $checkout_data
 */
function pms_dc_save_discount_inside_subscriptionmeta( $subscription, $checkout_data ){

    // check if subscription has free trial
    if ( !empty( $subscription->trial_end ) ) {

        // Get discount
        $discount = pms_get_discount_by_code( $_POST['discount_code'] );

        // Make sure discount doesn't apply to all recurring payment (just the first one)
        if( !empty( $discount ) && ( empty( $discount->recurring_payments ) || !$checkout_data['is_recurring'] ) && function_exists( 'pms_update_member_subscription_meta' ) ) {

            // Save the discount inside _pms_member_subscriptionmeta
            pms_update_member_subscription_meta($subscription->id, '_discount_code_id', $discount->id);

        }
    }
}
add_action( 'pms_after_inserting_subscription_data_inside_db', 'pms_dc_save_discount_inside_subscriptionmeta', 10, 2 );


/**
 * Function that applies the saved (non-recurring) discount to the first subscription payment generated by the cron job, after the free trial has ended
 *
 * @param  array $payment_data
 * @param object $subscription
 * return array $payment_data
 */
function pms_dc_modify_first_cron_payment_data( $payment_data, $subscription ){

    //check if there is any discount saved for this subscription
    if ( function_exists( 'pms_get_member_subscription_meta' ) ){
        $discount_id = pms_get_member_subscription_meta( $subscription->id, '_discount_code_id', true);
    }

    // Get discount data
    $discount = '';
    if ( !empty($discount_id) ) {
        $discount = pms_get_discount( $discount_id );
    }

    if ( is_object( $discount ) ) {

        //Apply discount
        $discounted_amount = pms_calculate_discounted_amount( $payment_data['amount'], $discount );
        $payment_data['amount'] = $discounted_amount;

        //Remove discount from db, because it applies only to the first payment
        if ( function_exists( 'pms_delete_member_subscription_meta' ) )
            pms_delete_member_subscription_meta( $subscription->id, '_discount_code_id', $discount_id);

    }

    return $payment_data;
}
add_filter( 'pms_cron_process_member_subscriptions_payment_data' , 'pms_dc_modify_first_cron_payment_data', 10, 2 );


/**
 * Function that updates discount data after it has been used
 *
 *
 */
function pms_dc_update_discount_data_after_use( $payment_id, $data, $old_data ) {

    // Get discount code used for the payment
    if( !empty( $data['status'] ) && $data['status'] == 'completed' ) {
        if( !empty( $payment_id ) && function_exists( 'pms_get_payment' ) ) {
            $payment = pms_get_payment( $payment_id );
            $code    = $payment->discount_code;
        }
    }

    if ( !empty($code) ) { // the payment used a discount code

        $discount_meta = PMS_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );

        if ( !empty($discount_meta) ) {  // the discount code exists

            if ( isset($discount_meta['pms_discount_uses'][0]) )
                $discount_meta['pms_discount_uses'][0]++;
            else
                $discount_meta['pms_discount_uses'][0] = 1;

            $discount_ID = PMS_Discount_Codes_Meta_Box::get_discount_ID_by_code( $code );

            if ( !empty($discount_ID) ) {
                update_post_meta($discount_ID, 'pms_discount_uses', $discount_meta['pms_discount_uses'][0]);

                if( ! empty( $discount_meta['pms_discount_max_uses'][0] ) && $discount_meta['pms_discount_uses'][0] >= $discount_meta['pms_discount_max_uses'][0])
                    PMS_Discount_Code::deactivate($discount_ID);
            }

            /**
             * Update (increment) discount uses for this user; they are stored inside the usermeta key 'pms_discount_uses_per_user_'.$code
             * */
            if ( !empty($payment->user_id) ) {

                $meta = get_user_meta($payment->user_id, 'pms_discount_uses_per_user_' . $code, true);

                $user_discount_uses = ( !empty( $meta ) ) ? (int)$meta : 0;
                update_user_meta( $payment->user_id, 'pms_discount_uses_per_user_'.$code, $user_discount_uses + 1 );

            }

        }
    }

}
add_action( 'pms_payment_update', 'pms_dc_update_discount_data_after_use', 10, 3 );

if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug = 'discount-codes';
    $localSerial = get_option( $slug . '_serial_number');
    $pms_nmf_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSDC', __FILE__, $slug );
}
