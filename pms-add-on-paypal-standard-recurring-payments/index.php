<?php
/**
 * Plugin Name: Paid Member Subscriptions - Recurring Payments for PayPal Standard
 * Plugin URI: http://www.cozmoslabs.com/
 * Description: Allows recurring payments through PayPal Standard
 * Version: 1.2.6
 * Author: Cozmoslabs, Mihai Iova
 * Author URI: http://www.cozmoslabs.com/
 * Text Domain: paid-member-subscriptions
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2015 Cozmoslabs (www.cozmoslabs.com)
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

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_PayPal_Standard_Recurring_Payments {

    public function __construct() {

        // Define global constants
        define( 'PMS_PPSRP_VERSION', '1.2.6' );
        define( 'PMS_PPSRP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_PPSRP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        // Include dependencies
        $this->include_dependencies();

        register_activation_hook( __FILE__, array( $this, 'install' ) );
        register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

    }


    /*
     * Function to include the files needed
     *
     */
    public function include_dependencies() {

        /*
         * Settings Admin Page
         */
        if( file_exists( PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include_once PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        /*
         * Front End
         */
        if( file_exists( PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-front-end.php' ) )
            include_once PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-front-end.php';

        /*
         * PayPal Functions
         */
        if( file_exists( PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-paypal.php' ) )
            include_once PMS_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-paypal.php';


    }

    /*
     * Hook that fires on plugin activation
     * Sets recurring payment option if it doesn't exist
     *
     */
    public function install() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 )
            $pms_settings = get_option( 'pms_settings', array() );
        else
            $pms_settings = get_option( 'pms_payments_settings', array() );

        if( empty( $pms_settings ) ) return;

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 && !isset( $pms_settings['payments']['recurring'] ) ) {
            $pms_settings['payments']['recurring'] = 1;
            update_option( 'pms_settings', $pms_settings );
        } else if ( !isset( $pms_settings['recurring'] ) ) {
            $pms_settings['recurring'] = 1;
            update_option('pms_payments_settings', $pms_settings);
        }

        $this->alter_tables();

    }


    /*
     * Function that changes existing tables to accommodate recurring payments
     *
     */
    public function alter_tables() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'pms_member_subscriptions';

        // Check to see if member subscriptions table exists
        if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {

            // Check to see if column exists, if it doesn't add it
            if( !in_array( 'payment_profile_id', $wpdb->get_col( "DESC " . $table_name, 0 ) ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD payment_profile_id varchar(32) NOT NULL" );
            }

        }

    }


    /*
     * Fires up on plugin deactivation
     *
     * Remove the recurring payment options setting
     *
     */
    public function uninstall() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 )
            $pms_settings = get_option( 'pms_settings', array() );
        else
            $pms_settings = get_option( 'pms_payments_settings', array() );

        if( empty($pms_settings) ) return;

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            if( isset($pms_settings['payments']['recurring']) && count($pms_settings['payments']['active_pay_gates']) == 1 && $pms_settings['payments']['active_pay_gates'][0] == 'paypal_standard' ) {
                unset($pms_settings['payments']['recurring']);
                update_option( 'pms_settings', $pms_settings );
            }
        } else {
            if( isset($pms_settings['recurring']) && count($pms_settings['active_pay_gates']) == 1 && $pms_settings['active_pay_gates'][0] == 'paypal_standard' ) {
                unset($pms_settings['recurring']);
                update_option( 'pms_payments_settings', $pms_settings );
            }
        }

    }
}

// Let's get the party started
new PMS_PayPal_Standard_Recurring_Payments;


if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug = 'recurring-payments-for-paypal-standard';
    $localSerial = pms_get_serial_number();
    $pms_rpps_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSRPPS', __FILE__, $slug );
}
