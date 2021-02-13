<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Changes the name of the PayPal gateway from PayPal Standard that is in Paid Member Subscriptions to
 * simply PayPal
 *
 */
function pmspp_settings_page_payment_gateway_paypal_title( $title ) {

    return __( 'PayPal', 'paid-member-subscriptions' );

}
add_filter( 'pms_settings_page_payment_gateway_paypal_title', 'pmspp_settings_page_payment_gateway_paypal_title' );


/**
 * Output the Reference Transaction checkbox
 *
 * @param array $options    - The settings option for Paid Member Subscriptions
 *
 */
function pms_settings_gateway_paypal_express_extra_fields( $options ) {

    echo '<div class="pms-form-field-wrapper">';
        echo '<label class="pms-form-field-label" for="paypal-express-reference-transactions">' . __( 'Reference Transactions', 'paid-member-subscriptions' ) . '</label>';

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            echo '<p class="description"><input type="checkbox" id="paypal-express-reference-transactions" name="pms_settings[payments][gateways][paypal][reference_transactions]" value="1" ' . ( isset( $options['payments']['gateways']['paypal']['reference_transactions'] ) ? 'checked' : '' ) . '/>' . sprintf( __( 'Check if your PayPal account has Reference Transactions enabled. %1$sLearn how to enable reference transactions.%2$s', 'paid-member-subscriptions' ), '<a href="https:/www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/paypal-pro-and-express-checkout/#Reference_Transactions" target="_blank">', '</a>' ) . '</p>';
        }
        else {
            echo '<p class="description"><input type="checkbox" id="paypal-express-reference-transactions" name="pms_payments_settings[gateways][paypal][reference_transactions]" value="1" ' . ( isset( $options['gateways']['paypal']['reference_transactions'] ) ? 'checked' : '' ) . '/>' . sprintf( __( 'Check if your PayPal account has Reference Transactions enabled. %1$sLearn how to enable reference transactions.%2$s', 'paid-member-subscriptions' ), '<a href="https:/www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/paypal-pro-and-express-checkout/#Reference_Transactions" target="_blank">', '</a>' ) . '</p>';
        }


    echo '</div>';

}
add_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', 'pms_settings_gateway_paypal_express_extra_fields' );


/**
 * Output the API username, API password and API signature for the PayPal business account
 *
 * @param array $options    - The settings option for Paid Member Subscriptions
 *
 */
if( ! function_exists('pms_settings_gateway_paypal_extra_fields') ) {

    function pms_settings_gateway_paypal_extra_fields( $options ) {

        // PayPal API fields
        $fields = array(
            'api_username' => array(
                'label' => __( 'API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Live site', 'paid-member-subscriptions'  )
            ),
            'api_password' => array(
                'label' => __( 'API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Live site', 'paid-member-subscriptions'  )
            ),
            'api_signature' => array(
                'label' => __( 'API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Live site', 'paid-member-subscriptions'  )
            ),
            'test_api_username' => array(
                'label' => __( 'Test API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Test/Sandbox site', 'paid-member-subscriptions'  )
            ),
            'test_api_password' => array(
                'label' => __( 'Test API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Test/Sandbox site', 'paid-member-subscriptions'  )
            ),
            'test_api_signature' => array(
                'label' => __( 'Test API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Test/Sandbox site', 'paid-member-subscriptions'  )
            )
        );

        foreach( $fields as $field_slug => $field_details ) {
            echo '<div class="pms-form-field-wrapper">';

            echo '<label class="pms-form-field-label" for="paypal-' . str_replace('_', '-', $field_slug) . '">' . $field_details['label'] . '</label>';

            if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 )
                echo '<input id="paypal-' . str_replace('_', '-', $field_slug) . '" type="text" name="pms_settings[payments][gateways][paypal][' . $field_slug . ']" value="' . ( isset($options['payments']['gateways']['paypal'][$field_slug]) ? $options['payments']['gateways']['paypal'][$field_slug] : '' ) . '" class="widefat" />';
            else
                echo '<input id="paypal-' . str_replace('_', '-', $field_slug) . '" type="text" name="pms_payments_settings[gateways][paypal][' . $field_slug . ']" value="' . ( isset($options['gateways']['paypal'][$field_slug]) ? $options['gateways']['paypal'][$field_slug] : '' ) . '" class="widefat" />';

            echo '<p class="description">' . $field_details['desc'] . '</p>';

            echo '</div>';
        }

    }
    add_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', 'pms_settings_gateway_paypal_extra_fields' );

}



/**
 * Adds extra fields for the member's subscription in the add new / edit subscription screen
 *
 * @param int    $subscription_id      - the id of the current subscription's edit screen. 0 for add new screen.
 * @param string $gateway_slug
 * @param array  $gateway_details
 *
 */
function pms_paypal_express_add_payment_gateway_admin_subscription_fields( $subscription_id = 0, $gateway_slug = '', $gateway_details = array() ) {

    if( empty( $gateway_slug ) || empty( $gateway_details ) )
        return;

    if( ! function_exists( 'pms_get_member_subscription_meta' ) )
        return;

    if( $gateway_slug != 'paypal_express' )
        return;

    // Set billing agreement value
    $billing_agreement_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_paypal_billing_agreement_id', true ) : '' );
    $billing_agreement_id = ( ! empty( $_POST['_paypal_billing_agreement_id'] ) ? $_POST['_paypal_billing_agreement_id'] : $billing_agreement_id );


    // PayPal Billing Agreement ID
    echo '<div class="pms-meta-box-field-wrapper">';

        echo '<label for="pms-subscription-paypal-billing-agreement-id" class="pms-meta-box-field-label">' . __( 'PayPal Billing Agreement ID', 'paid-member-subscriptions' ) . '</label>';
        echo '<input id="pms-subscription-paypal-billing-agreement-id" type="text" name="_paypal_billing_agreement_id" class="pms-subscription-field" value="' . esc_attr( $billing_agreement_id ) . '" />';

    echo '</div>';

}
add_action( 'pms_view_add_new_edit_subscription_payment_gateway_extra', 'pms_paypal_express_add_payment_gateway_admin_subscription_fields', 10, 3 );


/**
 * Checks to see if data from the extra subscription fields is valid
 *
 * @param array $admin_notices
 *
 * @return array
 *
 */
function pms_paypal_express_validate_subscription_data_admin_fields( $admin_notices = array() ) {

    // Validate the billing agreement id
    if( ! empty( $_POST['_paypal_billing_agreement_id'] ) ) {

        if( false === strpos( $_POST['_paypal_billing_agreement_id'], 'B-' ) )
            $admin_notices[] = array( 'error' => __( 'The provided PayPal Billing Agreement ID is not valid.', 'paid-member-subscriptions' ) );

    }

    return $admin_notices;

}
add_filter( 'pms_submenu_page_members_validate_subscription_data', 'pms_paypal_express_validate_subscription_data_admin_fields' );


/**
 * Saves the values for the payment gateway subscription extra fields
 *
 * @param int $subscription_id
 *
 */
function pms_paypal_express_save_payment_gateway_admin_subscription_fields( $subscription_id = 0 ) {

    if( ! function_exists( 'pms_update_member_subscription_meta' ) )
        return;

    if( $subscription_id == 0 )
        return;

    if( ! is_admin() )
        return;

    if( ! current_user_can( 'manage_options' ) )
        return;

    if( empty( $_POST['payment_gateway'] ) || $_POST['payment_gateway'] !== 'paypal_express' )
        return;

    // Update the billing agreement id
    if( isset( $_POST['_paypal_billing_agreement_id'] ) )
        pms_update_member_subscription_meta( $subscription_id, '_paypal_billing_agreement_id', sanitize_text_field( $_POST['_paypal_billing_agreement_id'] ) );


}
add_action( 'pms_member_subscription_inserted', 'pms_paypal_express_save_payment_gateway_admin_subscription_fields' );
add_action( 'pms_member_subscription_updated', 'pms_paypal_express_save_payment_gateway_admin_subscription_fields' );
