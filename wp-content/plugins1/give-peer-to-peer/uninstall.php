<?php

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Give file.
include_once GIVE_PLUGIN_DIR . 'give.php';

global $wpdb;

if ( give_is_setting_enabled( give_get_option( 'uninstall_on_delete' ) ) ) {

	// Remove all database tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_campaigns" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_teams" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_team_invitations" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_campaigns" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_fundraisers" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_sponsors" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}give_p2p_donation_source" );
}
