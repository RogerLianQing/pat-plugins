<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin page
 */
?>

<div class="wrap">

    <h1>
        <?php echo $this->page_title; ?>

        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_subscription' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2"><?php echo __( 'Add New', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_new_members_bulk' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2"><?php echo __( 'Bulk Add New', 'paid-member-subscriptions' ); ?></a>
    </h1>
    <form method="get">
        <input type="hidden" name="page" value="pms-members-page" />
        <?php
            $this->list_table->prepare_items();
            $this->list_table->views();
            $this->list_table->search_box( __( 'Search Members', 'paid-member-subscriptions' ), 'pms_search_members' );
        ?>
        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">
                    <?php
                    $this->list_table->display();

                    wp_nonce_field( 'pms_bulk_delete_subscription_nonce' );
                    ?>
                </div>

                <div id="postbox-container-1" class="postbox-container filter-members-sidebox">
                    <?php $this->list_table->pagination( 'top' ); ?>
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox">

                            <!-- Meta-box Title -->
                            <h3>
									<span>
										<?php
                                        _e( 'Filter by', 'paid-member-subscriptions' );
                                        ?>
									</span>
                            </h3>

                            <div class="submitbox">
                                <div id="major-publishing-actions">
                                    <?php
                                    echo '<div style="display: inline-block;">';

                                        echo '<div class="pms-members-filter">';
                                        /*
                                         * Add a custom select box to filter the list by Subscription Plans
                                         *
                                         */
                                        $subscription_plans = pms_get_subscription_plans( false );

                                            echo '<select name="pms-filter-subscription-plan" class="pms-filter-select" id="pms-filter-subscription-plan">';
                                            echo '<option value="">' . __( 'Subscription Plan...', 'paid-member-subscriptions' ) . '</option>';

                                            foreach( $subscription_plans as $subscription_plan )
                                                echo '<option value="' . $subscription_plan->id . '" ' . ( !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, $_GET['pms-filter-subscription-plan'], false ) : '' ) . '>' . $subscription_plan->name . '</option>';
                                            echo '</select>';
                                        echo '</div>';

                                        /**
                                         * Action to add more filters
                                         */
                                        do_action( 'pms_members_list_extra_table_nav', 'top' );

                                        $payment_gateways = pms_get_payment_gateways();
                                        $payment_gateways_keys = array_keys( $payment_gateways );

                                        echo '<div class="pms-members-filter">';
                                            echo '<select name="pms-filter-payment-gateway" class="pms-filter-select" id="pms-filter-payment-gateway">';
                                                echo '<option value="">' . __( 'Payment Gateway...', 'paid-member-subscriptions' ) . '</option>';
                                                $i = 0;
                                                foreach( $payment_gateways as $payment_gateway ){
                                                    if( isset( $payment_gateway[ 'display_name_admin' ] ) )
                                                        echo '<option value="' . $payment_gateways_keys[ $i ] . '" ' . ( !empty( $_GET['pms-filter-payment-gateway'] ) ? selected( $payment_gateways_keys[ $i ], $_GET['pms-filter-payment-gateway'], false ) : '' ) . '>' . $payment_gateway[ 'display_name_admin' ] . '</option>';
                                                    $i++;
                                                }
                                            echo '</select>';
                                        echo '</div>';

                                        echo '<div class="pms-members-filter">';
                                            echo '<select name="pms-filter-start-date" class="pms-filter-select" id="pms-filter-start-date">';
                                                echo '<option value="">' . __( 'Start Date...', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_week" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_week", $_GET['pms-filter-start-date'], false ) : '' ) . '>' . __( 'Last 7 Days', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_month" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_month", $_GET['pms-filter-start-date'], false ) : '' ) . '>' . __( 'Last 30 Days', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_year" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_year", $_GET['pms-filter-start-date'], false ) : '' ) . '>' . __( 'Last Year', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="custom" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "custom", $_GET['pms-filter-start-date'], false ) : '' ) . '>' . __( 'Custom', 'paid-member-subscriptions' ) . '</option>';
                                            echo '</select>';
                                        echo '</div>';
                                        echo '<div class="pms-custom-interval" id="pms-start-date-interval">';
                                            echo '<label id="pms-label-start-date-beginning" for="pms-datepicker-start-date-beginning">' . __( 'Start of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-start-date-beginning" type="text" name="pms-datepicker-start-date-beginning" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-start-date-beginning'] ) ? $_GET['pms-datepicker-start-date-beginning'] : '' ) . '">';

                                            echo '<label id="pms-label-start-date-end" for="pms-datepicker-start-date-end">' . __( 'End of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-start-date-end" type="text" name="pms-datepicker-start-date-end" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-start-date-end'] ) ? $_GET['pms-datepicker-start-date-end'] : '' ) . '">';
                                        echo '</div>';


                                        echo '<div class="pms-members-filter">';
                                            echo '<select name="pms-filter-expiration-date" class="pms-filter-select" id="pms-filter-expiration-date">';
                                                echo '<option value="">' . __( 'Expiration Date...', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="today" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "today", $_GET['pms-filter-expiration-date'], false ) : '' ) . '>' . __( 'Today', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="tomorrow" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "tomorrow", $_GET['pms-filter-expiration-date'], false ) : '' ) . '>' . __( 'Tomorrow', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="this_week" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_week", $_GET['pms-filter-expiration-date'], false ) : '' ) . '>' . __( 'This Week', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="this_month" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_month", $_GET['pms-filter-expiration-date'], false ) : '' ) . '>' . __( 'This Month', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="custom" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "custom", $_GET['pms-filter-expiration-date'], false ) : '' ) . '>' . __( 'Custom', 'paid-member-subscriptions' ) . '</option>';
                                            echo '</select>';
                                        echo '</div>';
                                        echo '<div class="pms-custom-interval" id="pms-expiration-date-interval">';
                                            echo '<label id="pms-label-expiration-date-beginning" for="pms-datepicker-expiration-date-beginning">' . __( 'Start of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-expiration-date-beginning" type="text" name="pms-datepicker-expiration-date-beginning" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? $_GET['pms-datepicker-expiration-date-beginning'] : '' ) . '" ' . ( !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? $_GET['pms-datepicker-expiration-date-beginning'] : '' ) . '>';

                                            echo '<label id="pms-label-expiration-date-end" for="pms-datepicker-expiration-date-end">' . __( 'End of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-expiration-date-end" type="text" name="pms-datepicker-expiration-date-end" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-expiration-date-end'] ) ? $_GET['pms-datepicker-expiration-date-end'] : '' ) . '">';
                                        echo '</div>';
                                        /*
                                         * Filter button
                                         *
                                         */
                                        echo '<input class="button button-secondary" id="pms-filter-button" type="submit" value="' . __( 'Filter', 'paid-member-subscriptions' ) . '" />';

                                    echo '</div>';
                                    ?>

                                    <div class="clear"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
