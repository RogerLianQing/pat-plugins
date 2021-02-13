jQuery(document).ready(function($) {

    /**********************************
     Disable pro feature
     *********************************/
    // Minimum Time (In Seconds) 
    $("#comment_time_minimum").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_time_minimum").attr("disabled",true);
    // Custom Message for Quick comments 
    $("#comment_quick_mgs").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_quick_mgs").attr("disabled",true);
    // Minimum Length of Character in Comments 
    $("#comment_minimum_length").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_minimum_length").attr("disabled",true);
    // Alert Message for Minimum Comments Length
    $("#comment_minimum_length_mgs").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_minimum_length_mgs").attr("disabled",true);
    // Maximum of Character in Comments
    $("#comment_maximum_length").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_maximum_length").attr("disabled",true);
    // Alert Message for Maximum Comments Length
    $("#comment_maximum_length_mgs").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_maximum_length_mgs").attr("disabled",true);


    // Notify a user when their comment is approved
    $("#comment_notify_user_comment_approved").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_notify_user_comment_approved").attr("disabled",true);
    // Notify Subject
    $("#notify_user_comment_subject").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#notify_user_comment_subject").attr("disabled",true);
    // Notify Message
    $("#notify_user_comment_messsage").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#notify_user_comment_messsage").attr("disabled",true);


    // Set “follow” or “nofollow” to Comments Link
    $("#comment_link_follows").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_link_follows").attr("disabled",true);
    // Add “noreferrer” to “rel” attribute if Comments Link Open in New Tab
    $("#comment_link_noreffer").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_link_noreffer").attr("disabled",true);
    // Add “noopener” to “rel” attribute if Comments Link Open in New Tab
    $("#comment_link_noopener").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_link_noopener").attr("disabled",true);


    // #ce0707irect Page after Comments
    $("#comment_redirect").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_redirect").attr("disabled",true);
    // Enable Comments Read More option
    $("#comment_readmore").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_readmore").attr("disabled",true);
    // Show Read More after (in Words)
    $("#comment_readmore_length").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_readmore_length").attr("disabled",true);
    // Links in the admin comments section to email individual commenters
    $("#comment_admin_section_email_individual_comenters").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_admin_section_email_individual_comenters").attr("disabled",true);
    // Enable a button in the WP toolbar to email all the commenters on a post
    $("#comment_button_wp_toolbar_email_commenters_post").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_button_wp_toolbar_email_commenters_post").attr("disabled",true);
    // Adds a sidebar widget to show the top commentators in your WP site
    $("#comment_add_sidebar_widget_show_top_commentators").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_add_sidebar_widget_show_top_commentators").attr("disabled",true);
    // Show All Comments
    $("#comment_show_all_comments").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_show_all_comments").attr("disabled",true);
    // Vertical scroll in recent comments widget
    $("#comment_vertical_scroll_recent_widget_comments").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#comment_vertical_scroll_recent_widget_comments").attr("disabled",true);


    $("#include_urls").attr("disabled",true);
    $(".qc_clr_btn_disabled").attr("disabled",true);

    // Enable Like/Dislike
    $("#like_dislike").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#like_dislike").attr("disabled",true);
    // Show Emotions in Comment
    $("#enable_emotions").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#enable_emotions").attr("disabled",true);
    // Allow Comments Filtering by Sentiment (Fontend)
    $("#enable_comment_filter").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#enable_comment_filter").attr("disabled",true);
    // Allow Comments Threshold by Negative Score
    $("#enable_comment_threshold").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#enable_comment_threshold").attr("disabled",true);
    // Threshold by Negative Score (EX: 0.5)
    $("#treshold_score").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#treshold_score").attr("disabled",true);
    
    // Threshold Message
    $("#treshold_msg").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#treshold_msg").attr("disabled",true);
    
    // qc_clr_email_subscription_enalbe_disable
    $("#qc_clr_email_subscription_enalbe_disable").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#qc_clr_email_subscription_enalbe_disable").attr("disabled",true);
    // qc_clr_email_subscription_lang_text
    $("#qc_clr_email_subscription_lang_text").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#qc_clr_email_subscription_lang_text").attr("disabled",true);
    // qc_clr_email_subscription_mailchamp_enalbe_disable
    $("#qc_clr_email_subscription_mailchamp_enalbe_disable").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#qc_clr_email_subscription_mailchamp_enalbe_disable").attr("disabled",true);
    // qc_clr_email_subscription_zapier_enalbe_disable
    $("#qc_clr_email_subscription_zapier_enalbe_disable").after('<strong class="qc-pro-alert">Pro Feature</strong>');
    $("#qc_clr_email_subscription_zapier_enalbe_disable").attr("disabled",true);


    $(".commentDelete").on("click", function(e){
        var result = false;
        result = confirm("Are you really want to delete? Once proceed, Option is irreversible.");
        return result;
    });

   
});