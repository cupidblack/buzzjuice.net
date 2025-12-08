<?php

namespace GiveP2P\Blocks;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;
use GiveP2P\Blocks\CampaignHighlight\Block as CampaignHighlightBlock;
use GiveP2P\Blocks\CampaignHighlight\Shortcode as CampaignHighlightShortcode;
use GiveP2P\Blocks\CampaignList\Block as CampaignListBlock;
use GiveP2P\Blocks\CampaignList\Shortcode as CampaignListShortcode;
use GiveP2P\Blocks\FundraiserLeaderboard\Block as FundraiserLeaderboardBlock;
use GiveP2P\Blocks\FundraiserLeaderboard\Shortcode as FundraiserLeaderboardShortcode;
use GiveP2P\Blocks\TeamLeaderboard\Block as TeamLeaderboardBlock;
use GiveP2P\Blocks\TeamLeaderboard\Shortcode as TeamLeaderboardShortcode;


/**
 * @since 1.6.0
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     *
     * @since 1.6.0
     */
    public function register():void
    {}

    /**
     * @inheritDoc
     *
     * @since 1.6.0
     */
    public function boot():void
    {

        Hooks::addAction('init', FundraiserLeaderboardShortcode::class, 'addShortcode');
        Hooks::addAction('init', FundraiserLeaderboardBlock::class, 'registerBlockType');
        Hooks::addAction('enqueue_block_editor_assets', FundraiserLeaderboardBlock::class, 'loadEditorAssets');

        Hooks::addAction('init', TeamLeaderboardShortcode::class, 'addShortcode');
        Hooks::addAction('init', TeamLeaderboardBlock::class, 'registerBlockType');
        Hooks::addAction('enqueue_block_editor_assets', TeamLeaderboardBlock::class, 'loadEditorAssets');

        Hooks::addAction('init', CampaignListShortcode::class, 'addShortcode');
        Hooks::addAction('init', CampaignListBlock::class, 'registerBlockType');
        Hooks::addAction('enqueue_block_editor_assets', CampaignListBlock::class, 'loadEditorAssets');

        Hooks::addAction('init', CampaignHighlightShortcode::class, 'addShortcode');
        Hooks::addAction('init', CampaignHighlightBlock::class, 'registerBlockType');
        Hooks::addAction('enqueue_block_editor_assets', CampaignHighlightBlock::class, 'loadEditorAssets');


    }
}

