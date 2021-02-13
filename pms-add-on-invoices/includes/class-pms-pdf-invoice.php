<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

Class PMS_PDF_Invoice extends TCPDF {

	/**
	 * Header
	 *
	 * Overwrites the default header of the parent class
	 *
	 */
	public function Header() {

	}

	/**
	 * Footer
	 *
	 * Outputs the footer notes message configured in the Settings on all invoices
	 *
	 */
	public function Footer() {

        $settings = apply_filters( 'pms_inv_template_footer_settings', get_option( 'pms_invoices_settings', array() ) );

        if( ! empty( $settings['notes'] ) ) {

            $font = apply_filters( 'pms_inv_invoice_template_default_font_family', !empty( $settings['font'] ) ? $settings['font'] : 'dejavusans' );

            $this->SetFont( $font, '', 22 );

            $width = $this->getPageWidth() - 16;

            //calculate order notes line height
            $footer_lines = $this->getNumLines( wpautop( $settings['notes'] ), $width );

            //maximum amount of 20 lines
            if( $footer_lines > 20 )
            $footer_lines = 20;

            //each line needs 4 units in order to be displayed; we start from -4 to target the bottom first line
            //then multiply each line with the amount of space it needs and add it to Y
            $y = 4 + ( $footer_lines * 4 );

            $this->SetY( -$y );
            $this->SetFontSize( 10 );
            $this->writeHTMLCell( $width , 15, '', '', wpautop( $settings['notes'] ) );

        }
	}

}
