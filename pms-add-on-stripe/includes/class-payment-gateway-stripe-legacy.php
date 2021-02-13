<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_Payment_Gateway_Stripe_Legacy extends PMS_Payment_Gateway {

    /** The Stripe Token generated from the credit card information
     *
     * @access protected
     * @var string
     *
     */
    protected $stripe_token;

    /**
     * The Stripe API secret key
     *
     * @access protected
     * @var string
     *
     */
    protected $secret_key;

    /**
     * The discount code being used on checkout
     *
     * @access protected
     * @var string
     *
     */
    protected $discount = false;


    /**
	 * Initialisation
	 *
	 */
    public function init() {

        // add Stripe publishable keys into the form
        if( !pms_stripe_check_filter_from_class_exists( 'pms_get_output_payment_gateways', get_class($this), 'field_publishable_key' ) )
            add_filter( 'pms_get_output_payment_gateways', array( $this, 'field_publishable_key' ), 10, 2 );

        // handle subscription plan change for member
        add_action( 'pms_member_replace_subscription', array( $this, 'replace_customer_subscription' ), 10, 4 );

        // Set Stripe token obtained with Stripe JS
        $this->stripe_token = ( !empty( $_POST['stripe_token'] ) ? sanitize_text_field( $_POST['stripe_token'] ) : '' );

        // Set API secret key
        $api_credentials  = pms_get_stripe_api_credentials();
        $this->secret_key = ( !empty( $api_credentials['secret_key'] ) ? $api_credentials['secret_key'] : '' );

        // Set discount
        if( !empty( $_POST['discount_code'] ) )
            $this->discount = pms_get_discount_by_code( trim( sanitize_text_field( $_POST['discount_code'] ) ) );

        $this->set_appinfo();
    }


    /*
     * Process payment
     *
     */
    public function process_sign_up() {

        if( empty( $this->payment_id ) )
            return;

        if( empty( $this->stripe_token ) )
            return;

        if( empty( $this->secret_key ) )
            return;


        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        // Verify API key
        try {

            Stripe\Account::retrieve();

        } catch( Exception $e ) {

            $error = __( 'Stripe API key is not valid.', 'paid-member-subscriptions' );

        }


        // Get payment
        $payment = pms_get_payment( $this->payment_id );


        // Return customer if exists, if not create him
        if( false === ( $customer = $this->get_customer( $this->user_id ) ) )

            $customer = $this->create_customer();

        else {
            $customer->source = $this->stripe_token;
            $customer->save();
        }


        // Make a one time payment
        if( !$this->recurring ) {

            // Make the payment
            try {

                if( $customer ) {

                    $charge = Stripe\Charge::create(
                        array(
                            'amount'        => round( $this->amount * 100 ), // because cents
                            'currency'      => $this->currency,
                            'customer'      => $customer->id,
                            'description'   => $this->subscription_plan->name
                        )
                    );

                    $payment_data = array(
                        'payment_id'        => $payment->id,
                        'user_id'           => $payment->user_id,
                        'amount'            => $this->amount / 100, // because cents
                        'status'            => 'completed',
                        'type'              => 'stripe_card_one_time',
                        'transaction_id'    => $charge->id,
                        'subscription_id'   => $payment->subscription_id
                    );

                    // Complete payment
                    $payment->update( array( 'type' => $payment_data['type'], 'transaction_id' => $payment_data['transaction_id'], 'status' => 'completed' ) );

                    // Update member details
                    $this->update_member_subscription_data( $payment_data );

                } else {

                    $error = __( 'Something went wrong. Please try again.', 'paid-member-subscriptions' );

                }

            } catch( Exception $e ) {

                $error = __( 'Something went wrong. Please try again.', 'paid-member-subscriptions' );

            }

        // Make recurring payment
        } else {

            // Create the subscription plan in Stripe if it doesn't exist
            if( ! $this->get_plan( $this->subscription_plan->id ) )
                $this->create_plan();


            // Handle discount codes
            $coupon = null;

            if( !empty( $_POST['discount_code'] ) ) {

                if( false === ( $coupon = $this->get_coupon( $this->discount->code ) ) )
                    $coupon = $this->create_coupon();

                $coupon = ( false === $coupon ? null : $coupon );

            }


            // Add the subscription to the customer
            try {

                if( $customer ) {

                    $subscription = $customer->subscriptions->create( array(
                        'plan'      => $this->subscription_plan->id,
                        'coupon'    => $coupon,
                        'metadata'  => array(
                            'payment_id'    => $this->payment_id,
                            'form_location' => $this->form_location
                        )
                    ));

                    // Update payment data
                    $payment->update( array( 'type' => 'stripe_card_subscription_payment', 'profile_id' => $subscription->id ) );

                }

            } catch( Exception $e ) {

                $error = __( 'Something went wrong. Please try again.', 'paid-member-subscriptions' );

            }

        }


        // Do success redirect
        if( !isset( $error ) && isset( $_POST['pmstkn'] ) ) {

            $redirect_url = add_query_arg( array( 'pms_gateway_payment_id' => base64_encode($this->payment_id),  'pmsscscd' => base64_encode('subscription_plans') ), $this->redirect_url );
            wp_redirect( $redirect_url );
            exit;

        /**
         * For payment errors we redirect to the same page and we will replace the entire content with
         * an error message
         *
         */
        } elseif( isset( $error ) ) {

            $redirect_url = add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => ( $this->form_location == 'register' ) ? '1' : '0' ), pms_get_current_page_url( true ) );
            wp_redirect( $redirect_url );
            exit;

        }
        else
            return;

    }


    /*
     * Process events sent by Stripe
     *
     */
    public function process_webhooks() {

        if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'stripe' )
            return;

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        // Get the input
        $input = @file_get_contents("php://input");
        $event = json_decode( $input );

        // Verify that the event was sent by Stripe
        if( isset( $event->id ) ) {

            try {
                Stripe\Event::retrieve( $event->id );
            } catch( Exception $e ) {
                return;
            }

        } else
            return;


        // Handle events
        switch( $event->type ) {

            case 'invoice.payment_succeeded':

                // Put together the payment data we have
                $invoice            = $event->data->object;
                $invoice_line_items = $invoice->lines->data;

                $payment_id = isset( $invoice_line_items[0]->metadata->payment_id ) ? $invoice_line_items[0]->metadata->payment_id : 0;
                $payment    = pms_get_payment( $payment_id );

                $payment_data = array(
                    'payment_id'        => $payment_id,
                    'user_id'           => $payment->user_id,
                    'amount'            => $invoice->amount_due / 100, // because cents
                    'status'            => 'completed',
                    'type'              => 'stripe_card_subscription_payment',
                    'transaction_id'    => $invoice->charge,
                    'profile_id'        => $invoice->subscription,
                    'subscription_id'   => $payment->subscription_id
                );

                $payment = pms_get_payment( $payment_id );

                // Handle payment completion
                if( $payment->status == 'pending' )

                    $payment->update( array( 'status' => $payment_data['status'], 'transaction_id' => $payment_data['transaction_id'], 'profile_id' => $payment_data['profile_id'] ) );

                elseif( $payment->status == 'complete' ) {

                    $payment->add( $payment_data['user_id'], $payment_data['status'], date('Y-m-d H:i:s'), $payment_data['amount'], $payment_data['subscription_id'] );
                    $payment->update( array( 'transaction_id' => $payment_data['transaction_id'], 'profile_id' => $payment_data['profile_id'] ) );

                }

                // Update member data
                $this->update_member_subscription_data( $payment_data );

                die('200');

                break; // End of case 'invoice.payment_succeeded'

            case 'customer.subscription.deleted':

                $subscription   = $event->data->object;
                $payment        = pms_get_payment( $subscription->metadata->payment_id );

                $member         = pms_get_member( $payment->user_id );
                $member_subscription = $member->get_subscription( $payment->subscription_id );

                if( !empty( $member_subscription ) )
                    $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $member_subscription['expiration_date'], 'canceled' );

                die('200');

                break; // End of case 'customer.subscription.deleted'

            default:
                break;

        }

    }


    /*
     * Handles all the member subscription data flow after a payment is complete
     *
     */
    public function update_member_subscription_data( $payment_data ) {

        if( empty( $payment_data ) || !is_array( $payment_data ) )
            return false;


        // Update member subscriptions
        $member = pms_get_member( $payment_data['user_id'] );

        // Update status to active for subscriptions that exist both in the user subscriptions and also in the payment info
        foreach( $member->subscriptions as $member_subscription ) {
            if( $member_subscription['subscription_plan_id'] == $payment_data['subscription_id'] ) {

                // If subscription is pending it is a new one
                if( $member_subscription['status'] == 'pending' ) {
                    $member_subscription_expiration_date = $member_subscription['expiration_date'];

                    // This is an old subscription
                } else {

                    $subscription_plan = pms_get_subscription_plan( $member_subscription['subscription_plan_id'] );

                    if( strtotime( $member_subscription['expiration_date'] ) < time() || $subscription_plan->duration === 0 )
                        $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                    else
                        $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription['expiration_date'] . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                }

                // Update subscription
                $member->update_subscription( $member_subscription['subscription_plan_id'], $member_subscription['start_date'], $member_subscription_expiration_date, 'active' );

                // Add payment recurring id aka profile id number
                if( !empty( $payment_data['profile_id'] ) )
                    pms_member_add_payment_profile_id( $member->user_id, $member_subscription['subscription_plan_id'], $payment_data['profile_id'] );

            }
        }

        /*
         * If the subscription plan id sent by the IPN is not found in the members subscriptions
         * then it could be an update to an existing one
         *
         * If one of the member subscriptions is in the same group as the payment subscription id,
         * the payment subscription id is an upgrade to the member subscription one
         *
         */
        if( !in_array( $payment_data['subscription_id'], $member->get_subscriptions_ids() ) ) {

            $group_subscription_plans = pms_get_subscription_plans_group( $payment_data['subscription_id'], false );

            if( count($group_subscription_plans) > 1 ) {

                // Get current member subscription that will be upgraded
                foreach( $group_subscription_plans as $subscription_plan ) {
                    if( in_array( $subscription_plan->id, $member->get_subscriptions_ids() ) ) {
                        $member_subscription = $subscription_plan;
                        break;
                    }
                }

                if( isset($member_subscription) ) {

                    do_action( 'pms_stripe_before_upgrade_subscription', $member_subscription->id, $payment_data );

                    $member->remove_subscription( $member_subscription->id );

                    $new_subscription_plan = pms_get_subscription_plan( $payment_data['subscription_id'] );

                    $member->add_subscription( $new_subscription_plan->id, date('Y-m-d H:i:s'), $new_subscription_plan->get_expiration_date(), 'active' );

                    // Add payment recurring id aka profile id number
                    if( !empty( $payment_data['profile_id'] ) )
                        pms_member_add_payment_profile_id( $member->user_id, $new_subscription_plan->id, $payment_data['profile_id'] );

                    do_action( 'pms_stripe_after_upgrade_subscription', $member_subscription->id, $payment_data );
                }

            }

        }

        return true;

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

        include_once 'views/view-billing-cc-form.php';

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
        include_once 'views/view-billing-cc-form.php';
        $output .= ob_get_clean();

        return $output;

    }


    /**
     * Display Stripe's publishable key field in the form
     *
     */
    public function field_publishable_key( $output, $pms_settings ) {
        static $has_run = false;

        if( !$has_run ){
            if( pms_is_payment_test_mode() )
                $prefix = 'test_';
            else
                $prefix = '';

            if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
                if( isset( $pms_settings['payments']['gateways']['stripe'][$prefix . 'api_publishable_key'] ) )
                    $output .= '<input type="hidden" id="stripe-pk" value="' . esc_attr(trim($pms_settings['payments']['gateways']['stripe'][$prefix . 'api_publishable_key'])) . '" />';
            } else {
                if( isset( $pms_settings['gateways']['stripe'][$prefix . 'api_publishable_key'] ) )
                    $output .= '<input type="hidden" id="stripe-pk" value="' . esc_attr(trim($pms_settings['gateways']['stripe'][$prefix . 'api_publishable_key'])) . '" />';
            }

            //$has_run = true;
        }

        return $output;

    }


    /**
     * Validate billing and credit card fields
     *
     */
    public function validate_fields() {

        if ( !empty($_POST['pay_gate']) && ($_POST['pay_gate'] == 'stripe') ) {


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
                        $settings['payments'] = get_option( 'pms_payments_settings', array() );

                    if( $discount !== false  ) {
                        // If is recurring payment
                        if( (isset( $_POST['pms_recurring'] ) && $_POST['pms_recurring'] == 1) || ( isset( $settings['payments']['recurring'] ) && $settings['payments']['recurring'] == 2 ) ) {

                            if( ( ($discount->type === 'percent' && $discount->amount >= 100) || ( $discount->type === 'fixed' && $discount->amount >= $subscription_plan->price ) ) && !empty( $discount->recurring_payments ) )
                                return;

                            // If it's a one time payment
                        } else {

                            if( ( $discount->type === 'percent' && $discount->amount >= 100 ) || ( $discount->type === 'fixed' && $discount->amount >= $subscription_plan->price ) )
                                return;
                        }
                    }

                }

            }

            // Go ahead and handle the errors
            $errors = apply_filters( 'pms_card_billing_errors', array(
                // 'pms_billing_first_name' => __( 'Please enter a Billing First Name.', 'paid-member-subscriptions' ),
                // 'pms_billing_last_name'  => __( 'Please enter a Billing Last Name.', 'paid-member-subscriptions' ),
                // 'pms_billing_address'    => __( 'Please enter a Billing Address.', 'paid-member-subscriptions' ),
                // 'pms_billing_city'       => __( 'Please enter a Billing City.', 'paid-member-subscriptions' ),
                // 'pms_billing_state'      => __( 'Please enter a Billing State.', 'paid-member-subscriptions' ),
                // 'pms_billing_country'    => __( 'Please enter a Billing Country.', 'paid-member-subscriptions'),
                // 'pms_billing_zip'        => __( 'Please enter a Billing ZIP code.', 'paid-member-subscriptions' ),
                'stripe_token'           => __( 'An error occurred. Please try again.', 'paid-member-subscriptions' )
            ));

            // Make sure all required fields are filled
            foreach ($errors as $key => $error) {

                if ( empty($_POST[$key]) && ($key !== 'pms_card_exp_date') ) {
                    pms_errors()->add($key, $error);
                }

            }

        }
    }


    /**
     * Replace the customer's subscription in Stripe when the admin changes the subscription from the WP back-end
     *
     * @param bool $update_result
     * @param int  $user_id
     * @param int  $new_subscription_plan_id
     * @param int  $old_subscription_plan_id
     *
     */
    public function replace_customer_subscription( $update_result, $user_id, $new_subscription_plan_id, $old_subscription_plan_id ) {

        if( !$update_result )
            return;

        // We presume the plan exists
        $plan_exists = true;

        if( !$this->get_plan( $new_subscription_plan_id ) ) {

            $this->subscription_plan = pms_get_subscription_plan( $new_subscription_plan_id );
            $this->currency          = pms_get_active_currency();

            $plan_exists = $this->create_plan();

        }

        // Do nothing if the plan exists
        if( !$plan_exists )
            return;

        $customer = $this->get_customer( $user_id );

        // Do nothing if the customer doesn't exist
        if( !$customer )
            return;

        $member = pms_get_member( $user_id );

        $member_subscription = $member->get_subscription( $new_subscription_plan_id );

        // Do nothing if the subscription's payment profile is missing
        if( empty( $member_subscription['payment_profile_id'] ) )
            return;

        if( ! pms_is_stripe_payment_profile_id( $member_subscription['payment_profile_id'] ) )
            return;

        $subscription = Stripe\Subscription::retrieve( $member_subscription['payment_profile_id'] );

        $subscription->plan = $new_subscription_plan_id;
        $subscription->save();

    }


    /*
     * Checks to see whether a plan exists or not in Stripe
     *
     * @param int $subscription_plan_id
     *
     */
    public function get_plan( $subscription_plan_id = 0 ) {

        if( $subscription_plan_id == 0 )
            return false;

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            $plan = Stripe\Plan::retrieve( $subscription_plan_id );
            return $plan;

        } catch( Exception $e ) {
            return false;
        }

    }


    /*
     * Creates a plan in Stripe to resemble the one on the website
     *
     */
    protected function create_plan() {

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            Stripe\Plan::create( array(
                'id'             => $this->subscription_plan->id,
                'name'           => $this->subscription_plan->name,
                'amount'         => round( $this->subscription_plan->price * 100 ), // because cents
                'currency'       => $this->currency,
                'interval'       => $this->subscription_plan->duration_unit,
                'interval_count' => $this->subscription_plan->duration
            ));

            return true;

        } catch( Exception $e ) {
            return false;
        }

    }


    /*
     * Returns the Stripe customer if it exists based on the user_id provided
     *
     * @param int $user_id
     *
     */
    public function get_customer( $user_id = 0 ) {

        if( $user_id == 0 )
            $user_id = $this->user_id;

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            // Get saved Stripe ID
            $customer_stripe_id = get_user_meta( $user_id, 'pms_stripe_customer_id', true );

            // Return if the customer id is missing
            if( empty( $customer_stripe_id ) )
                return false;

            // Get customer
            $customer = Stripe\Customer::retrieve( $customer_stripe_id );

            // If empty name on the Stripe Customer try to add it from the website
            if( apply_filters( 'pms_stripe_update_customer_name', true ) && empty( $customer->name ) ){

                $name = $this->get_user_name( $user_id );

                if( !empty( $name ) ){
                    \Stripe\Customer::update(
                        $customer_stripe_id,
                        array(
                            'name' => $this->get_user_name( $user_id )
                        )
                    );
                }

            }

            if( isset( $customer->deleted ) && $customer->deleted == true )
                return false;
            else
                return $customer;

        } catch( Exception $e ) {
            return false;
        }

    }


    //@NOTE: Duplicated in the Payment Intents gateway for now, see if you can remove the stripe token from this and add it later
    //@NOTE: (in the register billing function, I think we already do it and this is redundant (or left from the initial Stripe integration))
    /*
     * Create a customer in Stripe to resemble the user on the website
     *
     */
    protected function create_customer() {

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            $customer = Stripe\Customer::create( array(
                'source'        => $this->stripe_token,
                'email'         => $this->user_email,
                'description'   => "User ID: " . $this->user_id
            ));

            // Save Stripe customer ID
            update_user_meta( $this->user_id, 'pms_stripe_customer_id', $customer->id );

            return $customer;

        } catch( Exception $e ) {

            $this->log_error_data( $e );

            return false;

        }

    }


    /*
     * Returns a Stripe coupon
     *
     */
    public function get_coupon( $discount_code = '' ) {

        if( empty( $discount_code ) )
            return false;

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            $coupon = Stripe\Coupon::retrieve( $discount_code );

            return $coupon;

        } catch( Exception $e ) {

            return false;

        }

    }


    /*
     * Create a coupon in Stripe to resemble the one on the website
     *
     */
    protected function create_coupon() {

        // Set API key
        Stripe\Stripe::setApiKey( $this->secret_key );

        try {

            // Set general args
            $coupon_args = array(
                'id'        => $this->discount->code,
                'currency'  => $this->currency
            );

            // Set percent or fixed discount arg
            if( $this->discount->type == 'fixed' )
                $coupon_args['amount_off'] = $this->discount->amount * 100; // because cents
            elseif( $this->discount->type == 'percent' )
                $coupon_args['percent_off'] = $this->discount->amount;

            // Set duration arg
            if( empty( $this->discount->recurring_payments ) )
                $coupon_args['duration'] = 'once';
            else
                $coupon_args['duration'] = 'forever';


            // Create the coupon
            $coupon = Stripe\Coupon::create( $coupon_args );

            return $coupon;

        } catch( Exception $e ) {

            return false;

        }

    }


    /*
     * Cancels a subscription in Stripe
     *
     */
    public function cancel_subscription( $subscription_id = '' ) {

        $customer = $this->get_customer();

        if( !$customer )
            return false;

        if( empty( $subscription_id ) )
            return false;

        try {

            $customer->subscriptions->retrieve( $subscription_id )->cancel();
            return true;

        } catch ( Exception $e ) {

            return false;

        }

    }

    /*
     * Save error data to payment
     *
     */
    public function log_error_data( $exception ) {
        if ( empty( $exception ) ) return;

        $payment = new PMS_Payment( $this->payment_id );

        if ( !method_exists( $payment, 'log_data' ) )
            return;

        $trace = $exception->getTrace();

        //If there's no error code in the exception, use a generic one
        $error_code = 'card_declined';

        $data = array();

        if ( !empty( $trace[0]['args'][0] ) ) {
            $error_obj = json_decode( $trace[0]['args'][0] );

            $data['data'] = $error_obj->error;

            if ( !empty( $error_obj->error->decline_code ) )
                $error_code = $error_obj->error->decline_code;
            else if ( !empty( $error_obj->error->code ) )
                $error_code = $error_obj->error->code;
        }

        $data['message'] = $exception->getMessage();
        $data['desc']    = 'stripe response';

        $payment->log_data( 'payment_failed', $data, $error_code );

    }

    /**
     * Send software information to Stripe with each request
     */
    private function set_appinfo() {
        Stripe\Stripe::setAppInfo(
          "Paid Member Subscriptions (WordPress)",
           PMS_STRIPE_VERSION,
           "https://www.cozmoslabs.com/",
           "pp_partner_Fk2RgE0VrGkLiR"
        );
    }

    public function get_user_name( $user_id ){
        $user = get_userdata( $user_id );

        if( empty( $user ) )
            return '';

        return $user->first_name . ' ' . $user->last_name;
    }

    /**
     * Checks if billing info is available in $_POST and returns an array with all the info
     * The array is ready to use with the Stripe API (see Customer -> Shipping field)
     */
    public function get_billing_details() {

        if( empty( $_POST ) )
            return array();

        $name = '';
        $billing_details = array();

        $keys = array(
            'line1'       => 'pms_billing_address',
            'city'        => 'pms_billing_city',
            'postal_code' => 'pms_billing_zip',
            'country'     => 'pms_billing_country',
            'state'       => 'pms_billing_state'
        );

        foreach( $keys as $stripe_key => $pms_key ) {
            if( !empty( $_POST[$pms_key] ) )
                $billing_details[$stripe_key] = $_POST[$pms_key];
        }

        return $billing_details;

    }
}
