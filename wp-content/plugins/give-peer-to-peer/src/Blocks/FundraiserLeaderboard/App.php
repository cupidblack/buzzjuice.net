<?php

namespace GiveP2P\Blocks\FundraiserLeaderboard;

use GiveP2P\P2P\Routes\Endpoint;


/**
 * @since 1.6.0
 */
class App
{
    /**
     *@since 1.6.0
     * Generates Fundraiser Leaderboard output
     **/

    public function getOutput( $attributes ): string
    {
        $this->loadAssets();

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p-fundraiser-leaderboard.css';

        ob_start();

        require $this->getTemplatePath();

        return ob_get_clean();
    }

    /**
     * @since 1.6.0
     * Get template path for Fundraiser Leaderboard component
     **/

    public function getTemplatePath(): string
    {
        return GIVE_P2P_DIR . '/src/Blocks/FundraiserLeaderboard/resources/views/fundraiserleaderboard.php';
    }

    /**
     *@since 1.6.0
     * Enqueue assets for Fundraiser Leaderboard
     **/

    public function loadAssets() : void
    {

        wp_enqueue_script(
            'give-p2p-fundraiser-leaderboard',
            GIVE_P2P_URL . 'public/js/give-p2p-fundraiser-leaderboard.js',

            ['wp-i18n','wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        wp_set_script_translations('give-p2p-fundraiser-leaderboard', 'give-peer-to-peer');

        wp_enqueue_style(
            'give-google-font-montserrat',
            'https://fonts.googleapis.com/css?family=Montserrat:400,500,500i,600,600i,700,700i&display=swap',
            [],
            null
        );

        $currency = give_get_currency();

        wp_localize_script(
            'give-p2p-fundraiser-leaderboard',
            'GiveP2P',
            [
                'apiRoot'               => esc_url_raw(rest_url(Endpoint::ROUTE_NAMESPACE)),
                'apiNonce'              => wp_create_nonce('wp_rest'),
                'locale'                => str_replace('_', '-', get_locale()),
                'currency'              => $currency,
                'currencySymbol'        => give_currency_symbol($currency, true),
                'currencyPosition'      => give_get_currency_position(),
                'thousandsSeparator'    => give_get_price_thousand_separator(),
                'decimalSeparator'      => give_get_price_decimal_separator(),
                'numberDecimals'        => give_get_price_decimals(),
            ]
        );
    }
}
