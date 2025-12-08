<?php

namespace GiveP2P\P2P\Routes;

use WP_REST_Request;
use WP_REST_Response;
use GiveP2P\P2P\Repositories\CampaignRepository;

/**
 * Class EditCampaignUrlRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class EditCampaignUrlRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'edit-campaign-url';

	/**
	 * @param CampaignRepository $campaignRepository
	 */
	public function __construct( CampaignRepository $campaignRepository ) {
		$this->repository = $campaignRepository;
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
					'permission_callback' => [ $this, 'permissionsCheck' ],
					'args'                => [
						'id'  => [
							'validate_callback' => function( $param ) {
								return ! empty( trim( $param ) );
							},
						],

						'url' => [
							'default' => '',
						],
					],
				],
				'schema' => [ $this, 'getSchema' ],
			]
		);
	}

	/**
	 * @return array
	 */
	public function getSchema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'logs',
			'type'       => 'object',
			'properties' => [
				'id'  => [
					'type'        => 'integer',
					'description' => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
				],
				'url' => [
					'type'        => 'string',
					'description' => esc_html__( 'Campaign URL', 'give-peer-to-peer' ),
				],
			],
		];
	}

	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function handleRequest( WP_REST_Request $request ) {

		$id  = $request->get_param( 'id' );
		$url = $request->get_param( 'url' );
		$url = sanitize_title( $url );

		// Bailout if Campaign doesn't exist
		$campaign = $this->repository->getCampaign( $id );

		if ( ! $campaign ) {
			return new WP_REST_Response(
				[
					'status' => false,
					'error'  => 'Invalid Campaign ID ' . $id,
				]
			);
		}

		// Default campaign url
		if ( empty( $url ) ) {
			$url = 'campaign-' . $request->get_param( 'id' );
		} else {
			// Check if campaign URL is already in use
			if ( $this->repository->campaignSlugExist( $url ) ) {
				$url = sprintf( '%s-%d', $url, $id );
			}
		}

		// Set URL
		$campaign->set( 'campaign_url', $url );
		$campaign->save();

		return new WP_REST_Response(
			[
				'status'     => true,
				'url'        => $url,
				'previewUrl' => home_url( 'campaign/' . $url . '/' ),
			]
		);
	}

}
