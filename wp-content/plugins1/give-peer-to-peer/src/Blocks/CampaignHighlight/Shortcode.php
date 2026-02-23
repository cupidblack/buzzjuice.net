<?php

namespace GiveP2P\Blocks\CampaignHighlight;

use GiveP2P\Blocks\CampaignHighlight\App as CampaignHighlightApp;

/**
 * @since 1.6.0
 */
class Shortcode
{
    protected $tag = 'p2p_campaign_highlight';

    /**
     * @since 1.6.0
     * Registers P2P Fundraiser Leaderboard Shortcode
     **/

    public function addShortcode()
    {
        add_shortcode($this->tag, [$this, 'renderCallback']);
    }

    /**
     * @since 1.6.0
     * Parse shortcode attributes
     */

    public function parseAttributes( $attributes ): array
    {
        $attributes = shortcode_atts(
            [
                'id'                    =>  "",
                'accent_color'          =>  '#28c77b',
                'show_avatar'           =>  true,
                'show_goal'             =>  true,
                'show_campaign_info'    =>  true,
                'show_description'      =>  true,
            ],
            $attributes,
            'p2p_campaign_highlight'
        );

        $boolean_attributes = [
            'show_campaign_info',
            'show_goal',
            'show_avatar',
            'show_description'
        ];

        // Converts to boolean.
        // Prevents condition check against boolean value.
        foreach ( $boolean_attributes as $attribute ) {
            if ( is_numeric( $attributes[ $attribute ] ) ) {
                $attributes[ $attribute ] = (bool) $attributes[ $attribute ];
            }

            $attributes[ $attribute ] = filter_var( $attributes[ $attribute ], FILTER_VALIDATE_BOOLEAN );
        }

        return $attributes;
    }

    /**
     *
     * @since 1.6.0
     **/
    public function renderCallback( $attributes ): string
    {
        $attributes = $this->parseAttributes( $attributes );

        return (new CampaignHighlightApp())->getOutput( $attributes );
    }
}
