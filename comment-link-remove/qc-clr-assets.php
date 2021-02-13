<?php

defined('ABSPATH') or die("No direct script access!");

function qc_clr_load_front_scripts(){
    //Styles
    // wp_enqueue_style( 'qcld_express_fontawesome_css', QCCLR_ASSETS_URL . '/css/font-awesome.min.css');
    //Scripts
    //wp_enqueue_script( 'jquery', 'jquery');
    //wp_enqueue_script( 'qc_clr_vertical_scroll', QCCLR_ASSETS_URL . '/js/qc-clr-jquery.marquee.min.js', array('jquery'));
}
add_action('wp_enqueue_scripts', 'qc_clr_load_front_scripts');

function qc_clr_load_admin_scripts(){
    //Styles
    wp_enqueue_style( 'qc_clr_admin_style_css', QCCLR_ASSETS_URL . "/css/qc-clr-style.css");

 	//Scripts
	wp_enqueue_script( 'jquery', 'jquery');
	wp_enqueue_script( 'qc_clr_admin_custom_scripts_js', QCCLR_ASSETS_URL . "/js/custom-scripts.js", array('jquery'));


}
add_action( 'admin_enqueue_scripts', 'qc_clr_load_admin_scripts');