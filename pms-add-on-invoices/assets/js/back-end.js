jQuery( function($) {

	/**
	 * Enable/disable the "Next Invoice Number" when checking "Reset Invoice Counter" checkbox
	 *
	 */
	$(document).on( 'click', '#invoices-reset-invoice-counter', function() {

		if( $(this).is(':checked') )
			$('#invoices-next-invoice-number').attr( 'disabled', false ).attr( 'readonly', false ).focus();
		else
			$('#invoices-next-invoice-number').attr( 'disabled', true ).attr( 'readonly', true );

	});

	// Upload Company Logo event
	$('body').on('click', '.pms-invoices-company-logo-upload', function(e){
		e.preventDefault();

    		var button = $(this),
    		    custom_uploader = wp.media({
					title: 'Insert image',
					library : {
						type : 'image'
					},
					button: {
						text: 'Use this image'
					},
					multiple: false
				}).on('select', function() {
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					$(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();
				}).open()
	});

	/*
	 * Remove image event
	 */
	$('body').on('click', '.pms-invoices-company-logo-remove', function(){
		$(this).hide().prev().val('').prev().addClass('button').html('Upload image')

		return false
	});

});
