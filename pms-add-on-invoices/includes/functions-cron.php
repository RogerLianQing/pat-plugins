<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Set the cron jobs
 *
 */
function pms_inv_set_cron_jobs() {

	if( ! wp_next_scheduled( 'pms_inv_cron_job_reset_yearly' ) )
		wp_schedule_event( time(), 'hourly', 'pms_inv_cron_job_reset_yearly' );

}
add_action( 'pms_inv_set_cron_jobs', 'pms_inv_set_cron_jobs' );


/**
 * Unset the cron jobs
 *
 */
function pms_inv_unset_cron_jobs() {

	wp_clear_scheduled_hook( 'pms_inv_cron_job_reset_yearly' );

}
add_action( 'pms_inv_unset_cron_jobs', 'pms_inv_unset_cron_jobs' );


/**
 * Resets the invoice number when entering a new year
 *
 * @return void
 *
 */
function pms_inv_cron_job_reset_yearly() {

	$reset_years = get_option( 'pms_inv_reset_invoice_number_years', array() );

	if( empty( $reset_years ) ) {

		update_option( 'pms_inv_reset_invoice_number_years', array( date('Y') ) );
		return;

	} else {

		$current_year = date('Y');

		if( ! in_array( $current_year, $reset_years ) ) {

			$settings 	  = get_option( 'pms_invoices_settings', array() );

			$reset_years[] = $current_year;

			// Update the reset years with the new year
			update_option( 'pms_inv_reset_invoice_number_years', $reset_years );

			// Update the invoice number
			if( ! empty( $settings['reset_yearly'] ) )
				update_option( 'pms_inv_invoice_number', '1' );

		}

	}

}
add_action( 'pms_inv_cron_job_reset_yearly', 'pms_inv_cron_job_reset_yearly' );
