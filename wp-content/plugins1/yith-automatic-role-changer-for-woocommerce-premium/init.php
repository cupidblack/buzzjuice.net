<?php
/**
 * Plugin Name: YITH Automatic Role Changer for WooCommerce Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-automatic-role-changer
 * Description: <code><strong>YITH Automatic Role Changer for WooCommerce Premium</strong></code> assigns a new or a different role to your shop customers automatically based on what they have bought. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce on <strong>YITH</strong></a>.
 * Version: 2.3.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-automatic-role-changer-for-woocommerce
 * Domain Path: /languages/
 * WC requires at least: 9.9
 * WC tested up to: 10.1
 * Requires Plugins: woocommerce
 *
 * @package YITH\AutomaticRoleChanger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YITH_WCARC_FREE_INIT', plugin_basename( __FILE__ ) );

/* === DEFINE === */
! defined( 'YITH_WCARC_VERSION' ) && define( 'YITH_WCARC_VERSION', '2.3.0' );
! defined( 'YITH_WCARC_INIT' ) && define( 'YITH_WCARC_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_WCARC_SLUG' ) && define( 'YITH_WCARC_SLUG', 'yith-automatic-role-changer-for-woocommerce' );
! defined( 'YITH_WCARC_SECRETKEY' ) && define( 'YITH_WCARC_SECRETKEY', '' );
! defined( 'YITH_WCARC_FILE' ) && define( 'YITH_WCARC_FILE', __FILE__ );
! defined( 'YITH_WCARC_PATH' ) && define( 'YITH_WCARC_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'YITH_WCARC_URL' ) && define( 'YITH_WCARC_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_WCARC_ASSETS_URL' ) && define( 'YITH_WCARC_ASSETS_URL', YITH_WCARC_URL . 'assets/' );
! defined( 'YITH_WCARC_REST_API_PATH' ) && define( 'YITH_WCARC_REST_API_PATH', YITH_WCARC_PATH . 'includes/rest-api/' );
! defined( 'YITH_WCARC_DIST_PATH' ) && define( 'YITH_WCARC_DIST_PATH', YITH_WCARC_PATH . 'dist/' );
! defined( 'YITH_WCARC_DIST_URL' ) && define( 'YITH_WCARC_DIST_URL', YITH_WCARC_URL . 'dist/' );
! defined( 'YITH_WCARC_LANGUAGES_PATH' ) && define( 'YITH_WCARC_LANGUAGES_PATH', YITH_WCARC_PATH . 'languages/' );
! defined( 'YITH_WCARC_ASSETS_JS_URL' ) && define( 'YITH_WCARC_ASSETS_JS_URL', YITH_WCARC_URL . 'assets/js/' );
! defined( 'YITH_WCARC_TEMPLATE_PATH' ) && define( 'YITH_WCARC_TEMPLATE_PATH', YITH_WCARC_PATH . 'templates/' );
! defined( 'YITH_WCARC_WC_TEMPLATE_PATH' ) && define( 'YITH_WCARC_WC_TEMPLATE_PATH', YITH_WCARC_PATH . 'templates/woocommerce/' );
! defined( 'YITH_WCARC_OPTIONS_PATH' ) && define( 'YITH_WCARC_OPTIONS_PATH', YITH_WCARC_PATH . 'plugin-options' );
! defined( 'YITH_WCARC_PREMIUM' ) && define( 'YITH_WCARC_PREMIUM', '1' );

// Plugin Framework Loader.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}
register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' );

if ( ! function_exists( 'yith_ywarc_declare_wc_features_support' ) ) {
	/**
	 * Declare support for WooCommerce features.
	 */
	function yith_ywarc_declare_wc_features_support() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_WCARC_INIT, true );
		}
	}
}

/* Start the plugin on plugins_loaded */
if ( ! function_exists( 'yith_ywarc_install' ) ) {
	/**
	 * Install the plugin
	 */
	function yith_ywarc_install() {

		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywarc_install_woocommerce_admin_notice' );
		} else {
			add_action( 'before_woocommerce_init', 'yith_ywarc_declare_wc_features_support' );
			do_action( 'yith_ywarc_init' );
		}
	}

	add_action( 'plugins_loaded', 'yith_ywarc_install', 11 );
}

if ( ! function_exists( 'yith_ywarc_install_woocommerce_admin_notice' ) ) {
	/** Print error that WooCommerce is needed */
	function yith_ywarc_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'YITH Automatic Role Changer for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'yith-automatic-role-changer-for-woocommerce' ); ?></p>
		</div>
		<?php
	}
}

add_action( 'yith_ywarc_init', 'yith_ywarc_init' );

if ( ! function_exists( 'yith_ywarc_init' ) ) {
	/**
	 * Start the plugin
	 */
	function yith_ywarc_init() {
		/**
		 * Load text domain
		 */
		if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
			yith_plugin_fw_load_plugin_textdomain( 'yith-automatic-role-changer-for-woocommerce', basename( dirname( __FILE__ ) ) . '/languages' );
		}

		require_once YITH_WCARC_PATH . 'includes/traits/trait-yith-wcarc-singleton-trait.php';
		require_once YITH_WCARC_PATH . 'includes/traits/trait-yith-wcarc-extensible-singleton-trait.php';

		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-premium.php';
		require_once YITH_WCARC_PATH . 'includes/rest-api/class-yith-wcarc-rest-server.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-admin.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-set-roles.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-roles-manager.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-admin-premium.php';
		require_once YITH_WCARC_PATH . 'includes/class-yith-role-changer-set-roles-premium.php';
		require_once YITH_WCARC_PATH . 'includes/functions.php';

		// Let's start the game!
		yith_role_changer();
	}
}
