<?php
/**
 * Plugin Name: Paid Member Subscriptions - Stripe Payment Gateway
 * Plugin URI: https://www.cozmoslabs.com/
 * Description: Accept credit and debit card payments through Stripe
 * Version: 1.4.1
 * Author: Cozmoslabs, Mihai Iova
 * Author URI: https://www.cozmoslabs.com/
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

Class PMS_Stripe {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_STRIPE_VERSION', '1.4.1' );
        define( 'PMS_STRIPE_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_STRIPE_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();

    }

    private function init() {

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Enable auth email by default on the first run
        if( get_option( 'pms_stripe_first_activation', false ) === false ){

            update_option( 'pms_stripe_first_activation', time() );

            $email_settings = get_option( 'pms_emails_settings', array() );

            $email_settings['stripe_authentication_is_enabled'] = 'yes';

            update_option( 'pms_emails_settings', $email_settings );
        }
    }


    /**
     * Load needed files
     *
     */
    private function load_dependencies() {

        // Stripe Library
        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'libs/stripe/init.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'libs/stripe/init.php';

        // Admin page
        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        // Gateway class and gateway functions
        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-actions.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-actions.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-filters.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-filters.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-legacy.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-legacy.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-payment-intents.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-payment-intents.php';

        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-emails.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'includes/class-emails.php';

        //Compatibility files with PB
        if( file_exists( PMS_STRIPE_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php' ) )
            include PMS_STRIPE_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php';

    }

    /**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {

        wp_enqueue_script( 'pms-stripe-js', 'https://js.stripe.com/v3/', array( 'jquery' ) );

        wp_enqueue_style( 'pms-stripe-style', PMS_STRIPE_PLUGIN_DIR_URL . 'assets/css/pms-stripe.css', array(), PMS_STRIPE_VERSION );
        wp_enqueue_script( 'pms-stripe-script', PMS_STRIPE_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_STRIPE_VERSION );

        wp_localize_script( 'pms-stripe-script', 'pms', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

        wp_localize_script( 'pms-stripe-script', 'pms_elements_styling', apply_filters( 'pms_stripe_elements_styling', array( 'base' => array(), 'invalid' => array() ) ) );

    }

    public function enqueue_admin_scripts( $hook ) {

        if( $hook != 'paid-member-subscriptions_page_pms-settings-page' )
            return;

        wp_enqueue_script( 'pms-stripe-admin-script', PMS_STRIPE_PLUGIN_DIR_URL . 'assets/js/admin-settings-payments.js', array('jquery'), PMS_STRIPE_VERSION );

    }

}

// Let's get this party started
new PMS_Stripe;


if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug = 'stripe';
    $localSerial = get_option( $slug . '_serial_number');
    $pms_stripe_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSSTP', __FILE__, $slug );
}
