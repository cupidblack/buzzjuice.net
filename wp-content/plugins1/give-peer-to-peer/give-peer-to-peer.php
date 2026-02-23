<?php

namespace GiveP2P;

use GiveP2P\Addon\Helpers\Activation;
use GiveP2P\Addon\Helpers\Environment;

/**
 * Plugin Name:         Give - Peer-to-Peer
 * Plugin URI:          https://givewp.com/addons/peer-to-peer/
 * Description:         Peer-to-Peer is a crowdfunding strategy which provides a means of decentralizing the fundraising for a campaign
 * Version:             1.6.6
 * Requires at least:   5.8
 * Requires PHP:        7.0
 * Author:              GiveWP
 * Author URI:          https://givewp.com/
 * Text Domain:         give-peer-to-peer
 * Domain Path:         /languages
 */
defined('ABSPATH') or exit;

// Add-on name
define('GIVE_P2P_NAME', 'Give Peer-to-Peer');

// Versions.php
define('GIVE_P2P_VERSION', '1.6.6');
define('GIVE_P2P_MIN_GIVE_VERSION', '2.27.3');

// Add-on paths
define('GIVE_P2P_FILE', __FILE__);
define('GIVE_P2P_DIR', plugin_dir_path(GIVE_P2P_FILE));
define('GIVE_P2P_URL', plugin_dir_url(GIVE_P2P_FILE));
define('GIVE_P2P_BASENAME', plugin_basename(GIVE_P2P_FILE));

require __DIR__ . '/vendor/autoload.php';

// Activate add-on hook.
register_activation_hook(GIVE_P2P_FILE, [Activation::class, 'activateAddon']);
// Deactivate add-on hook.
register_deactivation_hook(GIVE_P2P_FILE, [Activation::class, 'deactivateAddon']);
// Uninstall add-on hook.
register_uninstall_hook(GIVE_P2P_FILE, [Activation::class, 'uninstallAddon']);

/**
 * Re-trigger add-on activation when GiveWP is activated.
 */
add_action('give_upgrades', [Activation::class, 'activateAddon']);

// Register the add-on service provider with the GiveWP core.
add_action(
    'before_give_init',
    function () {
        // Check Give min required version.
        if (Environment::giveMinRequiredVersionCheck()) {
            give()->registerServiceProvider(Addon\ServiceProvider::class);
            give()->registerServiceProvider(Campaigns\ServiceProvider::class);
            give()->registerServiceProvider(P2P\ServiceProvider::class);
            give()->registerServiceProvider(Routing\ServiceProvider::class);
            give()->registerServiceProvider(Donations\ServiceProvider::class);
            give()->registerServiceProvider(Email\ServiceProvider::class);
            give()->registerServiceProvider(Exports\ServiceProvider::class);
            give()->registerServiceProvider(Reallocation\ServiceProvider::class);
            give()->registerServiceProvider(Blocks\ServiceProvider::class);
        }
    }
);

// Check to make sure GiveWP core is installed and compatible with this add-on.
add_action('admin_init', [Environment::class, 'checkEnvironment']);
