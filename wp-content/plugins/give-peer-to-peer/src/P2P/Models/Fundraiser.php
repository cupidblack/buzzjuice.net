<?php

namespace GiveP2P\P2P\Models;

use GiveP2P\P2P\Models\Traits\Properties;
use GiveP2P\P2P\Models\Traits\Status;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\Notification;
use InvalidArgumentException;

/**
 * Class Fundraiser
 * @package GiveP2P\P2P\Models
 *
 * @since   1.0.0
 */
class Fundraiser
{

    use Status;
    use Properties;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var int
     */
    protected $campaign_id;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var int
     */
    protected $team_id;

    /**
     * @var bool
     */
    protected $team_captain;

    /**
     * @var string
     */
    protected $date_created;

    /**
     * @var string
     */
    protected $team_name;

    /**
     * @var int
     */
    protected $goal = 0;

    /**
     * @var string
     */
    protected $story = '';

    /**
     * @var string
     */
    protected $profile_image;

    /**

	 * @var bool
	 */
	protected $notify_of_donations;



	/**
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getCampaignId() {
		return $this->campaign_id;
	}

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * @return bool
     */
    public function isTeamCaptain()
    {
        return (bool)$this->team_captain;
    }

    /**
     * @since 1.5.0
     *
     * @return bool
     */
    public function isNotifiedOfDonations()
    {
        return (bool)$this->notify_of_donations;
    }

    /**
     * @since 1.5.0
     *
     * @return string
     */
    public function getEmail()
    {
        $user = get_user_by('id', $this->getUserId());

        return $user->user_email;
    }

    /**
     * @return int
     */
    public function getGoal()
    {
        return ( int )$this->goal;
    }

    /**
     * @return string
     */
    public function getStory()
    {
        return $this->story;
    }

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return $this->date_created;
    }

    /**
     * return array
     */
    public function getRequiredFields()
    {
        return [
            'campaign_id',
            'user_id',
        ];
    }

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'                         => $this->id,
			'campaign_id'                => $this->campaign_id,
			'user_id'                    => $this->user_id,
			'team_id'                    => $this->team_id,
			'team_captain'               => $this->team_captain,
			'team_name'                  => $this->team_name,
			'goal'                       => $this->goal,
			'story'                      => $this->story,
			'profile_image'              => $this->profile_image,
			'status'                     => $this->status,
			'date_created'               => $this->date_created,
			'notify_of_donations'        => $this->notify_of_donations,
		];
	}


    /**
     * @param array $fundraiserData
     *
     * @return static
     */
    public static function fromArray($fundraiserData)
    {
        $fundraiser = new static();
        $fundraiser->validateArray($fundraiserData);
        $fundraiser->setPropertiesFromArray($fundraiserData);

        return $fundraiser;
    }


    /**
     * Validate Fundraiser data array
     *
     * @param array $fundraiserData
     */
    private function validateArray($fundraiserData)
    {
        if (array_diff($this->getRequiredFields(), array_keys($fundraiserData))) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__('To create a Fundraiser, please provide all the required fields: %s',
                        'give-peer-to-peer'),
                    implode(', ', $this->getRequiredFields())
                )
            );
        }
    }

    /**
     * Save Fundraiser
     * @return bool
     */
    public function save()
    {
        /**
         * @var FundraiserRepository $repository
         */
        $repository = give(FundraiserRepository::class);

        if ($this->getId()) {
            return $repository->saveFundraiser($this);
        } else {
            $this->id = $repository->insertFundraiser($this);

            return (bool)$this->id;
        }
    }
}
