<?php
/**
 * Pro-specific install routines.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wpcode_plugin_activation', 'wpcode_pro_install_routines' );
add_action( 'wpcode_before_version_update', 'wpcode_pro_upgrade_routines' );

/**
 * Called when the plugin is activated to run pro-specific stuff like creating custom DB tables.
 *
 * @return void
 */
function wpcode_pro_install_routines() {
	$db = new WPCode_DB();
	$db->maybe_update_db();

	// Let's track when the pro plugin was first activated.
	$activated = get_option( 'ihaf_activated', array() );
	if ( ! is_array( $activated ) ) {
		$activated = array();
	}
	if ( empty( $activated['wpcode_pro'] ) ) {
		$activated['wpcode_pro'] = time();
		update_option( 'ihaf_activated', $activated );
	}

	// If the license class is not loaded let's bail.
	if ( ! class_exists( 'WPCode_License' ) ) {
		return;
	}

	// If we have a license key from the connect process, and we don't have a license key set, let's use it.
	$license = get_option( 'wpcode_connect', false );
	// Let's delete the connect option, if this fails for any reason it shouldn't block the activation process.
	delete_option( 'wpcode_connect' );
	if ( empty( $license ) ) {
		return;
	}
	// If the license instance is not set let's set it.
	if ( ! isset( wpcode()->license ) ) {
		wpcode()->license = new WPCode_License();
	}
	// If they already set a license key, ignore this.
	$license_key = wpcode()->license->get();
	if ( empty( $license_key ) ) {
		// Let's set the license key.
		wpcode()->license->verify_key( $license );
	}
}

/**
 * Run pro-specific upgrade routines.
 *
 * @param array $activated The value of the "ihaf_activated" option.
 *
 * @return void
 */
function wpcode_pro_upgrade_routines( $activated ) {
	if ( empty( $activated['version'] ) ) {
		// If no version is set this is the first install so let's skip.
		return;
	}

	if ( version_compare( $activated['version'], WPCODE_VERSION, '<' ) ) {
		// Let's run upgrade routines but only for the versions needed.
		if ( version_compare( $activated['version'], '2.0.7', '<' ) ) {
			// Upgrade to 2.0.7.
			wpcode_pro_update_2_0_7();
		}
		// Let's run upgrade routines but only for the versions needed.
		if ( version_compare( $activated['version'], '2.0.12', '<' ) ) {
			// Upgrade to 2.0.12.
			wpcode_pro_update_2_0_12();
		}
		// Let's run upgrade routines but only for the versions needed.
		if ( version_compare( $activated['version'], '2.2', '<' ) ) {
			// Upgrade to 2.2.
			wpcode_pro_update_2_2();
		}
	}
}

/**
 * Upgrade routine for 2.0.7.
 *
 * @return void
 */
function wpcode_pro_update_2_0_7() {
	// Let's reset the license cache to grab the addons data.
	// Deleting this option will prompt the plugin to update the key data the next time admin_init runs.
	delete_option( 'wpcode_license_updates' );
}

/**
 * Upgrade routine for 2.0.12.
 *
 * @return void
 */
function wpcode_pro_update_2_0_12() {
	// Reset the my-library cache.
	wpcode()->file_cache->delete( 'library/my-snippets' );
}

/**
 * Upgrade routine for 2.2.0
 *
 * @return void
 */
function wpcode_pro_update_2_2() {
	// Update the db table for revisions.
	$db = new WPCode_DB();
	$db->maybe_update_db();
}
