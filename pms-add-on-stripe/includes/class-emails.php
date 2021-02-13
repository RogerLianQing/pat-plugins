<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_Stripe_Emails {

    public function __construct(){

        add_action( 'pms_stripe_send_authentication_email',        array( $this, 'send_invitation_email' ), 10, 3 );

        // Add the invite email on the settings page
        add_action( 'pms-settings-page_tab_emails_after_user_tab', array( $this, 'add_auth_email_settings' ), 30 );

        // Add extra merge tags
        add_filter( 'pms_merge_tags',                              array( $this, 'add_extra_tags' ) );

        // Merge Tags handler functions
        add_filter( 'pms_merge_tag_stripe_auth_link',              array( $this, 'auth_link' ), 10, 2 );
        add_filter( 'pms_merge_tag_payment_amount',                array( $this, 'payment_amount' ), 10, 4 );

    }

    public function send_invitation_email( $user_id, $auth_url, $payment_id ){
        if( apply_filters( 'pms_mail_stop_emails', false ) )
            return;

        $user = get_userdata( $user_id );

        if( empty( $user->ID ) )
            return;

        $settings = get_option( 'pms_emails_settings', array() );

        if( !isset( $settings['stripe_authentication_is_enabled'] ) )
            return;

        if( !empty( $settings['stripe_authentication_sub_subject'] ) )
            $subject = $settings['stripe_authentication_sub_subject'];
        else
            $subject = $this->get_default_email_subject();

        if( !empty( $settings['stripe_authentication_sub'] ) )
            $content = $settings['stripe_authentication_sub'];
        else
            $content = $this->get_default_email_content();

        $payment = pms_get_payment( $payment_id );

        $extra_info = array(
            'stripe_auth_url' => $auth_url,
            'payment_amount'  => $payment->amount . pms_get_currency_symbol( pms_get_active_currency() )
        );

        if( !function_exists( 'pms_should_use_old_merge_tags' ) || pms_should_use_old_merge_tags() === true ){
            $subject = PMS_Merge_Tags::pms_process_merge_tags( $subject, $extra_info, $payment->subscription_id );
            $content = PMS_Merge_Tags::pms_process_merge_tags( $content, $extra_info, $payment->subscription_id );
        } else {
            $subject = PMS_Merge_Tags::process_merge_tags( $subject, $extra_info, $payment->subscription_id, $payment->id );
            $content = PMS_Merge_Tags::process_merge_tags( $content, $extra_info, $payment->subscription_id, $payment->id );
        }

        $content = wpautop( $content );
        $content = do_shortcode( $content );

        // Filter before sending
        $subject = apply_filters( 'pms_email_subject_user', $subject, 'stripe_authentication', $extra_info, $payment->subscription_id );
        $content = apply_filters( 'pms_email_content_user', $content, 'stripe_authentication', $extra_info, $payment->subscription_id );

        // Add filter to enable html encoding
        add_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Temporary change the from name and from email
        add_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20, 1 );
        add_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20, 1 );

            if( wp_mail( $user->user_email, $subject, $content ) )
                $payment->log_data( 'stripe_authentication_sent' );

        // Reset html encoding
        remove_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Reset the from name and email
        remove_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20 );
        remove_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20 );
    }

    public function add_auth_email_settings( $options ){
        ?>

        <div class="pms-heading-wrap">
            <h3><?php esc_html_e( 'Stripe Authentication Email', 'paid-member-subscriptions') ?></h3>

            <label for="stripe-authentication-is-enabled">
                <input type="checkbox" id="stripe-authentication-is-enabled" name="pms_emails_settings[stripe_authentication_is_enabled]" value="yes" <?php echo ( isset( $options['stripe_authentication_is_enabled'] ) ? 'checked' : '' ); ?> />

                <?php esc_html_e( 'Enable email', 'paid-member-subscriptions' ); ?>
            </label>
            </div>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="email-stripe-authentication-sub-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-stripe-authentication-sub-subject" class="widefat" name="pms_emails_settings[stripe_authentication_sub_subject]" value="<?php echo ( isset( $options['stripe_authentication_sub_subject'] ) ? esc_attr( $options['stripe_authentication_sub_subject'] ) : esc_attr( $this->get_default_email_subject() ) ) ?>">
            </div>

            <div class="pms-form-field-wrapper">
                <label class="pms-form-field-label" for="emails-stripe-authentication-sub"><?php esc_html_e( 'Content', 'paid-member-subscriptions' ) ?></label>
                <?php wp_editor( ( isset($options['stripe_authentication_sub']) ? $options['stripe_authentication_sub'] : $this->get_default_email_content() ), 'emails-stripe-authentication-sub', array( 'textarea_name' => 'pms_emails_settings[stripe_authentication_sub]', 'editor_height' => 250 ) ); ?>
            </div>

        <?php
    }

    public function add_extra_tags( $available_tags ){
        $extra_tags = apply_filters( 'pms_stripe_merge_tags', array( 'site_name', 'site_url', 'payment_amount', 'stripe_auth_link' ) );

        return array_merge( $available_tags, $extra_tags );
    }

    public function auth_link( $value, $extra_info ){
        if( is_array( $extra_info ) && !empty( $extra_info['stripe_auth_url'] ) )
            return sprintf( '<a href="%s" target="_blank">%s</a>', $extra_info['stripe_auth_url'], $extra_info['stripe_auth_url'] );

        return;
    }

    public function payment_amount( $value, $extra_info, $subscription_id, $payment_id ){

        if( is_array( $extra_info ) && !empty( $extra_info['payment_amount'] ) )
            return $extra_info['payment_amount'];
        elseif( !empty( $payment_id ) ){
            $payment = pms_get_payment( (int)$payment_id );

            return $payment->amount;
        }

        return;
    }

    private function get_default_email_subject(){
        return esc_html__( 'Payment Authentication required on {{site_name}}', 'paid-member-subscriptions' );
    }

    private function get_default_email_content(){
        return __( '<p>Hello {{display_name}},</p> <p>Payment Authentication is required in order to confirm the payment of <strong>{{subscription_price}}</strong> for the <strong>{{subscription_name}}</strong> subscription on <strong>{{site_name}}</strong>.</p> <p>Click on the following link in order to authenticate the payment: {{stripe_auth_link}}</p>', 'paid-member-subscriptions' );
    }
}

new PMS_Stripe_Emails;
