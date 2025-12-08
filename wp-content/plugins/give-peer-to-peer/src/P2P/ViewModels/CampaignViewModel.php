<?php

namespace GiveP2P\P2P\ViewModels;

use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ValueObjects\Status;

/**
 * Class CampaignViewModel
 * @package GiveP2P\P2P\ViewModels
 *
 * @since 1.0.0
 */
class CampaignViewModel {

	/**
	 * @var Campaign
	 */
	private $campaign;

	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @var TeamRepository
	 */
	private $teamRepository;

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * CampaignViewModel constructor.
	 *
	 * @param  Campaign  $campaign
	 * @param  CampaignRepository  $campaignRepository
	 * @param  TeamRepository  $teamRepository
	 * @param  FundraiserRepository  $fundraiserRepository
	 */
	public function __construct(
		Campaign $campaign,
		CampaignRepository $campaignRepository,
		TeamRepository $teamRepository,
		FundraiserRepository $fundraiserRepository
	) {
		$this->campaign             = $campaign;
		$this->campaignRepository   = $campaignRepository;
		$this->teamRepository       = $teamRepository;
		$this->fundraiserRepository = $fundraiserRepository;
	}

	/**
     * @since 1.4.0 Add "teams_registration" data.
	 * @return array
	 */
	public function exports() {
		return [
			'campaign_id'            => $this->campaign->getId(),
			'campaign_title'         => $this->campaign->getTitle(),
			'campaign_url'           => $this->campaign->getUrl(),
			'campaign_goal'          => $this->campaign->getGoal(),
			'campaign_long_desc'     => $this->campaign->getLongDescription(),
			'campaign_amount_raised' => $this->campaignRepository->getRaisedAmount( $this->campaign->getId() ),
			'teams_registration'     => $this->campaign->isTeamsRegistrationEnabled(),
			'teams_total'            => $this->teamRepository->getCampaignTeamsCount( $this->campaign->getId() ),
			'teams_pending'          => $this->teamRepository->getCampaignTeamsCountByStatus( $this->campaign->getId(), Status::PENDING ),
			'fundraisers_total'      => $this->fundraiserRepository->getCampaignFundraisersCount( $this->campaign->getId() ),
			'fundraisers_pending'    => $this->fundraiserRepository->getCampaignFundraisersCountByStatus( $this->campaign->getId(), Status::PENDING ),
			'start_date'             => is_null( $this->campaign->getStartDate() ) ? $this->campaign->getDateCreated() : $this->campaign->getStartDate(),
			'end_date'               => $this->campaign->getEndDate(),
			'sponsors_display'       => $this->campaign->get( 'sponsors_display' ),
			'hasTeamApprovals'       => $this->campaign->doesRequireTeamApproval(),
			'hasFundraiserApprovals' => $this->campaign->doesRequireFundraiserApproval(),
			'status'                 => $this->campaign->get( 'status' ),
			'campaign_logo'          => $this->campaign->getLogo(),
		];
	}
}
