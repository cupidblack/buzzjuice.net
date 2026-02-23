<?php

namespace GiveP2P\Blocks\FundraiserLeaderboard;

use GiveP2P\Blocks\FundraiserLeaderboard\App as FundraiserLeaderboardApp;

/**
 * @since 1.6.0
 */
class Shortcode
{

    protected $tag = 'p2p_fundraiser_leaderboard';

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
                'id'                    =>  '',
                'layout'                => 'grid',
                'columns'               => 'max',
                'align'                 => 'wide',
                'accent_color'          => "#28c77b",
                'per_page'              =>  8,
                'search'                =>  '',
                'offset'                =>  '',
                'show_avatar'           =>  true,
                'show_goal'             =>  true,
                'show_description'      =>  true,
                'show_pagination'       =>  true,
            ],
            $attributes,
            'p2p_fundraiser_leaderboard'
        );

        $boolean_attributes = [
            'show_description',
            'show_goal',
            'show_avatar',
            'show_pagination'
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
     * @since 1.6.0
     * Returns Shortcode markup
     **/

    public function renderCallback( $attributes ): string
    {
        $attributes = $this->parseAttributes( $attributes );

        return (new FundraiserLeaderboardApp())->getOutput($attributes);
    }
}
