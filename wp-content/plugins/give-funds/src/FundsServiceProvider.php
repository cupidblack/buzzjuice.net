<?php
namespace GiveFunds;

use Give\Framework\Migrations\MigrationsRegister;
use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider;
use GiveFunds\Admin\AddFundPage;
use GiveFunds\Admin\DonationFormsOptions;
use GiveFunds\Admin\DonationOptions;
use GiveFunds\Admin\EditFundPage;
use GiveFunds\Admin\FundOptions;
use GiveFunds\Admin\FundsExport;
use GiveFunds\Admin\FundsPage as FundsListPage;
use GiveFunds\Admin\ListTable\ListTable;
use GiveFunds\Admin\OverviewFund as OverviewFundPage;
use GiveFunds\Admin\ReportsPage;
use GiveFunds\Email\Tags;
use GiveFunds\Factories\Funds;
use GiveFunds\Form\SelectFund;
use GiveFunds\Infrastructure\ActivationBanner;
use GiveFunds\Infrastructure\Language;
use GiveFunds\Infrastructure\License;
use GiveFunds\Listeners\DeleteRelationshipOnFormDelete;
use GiveFunds\Listeners\SubscriptionPayment;
use GiveFunds\Migrations\AddFundIdColumnToRevenueTable;
use GiveFunds\Migrations\AddGeneralFund;
use GiveFunds\Migrations\AddPositionColumnToFundFormRelationshipTable;
use GiveFunds\Migrations\AssociateRevenueToDefaultFund;
use GiveFunds\Migrations\CreateFundFormRelationship;
use GiveFunds\Migrations\CreateFundsTable;
use GiveFunds\Migrations\RemoveConstraints;
use GiveFunds\Receipt\UpdateDonationReceipt;
use GiveFunds\Routes\DonationsRoute;
use GiveFunds\Routes\FundOverviewRoute;
use GiveFunds\Routes\FundPercentages;
use GiveFunds\Routes\ReassignFundRoute;
use GiveFunds\Routes\ReportsRoute;

/**
 * Service provider responsible for add-on initialization.
 *
 * @package     GiveFunds\Funds
 * @copyright   Copyright (c) 2020, GiveWP
 */
class FundsServiceProvider implements ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		give()->singleton( Funds::class );
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		$this->registerMigrations();
		$this->init();

		is_admin()
			? $this->loadBackend()
			: $this->loadFrontend();
	}


	/**
	 * Load add-on assets on all.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init() {
		// Run this hook before give wp core
		// @see src/Revenue/RevenueServiceProvider.php::31
		Hooks::addAction( 'give_insert_payment', DonationHandler::class, 'handle', 998, 2 );
		Hooks::addAction( 'give_subscription_inserted', SubscriptionPayment::class, 'handleSubscription', 20, 2 );
		Hooks::addAction( 'give_recurring_add_subscription_payment', SubscriptionPayment::class, 'handleRenewal', 20, 2 );
		Hooks::addAction( 'init', Language::class, 'load' );
        // Rest routes
        Hooks::addAction('rest_api_init', FundOverviewRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', DonationsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', ReassignFundRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', ReportsRoute::class, 'registerRoute');
        Hooks::addAction('rest_api_init', FundPercentages::class, 'registerRoute');
        // Register Email tags
        Hooks::addFilter('give_email_tags', Tags::class, 'registerEmailTags');
        Hooks::addFilter('give_email_preview_template_tags', Tags::class, 'handlePreviewEmailTags');

        // Register List Table Columns
        Hooks::addFilter('givewp_donations_list_table', ListTable::class, 'registerDonationsListTableColumns');
    }


	/**
	 * Load add-on backend assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function loadBackend() {
		// Handle forigen key constraints
		Hooks::addAction( 'delete_post', DeleteRelationshipOnFormDelete::class );

		// Handle pages data
		Hooks::addAction( 'admin_init', FundsListPage::class, 'handleData' );
		Hooks::addAction( 'admin_init', AddFundPage::class, 'handleData' );
		Hooks::addAction( 'admin_init', EditFundPage::class, 'handleData' );

		// Register "Fund Options" section
		Hooks::addFilter( 'give_metabox_form_data_settings', FundOptions::class, 'registerSection', 10, 2 );

		// Add options to the View Donation page
		Hooks::addAction( 'give_view_donation_details_totals_after', DonationOptions::class, 'renderDropdown', 900 );

		// Handle funds on donation update
		Hooks::addAction( 'give_updated_edited_donation', DonationOptions::class, 'handleData' );
		// Donation bulk actions
		Hooks::addFilter( 'give_payments_table_bulk_actions', DonationOptions::class, 'registerBulkActions' );
		Hooks::addAction( 'give_payments_table_do_bulk_action', DonationOptions::class, 'handleBulkActions', 10, 2 );
		// Add Fund column in Donations list table
		Hooks::addFilter( 'give_payments_table_columns', DonationOptions::class, 'addFundColumn' );
		Hooks::addFilter( 'give_payments_table_column', DonationOptions::class, 'filterFundColumn', 10, 3 );

		// Donation forms bulk actions
		Hooks::addFilter( 'bulk_actions-edit-give_forms', DonationFormsOptions::class, 'registerBulkActions' );
		Hooks::addFilter( 'handle_bulk_actions-edit-give_forms', DonationFormsOptions::class, 'handleBulkActions', 10, 3 );

		// Handle selected form funds
		Hooks::addAction( 'save_post_give_forms', FundOptions::class, 'handleFormSelectedFunds', 10, 2 );
		// Get associated funds from give_funds_form_relationship table insted of give_formmeta
		foreach ( [ 'give_funds_admin_choice_field_value', 'give_funds_donor_choice_field_value' ] as $hook ) {
			Hooks::addFilter( $hook, FundOptions::class, 'getFieldValue', 10, 3 );
		}

		// Show Receipt info
		Hooks::addAction( 'give_payment_receipt_after', UpdateDonationReceipt::class, 'renderRow', 10, 2 );
		Hooks::addAction( 'give_new_receipt', UpdateDonationReceipt::class, 'renderRowSequoiaTemplate' );

		// Check license and show activation banner
		Hooks::addAction( 'admin_init', License::class, 'check' );
		Hooks::addAction( 'admin_init', ActivationBanner::class, 'show', 20 );

		Hooks::addAction( 'admin_enqueue_scripts', Assets::class, 'loadBackendAssets' );

		// Register pages
		Hooks::addAction( 'admin_menu', FundsListPage::class, 'registerPage' );
		Hooks::addAction( 'admin_menu', AddFundPage::class, 'registerPage' );
		Hooks::addAction( 'admin_menu', EditFundPage::class, 'registerPage' );
		Hooks::addAction( 'admin_menu', OverviewFundPage::class, 'registerPage' );
		Hooks::addAction( 'admin_menu', ReportsPage::class, 'registerPage' );

		// Export integration
		Hooks::addAction( 'give_export_donation_fields', FundsExport::class, 'renderOptions', 100 );
		Hooks::addFilter( 'give_export_donation_get_columns_name', FundsExport::class, 'filterColumns', 10, 2 );
		Hooks::addFilter( 'give_export_donation_data', FundsExport::class, 'filterData', 10, 3 );

		// Import integration
		Hooks::addFilter( 'give_import_donations_options', DonationHandler::class, 'importFields' );
		Hooks::addAction( 'give_import_after_import_payment', DonationHandler::class, 'handleImport', 10, 3 );
	}

	/**
	 * Load add-on front-end assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function loadFrontend() {
		// Add select dropdown to the form
		Hooks::addAction( 'give_after_donation_levels', SelectFund::class, 'renderDropdown' );

		// Load front-end assets.
		Hooks::addAction( 'wp_enqueue_scripts', Assets::class, 'loadFrontendAssets' );
	}

	/**
	 * Register migrations
	 *
	 * @since 1.0.0
	 */
	private function registerMigrations() {
		/* @var MigrationsRegister $migrationRegister */
		$migrationRegister = give( MigrationsRegister::class );

		$migrationRegister->addMigration( CreateFundsTable::class );
		$migrationRegister->addMigration( CreateFundFormRelationship::class );
		$migrationRegister->addMigration( AddGeneralFund::class );
		$migrationRegister->addMigration( AddFundIdColumnToRevenueTable::class );
		$migrationRegister->addMigration( AssociateRevenueToDefaultFund::class );
		$migrationRegister->addMigration( RemoveConstraints::class );
		$migrationRegister->addMigration( AddPositionColumnToFundFormRelationshipTable::class );
	}

}
