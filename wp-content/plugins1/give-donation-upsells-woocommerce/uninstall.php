<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 * @author     GiveWP
 *
 * @package    Give_WooCommerce_Addon
 */

// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}