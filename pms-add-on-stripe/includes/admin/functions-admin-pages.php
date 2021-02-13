<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Function that adds the HTML for Stripe in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_add_settings_content_stripe( $options ) {

    // Stripe API fields
    $fields = array(
        'test_api_publishable_key' => array(
            'label' => __( 'Test Publishable Key', 'paid-member-subscriptions' )
        ),
        'test_api_secret_key' => array(
            'label' => __( 'Test Secret Key', 'paid-member-subscriptions' )
        ),
        'api_publishable_key' => array(
            'label' => __( 'Live Publishable Key', 'paid-member-subscriptions' )
        ),
        'api_secret_key' => array(
            'label' => __( 'Live Secret Key', 'paid-member-subscriptions' )
        )
    );

    echo '<div class="pms-payment-gateway-wrapper">';

        echo '<h4 class="pms-payment-gateway-title">' . __( 'Stripe', 'paid-member-subscriptions' ) . '</h4>';

        foreach( $fields as $field_slug => $field_options ) {
            echo '<div class="pms-form-field-wrapper">';

            echo '<label class="pms-form-field-label" for="stripe-' . str_replace( '_', '-', $field_slug ) . '">' . $field_options['label'] . '</label>';

            if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 )
                echo '<input id="stripe-' . str_replace( '_', '-', $field_slug ) . '" type="text" name="pms_settings[payments][gateways][stripe][' . $field_slug . ']" value="' . ( isset( $options['payments']['gateways']['stripe'][$field_slug] ) ? $options['payments']['gateways']['stripe'][$field_slug] : '' ) . '" class="widefat" />';
            else
                echo '<input id="stripe-' . str_replace( '_', '-', $field_slug ) . '" type="text" name="pms_payments_settings[gateways][stripe][' . $field_slug . ']" value="' . ( isset( $options['gateways']['stripe'][$field_slug] ) ? $options['gateways']['stripe'][$field_slug] : '' ) . '" class="widefat" />';

            if( isset( $field_options['desc'] ) )
                echo '<p class="description">' . $field_options['desc'] . '</p>';

            echo '</div>';
        }

        do_action( 'pms_settings_page_payment_gateway_stripe_extra_fields', $options );

    echo '</div>';


}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_add_settings_content_stripe' );


/**
 * Adds extra fields for the member's subscription in the add new / edit subscription screen
 *
 * @param int    $subscription_id      - the id of the current subscription's edit screen. 0 for add new screen.
 * @param string $gateway_slug
 * @param array  $gateway_details
 *
 */
function pms_stripe_add_payment_gateway_admin_subscription_fields( $subscription_id = 0, $gateway_slug = '', $gateway_details = array() ) {

    if( empty( $gateway_slug ) || empty( $gateway_details ) )
        return;

    if( ! function_exists( 'pms_get_member_subscription_meta' ) )
        return;

    if( $gateway_slug != 'stripe' )
        return;

    // Set card id value
    $stripe_customer_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_customer_id', true ) : '' );
    $stripe_customer_id = ( ! empty( $_POST['_stripe_customer_id'] ) ? $_POST['_stripe_customer_id'] : $stripe_customer_id );

    // Set card id value
    $stripe_card_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_card_id', true ) : '' );
    $stripe_card_id = ( ! empty( $_POST['_stripe_card_id'] ) ? $_POST['_stripe_card_id'] : $stripe_card_id );

    // Stripe Customer ID
    echo '<div class="pms-meta-box-field-wrapper">';

        echo '<label for="pms-subscription-stripe-customer-id" class="pms-meta-box-field-label">' . __( 'Stripe Customer ID', 'paid-member-subscriptions' ) . '</label>';
        echo '<input id="pms-subscription-stripe-customer-id" type="text" name="_stripe_customer_id" class="pms-subscription-field" value="' . esc_attr( $stripe_customer_id ) . '" />';

    echo '</div>';

    // Stripe Card ID
    echo '<div class="pms-meta-box-field-wrapper">';

        echo '<label for="pms-subscription-stripe-card-id" class="pms-meta-box-field-label">' . __( 'Stripe Card ID', 'paid-member-subscriptions' ) . '</label>';
        echo '<input id="pms-subscription-stripe-card-id" type="text" name="_stripe_card_id" class="pms-subscription-field" value="' . esc_attr( $stripe_card_id ) . '" />';

    echo '</div>';

}
add_action( 'pms_view_add_new_edit_subscription_payment_gateway_extra', 'pms_stripe_add_payment_gateway_admin_subscription_fields', 10, 3 );


/**
 * Checks to see if data from the extra subscription fields is valid
 *
 * @param array $admin_notices
 *
 * @return array
 *
 */
function pms_stripe_validate_subscription_data_admin_fields( $admin_notices = array() ) {

    // Validate the customer id
    if( ! empty( $_POST['_stripe_customer_id'] ) ) {

        if( false === strpos( $_POST['_stripe_customer_id'], 'cus_' ) )
            $admin_notices[] = array( 'error' => __( 'The provided Stripe Customer ID is not valid.', 'paid-member-subscriptions' ) );

    }

    // Validate the card id
    if( ! empty( $_POST['_stripe_card_id'] ) ) {

        if( preg_match( '(card_|pm_)', $_POST['_stripe_card_id'] ) !== 1 )
            $admin_notices[] = array( 'error' => __( 'The provided Stripe Card ID is not valid.', 'paid-member-subscriptions' ) );

    }

    return $admin_notices;

}
add_filter( 'pms_submenu_page_members_validate_subscription_data', 'pms_stripe_validate_subscription_data_admin_fields' );


/**
 * Saves the values for the payment gateway subscription extra fields
 *
 * @param int $subscription_id
 *
 */
function pms_stripe_save_payment_gateway_admin_subscription_fields( $subscription_id = 0 ) {

    if( ! function_exists( 'pms_update_member_subscription_meta' ) )
        return;

    if( $subscription_id == 0 )
        return;

    if( ! is_admin() )
        return;

    if( ! current_user_can( 'manage_options' ) )
        return;

    if( empty( $_POST['payment_gateway'] ) || ( $_POST['payment_gateway'] !== 'stripe' && $_POST['payment_gateway'] !== 'stripe_intents' ) )
        return;

    // Update the customer id
    if( isset( $_POST['_stripe_customer_id'] ) ){

        if( pms_update_member_subscription_meta( $subscription_id, '_stripe_customer_id', sanitize_text_field( $_POST['_stripe_customer_id'] ) ) )
            pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'stripe_customer_id', 'who' => get_current_user_id() ) );

    }


    // Update the card id
    if( isset( $_POST['_stripe_card_id'] ) ){

        if( pms_update_member_subscription_meta( $subscription_id, '_stripe_card_id', sanitize_text_field( $_POST['_stripe_card_id'] ) ) )
            pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'stripe_card_id', 'who' => get_current_user_id() ) );

    }

}
add_action( 'pms_member_subscription_inserted', 'pms_stripe_save_payment_gateway_admin_subscription_fields' );
add_action( 'pms_member_subscription_updated', 'pms_stripe_save_payment_gateway_admin_subscription_fields' );

function pms_stripe_sanitize_settings( $settings ){

    if( empty( $settings['gateways']['stripe'] ) )
        return $settings;

    // Test Keys
    if( !empty( $settings['gateways']['stripe']['test_api_publishable_key'] ) && strpos( $settings['gateways']['stripe']['test_api_publishable_key'], 'pk_test' ) === false ){
        $settings['gateways']['stripe']['test_api_publishable_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][test_api_publishable_key]', 'test-api-pk', __( 'The Test Publishable Key you entered is invalid. The key should start with `pk_test`.', 'paid-member-subscriptions' ) );
    }

    if( !empty( $settings['gateways']['stripe']['test_api_secret_key'] ) && strpos( $settings['gateways']['stripe']['test_api_secret_key'], 'sk_test' ) === false ){
        $settings['gateways']['stripe']['test_api_secret_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][test_api_secret_key]', 'test-api-sk', __( 'The Test Secret Key you entered is invalid. The key should start with `sk_test`.', 'paid-member-subscriptions' ) );
    }

    // Live Keys
    if( !empty( $settings['gateways']['stripe']['api_publishable_key'] ) && strpos( $settings['gateways']['stripe']['api_publishable_key'], 'pk_live' ) === false ){
        $settings['gateways']['stripe']['api_publishable_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][api_publishable_key]', 'live-api-pk', __( 'The Live Publishable Key you entered is invalid. The key should start with `pk_live`.', 'paid-member-subscriptions' ) );
    }

    if( !empty( $settings['gateways']['stripe']['api_secret_key'] ) && strpos( $settings['gateways']['stripe']['api_secret_key'], 'sk_live' ) === false ){
        $settings['gateways']['stripe']['api_secret_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][api_secret_key]', 'live-api-sk', __( 'The Live Secret Key you entered is invalid. The key should start with `sk_live`.', 'paid-member-subscriptions' ) );
    }

    return $settings;

}
add_filter( 'pms_sanitize_settings', 'pms_stripe_sanitize_settings' );

function pms_stripe_add_currencies( $currencies ){

    if( version_compare( PMS_VERSION, '2.0.0', '<' ) )
        return $currencies;

    // We're overwriting the currencies from the main plugin
    $currencies = array(
        'USD' => __( 'US Dollar', 'paid-member-subscriptions' ),
        'EUR' => __( 'Euro', 'paid-member-subscriptions' ),
        'GBP' => __( 'Pound sterling', 'paid-member-subscriptions' ),
        'CAD' => __( 'Canadian dollar', 'paid-member-subscriptions' ),
        'AED' => __( 'United Arab Emirates dirham', 'paid-member-subscriptions' ),
		'AFN' => __( 'Afghan afghani', 'paid-member-subscriptions' ),
		'ALL' => __( 'Albanian lek', 'paid-member-subscriptions' ),
		'AMD' => __( 'Armenian dram', 'paid-member-subscriptions' ),
		'ANG' => __( 'Netherlands Antillean guilder', 'paid-member-subscriptions' ),
		'AOA' => __( 'Angolan kwanza', 'paid-member-subscriptions' ),
		'ARS' => __( 'Argentine peso', 'paid-member-subscriptions' ),
		'AUD' => __( 'Australian dollar', 'paid-member-subscriptions' ),
		'AWG' => __( 'Aruban florin', 'paid-member-subscriptions' ),
		'AZN' => __( 'Azerbaijani manat', 'paid-member-subscriptions' ),
		'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'paid-member-subscriptions' ),
		'BBD' => __( 'Barbadian dollar', 'paid-member-subscriptions' ),
		'BDT' => __( 'Bangladeshi taka', 'paid-member-subscriptions' ),
		'BGN' => __( 'Bulgarian lev', 'paid-member-subscriptions' ),
		'BIF' => __( 'Burundian franc', 'paid-member-subscriptions' ),
		'BMD' => __( 'Bermudian dollar', 'paid-member-subscriptions' ),
		'BND' => __( 'Brunei dollar', 'paid-member-subscriptions' ),
		'BOB' => __( 'Bolivian boliviano', 'paid-member-subscriptions' ),
		'BRL' => __( 'Brazilian real', 'paid-member-subscriptions' ),
		'BSD' => __( 'Bahamian dollar', 'paid-member-subscriptions' ),
		'BWP' => __( 'Botswana pula', 'paid-member-subscriptions' ),
		'BZD' => __( 'Belize dollar', 'paid-member-subscriptions' ),
		'CDF' => __( 'Congolese franc', 'paid-member-subscriptions' ),
		'CHF' => __( 'Swiss franc', 'paid-member-subscriptions' ),
		'CLP' => __( 'Chilean peso', 'paid-member-subscriptions' ),
		'CNY' => __( 'Chinese yuan', 'paid-member-subscriptions' ),
		'COP' => __( 'Colombian peso', 'paid-member-subscriptions' ),
		'CRC' => __( 'Costa Rican col&oacute;n', 'paid-member-subscriptions' ),
		'CVE' => __( 'Cape Verdean escudo', 'paid-member-subscriptions' ),
		'CZK' => __( 'Czech koruna', 'paid-member-subscriptions' ),
		'DJF' => __( 'Djiboutian franc', 'paid-member-subscriptions' ),
		'DKK' => __( 'Danish krone', 'paid-member-subscriptions' ),
		'DOP' => __( 'Dominican peso', 'paid-member-subscriptions' ),
		'DZD' => __( 'Algerian dinar', 'paid-member-subscriptions' ),
		'EGP' => __( 'Egyptian pound', 'paid-member-subscriptions' ),
		'ERN' => __( 'Eritrean nakfa', 'paid-member-subscriptions' ),
		'ETB' => __( 'Ethiopian birr', 'paid-member-subscriptions' ),
		'FJD' => __( 'Fijian dollar', 'paid-member-subscriptions' ),
		'FKP' => __( 'Falkland Islands pound', 'paid-member-subscriptions' ),
		'GEL' => __( 'Georgian lari', 'paid-member-subscriptions' ),
		'GIP' => __( 'Gibraltar pound', 'paid-member-subscriptions' ),
		'GMD' => __( 'Gambian dalasi', 'paid-member-subscriptions' ),
		'GNF' => __( 'Guinean franc', 'paid-member-subscriptions' ),
		'GTQ' => __( 'Guatemalan quetzal', 'paid-member-subscriptions' ),
		'GYD' => __( 'Guyanese dollar', 'paid-member-subscriptions' ),
		'HKD' => __( 'Hong Kong dollar', 'paid-member-subscriptions' ),
		'HNL' => __( 'Honduran lempira', 'paid-member-subscriptions' ),
		'HRK' => __( 'Croatian kuna', 'paid-member-subscriptions' ),
		'HTG' => __( 'Haitian gourde', 'paid-member-subscriptions' ),
		'HUF' => __( 'Hungarian forint', 'paid-member-subscriptions' ),
		'IDR' => __( 'Indonesian rupiah', 'paid-member-subscriptions' ),
		'ILS' => __( 'Israeli new shekel', 'paid-member-subscriptions' ),
		'INR' => __( 'Indian rupee', 'paid-member-subscriptions' ),
		'ISK' => __( 'Icelandic kr&oacute;na', 'paid-member-subscriptions' ),
		'JMD' => __( 'Jamaican dollar', 'paid-member-subscriptions' ),
		'JPY' => __( 'Japanese yen', 'paid-member-subscriptions' ),
		'KES' => __( 'Kenyan shilling', 'paid-member-subscriptions' ),
		'KGS' => __( 'Kyrgyzstani som', 'paid-member-subscriptions' ),
		'KHR' => __( 'Cambodian riel', 'paid-member-subscriptions' ),
		'KMF' => __( 'Comorian franc', 'paid-member-subscriptions' ),
		'KRW' => __( 'South Korean won', 'paid-member-subscriptions' ),
		'KYD' => __( 'Cayman Islands dollar', 'paid-member-subscriptions' ),
		'KZT' => __( 'Kazakhstani tenge', 'paid-member-subscriptions' ),
		'LAK' => __( 'Lao kip', 'paid-member-subscriptions' ),
		'LBP' => __( 'Lebanese pound', 'paid-member-subscriptions' ),
		'LKR' => __( 'Sri Lankan rupee', 'paid-member-subscriptions' ),
		'LRD' => __( 'Liberian dollar', 'paid-member-subscriptions' ),
		'LSL' => __( 'Lesotho loti', 'paid-member-subscriptions' ),
		'MAD' => __( 'Moroccan dirham', 'paid-member-subscriptions' ),
		'MDL' => __( 'Moldovan leu', 'paid-member-subscriptions' ),
		'MGA' => __( 'Malagasy ariary', 'paid-member-subscriptions' ),
		'MKD' => __( 'Macedonian denar', 'paid-member-subscriptions' ),
		'MMK' => __( 'Burmese kyat', 'paid-member-subscriptions' ),
		'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'paid-member-subscriptions' ),
		'MOP' => __( 'Macanese pataca', 'paid-member-subscriptions' ),
		'MUR' => __( 'Mauritian rupee', 'paid-member-subscriptions' ),
		'MVR' => __( 'Maldivian rufiyaa', 'paid-member-subscriptions' ),
		'MWK' => __( 'Malawian kwacha', 'paid-member-subscriptions' ),
		'MXN' => __( 'Mexican peso', 'paid-member-subscriptions' ),
		'MYR' => __( 'Malaysian ringgit', 'paid-member-subscriptions' ),
		'MZN' => __( 'Mozambican metical', 'paid-member-subscriptions' ),
		'NAD' => __( 'Namibian dollar', 'paid-member-subscriptions' ),
		'NGN' => __( 'Nigerian naira', 'paid-member-subscriptions' ),
		'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'paid-member-subscriptions' ),
		'NOK' => __( 'Norwegian krone', 'paid-member-subscriptions' ),
		'NPR' => __( 'Nepalese rupee', 'paid-member-subscriptions' ),
		'NZD' => __( 'New Zealand dollar', 'paid-member-subscriptions' ),
		'PAB' => __( 'Panamanian balboa', 'paid-member-subscriptions' ),
		'PEN' => __( 'Sol', 'paid-member-subscriptions' ),
		'PGK' => __( 'Papua New Guinean kina', 'paid-member-subscriptions' ),
		'PHP' => __( 'Philippine peso', 'paid-member-subscriptions' ),
		'PKR' => __( 'Pakistani rupee', 'paid-member-subscriptions' ),
		'PLN' => __( 'Polish z&#x142;oty', 'paid-member-subscriptions' ),
		'PYG' => __( 'Paraguayan guaran&iacute;', 'paid-member-subscriptions' ),
		'QAR' => __( 'Qatari riyal', 'paid-member-subscriptions' ),
		'RON' => __( 'Romanian leu', 'paid-member-subscriptions' ),
		'RSD' => __( 'Serbian dinar', 'paid-member-subscriptions' ),
		'RUB' => __( 'Russian ruble', 'paid-member-subscriptions' ),
		'RWF' => __( 'Rwandan franc', 'paid-member-subscriptions' ),
		'SAR' => __( 'Saudi riyal', 'paid-member-subscriptions' ),
		'SBD' => __( 'Solomon Islands dollar', 'paid-member-subscriptions' ),
		'SCR' => __( 'Seychellois rupee', 'paid-member-subscriptions' ),
		'SEK' => __( 'Swedish krona', 'paid-member-subscriptions' ),
		'SGD' => __( 'Singapore dollar', 'paid-member-subscriptions' ),
		'SHP' => __( 'Saint Helena pound', 'paid-member-subscriptions' ),
		'SLL' => __( 'Sierra Leonean leone', 'paid-member-subscriptions' ),
		'SOS' => __( 'Somali shilling', 'paid-member-subscriptions' ),
		'SRD' => __( 'Surinamese dollar', 'paid-member-subscriptions' ),
		'SZL' => __( 'Swazi lilangeni', 'paid-member-subscriptions' ),
		'THB' => __( 'Thai baht', 'paid-member-subscriptions' ),
		'TJS' => __( 'Tajikistani somoni', 'paid-member-subscriptions' ),
		'TOP' => __( 'Tongan pa&#x2bb;anga', 'paid-member-subscriptions' ),
		'TRY' => __( 'Turkish lira', 'paid-member-subscriptions' ),
		'TTD' => __( 'Trinidad and Tobago dollar', 'paid-member-subscriptions' ),
		'TWD' => __( 'New Taiwan dollar', 'paid-member-subscriptions' ),
		'TZS' => __( 'Tanzanian shilling', 'paid-member-subscriptions' ),
		'UAH' => __( 'Ukrainian hryvnia', 'paid-member-subscriptions' ),
		'UGX' => __( 'Ugandan shilling', 'paid-member-subscriptions' ),
		'UYU' => __( 'Uruguayan peso', 'paid-member-subscriptions' ),
		'UZS' => __( 'Uzbekistani som', 'paid-member-subscriptions' ),
		'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'paid-member-subscriptions' ),
		'VUV' => __( 'Vanuatu vatu', 'paid-member-subscriptions' ),
		'WST' => __( 'Samoan t&#x101;l&#x101;', 'paid-member-subscriptions' ),
		'XAF' => __( 'Central African CFA franc', 'paid-member-subscriptions' ),
		'XCD' => __( 'East Caribbean dollar', 'paid-member-subscriptions' ),
		'XOF' => __( 'West African CFA franc', 'paid-member-subscriptions' ),
		'XPF' => __( 'CFP franc', 'paid-member-subscriptions' ),
		'YER' => __( 'Yemeni rial', 'paid-member-subscriptions' ),
		'ZAR' => __( 'South African rand', 'paid-member-subscriptions' ),
		'ZMW' => __( 'Zambian kwacha', 'paid-member-subscriptions' ),
    );

    return $currencies;

}
add_filter( 'pms_currencies', 'pms_stripe_add_currencies' );
