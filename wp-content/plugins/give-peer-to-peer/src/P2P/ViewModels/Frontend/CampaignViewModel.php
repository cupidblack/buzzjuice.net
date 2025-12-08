<?php

namespace GiveP2P\P2P\ViewModels\Frontend;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Models\Campaign;

/**
 * Class CampaignViewModel
 * @package GiveP2P\P2P\ViewModels\Frontend
 *
 * @since 1.0.0
 */
class CampaignViewModel {

	/**
	 * @var Campaign
	 */
	private $campaign;


	/**
	 * CampaignViewModel constructor.
	 *
	 * @param  Campaign  $campaign
	 */
	public function __construct( Campaign $campaign ) {
		$this->campaign = $campaign;
	}

	/**
	 * @return array
	 */
	public function exports() {
		return [
			'campaign_id'                  => $this->campaign->getId(),
			'campaign_title'               => $this->campaign->get( 'campaign_title' ),
			'campaign_goal'                => Money::ofMinor( $this->campaign->get( 'campaign_goal' ), give_get_option( 'currency' ) )->getAmount(),
			'campaign_image'               => $this->campaign->get( 'campaign_image' ),
			'campaign_logo'                => $this->campaign->get( 'campaign_logo' ),
			'long_desc'                    => wpautop( $this->campaign->get( 'long_desc' ) ),
			'short_desc'                   => wpautop( $this->campaign->get( 'short_desc' ) ),
			'primary_color'                => $this->campaign->get( 'primary_color' ),
			'secondary_color'              => $this->campaign->get( 'secondary_color' ),
			'campaign_url'                 => home_url( 'campaign/' . $this->campaign->get( 'campaign_url' ) ),
			'base_url'                     => parse_url( home_url( 'campaign/' . $this->campaign->get( 'campaign_url' ) ), PHP_URL_PATH ),
			'iframe_url'                   => $this->getIframeURL(),
			'sponsor_linking'              => $this->campaign->get( 'sponsor_linking' ),
            'teams_registration'           => $this->campaign->isTeamsRegistrationEnabled(),
            'team_goal'                    => Money::ofMinor( $this->campaign->get( 'team_goal' ), give_get_option( 'currency' ) )->getAmount(),
			'team_story_placeholder'       => $this->campaign->get( 'team_story_placeholder' ),
			'fundraiser_goal'              => Money::ofMinor( $this->campaign->get( 'fundraiser_goal' ), give_get_option( 'currency' ) )->getAmount(),
			'fundraiser_approvals'         => $this->campaign->get( 'fundraiser_approvals' ),
			'fundraiser_story_placeholder' => $this->campaign->get( 'fundraiser_story_placeholder' ),
			'sponsors_display'             => $this->campaign->get( 'sponsors_display' ),
			'sponsors_enabled'             => $this->campaign->get( 'sponsors_enabled' ),
			'sponsor_section_heading'      => $this->campaign->get( 'sponsor_section_heading' ),
			'sponsor_application_page'     => filter_var( $this->campaign->get( 'sponsor_application_page' ), FILTER_VALIDATE_URL )
				? $this->campaign->get( 'sponsor_application_page' )
				: '',
			'status'                       => $this->campaign->get( 'status' ),
			'is_active'                    => $this->campaign->hasApprovalStatus(),
		];
	}

	/**
	 * @since 1.0.0
	 * @return string
	 */
	protected function getIframeURL() {
		return add_query_arg( [
			'url_prefix'               => give_get_option('form_page_url_prefix', 'give'),
			'form_id'                  => $this->campaign->get( 'form_id' ),
			'giveDonationFormInIframe' => 1,
			'p2pSourceID'              => $this->campaign->get( 'id' ),
			'p2pSourceType'            => 'campaign',
		], home_url() );
	}
}
