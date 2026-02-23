<?php

namespace GiveP2P\Blocks\CampaignList;

use GiveP2P\Blocks\CampaignList\App as CampaignListApp;

/**
 * @since 1.6.0
 */
class Block
{

    /**
     * @since 1.6.0
     * Registers Campaign List block
     **/

    public function registerBlockType()
    {
        register_block_type(
            __DIR__ . '/resources/js/block.json',
            [
                'render_callback' => [$this, 'renderCallback'],
            ]
        );
    }

    /**
     * @since 1.6.0
     */

    public function renderCallback( $attributes )
    {
        return (new CampaignListApp())->getOutput( $attributes );
    }

    /**
     * @since 1.6.0
     * load block editor assets
     **/

    public function loadEditorAssets( )
    {

        wp_enqueue_script(
            'give-p2p-campaign-list-block',
            GIVE_P2P_URL . 'public/js/give-p2p-campaign-list-block.js',
            ['wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        wp_enqueue_style(
            'give-p2p-campaign-list-block',
            GIVE_P2P_URL . 'public/css//give-p2p-campaign-list-block.css',
            [],
            GIVE_P2P_VERSION
        );

        wp_set_script_translations('give-p2p-campaign-list-block', 'give-peer-to-peer');

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p-campaign-list.css';

        wp_localize_script(
            'give-p2p-campaign-list-block',
            'GiveP2PCampaignList',
            [
                'shadowRootStylesheet'  => $shadowRootStylesheet
            ]
        );
    }
}

