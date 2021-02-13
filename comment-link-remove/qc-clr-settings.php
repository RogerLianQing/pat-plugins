<?php

defined('ABSPATH') or die("No direct script access!");

/**
 * Options Page - CLR
 */

class CommentLinkRemove {

    private $comment_link_remove_options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'comment_link_remove_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_8' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_6' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_2' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_3' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_4' ) );
        add_action( 'admin_init', array( $this, 'comment_link_remove_page_init_5' ) );
    }

    public function comment_link_remove_add_plugin_page() {

        include_once 'class-qcld-free-plugin-upgrade-notice.php';
       
        add_menu_page(
            __('Comment Link Remove & Comment Tools'), // page_title
            __('QC CLR Settings'), // menu_title
            'manage_options', // capability
            'comment-link-remove', // menu_slug
            array( $this, 'comment_link_remove_create_admin_page' ) // function
        );

        add_submenu_page( 'comment-link-remove', __('Commenter Emails'), __('Commenter Emails'), 'manage_options', 'commenter-emails', 'comment_link_remove_commenter_email_pro_feature');

    }

    public function comment_link_remove_create_admin_page() {
        
        $this->comment_link_remove_options = get_option( 'comment_link_remove_option_name' ); 		

        ?>

<div class="wrap-content">
  <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
      <div id="post-body-content" >
        <h2 class="clr_titles"><?php  esc_html_e( 'Comment Link Remove & Comment Tools', 'qc-clr' ); ?></h2>
        <p> <?php  esc_html_e( 'Here you can manage custom settings for the Comment Link Remove plugin.', 'qc-clr' ); ?></p>
         <?php 

                        global $wpdb;
                        
                        if( current_user_can( 'manage_options' ) )
                        {
                            if( isset($_POST['delAllCmts']) && $_POST['delAllCmts'] == 'delAllCmts' )
                            {
                                $response = true;
                                
                                $wpdb->query($wpdb->prepare( "UPDATE `{$wpdb->prefix}posts` set comment_count=%d",0));

                                $wpdb->get_results("DELETE FROM `{$wpdb->prefix}comments` "); 

                                if($response)
                                {
                                    echo "<strong>All Comments deleted successfully!</strong><br>"; 
                                }
                                
                            }

                            if( isset($_POST['delPendingCmts']) && $_POST['delPendingCmts'] == 'delPendingCmts'  )
                            {
                                $query = $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}comments` WHERE `comment_approved` = %d",0);
                                $response = $wpdb->query($query);  

                                if($response)
                                {
                                    echo "<strong>All Pending Comments deleted successfully!</strong><br>"; 
                                }
                                
                            }

                            if( isset($_POST['delSpamCmts']) && $_POST['delSpamCmts'] == 'delSpamCmts'  )
                            {
                                $query = $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}comments` WHERE `comment_approved` = %s",'spam');
                                $response = $wpdb->query($query);  
                

                                if($response)
                                {
                                    echo "<strong>All Spam Comments deleted successfully!</strong><br>"; 
                                }
                                
                            }
                        }

                        ?>

        <div class="clr_warapper">
            <h2><?php  esc_html_e( 'Free Features', 'qc-clr' ); ?></h2>
            <div class="dele-cmts">
                <p><strong> <?php  esc_html_e( 'Delete comments easily:', 'qc-clr' ); ?></strong></p>
                <p></p>
                <form style="display: inline-block;" action="" method="POST">
                    <button class="commentDelete button button-primary" type="submit" name="delAllCmts" value="delAllCmts"> <?php  esc_html_e( 'Delete All Comments', 'qc-clr' ); ?></button>
                    </form>
                    <form style="display: inline-block;" action="" method="POST">
                    <button class="commentDelete button button-primary" type="submit" name="delPendingCmts" value="delPendingCmts"> <?php  esc_html_e( 'Delete Pending Comments', 'qc-clr' ); ?></button>
                    </form>
                    <form style="display: inline-block;" action="" method="POST">
                    <button class="commentDelete button button-primary" type="submit" name="delSpamCmts" value="delSpamCmts"> <?php  esc_html_e( 'Delete Spam Comments', 'qc-clr' ); ?></button>
                </form>
            </div>
        <form method="post" action="options.php">
            <?php settings_fields( 'comment_link_remove_option_group' ); ?>
            <!-- CUSTOM STYLABLE SECTION #1 -->
                <div class="clr_head">
                    <h2> <?php  esc_html_e( 'General Settings', 'qc-clr' ); ?></h2>
                <?php do_settings_sections( 'comment-link-remove-admin' ,'comment_link_remove_setting_section' ); ?>

                </div>
            </div>
            <div class="clr_warapper">

                <h2> <?php  esc_html_e( 'Pro Features', 'qc-clr' ); ?></h2>

                <div class="clr_head">
                    <h2  class="clr_head_titles"><?php  esc_html_e( 'Email Subscription', 'qc-clr' ); ?>  </h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-8' ,'comment_link_remove_setting_section_8' ); ?>

                </div>
                <div class="clr_head">
                    <h2 class="clr_head_titles"> <?php  esc_html_e( 'Comment Sentiment Settings', 'qc-clr' ); ?> <span style="color:red"> <?php  esc_html_e( '( Works with English language only )', 'qc-clr' ); ?></span></h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-6', 'comment_link_remove_setting_section_5'); ?>
                </div>
                <div class="clr_head">
                    <h2><?php  esc_html_e( 'Set Time Interval & Message Length Requirement', 'qc-clr' ); ?></h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-2', 'comment_link_remove_setting_section_1'); ?>
                </div>

                <div class="clr_head">
                    <h2><?php  esc_html_e( 'User Notification', 'qc-clr' ); ?></h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-4', 'comment_link_remove_setting_section_3'); ?>
                </div>
                <div class="clr_head">
                    <h2><?php  esc_html_e( 'SEO Settings', 'qc-clr' ); ?></h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-3', 'comment_link_remove_setting_section_2'); ?>
                </div>
                <div class="clr_head">
                    <h2><?php  esc_html_e( 'Other Settings', 'qc-clr' ); ?></h2>
                    <?php do_settings_sections( 'comment-link-remove-admin-5', 'comment_link_remove_setting_section_4'); ?>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
       
       
      </div>
      <!-- /post-body-content -->
      
      <hr>
      
      <!-- Right Sidebar -->
      <div id="postbox-container-1" id="postbox-container">
      
          <!-- Plugin Logo -->
          <div class="qc-promo-title"><?php  esc_html_e( 'QC Comment Link Remove', 'qc-clr' ); ?></div>
      
            <!-- Promo Block 1 -->
            <div class="qc-promo-content"> 
                <div class="qc-promo-plugins">

                    <img src="<?php echo esc_url(QCCLR_ASSETS_URL) ?>/img/qc-logo-full.png" alt="QuantumCloud Logo">
                    <br><br><hr><br>
                    <a href="<?php echo esc_url( 'http://www.quantumcloud.com', 'qc-clr' ); ?>" target="_blank"><?php  esc_html_e( 'QuantumCloud', 'qc-clr' ); ?></a>
                </div>
            </div>
            <!-- Promo Plugin -->
            <div class="qc-promo-plugins-info">
                <h4><?php  esc_html_e( 'Try our other free plugins!', 'qc-clr' ); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/simple-link-directory/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'Simple', 'qc-clr' ); ?> <div class="qc-promo-bold">  <?php  esc_html_e( 'Link  Directory', 'qc-clr' ); ?> </div> <?php  esc_html_e( 'plugin', 'qc-clr' ); ?></a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/phone-directory/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'Simple', 'qc-clr' ); ?> <div class="qc-promo-bold"> <?php  esc_html_e( 'Business Directory', 'qc-clr' ); ?> </div> <?php  esc_html_e( 'plugin with maps', 'qc-clr' ); ?></a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/simple-media-directory/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'Simple', 'qc-clr' ); ?> <div class="qc-promo-bold"> <?php  esc_html_e( 'Video Directory', 'qc-clr' ); ?> </div> <?php  esc_html_e( 'plugin', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/chatbot/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'ChatBot', 'qc-clr' ); ?></div> <?php  esc_html_e( 'for WordPress', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'WPBot', 'qc-clr' ); ?></div> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/woowbot-woocommerce-chatbot/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'ChatBot', 'qc-clr' ); ?></div> <?php  esc_html_e( 'for WooCommerce', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'WoowBot', 'qc-clr' ); ?></div> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/slider-hero', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'Slider Hero', 'qc-clr' ); ?></div> <?php  esc_html_e( 'WordPress Slider Plugin', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/portfolio-x/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'WordPress', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'Portfolio', 'qc-clr' ); ?></div> <?php  esc_html_e( 'plugin', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/express-shop/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'WooCommerce', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'One Page Store', 'qc-clr' ); ?></div> <?php  esc_html_e( 'Express Shop', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/increase-sales/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'Increase Sales', 'qc-clr' ); ?></div> <?php  esc_html_e( 'on Your Stores', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/shop-assistant-for-woocommerce-jarvis/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'WooCommerce', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'Shop Assistant', 'qc-clr' ); ?></div> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/woo-tabbed-category-product-listing/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'Woo Tabbed Category', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'Product Listing', 'qc-clr' ); ?></div> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/ichart/', 'qc-clr' ); ?>" target="_blank"> <?php  esc_html_e( 'Easy', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'Charts', 'qc-clr' ); ?></div> <?php  esc_html_e( 'and', 'qc-clr' ); ?> <div class="qc-promo-bold"><?php  esc_html_e( 'Graphs', 'qc-clr' ); ?></div>  <?php  esc_html_e( 'plugin - iChart', 'qc-clr' ); ?></a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/infographic-and-list-builder-ilist/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'Infographic', 'qc-clr' ); ?></div> <?php  esc_html_e( 'Maker plugin - iList', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/knowledgebase-helpdesk/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'KnowledgeBase Helpdesk', 'qc-clr' ); ?></div> <?php  esc_html_e( 'Plugin with ChatBot', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/floating-action-buttons/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'Floating Action', 'qc-clr' ); ?></div> <?php  esc_html_e( 'Buttons plugin', 'qc-clr' ); ?> </a></li>
                    <li><a href="<?php echo esc_url( 'https://wordpress.org/plugins/seo-help/', 'qc-clr' ); ?>" target="_blank"> <div class="qc-promo-bold"><?php  esc_html_e( 'SEO', 'qc-clr' ); ?></div> <?php  esc_html_e( 'Help', 'qc-clr' ); ?> </a></li>
                </ul>
            </div>
    </div>
    <!-- /Right Sidebar -->
    <div class="clear"></div>
  </div>
  <div class="clear"></div>
  <!-- /post-body --> 
</div>
<!-- /poststuff -->

</div>
<?php }

    public function comment_link_remove_page_init() {
        register_setting(
            'comment_link_remove_option_group', // option_group
            'comment_link_remove_option_name', // option_name
            array( $this, 'comment_link_remove_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'comment_link_remove_setting_section', // id
            '', // title
            array( $this, 'comment_link_remove_section_info' ), // callback
            'comment-link-remove-admin' // page
        );

        add_settings_field(
            'remove_author_uri_field_0', // id
            __('Remove WEBSITE Field from Comment Form', 'qc-clr'), // title
            array( $this, 'remove_author_uri_field_0_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'remove_any_link_from_author_field_1', // id
            __('Remove hyper-link from comment AUTHOR Bio', 'qc-clr'), // title
            array( $this, 'remove_any_link_from_author_field_1_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'remove_links_from_comments_field_3', // id
            __('Disable turning URLs into hyper-links in comments', 'qc-clr'), // title
            array( $this, 'remove_links_from_comments_field_3_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'remove_links_from_comments_field_2', // id
            __('Remove HTML Link Tags in comments', 'qc-clr'), // title
            array( $this, 'remove_links_from_comments_field_2_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'disable_comments_totally', // id
            __('Disable Comments Globally', 'qc-clr'), // title
            array( $this, 'disable_comments_totally_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'hide_existing_cmts', // id
            __('Hide Existing Comments', 'qc-clr'), // title
            array( $this, 'hide_existing_cmts_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );

        add_settings_field(
            'open_link_innewtab', // id
            __('Open Comment Links in New Tab', 'qc-clr'), // title
            array( $this, 'open_link_innewtab_callback' ), // callback
            'comment-link-remove-admin', // page
            'comment_link_remove_setting_section' // section
        );
    }

    // section 2
    public function comment_link_remove_page_init_2() {
        add_settings_section(
            'comment_link_remove_setting_section_1', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_2' ), // callback
            'comment-link-remove-admin-2' // page
        );

        add_settings_field(
            'comment_time_minimum', // id
            __('Minimum Time (In Seconds) <br> <span class="qcld_msg">( Set Time Interval Required Between Comments to Avoid Spamming )</span>', 'qc-clr'), // title
            array( $this, 'comment_time_minimum_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

        add_settings_field(
            'comment_quick_mgs', // id
            __('Custom Message for Quick comments', 'qc-clr'), // title
            array( $this, 'comment_quick_mgs_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

        add_settings_field(
            'comment_minimum_length', // id
            __('Minimum Length of Character in Comments <br> <span class="qcld_msg">( Avoid Low Value Comments )</span>', 'qc-clr'), // title
            array( $this, 'comment_minimum_length_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

        add_settings_field(
            'comment_minimum_length_mgs', // id
            __('Alert Message for Minimum Comments Length ', 'qc-clr'), // title
            array( $this, 'comment_minimum_length_mgs_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

        add_settings_field(
            'comment_maximum_length', // id
            __('Maximum of Character in Comments <br> <span class="qcld_msg">( Helps Avoid Spamming )</span>', 'qc-clr'), // title
            array( $this, 'comment_maximum_length_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

        add_settings_field(
            'comment_maximum_length_mgs', // id
            __('Alert Message for Maximum Comments Length ', 'qc-clr'), // title
            array( $this, 'comment_maximum_length_mgs_callback' ), // callback
            'comment-link-remove-admin-2', // page
            'comment_link_remove_setting_section_1' // section
        );

    }

    // section 3
     public function comment_link_remove_page_init_3() {
        add_settings_section(
            'comment_link_remove_setting_section_2', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_3' ), // callback
            'comment-link-remove-admin-3' // page
        );

        

        add_settings_field(
            'comment_link_follow', // id
            __('Set “follow” or “nofollow” to Comments Link ', 'qc-clr'), // title
            array( $this, 'comment_link_follow_callback' ), // callback
            'comment-link-remove-admin-3', // page
            'comment_link_remove_setting_section_2' // section
        );

        add_settings_field(
            'comment_link_noreffer', // id
            __('Add “noreferrer” to “rel” attribute if  Comments Link Open in New Tab  ', 'qc-clr'), // title
            array( $this, 'comment_link_noreffer_callback' ), // callback
            'comment-link-remove-admin-3', // page
            'comment_link_remove_setting_section_2' // section
        );

        add_settings_field(
            'comment_link_noopener', // id
            __('Add “noopener” to “rel” attribute if  Comments Link Open in New Tab  ', 'qc-clr'), // title
            array( $this, 'comment_link_noopener_callback' ), // callback
            'comment-link-remove-admin-3', // page
            'comment_link_remove_setting_section_2' // section
        );


    }

    // section 4
    public function comment_link_remove_page_init_4() {
        add_settings_section(
            'comment_link_remove_setting_section_3', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_4' ), // callback
            'comment-link-remove-admin-4' // page
        );

        add_settings_field(
            'comment_notify_user_comment_approved', // id
            __('Notify a user when their comment is approved', 'qc-clr'), // title
            array( $this, 'comment_notify_user_comment_approved_callback' ), // callback
            'comment-link-remove-admin-4', // page
            'comment_link_remove_setting_section_3' // section
        );
        add_settings_field(
            'notify_user_comment_subject', // id
            __('Notify Subject', 'qc-clr'), // title
            array( $this, 'notify_user_comment_subject_callback' ), // callback
            'comment-link-remove-admin-4', // page
            'comment_link_remove_setting_section_3' // section
        );

        add_settings_field(
            'notify_user_comment_messsage', // id
            __('Notify Message ', 'qc-clr'), // title
            array( $this, 'notify_user_comment_messsage_callback' ), // callback
            'comment-link-remove-admin-4', // page
            'comment_link_remove_setting_section_3' // section
        );

    }

    // section 4
    public function comment_link_remove_page_init_5() {
        add_settings_section(
            'comment_link_remove_setting_section_4', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_5' ), // callback
            'comment-link-remove-admin-5' // page
        );

        add_settings_field(
            'comment_redirect', // id
            __('Redirect Page after Comments', 'qc-clr'), // title
            array( $this, 'comment_redirect_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );


        add_settings_field(
            'comment_readmore', // id
            __('Enable Comments Read More option', 'qc-clr'), // title
            array( $this, 'comment_readmore_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_readmore_length', // id
            __('Show Read More after (in Words)', 'qc-clr'), // title
            array( $this, 'comment_readmore_length_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_admin_section_email_individual_comenters', // id
            __('Links in the admin comments section to email individual commenters', 'qc-clr'), // title
            array( $this, 'comment_admin_section_email_individual_comenters_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_button_wp_toolbar_email_commenters_post', // id
            __('Enable a button in the WP toolbar to email all the commenters on a post', 'qc-clr'), // title
            array( $this, 'comment_button_wp_toolbar_email_commenters_post_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_add_sidebar_widget_show_top_commentators', // id
            __('Adds a sidebar widget to show the top commentators in your WP site', 'qc-clr'), // title
            array( $this, 'comment_add_sidebar_widget_show_top_commentators_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_show_all_comments', // id
            __('Show All Comments', 'qc-clr'), // title
            array( $this, 'comment_show_all_comments_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

        add_settings_field(
            'comment_vertical_scroll_recent_widget_comments', // id
            __('Vertical scroll in recent comments widget', 'qc-clr'), // title
            array( $this, 'comment_vertical_scroll_recent_widget_comments_callback' ), // callback
            'comment-link-remove-admin-5', // page
            'comment_link_remove_setting_section_4' // section
        );

    }




    public function comment_link_remove_page_init_6() {
        add_settings_section(
            'comment_link_remove_setting_section_5', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_6' ), // callback
            'comment-link-remove-admin-6' // page
        );

       add_settings_field(
            'like_dislike', // id
            __('Enable Like/Dislike', 'qc-clr'), // title
            array( $this, 'like_dislike' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );
        add_settings_field(
            'enable_emotions', // id
            __('Show Emotions in Comment', 'qc-clr'), // title
            array( $this, 'enable_emotions' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );
        add_settings_field(
            'enable_comment_filter', // id
            __('Allow Comments Filtering by Sentiment (Frontend)', 'qc-clr'), // title
            array( $this, 'enable_comment_filter' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );
        
        add_settings_field(
            'enable_comment_threshold', // id
            __('Allow Comments Threshold by Negative Score', 'qc-clr'), // title
            array( $this, 'enable_comment_threshold' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );
        
        add_settings_field(
            'treshold_score', // id
            __('Threshold by Negative Score (EX: 0.5)', 'qc-clr'), // title
            array( $this, 'treshold_score' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );
        
        add_settings_field(
            'treshold_msg', // id
            __('Threshold Message', 'qc-clr'), // title
            array( $this, 'treshold_msg' ), // callback
            'comment-link-remove-admin-6', // page
            'comment_link_remove_setting_section_5' // section
        );

    }


    public function comment_link_remove_page_init_8() {
        add_settings_section(
            'comment_link_remove_setting_section_8', // id
            '', // title
            array( $this, 'comment_link_remove_section_info_8' ), // callback
            'comment-link-remove-admin-8' // page
        );

       add_settings_field(
            'qc_clr_email_subscription_enalbe_disable', // id
            __('Enable Email Subscription', 'qc-clr'), // title
            array( $this, 'qc_clr_email_subscription_enalbe_disable_callback' ), // callback
            'comment-link-remove-admin-8', // page
            'comment_link_remove_setting_section_8' // section
        );
        add_settings_field(
            'qc_clr_email_subscription_lang_text', // id
            __('Language ( Subscribe to our newsletter ) ', 'qc-clr'), // title
            array( $this, 'qc_clr_email_subscription_lang_text_callback' ), // callback
            'comment-link-remove-admin-8', // page
            'comment_link_remove_setting_section_8' // section
        );
        add_settings_field(
            'qc_clr_email_subscription_mailchamp_enalbe_disable', // id
            __('Enable Mailchimp :', 'qc-clr'), // title
            array( $this, 'qc_clr_email_subscription_mailchamp_enalbe_disable_callback' ), // callback
            'comment-link-remove-admin-8', // page
            'comment_link_remove_setting_section_8' // section
        );
        add_settings_field(
            'qc_clr_email_subscription_zapier_enalbe_disable', // id
            __('Enable Zapier', 'qc-clr'), // title
            array( $this, 'qc_clr_email_subscription_zapier_enalbe_disable_callback' ), // callback
            'comment-link-remove-admin-8', // page
            'comment_link_remove_setting_section_8' // section
        );

    }
    

    public function comment_link_remove_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['remove_author_uri_field_0'] ) ) {
            $sanitary_values['remove_author_uri_field_0'] = $input['remove_author_uri_field_0'];
        }

        if ( isset( $input['remove_any_link_from_author_field_1'] ) ) {
            $sanitary_values['remove_any_link_from_author_field_1'] = $input['remove_any_link_from_author_field_1'];
        }

        if ( isset( $input['remove_links_from_comments_field_3'] ) ) {
            $sanitary_values['remove_links_from_comments_field_3'] = $input['remove_links_from_comments_field_3'];
        }

        if ( isset( $input['remove_links_from_comments_field_2'] ) ) {
            $sanitary_values['remove_links_from_comments_field_2'] = $input['remove_links_from_comments_field_2'];
        }

        if ( isset( $input['disable_comments_totally'] ) ) {
            $sanitary_values['disable_comments_totally'] = $input['disable_comments_totally'];
        }

        if ( isset( $input['hide_existing_cmts'] ) ) {
            $sanitary_values['hide_existing_cmts'] = $input['hide_existing_cmts'];
        }

        if ( isset( $input['open_link_innewtab'] ) ) {
            $sanitary_values['open_link_innewtab'] = $input['open_link_innewtab'];
        }

        return $sanitary_values;
    }

    public function comment_link_remove_section_info() {
        
    }

    public function comment_link_remove_section_info_2() {
        
    }

    public function comment_link_remove_section_info_3() {
        
    }

    public function comment_link_remove_section_info_4() {
        
    }

    public function comment_link_remove_section_info_5() {
        
    }

    public function comment_link_remove_section_info_6() {
        
    }

    public function comment_link_remove_section_info_8() {
        
    }


    public function remove_author_uri_field_0_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[remove_author_uri_field_0]" id="remove_author_uri_field_0" value="1" %s>',
            ( isset( $this->comment_link_remove_options['remove_author_uri_field_0'] ) && $this->comment_link_remove_options['remove_author_uri_field_0'] === '1' ) ? 'checked' : ''
        );
    }

    public function remove_any_link_from_author_field_1_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[remove_any_link_from_author_field_1]" id="remove_any_link_from_author_field_1" value="1" %s>',
            ( isset( $this->comment_link_remove_options['remove_any_link_from_author_field_1'] ) && $this->comment_link_remove_options['remove_any_link_from_author_field_1'] === '1' ) ? 'checked' : ''
        );
    }

    public function remove_links_from_comments_field_3_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[remove_links_from_comments_field_3]" id="remove_links_from_comments_field_3" value="1" %s>',
            ( isset( $this->comment_link_remove_options['remove_links_from_comments_field_3'] ) && $this->comment_link_remove_options['remove_links_from_comments_field_3'] === '1' ) ? 'checked' : ''
        );
    }

    public function remove_links_from_comments_field_2_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[remove_links_from_comments_field_2]" id="remove_links_from_comments_field_2" value="1" %s>',
            ( isset( $this->comment_link_remove_options['remove_links_from_comments_field_2'] ) && $this->comment_link_remove_options['remove_links_from_comments_field_2'] === '1' ) ? 'checked' : ''
        );
    }

    public function disable_comments_totally_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[disable_comments_totally]" id="disable_comments_totally" value="1" %s>',
            ( isset( $this->comment_link_remove_options['disable_comments_totally'] ) && $this->comment_link_remove_options['disable_comments_totally'] === '1' ) ? 'checked' : ''
        );
    }

    public function hide_existing_cmts_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[hide_existing_cmts]" id="hide_existing_cmts" value="1" %s>',
            ( isset( $this->comment_link_remove_options['hide_existing_cmts'] ) && $this->comment_link_remove_options['hide_existing_cmts'] === '1' ) ? 'checked' : ''
        );
    }

    public function open_link_innewtab_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[open_link_innewtab]" id="open_link_innewtab" value="1" %s>',
            ( isset( $this->comment_link_remove_options['open_link_innewtab'] ) && $this->comment_link_remove_options['open_link_innewtab'] === '1' ) ? 'checked' : ''
        );
    }
    public function like_dislike() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[like_dislike]" id="like_dislike" value="1" %s>',
            ( isset( $this->comment_link_remove_options['like_dislike'] ) && $this->comment_link_remove_options['like_dislike'] === '1' ) ? 'checked' : ''
        );
    }
    public function enable_emotions() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[enable_emotions]" id="enable_emotions" value="1" %s>',
            ( isset( $this->comment_link_remove_options['enable_emotions'] ) && $this->comment_link_remove_options['enable_emotions'] === '1' ) ? 'checked' : ''
        );
    }
    
    public function enable_comment_filter() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[enable_comment_filter]" id="enable_comment_filter" value="1" %s>',
            ( isset( $this->comment_link_remove_options['enable_comment_filter'] ) && $this->comment_link_remove_options['enable_comment_filter'] === '1' ) ? 'checked' : ''
        );
    }
    public function enable_comment_threshold() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[enable_comment_threshold]" id="enable_comment_threshold" value="1" %s>',
            ( isset( $this->comment_link_remove_options['enable_comment_threshold'] ) && $this->comment_link_remove_options['enable_comment_threshold'] === '1' ) ? 'checked' : ''
        );
    }
    public function treshold_score() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[treshold_score]" id="treshold_score" value="%s" placeholder="Ex: 0.5">',
            ( isset( $this->comment_link_remove_options['treshold_score'] ) && $this->comment_link_remove_options['treshold_score'] != '' ) ? $this->comment_link_remove_options['treshold_score'] : '0.5'
        );
        echo '<span style="font-size: 12px;margin-left: 10px;">This will display alert for Comments that contain negative words. Adjust the Threshold Score to accommodate your user base.</span>';
    }
    public function treshold_msg() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[treshold_msg]" id="treshold_msg" value="%s" placeholder="">',
            ( isset( $this->comment_link_remove_options['treshold_msg'] ) && $this->comment_link_remove_options['treshold_msg'] != '' ) ? $this->comment_link_remove_options['treshold_msg'] : 'Your comment is too negative! Please be polite and professional while writing!'
        );
    }
    public function comment_time_minimum_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[comment_time_minimum]" id="comment_time_minimum" value="%d" >',
            ( isset( $this->comment_link_remove_options['comment_time_minimum'] ) && $this->comment_link_remove_options['comment_time_minimum'] !='' )? $this->comment_link_remove_options['comment_time_minimum']  :10
        );
    }
    public function comment_quick_mgs_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[comment_quick_mgs]" id="comment_quick_mgs" value="%s" >',
            ( isset( $this->comment_link_remove_options['comment_quick_mgs'] ) && $this->comment_link_remove_options['comment_quick_mgs'] !='' ) ? $this->comment_link_remove_options['comment_quick_mgs'] : 'You are posting comments too quickly'
        );
    }
    public function comment_minimum_length_callback() {
        printf(
            '<input type="number" name="comment_link_remove_option_name[comment_minimum_length]" id="comment_minimum_length" value="%d" >',
            ( isset( $this->comment_link_remove_options['comment_minimum_length'] ) && $this->comment_link_remove_options['comment_minimum_length'] !='' )? $this->comment_link_remove_options['comment_time_minimum']  :15
        );
    }
    public function comment_minimum_length_mgs_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[comment_minimum_length_mgs]" id="comment_minimum_length_mgs" value="%s" >',
            ( isset( $this->comment_link_remove_options['comment_minimum_length_mgs'] ) && $this->comment_link_remove_options['comment_minimum_length_mgs'] !='' ) ? $this->comment_link_remove_options['comment_minimum_length_mgs'] : 'Your comments is too short, try to say some more useful messages.'
        );
    }
    public function comment_maximum_length_callback() {
            printf(
                '<input type="number" name="comment_link_remove_option_name[comment_maximum_length]" id="comment_maximum_length" value="%d" >',
                ( isset( $this->comment_link_remove_options['comment_maximum_length'] ) && $this->comment_link_remove_options['comment_maximum_length'] !='' )? $this->comment_link_remove_options['comment_maximum_length']  :1500
            );
    }
    public function comment_maximum_length_mgs_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[comment_maximum_length_mgs]" id="comment_maximum_length_mgs" value="%s" >',
            ( isset( $this->comment_link_remove_options['comment_maximum_length_mgs'] ) && $this->comment_link_remove_options['comment_maximum_length_mgs'] !='' ) ? $this->comment_link_remove_options['comment_maximum_length_mgs'] : 'Your Comments is too long, try to minimize useful message.'
        );
    }

    public function comment_redirect_callback() {
        $clr_options = get_option( 'comment_link_remove_option_name' );


        $comment_redirect = isset($clr_options['comment_redirect']) ? $clr_options['comment_redirect'] : 0;
        wp_dropdown_pages( array(
            'name'              => "comment_link_remove_option_name[comment_redirect]",
            'id'                => 'comment_redirect',
            'depth'             => 0,
            'option_none_value' => 0,
            'selected'          => $comment_redirect,
            'show_option_none'  => __( 'Don\'t redirect first time commenters', 'qc-clr' ),
        ) );

        if (0 != $comment_redirect ) {
            echo '<br><br><a target="_blank" href="' . get_permalink( $clr_options['comment_redirect'] ) . '">' . __( 'Current redirect page', 'qc-clr' ) . '</a>';
        }
    }

     public function comment_readmore_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_readmore]" id="comment_readmore" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_readmore'] ) && $this->comment_link_remove_options['comment_readmore'] =='1' ) ? 'checked' : ''
        );
    }

    public function comment_readmore_length_callback() {
        printf(
            '<input type="text" style="width:200px" name="comment_link_remove_option_name[comment_readmore_length]" id="comment_readmore_length" value="%d" >',
            ( isset( $this->comment_link_remove_options['comment_readmore_length'] ) && $this->comment_link_remove_options['comment_readmore_length'] !=0 ) ? $this->comment_link_remove_options['comment_readmore_length'] : 10
        );
    }

    public function comment_link_follow_callback() {

        $clr_options = get_option( 'comment_link_remove_option_name' );
        $comment_link_follow = isset($clr_options['comment_link_follow']) ? $clr_options['comment_link_follow'] : "";
        ?>
        <select name="comment_link_remove_option_name[comment_link_follow]" id="comment_link_follows">
            <option value="">Choice an option</option>
            <option value="follow" <?php if($comment_link_follow=="follow"){echo 'selected';}  ?>>Add "follow"</option>
            <option value="nofollow" <?php if($comment_link_follow=="nofollow"){echo 'selected';}  ?>>Add "nofollow"</option>
        </select>
        <?php
    }

    public function comment_link_noreffer_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_link_noreffer]" id="comment_link_noreffer" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_link_noreffer'] ) && $this->comment_link_remove_options['comment_link_noreffer'] =='1' ) ? 'checked' : ''
        );
    }

    public function comment_link_noopener_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_link_noopener]" id="comment_link_noopener" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_link_noopener'] ) && $this->comment_link_remove_options['comment_link_noopener'] =='1' ) ? 'checked' : ''
        );
    }

    // Admin section email individual commenters
    public function comment_admin_section_email_individual_comenters_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_admin_section_email_individual_comenters]" id="comment_admin_section_email_individual_comenters" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_admin_section_email_individual_comenters'] ) && $this->comment_link_remove_options['comment_admin_section_email_individual_comenters'] =='1' ) ? 'checked' : ''
        );
    }

    // A button in the WP toolbar to email all the commenters on a post
    public function comment_button_wp_toolbar_email_commenters_post_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_button_wp_toolbar_email_commenters_post]" id="comment_button_wp_toolbar_email_commenters_post" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_button_wp_toolbar_email_commenters_post'] ) && $this->comment_link_remove_options['comment_button_wp_toolbar_email_commenters_post'] =='1' ) ? 'checked' : ''
        );
    }

    // Notify a user when their comment is approved.
    public function comment_notify_user_comment_approved_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_notify_user_comment_approved]" id="comment_notify_user_comment_approved" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_notify_user_comment_approved'] ) && $this->comment_link_remove_options['comment_notify_user_comment_approved'] =='1' ) ? 'checked' : ''
        );
    }

    // Notify a user when their comment is approved. notify_user_comment_subject / notify_user_comment_messsage
    public function notify_user_comment_subject_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[notify_user_comment_subject]" id="notify_user_comment_subject" value="%s" >',
            ( isset( $this->comment_link_remove_options['notify_user_comment_subject'] ) && $this->comment_link_remove_options['notify_user_comment_subject'] !='' ) ? $this->comment_link_remove_options['notify_user_comment_subject'] : 'Your comment has been approved'
        );
    }

    // Notify a user when their comment is approved.
    public function notify_user_comment_messsage_callback() {

        printf(
            '<textarea type="text" name="comment_link_remove_option_name[notify_user_comment_messsage]" id="notify_user_comment_messsage">%s</textarea>',
            ( isset( $this->comment_link_remove_options['notify_user_comment_messsage'] ) && $this->comment_link_remove_options['notify_user_comment_messsage'] !='' ) ? $this->comment_link_remove_options['notify_user_comment_messsage'] : 'Thanks for your comment! It has been approved. To view the post, look at the link be'
        );


    }

    // Adds a sidebar widget to show the top commentators in your WP site
    public function comment_add_sidebar_widget_show_top_commentators_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_add_sidebar_widget_show_top_commentators]" id="comment_add_sidebar_widget_show_top_commentators" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_add_sidebar_widget_show_top_commentators'] ) && $this->comment_link_remove_options['comment_add_sidebar_widget_show_top_commentators'] =='1' ) ? 'checked' : ''
        );
    }

    // Show All Comments
    public function comment_show_all_comments_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_show_all_comments]" id="comment_show_all_comments" value="1" %s ><code>[clr_all_comments][/clr_all_comments]</code> ( Add this shortcode your expected page )',
            ( isset( $this->comment_link_remove_options['comment_show_all_comments'] ) && $this->comment_link_remove_options['comment_show_all_comments'] =='1' ) ? 'checked' : ''
        );
    }

    // Vertical scroll recent comments widget
    public function comment_vertical_scroll_recent_widget_comments_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_vertical_scroll_recent_widget_comments]" id="comment_vertical_scroll_recent_widget_comments" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_vertical_scroll_recent_widget_comments'] ) && $this->comment_link_remove_options['comment_vertical_scroll_recent_widget_comments'] =='1' ) ? 'checked' : ''
        );
    }

    // Allows to export the names and email addresses of users, who have left comments on the blog
    public function comment_allows_export_the_names_and_email_addresses_comments_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[comment_allows_export_the_names_and_email_addresses_comments]" id="comment_allows_export_the_names_and_email_addresses_comments" value="1" %s >',
            ( isset( $this->comment_link_remove_options['comment_allows_export_the_names_and_email_addresses_comments'] ) && $this->comment_link_remove_options['comment_allows_export_the_names_and_email_addresses_comments'] =='1' ) ? 'checked' : ''
        );
    }


    // qc_clr_email_subscription_enalbe_disable
    public function qc_clr_email_subscription_enalbe_disable_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[qc_clr_email_subscription_enalbe_disable]" id="qc_clr_email_subscription_enalbe_disable" value="1" %s >',
            ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_enalbe_disable'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_enalbe_disable'] =='1' ) ? 'checked' : ''
        );
    }

    // qc_clr_email_subscription_lang_text
    public function qc_clr_email_subscription_lang_text_callback() {
        printf(
            '<input type="text" name="comment_link_remove_option_name[qc_clr_email_subscription_lang_text]" id="qc_clr_email_subscription_lang_text" value="%s" >',
            ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_lang_text'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_lang_text'] !='' ) ? $this->comment_link_remove_options['qc_clr_email_subscription_lang_text'] : 'Subscribe to our newsletter'
        );
    }

    // qc_clr_email_subscription_zapier_enalbe_disable
    public function qc_clr_email_subscription_zapier_enalbe_disable_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[qc_clr_email_subscription_zapier_enalbe_disable]" id="qc_clr_email_subscription_zapier_enalbe_disable" value="1" %s > %s',
            ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_zapier_enalbe_disable'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_zapier_enalbe_disable'] =='1' ) ? 'checked' : '', ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_zapier_enalbe_disable'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_zapier_enalbe_disable'] =='1' ) ? '<a href="edit.php?post_type=qc_clr_zapier"> Manage Settings</a>' : ''
        );
    }

    // qc_clr_email_subscription_mailchamp_enalbe_disable
    public function qc_clr_email_subscription_mailchamp_enalbe_disable_callback() {
        printf(
            '<input type="checkbox" name="comment_link_remove_option_name[qc_clr_email_subscription_mailchamp_enalbe_disable]" id="qc_clr_email_subscription_mailchamp_enalbe_disable" value="1" %s > %s',
            ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_mailchamp_enalbe_disable'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_mailchamp_enalbe_disable'] =='1' ) ? 'checked' : '',  ( isset( $this->comment_link_remove_options['qc_clr_email_subscription_mailchamp_enalbe_disable'] ) && $this->comment_link_remove_options['qc_clr_email_subscription_mailchamp_enalbe_disable'] =='1' ) ? '<a href="edit.php?post_type=qc_clr_mailchamp"> Manage Settings</a>' : ''
        );
    }




}


function comment_link_remove_commenter_email_pro_feature(){

    ?>

        <div class="wrap">
            <div class="qcld_express_wrap">
                <h2><?php  esc_html_e( 'List of All Commenter Email IDs', 'qc-clr' ); ?></h2>
                <p><?php  esc_html_e( 'List of All Commenter Email IDs is a Pro Version Feature. Please ', 'qc-clr' ); ?> <a href="<?php  echo esc_url( 'https://www.quantumcloud.com/products/comment-tools/', 'qc-clr' ); ?>" target="_blank"><span class="qc_clr_pro_feature" > <?php  esc_html_e( 'Upgrade to the Pro Version.', 'qc-clr' ); ?></span> </a></p>
               <p><?php  esc_html_e( 'There are 5 unique and approved commenter email addresses for this site.', 'qc-clr' ); ?></p>
            </div>
        </div>
        <div class="wrap">
           <table class="qc-clr-commenter-emails-table">
              <tbody>
                 <tr>
                    <th><?php  esc_html_e( 'Email', 'qc-clr' ); ?></th>
                    <th><?php  esc_html_e( 'Name', 'qc-clr' ); ?></th>
                    <th><?php  esc_html_e( 'URL', 'qc-clr' ); ?></th>
                 </tr>
                 <tr>
                    <td><?php  esc_html_e( 'sample1@gmail.com', 'qc-clr' ); ?></td>
                    <td><?php  esc_html_e( 'Alex Gould', 'qc-clr' ); ?></td>
                    <td><?php  echo esc_url( 'https://sample.com', 'qc-clr' ); ?></td>
                 </tr>
                 <tr>
                    <td><?php  esc_html_e( 'sample2@gmail.com', 'qc-clr' ); ?></td>
                    <td><?php  esc_html_e( 'D.R. Allisonin', 'qc-clr' ); ?></td>
                    <td><?php  echo esc_url( 'https://sample2.com', 'qc-clr' ); ?></td>
                 </tr>
                 <tr>
                    <td><?php  esc_html_e( 'sample3@gmail.com', 'qc-clr' ); ?></td>
                    <td><?php  esc_html_e( 'Anonymous', 'qc-clr' ); ?></td>
                    <td></td>
                 </tr>
                 <tr>
                    <td><?php  esc_html_e( 'sample4@gmail.com', 'qc-clr' ); ?></td>
                    <td><?php  esc_html_e( 'Dave Arnold', 'qc-clr' ); ?></td>
                    <td></td>
                 </tr>
                 <tr>
                    <td><?php  esc_html_e( 'sample5@gmail.com', 'qc-clr' ); ?></td>
                    <td><?php  esc_html_e( 'Manuel Castillo', 'qc-clr' ); ?></td>
                    <td></td>
                 </tr>
              </tbody>
           </table>
           <p><?php  esc_html_e( '5 commenter emails listed.', 'qc-clr' ); ?></p>
        </div>
        <div class="wrap">
           <p></p>
           <form action="" method="get">
              <label for="include_url"><input type="checkbox" name="include_url" id="include_urls" value="1"> <?php  esc_html_e( 'Include commenter website?', 'qc-clr' ); ?> <br><input type="hidden" name="page" value="comment-link-remove/qc-clr-export-comments.php"><input type="hidden" name="download_csv" value="1"><label for="submit"><?php  esc_html_e( 'Download this list of email addresses as a CSV file :', 'qc-clr' ); ?> <input type="submit" name="submit" class="qc_clr_btn_disabled" value="Download"></label>
              <p></p>
              </label>
           </form>
            <div class="qcld_express_wrap">
                <p> <a href="<?php  echo esc_url( 'https://www.quantumcloud.com/products/comment-tools/', 'qc-clr' ); ?>" target="_blank"><span  class="qc_clr_pro_feature" > <?php  esc_html_e( 'Upgrade to the Pro Version.', 'qc-clr' ); ?> </span> </a></p>
            </div>
        </div>

        <?php

}


if ( is_admin() )
    $comment_link_remove = new CommentLinkRemove();