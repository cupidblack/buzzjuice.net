<?php

namespace GiveP2P\Addon\Helpers;

use GiveP2P\P2P\Routes\Endpoint;

/**
 * Helper class responsible for loading add-on assets.
 *
 * @package     GiveP2P\Addon
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Assets
{
    /**
     * Load add-on backend assets.
     *
     * @since 1.0.0
     * @return void
     */
    public static function loadBackendAssets()
    {
        wp_enqueue_style(
            'give-p2p-style-backend',
            GIVE_P2P_URL . 'public/css/give-p2p-admin.css',
            [],
            GIVE_P2P_VERSION
        );

        wp_enqueue_script(
            'give-p2p-script-backend',
            GIVE_P2P_URL . 'public/js/give-p2p-admin.js',
            ['media', 'wp-color-picker', 'wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        wp_set_script_translations('give-p2p-script-backend', 'give-peer-to-peer');

        if (Environment::isCampaignsPage()) {
            wp_enqueue_script(
                'give-p2p-campaigns-app',
                GIVE_P2P_URL . 'public/js/give-p2p-campaigns-app.js',
                ['wp-element'],
                GIVE_P2P_VERSION,
                true
            );

            Language::localize('give-peer-to-peer');

            wp_enqueue_style(
                'give-p2p-campaigns-app',
                GIVE_P2P_URL . 'public/css/give-p2p-campaigns-app.css',
                [],
                GIVE_P2P_VERSION
            );
        }

        $currency = give_get_currency();

        wp_localize_script(
            'give-p2p-script-backend',
            'GiveP2P',
            [
                'apiRoot' => esc_url_raw(rest_url(Endpoint::ROUTE_NAMESPACE)),
                'apiNonce' => wp_create_nonce('wp_rest'),
                'giveRoot' => GIVE_PLUGIN_URL,
                'adminURL' => admin_url(),
                'locale' => str_replace('_', '-', get_locale()),
                'currency' => $currency,
                'currencySymbol' => give_currency_symbol($currency, true),
                'currencyPosition' => give_get_currency_position(),
                'thousands_separator' => give_get_price_thousand_separator(),
                'decimal_separator' => give_get_price_decimal_separator(),
                'editCampaignPage' => admin_url('edit.php?post_type=give_forms&page=p2p-edit-campaign'),
                'maxUploadSize' => wp_max_upload_size(),
                'SSL' => is_ssl(),
            ]
        );
    }

    /**
     * Load add-on front-end assets.
     *
     * @since 1.0.0
     * @return void
     */
    public static function loadFrontendAssets()
    {
        // Only load assets for front-end campaign pages
        if (!get_query_var('give_route') || strpos(get_query_var('give_route'), 'campaign/{campaign}') === false) {
            return;
        }

        wp_enqueue_script(
            'give-p2p-script-frontend',
            GIVE_P2P_URL . 'public/js/give-p2p.js',
            ['wp-i18n','wp-element'],
            GIVE_P2P_VERSION,
            true
        );

        Language::localize('give-peer-to-peer');

        $currency = give_get_currency();

        $shadowRootStylesheet = GIVE_P2P_URL . 'public/css/give-p2p.css';

        wp_localize_script(
            'give-p2p-script-frontend',
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
                'maxUploadSize' => wp_max_upload_size(),
                'shadowRootStylesheet' => $shadowRootStylesheet,
                'SSL' => is_ssl(),
            ]
        );

        // Preload the stylesheet used in the Shadow Root so it is ready.
        add_action('wp_head', function () use ($shadowRootStylesheet) {
            printf('<link rel="preload" href="%s" as="style">', $shadowRootStylesheet);
        }, 10);

        wp_enqueue_style(
            'give-p2p-font',
            'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap',
            false
        );
    }
}
