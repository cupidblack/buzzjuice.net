<?php

namespace GiveP2P\Blocks\FundraiserLeaderboard;

use GiveP2P\Blocks\FundraiserLeaderboard\App as FundraiserLeaderboardApp;


/**
 * @since 1.6.0
 */
class Block
{

    /**
     * @since 1.6.0
     * Registers Fundraiser Leaderboard block
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
        return (new FundraiserLeaderboardApp())->getOutput( $attributes );
    }

    /**
     * @since 1.6.0
     * load block editor assets
     **/

    public function loadEditorAssets( )
    {
        wp_enqueue_script(
            'give-p2p-fundraiser-leaderboard-block',
            GIVE_P2P_URL . 'public/js/give-p2p-fundraiser-leaderboard-block.js',
            ['wp-element'],
            GIVE_P2P_VERSION,
            true
        );


        wp_enqueue_style(
            'give-p2p-fundraiser-leaderboard-block',
            GIVE_P2P_URL . 'public/css//give-p2p-fundraiser-leaderboard-block.css',
            [],
            GIVE_P2P_VERSION
        );

        wp_set_script_translations('give-p2p-fundraiser-leaderboard-block', 'give-peer-to-peer');

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p-fundraiser-leaderboard.css';

        wp_localize_script(
            'give-p2p-fundraiser-leaderboard-block',
            'GiveP2PFundraiserLeaderboard',
            [
                'shadowRootStylesheet' => $shadowRootStylesheet
            ]
        );
    }
}

