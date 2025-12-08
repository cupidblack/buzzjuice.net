<?php

namespace GiveP2P\P2P\Admin;

use GiveP2P\Addon\Helpers\Redirect;
use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Admin\Contracts\AdminPage;
use GiveP2P\P2P\Admin\Settings\Collection as FieldsCollection;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\ViewModels\Frontend\CampaignViewModel;

/**
 * EditCampaign Page
 * @package GiveP2P\P2P\Admin
 *
 * @since 1.0.0
 */
class EditCampaign extends AdminPage
{
    /**
     * Page slug
     */
    const PAGE_SLUG = 'p2p-edit-campaign';

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var array
     */
    private $campaignData;

    /**
     * @inheritDoc
     */
    public function registerPage()
    {
        add_submenu_page(
            null,
            esc_html__('Edit Peer-to-Peer Campaign', 'give-peer-to-peer'),
            null,
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    /**
     * @inheritDoc
     */
    public function renderPage()
    {
        /**
         * @var CampaignRepository $campaignRepository
         */
        $campaignRepository = give(CampaignRepository::class);

        // Get campaign
        $this->campaign = $campaignRepository->getCampaign(absint($_GET['id']));

        // Redirect to campaigns page if there is no campaign data
        if (!$this->campaign) {
            Redirect::to(admin_url('edit.php?post_type=give_forms&page=' . Campaigns::PAGE_SLUG));
        }

        $campaignViewModel = new CampaignViewModel($this->campaign);
        $this->campaignData = $campaignViewModel->exports();

        View::render(
            'P2P.admin/edit-campaign-container',
            [
                'campaign' => $this->campaign,
                'campaignData' => $this->campaignData,
                'content' => $this->getCurrentPage(),
                'currentTab' => isset($_GET['tab']) ? $_GET['tab'] : '',
            ]
        );
    }

    /**
     * Get current campaign page
     *
     * @return string
     */
    public function getCurrentPage()
    {
        $tab = isset($_GET['tab'])
            ? sanitize_text_field($_GET['tab'])
            : null;

        if (in_array($tab, ['teams', 'fundraisers'], true)) {
            return View::load(
                'P2P.admin/campaign-app',
                [
                    'campaign' => $this->campaign,
                    'campaignData' => $this->campaignData,
                    'teamId' => isset($_GET['team_id']) ? absint($_GET['team_id']) : null,
                    'screen' => $tab,
                ]
            );
        }

        $collection = give(FieldsCollection::class);

        return View::load(
            'P2P.admin/edit-campaign',
            [
                'campaign' => $this->campaign,
                'campaignData' => $this->campaignData,
                'campaignFields' => $collection->campaign()->getFieldsWithData($this->campaign),
                'teamFields' => $collection->team()->getFieldsWithData($this->campaign),
                'fundraiserFields' => $collection->fundraiser()->getFieldsWithData($this->campaign),
                'sponsorFields' => $collection->sponsor()->getFieldsWithData($this->campaign),
            ]
        );
    }
}
