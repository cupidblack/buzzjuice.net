<?php

namespace GiveP2P\P2P\Models;

use GiveP2P\P2P\Models\Traits\Properties;
use GiveP2P\P2P\Models\Traits\Status;
use GiveP2P\P2P\Repositories\TeamRepository;
use InvalidArgumentException;

/**
 * Class Team
 * @package GiveP2P\P2P\Models
 *
 * @since   1.0.0
 */
class Team
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
    protected $owner_id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $story;

    /**
     * @var string
     */
    protected $profile_image;

    /**
     * @var int
     */
    protected $goal;

    /**
     * @var string
     */
    protected $access;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $date_created;

    /**

	 * @var bool
	 */
	protected $notify_of_fundraisers;

    /**
	 * @var bool
	 */
	protected $notify_of_team_donations;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCampaignId()
    {
        return $this->campaign_id;
    }

    /**
     * @since 1.4.0
     * @return int
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getGoal()
    {
        return ( int )$this->goal;
    }

    /**
     * return array
     */
    public function getRequiredFields()
    {
        return [
            'campaign_id',
            'name',
        ];
    }

    /**
     * @since 1.5.0
     *
     * @return bool
     */
    public function isNotifiedOfTeamDonations()
    {
        return (bool)$this->notify_of_team_donations;
    }

    /**
     * @since 1.5.0
     *
     * @return bool
     */
    public function isNotifiedOfFundraisersJoined()
    {
        return (bool)$this->notify_of_fundraisers;
    }

    /**
     * @param array $teamData
     *
     * @return static
     */
    public static function fromArray($teamData)
    {
        $team = new static();
        $team->validateArray($teamData);
        $team->setPropertiesFromArray($teamData);

        return $team;
    }

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'                        => $this->id,
			'campaign_id'               => $this->campaign_id,
			'owner_id'                  => $this->owner_id,
			'name'                      => $this->name,
			'story'                     => $this->story,
			'profile_image'             => $this->profile_image,
			'goal'                      => $this->goal,
			'access'                    => $this->access,
			'status'                    => $this->status,
			'date_created'              => $this->date_created,
			'notify_of_fundraisers'     => $this->notify_of_fundraisers,
			'notify_of_team_donations'  => $this->notify_of_team_donations,
		];
	}

    /**
     * Validate Team data array
     *
     * @param array $teamData
     */
    private function validateArray($teamData)
    {
        if (array_diff($this->getRequiredFields(), array_keys($teamData))) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__('To create a team, complete all required fields: %s', 'give-peer-to-peer'),
                    implode(', ', $this->getRequiredFields())
                )
            );
        }
    }


    /**
     * Save Team data
     * @return bool
     */
    public function save()
    {
        /**
         * @var TeamRepository $repository
         */
        $repository = give(TeamRepository::class);

        return $this->getId()
            ? $repository->saveTeam($this)
            : $repository->insertTeam($this);
    }
}
