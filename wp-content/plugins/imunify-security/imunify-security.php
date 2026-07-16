<?php
/**
 * Plugin Name: Imunify Security
 * Plugin URI: https://imunify360.com/imunify-security-wp-plugin/
 * Description: Imunify Security WordPress plugin is a comprehensive tool offering malware scanning, firewall protection, and intrusion detection for WordPress websites.
 * Version: 4.0.1
 * Requires at least: 5.0.0
 * Requires PHP: 5.6
 * Author: CloudLinux
 * Author URI: https://www.cloudlinux.com
 * Text Domain: imunify-security
 * Domain Path: /languages
 * Licence: CloudLinux Commercial License
 *
 * Copyright 2010-2026 CloudLinux
 */

use CloudLinux\Imunify\App\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'IMUNIFY_SECURITY_SLUG', 'imunify-security' );
define( 'IMUNIFY_SECURITY_PATH', dirname( __FILE__ ) );
define( 'IMUNIFY_SECURITY_VERSION', '4.0.1' );
define( 'IMUNIFY_SECURITY_FILE_PATH', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefixes = array(
			'CloudLinux\\Imunify\\Composer\\Semver\\' => IMUNIFY_SECURITY_PATH . '/lib/CloudLinux/Imunify/Composer/Semver/',
			'CloudLinux\\Imunify\\'                   => IMUNIFY_SECURITY_PATH . '/inc/',
		);

		foreach ( $prefixes as $prefix => $base_dir ) {
			if ( 0 === strpos( $class, $prefix ) ) {
				$relative_class = substr( $class, strlen( $prefix ) );
				$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
				// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( @file_exists( $file ) ) {
					include_once $file;
				}
				break;
			}
		}
	}
);

register_activation_hook(
	__FILE__,
	array( \CloudLinux\Imunify\App\Bot\LifecycleHooks::class, 'onActivate' )
);

register_deactivation_hook(
	__FILE__,
	array( \CloudLinux\Imunify\App\Bot\LifecycleHooks::class, 'onDeactivate' )
);

register_uninstall_hook(
	__FILE__,
	array( \CloudLinux\Imunify\App\Bot\LifecycleHooks::class, 'onUninstall' )
);

add_action( 'plugins_loaded', array( \CloudLinux\Imunify\App\Bot\MuPluginSelfHealer::class, 'check' ) );

try {
	if ( ! class_exists( Plugin::class ) ) {
		return;
	}

	Plugin::instance()->init();
} catch ( \Exception $e ) {
	do_action(
		'imunify_security_set_error',
		E_WARNING,
		'Init plugin failed: ' . $e->getMessage(),
		__FILE__,
		__LINE__,
		array(
			'fingerprint' => array( 'init-plugin-failed', get_class( $e ) ),
		)
	);
}
