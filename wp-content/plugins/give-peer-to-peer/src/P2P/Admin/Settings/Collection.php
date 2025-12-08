<?php

namespace GiveP2P\P2P\Admin\Settings;

use GiveP2P\P2P\Admin\Contracts\AdminPageSettings;

/**
 * Fields Collection
 *
 * @package GiveP2P\P2P\Admin\Settings
 *
 * @since 1.0.0
 */
class Collection extends AdminPageSettings
{

    /**
     * @var Campaign
     */
    private $campaignSettings;
    /**
     * @var Team
     */
    private $teamSettings;
    /**
     * @var Fundraiser
     */
    private $fundraiserSettings;
    /**
     * @var Sponsor
     */
    private $sponsorSettings;

    /**
     * @param Campaign $campaignSettings
     * @param Team $teamSettings
     * @param Fundraiser $fundraiserSettings
     * @param Sponsor $sponsorSettings
     */
    public function __construct(
        Campaign $campaignSettings,
        Team $teamSettings,
        Fundraiser $fundraiserSettings,
        Sponsor $sponsorSettings
    ) {
        $this->campaignSettings = $campaignSettings;
        $this->teamSettings = $teamSettings;
        $this->fundraiserSettings = $fundraiserSettings;
        $this->sponsorSettings = $sponsorSettings;
    }

    /**
     * @return Campaign
     */
    public function campaign()
    {
        return $this->campaignSettings;
    }

    /**
     * @return Team
     */
    public function team()
    {
        return $this->teamSettings;
    }

    /**
     * @return Fundraiser
     */
    public function fundraiser()
    {
        return $this->fundraiserSettings;
    }

    /**
     * @return Sponsor
     */
    public function sponsor()
    {
        return $this->sponsorSettings;
    }

    /**
     * @inheritDoc
     */
    public function getFields()
    {
        return array_merge(
            $this->campaignSettings->getFields(),
            $this->teamSettings->getFields(),
            $this->fundraiserSettings->getFields(),
            $this->sponsorSettings->getFields(),
        );
    }

}
