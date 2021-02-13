<?php
//Load Mailchimp
require_once( 'mailchimp.php' );
//Load Campaign Monitor
require_once( 'campaignmonitor.php' );
//Load HubSpot
require_once( 'hubspot.php' );

global $ig_rm_tracker;

$active_plugins = $ig_rm_tracker::get_active_plugins();

$es_plugins = array(
	'email-subscribers/email-subscribers.php',
	'email-subscribers-premium/email-subscribers-premium.php',
	'email-subscribers-starter/email-subscribers-premium.php',
	'email-subscribers-pro/email-subscribers-premium.php',
);

// Is ES Lite or Premium plugin activated?
if ( count( array_intersect( $es_plugins, $active_plugins ) ) > 0 ) {
	require_once( 'email_subscribers.php' );
}

//Is Mailpoet plugin activated?
if ( in_array( 'wysija-newsletters/index.php', $active_plugins ) ) {
	require_once( 'mailpoet.php' );
}
