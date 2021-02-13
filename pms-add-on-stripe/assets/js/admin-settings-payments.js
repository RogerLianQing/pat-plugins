jQuery(document).ready(function() {

    jQuery('input[type="checkbox"][value="stripe"]').click( function() {

        if ( jQuery('input[type="checkbox"][value="stripe"]').is(':checked') )
            jQuery('input[type="checkbox"][value="stripe_intents"]').prop( 'checked', false )
            jQuery( '.pms-stripe-admin-warning' ).show()

    })

    jQuery('input[type="checkbox"][value="stripe_intents"]').click( function() {

        if ( jQuery('input[type="checkbox"][value="stripe_intents"]').is(':checked') ){
            jQuery('input[type="checkbox"][value="stripe"]').prop( 'checked', false )
            jQuery( '.pms-stripe-admin-warning' ).hide()
        }

    })
});
