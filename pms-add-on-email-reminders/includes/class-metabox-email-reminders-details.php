<?php
/*
 *
 * Class for adding the Email Reminders details metabox
 * */

if ( class_exists( 'PMS_Meta_Box' ) ) {

    class PMS_Email_Reminders_Meta_Box extends PMS_Meta_Box {

        /*
         * Method to hook the output and save data methods
         *
         * */
        public function init(){
            // Hook the output method to the parent's class action for output instead of overwriting the output_content method
            add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );

            // Hook the save_data method to the parent's class action for saving data instead of overwriting the save_meta_box method
            add_action( 'pms_save_meta_box_pms-email-reminders', array( $this, 'save_data' ) );

            // Add admin notices for validating the entered email reminder data
            add_action('admin_notices', array( $this, 'add_admin_notices' ) );

        }

        /*
         * Method to output the Email Reminder details metabox
         *
         * */
        public function output( $post ){

            $email_reminder = new PMS_Email_Reminder( $post );

            include_once 'views/view-meta-box-email-reminders.php';

        }

        /*
         * Method to save data from the Email Reminder details metabox
         *
         * */
        public function save_data( $post_id ){

            $validation_errors = array(); // here we'll store all the validation errors

            // Update email send to
            if( ! empty( $_POST['pms_email_reminder_send_to'] ) ) {
                update_post_meta( $post_id, 'pms_email_reminder_send_to', sanitize_text_field( $_POST['pms_email_reminder_send_to'] ) );
            }

            // Update admin emails
            if( ! empty( $_POST['pms_email_reminder_admin_emails'] ) ) {

                $admin_emails = array_map( 'trim', explode( ',', sanitize_text_field( $_POST['pms_email_reminder_admin_emails'] ) ) );

                foreach( $admin_emails as $key => $admin_email ) {

                    if( ! is_email( $admin_email ) )
                        unset( $admin_emails[$key] );

                }

                update_post_meta( $post_id, 'pms_email_reminder_admin_emails', implode( ', ', $admin_emails ) );

            } else
                update_post_meta( $post_id, 'pms_email_reminder_admin_emails', '' );


            // Update email trigger
            if( !empty( $_POST['pms_email_reminder_trigger'] ) ) {
                update_post_meta($post_id, 'pms_email_reminder_trigger', (int)$_POST['pms_email_reminder_trigger']);
            }

            // Update email trigger unit
            if( isset( $_POST['pms_email_reminder_trigger_unit'] ) ) {
                update_post_meta($post_id, 'pms_email_reminder_trigger_unit', sanitize_text_field( $_POST['pms_email_reminder_trigger_unit'] ) );
            }

            // Update email trigger event
            if( !empty( $_POST['pms_email_reminder_event'] ) ) {
                update_post_meta( $post_id, 'pms_email_reminder_event', sanitize_text_field( $_POST['pms_email_reminder_event'] ) );
            }

            // Update email subject
            if ( isset($_POST['pms_email_reminder_subject']) ) {
                if( !empty( $_POST['pms_email_reminder_subject'] ) ) {
                    update_post_meta( $post_id, 'pms_email_reminder_subject', sanitize_text_field( trim($_POST['pms_email_reminder_subject']) ) );
                }
                else
                    $validation_errors[] = __('Please fill in the Subject for the Email Reminder', 'paid-member-subscriptions');
            }

            // Update email content
            if ( isset($_POST['pms-email-reminder-content']) ){
                if( !empty( $_POST['pms-email-reminder-content'] ) ) {
                    update_post_meta( $post_id, 'pms-email-reminder-content', preg_replace( '@<(script)[^>]*?>.*?</\\1>@si', '', $_POST['pms-email-reminder-content'] ) );
                }
                else
                    $validation_errors[] = __('Please fill in the Content for the Email Reminder', 'paid-member-subscriptions');
            }

            // Update email reminder subscription(s)
            if ( isset($_POST['pms_email_reminder_subscriptions']) ){
                if( ! empty( $_POST['pms_email_reminder_subscriptions'] ) && is_array($_POST['pms_email_reminder_subscriptions']) ){
                    $email_reminder_subscriptions = implode(',', $_POST['pms_email_reminder_subscriptions']);
                    update_post_meta( $post_id, 'pms_email_reminder_subscriptions', $email_reminder_subscriptions );
                }
                else
                    $validation_errors[] = __('Please select at least one Subscription plan', 'paid-member-subscriptions');
            }


            // Update email reminder status
            if( isset( $_POST['pms_email_reminder_status'] ) ) {

                $status = sanitize_text_field( $_POST['pms_email_reminder_status'] );

                update_post_meta($post_id, 'pms_email_reminder_status', $status );

                if ( ! wp_is_post_revision( $post_id ) ){

                    // unhook this function so it doesn't loop infinitely
                    remove_action('pms_save_meta_box_pms-email-reminders', array( $this, 'save_data' ));

                    // Change the post status as the email reminder status
                    $post = array(
                        'ID'           => $post_id,
                        'post_status'  => $status,
                    );
                    wp_update_post( $post );

                    // re-hook this function
                    add_action('pms_save_meta_box_pms-email-reminders', array( $this, 'save_data' ) );

                }

            }

            // If we have validation errors, save them in a transient
            if ( !empty( $validation_errors ) ) {
                set_transient( 'pms_er_metabox_validation_errors', $validation_errors, 60 );
            }

        }


         /*
         * Method for displaying validation errors using admin_notices hook
         *
         */
            public function add_admin_notices() {

                $validation_errors = get_transient('pms_er_metabox_validation_errors');

                if ( !empty( $validation_errors ) ){

                    delete_transient( 'pms_er_metabox_validation_errors' );

                    foreach ( $validation_errors as $error )
                        echo '<div class="error">
                                <p>' . $error . '</p>
                             </div>';
                }
         }



    } // end class PMS_Email_Reminders_Meta_Box

    $pms_meta_box_email_reminders_details = new PMS_Email_Reminders_Meta_Box( 'pms_email_reminders', __( 'Email Reminder Details', 'paid-member-subscriptions' ), 'pms-email-reminders', 'normal' );
    $pms_meta_box_email_reminders_details->init();

}
