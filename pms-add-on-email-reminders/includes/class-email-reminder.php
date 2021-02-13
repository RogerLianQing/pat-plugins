<?php

class PMS_Email_Reminder {

    public $id;

    public $name;

    public $send_to;

    public $admin_emails;

    public $trigger;

    public $trigger_unit;

    public $event;

    public $subject;

    public $content;

    public $subscriptions;

    public $status;

    public $recurring_subscriptions;


    public function __construct( $id_or_post ) {

        if( !is_object( $id_or_post ) )
            $id_or_post = (int)$id_or_post;

        // Abort if id is not an integer
        if( !is_object( $id_or_post ) && !is_int( $id_or_post ) )
            return;

        $this->init( $id_or_post );
    }

    public function init( $id_or_post ){
        /*
         * Set Email reminder data from the cpt itself
         *
         */

        if( is_object( $id_or_post ) ) {

            $id = $id_or_post->ID;
            $email_reminder = $id_or_post;

        } else {

            $id = $id_or_post;
            $email_reminder = get_post( $id );

        }


        if( !$email_reminder )
            return null;

        $this->id   = $email_reminder->ID;
        $this->name = $email_reminder->post_title;


        /*
         * Set Email Reminder data from the post meta data (metabox)
         *
         */
        $post_meta_email_reminder = get_post_meta( $id );

        // Email reminder send to option
        $this->send_to = isset( $post_meta_email_reminder['pms_email_reminder_send_to'] ) ? $post_meta_email_reminder['pms_email_reminder_send_to'][0] : 'user';

        // Email reminder admin emails
        $this->admin_emails = isset( $post_meta_email_reminder['pms_email_reminder_admin_emails'] ) ? $post_meta_email_reminder['pms_email_reminder_admin_emails'][0] : '';

        // Email reminder trigger
        $this->trigger =  isset( $post_meta_email_reminder['pms_email_reminder_trigger'] ) ? $post_meta_email_reminder['pms_email_reminder_trigger'][0] : '';

        // Email reminder trigger unit
        $this->trigger_unit =  isset( $post_meta_email_reminder['pms_email_reminder_trigger_unit'] ) ? $post_meta_email_reminder['pms_email_reminder_trigger_unit'][0] : '';

        // Email reminder event
        $this->event =  isset( $post_meta_email_reminder['pms_email_reminder_event'] ) ? $post_meta_email_reminder['pms_email_reminder_event'][0] : '';

        // Email subject
        $this->subject =  isset( $post_meta_email_reminder['pms_email_reminder_subject'] ) ? $post_meta_email_reminder['pms_email_reminder_subject'][0] : '';

        // Email content
        $this->content =  isset( $post_meta_email_reminder['pms-email-reminder-content'] ) ? $post_meta_email_reminder['pms-email-reminder-content'][0] : '';

        // Email reminder subscriptions
        $this->subscriptions = isset( $post_meta_email_reminder['pms_email_reminder_subscriptions'] ) ? $post_meta_email_reminder['pms_email_reminder_subscriptions'][0] : '';

        // Email reminder status
        $this->status =  isset( $post_meta_email_reminder['pms_email_reminder_status'] ) ? $post_meta_email_reminder['pms_email_reminder_status'][0] : '';

    }


    /*
     * Method that checks if the Email Reminder status is active
     *
     */
    public function is_active() {

        if( $this->status == 'active' )
            return true;
        elseif ( $this->status == 'inactive' )
            return false;

    }


    /*
     * Activate the Email Reminder
     *
     * @param $post_id
     *
     */
    public static function activate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_email_reminder_status', 'active' );

        // Change the post status to "active" as well
        $post = array(
            'ID'           => $post_id,
            'post_status'   => 'active',
        );
        wp_update_post( $post );
    }


    /*
     * Deactivate the Email Reminder
     *
     * @param $post_id
     *
     */
    public static function deactivate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_email_reminder_status', 'inactive' );

        // Change the post status to "inactive" as well
        $post = array(
            'ID'           => $post_id,
            'post_status'   => 'inactive',
        );
        wp_update_post( $post );

    }


    /*
     * Delete Email reminder
     *
     * @param $post_id
     *
     */
    public static function remove( $post_id ) {

        $email_reminder_post = get_post( $post_id );

        // If the post doesn't exist just skip everything
        if( !$email_reminder_post )
            return;


        wp_delete_post( $post_id );

    }


}