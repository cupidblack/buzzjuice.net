<?php

namespace GiveP2P\P2P\Admin\Settings;

use Give\Framework\FieldsAPI\Option;
use GiveP2P\P2P\Admin\Contracts\AdminPageSettings;
use GiveP2P\P2P\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Options;
use GiveP2P\P2P\Repositories\FormRepository;

/**
 * Campaign Fields Settings
 *
 * @package GiveP2P\P2P\Admin\Settings
 *
 * @since 1.0.0
 */
class Campaign extends AdminPageSettings {

    /**
     * @inheritDoc
     */
    public function getFields() {
        return [
            Field::virtual( 'campaign_title' )
                 ->label( __( 'Campaign Title', 'give-peer-to-peer' ) )
                 ->required(),
            Field::virtual( 'status' )
                 ->label( __( 'Campaign status', 'give-peer-to-peer' ) ),

            Field::virtual( 'campaign_url' )
                 ->label( __( 'Campaign URL', 'give-peer-to-peer' ) ),

            Field::radio( 'form_new' )
                 ->label( __( 'Donation Form', 'give-peer-to-peer' ) )
                 ->helpText( __( 'All revenue from your campaign\'s fundraising is associated with a donation form. You can choose an existing donation form or create a new one for your campaign.', 'give-peer-to-peer' ) )
                 ->options(
                     Option::make( Options::ENABLED, __( 'Create a new donation form', 'give-peer-to-peer' ) ),
                     Option::make( Options::DISABLED, __( 'Use an existing donation form', 'give-peer-to-peer' ) )
                 )
                 ->defaultValue( Options::DISABLED ),

            Field::select( 'form_id' )
                 ->label( __( 'Donation Form', 'give-peer-to-peer' ) )
                 ->options( ...$this->getDonationForms() )
                 ->helpText( __( 'All revenue from your campaign\'s fundraising is associated with a donation form. You can choose an existing donation form or create a new one for your campaign.', 'give-peer-to-peer' ) )
                 ->renderAfter( [ $this, 'renderEditFormLink' ] )
                 ->showIf( 'form_new', '=', Options::DISABLED ),

            Field::radio( 'registration_digest' )
                 ->label( __( 'Registration Notifications', 'give-peer-to-peer' ) )
                 ->helpText( __( 'When a new fundraiser or team is registered an email will be sent to notify the site administrator.', 'give-peer-to-peer' ) )
                 ->defaultValue( Options::DISABLED )
                 ->options(
                     Option::make( Options::ENABLED, __( 'Send one daily email for new registrations.', 'give-peer-to-peer' ) ),
                     Option::make( Options::DISABLED, __( 'Send a separate email for each new registration.', 'give-peer-to-peer' ) )
                 ),

            Field::textarea( 'short_desc' )
                 ->label( __( 'Short Description', 'give-peer-to-peer' ) )
                 ->helpText( __( 'This displays at the top of the main campaign page below the Campaign Name. It should describe your fundraising campaign, and invite visitors to join, start a team, donate, or share the campaign. Keep the description to one or two sentences.', 'give-peer-to-peer' ) )
                 ->defaultValue( __( 'Join a fundraising team, create your own, or donate to help fund this campaign.', 'give-peer-to-peer' ) ),

            Field::editor( 'long_desc' )
                 ->label( __( 'Long Description', 'give-peer-to-peer' ) )
                 ->options( [ 'editor_height', 250 ], ['wpautop', true] )
                 ->defaultValue( sprintf( __( '<h2>Help Support our Cause</h2> <p>Join our fundraiser to help %1$s raise important funds to further our mission!</p> <!--more--> <p>Whether you make a donation, start a team, or simply support us by sharing the campaign, your help goes a long way and makes a difference.</p>', 'give-peer-to-peer'
                 ), get_bloginfo( 'sitename' ) ) )
                 ->helpText( __( 'A long form description of your campaign providing details to potential donors and fundraisers. In this space you can add images, text, and media files to help motivate visitors to support or join the campaign.', 'give-peer-to-peer' ) ),

            Field::image( 'campaign_logo' )
                 ->label( __( 'Campaign Logo', 'give-peer-to-peer' ) )
                 ->helpText( __( 'The Campaign logo displays above the campaign title on all pages and should be at least 200x100px for optimal quality.', 'give-peer-to-peer' ) ),

            Field::image( 'campaign_image' )
                 ->label( __( 'Campaign Image', 'give-peer-to-peer' ) )
                 ->helpText( __( 'The Campaign Image displays on all pages and should be at least 1200x800px for optimal quality', 'give-peer-to-peer' ) ),

            Field::color( 'primary_color' )
                 ->label( __( 'Primary Color', 'give-peer-to-peer' ) )
                 ->helpText( __( 'This color is used in buttons and overlays. Select a color that compliments the color scheme of your site in general.', 'give-peer-to-peer' ) )
                 ->defaultValue( '#28C77B' ),

            Field::money( 'campaign_goal' )
                 ->label( __( 'Campaign Fundraising Goal', 'give-peer-to-peer' ) )
                 ->helpText( __( 'How much would you like the overall goal for this campaign to be? This is the goal amount that all teams and fundraisers will be working towards.', 'give-peer-to-peer' ) )
                 ->defaultValue( 10000000 ),

            Field::color( 'secondary_color' )
                 ->label( __( 'Goal Bar Color', 'give-peer-to-peer' ) )
                 ->helpText( __( 'Used for the progress bar on the campaign, team, and fundraisers pages.', 'give-peer-to-peer' ) )
                 ->defaultValue( '#FFA200' ),
        ];
    }

    /**
     * Render edit form link
     */
    public function renderEditFormLink() {
        return sprintf(
            '<a href="%s" class="give-p2p-edit-form-link">%s</a>',
            admin_url( 'post.php?post={formId}&action=edit' ),
            esc_html__( 'Edit Donation Form', 'give-peer-to-peer' )
        );
    }

    /**
     * @return array
     */
    private function getDonationForms() {
        $forms = give( FormRepository::class )->getDonationForms();

        $donationForms = [];

        foreach ( $forms as $form ) {
            list ( $value, $label ) = $form;
            array_push( $donationForms, Option::make( $value, $label ) );
        }

        return $donationForms;
    }
}
