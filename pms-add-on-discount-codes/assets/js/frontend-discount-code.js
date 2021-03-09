/*
 * JavaScript for front-end discount code display
 *
 */
jQuery(document).ready(function($) {

    // Cache the parent form of the apply discount button
    var $pms_form;

    // Cache the value of the last checked discount code
    var last_checked_discount_code;

    /**
     * Trigger automatically "Apply" discount button when the user already entered a discount code and selects another subscription plan, or checks the "Automatically renew subscription" checkbox.
     * This will update the discount message shown below the field.
     *
     */
	
	var discount_length = $('#pms_subscription_plans_discount_code').length;
	var disscount_val = $('#pms_subscription_plans_discount_code').val();
	
	if ( discount_length == 0){
		discount_length = 4
	}
	if(disscount_val == ''){
		disscount_val = 'ShowtheReceipt'
	}
    $('.pms-subscription-plan input[type="radio"][name="subscription_plans"]').click(function(){

        // If subscription is not free and discount code field is not empty
        if (  (( $(this).attr("data-price") > 0) && ( discount_length > 0 )) || disscount_val == 'ShowtheReceipt' ){

            $('#pms-apply-discount').trigger('click');

        } else {
            $('#pms-subscription-plans-discount-messages-wrapper').hide();
            $('#pms-subscription-plans-discount-messages').hide();
        }

    });
	
	

    $('.pms-subscription-plan-auto-renew input[type="checkbox"][name="pms_recurring"]').click(function(){

        // If discount code field is not empty
        if ( discount_length > 0 ){

            $('#pms-apply-discount').trigger('click');

        } else {

            $('#pms-subscription-plans-discount-messages-wrapper').hide();
            $('#pms-subscription-plans-discount-messages').hide();

        }

    });


    /**
     * Handles discount code validation when the user clicks the "Apply" discount button
     *
     */
    $('#pms-apply-discount').click(function(e){
		var discount_length = $('#pms_subscription_plans_discount_code').length;
		var disscount_val = $('#pms_subscription_plans_discount_code').val();
		
			if ( discount_length == 0){
		discount_length = 4
	}
	if(disscount_val == ''){
		disscount_val = 'ShowtheReceipt'
	}

        e.preventDefault();

        // If undefined, cache the parent form
        if( typeof $pms_form == 'undefined' )
            $pms_form = $(this).closest('form');

        var $subscription_plan = '';

        $('.pms-subscription-plan input[type="radio"]').each(function(){
            if($(this).is(':checked')){
                $subscription_plan = $(this);
            }
        });

        if( $subscription_plan == '' ) {
            $subscription_plan = $('input[type=hidden][name=subscription_plans]');
        }

		
		
        if( disscount_val == '' ) {
            $('#pms-subscription-plans-discount-messages-wrapper').fadeOut( 350 );
            $('#pms-subscription-plans-discount-messages').fadeOut( 350 )

            $subscription_plan.data( 'discounted-price', false )
            jQuery(document).trigger( 'pms_discount_error' )

            return false;
        }

        // Cache the discount code
        last_checked_discount_code = disscount_val;

        pwyw_price = '';

        if ( $('input[name="subscription_price_'+$subscription_plan.val()+'"]').length != 0 )
            pwyw_price = $('input[name="subscription_price_'+$subscription_plan.val()+'"]').val();

        var data = {
            'action'      : 'pms_discount_code',
            'code'        : $.trim( disscount_val),
            'subscription': $subscription_plan.val(),
            'recurring'   : $('input[name="pms_recurring"]:checked').val(),
            'pwyw_price'  : pwyw_price,
            'pmstkn'      : $pms_form.find('#pmstkn').val()
        };

        if( data.pmstkn === undefined && jQuery( '.wppb-register-user' ).length > 0 )
            data.pmstkn = 'pb_form'

        //Make sure it's not an empty discount
        if ( data['code'] !== '' ) {

            $('#pms-subscription-plans-discount-messages').hide();
            $('#pms-subscription-plans-discount-messages-wrapper').show();
            $('#pms-subscription-plans-discount-messages-loading').fadeIn(350);

            // We can also pass the url value separately from ajaxurl for front end AJAX implementations
            jQuery.post(pms_discount_object.ajax_url, data, function (response) {

                if (response.success != undefined) {

                    // Add success message
                    $('#pms-subscription-plans-discount-messages').removeClass('pms-discount-error');
                    $('#pms-subscription-plans-discount-messages').addClass('pms-discount-success');

					
					//Roger Custom
					if(disscount_val == 'ShowtheReceipt'){

						var string = response.success.message;
						var number = string.replace('Discount successfully applied! Amount to be charged is','')+ ' ';
						var number = number.replace('. ', '').replace('&#36;', '').replace(',', '');
						var aftertax = parseFloat(number).toFixed(2);

						var beforetax = (aftertax / 1.13).toFixed(2);
						var counts = [0, 12.99, 999.99, 79.99, 29.99, 0.5],
							  goal = beforetax;

						var closest = counts.reduce(function(prev, curr) {
							  return (Math.abs(curr - goal) < Math.abs(prev - goal) ? curr : prev);
							});
						
						var plan = ''
						switch ( $subscription_plan.val().toString()) {
							  case '615':
								plan = "ACI VIP Membership";
								break;
							  case '616':
								plan = "Premium Membership";
								break;
							  case '617':
								plan = "Regular Membership";
								break;
							  case '713':
								plan = "Test Membership";
								break;

							}
						
						var tax = (aftertax - closest).toFixed(2);
						var text ='<p> Summary' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'You have Chosen: ' +  ' </span>' +  plan + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Price: ' +  ' </span>'+ ' &#36; ' +  closest.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Tax (HST): ' +  ' </span>'+ ' &#36; ' +  tax.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Subtotal: ' +  ' </span>'+ ' &#36; ' +  aftertax.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Discount: ' +  ' </span>'+ ' &#36; ' +  '0.00' + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Total To Pay: ' +  ' </span>'+ ' &#36; ' +  aftertax.toString() + ' </p> '

					   $('#pms-subscription-plans-discount-messages-loading').fadeOut(350, function () {
                        $('#pms-subscription-plans-discount-messages').html(text).fadeIn(350);
                    })
					   }
					// If there is discount
					else{
						var aftertax = 0;
						var string = response.success.message;
						var amount = string.replace('Discount successfully applied! Amount to be charged now is', '').replace('then', '').replace('every year', '').replace('&#36;', '');
						var amount = (amount + ' ').replace('. ', '').replace(',', '')
						var numbers = amount.split("&#36;");
						//var jk = parseFloat(amount);

						var beforetax = 0;
						var plan = '';
						var tax = 0;
						switch ( $subscription_plan.val().toString()) {
							  case '615':
								plan = "ACI VIP Membership";
								beforetax = 999.99;
								tax = (999.99 * 0.13).toFixed(2);
								aftertax = (999.99 * 1.13).toFixed(2);
								break;
							  case '616':
								plan = "Premium Membership";
								beforetax = 79.99;
								tax = (79.99 * 0.13).toFixed(2);
								aftertax = (79.99 * 1.13).toFixed(2);
								break;
							  case '617':
								plan = "Regular Membership";
								beforetax = 29.99;
								tax = (29.99 * 0.13).toFixed(2);
								aftertax = (29.99 * 1.13).toFixed(2);
								break;
							  case '713':
								plan = "Test Membership";
								beforetax = 0.5;
								tax = (0.5 * 0.13).toFixed(2);
								aftertax = (0.5 * 1.13).toFixed(2);
								break;
							  case '18':
								plan = "silver";
								beforetax = 12.99;
								tax = (12.99 * 0.13).toFixed(2);
								aftertax = (12.99 * 1.13).toFixed(2);
								break;

							}

						var discount = (aftertax - numbers[0]).toFixed(2);
						
						var text ='<p> Summary' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'You Have Chosen: ' +  ' </span>' +  plan + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Price: ' +  ' </span>'+ ' &#36; ' +  beforetax.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Tax (HST): ' +  ' </span>'+ ' &#36; ' +  tax.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Subtotal: ' +  ' </span>'+ ' &#36; ' +  aftertax.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Discount: ' +  ' </span>'+ ' &#36; ' +  discount.toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Total To Pay: ' +  ' </span>'+ ' &#36; ' +  numbers[0].toString() + ' </p> ' + 
							'<p style="text-align:right"> <span style="float:left">'+ 'Next Payment: ' +  ' </span>'+ ' &#36; ' +  numbers[1].toString() + ' </p> ' + 
							
					   $('#pms-subscription-plans-discount-messages-loading').fadeOut(350, function () {
                        $('#pms-subscription-plans-discount-messages').html(text).fadeIn(350);
                    });

					   }
					
                   
                    // Hide payment fields
                    if (response.is_full_discount)
                        hide_payment_fields($pms_form);
                    else
                        show_payment_fields($pms_form);

                    $subscription_plan.data( 'price-original', $subscription_plan.data('price') )
                    $subscription_plan.data( 'price', response.discounted_price )
                    $subscription_plan.data( 'discounted-price', true )

                    jQuery(document).trigger( 'pms_discount_success' )

                }

                if (response.error != undefined) {

                    // Add error message
                    $('#pms-subscription-plans-discount-messages').removeClass('pms-discount-success');
                    $('#pms-subscription-plans-discount-messages').addClass('pms-discount-error');

					// Roger Custom
					var first_char = ["A", "B", "a", "b"];
					if( first_char.includes(data['code'].charAt(0)) ){
						response.error.message =  "Oops, you might have used a promotional code from another website. Try to use it on https://canadianinventorsassociation.com/";
					}
					if( data['code'] == 'ShowtheReceipt'){
						response.error.message =  "This subscription is free!";
					}
					
                    $('#pms-subscription-plans-discount-messages-loading').fadeOut(350, function () {
                        $('#pms-subscription-plans-discount-messages').html(response.error.message).fadeIn(350);
                    });

                    // Show payment fields
                    show_payment_fields($pms_form);

                    $subscription_plan.data( 'price', $subscription_plan.data('price-original') )
                    $subscription_plan.data( 'discounted-price', false )

                    jQuery(document).trigger( 'pms_discount_error' )

                }

            });
        } else {
			
            $subscription_plan.data( 'price', $subscription_plan.data('price-original') )
            $subscription_plan.data( 'discounted-price', false )

            jQuery(document).trigger( 'pms_discount_error' )

        }

    });

    /**
     * If there is a discount code value already set on document ready
     * apply it
     *
     */
    if( $('input[name=discount_code]').val() != '' )
        $('#pms-apply-discount').trigger('click');

    /**
     * When losing focus of the discount code field, directly apply the discount
     *
     */
    $('input[name=discount_code]').on( 'blur', function() {

        if( last_checked_discount_code != $('input[name=discount_code]').val() )
            $('#pms-apply-discount').trigger('click');

        if ( $('input[name=discount_code]').val() == '' )
            show_payment_fields( $pms_form );
    });

    /**
     * Clones and caches the wrappers for the payment gateways and the credit card / billing information
     * It replaces these wrappers with empy spans that represent the wrappers
     *
     */
    function hide_payment_fields( $form ) {

        if( typeof $form.pms_paygates_wrapper == 'undefined' )
            $form.pms_paygates_wrapper = $form.find('#pms-paygates-wrapper').clone();

        $form.find('#pms-paygates-wrapper').replaceWith('<span id="pms-paygates-wrapper">');

        $form.find('.pms-credit-card-information').hide()

        if( typeof $form.pms_billing_details == 'undefined' ){

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){
                $form.find('#pms_billing_country').chosen('destroy')
                $form.find('#pms_billing_state').chosen('destroy')
            }

            $form.pms_billing_details = $form.find('.pms-billing-details').clone();

        }

        $form.find('.pms-billing-details').replaceWith('<span class="pms-billing-details">');

    }


    /**
     * It replaces the placeholder spans, that represent the payment gateway and the credit card
     * and billing information, with the cached wrappers that contain the actual fields
     *
     */
    function show_payment_fields( $form ) {

        if( typeof $form.pms_paygates_wrapper != 'undefined' )
            $form.find('#pms-paygates-wrapper').replaceWith( $form.pms_paygates_wrapper );

        if( typeof $pms_checked_paygate != 'unedfined' && $pms_checked_paygate.data('type') == 'credit_card' )
            $form.find('.pms-credit-card-information').show()

        if( typeof $form.pms_billing_details != 'undefined' ){

            $form.find('.pms-billing-details').replaceWith( $form.pms_billing_details )

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){

                $form.find('#pms_billing_country').chosen( JSON.parse( PMS_ChosenStrings ) )

                if( $('#pms_billing_state option').length > 0 )
                    $form.find('#pms_billing_state').chosen( JSON.parse( PMS_ChosenStrings ) )

            }

        }


    }


    /*
     * Show / Hide discount code field if a free plan is selected
     *
     */
    toggle_discount_box( $( 'input[name=subscription_plans][type=radio]' ).length > 0 ? $( 'input[name=subscription_plans][type=radio]:checked' ) : $( 'input[name=subscription_plans][type=hidden]' ) );

    $('input[type=radio][name=subscription_plans]').click( function() {
        toggle_discount_box( $(this) );
    });

    /*
     * Show / Hide discount code field if a free plan is selected
     *
     */
    function toggle_discount_box( $element ) {

        var selector = '#pms-subscription-plans-discount';

        if( !subscription_has_discount( $element.val() ) )
            $(selector).hide()
        else {
            if( $element.attr('data-price') == '0' ) {

                if ( typeof $element.attr('data-sign_up_fee') == 'undefined' || $element.attr('data-sign_up_fee') == '0' )
                    $(selector).hide();

            } else {
                $(selector).show();
            }
        }

    }

    function subscription_has_discount( subscription_id ){

        // show the box if we don't have data available (old default)
        if( typeof pms_discount_object == 'undefined' || typeof pms_discount_object.discounted_subscriptions == 'undefined' )
            return true

        let return_value  = false
        let subscriptions = JSON.parse( pms_discount_object.discounted_subscriptions )

        for (var subscription in subscriptions ){
            if( subscription_id == subscriptions[subscription] )
                return_value = true
        }

        return return_value

    }

});
