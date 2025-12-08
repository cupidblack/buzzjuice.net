<?php

namespace GiveP2P\P2P;

use Give\Framework\Migrations\MigrationsRegister;
use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;
use GiveP2P\P2P\Admin\AddCampaign;
use GiveP2P\P2P\Admin\Campaigns;
use GiveP2P\P2P\Admin\EditCampaign;
use GiveP2P\P2P\Admin\Export;
use GiveP2P\P2P\Controllers\Admin\AddCampaignController;
use GiveP2P\P2P\Controllers\Admin\EditCampaignController;
use GiveP2P\P2P\Migrations\AddNotifyOfDonationsColumn;
use GiveP2P\P2P\Migrations\AddNotifyOfFundraisersColumn;
use GiveP2P\P2P\Migrations\AddNotifyOfTeamDonationsColumn;
use GiveP2P\P2P\Migrations\AddRegistrationDigestColumn;
use GiveP2P\P2P\Migrations\AddTeamsRegistrationColumn;
use GiveP2P\P2P\Migrations\CreateCampaignsTable;
use GiveP2P\P2P\Migrations\CreateDonationSourceTable;
use GiveP2P\P2P\Migrations\CreateFundraisersTable;
use GiveP2P\P2P\Migrations\CreateSponsorsTable;
use GiveP2P\P2P\Migrations\CreateTeamInvitationsTable;
use GiveP2P\P2P\Migrations\CreateTeamsTable;
use GiveP2P\P2P\Migrations\ModifyTeamDateCreatedColumn;
use GiveP2P\P2P\Migrations\UpdateDonationSourceAnonymousColumn;
use GiveP2P\P2P\OpenGraphMetaTags\MetaTagsHTMLGenerator;
use GiveP2P\P2P\Receipt\DonationReceipt;
use GiveP2P\P2P\Routes\CloneCampaignRoute;
use GiveP2P\P2P\Routes\CreateFundraiserJoinTeamRoute;
use GiveP2P\P2P\Routes\CreateFundraiserProfileRoute;
use GiveP2P\P2P\Routes\CreateTeamRoute;
use GiveP2P\P2P\Routes\EditCampaignUrlRoute;
use GiveP2P\P2P\Routes\FundraiserCanJoinTeamRoute;
use GiveP2P\P2P\Routes\FundraiserJoinTeamRoute;
use GiveP2P\P2P\Routes\FundraiserLogin;
use GiveP2P\P2P\Routes\GetAdminEmailsRoute;
use GiveP2P\P2P\Routes\GetCampaign;
use GiveP2P\P2P\Routes\GetCampaignFundraisersSearch;
use GiveP2P\P2P\Routes\GetCampaignsRoute;
use GiveP2P\P2P\Routes\GetCampaignTeamsSearch;
use GiveP2P\P2P\Routes\GetCampaignTopDonorsRoute;
use GiveP2P\P2P\Routes\GetEmailSettingsRoute;
use GiveP2P\P2P\Routes\GetFundraiserInfo;
use GiveP2P\P2P\Routes\GetFundraiserRoute;
use GiveP2P\P2P\Routes\GetFundraisersRoute;
use GiveP2P\P2P\Routes\GetFundraisersTopDonorsRoute;
use GiveP2P\P2P\Routes\GetTeamFundraisersRoute;
use GiveP2P\P2P\Routes\GetTeamRoute;
use GiveP2P\P2P\Routes\GetTeamsRoute;
use GiveP2P\P2P\Routes\GetTeamTopDonorsRoute;
use GiveP2P\P2P\Routes\GetUserInfoRoute;
use GiveP2P\P2P\Routes\RegisterFundraiserRoute;
use GiveP2P\P2P\Routes\RegisterJoinTeamRoute;
use GiveP2P\P2P\Routes\SendPasswordResetEmail;
use GiveP2P\P2P\Routes\SendTeamInvitationEmails;
use GiveP2P\P2P\Routes\UpdateFundraiserApprovalRoute;
use GiveP2P\P2P\Routes\UpdateFundraiserProfileRoute;
use GiveP2P\P2P\Routes\UpdateTeamApprovalRoute;
use GiveP2P\P2P\Routes\UpdateTeamRoute;

/**
 * P2P ServiceProvider Class
 * @package GiveP2P\P2P
 *
 * @since   1.0.0
 */
class ServiceProvider implements GiveServiceProvider
{

    /**
     * @inheritDoc
     */
    public function register()
    {
        global $wpdb;
        $wpdb->give_p2p_teams = "{$wpdb->prefix}give_p2p_teams";
        $wpdb->give_p2p_team_invitations = "{$wpdb->prefix}give_p2p_team_invitations";
        $wpdb->give_p2p_campaigns = "{$wpdb->prefix}give_p2p_campaigns";
        $wpdb->give_p2p_fundraisers = "{$wpdb->prefix}give_p2p_fundraisers";
        $wpdb->give_p2p_sponsors = "{$wpdb->prefix}give_p2p_sponsors";
        $wpdb->give_p2p_donation_source = "{$wpdb->prefix}give_p2p_donation_source";

        give()->singleton(Mailer::class, function () {
            return new Mailer(trailingslashit(plugin_dir_path(__FILE__) . 'resources/views/email'));
        });
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        include 'config/routes.php';
        $this->registerMigrations();

        $this->init();

        if (is_admin()) {
            $this->loadBackend();
        }

        Hooks::addAction('give_daily_scheduled_events', Commands\SendAdminRegistrationDigestEmail::class);

        Hooks::addAction('delete_user', Commands\PreventFundraiserUserDeletion::class);

        // Integrate with the Donation Form screen
        Hooks::addAction('edit_form_top', Commands\AddDonationFormNotice::class);

        // Integrate with the Legacy Form
        Hooks::addAction('give_fields_donation_form', Commands\AddCustomFields::class);
        Hooks::addAction('give_insert_payment', Commands\InsertDonationSource::class, '__invoke', 999, 1);
        Hooks::addAction('updated_donation_meta', Commands\SyncDonorWithDonation::class, '__invoke', 10, 4);

        Hooks::addFilter('wpseo_frontend_presenters', MetaTagsHTMLGenerator::class, 'removeYoastMetaTagsFromCampaignLandingPage', 9, 2);
        Hooks::addAction('wp_head', MetaTagsHTMLGenerator::class);
    }

    /**
     * Register P2P Campaign Migrations
     */
    private function registerMigrations()
    {
        give(MigrationsRegister::class)->addMigrations(
            [
                CreateCampaignsTable::class,
                CreateFundraisersTable::class,
                CreateSponsorsTable::class,
                CreateTeamsTable::class,
                CreateTeamInvitationsTable::class,
                CreateDonationSourceTable::class,
                AddRegistrationDigestColumn::class,
                ModifyTeamDateCreatedColumn::class,
                AddTeamsRegistrationColumn::class,
                AddNotifyOfDonationsColumn::class,
                AddNotifyOfFundraisersColumn::class,
                AddNotifyOfTeamDonationsColumn::class,
                UpdateDonationSourceAnonymousColumn::class,
            ]
        );
    }

    private function init()
    {
        // Rest routes
        Hooks::addAction('rest_api_init', GetCampaign::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetCampaignsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetFundraiserInfo::class, 'registerRoute');
        Hooks::addAction('rest_api_init', FundraiserLogin::class, 'registerRoute');
        Hooks::addAction('rest_api_init', FundraiserCanJoinTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', FundraiserJoinTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', CreateTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', UpdateTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetTeamsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', EditCampaignUrlRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetFundraisersRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetFundraiserRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', RegisterFundraiserRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', RegisterJoinTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', UpdateFundraiserProfileRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', UpdateTeamApprovalRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', UpdateFundraiserApprovalRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetCampaignTopDonorsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetTeamTopDonorsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetFundraisersTopDonorsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetCampaignTeamsSearch::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetCampaignFundraisersSearch::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetTeamFundraisersRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', SendTeamInvitationEmails::class, 'registerRoute');
        Hooks::addAction('rest_api_init', SendPasswordResetEmail::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetUserInfoRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', CreateFundraiserProfileRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', CreateFundraiserJoinTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', CloneCampaignRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', GetAdminEmailsRoute::class, 'registerRoute');

        Hooks::addAction('rest_api_init', Routes\Admin\CreateTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\UpdateTeamRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\UpdateTeamCaptainRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\AddTeamCaptainRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\SendTeamInvitationEmailsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\CreateWPUserRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\CreateFundraiserRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', Routes\Admin\UpdateFundraiserRoute::class, 'registerRoute');
    }

    /**
     * Load domain backend
     */
    private function loadBackend()
    {
        // Save Campaign Data
        Hooks::addAction('admin_init', AddCampaignController::class, 'handleData');
        Hooks::addAction('admin_init', EditCampaignController::class, 'handleData');

        // Register pages
        Hooks::addAction('admin_menu', Campaigns::class, 'registerPage');
        Hooks::addAction('admin_menu', AddCampaign::class, 'registerPage');
        Hooks::addAction('admin_menu', EditCampaign::class, 'registerPage');

        // Receipt info
        Hooks::addAction('give_payment_receipt_after', DonationReceipt::class, 'showInfoLegacyTemplate', 10, 2);
        Hooks::addAction('give_new_receipt', DonationReceipt::class, 'showInfoSequoiaTemplate');

        // Export data
        Hooks::addAction('give_export_donation_fields', Export::class, 'renderOptions', 100);
        Hooks::addFilter('give_export_donation_get_columns_name', Export::class, 'filterColumns', 20, 2);
        Hooks::addFilter('give_export_donation_data', Export::class, 'filterData', 10, 3);
        Hooks::addFilter('give_export_donations_get_custom_fields', Export::class, 'filterCustomFields');
    }
}
