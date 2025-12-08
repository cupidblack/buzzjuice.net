<?php
! defined( 'ABSPATH' ) && exit();

/*
 * Plugin Name: TotalPoll – Pro
 * Plugin URI: https://totalsuite.net/products/totalpoll/
 * Description: TotalPoll is a responsive and customizable poll plugin that will help you create voting contest, competition, image poll, simple poll.
 * Version: 4.12.0
 * Author: TotalSuite
 * Author URI: https://totalsuite.net/
 * Text Domain: totalpoll
 * Domain Path: languages
 * Requires at least: 4.8
 * Requires PHP: 5.6
 * Tested up to: 6.8.2
 */
update_option( 'totalpoll_license_key', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' );
update_option( 'totalpoll_license_status', true );
update_option( 'totalpoll_license_email', 'noreply@gmail.com' );

if ( defined( 'TOTALPOLL_ROOT' ) ) {
	function _totalpoll_pro_lite_check() {
		$installedPlugins = get_plugins();

		if ( array_key_exists( 'totalpoll-lite/plugin.php', $installedPlugins ) &&
		     array_key_exists( 'totalpoll/plugin.php', $installedPlugins ) ) {
			deactivate_plugins( 'totalpoll-lite/plugin.php', true );
			activate_plugin( 'totalpoll/plugin.php', true );
		}
	}

	add_action( 'shutdown', '_totalpoll_pro_lite_check' );

	return;
}


// Root plugin file name
define( 'TOTALPOLL_ROOT', __FILE__ );

// TotalPoll environment
$env = require dirname( __FILE__ ) . '/env.php';

// Include plugin setup
require_once dirname( __FILE__ ) . '/setup.php';

// Setup
$plugin = new TotalPollSetup( $env );

// Oh yeah, we're up and running!
