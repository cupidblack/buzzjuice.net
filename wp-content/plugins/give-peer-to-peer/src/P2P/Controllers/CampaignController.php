<?php

namespace GiveP2P\P2P\Controllers;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Helpers\RelativeDateHelper;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\DonationRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\View;
use GiveP2P\P2P\ViewModels\Frontend\AuthViewModel;
use GiveP2P\P2P\ViewModels\Frontend\CampaignViewModel;
use GiveP2P\Routing\NotFoundException;

class CampaignController {

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
	private $fundraiserRepository;

	public function __construct(
		CampaignRepository $campaignRepository,
		DonationRepository $donationRepository,
		FundraiserRepository $fundRepository
	) {
		$this->campaignRepository   = $campaignRepository;
		$this->donationRepository   = $donationRepository;
		$this->fundraiserRepository = $fundRepository;
	}

	public function campaign( $campaignSlug ) {

		$campaign = $this->campaignRepository->getCampaignBySlug( $campaignSlug );

		if ( ! $campaign || ( ! current_user_can( 'administrator' ) && ! $campaign->hasApprovalStatus() ) ) {
			throw new NotFoundException( 'Campaign not found.' );
		}

		$authViewModel     = new AuthViewModel( $campaign );
		$campaignViewModel = new CampaignViewModel( $campaign );

		$recentDonationsCount = 6;

		$campaignDonations = array_map( function ( $donation ) use ( $campaignSlug ) {
			$donation[ 'isRecurring' ] = (bool) give_get_meta( $donation['donationID'], '_give_is_donation_recurring', true );

			switch ( $donation[ 'sourceType' ] ) {
				case 'team':
					$donation[ 'sourceName' ] = $donation[ 'teamName' ];
					$donation[ 'sourceLink' ] = sprintf( home_url( '/campaign/%s/team/%d' ), $campaignSlug, $donation[ 'sourceId' ] );
					break;

				case 'fundraiser':
					$fundraiser               = $this->fundraiserRepository->getFundraiser( $donation[ 'sourceId' ] );
					$donation[ 'sourceName' ] = get_userdata( $fundraiser->get( 'user_id' ) )->display_name;
					$donation[ 'sourceLink' ] = sprintf( home_url( '/campaign/%s/fundraiser/%d' ), $campaignSlug, $donation[ 'sourceId' ] );
					break;

				default:
					$donation[ 'sourceName' ] = $donation[ 'campaignName' ];
					$donation[ 'sourceLink' ] = sprintf( home_url( '/campaign/%s' ), $campaignSlug );
			}

			return $donation;
		}, $this->donationRepository->getRecentDonationsForCampaign( $campaign->get( 'id' ), $recentDonationsCount ) );

		$raisedAmount  = $this->campaignRepository->getRaisedAmount( $campaign->get( 'id' ) );
		$donationsCount = $this->campaignRepository->getDonationsCount( $campaign->get( 'id' ) );
		$campaignStats = [
			'raisedAmount'     => Money::ofMinor( $raisedAmount, give_get_option( 'currency' ) )->getAmount(),
			'donationsCount'   => $donationsCount,
			'averageAmount'    => ( $raisedAmount && $donationsCount )
				? Money::ofMinor( (int) ($raisedAmount / $donationsCount), give_get_option( 'currency' ) )->getAmount()
				: 0,
			'fundraisersCount' => $this->campaignRepository->getFundraisersCount( $campaign->get( 'id' ) ),
			'teamsCount'       => $this->campaignRepository->getTeamsCount( $campaign->get( 'id' ) ),
			'raisedPercentage' => ( $campaign->getGoal() && $raisedAmount )
				? round( $raisedAmount / $campaign->getGoal() * 100 )
				: 0,
		];

		$donations = array_map( function ( $donation ) {
			return [
				'donorName'            => ! $donation[ 'isAnonymousDonation' ] && isset( $donation[ 'donorName' ] )
					? $donation[ 'donorName' ]
					: __( 'Anonymous' ),
				'teamOrFundraiserName' => $donation[ 'sourceName' ],
				'sourceLink'           => $donation[ 'sourceLink' ],
				'donationType'         => $donation[ 'isRecurring' ]
					? __( 'recurring', 'give-peer-to-peer' )
					: __( 'one-time', 'give-peer-to-peer' ),
				'donationDate'         => $donation[ 'donationDate' ],
				'relativeDateString'   => ( new RelativeDateHelper( $donation[ 'donationDate' ] ) )->days( __( 'today', 'give-peer-to-peer' ),
					__( 'yesterday', 'give-peer-to-peer' ), __( '%d days ago', 'give-peer-to-peer' ) ),
				'amount'               => Money::ofMinor( $donation[ 'donationAmount' ], give_get_option( 'currency' ) )->getAmount(),
			];
		}, $campaignDonations );

		usort( $donations, function ( $a, $b ) {
			return strtotime($b[ 'donationDate' ]) - strtotime($a[ 'donationDate' ]);
		} );

		return new View( 'p2p-app', [
			'campaign'       => $campaignViewModel->exports(),
			'donations'      => array_slice( $donations, 0, $recentDonationsCount ),
			'sponsors'       => array_map( function ( $sponsor ) {
				return $sponsor->toArray();
			}, $campaign->getSponsors() ),
			'campaign-stats' => $campaignStats,
			'auth'           => $authViewModel->exports(),
		] );
	}
}
