<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * PayPal Pro Class
 *
 * Handles one-time payments and subscription creation within PayPal through the API
 *
 * Subscriptions on the website are handled by the IPNs sent by PayPal, which is done in the
 * PMS_Payment_Gateway_PayPal_Express_Legacy class
 *
 */
Class PMS_Payment_Gateway_PayPal_Pro extends PMS_Payment_Gateway {

    /**
     * The features supported by the payment gateway
     *
     * @access public
     * @var array
     *
     */
    public $supports;


    /**
     * Initialisation
     *
     */
    public function init() {

        $this->supports = array(
            'gateway_scheduled_payments',
            'recurring_payments'
        );

        $active_gateways = pms_get_active_payment_gateways();

        if( in_array( 'paypal_pro', $active_gateways ) ){
            // Add the needed sections for the checkout forms
            add_filter( 'pms_extra_form_sections', array( __CLASS__, 'register_form_sections' ), 25, 2 );

            // Add the needed form fields for the checkout forms
            add_filter( 'pms_extra_form_fields', array( __CLASS__, 'register_form_fields' ), 25, 2 );
        }

    }


    /**
     * Register the Credit Card and Billing Details sections
     *
     * @param array  $sections
     * @param string $form_location
     *
     */
    public static function register_form_sections( $sections = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment' ) ) )
            return $sections;

        // Add the credit card details if it does not exist
        if( empty( $sections['credit_card_information'] ) ) {

            $sections['credit_card_information'] = array(
                'name'    => 'credit_card_information',
                'element' => 'ul',
                'id'      => 'pms-credit-card-information',
                'class'   => 'pms-credit-card-information pms-section-credit-card-information'
            );

        }

        // make sure there is no other "Billing Details" section added, probably by a payment gateway
        // add a new field section that will contain the "Billing Details" fields
        if( empty( $sections['billing_details'] ) ) {

            $sections['billing_details'] = array(
                'name'    => 'billing_details',
                'element' => 'ul',
                'class'   => 'pms-billing-details pms-section-billing-details'
            );

        }

        return $sections;

    }


    /**
     * Register the Credit Card and Billing Fields to the checkout forms
     *
     * @param array $fields
     *
     * @return array
     *
     */
    public static function register_form_fields( $fields = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment' ) ) )
            return $fields;


        /**
         * Add the Credit Card fields
         *
         */
        $fields['pms_credit_card_heading'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'heading',
            'default'         => '<h4>' . __( 'Credit Card Information', 'paid-member-subscriptions' ) . '</h4>',
            'element_wrapper' => 'li',
        );

        $fields['pms_card_number'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'text',
            'name'            => 'pms_card_number',
            'value'           => '',
            'label'           => __( 'Card Number', 'paid-member-subscriptions' ),
            'element_wrapper' => 'li',
            'required'        => 1,
            'element_attributes' => array( 'size' => 4, 'maxlength' => 4 )
        );

        $fields['pms_card_cvv'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'text',
            'name'            => 'pms_card_cvv',
            'value'           => '',
            'label'           => __( 'Card CVV', 'paid-member-subscriptions' ),
            'element_wrapper' => 'li',
            'required'        => 1,
            'element_attributes' => array( 'size' => 20, 'maxlength' => 20 )
        );

        $fields['pms_card_exp_date'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'card_expiration_date',
            'label'           => __( 'Expiration Date', 'paid-member-subscriptions' ),
            'element_wrapper' => 'li',
            'required'        => 1
        );



        /**
         * Add the Billing Fields
         *
         */
        $fields['pms_billing_details_heading'] = array(
            'section'         => 'billing_details',
            'type'            => 'heading',
            'default'         => '<h4>' . __( 'Billing Details', 'paid-member-subscriptions' ) . '</h4>',
            'element_wrapper' => 'li',
        );

        $fields['pms_billing_first_name'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_first_name',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_first_name'] ) ? $_POST['pms_billing_first_name'] : '' ),
            'label'           => __( 'Billing First Name', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_last_name'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_last_name',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_last_name'] ) ? $_POST['pms_billing_last_name'] : '' ),
            'label'           => __( 'Billing Last Name', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_address'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_address',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_address'] ) ? $_POST['pms_billing_address'] : '' ),
            'label'           => __( 'Billing Address', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_city'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_city',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_city'] ) ? $_POST['pms_billing_city'] : '' ),
            'label'           => __( 'Billing City', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_zip'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_zip',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_zip'] ) ? $_POST['pms_billing_zip'] : '' ),
            'label'           => __( 'Billing Zip / Postal Code', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_country'] = array(
            'section'         => 'billing_details',
            'type'            => 'select',
            'name'            => 'pms_billing_country',
            'default'         => ( isset( $_POST['pms_billing_country'] ) ? $_POST['pms_billing_country'] : '' ),
            'label'           => __( 'Billing Country', 'paid-member-subscriptions' ),
            'options'         => function_exists( 'pms_get_countries' ) ? pms_get_countries() : array(),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        $fields['pms_billing_state'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_state',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_state'] ) ? $_POST['pms_billing_state'] : '' ),
            'label'           => __( 'Billing State / Province', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1
        );

        return $fields;

    }


    public function process_sign_up() {

        // Do nothing if the payment id wasn't sent
        if( $this->payment_id === false )
            return;


        // Set Merchant API endpoint
        if( $this->test_mode )
            $api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
        else
            $api_endpoint = 'https://api-3t.paypal.com/nvp';

        // API Credentials
        $api_credentials = pms_get_paypal_api_credentials();

        //Get user IP address
        $user_ip_address = '';
        if ( function_exists( 'pms_get_user_ip_address' ) )
            $user_ip_address = pms_get_user_ip_address();


        if ( $this->recurring == 0 ) { // process a one time payment

             $args = array(
                 'METHOD'            => 'DoDirectPayment',
                 'USER'              => $api_credentials['username'],
                 'PWD'               => $api_credentials['password'],
                 'SIGNATURE'         => $api_credentials['signature'],
                 'VERSION'           => '68',
                 'BUTTONSOURCE'      => 'Cozmoslabs_SP',
                 'AMT'               => $this->amount,
                 'CURRENCYCODE'      => $this->currency,
                 'cancelUrl'         => pms_get_current_page_url(),
                 'returnUrl'         => pms_get_current_page_url(),
                 'IPADDRESS'         => $user_ip_address,
                 'ACCT'              => sanitize_text_field( $_POST['pms_card_number'] ),
                 'EXPDATE'           => sanitize_text_field( $_POST['pms_card_exp_month'] . $_POST['pms_card_exp_year'] ), // needs to be in the format 062019
                 'CVV2'              => sanitize_text_field( $_POST['pms_card_cvv'] ),
                 'FIRSTNAME'         => sanitize_text_field( $_POST['pms_billing_first_name'] ),
                 'LASTNAME'          => sanitize_text_field( $_POST['pms_billing_last_name'] ),
                 'STREET'            => sanitize_text_field( $_POST['pms_billing_address'] ),
                 'CITY'              => sanitize_text_field( $_POST['pms_billing_city'] ),
                 'STATE'             => sanitize_text_field( $_POST['pms_billing_state'] ),
                 'COUNTRYCODE'       => sanitize_text_field( $_POST['pms_billing_country'] ),
                 'ZIP'               => sanitize_text_field( $_POST['pms_billing_zip'] ),
                 'CUSTOM'            => $this->payment_id
             );

         }

         else {  // process a recurring payment (Create a recurring payment profile)

            $args = array(
                'METHOD'            => 'CreateRecurringPaymentsProfile',
                'USER'              => $api_credentials['username'],
                'PWD'               => $api_credentials['password'],
                'SIGNATURE'         => $api_credentials['signature'],
                'VERSION'           => '68',
                'cancelUrl'         => pms_get_current_page_url(),
                'returnUrl'         => pms_get_current_page_url(),
                'PROFILESTARTDATE'  => date( "Y-m-d\Tg:i:s", strtotime( "+" . $this->subscription_plan->duration . ' ' . $this->subscription_plan->duration_unit, time() ) ),
                'DESC'              => (is_object( $this->subscription_plan ) && isset($this->subscription_plan->name)) ? $this->subscription_plan->name : '',
                'BILLINGPERIOD'     => (is_object( $this->subscription_plan) && isset($this->subscription_plan->duration_unit)) ? ucfirst($this->subscription_plan->duration_unit) : 'Year',  // Year, Month etc.
                'BILLINGFREQUENCY'  => (is_object( $this->subscription_plan) && isset($this->subscription_plan->duration) && ($this->subscription_plan->duration > 0)) ? $this->subscription_plan->duration : 1,
                'TOTALBILLINGCYCLES'=> $this->recurring ? 0 : 1,  // 0 means it will run forever until it's cancelled, 1 is for one billing cycle (one time payments)
                'AMT'               => $this->amount,
                'CURRENCYCODE'      => $this->currency,
                'ACCT'              => sanitize_text_field( $_POST['pms_card_number'] ),
                'EXPDATE'           => sanitize_text_field( $_POST['pms_card_exp_month'] . $_POST['pms_card_exp_year'] ), // needs to be in the format 062019
                'CVV2'              => sanitize_text_field( $_POST['pms_card_cvv'] ),
                'EMAIL'             => $this->user_email,
                'FIRSTNAME'         => sanitize_text_field( $_POST['pms_billing_first_name'] ),
                'LASTNAME'          => sanitize_text_field( $_POST['pms_billing_last_name'] ),
                'STREET'            => sanitize_text_field( $_POST['pms_billing_address'] ),
                'CITY'              => sanitize_text_field( $_POST['pms_billing_city'] ),
                'STATE'             => sanitize_text_field( $_POST['pms_billing_state'] ),
                'COUNTRYCODE'       => sanitize_text_field( $_POST['pms_billing_country'] ),
                'ZIP'               => sanitize_text_field( $_POST['pms_billing_zip'] ),
                'INITAMT'           => $this->recurring && !is_null($this->sign_up_amount) ? $this->sign_up_amount : $this->amount,
                'CUSTOM'            => $this->payment_id
            );

        }

        $payment = pms_get_payment( $this->payment_id );

        // Post to PayPal
        $resp = wp_remote_post( $api_endpoint, array(
            'timeout'     => 60,
            'sslverify'   => false,
            'httpversion' => '1.1',
            'body'        => $args
            )
        );

        if ( is_wp_error( $resp ) ) {  // PayPal post failed

            $data = array(
                'message'  => $resp->get_error_message(),
                'request'  => $this->strip_request( $args ),
                'response' => $resp,
            );

            $this->log( 'paypal_api_error', $data, array(), false );

        } else if ( 200 == $resp['response']['code'] && 'OK' == $resp['response']['message'] ) {
            // we received a PayPal response, now let's check the ACK and see how the payment went

            parse_str( $resp['body'], $paypal_data );

            if ( ( strtolower($paypal_data['ACK']) == 'failure') || ( strtolower($paypal_data['ACK'] ) == 'failurewithwarning') ) {

                $this->log( 'payment_failed', $paypal_data, $args );

                $payment->update( array( 'status' => 'failed' ) );

            } elseif ( ( strtolower( $paypal_data['ACK'] ) == 'success') || ( strtolower($paypal_data['ACK'] ) == 'successwithwarning' ) ) { // payment was successful

                // Update payment status to complete, the transaction ID will be added from the IPN response later
                $payment_type = ( $this->recurring == 0 ) ? 'web_accept_paypal_pro' : 'recurring_payment_profile_created';

                // Payment data to update the payment
                $payment_data = array( 'type' => $payment_type );

                // Complete payment if it is a one-time one
                if( $payment_type == 'web_accept_paypal_pro' )
                    $payment_data['status'] = 'completed';


                // Update profile id of the payment for recurring payments
                if( !empty( $paypal_data['PROFILEID'] ) ) {
                    $payment_profile_id         = trim( $paypal_data['PROFILEID'] );
                    $payment_data['profile_id'] = $payment_profile_id;
                }

                if( $this->recurring != 0 && $payment->status != 'completed' )
                    $payment->log_data( 'paypal_ipn_waiting' );

                // Update payment
                $payment->update( $payment_data );

                // Update member data (check if it's an upgrade, renew, register or new subscription)
                $member = pms_get_member( $this->user_id );

                // Update member data only if the payment is a web_accept (or if a 100% first month discount code was used)
                // Recurring payments will be handled by the web_hook found in class-payment-gateway-paypal-express.php
                if ( isset( $this->form_location ) && ( $payment_type == 'web_accept_paypal_pro' || ( !empty( $payment->discount_code ) && $payment->amount == 0 ) ) ) {

                    switch( $this->form_location ) {

                        case 'register':
                        case 'retry_payment':
                        case 'new_subscription': {

                            $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $this->user_id, 'subscription_plan_id' => $this->subscription_plan->id ) );

                            if( ! empty( $member_subscriptions ) ) {

                                $member_subscription = $member_subscriptions[0];

                                $member_subscription->update( array(
                                    'status'             => 'active',
                                    'payment_profile_id' => ( $this->recurring && ! empty( $paypal_data['PROFILEID'] ) ? $payment_profile_id : '' )
                                ));

                            }

                            do_action( 'pms_paypal_pro_after_new_subscription', $member_subscription, $paypal_data );

                            break;
                        }

                        case 'upgrade_subscription': {

                            $subscription_plans_group = pms_get_subscription_plans_group( $this->subscription_plan->id, false );

                            foreach( $subscription_plans_group as $subscription_plan ) {

                                if( in_array( $subscription_plan->id, $member->get_subscriptions_ids() ) )
                                    $old_subscription_plan = $subscription_plan;

                            }

                            if( ! empty( $old_subscription_plan ) ) {

                                // Remove old subscription plan (the one from which he upgraded)
                                $member->remove_subscription( $old_subscription_plan->id );

                                $subscription_data = array(
                                    'user_id'              => $this->user_id,
                                    'subscription_plan_id' => $this->subscription_plan->id,
                                    'start_date'           => date( 'Y-m-d H:i:s' ),
                                    'expiration_date'      => $this->subscription_plan->get_expiration_date(),
                                    'status'               => 'active',
                                    'payment_profile_id'   => ( $this->recurring && ! empty( $paypal_data['PROFILEID'] ) ? $payment_profile_id : '' )
                                );

                                $subscription = new PMS_Member_Subscription();
                                $subscription->insert( $subscription_data );

                            }

                            do_action( 'pms_paypal_pro_after_subscription_upgrade', $subscription, $paypal_data );

                            break;
                        }

                        case 'renew_subscription': {

                            $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $this->user_id, 'subscription_plan_id' => $this->subscription_plan->id ) );

                            if( ! empty( $member_subscriptions ) ) {

                                $member_subscription = $member_subscriptions[0];

                                if( strtotime( $member_subscription->expiration_date ) < time() || $this->subscription_plan->duration === 0 )
                                    $renew_expiration_date = $this->subscription_plan->get_expiration_date();
                                else
                                    $renew_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+' . $this->subscription_plan->duration . ' ' . $this->subscription_plan->duration_unit ) );

                            }

                            $member_subscription->update( array(
                                'expiration_date'    => $renew_expiration_date,
                                'status'             => 'active',
                                'payment_profile_id' => ( $this->recurring && ! empty( $paypal_data['PROFILEID'] ) ? $payment_profile_id : '' )
                            ));

                            do_action( 'pms_paypal_pro_after_subscription_renewal', $member_subscription, $paypal_data );

                            break;
                        }

                    }

                }

                // Do Success Redirect
                if( !isset( $error_message ) && isset( $_POST['pmstkn'] ) ) {

                    $redirect_url = add_query_arg( array( 'pms_gateway_payment_id' => base64_encode( $this->payment_id ), 'pmsscscd' => base64_encode( 'subscription_plans' ) ), $this->redirect_url);
                    wp_redirect( $redirect_url );
                    exit;

                }

            }

        }

    }

    /*
     * Display Card Information & Billing Details forms
     *
     */
    public function fields() {

        global $wp_current_filter;

        if( is_array( $wp_current_filter ) && in_array( 'wp_head', $wp_current_filter ) )
            return;

        if( !defined( 'PMS_CREDIT_CARD_FORM' ) )
            define( 'PMS_CREDIT_CARD_FORM', true );
        else
            return;

        include 'views/view-billing-cc-form.php';

    }

    /*
     * Display Card Information & Billing Details on Profile Builder - Register form
     *
     */
    public function fields_pb( $output ) {

        global $wp_current_filter;

        if( is_array( $wp_current_filter ) && in_array( 'wp_head', $wp_current_filter ) )
            return $output;

        if( !defined( 'PMS_CREDIT_CARD_FORM' ) )
            define( 'PMS_CREDIT_CARD_FORM', true );
        else
            return $output;

        ob_start();
        include 'views/view-billing-cc-form.php';
        $output .= ob_get_clean();

        return $output;

    }


    /**
     * Validate billing and credit card fields
     *
     */
    public function validate_fields() {

        if ( !empty($_POST['pay_gate']) && ($_POST['pay_gate'] == 'paypal_pro') ) {


            // If subscription plan is free, skip field checks
            if( !empty( $_POST['subscription_plans'] ) ) {

                $subscription_plan = pms_get_subscription_plan( (int)$_POST['subscription_plans'] );

                if( $subscription_plan->price == 0 )
                    return;


                // If subscription plan is fully discounted
                if( !empty( $_POST['discount_code'] ) ) {

                    $discount = pms_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

                    if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 )
                        $settings = get_option( 'pms_settings' );
                    else
                        $settings = get_option( 'pms_payments_settings' );

                    if( $discount !== false  ) {

                        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
                            // If is recurring payment
                            if ((isset($_POST['pms_recurring']) && $_POST['pms_recurring'] == 1) || (isset($settings['payments']['recurring']) && $settings['payments']['recurring'] == 2)) {

                                if ((($discount->type === 'percent' && $discount->amount >= 100) || ($discount->type === 'fixed' && $discount->amount >= $subscription_plan->price)) && !empty($discount->recurring_payments))
                                    return;

                            // If it's a one time payment
                            } else {
                                if (($discount->type === 'percent' && $discount->amount >= 100) || ($discount->type === 'fixed' && $discount->amount >= $subscription_plan->price))
                                    return;
                            }
                        } else {
                            if ((isset($_POST['pms_recurring']) && $_POST['pms_recurring'] == 1) || (isset($settings['recurring']) && $settings['recurring'] == 2)) {
                                if ((($discount->type === 'percent' && $discount->amount >= 100) || ($discount->type === 'fixed' && $discount->amount >= $subscription_plan->price)) && !empty($discount->recurring_payments))
                                    return;
                            } else {
                                if (($discount->type === 'percent' && $discount->amount >= 100) || ($discount->type === 'fixed' && $discount->amount >= $subscription_plan->price))
                                    return;
                            }
                        }


                    }

                }

            }

            // Go ahead and handle errors
            $errors = apply_filters( 'pms_card_billing_errors', array(
                'pms_billing_first_name' => __( 'Please enter a Billing First Name.', 'paid-member-subscriptions' ),
                'pms_billing_last_name'  => __( 'Please enter a Billing Last Name.', 'paid-member-subscriptions' ),
                'pms_billing_address'    => __( 'Please enter a Billing Address.', 'paid-member-subscriptions' ),
                'pms_billing_city'       => __( 'Please enter a Billing City.', 'paid-member-subscriptions' ),
                'pms_billing_state'      => __( 'Please enter a Billing State.', 'paid-member-subscriptions' ),
                'pms_billing_country'    => __( 'Please enter a Billing Country.', 'paid-member-subscriptions'),
                'pms_billing_zip'        => __( 'Please enter a Billing ZIP code.', 'paid-member-subscriptions' ),
                'pms_card_number'        => __( 'Please enter a valid card number.', 'paid-member-subscriptions' ),
                'pms_card_cvv'           => __( 'Please enter a valid card verification value.', 'paid-member-subscriptions' ),
                'pms_card_exp_date'      => __( 'Please enter a valid card expiration date.', 'paid-member-subscriptions' ),
            ));

            foreach ( $errors as $key => $error ) {

                // Make sure all required fields are filled
                if ( empty($_POST[$key]) && ($key !== 'pms_card_exp_date') ) {
                    pms_errors()->add($key, $error);
                }

                // Validate a credit card number
                if ( ($key == 'pms_card_number') && (!empty($_POST[$key])) ) {
                    $card_type = pms_validate_cc_number( trim($_POST['pms_card_number']) );
                    if ( $card_type == false ) {
                        pms_errors()->add($key, $error);
                    }
                }

                // Check for past date
                if ( ($key == 'pms_card_exp_date') && ($_POST['pms_card_exp_year'] < date('Y') || $_POST['pms_card_exp_year'] == date('Y') && $_POST['pms_card_exp_month'] < date('m')) ) {
                        pms_errors()->add($key, $error);
                }

            }

        }
    }

    /**
     * Method to log certain actions/errors to the related payment
     *
     * @param  string    $code              Internal event code
     * @param  array     $response          Response we received from PayPal or array with error information (optional)
     * @param  array     $request           Data sent to PayPal (optional)
     * @param  bool      $needs_processing
     */
    public function log( $code, $response = array(), $request = array(), $needs_processing = true ) {
        $payment = pms_get_payment( $this->payment_id );

        if ( !method_exists( $payment, 'log_data') )
            return;

        if ( empty( $response ) )
            $payment->log_data( $code );
        else if ( !$needs_processing )
            $payment->log_data( $code, $response );
        else {
            $error_code = ( isset( $response['L_ERRORCODE0'] ) ? $response['L_ERRORCODE0'] : '' );

            $data = array(
                'message'  => ( isset( $response['L_LONGMESSAGE0'] ) ? $response['L_LONGMESSAGE0'] : '' ),
                'request'  => $this->strip_request( $request ),
                'response' => $response,
            );

            $payment->log_data( $code, $data, $error_code );
        }
    }

    /**
     * Strips data like user credit card details and API credentials from the request array
     *
     * @param  array   $request   Data sent to PayPal
     * @return array              Array without the listed keys
     */
    private function strip_request( $request ) {
        $keys = array( 'USER', 'PWD', 'SIGNATURE', 'BUTTONSOURCE', 'VERSION', 'ACCT', 'EXPDATE', 'CVV2', 'IPADDRESS' );

        return array_diff_key( $request, array_flip( $keys ) );
    }

    /*
     * Verify that the payment gateway is setup correctly
     *
     */
    public function validate_credentials() {

        if ( pms_get_paypal_email() === false )
            pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayPal Address is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );

        if ( pms_get_paypal_api_credentials() === false )
            pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayPal API credentials are missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );

    }

}
