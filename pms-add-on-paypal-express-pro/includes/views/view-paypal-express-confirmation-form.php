<?php
/*
 * Confirmation form HTML output to display to the user to confirm his payment
 *
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

?>

<?php
    global $pms_checkout_details;
    $payment           = pms_get_payment( $pms_checkout_details['payment_data']['payment_id'] );
    $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );
    $is_recurring      = ( !empty( $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] ) && $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] == 1 ? true : false );
    $is_discounted     = ( !empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) && !is_null( $pms_checkout_details['payment_data']['sign_up_amount'] ) ? true : false );
    $pms_settings      = get_option( 'pms_payments_settings' );
    $currency_symbol   = pms_get_currency_symbol( $pms_settings['currency'] );
?>

<div id="pms_ppe_confirm_payment" class="pms-form">
    <h3><?php echo apply_filters( 'pms_ppe_confirm_payment_heading', __( 'Payment confirmation', 'paid-member-subscriptions' ) ); ?></h3>
    <table id="pms-confirm-payment">
        <thead>
            <tr>
                <th><?php _e( 'Subscription', 'paid-member-subscriptions' ); ?></th>
                <th><?php _e( 'Price', 'paid-member-subscriptions' ); ?></th>
                <th><?php _e( 'Recurring', 'paid-member-subscriptions' ); ?></th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?php echo $subscription_plan->name; ?></td>
                <td>
                    <?php
                        echo ( $is_recurring && $is_discounted ) ? '<div>' . $currency_symbol . $pms_checkout_details['payment_data']['sign_up_amount'] . '</div>' : '';
                        echo '<div>' . $currency_symbol . $pms_checkout_details['PAYMENTREQUEST_0_AMT'] . '</div>';
                    ?>
                </td>
                <td>
                    <?php echo ( $is_recurring && $is_discounted ? '<div>' . sprintf( _n( 'For first %d %s', 'For first %d %ss', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration, $subscription_plan->duration_unit ) . '</div>' : '' ); ?>
                    <?php echo ( $is_recurring ? '<div>' . sprintf( _n( 'Once every %d %s', 'Once every %d %ss', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration, $subscription_plan->duration_unit ) . '</div>' : '-' ); ?>
                </td>
            </tr>
        </tbody>

    </table>

    <form id="pms-paypal-express-confirmation-form" action="<?php echo remove_query_arg( array( 'token', 'PayerID' ), pms_get_current_page_url() ) ?>" method="POST">

        <?php wp_nonce_field( 'pms_payment_process_confirmation', 'pmstkn' ); ?>

        <input type="hidden" name="pms_token" value="<?php echo ( isset($pms_checkout_details['TOKEN']) ? esc_attr( $pms_checkout_details['TOKEN'] ) : '' ); ?>" />
        <?php if( $is_recurring ): ?>
            <input type="hidden" name="pms_is_recurring" value="1" />
        <?php endif; ?>

        <input type="submit" value="<?php echo apply_filters( 'pms_ppe_confirm_payment_button_value', __( 'Confirm payment', 'paid-member-subscriptions' ) ); ?>" />
    </form>
</div>
