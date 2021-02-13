/**
 * Uncheck PayPal Standard when PayPal Express Checkout is checked, and vice versa
 */

jQuery(document).ready(function() {

    jQuery('input[type="checkbox"][value="paypal_express"]').click( function() {

        if ( jQuery('input[type="checkbox"][value="paypal_express"]').is(':checked') ) {

            jQuery('input[type="checkbox"][value="paypal_standard"]').prop('checked', false);

        }
    })

    jQuery('input[type="checkbox"][value="paypal_standard"]').click( function() {

        if ( jQuery('input[type="checkbox"][value="paypal_standard"]').is(':checked') ) {

            jQuery('input[type="checkbox"][value="paypal_express"]').prop('checked', false);

        }
    })

});
