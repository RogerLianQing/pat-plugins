<?php
/**
 * Plugin Name: Paid Member Subscriptions - Invoices Add-on
 * Plugin URI: https://www.cozmoslabs.com/
 * Description: Allow both admins and customers to download PDF invoices for membership payments.
 * Version: 1.2.4
 * Author: Cozmoslabs, Adrian Spiac, Mihai Iova
 * Author URI: https://www.cozmoslabs.com/
 * Text Domain: pms-add-on-invoices
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2018 Cozmoslabs (www.cozmoslabs.com)
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
if( ! defined( 'ABSPATH' ) )
    exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;


Class PMS_Invoices {

	/**
	 * Constructor
	 *
	 */
	public function __construct() {

		/* Define constants */
		define( 'PMS_INV_VERSION', 		   '1.2.4' );
		define( 'PMS_INV_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
		define( 'PMS_INV_PLUGIN_DIR_URL',  plugin_dir_url( __FILE__ ) );

		add_action( 'admin_init', array( $this, 'update_check' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_back_end_scripts' ), 20 );

		$this->include_files();

		register_activation_hook( __FILE__, array( $this, 'set_cron_jobs' ) );
        register_deactivation_hook( __FILE__, array( $this, 'unset_cron_jobs' ) );

	}


	/**
	 * Fires on admin_init to handle plugin update specific tasks
	 *
	 */
	public function update_check() {

		$db_version = get_option( 'pms_inv_version', '' );

		if( $db_version != PMS_INV_VERSION ) {

			update_option( 'pms_inv_version', PMS_INV_VERSION );

			// Add first time activation
			if( get_option( 'pms_inv_first_activation', '' ) == '' ) {

				// Add first activation timestamp
				update_option( 'pms_inv_first_activation', time() );

				// On first activation add the general counter invoice number
				update_option( 'pms_inv_invoice_number', '1' );

				// On first activation add the current year to the reset invoices number years
				update_option( 'pms_inv_reset_invoice_number_years', array( date('Y') ) );

				/**
				 * Do extra actions on plugin's first ever activation
				 *
				 */
				do_action( 'pms_inv_first_activation' );

			}

			/**
			 * Do extra tasks on plugin update
			 *
			 * @param string $db_version - the previous version of the plugin
			 * @param string PMS_INV_VERSION     - the new (current) version of the plugin
			 *
			 */
			do_action( 'pms_inv_update_check', $db_version, PMS_INV_VERSION );

		}

	}


	/**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {

        wp_enqueue_script( 'pms-invoices-script', PMS_INV_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_INV_VERSION );
        wp_enqueue_style( 'pms-invoices-style', PMS_INV_PLUGIN_DIR_URL . 'assets/css/front-end.css', array(), PMS_INV_VERSION );

    }


    /**
     * Enqueue back-end scripts and styles
     *
     */
    public function enqueue_back_end_scripts() {

    	wp_enqueue_script( 'pms-invoices-back-end', PMS_INV_PLUGIN_DIR_URL . 'assets/js/back-end.js', array('jquery'), PMS_INV_VERSION );

    }


    /**
	 * Include add-on files
	 *
	 */
	private function include_files() {

		if ( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-cron.php' ) )
    		include_once( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-cron.php' );

		if ( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-form-extra-fields.php' ) )
    		include_once( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-form-extra-fields.php' );

    	if ( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-admin.php' ) )
    		include_once( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-admin.php' );

		if ( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-invoices.php' ) )
    		include_once( PMS_INV_PLUGIN_DIR_PATH . 'includes/functions-invoices.php' );

    	// Invoice templates
    	if ( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/templates/default.php' ) )
    		include_once( PMS_INV_PLUGIN_DIR_PATH . 'includes/templates/default.php' );

    	/**
    	 * Hook to add extra files
    	 *
    	 */
    	do_action( 'pms_inv_include_files' );

	}


	/**
	 * Sets an action hook to add custom schedules
	 *
	 */
	public function set_cron_jobs() {

		do_action( 'pms_inv_set_cron_jobs' );

	}


	/**
	 * Sets an action hook to remove custom schedules
	 *
	 */
	public function unset_cron_jobs() {

		do_action( 'pms_inv_unset_cron_jobs' );

	}

}

// Let's get the party started
new PMS_Invoices();


/* Handle add-on updates */
if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug = 'invoices';
    $localSerial = get_option( $slug . '_serial_number');
    $pms_invoices_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSINV', __FILE__, $slug );
}
