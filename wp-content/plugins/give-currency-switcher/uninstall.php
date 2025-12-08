<?php
/**
 * Give Currency Switcher Uninstall
 *
 * @link              https://givewp.com
 * @since             1.0.0
 * @package           Give_Currency_Switcher
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete current version so activation runs if reactivated.
delete_option( 'give_currency_switcher_version' );

// Clear CRON jobs.
wp_clear_scheduled_hook( 'cs_exchange_rate_weekly_task', [ 'weekly' ] );
wp_clear_scheduled_hook( 'cs_exchange_rate_daily_task', [ 'daily' ] );
wp_clear_scheduled_hook( 'cs_exchange_rate_twicedaily_task', [ 'twicedaily' ] );
wp_clear_scheduled_hook( 'cs_exchange_rate_hourly_task', [ 'hourly' ] );


// @todo remove currency switcher settings
