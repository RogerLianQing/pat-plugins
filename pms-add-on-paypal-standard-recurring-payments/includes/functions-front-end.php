<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Function that outputs the automatic renewal option in the front-end for the user/customer to see
 * This function was deprecated due to moving the functionality to the core of Paid Member Subscriptions
 *
 * @deprecated 1.2.0
 *
 */
if( !function_exists( 'pms_renewal_option_field' ) ) {

    function pms_renewal_option_field($output, $include, $exclude_id_group, $member, $pms_settings) {

        // Get all subscription plans
        if (empty($include))
            $subscription_plans = pms_get_subscription_plans();
        else {
            if (!is_object($include[0]))
                $subscription_plans = pms_get_subscription_plans(true, $include);
            else
                $subscription_plans = $include;
        }

        // Calculate the amount for all subscription plans
        $amount = 0;
        foreach ($subscription_plans as $subscription_plan) {
            $amount += $subscription_plan->price;
        }

        if (!$member && isset($pms_settings['payments']['recurring']) && $pms_settings['payments']['recurring'] == 1 && $amount != 0) {

            $output .= '<div class="pms-subscription-plan-auto-renew">';
            $output .= '<label><input name="pms_recurring" type="checkbox" value="1" ' . (isset($_REQUEST['pms_recurring']) ? 'checked="checked"' : '') . ' />' . apply_filters('pms_auto_renew_label', __('Automatically renew subscription', 'paid-member-subscriptions')) . '</label>';
            $output .= '</div>';

        }

        return $output;

    }
    //add_filter('pms_output_subscription_plans', 'pms_renewal_option_field', 20, 5);
}