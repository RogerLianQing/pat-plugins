<?php 
//restrict_manage_posts export and filter
add_action( 'rainmaker_add_form_actions', 'rm_add_captcha_upsale'  );
add_action( 'restrict_manage_posts', 'add_export_lead_updale' );


function rm_add_captcha_upsale(){
	$assets_url = untrailingslashit( plugins_url( '/', __FILE__ ) ) .'/';

	?>
	<a target="_blank" href="https://www.icegram.com/documentation/how-to-enable-captcha-in-your-rainmaker-form/?utm_source=in_app&utm_medium=rm_captcha&utm_campaign=rm_upsale"><li class="rm-field-row"><div class="rm-form-field-set"><img src="<?php echo  $assets_url ?>../assets/images/rm-captcha-upsale.png"></div></li></a>
	<?php
}

function add_export_lead_updale(){
	if ( empty( $_GET['post_type'] ) || ( $_GET['post_type'] != 'rainmaker_lead') )
        		return;
	$assets_url = untrailingslashit( plugins_url( '/', __FILE__ ) ) .'/';

	?>
	 <a target="_blank" href="https://www.icegram.com/rainmaker-pricing-table/?utm_source=in_app&utm_medium=filter_leads&utm_campaign=rm_upsale"><img src="<?php echo  $assets_url ?>../assets/images/es-filter-upsale.png"></a>
	 <a target="_blank" href="https://www.icegram.com/rainmaker-pricing-table/?utm_source=in_app&utm_medium=export_leads&utm_campaign=rm_upsale"><img class="alignright" style="margin-top: 1px;" src="<?php echo  $assets_url ?>../assets/images/export-leads-updale.png"></a>
	<?php
}
?>