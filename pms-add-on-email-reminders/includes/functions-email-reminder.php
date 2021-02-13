<?php
/**
 * Functions for things related to email reminders
 */


/**
 * Returns all email reminders into an array of objects
 *
 * @param $only_active   - true to return only active email reminders, false to return all
 *
 * @param $trigger_unit   - used to filter email reminders which should be send by the hourly or the daily cron job
 *
 *  @return array
 */
function pms_get_email_reminders( $trigger_unit, $only_active = true ) {

    $post_status = ( $only_active == true ) ? 'active' : 'any';
    $email_reminders = get_posts( array('post_type' => 'pms-email-reminders', 'numberposts' => -1, 'post_status' => $post_status ) );

    $email_reminders_array = array();

    // return array of email reminder objects
    if ( !empty($email_reminders) ) {

        foreach ( $email_reminders as $reminder ) {

            $email_reminder = new PMS_Email_Reminder($reminder->ID);

            if ( ( $trigger_unit == 'hourly' ) && ( $email_reminder->trigger_unit == 'hour' ) )
                // return only the email reminders which should be sent by the hourly cron job
                $email_reminders_array[] = $email_reminder;

            if ( ( $trigger_unit != 'hourly' ) && ( $email_reminder->trigger_unit != 'hour' ) )
                // return only the email reminders who should be sent by the daily cron job
                $email_reminders_array[] = $email_reminder;

        }

    }

    return $email_reminders_array;
}


/**
 * Function that returns the member subscriptions that match filters made from the
 * given trigger and trigger unit
 *
 * @param PMS_Email_Reminder $email_reminder
 * @param string $trigger_unit
 *
 * @return array
 *
 */
function pms_er_get_member_subscriptions( $email_reminder, $trigger_unit ) {
    if( empty( $email_reminder->subscriptions ) )
        return;

    global $wpdb;

    // define subscription status to use in query
    $status = 'active';

    if ($email_reminder->event == 'after_member_abandons_signup')
        $status = 'pending';
    elseif ($email_reminder->event == 'after_subscription_expires')
        $status = 'expired';

    // define which column to use in the sql select based on email reminder event
    $column_name = "start_date";
    if ( ($email_reminder->event == 'before_subscription_expires') || ($email_reminder->event == 'after_subscription_expires') || ($email_reminder->event == 'before_subscription_renews_automatically') ) {
        $column_name = "expiration_date";
    }

    // used in defining the time intervals below
    $operator = ( ($email_reminder->event == 'before_subscription_expires') || ($email_reminder->event == 'before_subscription_renews_automatically') ) ? '+' : '-';

    // define time intervals
    $trigger_timestamp = strtotime( $operator . $email_reminder->trigger. ' ' . $email_reminder->trigger_unit);

    if ( $trigger_unit == 'hourly' ){
        // get 1 hour interval
        $begin = strtotime("-1 hour", $trigger_timestamp);
        $end = $trigger_timestamp;
    }
    else {
        // get begin and end of day interval
        $begin = strtotime("midnight", $trigger_timestamp);
        $end   = strtotime("tomorrow", $begin) - 1;
    }

    $begin_date = date("Y-m-d H:i:s", $begin);
    $end_date = date("Y-m-d H:i:s", $end);

    // create query string
    $query_string = "SELECT * ";
    $query_from = "FROM {$wpdb->prefix}pms_member_subscriptions member_subscriptions ";
    $query_join = "";
    $query_where = "WHERE member_subscriptions.status LIKE '" . $status. "'";

    // add inner join if email reminder event is 'since_last_login'
    if ( $email_reminder->event == 'since_last_login' ) {

        $query_join = "INNER JOIN {$wpdb->usermeta} usermeta ON member_subscriptions.user_id = usermeta.user_id ";
        $query_where .= " AND usermeta.meta_key = 'last_login' AND usermeta.meta_value BETWEEN '" . $begin_date . "' AND '" .$end_date . "' ";

    }
    else if ( $email_reminder->event == 'before_subscription_renews_automatically' )
        $query_where .= " AND member_subscriptions.billing_next_payment BETWEEN '" . $begin_date . "' AND '" . $end_date . "' ";
    else
        $query_where .= " AND member_subscriptions.{$column_name} BETWEEN '" . $begin_date . "' AND '" . $end_date . "' ";


    // add to query if member subscription is recurring
    if ( $email_reminder->event == 'before_subscription_renews_automatically' ) {

        $query_where .= " AND (
            ( member_subscriptions.payment_profile_id IS NOT NULL AND TRIM(member_subscriptions.payment_profile_id) <> '' ) OR
            ( member_subscriptions.expiration_date = '0000-00-00 00:00:00' )
        )";

    }

    // recurring subscriptions should not receive e-mails for before subscription expiration
    if ( $email_reminder->event == 'before_subscription_expires' ) {

        $query_where .= " AND member_subscriptions.payment_profile_id = '' ";

    }

    // get only results which have the subscription plans selected in the email reminder settings
    if ( strpos( $email_reminder->subscriptions, 'all_subscriptions' ) === false ) {

        $query_where .= " AND member_subscriptions.subscription_plan_id IN (" . $email_reminder->subscriptions . ") ";

    }

    // Concatenate the sections into the full query string
    $query_string .= $query_from . $query_join . $query_where;

    $results = $wpdb->get_results( $query_string, ARRAY_A );

    return $results;

}


/**
 * Function that sends the reminder emails
 *
 */
function pms_send_email_reminders( $result, $email_reminder ){

    $user_info = get_userdata( $result['user_id'] );

    // Set the reminder send to
    if( $email_reminder->send_to == 'user' ) {

        $reminder_send_to = $user_info->user_email;

    } else {

        $reminder_send_to = array();

        if( ! empty( $email_reminder->admin_emails ) ) {

            $admin_emails = array_map( 'trim', explode( ',', $email_reminder->admin_emails ) );

            foreach( $admin_emails as $key => $admin_email ) {

                if( ! is_email( $admin_email ) )
                    unset( $admin_emails[$key] );

            }

            $reminder_send_to = $admin_emails;

        }

    }

    // Set the reminder subject and content
    if ( class_exists( 'PMS_Merge_Tags' ) ) {

        $reminder_subject = PMS_Merge_Tags::pms_process_merge_tags( $email_reminder->subject, $user_info, $result['subscription_plan_id'], $result['start_date'], $result['expiration_date'], $result['status'] );
        $reminder_content = PMS_Merge_Tags::pms_process_merge_tags( $email_reminder->content, $user_info, $result['subscription_plan_id'], $result['start_date'], $result['expiration_date'], $result['status'] );

    } else {

        $reminder_subject = $email_reminder->subject;
        $reminder_content = $email_reminder->content;

    }

    // Format email message
    $reminder_content = wpautop( $reminder_content );

    //we add this filter to enable html encoding
    add_filter( 'wp_mail_content_type', 'pms_er_email_content_type' );

    // Temporary change the from name and from email
    add_filter( 'wp_mail_from_name', 'pms_er_from_name', 20, 1 );
    add_filter( 'wp_mail_from', 'pms_er_from_email', 20, 1 );

    wp_mail( $reminder_send_to, $reminder_subject, $reminder_content );

    // Reset html encoding
    remove_filter( 'wp_mail_content_type', 'pms_er_email_content_type' );

    // Reset the from name and email
    remove_filter( 'wp_mail_from_name', 'pms_er_from_name', 20 );
    remove_filter( 'wp_mail_from', 'pms_er_from_email', 20 );

}


/*
 * Process email reminders
 *
 */
function pms_process_email_reminders( $trigger_unit = 'daily' ){

    $email_reminders_array = pms_get_email_reminders( $trigger_unit );

    // Check if we have any active email reminders
    if ( !empty( $email_reminders_array ) ) {

        foreach ( $email_reminders_array as $email_reminder ) {

            $results = pms_er_get_member_subscriptions( $email_reminder, $trigger_unit );

            // if we have any results from the query send the reminder emails
            if ( ! empty( $results ) ) {

                // for the 'since_last_login' event, send only one reminder email per user (even if he has multiple active subscriptions) and use the first subscription data in merge tags
                if ( $email_reminder->event == 'since_last_login' ) $results = $results[0];

                foreach ($results as $result) {

                    pms_send_email_reminders($result, $email_reminder);

                }

            }

        } // end foreach

    }

}
//add_action('init', 'pms_process_email_reminders');
add_action('pms_send_email_reminders_hourly', 'pms_process_email_reminders', 10, 1);
add_action('pms_send_email_reminders_daily', 'pms_process_email_reminders', 10, 1);

function pms_er_from_name( $site_name ) {
    $pms_settings = get_option( 'pms_emails_settings' );

    if ( !empty( $pms_settings['email-from-name'] ) )
        $site_name = $pms_settings['email-from-name'];
    else
        $site_name = get_bloginfo('name');

    return $site_name;
}

function pms_er_from_email() {
    $pms_settings = get_option( 'pms_emails_settings' );

    if ( ! empty( $pms_settings['email-from-email'] ) ) {

        if( is_email( $pms_settings['email-from-email'] ) )
            $sender_email = $pms_settings['email-from-email'];

    } else
        $sender_email = get_bloginfo( 'admin_email' );

    return $sender_email;
}

function pms_er_email_content_type() {

    return 'text/html';

}
