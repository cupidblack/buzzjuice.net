<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since unreleased
 */
class UpdateFundraiserProfileRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'update-fundraiser-profile';

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

	/**
	 * @param  FileUpload  $fileUpload
	 * @param  FundraiserRepository  $fundraiserRepository
	 */
	public function __construct(
		FileUpload $fileUpload,
		FundraiserRepository $fundraiserRepository
	) {
		$this->fileUpload           = $fileUpload;
		$this->fundraiserRepository = $fundraiserRepository;
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
					'permission_callback' => 'is_user_logged_in',
					'args'                => [
						'campaignId' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'goal' => [
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => esc_html__('Individual fundraising goal', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                            },
                        ],
                        'story' => [
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => esc_html__('Individual fundraising story', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            },
                        ],
                        'notify_of_donations' => [
                            'required'          => false,
                            'type'              => 'boolean',
                            'description' => esc_html__('Donation Notification', 'give-peer-to-peer'),
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
		$fundraiserId = $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId(
			get_current_user_id(),
			$request->get_param( 'campaignId' )
		);

		if ( ! $fundraiserId ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Fundraiser does not exist on campaign', 'give-peer-to-peer' ),
				],
				404
			);
		}

		if ( ! $fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId ) ) {
            return new WP_REST_Response(
                [
                    'message' => __('Fundraiser does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $goal = Money::of($request->get_param('goal'), give_get_option('currency'));

        $fundraiser->set('story', $request->get_param('story'));
        $fundraiser->set('goal', $goal->getMinorAmount());
        $fundraiser->set('notify_of_donations', $request->get_param('notify_of_donations'));


        // Handle file upload
        if (!empty($file = $request->get_file_params())) {

            $upload = $this->fileUpload->handleFile($file);

            if (is_wp_error($upload)) {
                return new WP_REST_Response(
                    [
                        'error' => 'upload_failed',
                        'message' => $upload->get_error_message(),
                    ],
                    400
                );
            }

            if ($image = wp_get_attachment_image_url($upload)) {
                $fundraiser->set('profile_image', $image);
            }
        }

		if ( ! $fundraiser->save() ) {
			return new WP_REST_Response(
				[
					'error'   => 'update_failed',
					'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
				],
				400
			);
		}

		return new WP_REST_Response();
	}

}
