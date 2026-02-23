<?php

namespace GiveP2P\Blocks\CampaignHighlight;

use GiveP2P\Blocks\CampaignHighlight\App as CampaignHighlightApp;

/**
 * @since 1.6.0
 */
class Block
{

    /**
     * @since 1.6.0
     * Registers Campaign  block
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
        return (new CampaignHighlightApp())->getOutput( $attributes );
    }

    /**
     * @since 1.6.0
     * load block editor assets
     **/

    public function loadEditorAssets()
    {
        wp_enqueue_script(
            'give-p2p-campaign-highlight-block',
            GIVE_P2P_URL . 'public/js/give-p2p-campaign-highlight-block.js',
            ['wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        wp_set_script_translations('give-p2p-campaign-highlight-block', 'give-peer-to-peer');

        wp_enqueue_style(
            'give-p2p-campaign-block',
            GIVE_P2P_URL . 'public/css/give-p2p-campaign-highlight-block.css',
            [],
            GIVE_P2P_VERSION
        );

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p-campaign-highlight.css';

        wp_localize_script(
            'give-p2p-campaign-highlight-block',
            'GiveP2PCampaignHighlight',
            [
                'shadowRootStylesheet'  => $shadowRootStylesheet
            ]
        );
    }
}

