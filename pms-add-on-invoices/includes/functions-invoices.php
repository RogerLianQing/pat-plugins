<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;


/**
 * Register the general invoice number setting option
 *
 */
function pms_inv_register_setting_invoice_number() {

    // Register the setting only if there's a value present.
    // If no value is present we don't want the update_options to empty our existing value
    if( ! empty( $_POST['pms_inv_invoice_number'] ) )
        register_setting( 'pms_settings', 'pms_inv_invoice_number' );

}
add_action( 'admin_init', 'pms_inv_register_setting_invoice_number' );


/**
 * Returns the URL which, when accessed, will generate the downloadable PDF invoice file
 *
 * @param int $payment_id   - the payment for which to generate the PDF invoice file
 *
 * @return string
 *
 */
function pms_inv_get_generate_invoice_pdf_link( $payment_id = 0 ) {

    if( empty( $payment_id ) )
        return '';

    $args = array(
        'pms-action' => 'generate_invoice_pdf',
        'payment_id' => (int)$payment_id,
        'pmstkn'     => wp_create_nonce( 'pms_inv_generate_invoice_pdf' )
    );

    $link = add_query_arg( $args, get_home_url() );

    return $link;

}


/**
 * Verifies if the payment is eligible to have a generate invoice link
 *
 * @param int $payment_id   - the payment for which to check the eligibility
 *
 * @return bool
 *
 */
function pms_inv_is_invoice_allowed( $payment_id = 0 ) {

    $return = true;

    if( empty( $payment_id ) ) {

        $return = false;

    } else {

        // Get plugin settings
        $settings = get_option( 'pms_invoices_settings', array() );

        if( empty( $settings['company_details'] ) ) {

            $return = false;

        } else {

            // Check to see if the payment has an invoice number
            $invoice_number = pms_get_payment_meta( $payment_id, 'pms_inv_invoice_number', true );

            if( empty( $invoice_number ) ) {

                $return = false;

            } else {

                // Check if payment is completed
                $payment = pms_get_payment( $payment_id );

                if( empty( $payment->amount ) ){

                    $return = false;

                } else if( ( !isset( $settings['pre_generate_invoices'] ) || $settings['pre_generate_invoices'] != '1' ) && $payment->status != 'completed' ) {

                    $return = false;

                }

            }

        }

    }

    /**
     * Filter the return value
     *
     * @param bool $return
     * @param int  $payment_id
     *
     */
    return apply_filters( 'pms_inv_is_invoice_allowed', $return, $payment_id );

}


/**
 * Verifies if the invoices should be available for users in the front-end
 *
 * @return bool
 *
 */
function pms_inv_are_invoices_available_for_users() {

    return apply_filters( 'pms_inv_are_invoices_available_for_users', true );

}


/**
 * Listenes for the generate PDF invoice action and generates link if all is good
 *
 */
function pms_inv_generate_invoice_pdf_listener() {

    if( empty( $_GET['pms-action'] ) || $_GET['pms-action'] != 'generate_invoice_pdf' )
        return;

    if( empty( $_GET['payment_id'] ) )
        return;

    if( !isset( $_GET['pmskey'] ) ){

        if( empty( $_GET['pmstkn'] ) || ! wp_verify_nonce( $_GET['pmstkn'], 'pms_inv_generate_invoice_pdf' ) )
            return;

        if( ! is_user_logged_in() )
            return;

    } else {

        $payment     = pms_get_payment( (int)$_GET['payment_id'] );
        $payment_key = pms_inv_get_url_access_key( $payment );

        if( md5( $payment_key ) !== md5( sanitize_text_field( $_GET['pmskey'] ) ) )
            return;
    }

    if( ! pms_inv_is_invoice_allowed( (int)$_GET['payment_id'] ) )
        return;

    // Include PDF generator
    if( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'libs/tcpdf/tcpdf.php' ) )
        include_once PMS_INV_PLUGIN_DIR_PATH . 'libs/tcpdf/tcpdf.php';

    // Include our PDF invoice generator wrapper
    if( file_exists( PMS_INV_PLUGIN_DIR_PATH . 'includes/class-pms-pdf-invoice.php' ) )
        include_once PMS_INV_PLUGIN_DIR_PATH . 'includes/class-pms-pdf-invoice.php';

    /**
     * General settings
     *
     */
    $settings = get_option( 'pms_invoices_settings', array() );

    /**
     * Payment
     *
     */
    $payment = pms_get_payment( (int)$_GET['payment_id'] );

    /**
     * Invoice number
     *
     */
    $invoice_number = pms_get_payment_meta( $payment->id, 'pms_inv_invoice_number', true );

    /**
     * Prepare the PDF invoice object
     *
     */
    $pdf_invoice = new PMS_PDF_Invoice( 'P', 'mm', 'A4', true, 'UTF-8', false );
    $pdf_invoice->SetDisplayMode( 'real' );
    $pdf_invoice->setJPEGQuality( 100 );

    $pdf_invoice->SetTitle( apply_filters( 'pms_inv_invoice_title', ( ! empty( $settings['title'] ) ? $settings['title'] : __( 'Invoice', 'paid-member-subscriptions' ) ), $invoice_number, $payment->id ) );
    $pdf_invoice->SetCreator( apply_filters( 'pms_inv_invoice_creator', 'Paid Member Subscriptions' ) );
    $pdf_invoice->SetAuthor( apply_filters( 'pms_inv_invoice_author', get_option( 'blogname' ) ) );

    /**
     * Filter the template to be used
     *
     * @param string
     *
     */
    $template = apply_filters( 'pms_inv_invoice_template', 'default' );

    /**
     * Action to execute the appropiate template callback
     *
     * @param PMS_PDF_Invoice $pdf_invoice
     * @param array           $payment
     *
     */
    do_action( 'pms_inv_invoice_template_' . $template, $pdf_invoice, $payment );


    if( ob_get_length() ) {
        ob_end_clean();
    }


    $pdf_invoice->Output( apply_filters( 'pms_inv_invoice_filename_prefix', sprintf( esc_html__( 'Invoice-%d', 'paid-member-subscriptions' ), $invoice_number ), $invoice_number ) . '.pdf', apply_filters( 'pms_inv_invoice_destination', 'I' ) );

    die();

}
add_action( 'init', 'pms_inv_generate_invoice_pdf_listener' );


/**
 * Returns an array with the billing details for the invoice
 *
 * @param int $payment_id
 *
 * @return array
 *
 */
function pms_inv_get_invoice_billing_details( $payment_id = 0 ) {

    if( empty( $payment_id ) )
        return array();

    $invoice_fields = pms_inv_get_invoice_fields();

    if( empty( $invoice_fields ) )
        return array();

    // Get payment
    $payment = pms_get_payment( $payment_id );

    // Billing details array
    $invoice_billing_details = array();

    /**
     * Get all invoice details saved for the payment
     *
     */
    foreach( $invoice_fields as $field ) {

        if( empty( $field['name'] ) )
            continue;

        $invoice_billing_details[$field['name']] = pms_get_payment_meta( $payment_id, $field['name'], true );

    }

    // Clear empty values
    $invoice_billing_details = array_filter( $invoice_billing_details );

    /**
     * Get all invoice details saved for the user, if the payment doesn't have any
     *
     */
    if( empty( $invoice_billing_details ) ) {

        foreach( $invoice_fields as $field ) {

            if( empty( $field['name'] ) )
                continue;

            $invoice_billing_details[$field['name']] = get_user_meta( $payment->user_id, $field['name'], true );

        }

    }

    // Clear empty values
    $invoice_billing_details = array_filter( $invoice_billing_details );

    /**
     * If we still have no invoice details fallback to data from the user
     *
     */
    if( empty( $invoice_billing_details ) ) {

        $user = get_user_by( 'id', $payment->user_id );

        // Set billing first name and last name if they exist, if not set the first name to be
        // the username
        if( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {

            $invoice_billing_details['pms_billing_first_name'] = $user->first_name;
            $invoice_billing_details['pms_billing_last_name']  = $user->last_name;

        } else {

            $invoice_billing_details['pms_billing_first_name'] = $user->user_login;

        }

        // Set billing email
        $invoice_billing_details['pms_billing_email'] = $user->user_email;

    }

    // Return
    return $invoice_billing_details;

}


/**
 * Helper function to replace invoice specific tags with the appropiate values
 *
 * @param string      $format    - the text containing the tags, that we want to modify
 * @param PMS_Payment $payment   - the payment for which to change the tags
 *
 * @return string
 *
 */
function pms_inv_parse_payment_invoice_tags( $format = '', $payment = null ) {

    if( is_null( $payment ) )
        return '';

    // Get payment invoice number
    $invoice_number = pms_get_payment_meta( $payment->id, 'pms_inv_invoice_number', true );

    // Tags to search for in the string replace
    $search = array(
        '{{number}}',
        '{{MM}}',
        '{{YYYY}}'
    );

    // Replace tags with the following values
    $replace = array(
        $invoice_number,
        date( 'm', strtotime( $payment->date ) ),
        date( 'Y', strtotime( $payment->date ) )
    );

    // Replace tags with values
    $formatted = str_replace( $search, $replace, $format );

    return $formatted;

}


/**
 * Add "Invoice" column to user's payment history table
 *
 */
function pms_inv_add_payment_history_header() {

    if( ! pms_inv_are_invoices_available_for_users() )
        return;

    echo '<th class="pms-payment-invoice">' . __( 'Invoice', 'paid-member-subscriptions' ) . '</th>';

}
add_action( 'pms_payment_history_table_header', 'pms_inv_add_payment_history_header' );


/**
 * Add invoice "Download" link for each completed payment under user's payment history table
 *
 * @param int    $user_id
 * @param object $payment
 *
 */
function pms_inv_add_payment_history_column( $user_id, $payment ) {

    if( ! pms_inv_are_invoices_available_for_users() )
        return;

    // Get generate PDF invoice link
    if( pms_inv_is_invoice_allowed( $payment->id ) )
        $invoice_link = pms_inv_get_generate_invoice_pdf_link( $payment->id );
    else
        $invoice_link = '';

    if( ! empty( $invoice_link ) ) {
       echo '<td class="pms-payment-invoice"><a target="_blank" href="' . $invoice_link . '">' . __( 'Download Invoice', 'paid-member-subscriptions' ) . '</a></td>';

    } else
        echo '<td class="pms-payment-invoice"></td>' ;

}
add_action( 'pms_payment_history_table_row', 'pms_inv_add_payment_history_column', 20, 2 );


/**
 * Adds the invoice link in the admin payments table for payments that are eligible
 *
 * @param array $actions
 * @param array $item
 *
 * @return array
 *
 */
function pms_inv_payments_list_table_entry_actions_invoice( $actions = array(), $item = array() ) {

    // Get generate PDF invoice link
    if( pms_inv_is_invoice_allowed( $item['id'] ) )
        $invoice_link = pms_inv_get_generate_invoice_pdf_link( $item['id'] );
    else
        $invoice_link = '';

    if( ! empty( $invoice_link ) ) {

        // Cache the delete action, delete it and add it again after the download invoice action
        $delete_action = $actions['delete'];
        unset( $actions['delete'] );

        // Add the download invoice action
        $actions['download_invoice'] = '<a target="_blank" href="' . $invoice_link . '">' . __( 'Download Invoice', 'paid-member-subscriptions' ) . '</a>';

        // Add back the delete action
        $actions['delete'] = $delete_action;

    }

    return $actions;

}
add_filter( 'pms_payments_list_table_entry_actions', 'pms_inv_payments_list_table_entry_actions_invoice', 10, 2 );


/**
 * Adds the invoice link in the payments list table from the Members -> Edit Member screen
 *
 */
function pms_inv_add_invoice_link_to_member_payments_list( $actions, $item ){

    if( pms_inv_is_invoice_allowed( $item['actions'] ) )
        $invoice_link = pms_inv_get_generate_invoice_pdf_link( $item['actions'] );
    else
        $invoice_link = '';

    if( !empty( $invoice_link ) )
        $actions = $actions . '<a target="_blank" href="' . $invoice_link . '">' . __( 'Invoice', 'paid-member-subscriptions' ) . '</a>';

    return $actions;

}
add_filter( 'pms_member_payments_list_table_actions', 'pms_inv_add_invoice_link_to_member_payments_list', 20, 2 );

/**
 * Changes the name of the Payments tab from the [pms-account] shortcode
 *
 */
function pms_inv_change_account_tab_name( $tabs, $args ) {
    $tabs['payments'] = __( 'Payments & Invoices', 'paid-member-subscriptions' );

    return $tabs;
}
add_filter( 'pms_member_account_tabs', 'pms_inv_change_account_tab_name', 20, 2 );

function pms_inv_check_if_payment_meta_exists( $meta_key, $meta_value ){
    global $wpdb;

    $result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pms_paymentmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );

    if( !empty( $result ) )
        return $result;

    return false;
}
