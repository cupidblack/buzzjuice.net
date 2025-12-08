<?php

namespace GiveP2P\P2P\Controllers;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Helpers\RelativeDateHelper;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\DonationRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\View;
use GiveP2P\P2P\ViewModels\Frontend\AuthViewModel;
use GiveP2P\P2P\ViewModels\Frontend\CampaignViewModel;
use GiveP2P\P2P\ViewModels\Frontend\TeamViewModel;
use GiveP2P\Routing\NotFoundException;

class TeamController {

	/**
	 * @var TeamRepository
	 */
	private $teamRepository;

	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @var DonationRepository
	 */
	private $donationRepository;
	/**
	 * @var FundraiserRepository
	 */
	protected $fundraiserRepository;

	public function __construct(
		TeamRepository $teamRepository,
		FundraiserRepository $fundRepository,
		CampaignRepository $campaignRepository,
		DonationRepository $donationRepository
	) {
		$this->teamRepository       = $teamRepository;
		$this->fundraiserRepository = $fundRepository;
		$this->campaignRepository   = $campaignRepository;
		$this->donationRepository   = $donationRepository;
	}

    /**
     * @since 1.4.0 Pass compaign when create object of AuthViewModel class
     *
     * @param string $campaignSlug
     * @param int $teamID
     *
     * @return View|void
     *
     * @throws NotFoundException
     */
	public function profile( $campaignSlug, $teamID ) {

		$campaign = $this->campaignRepository->getCampaignBySlug( $campaignSlug );

		if ( ! $campaign || ( ! current_user_can( 'administrator' ) && ! $campaign->hasApprovalStatus() ) ) {
			throw new NotFoundException( 'Campaign not found.' );
		}

		if ( ! $team = $this->teamRepository->getTeam( $teamID ) ) {
			throw new NotFoundException( 'Team not found.' );
		}

        // Fundraiser can edit team profile till profile approval but can not view it.
        if ( ! $team->hasApprovalStatus() && !$this->isRequestingTeamUpdateView() ) {
			if ( get_current_user_id() == Fundraiser::getFundraiser( $team->get( 'owner_id' ) )->get( 'user_id' ) ) {
                /**
                 * @since 1.3.0 For unapproved teams, redirects the team captain user to "Start Fundraising".
                 */
                wp_redirect(home_url('campaign/' . $campaignSlug . '/start-fundraising'));
                die();
            }

            throw new NotFoundException( 'Team not found.' );
        }

		$authViewModel = new AuthViewModel($campaign);
        $teamViewModel = new TeamViewModel($team, $this->teamRepository);
        $campaignViewModel = new CampaignViewModel($campaign);

		$donations = array_map( function ( $donation ) use ( $campaign, $team ) {
			$donation[ 'isRecurring' ] = (bool) give_get_meta( $donation['donationID'], '_give_is_donation_recurring', true );

			switch ( $donation[ 'sourceType' ] ) {
				case 'fundraiser':
					$fundraiser = $this->fundraiserRepository->getFundraiser( $donation[ 'sourceId' ] );
					$donation[ 'sourceName' ] = get_userdata( $fundraiser->get( 'user_id' ) )->display_name;
					$donation[ 'sourceLink' ] = sprintf( home_url( '/campaign/%s/fundraiser/%d' ), $campaign->getUrl(), $donation[ 'sourceId' ] );
					break;

				default:
					$donation[ 'sourceName' ] = $team->get( 'name' );
					$donation[ 'sourceLink' ] = sprintf( home_url( '/campaign/%s/team/%d' ), $campaign->getUrl(), $team->getId() );
			}

			return [
				'donorName'            => ! $donation[ 'isAnonymousDonation' ] && isset( $donation[ 'donorName' ] )
					? $donation[ 'donorName' ]
					: __( 'Anonymous' ),
				'teamOrFundraiserName' => $donation[ 'sourceName' ],
				'sourceLink' => $donation[ 'sourceLink' ],
				'donationType'         => $donation[ 'isRecurring' ]
					? __( 'recurring', 'give-peer-to-peer' )
					: __( 'one-time', 'give-peer-to-peer' ),
				'relativeDateString'   => ( new RelativeDateHelper( $donation[ 'donationDate' ] ) )->days( __( 'today', 'give-peer-to-peer' ),
					__( 'yesterday', 'give-peer-to-peer' ), __( '%d days ago', 'give-peer-to-peer' ) ),
				'amount'               => Money::ofMinor( $donation[ 'donationAmount' ], give_get_option( 'currency' ) )->getAmount(),
			];
		}, $this->donationRepository->getRecentDonationsForTeam( $team->getId(), 6 ) );

		$avgAmount    = $this->teamRepository->getAverageDonationAmount( $team->getId() );
		$raisedAmount = $this->teamRepository->getRaisedAmount( $team->getId() );

		$teamStats = [
			'donorsCount'      => $this->teamRepository->getDonorsCount( $team->getId() ),
			'donationsCount'   => $this->teamRepository->getDonationsCount( $team->getId() ),
			'averageAmount'    => Money::ofMinor( $avgAmount, give_get_option( 'currency' ) )->getAmount(),
			'raisedAmount'     => Money::ofMinor( $raisedAmount, give_get_option( 'currency' ) )->getAmount(),
			'raisedPercentage' => ( $team->getGoal() && $raisedAmount )
				? round( $raisedAmount / $team->getGoal() * 100 )
				: 0,
		];

		$teamData = array_merge(
			$teamViewModel->exports(),
			[
				'iframe_url'      => add_query_arg( [
					'url_prefix'               => give_get_option('form_page_url_prefix', 'give'),
					'form_id'                  => $campaign->get( 'form_id' ),
					'giveDonationFormInIframe' => 1,
					'p2pSourceID'              => $team->getId(),
					'p2pSourceType'            => 'team',
				], home_url() ),
				'captain_user_id' => $this->fundraiserRepository->getUserIdByFundraiserIdAndCampaignId(
					$team->get( 'owner_id' ),
					$campaign->getId()
				)
			]
		);

		return new View( 'p2p-app', [
			'team'       => $teamData,
			'campaign'   => $campaignViewModel->exports(),
			'donations'  => $donations,
			'sponsors'   => array_map( function ( $sponsor ) {
				return $sponsor->toArray();
			}, $campaign->getSponsors() ),
			'team-stats' => $teamStats,
			'auth'       => $authViewModel->exports(),

		] );

	}

	public function donate( $campaignSlug, $teamId ) {

		if ( ! $campaign = $this->campaignRepository->getCampaignBySlug( $campaignSlug ) ) {
			throw new NotFoundException( 'Campaign not found.' );
		}

		if ( ! $team = $this->teamRepository->getTeam( $teamId ) ) {
			throw new NotFoundException( 'Team not found.' );
		}

		if ( ! $team->hasApprovalStatus() ) {
			if ( get_current_user_id() != Fundraiser::getFundraiser( $team->get( 'owner_id' ) )->get( 'user_id' ) ) {
				throw new NotFoundException( 'Fundraiser not found.' );
			}
		}

		$authViewModel     = new AuthViewModel($campaign);
		$campaignViewModel = new CampaignViewModel( $campaign );

		return new View( 'p2p-app', [
			'campaign' => $campaignViewModel->exports(),
			'team'     => array_merge( $team->toArray(), [
				'iframe_url' => add_query_arg( [
					'url_prefix'               => give_get_option('form_page_url_prefix', 'give'),
					'form_id'                  => $campaign->get( 'form_id' ),
					'giveDonationFormInIframe' => 1,
					'p2pSourceID'              => $team->getId(),
					'p2pSourceType'            => 'team',
				], home_url() )
			] ),
			'sponsors' => array_map( function ( $sponsor ) {
				return $sponsor->toArray();
			}, $campaign->getSponsors() ),
			'auth'     => $authViewModel->exports(),
		] );
	}

    /**
     * This function returns boolean result whether request for team update view.
     *
     * @since 1.4.0
     */
    private function isRequestingTeamUpdateView(): bool
    {
        global $wp_query;


        return false !== strpos(
                $wp_query->query['give_route'],
                "campaign/{campaign}/team/{team}/update"
            );
    }
}
