jQuery( function( $ ) {

    if( !( jQuery('#stripe-pk').length > 0 ) )
        return false

    var stripe_pk = $( '#stripe-pk' ).val();

    //compatibility with PB conditional logic. if there are multiple subscription plans fields and the first one is hidden then it won't have a value attribute because of conditional logic
    if( typeof stripe_pk == 'undefined' || stripe_pk == '' )
        stripe_pk = $('#stripe-pk').attr('conditional-value');

    if( typeof stripe_pk == 'undefined' )
        return false

    var elementStyles = window.pms_elements_styling ? window.pms_elements_styling : { base: {} }

    var stripe   = Stripe( stripe_pk )
    var elements = stripe.elements()

    var card = elements.create('card', { style: elementStyles } )

    var payment_id    = 0
    var form_location = ''

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var pms_checked_subscription   = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

    if( $('#pms-stripe-credit-card-details').length > 0 )
        card.mount('#pms-stripe-credit-card-details')

    // Paid Member Subscription submit buttons
    var payment_buttons  = 'input[name=pms_register], ';
        payment_buttons += 'input[name=pms_new_subscription], ';
        payment_buttons += 'input[name=pms_upgrade_subscription], ';
        payment_buttons += 'input[name=pms_renew_subscription], ';
        payment_buttons += 'input[name=pms_confirm_retry_payment_subscription], ';

    // Profile Builder submit buttons
        payment_buttons += '.wppb-register-user input[name=register]';

    //Regular Charges API
    $(document).on( 'click', payment_buttons, stripePaymentGatewayHandler )
    $(document).on( 'wppb_invisible_recaptcha_success', stripePaymentGatewayHandler )

    // Payment Intents
    $(document).on( 'click', payment_buttons, stripeIntentsPaymentGatewayHandler )
    $(document).on( 'wppb_invisible_recaptcha_success', stripeIntentsPaymentGatewayHandler )

    // Handle user payment reauthentication when he lands on the page
    $(document).ready( function() {

        handleCreditCardFields()

        if( $('#pms-auth-form').length == 0 )
            return

        if( form_location == '' )
            form_location = $('#pms-form-location').val()

        var data = $('#pms-auth-form').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        data.action = 'pms_reauthenticate_intent'

        $.post( pms.ajax_url, data, function( response ) {
            handleServerResponse( JSON.parse( response ), '' )
        });

        handleCreditCardFields()
    })

    // Comptibility with the other style of credit card fields from PayPal Pro
    $(document).on( 'click', 'input.pms_pay_gate', function(e) {
        handleCreditCardFields()
    })

    // Show credit card form error messages to the user as they happpen
    card.addEventListener( 'change', function( event ) {

        if( event.error ) {
            if( jQuery( '.pms-stripe-error-message' ).length > 0 )
                jQuery( '.pms-stripe-error-message' ).html( event.error.message )
            else
                jQuery( '<div class="pms-stripe-error-message">' + event.error.message + '</div>' ).insertAfter( '#pms-stripe-credit-card-details' )
        } else
            jQuery( '.pms-stripe-error-message' ).html( '' )

    })

    function stripePaymentGatewayHandler(e){

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe' )
            return;

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return;

        e.preventDefault();

        // Disable the button
        $(this).attr( 'disabled', true );

        createToken( $(this) );
    }

    function stripeIntentsPaymentGatewayHandler(e){

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe_intents' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe_intents' )
            return;

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return;

        e.preventDefault();

        removeErrors();

        var current_button = $(this)

        var clientSecret = $('input[name="setup_intent_key"]').val()

        // Disable the button
        current_button.attr( 'disabled', true );

        stripe.handleCardSetup( clientSecret, card, { payment_method_data : { billing_details : pms_stripe_get_billing_details() } } ).then(function(result) {
            //console.log( result )

            let token

            if( result.error && result.error.type && result.error.type == 'validation_error' )
                stripeResetSubmitButton( current_button )
            else {
                if ( result.error )
                    token = { id : result.error.setup_intent.id }
                else
                    token = { id : result.setupIntent.payment_method }

                stripeTokenHandler( token )
            }
        })

        // var data = $(payment_buttons).closest('form').serializeArray().reduce(function(obj, item) {
        //     obj[item.name] = item.value;
        //     return obj;
        // }, {});
        //
        // stripe.createPaymentMethod('card', card).then(function(result) {
        //     if (result.error) {
        //         console.log( result.error )
        //         var errorObj       = jQuery( '<div class="pms_field-errors-wrapper pms-is-js"><p>' + result.error.message + '</p></div>' )
        //         var scrollLocation = '.pms-credit-card-information'
        //
        //         //Show error message to user
        //         jQuery( scrollLocation + ' li.pms-field' ).after( errorObj )
        //
        //         scrollTo( scrollLocation, current_button )
        //     } else {
        //         console.log( 'created payment method; sending ajax request to website' )
        //
        //         data.action       = 'pms_create_payment_intent'
        //         data.stripe_token = result.paymentMethod.id
        //         data.current_page = window.location.href
        //
        //         $.post( pms.ajax_url, data, function( response ) {
        //             console.log( response )
        //             handleServerResponse( JSON.parse( response ), current_button )
        //         });
        //     }
        // });

    }

    /*
     * Stripe response handler
     *
     */
    function stripeTokenHandler( token ) {

        $form = $(payment_buttons).closest('form');

        $form.append( $('<input type="hidden" name="stripe_token" />').val( token.id ) );

        // We have to append a hidden input to the form to simulate that the submit
        // button has been clicked to have it to the $_POST
        var button_name = $form.find(payment_buttons).attr('name');
        var button_value = $form.find(payment_buttons).val();

        $form.append( $('<input type="hidden" />').val( button_value ).attr('name', button_name ) );

        $form.get(0).submit();

    }

    function createToken( payment_button ){
        stripe.createToken(card).then(function(result) {
            if( result.error )
                stripeResetSubmitButton( payment_button );
            else
                stripeTokenHandler( result.token );
        })
    }

    function stripeResetSubmitButton( target ) {

        setTimeout( function() {
            target.attr( 'disabled', false ).removeClass( 'pms-submit-disabled' ).val( target.data( 'original-value' ) ).blur();
        }, 1 );

    }

    function handleServerResponse( response, payment_button ){
        //console.log( response )

        if( payment_id == 0 )
            payment_id = response.payment_id

        if( form_location == '' )
            form_location = response.form_location

        if( response.validation_errors )
            addValidationErrors( response.errors, payment_button )
        else if ( response.error ) {
            //console.log( 'error' )

            // If error, redirect to payment failed page
            if( response.redirect_url )
                window.location.replace( response.redirect_url )

        } else if ( response.requires_action ) {
            //console.log( 'requires auth ')

            // Use Stripe.js to handle required card action
            stripe.handleCardAction(
                response.payment_intent_client_secret
            ).then(function(result) {
                if ( result.error ) {
                    //console.log( '3D Secure confirmation failed' )

                    //send error data to server
                    var data                    = {}
                        data.action             = 'pms_failed_payment_authentication'
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.error              = result.error
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });

                } else {
                    // console.log( 'payment intent needs to be confirmed again on the server' )
                    // console.log( result )

                    var data                    = {}
                        data.action             = 'pms_confirm_payment_intent'
                        data.stripe_token       = result.paymentIntent.id
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });
                }
            });
        } else {
            //console.log( 'success' )

            //create a dummy form and submit it
            var redirect_url = ''

            if( !response.redirect_url )
                redirect_url = window.location.href
            else
                redirect_url = response.redirect_url

            var form = $('<form action="' + redirect_url + '" method="post">' +
                        '<input type="text" name="pms_register" value="1" /></form>')

            $('body').append(form)
                form.submit()
        }

    }

    function addValidationErrors( errors, payment_button ){
        var scrollLocation = '';

        $.each( errors, function(index, value){

            if( index == 'form_general' ){
                $.pms_add_general_error( value )

                scrollLocation = '.pms-form'
            } else if( index == 'subscription_plan' ){
                $.pms_add_subscription_plans_error( value )

                if( scrollLocation == '' )
                    scrollLocation = '.pms-field-subscriptions'
            } else {
                $.pms_add_field_error( value, index )

                if( scrollLocation == '' && value.indexOf('pms_billing') !== -1 )
                    scrollLocation = '.pms-billing-details'
                else
                    scrollLocation = '.pms-form'
            }

        })

        scrollTo( scrollLocation, payment_button )
    }

    function removeErrors(){
        $('.pms_field-errors-wrapper').remove()
    }

    function scrollTo( scrollLocation, payment_button ){
        var form = $(scrollLocation)[0]

        var coord  = form.getBoundingClientRect().top + window.pageYOffset
        var offset = -170

        window.scrollTo({
            top      : coord + offset,
            behavior : 'smooth'
        })

        stripeResetSubmitButton( payment_button )
    }

    function getAutoRenewalStatus(){
        if( jQuery( 'input[name="pms_default_recurring"]' ).val() == 2 )
            return 1

        if( pms_checked_subscription.data( 'recurring' ) == 2 )
            return 1

        if( jQuery( 'input[name="pms_recurring"]' ).is(':visible') && jQuery( 'input[name="pms_recurring"]' ).is(':checked') )
            return 1

        if( !jQuery( 'input[name="pms_recurring"]' ).is(':visible') && jQuery( 'input[name="intent_id"]' ).length > 0 )
            return 1

        return 0
    }

    function handleCreditCardFields(){

        if( jQuery( '.pms_pay_gate:checked' ).val() == 'paypal_pro' ){

            jQuery('#pms_card_number').attr( 'name', 'pms_card_number' )
            jQuery('#pms_card_cvv').attr( 'name', 'pms_card_cvv' )
            jQuery('#pms_card_exp_month').attr( 'name', 'pms_card_exp_month' )
            jQuery('#pms_card_exp_year').attr( 'name', 'pms_card_exp_year' )

            jQuery( '#pms-stripe-credit-card-details' ).hide()
            jQuery( '#pms-credit-card-information li:not(.pms-field-type-heading)' ).show()

        } else if( jQuery( '.pms_pay_gate:checked' ).val() == 'stripe_intents' || jQuery( '.pms_pay_gate:checked' ).val() == 'stripe' ){

            jQuery('#pms_card_number').removeAttr( 'name' )
            jQuery('#pms_card_cvv').removeAttr( 'name' )
            jQuery('#pms_card_exp_month').removeAttr( 'name' )
            jQuery('#pms_card_exp_year').removeAttr( 'name' )

            jQuery( '#pms-credit-card-information li:not(.pms-field-type-heading)' ).hide()
            jQuery( '#pms-stripe-credit-card-details' ).show()

        }

    }

    function pms_stripe_get_billing_details() {

        var data = {}

        var email = jQuery( '.pms-form input[name="user_email"], .wppb-user-forms input[name="email"]' ).val()

        if( typeof email != 'undefined' )
            data.email = email

        var name = ''

        if( jQuery( '.pms-billing-details input[name="pms_billing_first_name"]' ).length > 0 )
            name = name + jQuery( '.pms-billing-details input[name="pms_billing_first_name"]' ).val() + ' '
        else if( jQuery( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).length > 0 )
            name = name + jQuery( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).val() + ' '

        if( jQuery( '.pms-billing-details input[name="pms_billing_last_name"]' ).length > 0 )
            name = name + jQuery( '.pms-billing-details input[name="pms_billing_last_name"]' ).val()
        else if( jQuery( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).length > 0 )
            name = name + jQuery( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).val()

        if( name.length > 0 )
            data.name = name

        if( jQuery( '.pms-billing-details ').length > 0 ){

            data.address = {
                city        : jQuery( '.pms-billing-details input[name="pms_billing_city"]' ).val(),
                country     : jQuery( '.pms-billing-details input[name="pms_billing_country"]' ).val(),
                line1       : jQuery( '.pms-billing-details input[name="pms_billing_address"]' ).val(),
                postal_code : jQuery( '.pms-billing-details input[name="pms_billing_zip"]' ).val(),
                state       : jQuery( '.pms-billing-details input[name="pms_billing_state"]' ).val()
            }

        }

        return data

    }
});
