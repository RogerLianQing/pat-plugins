<?php
/**
 * Plugin Name: Paid Member Subscriptions - PayPal Pro and PayPal Express
 * Plugin URI: http://www.cozmoslabs.com/
 * Description: Accept one-time or recurring payments through PayPal Pro and PayPal Express.
 * Version: 1.4.4
 * Author: Cozmoslabs, Mihai Iova, Adrian Spiac
 * Author URI: http://www.cozmoslabs.com/
 * Text Domain: paid-member-subscriptions
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

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_PayPal_Express_Pro {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMSPP_VERSION', '1.4.4' );
        define( 'PMSPP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMSPP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        // Deactivation actions
        //register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

        // Include dependencies
        $this->include_dependencies();

        // Initialize the plugin
        $this->init();

    }

    /*
     * Initialize the plugin
     *
     * */
    public function init(){

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /*
     * Function to include the files needed
     *
     */
    public function include_dependencies() {

        /*
         * Settings Admin Page
         */
        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        /*
         * PayPal Express Checkout and PayPal Pro
         */
        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express-legacy.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express-legacy.php';

        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express.php';

        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-pro.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-pro.php';

        /*
         * Compatibility files with PB
         */
        if( file_exists( PMSPP_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php' ) )
            include PMSPP_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php';

    }


    /**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {

        wp_enqueue_style( 'pms-paypal-express-pro-style', PMSPP_PLUGIN_DIR_URL . 'assets/css/pms-paypal-express-pro.css', array(), PMSPP_VERSION );

    }


    /**
     * Enqueue admin scripts
     *
     */
    public function enqueue_admin_scripts( $hook ) {

        if( $hook != 'paid-member-subscriptions_page_pms-settings-page' )
            return;

        wp_enqueue_script( 'pms-paypal-express-pro-admin-script', PMSPP_PLUGIN_DIR_URL . 'assets/js/admin.js', array('jquery'), PMSPP_VERSION );

    }



    /*
     * Actions to be performed on plugin deactivation
     *
     */
    public function uninstall() {

        $pms_settings = get_option( 'pms_payments_settings' );

        $active_gateways = array_diff( $pms_settings['active_pay_gates'], array( 'paypal_express', 'paypal_pro' ) );

        if( empty( $active_gateways ) )
            $active_gateways = array( 'paypal_standard' );

        $pms_settings['active_pay_gates'] = $active_gateways;

        update_option( 'pms_payments_settings', $pms_settings );

    }


}

// Let's get the party started
new PMS_PayPal_Express_Pro;


if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug           = 'paypal-pro-paypal-express';
    $localSerial    = pms_get_serial_number();
    $pms_nmf_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSPPE', __FILE__, $slug );
}
