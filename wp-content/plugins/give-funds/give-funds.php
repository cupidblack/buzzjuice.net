<?php namespace GiveFunds;
use GiveFunds\Infrastructure\Environment;

/**
 * Plugin Name: Give - Funds and Designations
 * Plugin URI: https://givewp.com/addons/funds-and-designations/
 * Description: Give your donors the option of designating gifts to specific funds within the organization.
 * Version: 1.2.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: GiveWP
 * Author URI:  https://givewp.com/
 * Text Domain: give-funds
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) or exit;

// Add-on name
define( 'GIVE_FUNDS_ADDON_NAME', 'Funds and Designations' );
// Versions
define( 'GIVE_FUNDS_ADDON_VERSION', '1.2.0' );
define( 'GIVE_FUNDS_ADDON_MIN_GIVE_VERSION', '2.24.0' );
// Add-on paths
define( 'GIVE_FUNDS_ADDON_FILE', __FILE__ );
define( 'GIVE_FUNDS_ADDON_DIR', plugin_dir_path( GIVE_FUNDS_ADDON_FILE ) );
define( 'GIVE_FUNDS_ADDON_URL', plugin_dir_url( GIVE_FUNDS_ADDON_FILE ) );
define( 'GIVE_FUNDS_ADDON_BASENAME', plugin_basename( GIVE_FUNDS_ADDON_FILE ) );

// Register table names.
global $wpdb;
$wpdb->give_funds                  = "{$wpdb->prefix}give_funds";
$wpdb->give_fund_form_relationship = "{$wpdb->prefix}give_fund_form_relationship";

require GIVE_FUNDS_ADDON_DIR . 'vendor/autoload.php';

// Register activation actions
register_activation_hook( GIVE_FUNDS_ADDON_FILE, [ Activation::class, 'activateAddon' ] );

// Register the add-on service provider with the GiveWP core.
add_action(
	'before_give_init',
	function() {
		if (
			Environment::giveMinRequiredVersionCheck()
			&& Environment::revenueDatabaseTableExists()
		) {
			give()->registerServiceProvider( FundsServiceProvider::class );
		}
	}
);

add_action(
	'admin_init',
	function() {
		// Check to make sure GiveWP core is installed and compatible with this add-on.
		Environment::checkEnvironment();
	}
);
