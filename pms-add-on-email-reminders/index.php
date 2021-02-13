<?php
/**
 * Plugin Name: Paid Member Subscriptions - Email Reminders
 * Plugin URI: https://www.cozmoslabs.com/
 * Description: Create multiple automated email reminders that are sent to members before or after certain events take place (subscription expires, last login, subscription activated etc.)
 * Version: 1.1.2
 * Author: Cozmoslabs, Adrian Spiac
 * Author URI: https://www.cozmoslabs.com/
 * Text Domain: pms-add-on-email-reminders
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2016 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if( ! defined( 'ABSPATH' ) )
    exit;

if( ! defined( 'PMS_VERSION' ) )
    return;

/* Define constants */

    define( 'PMS_ER_VERSION', '1.1.1' );
    define( 'PMS_ER_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'PMS_ER_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

/* Cron jobs */

    // Add cron job on plugin activation
    register_activation_hook( __FILE__ , 'pms_er_add_cron_job' );

    // Remove cron job on plugin deactivation
    register_deactivation_hook( __FILE__ , 'pms_er_clear_cron_job' );


    function pms_er_add_cron_job(){
    	if ( ! wp_next_scheduled( 'pms_send_email_reminders_hourly' ) ) {
		    wp_schedule_event( time(), 'hourly', 'pms_send_email_reminders_hourly', array( 'hourly' ) );
    	}

	   if ( ! wp_next_scheduled( 'pms_send_email_reminders_daily' ) ) {
		    wp_schedule_event( time(), 'daily', 'pms_send_email_reminders_daily', array( 'daily' ) );
	   }
    }

    function pms_er_clear_cron_job(){

        wp_clear_scheduled_hook( 'pms_send_email_reminders_hourly', array( 'hourly' ) );
        wp_clear_scheduled_hook( 'pms_send_email_reminders_daily', array( 'daily' ) );

    }


/* Include needed files */

    if ( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-email-reminder.php' ) )
        include_once( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-email-reminder.php' );

    if ( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-admin-email-reminders.php' ) )
        include_once( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-admin-email-reminders.php' );

    if ( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-email-reminders-details.php' ) )
        include_once( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-email-reminders-details.php' );

    if ( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-available-tags.php' ) )
        include_once( PMS_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-available-tags.php' );

    if ( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/functions-email-reminder.php' ) )
        include_once( PMS_ER_PLUGIN_DIR_PATH . 'includes/functions-email-reminder.php' );


/* Adding Admin scripts */

function pms_er_add_admin_scripts(){


    // If the file exists where it should be, enqueue it
    if( file_exists( PMS_ER_PLUGIN_DIR_PATH . 'includes/assets/js/cpt-email-reminders.js' ) )
        wp_enqueue_script( 'pms-email-reminders-js', PMS_ER_PLUGIN_DIR_URL . 'includes/assets/js/cpt-email-reminders.js', array( 'jquery' ) );

    // add back-end css for Email Reminders cpt
    wp_enqueue_style( 'pms-er-style-back-end', PMS_ER_PLUGIN_DIR_URL . 'includes/assets/css/style-back-end.css' );

}
add_action('pms_cpt_enqueue_admin_scripts_pms-email-reminders','pms_er_add_admin_scripts');


/* Positioning the Email Reminders label under Payments in PMS submenu */

function pms_er_submenu_order( $menu_order){
    global $submenu;

    if ( isset($submenu['paid-member-subscriptions']) ) {

        foreach ( $submenu['paid-member-subscriptions'] as $key => $value ) {
            if ($value[2] == 'edit.php?post_type=pms-email-reminders') $email_reminders_key = $key;
            if ($value[2] == 'pms-settings-page') $settings_key = $key;
        }

        if ( isset( $settings_key ) && isset( $email_reminders_key ) ) {
            $email_reminders_value = $submenu['paid-member-subscriptions'][$email_reminders_key];

            if ( $settings_key > $email_reminders_key ) $email_reminders_key--;
            unset( $submenu['paid-member-subscriptions'][$email_reminders_key] );

            $array1 = array_slice( $submenu['paid-member-subscriptions'], 0, $settings_key );
            $array2 = array_slice( $submenu['paid-member-subscriptions'], $settings_key );
            array_push( $array1, $email_reminders_value );

            $submenu['paid-member-subscriptions'] = array_merge($array1, $array2);

        }
    }

    return $menu_order;

}
//add_filter('custom_menu_order','pms_er_submenu_order');


/* Handle add-on updates */

if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug = 'email-reminders';
    $localSerial = get_option( $slug . '_serial_number');
    $pms_nmf_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSER', __FILE__, $slug );
}
