<?php

namespace GiveP2P\P2P\Models;

use GiveP2P\P2P\Admin\Contracts\SettingsData;
use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\Models\Traits\Properties;
use GiveP2P\P2P\Models\Traits\Status;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\ValueObjects\Enabled;
use InvalidArgumentException;

/**
 * P2P Campaign Model
 *
 * @package GiveP2P\P2P\Models
 *
 * @since 1.0.0
 */
class Campaign implements SettingsData {

	use Status;
	use Properties;

	/**
	 * @var int
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $campaign_title;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var string
	 */
	protected $campaign_url;

	/**
	 * @var string
	 */
	protected $short_desc;

	/**
	 * @var string
	 */
	protected $long_desc;

	/**
	 * @var string
	 */
	protected $campaign_logo;

	/**
	 * @var string
	 */
	protected $campaign_image;

	/**
	 * @var string
	 */
	protected $primary_color;

	/**
	 * @var string
	 */
	protected $campaign_goal;

	/**
	 * @var string
	 */
	protected $secondary_color;

	/**
	 * @var string
	 */
	protected $sponsors_enabled;

	/**
	 * @var string
	 */
	protected $sponsor_linking;

	/**
	 * @var string
	 */
	protected $sponsor_section_heading;

	/**
	 * @var string
	 */
	protected $sponsor_application_page;

	/**
	 * @var string
	 */
	protected $sponsors_display;

	/**
	 * @var array
	 */
	protected $sponsors;

	/**
	 * @var string
	 */
	protected $fundraiser_approvals;

	/**
	 * @var string
	 */
	protected $fundraiser_approvals_email_subject;

	/**
	 * @var string
	 */
	protected $fundraiser_approvals_email_body;

	/**
	 * @var int
	 */
	protected $fundraiser_goal;

	/**
	 * @var string
	 */
	protected $fundraiser_story_placeholder;

    /**
     * @var string
     */
    protected $teams_registration;

    /**
	 * @var string
	 */
	protected $team_approvals;

	/**
	 * @var string
	 */
	protected $team_approvals_email_subject;

	/**
	 * @var string
	 */
	protected $team_approvals_email_body;

	/**
	 * @var int
	 */
	protected $team_goal;

	/**
	 * @var string
	 */
	protected $team_story_placeholder;

    /**
     * @var string
     */
	protected $registration_digest;

	/**
	 * @var string
	 */
	protected $team_rankings;

	/**
	 * @var string
	 */
	protected $date_created;

	/**
	 * @var string|null
	 */
	protected $start_date;

	/**
	 * @var string|null
	 */
	protected $end_date;

	/**
	 * @param  array  $campaignData
	 *
	 * @return static
	 */
	public static function fromArray( $campaignData ) {
		$campaign = new static();
		$campaign->validateArray( $campaignData );
		$campaign->setPropertiesFromArray( $campaignData );

		return $campaign;
	}

	/**
	 * Get campaign model from collection of fields
	 *
	 * @param  FormField[]  $fields
	 *
	 * @return static
	 */
	public static function fromCollection( $fields ) {
		$campaign = new static();
		$campaign->validateCollection( $fields );
		$campaign->setPropertiesFromCollection( $fields );

		return $campaign;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'                                 => $this->id,
			'campaign_title'                     => $this->campaign_title,
			'campaign_url'                       => $this->campaign_url,
			'form_id'                            => $this->form_id,
			'short_desc'                         => $this->short_desc,
			'long_desc'                          => $this->long_desc,
			'campaign_logo'                      => $this->campaign_logo,
			'campaign_image'                     => $this->campaign_image,
			'primary_color'                      => $this->primary_color,
			'campaign_goal'                      => $this->campaign_goal,
			'secondary_color'                    => $this->secondary_color,
			'sponsors_enabled'                   => $this->sponsors_enabled,
			'sponsor_linking'                    => $this->sponsor_linking,
			'sponsor_section_heading'            => $this->sponsor_section_heading,
			'sponsor_application_page'           => $this->sponsor_application_page,
			'sponsors_display'                   => $this->sponsors_display,
			'sponsors'                           => $this->sponsors,
			'fundraiser_approvals'               => $this->fundraiser_approvals,
			'fundraiser_approvals_email_subject' => $this->fundraiser_approvals_email_subject,
			'fundraiser_approvals_email_body'    => $this->fundraiser_approvals_email_body,
			'fundraiser_goal'                    => $this->fundraiser_goal,
			'fundraiser_story_placeholder'       => $this->fundraiser_story_placeholder,
			'teams_registration'                 => $this->teams_registration,
			'team_approvals'                     => $this->team_approvals,
			'team_approvals_email_subject'       => $this->team_approvals_email_subject,
			'team_approvals_email_body'          => $this->team_approvals_email_body,
			'team_goal'                          => $this->team_goal,
			'team_story_placeholder'             => $this->team_story_placeholder,
			'team_rankings'                      => $this->team_rankings,
            'registration_digest'                => $this->registration_digest,
			'start_date'                         => $this->start_date,
			'end_date'                           => $this->end_date,
			'date_created'                       => $this->date_created,
			'status'                             => $this->status,
		];
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	public function getUrl() {
		return $this->campaign_url;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->campaign_title;
	}

	/**
	 * @return Sponsor[]
	 */
	public function getSponsors() {
		$sponsors = [];

		if ( is_array( $this->sponsors ) ) {
			foreach ( $this->sponsors as $sponsor ) {
				$sponsors[] = Sponsor::fromArray( $sponsor );
			}
		}

		return $sponsors;
	}

	/**
	 * @return int
	 */
	public function getGoal() {
		return $this->campaign_goal;
	}

	/**
	 * @return string|null
	 */
	public function getStartDate() {
		return $this->start_date;
	}

	/**
	 * @return string|null
	 */
	public function getEndDate() {
		return $this->end_date;
	}

	/**
	 * @return string
	 */
	public function getDateCreated() {
		return $this->date_created;
	}

	/**
	 * @return bool
	 */
	public function doesRequireFundraiserApproval() {
		return Enabled::ENABLED === $this->fundraiser_approvals;
	}

	/**
	 * @return bool
	 */
	public function doesRequireTeamApproval() {
		return Enabled::ENABLED === $this->team_approvals;
	}

    /**
     * @return bool
     */
	public function isRegistrationDigestEnabled() {
	    return Enabled::ENABLED === $this->registration_digest;
    }

    /**
     * This function tells whether teams registration enabled for campaign.
     *
     * @since 1.4.0
     */
    public function isTeamsRegistrationEnabled(): bool
    {
        return Enabled::ENABLED === $this->teams_registration;
    }

    /**
     * @return bool
     */
    public function shouldSendIndividualRegistrationEmails() {
	    return ! $this->isRegistrationDigestEnabled();
    }

	/**
	 * @return int
	 */
	public function getFormID() {
		return $this->form_id;
	}


    /**
	 * @since 1.6.0
	 */
	public function getLongDescription(): string
    {
		return $this->long_desc;
	}

    /**
     * @return string
     */
    public function getImage() {
        return $this->campaign_image;
    }

    /**
     * @return string
     */
    public function getShortDescription() {
        return $this->short_desc;
    }

    /**
     * @since 1.6.1
     *
     */
    public function getLogo() {
        return $this->campaign_logo;
    }

	/**
	 * Validate Campaign data array
	 *
	 * @param  array  $campaignData
	 */
	private function validateArray( $campaignData ) {
		if ( array_diff( $this->getRequiredFields(), array_keys( $campaignData ) ) ) {
			throw new InvalidArgumentException(
				esc_html__( 'To create a Campaign, please provide all the required fields: ' . implode( ', ', $this->getRequiredFields() ), 'give-peer-to-peer' )
			);
		}
	}

	/**
	 * Validate Campaign FieldCollection
	 *
	 * @param  FormField[] $fields
	 */
	private function validateCollection( $fields ) {
		$data = [];

		foreach ( $fields as $field ) {
			$data[ $field->getName() ] = $field->getDefaultValue();
		}

		if ( array_diff( $this->getRequiredFields(), array_keys( $data ) ) ) {
			throw new InvalidArgumentException(
				esc_html__(
					'To create a Campaign object, please provide FieldCollection with all the required fields: ' . implode(
						', ',
						$this->getRequiredFields()
					),
					'give-peer-to-peer'
				)
			);
		}
	}

	/**
	 * return array
	 */
	public function getRequiredFields() {
		return [
			'campaign_title',
			'form_id',
		];
	}

	/**
	 * Save Campaign data
	 * @return bool
	 */
	public function save() {
		/**
		 * @var CampaignRepository $repository
		 */
		$repository = give( CampaignRepository::class );

		if ( $this->getId() ) {
			return $repository->saveCampaign( $this );
		} else {
			$this->id = $repository->insertCampaign( $this );
			return (bool) $this->id;
		}
	}
}
