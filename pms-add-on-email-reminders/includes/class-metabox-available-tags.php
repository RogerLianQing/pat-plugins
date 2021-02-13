<?php
/**
 * Class for adding the Email Reminder Available Tags
 */

if ( class_exists( 'PMS_Meta_Box' ) ) {

    class PMS_ER_Available_Tags_Meta_Box extends PMS_Meta_Box {

        /*
         * Method to hook the output methods
         *
         * */
        public function init() {

            // Hook the output method to the parent's class action for output instead of overwriting the output_content method
            add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );

            if( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '2.0.2', '<' ) === true ) {

                // Hook to add more Available Merge Tags
                add_filter( 'pms_merge_tags', array( $this, 'add_more_tags') );

                /* Filters for replacing merge tags with actual content */
                add_filter( 'pms_merge_tag_subscription_status', array( $this, 'pms_tag_subscription_status' ), 10, 6 );
                add_filter( 'pms_merge_tag_subscription_start_date', array( $this, 'pms_tag_subscription_start_date' ), 10, 6 );
                add_filter( 'pms_merge_tag_subscription_expiration_date', array( $this, 'pms_tag_subscription_expiration_date' ), 10, 6 );
                add_filter( 'pms_merge_tag_subscription_price', array( $this, 'pms_tag_subscription_price' ), 10, 6 );
                add_filter( 'pms_merge_tag_subscription_duration', array( $this, 'pms_tag_subscription_duration' ), 10, 6 );
                add_filter( 'pms_merge_tag_username', array( $this, 'pms_tag_username' ), 10, 6 );
                add_filter( 'pms_merge_tag_first_name', array( $this, 'pms_tag_firstname' ), 10, 6 );
                add_filter( 'pms_merge_tag_last_name', array( $this, 'pms_tag_lastname' ), 10, 6 );
                add_filter( 'pms_merge_tag_user_email', array( $this, 'pms_tag_user_email' ), 10, 6 );
                add_filter( 'pms_merge_tag_site_name', array( $this, 'pms_tag_site_name' ), 10, 6 );
                add_filter( 'pms_merge_tag_site_url', array( $this, 'pms_tag_site_url' ), 10, 6 );

            }
            
        }

        /*
         * Method to output the Email Reminder details metabox
         *
         * */
        public function output( $post ){

            $email_reminder = new PMS_Email_Reminder( $post );

            if ( class_exists( 'PMS_Merge_Tags' ) ){

                $available_merge_tags = PMS_Merge_Tags::get_merge_tags();

                foreach( $available_merge_tags as $available_merge_tag ){

                    echo ' <input readonly="" type="text"  value="{{'. $available_merge_tag .'}}"> ';
                }

            }

        }

        /*
         * Method to add more merge tags to the Available Tags metabox
         *
         * */
        public function add_more_tags( $available_merge_tags) {

            $er_available_merge_tags = apply_filters( 'pms_er_merge_tags', array( 'subscription_status', 'subscription_start_date', 'subscription_expiration_date', 'subscription_price', 'subscription_duration', 'first_name', 'last_name', 'username', 'user_email', 'site_name', 'site_url' ) );

            if ( is_array( $er_available_merge_tags ) ) {

                foreach ( $er_available_merge_tags as $tag )

                    $available_merge_tags[] = $tag;
            }

            return $available_merge_tags;
        }


        /**
         * Replace the {{subscription_status}} tag
         */
        public function pms_tag_subscription_status( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( !empty( $status ) )
                return $status;

        }

        /**
         * Replace the {{subscription_start_date}} tag
         */
        public function pms_tag_subscription_start_date( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( !empty( $start_date ) )

                return $start_date;
        }

        /**
         * Replace the {{subscription_expiration_date}} tag
         */
        public function pms_tag_subscription_expiration_date( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( !empty( $expiration_date ) )

                return $expiration_date;
        }

        /**
         * Replace the {{subscription_price}} tag
         */
        public function pms_tag_subscription_price( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( !empty( $user_info->ID ) ) {

                $payments = pms_get_payments( array( 'user_id' => $user_info->ID ) );

                // If the website is doing cron we don't want the price of the last payment
                if ( empty( $payments ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {

                    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

                    if ( !empty( $_POST['discount_code']) && !empty( $subscription_plan->price ) )
                        $amount = pms_calculate_discounted_amount( $subscription_plan->price, $_POST['discount_code'] );
                    else
                        $amount = $subscription_plan->price;
                } else
                    $amount = $payments[0]->amount;

                $currency = pms_get_active_currency();

                $price = ( $amount == 0 ) ? __( 'Free', 'paid-member-subscriptions' ) : $amount;

                return $price . ' ' . $currency;
            }

        }

        /**
         * Replace the {{subscription_duration}} tag
         */
        public function pms_tag_subscription_duration( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( !empty( $subscription_plan_id ) ) {

                if ( function_exists( 'pms_get_subscription_plan' ) ) {

                    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

                    if ( $subscription_plan->duration == 0 )

                        return __( 'unlimited', 'paid-member-subscriptions' );

                    else

                        return $subscription_plan->duration . ' ' . $subscription_plan->duration_unit . '(s)';
                }

            }

        }

        /**
         * Replace the {{username}} tag
         */
        public function pms_tag_username( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( is_object( $user_info ) && !empty( $user_info->user_login ) )

                return $user_info->user_login;

        }

        /**
         * Replace the {{first_name}} tag
         */
        public function pms_tag_firstname( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( is_object( $user_info ) && !empty( $user_info->ID ) ) {
                $first_name = get_user_meta( $user_info->ID, 'first_name', true );

                if ( !empty( $first_name ) )
                    return $first_name;
            }

        }

        /**
         * Replace the {{last_name}} tag
         */
        public function pms_tag_lastname( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( is_object( $user_info ) && !empty( $user_info->ID ) ) {
                $last_name = get_user_meta( $user_info->ID, 'last_name', true );

                if ( !empty( $last_name ) )
                    return $last_name;
            }

        }

        /**
         * Replace the {{user_email}} tag
         */
        public function pms_tag_user_email( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            if ( is_object( $user_info ) && !empty( $user_info->user_email ) )

                return $user_info->user_email;

        }

        /**
         * Replace the {{site_name}} tag
         */
        public function pms_tag_site_name( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            return html_entity_decode( get_bloginfo( 'name' ) );

        }

        /**
         * Replace the {{site_url}} tag
         */
        public function pms_tag_site_url( $value, $user_info, $subscription_plan_id, $start_date, $expiration_date, $status ){

            return get_bloginfo( 'url' );
        }




    } // end Class

    $pms_meta_box_available_tags = new PMS_ER_Available_Tags_Meta_Box( 'pms_er_available_tags', __( 'Available Tags', 'paid-member-subscriptions' ), 'pms-email-reminders', 'side' );
    $pms_meta_box_available_tags->init();

}
