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
use GiveP2P\P2P\ViewModels\FundraiserViewModel;
use GiveP2P\Routing\NotFoundException;

class FundraiserController {

	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * @var DonationRepository
	 */
	private $donationRepository;

	public function __construct(
		CampaignRepository $campaignRepository,
		FundraiserRepository $fundraiserRepository,
		DonationRepository $donationRepository
	) {
		$this->campaignRepository   = $campaignRepository;
		$this->fundraiserRepository = $fundraiserRepository;
		$this->donationRepository   = $donationRepository;
	}

	/**
	 * @param  string  $campaignSlug
	 * @param  int     $fundraiserId
	 *
	 * @return View
     *
	 * @throws NotFoundException
	 */
	public function profile( $campaignSlug, $fundraiserId ) {

		$campaign = $this->campaignRepository->getCampaignBySlug( $campaignSlug );

		if ( ! $campaign || ( ! current_user_can( 'administrator' ) && ! $campaign->hasApprovalStatus() ) ) {
			throw new NotFoundException( 'Campaign not found.' );
		}

		if ( ! $fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId ) ) {
			throw new NotFoundException( 'Fundraiser not found.' );
		}

        // Fundraiser can edit profile till profile approval but can not view it.
        if (!$fundraiser->hasApprovalStatus() && !$this->isRequestingFundraiserUpdateView()) {
            if (get_current_user_id() === (int)$fundraiser->get('user_id')) {
                /**
                 * @since 1.3.0 For unapproved fundraisers, redirects the fundraiser user to "Start Fundraising".
                 */
                wp_redirect(home_url('campaign/' . $campaignSlug . '/start-fundraising'));
                die();
            }

            throw new NotFoundException('Fundraiser not found.');
        }

		$user                = get_userdata( $fundraiser->get( 'user_id' ) );
		$authViewModel       = new AuthViewModel( $campaign );
		$campaignViewModel   = new CampaignViewModel( $campaign );
		$fundraiserViewModel = new FundraiserViewModel( $fundraiser, $this->fundraiserRepository, $user );

		$donations = array_map( function ( $donation ) use ( $user ) {
			$donation[ 'isRecurring' ] = (bool) give_get_meta( $donation[ 'donationID' ], '_give_is_donation_recurring', true );

			return [
				'donorName'            => ! $donation[ 'isAnonymousDonation' ] && isset( $donation[ 'donorName' ] )
					? $donation[ 'donorName' ]
					: __( 'Anonymous' ),
				'teamOrFundraiserName' => $user->display_name,
				'donationType'         => $donation[ 'isRecurring' ]
					? __( 'recurring', 'give-peer-to-peer' )
					: __( 'one-time', 'give-peer-to-peer' ),
				'relativeDateString'   => ( new RelativeDateHelper( $donation[ 'donationDate' ] ) )->days( __( 'today', 'give-peer-to-peer' ),
					__( 'yesterday', 'give-peer-to-peer' ), __( '%d days ago', 'give-peer-to-peer' ) ),
				'amount'               => Money::ofMinor( $donation[ 'donationAmount' ], give_get_option( 'currency' ) )->getAmount(),
			];
		}, $this->donationRepository->getRecentDonationsForFundraiser( $fundraiserId, 6 ) );

		$avgAmount    = $this->fundraiserRepository->getAverageDonationAmount( $fundraiser->getId() );
		$raisedAmount = $this->fundraiserRepository->getRaisedAmount( $fundraiser->getId() );

		$fundraiserStats = [
			'donorsCount'      => $this->fundraiserRepository->getDonorsCount( $fundraiser->getId() ),
			'donationsCount'   => $this->fundraiserRepository->getDonationsCount( $fundraiser->getId() ),
			'averageAmount'    => Money::ofMinor( $avgAmount, give_get_option( 'currency' ) )->getAmount(),
			'raisedAmount'     => Money::ofMinor( $raisedAmount, give_get_option( 'currency' ) )->getAmount(),
			'raisedPercentage' => ( $fundraiser->getGoal() && $raisedAmount )
				? round( $raisedAmount / $fundraiser->getGoal() * 100 )
				: 0,
		];

		return new View( 'p2p-app', [
			'user'             => [ 'name' => $user->display_name ],
			'campaign'         => $campaignViewModel->exports(),
			'fundraiser'       => array_merge( $fundraiserViewModel->exports(), [
				'iframe_url' => add_query_arg( [
					'url_prefix'               => give_get_option('form_page_url_prefix', 'give'),
					'form_id'                  => $campaign->get( 'form_id' ),
					'giveDonationFormInIframe' => 1,
					'p2pSourceID'              => $fundraiser->get( 'id' ),
					'p2pSourceType'            => 'fundraiser',
				], home_url() )
			] ),
			'donations'        => $donations,
			'sponsors'         => array_map( function ( $sponsor ) {
				return $sponsor->toArray();
			}, $campaign->getSponsors() ),
			'fundraiser-stats' => $fundraiserStats,
			'auth'             => $authViewModel->exports(),
		] );
	}

	public function donate( $campaignSlug, $fundraiserId ) {

		if ( ! $campaign = $this->campaignRepository->getCampaignBySlug( $campaignSlug ) ) {
			throw new NotFoundException( 'Campaign not found.' );
		}

		if ( ! $fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId ) ) {
			throw new NotFoundException( 'Fundraiser not found.' );
		}

		if ( ! $fundraiser->hasApprovalStatus() ) {
			if ( get_current_user_id() != $fundraiser->get( 'user_id' ) ) {
				throw new NotFoundException( 'Fundraiser not found.' );
			}
		}

		$user                = get_userdata( $fundraiser->get( 'user_id' ) );
		$authViewModel       = new AuthViewModel( $campaign );
		$campaignViewModel   = new CampaignViewModel( $campaign );
		$fundraiserViewModel = new FundraiserViewModel( $fundraiser, $this->fundraiserRepository, $user );

		return new View( 'p2p-app', [
			'user'       => [ 'name' => $user->display_name ],
			'campaign'   => $campaignViewModel->exports(),
			'fundraiser' => array_merge( $fundraiserViewModel->exports(), [
				'iframe_url' => add_query_arg( [
					'url_prefix'               => give_get_option('form_page_url_prefix', 'give'),
					'form_id'                  => $campaign->get( 'form_id' ),
					'giveDonationFormInIframe' => 1,
					'p2pSourceID'              => $fundraiser->get( 'id' ),
					'p2pSourceType'            => 'fundraiser',
				], home_url() )
			] ),
			'sponsors'   => array_map( function ( $sponsor ) {
				return $sponsor->toArray();
			}, $campaign->getSponsors() ),
			'auth'       => $authViewModel->exports(),
		] );
	}

    /**
     * This function returns boolean result whether request for fundraiser update view.
     *
     * @since 1.4.0
     */
    private function isRequestingFundraiserUpdateView(): bool
    {
        global $wp_query;


        return false !== strpos(
                $wp_query->query['give_route'],
                "campaign/{campaign}/fundraiser/{user}/update"
            );
    }
}
