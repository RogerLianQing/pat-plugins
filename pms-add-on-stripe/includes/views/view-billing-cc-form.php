<?php
/**
 * HTML output for the Billing Information and Credit Card details
 *
 */

    // Exit if accessed directly
    if( ! defined( 'ABSPATH' ) ) exit;

    // Return if PMS is not active
    if( ! defined( 'PMS_VERSION' ) ) return;

?>

<!-- Display the Credit Card Information form -->

<ul class="pms-form-fields-wrapper pms-credit-card-information">

    <?php $payment_errors = pms_errors()->get_error_messages( 'billing_cc' ); ?>

    <?php pms_display_field_errors( $payment_errors ); ?>

    <h4><?php echo apply_filters( 'pms_card_form_heading_credit_card_information', __( 'Credit Card Information', 'paid-member-subscriptions' ) ); ?></h4>

    <?php $field_errors = pms_errors()->get_error_messages( 'card_number' ); ?>

    <li class="pms-field pms-field-card-number <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_card_number"><?php echo apply_filters( 'pms_card_form_label_number', __( 'Card Number *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_card_number" name="card_number" type="text" size="20" maxlength="20" value="" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'card_cvv' ); ?>

    <li class="pms-field pms-field-card-cvv <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_card_cvv"><?php echo apply_filters( 'pms_card_form_label_cvv', __( 'Card CVV *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_card_cvv" name="card_cvv" type="text" size="4" maxlength="4" value="" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'card_exp_date' ); ?>

    <li class="pms-field pms-field-card-expiration <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_card_exp_month"><?php echo apply_filters( 'pms_card_form_label_expiration', __( 'Expiration Date *', 'paid-member-subscriptions' ) ); ?></label>

        <select id="pms_card_exp_month" name="card_exp_month">
            <?php for( $i = 1; $i <= 12; $i++ ) : ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>

        <span class="pms_expiration_date_separator"> / </span>

        <select id="pms_card_exp_year" name="card_exp_year">
            <?php
            $year = date( 'Y' );
            for( $i = $year; $i <= $year + 10; $i++ ) : ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

</ul>


<!-- Display the Billing Details form -->

<ul class="pms-form-fields-wrapper pms-billing-details">

    <h4><?php echo apply_filters( 'pms_billing_form_heading_billing_details', __( 'Billing Details', 'paid-member-subscriptions' ) ); ?></h4>

    <?php
    $first_name = '';
    $last_name = '';
    if ( is_user_logged_in() ) {
        $user_ID = get_current_user_id();
        $first_name = get_user_meta($user_ID, 'first_name', true );
        $last_name = get_user_meta($user_ID, 'last_name', true);
    }

    $field_errors = pms_errors()->get_error_messages( 'billing_first_name' ); ?>

    <li class="pms-field pms-field-billing-first-name <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_first_name"><?php echo apply_filters( 'pms_billing_form_label_first_name', __( 'Billing First Name *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_first_name" name="billing_first_name" type="text" value="<?php echo ( isset( $_POST['billing_first_name'] ) ? esc_attr( $_POST['billing_first_name'] ) : $first_name ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_last_name' ); ?>

    <li class="pms-field pms-field-billing-last-name <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_last_name"><?php echo apply_filters( 'pms_billing_form_label_last_name', __( 'Billing Last Name *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_last_name" name="billing_last_name" type="text" value="<?php echo ( isset( $_POST['billing_last_name'] ) ? esc_attr( $_POST['billing_last_name'] ) : $last_name ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_address' ); ?>

    <li class="pms-field pms-field-billing-address <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_address"><?php echo apply_filters( 'pms_billing_form_label_address', __( 'Billing Address *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_address" name="billing_address" type="text" value="<?php echo ( isset( $_POST['billing_address'] ) ? esc_attr( $_POST['billing_address'] ) : '' ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_city' ); ?>

    <li class="pms-field pms-field-billing-city <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_city"><?php echo apply_filters( 'pms_billing_form_label_city', __( 'Billing City *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_city" name="billing_city" type="text" value="<?php echo ( isset( $_POST['billing_city'] ) ? esc_attr( $_POST['billing_city'] ) : '' ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_zip' ); ?>

    <li class="pms-field pms-field-billing-zip <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_zip"><?php echo apply_filters( 'pms_billing_form_label_zip', __( 'Billing Zip / Postal Code *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_zip" name="billing_zip" type="text" value="<?php echo ( isset( $_POST['billing_zip'] ) ? esc_attr( $_POST['billing_zip'] ) : '' ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_country' ); ?>

    <li class="pms-field pms-field-billing-country <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_country"><?php echo apply_filters( 'pms_billing_form_label_country', __( 'Billing Country *', 'paid-member-subscriptions' ) ); ?></label>

        <?php if (function_exists('pms_get_countries')) {
            $country_array = pms_get_countries();

            echo '<select id="pms_billing_country" name="billing_country" >';

            foreach ( $country_array as $code => $country ) { ?>
                <option value="<?php echo $code; ?>" <?php echo (!empty($_POST['billing_country']) && ($_POST['billing_country']== $code)) ? 'selected' : ''; ?> > <?php echo $country; ?></option>
            <?php } ?>
            </select>
        <?php }
        else { ?>
            <input id="pms_billing_country" name="billing_country" type="text" value="<?php echo ( isset( $_POST['billing_country'] ) ? esc_attr( $_POST['billing_country'] ) : '' ); ?>" />
        <?php }
        pms_display_field_errors( $field_errors ); ?>
    </li>

    <?php $field_errors = pms_errors()->get_error_messages( 'billing_state' ); ?>

    <li class="pms-field pms-field-billing-state <?php ( !empty( $field_errors ) ? 'pms-field-error' : '' ) ?>">
        <label for="pms_billing_state"><?php echo apply_filters( 'pms_billing_form_label_state', __( 'Billing State / Province *', 'paid-member-subscriptions' ) ); ?></label>
        <input id="pms_billing_state" name="billing_state" type="text" value="<?php echo ( isset( $_POST['billing_state'] ) ? esc_attr( $_POST['billing_state'] ) : '' ); ?>" />

        <?php pms_display_field_errors( $field_errors ); ?>
    </li>


</ul>


