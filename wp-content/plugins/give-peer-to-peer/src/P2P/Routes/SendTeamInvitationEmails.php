<?php

namespace GiveP2P\P2P\Routes;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Mailer;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamInvitationRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class CreateTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class SendTeamInvitationEmails extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'send-team-invitation-emails';

	/**
	 * @var Mailer
	 */
	protected $mailer;

	/**
	 * @var TeamRepository
	 */
	protected $teamRepository;

	/**
	 * @var CampaignRepository
	 */
	protected $campaignRepository;

	/**
	 * @var FundraiserRepository
	 */
	protected $fundraiserRepository;

	/**
	 * @var TeamInvitationRepository
	 */
	protected $invitationRepository;

	/**
	 * @param TeamRepository $teamRepository
	 * @param CampaignRepository $campaignRepository
	 * @param FundraiserRepository $fundraiserRepository
	 * @param TeamInvitationRepository $invitationRepository
	 */
	public function __construct(
		Mailer $mailer,
		TeamRepository $teamRepository,
		CampaignRepository $campaignRepository,
		FundraiserRepository $fundraiserRepository,
		TeamInvitationRepository $invitationRepository
	) {
		$this->mailer = $mailer;
		$this->teamRepository = $teamRepository;
		$this->campaignRepository = $campaignRepository;
		$this->fundraiserRepository = $fundraiserRepository;
		$this->invitationRepository = $invitationRepository;
	}


	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return bool
	 */
	public function teamOwnerPermissionCheck( WP_REST_Request $request ) {

		if( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		global $wpdb;

		//Verify that the current user is the owner of the team for which the invitations will be sent.
		return (bool) DB::get_var(
			DB::prepare(
				"
				SELECT count(teams.id)
				FROM $wpdb->give_p2p_teams AS teams
				JOIN $wpdb->give_p2p_fundraisers as fundraisers ON fundraisers.id = teams.owner_id
				WHERE teams.id = %d
				  AND fundraisers.user_id = %d",
				$request->get_param( 'team_id' ),
				get_current_user_id()
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function registerRoute() {
		register_rest_route(
			parent::ROUTE_NAMESPACE,
			$this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'handleRequest' ],
					'permission_callback' => [ $this, 'teamOwnerPermissionCheck' ],
					'args'                => [
						'team_id' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Team ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
					],
				],
			]
		);
	}


	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function handleRequest( WP_REST_Request $request ) {
        $team = $this->teamRepository->getTeam($request->get_param('team_id'));

        /**
         * @since 1.3.0 Only send invitation emails if the team is approved.
         */
        if (!$team || !$team->hasApprovalStatus()) {
            return new WP_REST_Response(null, 400);
        }

        $campaign = $this->campaignRepository->getCampaign($team->get('campaign_id'));
        if (!$campaign) {
            return new WP_REST_Response(null, 400);
        }

        $invitations = $this->invitationRepository->getForTeamNotSent($team->getId());

        if (!$invitations) {
            return new WP_REST_Response(null, 200);
        }

		$teamUrl = home_url( 'campaign/' . $campaign->getUrl() . '/team/' . $team->getId() . '/join' );
		$campaignUrl = home_url( 'campaign/' . $campaign->getUrl() );

		$fromName = give_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$fromAddress = give_get_option( 'from_email', get_option( 'admin_email' ) );

		$headers  = "From: $fromName <$fromAddress>\r\n";
		$headers .= "Reply-To: $fromAddress\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";

		$subject = __( 'Invitation to fundraise', 'give-peer-to-peer' );

		$emailMessage = $this->mailer->getContents( 'centered', [
			'siteName' => get_bloginfo('name'),
			'siteURL' => home_url(),
			'logo' => $team->get('profile_image'),
			'emailTitle' => $subject,
			'emailContent' => sprintf(
					__( 'You are invited to fundraise with %s on behalf of %s.', 'give-peer-to-peer' ),
					sprintf( '<a href="%s">%s</a>', $teamUrl, $team->getName() ),
					sprintf( '<a href="%s">%s</a>', $campaignUrl, $campaign->getTitle() )
				),
		] );

		foreach( $invitations as $invitation ) {
			$sent = wp_mail(
				$invitation->getEmail(),
				$subject,
				$emailMessage,
				$headers
			);
			if( $sent ) {
				$invitation->setDateSent(date('Y-m-d H:i:s'));
				$invitation->save();
			}
		}

		return new WP_REST_Response();
	}


}
