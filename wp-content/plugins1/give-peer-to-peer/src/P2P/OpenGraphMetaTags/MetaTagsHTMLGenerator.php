<?php

namespace GiveP2P\P2P\OpenGraphMetaTags;

use GiveP2P\P2P\Repositories\CampaignRepository;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Presenters\Canonical_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Description_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Site_Name_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Title_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Type_Presenter;
use Yoast\WP\SEO\Presenters\Open_Graph\Url_Presenter;
use Yoast\WP\SEO\Presenters\Twitter\Card_Presenter as TwitterCardPresenter;
use Yoast\WP\SEO\Presenters\Twitter\Creator_Presenter as TwitterCardCreatorPresenter;
use Yoast\WP\SEO\Presenters\Twitter\Description_Presenter as TwitterCardDescriptionPresenter;
use Yoast\WP\SEO\Presenters\Twitter\Image_Presenter as TwitterCardImagePresenter;
use Yoast\WP\SEO\Presenters\Twitter\Site_Presenter as TwitterCardSitePresenter;
use Yoast\WP\SEO\Presenters\Twitter\Title_Presenter as TwitterCardTitlePresenter;

/**
 * class MetaTagsHTMLGenerator
 *
 * @since 1.6.2
 */
class MetaTagsHTMLGenerator
{
    /**
     * @since 1.6.0
     */
    private function isCampaignPage(): bool
    {
        // Only load assets for front-end campaign pages
        return get_query_var('give_route') &&
               strpos(get_query_var('give_route'), 'campaign/{campaign}') !== false;
    }

    /**
     * @since 1.6.0
     *
     * @return void
     */
    public function __invoke()
    {
        $campaign = give(CampaignRepository::class)
            ->getCampaignBySlug(get_query_var('give_campaign'));

        if (! $campaign) {
            return;
        }

        if (! $this->isCampaignPage()) {
            return;
        }

        $locale = esc_attr(get_locale());
        $campaignTitle = esc_attr($campaign->getTitle());
        $campaignURL = esc_url(home_url('/campaign/' . $campaign->getUrl()));
        $campaignImage = esc_url($campaign->getImage());
        $campaignShortDescription = esc_attr($campaign->getShortDescription());

        echo "<!-- P2P Campaign: Primary Meta Tags -->
            <meta name=\"title\" content=\"$campaignTitle\" class=\"p2p-seo-meta-tag\">
            <meta name=\"description\" content=\"$campaignShortDescription\" class=\"p2p-seo-meta-tag\">

            <!-- P2P Campaign: Open Graph / Facebook -->
            <meta property=\"og:locale\" content=\"$locale\" class=\"p2p-seo-meta-tag\">
            <meta property=\"og:type\" content=\"website\" class=\"p2p-seo-meta-tag\">
            <meta property=\"og:title\" content=\"$campaignTitle\" class=\"p2p-seo-meta-tag\">
            <meta property=\"og:url\" content=\"$campaignURL\" class=\"p2p-seo-meta-tag\">
            <meta property=\"og:image\" content=\"$campaignImage\" class=\"p2p-seo-meta-tag\">
            <meta property=\"og:description\" content=\"$campaignShortDescription\" class=\"p2p-seo-meta-tag\">

            <!-- P2P Campaign: Twitter -->
            <meta property=\"twitter:card\" content=\"summary_large_image\" class=\"p2p-seo-meta-tag\">
            <meta property=\"twitter:image\" content=\"$campaignImage\" class=\"p2p-seo-meta-tag\">
            <meta property=\"twitter:site\" content=\"$campaignURL\" class=\"p2p-seo-meta-tag\">
            <meta property=\"twitter:title\" content=\"$campaignTitle\" class=\"p2p-seo-meta-tag\">
            <meta property=\"twitter:description\" content=\"$campaignShortDescription\" class=\"p2p-seo-meta-tag\">
            ";
    }

    /**
     * @since 1.6.0
     *
     * @param array $presenters
     * @param Meta_Tags_Context|null $context
     */
    public function removeYoastMetaTagsFromCampaignLandingPage($presenters, $context): array
    {
        $campaign = give(CampaignRepository::class)
            ->getCampaignBySlug(get_query_var('give_campaign'));

        if (! $campaign) {
            return $presenters;
        }

        if (! $this->isCampaignPage() || ! $context || 'Home_Page' !== $context->page_type) {
            return $presenters;
        }

        foreach ($presenters as $index => $presenter) {
            switch (get_class($presenter)) {
                case Canonical_Presenter::class:
                case Locale_Presenter::class:
                case Type_Presenter::class:
                case Title_Presenter::class:
                case Url_Presenter::class:
                case Site_Name_Presenter::class:
                case Image_Presenter::class:
                case Description_Presenter::class:
                case TwitterCardPresenter::class:
                case TwitterCardImagePresenter::class:
                case TwitterCardTitlePresenter::class:
                case TwitterCardSitePresenter::class:
                case TwitterCardDescriptionPresenter::class:
                case TwitterCardCreatorPresenter::class:
                    unset($presenters[$index]);
                    break;
            }
        }

        return $presenters;
    }
}
