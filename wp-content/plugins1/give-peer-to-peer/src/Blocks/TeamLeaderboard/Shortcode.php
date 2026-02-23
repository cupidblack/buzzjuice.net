<?php

namespace GiveP2P\Blocks\TeamLeaderboard;

use GiveP2P\Blocks\TeamLeaderboard\App as TeamLeaderboardApp;

/**
 * @since 1.6.0
 */
class Shortcode
{
    protected $tag = 'p2p_team_leaderboard';


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
                'layout'                =>  'grid',
                'columns'               =>  'max',
                'align'                 =>  'wide',
                'accent_color'          =>  "#28c77b",
                'per_page'              =>  8,
                'offset'                =>  '',
                'show_avatar'           =>  true,
                'show_goal'             =>  true,
                'show_team_info'        =>  true,
                'show_pagination'       =>  true,
            ],
            $attributes,
            'p2p_team_leaderboard'
        );

        $boolean_attributes = [
            'show_team_info',
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
     *
     * @since 1.6.0
     **/
    public function renderCallback( $attributes )
    {
        $attributes = $this->parseAttributes( $attributes );

        return (new TeamLeaderboardApp())->getOutput($attributes);
    }
}
