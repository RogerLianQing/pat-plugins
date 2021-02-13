// Display "Billing Details" section if selected subscription plan is NOT free (price !== 0)

jQuery( function($) {

    var $section_billing_details = $('.pms-section-billing-details');

    // Subscription plan and payment gateway selector
    var subscription_plan_selector = 'input[name=subscription_plans]';
    var paygate_selector 		   = 'input[name=pay_gate]';

    var $pms_checked_subscription = $( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' );

    // Check to see if this is a PB form with email confirmation active
    var is_pb_email_confirmation_on = ( $section_billing_details.siblings('.pms-email-confirmation-payment-message').length > 0 ? true : false );

    $(document).ready( function() {

        // If no email confirmation and subscription is not free, show billing details
        if ( $pms_checked_subscription.length > 0 && !is_pb_email_confirmation_on && $pms_checked_subscription.data('price') != 0 )
            $section_billing_details.show()

        $(document).on( 'click', paygate_selector, function() {

            if( $pms_checked_subscription.length > 0 && !is_pb_email_confirmation_on && $pms_checked_subscription.data('price') != 0 ){
                $section_billing_details = $('.pms-section-billing-details')
                $section_billing_details.show()
            }

	    })

	    $(document).on( 'click', subscription_plan_selector, function() {

            $pms_checked_subscription = $( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

            if( ! is_pb_email_confirmation_on )
                $(paygate_selector + ':checked').trigger('click')

	    })

    })



    /**
     * Add a class "pms-has-value" to the billing email address if it is not empty
     * This will be used to not autocomplete the field when the user_email is being introduced
     *
     */
    $(document).ready( function() {

        $('input[name=pms_billing_email]').each( function() {

            if( $(this).val() != '' )
                $(this).addClass( 'pms-has-value' );

        });

    });

    /**
     * Fill in billing email address when typing the email address
     *
     */
    $(document).on( 'keyup', '#pms_user_email, .wppb-form-field input[name=email]', function() {

        if( $(this).closest('form').find('[name=pms_billing_email]').length == 0 )
            return false;

        if( $(this).closest('form').find('[name=pms_billing_email]').hasClass('pms-has-value') )
            return false;

        $(this).closest('form').find('[name=pms_billing_email]').val( $(this).val() );

    });

});
