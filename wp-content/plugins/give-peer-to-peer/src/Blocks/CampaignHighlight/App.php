<?php

namespace GiveP2P\Blocks\CampaignHighlight;


use GiveP2P\P2P\Routes\Endpoint;

/**
 * @since 1.6.0
 */
class App
{

    /**
     *@since 1.6.0
     * Generates Campaign output
     **/

    public function getOutput( $attributes ): string
    {
        $this->loadAssets();

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p-campaign-highlight.css';

        ob_start();

        require $this->getTemplatePath();

        return ob_get_clean();
    }

    /**
     * @since 1.6.0
     * Get template path for Campaign component
     **/

    public function getTemplatePath(): string
    {
        return GIVE_P2P_DIR . '/src/Blocks/CampaignHighlight/resources/views/campaignhighlight.php';
    }


    /**
     *@since 1.6.0
     * Enqueue assets for Campaign
     **/

    public function loadAssets() : void
    {
        $currency = give_get_currency();

        wp_enqueue_script(
            'give-p2p-campaign-highlight',
            GIVE_P2P_URL . 'public/js/give-p2p-campaign-highlight.js',

            ['wp-i18n','wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        wp_localize_script(
            'give-p2p-campaign-highlight',
            'GiveP2P',
            [
                'apiRoot' => esc_url_raw(rest_url(Endpoint::ROUTE_NAMESPACE)),
                'apiNonce' => wp_create_nonce('wp_rest'),
                'locale' => str_replace('_', '-', get_locale()),
                'currency' => $currency,
                'currencySymbol' => give_currency_symbol($currency, true),
                'currencyPosition' => give_get_currency_position(),
                'thousandsSeparator' => give_get_price_thousand_separator(),
                'decimalSeparator' => give_get_price_decimal_separator(),
                'numberDecimals' => give_get_price_decimals(),
            ]
        );

        wp_set_script_translations('give-p2p-campaign-highlight', 'give-peer-to-peer');

        wp_enqueue_style(
            'give-google-font-montserrat',
            'https://fonts.googleapis.com/css?family=Montserrat:500,500i,600,600i,700,700i&display=swap',
            [],
            null
        );
    }
}
