<?php
/*
 * Plugin Name: Rainmaker - The Best Readymade WP Forms Plugin
 * Plugin URI: http://www.icegram.com/addons/icegram-rainmaker/
 * Description: The complete solution to create simple forms and collect leads
 * Version: 1.2.6
 * Tested up to: 5.6
 * Author: icegram
 * Author URI: http://www.icegram.com/
 *
 * Copyright (c) 2016-20 Icegram
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: icegram-rainmaker
 * Domain Path: /lang/
*/

if ( ! defined( 'IG_RM_PLUGIN_VERSION' ) ) {
	define( 'IG_RM_PLUGIN_VERSION', '1.2.6' );
}

if ( ! defined( 'IG_RM_PLUGIN_URL' ) ) {
	define( 'IG_RM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

function initialize_icegram_rainmaker() {
	global $icegram_rainmaker;
	// i18n / l10n - load translations
	load_plugin_textdomain( 'icegram-rainmaker', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	include_once 'classes/class-icegram-rainmaker.php';
	$icegram_rainmaker = new Rainmaker();
}

function install_icegram_rainmaker() {
	// Redirect to welcome screen
	delete_option( '_icegram_rm_activation_redirect' );
	add_option( '_icegram_rm_activation_redirect', 'pending' );
}

add_action( 'plugins_loaded', 'initialize_icegram_rainmaker' );
register_activation_hook( __FILE__, 'install_icegram_rainmaker' );
