<style type="text/css">
    .ig_es_offer {	
		width:70%;
		margin: 0 auto;
		text-align: center;
		padding-top: 0.8em;
	}

</style>
<?php

$timezone_format = _x( 'Y-m-d', 'timezone date format' );
$ig_current_date = strtotime( date_i18n( $timezone_format ) );

// BFCM 2020 Campaign
if ( ( get_option( 'rm_offer_bfcm_2020_icegram' ) !== 'yes' ) && ( $ig_current_date >= strtotime( "2020-11-24" ) ) && ( $ig_current_date <= strtotime( "2020-12-02" ) ) ) { ?>
    <div class="wrap">
		<div class="ig_es_offer">
        	<a target="_blank" href="?rm_dismiss_admin_notice=1&rm_option_name=rm_offer_bfcm_2020"><img style="margin:0 auto" src="<?php echo $this->plugin_url; ?>../assets/images/bfcm_2020.jpg"/></a>
    	</div>
    </div>
<?php } ?>

