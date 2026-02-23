<?php

namespace GiveP2P\P2P\Admin\Settings;

use Give\Framework\FieldsAPI\Option;
use GiveP2P\P2P\Admin\Contracts\AdminPageSettings;
use GiveP2P\P2P\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Options;

/**
 * Sponsor Fields Settings
 *
 * @package GiveP2P\P2P\Admin\Settings
 *
 * @since 1.0.0
 */
class Sponsor extends AdminPageSettings {

	/**
	 * @inheritDoc
	 */
	public function getFields() {
		return [
			Field::radio( 'sponsors_enabled' )
			     ->label( __( 'Sponsors', 'give-peer-to-peer' ) )
			     ->helpText( __( 'If enabled, this adds a section to the campaign page to display sponsors.', 'give-peer-to-peer' ) )
			     ->defaultValue( Options::DISABLED )
			     ->options(
				     Option::make( Options::ENABLED, __( 'Enabled', 'give-peer-to-peer' ) ),
				     Option::make( Options::DISABLED, __( 'Disabled', 'give-peer-to-peer' ) )
			     ),

			Field::radio( 'sponsor_linking' )
			     ->label( __( 'Sponsor Linking', 'give-peer-to-peer' ) )
			     ->helpText( __( 'This controls whether or not the sponsor images link to their websites. Linking with follow is better for their SEO but may hurt your SEO, linking with no follow and sponsored attributes has no effect on SEO, disabling linking will simply display the sponsor’s image.', 'give-peer-to-peer' ) )
			     ->showIf( 'sponsors_enabled', '=', Options::ENABLED )
			     ->defaultValue( Options::NOFOLLOW )
			     ->options(
				     Option::make( Options::FOLLOW, __( 'Link with Follow Attribute', 'give-peer-to-peer' ) ),
				     Option::make( Options::NOFOLLOW, __( 'Link with No Follow and Sponsored Attribute', 'give-peer-to-peer' ) ),
				     Option::make( Options::DISABLED, __( 'Disable Linking', 'give-peer-to-peer' ) )
			     ),

			Field::text( 'sponsor_section_heading' )
			     ->label( __( 'Sponsors Section Heading', 'give-peer-to-peer' ) )
			     ->helpText( __( 'The sponsors heading displays above the list of sponsors.', 'give-peer-to-peer' ) )
			     ->defaultValue( __( 'Our Wonderful Sponsors', 'give-peer-to-peer' ) )
			     ->showIf( 'sponsors_enabled', '=', Options::ENABLED ),

			Field::text( 'sponsor_application_page' )
			     ->label( __( 'Sponsor Application Page', 'give-peer-to-peer' ) )
			     ->helpText( __( "If you provide a fully qualified URL (including the <code>https://</code>) here, it displays a button in the Sponsors section to allow new sponsors to contact you. Link to a page with a contact form on it. To disable display of that button, leave this field blank", 'give-peer-to-peer' ) )
			     ->placeholder( home_url() )
			     ->showIf( 'sponsors_enabled', '=', Options::ENABLED ),

			Field::radio( 'sponsors_display' )
			     ->label( __( 'Sponsors Display', 'give-peer-to-peer' ) )
			     ->helpText( __( 'You can either display the sponsors section only on the main campaign page, or also on all team pages and fundraiser pages', 'give-peer-to-peer' ) )
				 ->showIf( 'sponsors_enabled', '=', Options::ENABLED )
			     ->defaultValue( Options::ALL )
			     ->options(
				     Option::make( Options::ALL, __( 'All Campaign Pages', 'give-peer-to-peer' ) ),
				     Option::make( Options::MAIN, __( 'Only Main Campaign Page', 'give-peer-to-peer' ) )
			     ),

			Field::repeater( 'sponsors' )
			     ->label( __( 'Sponsors', 'give-peer-to-peer' ) )
			     ->showIf( 'sponsors_enabled', '=', Options::ENABLED )
			     ->repeaterBlockTitle( __( 'Sponsor', 'give-peer-to-peer' ) )
			     ->repeaterButtonText( __( 'Add Sponsor', 'give-peer-to-peer' ) )
			     ->options(
				     Field::text( 'sponsor_name' )
				          ->label( __( 'Sponsor Name', 'give-peer-to-peer' ) )
				          ->helpText( __( 'The name of the sponsor is used for alt and title text.', 'give-peer-to-peer' ) ),

				     Field::text( 'sponsor_url' )
				          ->label( __( 'Sponsor URL', 'give-peer-to-peer' ) )
				          ->helpText( __( 'The name of the sponsor is used for alt and title text.', 'give-peer-to-peer' ) ),

				     Field::image( 'sponsor_image' )
				          ->label( __( 'Sponsor Image', 'give-peer-to-peer' ) )
				          ->helpText( __( 'The sponsor image should be at least 150x100 for optimal quality.', 'give-peer-to-peer' ) )
			     ),
		];
	}

}
