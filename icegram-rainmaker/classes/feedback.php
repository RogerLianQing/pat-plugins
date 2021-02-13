<?php

/**
 * Get additional system & plugin specific information for feedback
 *
 */
if ( ! function_exists( 'ig_rm_get_additional_info' ) ) {

	/**
	 * Get Rainmaker specific information
	 *
	 * @param $additional_info
	 * @param bool $system_info
	 *
	 * @return mixed
	 *
	 * @since 1.0
	 */
	function ig_rm_get_additional_info( $additional_info, $system_info = false ) {
		global $icegram_rainmaker, $ig_rm_tracker;
		$additional_info['version'] = $icegram_rainmaker->version;
		if ( $system_info ) {

			$additional_info['active_plugins']   = implode( ', ', $ig_rm_tracker::get_active_plugins() );
			$additional_info['inactive_plugins'] = implode( ', ', $ig_rm_tracker::get_inactive_plugins() );
			$additional_info['current_theme']    = $ig_rm_tracker::get_current_theme_info();
			$additional_info['wp_info']          = $ig_rm_tracker::get_wp_info();
			$additional_info['server_info']      = $ig_rm_tracker::get_server_info();

			// RM Specific information
			$additional_info['plugin_meta_info'] = Rainmaker::get_rm_meta_info();
		}

		return $additional_info;

	}

}

add_filter( 'ig_rm_additional_feedback_meta_info', 'ig_rm_get_additional_info', 10, 2 );

if ( ! function_exists( 'ig_rm_review_message_data' ) ) {
	/**
	 * Filter 5 star review data
	 *
	 * @param $review_data
	 *
	 * @return mixed
	 *
	 * @since 1.0.1
	 */
	function ig_rm_review_message_data( $review_data ) {

		$review_url = 'https://wordpress.org/support/plugin/icegram-rainmaker/reviews/';
		$icon_url   = IG_RM_PLUGIN_URL . 'assets/images/icon-64.png';
		$message    = __( "<span><p>We hope you're enjoying <b>Rainmaker</b> plugin! Could you please do us a BIG favor and give us a 5-star rating on WordPress to help us spread the word and boost our motivation?</p>", 'icegram-rainmaker' );

		$review_data['review_url'] = $review_url;
		$review_data['icon_url']   = $icon_url;
		$review_data['message']    = $message;

		return $review_data;
	}
}

add_filter( 'ig_rm_review_message_data', 'ig_rm_review_message_data', 10 );

if ( ! function_exists( 'ig_rm_can_ask_user_for_review' ) ) {
	/**
	 * Can we ask user for 5 star review?
	 *
	 * @return bool
	 *
	 * @since 1.0.1
	 */
	function ig_rm_can_ask_user_for_review( $enable, $review_data ) {

		if ( $enable ) {
			$screen = get_current_screen();
			if ( ! in_array( $screen->id, array( 'edit-rainmaker_form', 'rainmaker_form', 'edit-rainmaker_lead', 'rainmaker_form_page_icegram-rainmaker-support', 'rainmaker_form_page_icegram-rainmaker-upgrade' ), true ) ) {
				return false;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$total_rm_leads         = wp_count_posts( 'rainmaker_lead' );
			$total_rm_leads_publish = $total_rm_leads->publish;
			
			if ( $total_rm_leads_publish < 2 ) {
				return false;
			}
		}

		return $enable;
	}
}

add_filter( 'ig_rm_can_ask_user_for_review', 'ig_rm_can_ask_user_for_review', 10, 2 );