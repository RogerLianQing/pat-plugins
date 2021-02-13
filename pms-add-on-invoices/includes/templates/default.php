<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

/**
 * The default invoice template
 *
 *
 */
function pms_inv_invoice_template_default( $pdf_invoice, $payment ) {

	/**
     * General settings
     *
     */
	$defaults = array(
	    'format'             => '{{number}}',
        'company_details' => __('Please fill your company details in Paid Member Subscriptions -> Settings -> Invoices', 'paid-member-subscriptions'),
        'title'           => __('Invoice', 'paid-member-subscriptions'),
        'logo'            => '',
        'font'            => 'dejavusans',
    );
    $settings = wp_parse_args( get_option( 'pms_invoices_settings', array() ), $defaults );

    /**
     * Subscription plan
     *
     */
    $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

	/**
     * Invoice data
     *
     */
    $invoice_number          = pms_inv_parse_payment_invoice_tags( ( ! empty( $settings['format'] ) ? $settings['format'] : '{{number}}' ), $payment );
    $invoice_billing_details = pms_inv_get_invoice_billing_details( $payment->id );

    /**
     * Payment gateway
     *
     */
    $payment_gateways = pms_get_payment_gateways();
    $payment_gateway  = ( ! empty( $payment_gateways[$payment->payment_gateway]['display_name_admin'] ) ? $payment_gateways[$payment->payment_gateway]['display_name_admin'] : '' );

    /**
     * Provider company details
     *
     */

    // Retrieve saved company details from the payment
    $payment_company_details = pms_get_payment_meta( $payment->id, 'pms_billing_settings_company_details', true );

    if( apply_filters( 'pms_inv_use_payment_company_details', true, $payment ) )
        $company_details = !empty( $payment_company_details ) ? wpautop( $payment_company_details ) : ( !empty( $settings['company_details'] ) ? wpautop( $settings['company_details'] ) : '' );
    else
        $company_details = ( ! empty( $settings['company_details'] ) ? wpautop( $settings['company_details'] ) : '' );

    /**
     * Client billing details
     *
     */
    $billing_details = '';

    if( ! empty( $invoice_billing_details ) ) {

        // Company
        if( ! empty( $invoice_billing_details['pms_billing_company'] ) ) {

            $billing_details .= $invoice_billing_details['pms_billing_company'];

        // First name and last name
        } else {

            $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_first_name'] ) ? $invoice_billing_details['pms_billing_first_name'] : '' ) . ' ';
            $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_last_name'] ) ? $invoice_billing_details['pms_billing_last_name'] : '' );

        }

        // Complete address string
        $billing_address = '';

        // Address
        if( ! empty( $invoice_billing_details['pms_billing_address'] ) ) {

            $billing_address .= $invoice_billing_details['pms_billing_address'];

        }

        // Zip code
        if( ! empty( $invoice_billing_details['pms_billing_zip'] ) ) {

            $billing_address .= ', ' . $invoice_billing_details['pms_billing_zip'];

        }

        // City
        if( ! empty( $invoice_billing_details['pms_billing_city'] ) ) {

            $billing_address .= ', ' . $invoice_billing_details['pms_billing_city'];

        }

        // Complete billing address string with new line
        $billing_details .= PHP_EOL . PHP_EOL . $billing_address;

        // Billing country
        if( ! empty( $invoice_billing_details['pms_billing_country'] ) ) {

            $countries = pms_get_countries();

            if( ! empty( $countries[$invoice_billing_details['pms_billing_country']] ) )
                $billing_details .= PHP_EOL . PHP_EOL . $countries[$invoice_billing_details['pms_billing_country']];

        }

        // Email
        $billing_details .= PHP_EOL . PHP_EOL . ( ! empty( $invoice_billing_details['pms_billing_email'] ) ? __( 'E-mail: ', 'paid-member-subscriptions' ) . $invoice_billing_details['pms_billing_email'] : '' );

        // Autop it
        $billing_details = wpautop( $billing_details );

    }


    /**
     * Start building the PDF invoice
     *
     */
    $font = apply_filters( 'pms_inv_invoice_template_default_font_family', $settings['font'] );

    $pdf_invoice->SetMargins( 8, 8, 8 );
    $pdf_invoice->SetX( 8 );

    $pdf_invoice->AddPage();


   	// Page title
    $pdf_invoice->SetFont( $font, '', 22 );
    $pdf_invoice->Cell( 0, 0, ( ! empty( $settings['title'] ) ? pms_inv_parse_payment_invoice_tags( $settings['title'], $payment ) : __( 'Invoice', 'paid-member-subscriptions' ) ), 0, 2, 'L', false );

    //Logo
    $logo = wp_get_attachment_url( $settings['logo'] );

    if( !empty( $logo ) ){
        $pdf_invoice->Image( $logo, 10, 10, 40, 40, '', '', 'T', false, 300, 'R', false, false, 0, true, false, false);
        $pdf_invoice->Ln( 7 );
    }

    $pdf_invoice->Ln( 7 );

    // Set default font
    $pdf_invoice->SetFont( $font, '', 10 );

    // Invoice number
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Invoice number: %s', 'paid-member-subscriptions' ), $invoice_number ), 0, 2, 'L', false );

    // Payment ID
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment ID: %s', 'paid-member-subscriptions' ), $payment->id ), 0, 2, 'L', false );

    // Payment date
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment date: %s', 'paid-member-subscriptions' ), date( 'Y-m-d', strtotime( $payment->date ) ) ), 0, 2, 'L', false );

    // Payment status
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment status: %s', 'paid-member-subscriptions' ), ucfirst( $payment->status ) ), 0, 2, 'L', false );

    // Payment gateway
    if( ! empty( $payment_gateway ) )
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment gateway: %s', 'paid-member-subscriptions' ), $payment_gateway ), 0, 2, 'L', false );

    $pdf_invoice->Ln( 10 );

    // Add a line
    $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );

    $pdf_invoice->Ln( 6 );

    // Set font for the headings
    $pdf_invoice->SetFont( $font, 'B', 12 );

    // Set Provided By Heading
    $provider_heading_y = $pdf_invoice->getY();
    $pdf_invoice->Cell( ( $pdf_invoice->getPageWidth() - 16 ) / 2, 12, __( 'Provided by:', 'paid-member-subscriptions' ), 0, 2, 'L', false );

    // Set Provided To Heading
    $pdf_invoice->SetXY( $pdf_invoice->getPageWidth() / 2, $provider_heading_y );
    $pdf_invoice->Cell( ( $pdf_invoice->getPageWidth() - 16 ) / 2, 12, __( 'Provided to:', 'paid-member-subscriptions' ), 0, 2, 'R', false );

    // Reset X
    $pdf_invoice->SetX( 8 );

    // Set font
    $pdf_invoice->SetFont( $font, '', 10 );

    // Add Provided By details
    $pdf_invoice->writeHTMLCell( ( $pdf_invoice->getPageWidth() - 16 ) / 2 , 50, '', '', $company_details );

    // Add Provided To details
    $pdf_invoice->writeHTMLCell( ( $pdf_invoice->getPageWidth() - 16 ) / 2 , 50, '', '', $billing_details, 0, 2, false, true, 'R' );

    // Add a line and some space
    $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
    $pdf_invoice->Ln( 10 );

    /**
     * The subscription heading
     *
     */
    $subscription_heading_y = $pdf_invoice->getY();

    // Set default font
    $pdf_invoice->SetFont( $font, 'B', 10 );

    // Reset position
    $pdf_invoice->SetXY( 8, $subscription_heading_y );

    // Payment subscription plan name heading
    $pdf_invoice->Cell( 0, 6, __( 'Subscription Plan', 'paid-member-subscriptions' ), 0, 2, 'L', false );

    // Reset position to
    $pdf_invoice->SetXY( ( $pdf_invoice->getPageWidth() - $pdf_invoice->getPageWidth() / 4 - 16 ), $subscription_heading_y );

    // Payment amount
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Amount (%s)', 'paid-member-subscriptions' ), pms_get_active_currency() ), 0, 2, 'R', false );


    /**
     * The subscription name and payment amount
     *
     */
    // Set default font
    $pdf_invoice->SetFont( $font, '', 10 );

    // Add a line
    $pdf_invoice->Ln( 1 );
    $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
    $pdf_invoice->Ln( 1 );

    // Reset position
    $subscription_y = $pdf_invoice->getY();
    $pdf_invoice->SetXY( 8, $subscription_y );

    // Payment subscription plan name heading
    $pdf_invoice->Cell( 0, 6, $subscription_plan->name, 0, 2, 'L', false );

    // Reset position to
    $pdf_invoice->SetXY( ( $pdf_invoice->getPageWidth() - $pdf_invoice->getPageWidth() / 4 - 16 ), $subscription_y );

    // Payment amount
    $pdf_invoice->Cell( 0, 6, pms_format_price( $payment->amount ), 0, 2, 'R', false );

    // Add a line
    $pdf_invoice->Ln( 1 );
    $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
    $pdf_invoice->Ln( 1 );


    /**
     * Invoice total amount
     *
     */
    $pdf_invoice->Cell( 0, 6, sprintf( __( 'Total (%s): %s', 'paid-member-subscriptions' ), pms_get_active_currency(), pms_format_price( $payment->amount ) ), 0, 2, 'R', false );

}
add_action( 'pms_inv_invoice_template_default', 'pms_inv_invoice_template_default', 10, 2 );
