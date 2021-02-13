<?php
    $subscription = pms_get_member_subscriptions( array( 'user_id' => $payment->user_id, 'subscription_plan_id' => $payment->subscription_id ) );
    $subscription = $subscription[0];
    $plan         = pms_get_subscription_plan( $payment->subscription_id );

    $token       = pms_get_member_subscription_meta( $subscription->id, '_stripe_card_id', true );
    $credentials = pms_get_stripe_api_credentials();
?>

<div class="pms-auth-form__wrapper">
    <p>
        <?php printf( __( 'You have a pending payment of %s for the %s subscription. Authentication is required for this transaction.', 'paid-member-subscriptions' ), '<strong>' . $payment->amount . pms_get_currency_symbol( pms_get_active_currency() ) . '</strong>', '<strong>' . $plan->name . '</strong>' ); ?>
    </p>

    <div class="pms-loader"></div>

    <p class="pms-auth-form__loader-msg">
        <?php _e( 'Please wait...', 'paid-member-subscriptions' ); ?>
    </p>

    <form class="pms-form" id="pms-auth-form">
        <input type="hidden" name="payment_id" value="<?php echo esc_attr( $payment->id ); ?>" />
        <input type="hidden" name="stripe_pk" id="stripe-pk" value="<?php echo esc_attr( $credentials['publishable_key'] ); ?>" />
        <input type="hidden" name="intent_id" value="<?php echo esc_attr( $_GET['pms-intent-id'] ); ?>" />
        <input type="hidden" name="stripe_token" value="<?php echo esc_attr( $token ); ?>" />
        <input type="hidden" name="form_location" id="pms-form-location" value="payment_authentication_form" />
        <input type="hidden" name="subscription_plans" value="<?php echo esc_attr( $plan->id ); ?>" />
        <input type="hidden" name="pms_recurring" value="true" />
    </form>
</div>
