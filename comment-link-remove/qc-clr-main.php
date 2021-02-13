<?php
/**
 * Plugin Name: Comment Link Remove
 * Plugin URI: https://wordpress.org/plugins/comment-link-remove
 * Description: Remove author link and any other posted links from the comment fields. 
 * Version: 2.1.4
 * Author: QuantumCloud
 * Author URI: https://www.quantumcloud.com/
 * Requires at least: 4.6
 * Tested up to: 5.5
 * Text Domain: qc-clr
 * Domain Path: /lang/
 * License: GPL2
 */

defined('ABSPATH') or die("No direct script access!");

//Custom Constants
define('QCCLR_URL', plugin_dir_url(__FILE__));
define('QCCLR_ASSETS_URL', QCCLR_URL . "/assets");

define('QCCLR_DIR', dirname(__FILE__));

//Include required files
require_once( 'qc-clr-settings.php' );
require_once( 'qc-clr-assets.php' );

//Perform Action
$remove_author_uri = 0;
$remove_author_txtlink = 0;
$remove_comment_link = 0;
$disable_turning_link = 0;

$clr_options = get_option( 'comment_link_remove_option_name' );
$remove_author_uri = isset($clr_options['remove_author_uri_field_0']) ? $clr_options['remove_author_uri_field_0'] : 0;
$remove_author_txtlink = isset($clr_options['remove_any_link_from_author_field_1']) ? $clr_options['remove_any_link_from_author_field_1'] : 0;
$remove_comment_link = isset($clr_options['remove_links_from_comments_field_2']) ? $clr_options['remove_links_from_comments_field_2'] : 0;
$disable_turning_link = isset($clr_options['remove_links_from_comments_field_3']) ? $clr_options['remove_links_from_comments_field_3'] : 0;

$disable_comments = isset($clr_options['disable_comments_totally']) ? $clr_options['disable_comments_totally'] : 0;
$hide_existing_cmts = isset($clr_options['hide_existing_cmts']) ? $clr_options['hide_existing_cmts'] : 0;
$open_link_innewtab = isset($clr_options['open_link_innewtab']) ? $clr_options['open_link_innewtab'] : 0;

//1. Remove Author URI or Link

function qcclr_disable_comment_url($fields) { 
    unset($fields['url']);
    return $fields;
}

if( $remove_author_uri === '1' )
{
	add_filter('comment_form_default_fields','qcclr_disable_comment_url', 60);

	add_filter('comment_form_field_url', '__return_false');
}

//2. Remove  hyperlink of comment author field

function qcclr_remove_html_link_tag_from_comment_author_link ( $link ) {

  if( !in_the_loop() ) {
      $link = preg_replace('/<a href=[\",\'](.*?)[\",\']>(.*?)<\/a>/', "\\2", $link);
  }

  return $link;

}

if( $remove_author_txtlink === '1' )
{

	if( !function_exists("qcclr_disable_comment_author_links")){
		function qcclr_disable_comment_author_links( $author_link ){
			return strip_tags( $author_link );
		}
		add_filter( 'get_comment_author_link', 'qcclr_disable_comment_author_links' );
	}

	function filter_get_comment_author_url( $url, $id, $comment ) {
	    return "";
	}
	add_filter( 'get_comment_author_url', 'filter_get_comment_author_url', 10, 3);
	
	add_filter( 'get_comment_author_link', 'qcclr_remove_html_link_tag_from_comment_author_link' );
	
}

//3. Disable turning URLs from comments into actual links

if( $disable_turning_link === '1' )
{
	remove_filter('comment_text', 'make_clickable', 9);
}

//4. Filter comment texts to remove link

if( $remove_comment_link === '1' )
{
	add_filter('comment_text', 'qcclr_filter_inserted_comment');
}

function qcclr_filter_inserted_comment( $text )
{
  $text = preg_replace('/<a href=[\",\'](.*?)[\",\']>(.*?)<\/a>/', "\\2", $text);

  return $text;
}

//5. Disable Comments Globally

// Close comments on the front-end
function qcclr_disable_comments_status() {
	return false;
}

if( $disable_comments === '1' )
{
	add_filter('comments_open', 'qcclr_disable_comments_status', 20, 2);
	add_filter('pings_open', 'qcclr_disable_comments_status', 20, 2);
}

//6. Hide Existing Comments

//Hide existing comments
function df_disable_comments_hide_existing_comments($comments) {
	$comments = array();
	return $comments;
}

if( $hide_existing_cmts === '1' )
{
	add_filter('comments_array', 'df_disable_comments_hide_existing_comments', 10, 2);
}

//7. Open Link in New Tab

function qcclr_filter_link_target( $text )
{
	if( preg_match('/<a.*?target=[^>]*?>/', $text) )
	{
		$text = str_replace('target="_blank"', '', $text);
		$text = str_replace('target="_top"', '', $text);
		$text = str_replace('target="_self"', '', $text);
		$text = str_replace('target="_parent"', '', $text);
	}
	
	$return = str_replace('<a', '<a target="_blank"', $text);
	
    return $return;
}

if( $open_link_innewtab === '1' )
{
	add_filter('comment_text', 'qcclr_filter_link_target', 10, 2);
}




