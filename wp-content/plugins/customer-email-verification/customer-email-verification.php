<?php
/**
 * Plugin Name:	Customer Email Verification
 * Plugin URI:	http://woocommerce.com/products/customer-email-verification/
 * Description: The Customer Email Verification helps WooCommerce store owners to reduce registration and orders spam by requiring customers to verify their email address when they register an account or before they can checkout on your store.
 * Version: 2.9.3
 * Author: zorem
 * Author URI: https://www.zorem.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 * Text Domain: customer-email-verification
 * Domain Path: /lang/
 * Requires Plugins: woocommerce
 *
 * WC requires at least: 4.8
 * WC tested up to:  10.6.2
 * Woo: 8105872:4b9f6cbe025271aa7ae3c09e808651bf
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package zorem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for Customer Email Verification
 * 
 * This class handles the initialization and management of all plugin functionality
 * including email verification, login authentication, and admin settings.
 */
class Customer_Email_Verification_Pro {

	/**
	 * Customer Email Verification Pro
	 *
	 * @var string
	 */
	public $version = '2.9.3';
	public $plugin_file;
	public $plugin_path;
	public $my_account;
	public $admin;
	public $preview;
	public $cev_pro_guest_user;
	public $cev_pro_login_authentication;
	public $email_login;
	public $customizer;
	public $customizer_options;
	public $signup;
	public $install;
	public $cev_pro_checkout_block;
	public $checkout_verification;
	public $enqueue;
	public $hooks;
	public $function;
	private static $action_links_registered = false;
	
	/**
	 * Initialize the main plugin function
	 * 
	 * Sets up the plugin by defining constants, checking dependencies,
	 * and initializing all necessary classes and hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Store the main plugin file path
		$this->plugin_file = __FILE__;
		
		// Set plugin path directly to avoid calling get_plugin_path() during construction
		$this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));

		// Define the plugin path constant if not already defined
		if (!defined('CUSTOMER_EMAIL_VERIFICATION_PATH')) {
			define('CUSTOMER_EMAIL_VERIFICATION_PATH', $this->plugin_path);
		}
		
		$this->includes_for_all();
		
		// Delay initialization to avoid issues during plugin activation
		add_action('plugins_loaded', array($this, 'initialize_plugin'), 5);
		
		// Load text domain on init
		add_action('init', array($this, 'load_pro_textdomain'), 20);
	}
	
	/**
	 * Initialize plugin after plugins are loaded
	 */
	public function initialize_plugin() {
		// Check if WooCommerce is active before proceeding with core functionality
		if ( $this->is_wc_active() ) {
			// Get the WooCommerce My Account page ID
			$this->my_account = get_option('woocommerce_myaccount_page_id');

			// If My Account page ID is empty, fallback to the homepage ID
			if ('' === $this->my_account) {
				$this->my_account = get_option('page_on_front');
			}
			
			// Include necessary plugin files and dependencies
			$this->includes();

			// Initialize core functionalities
			$this->init();
			
			// Note: Hooks are initialized automatically when CEV_Pro_Hooks is instantiated
			// Note: $this->admin->init() is called automatically via 'init' hook 
			// set in WC_Customer_Email_Verification_Admin_Pro constructor
			
			// Initialize preview-related functionalities
			$this->preview->init();
			
			// Call on_plugins_loaded directly since we're already on plugins_loaded hook
			$this->on_plugins_loaded();
		}
	}
	
	/**
	 * Include files that are needed regardless of WooCommerce status
	 * 
	 * These files are loaded early because they handle core functionality
	 * that must be available even if WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	public function includes_for_all() {
		// Use plugin_path directly to avoid method calls during construction
		$plugin_path = $this->plugin_path;
		
		// Load functions file first (contains cev_pro_admin_settings function)
		$functions_file = $plugin_path . '/includes/cev-pro-functions.php';
		if (file_exists($functions_file)) {
			require_once $functions_file;
			// Initialize the function property to access CEV_Functions_Pro methods
			$this->function = CEV_Functions_Pro::get_instance();
		}
		
		// Load admin settings class first
		$admin_settings_file = $plugin_path . '/includes/admin/cev-pro-admin-settings.php';
		if (file_exists($admin_settings_file)) {
			require_once $admin_settings_file;
		}
		
		// Load admin functionalities - handles plugin settings and admin interface
		$admin_file = $plugin_path . '/includes/admin/class-wc-customer-email-verification-admin.php';
		if (file_exists($admin_file)) {
			require_once $admin_file;
			if (class_exists('WC_Customer_Email_Verification_Admin_Pro')) {
				$this->admin = WC_Customer_Email_Verification_Admin_Pro::get_instance();
			}
		}
		
		// Load admin user management class
		$admin_user_file = $plugin_path . '/includes/admin/cev-pro-admin-user.php';
		if (file_exists($admin_user_file)) {
			require_once $admin_user_file;
		}

		// Load admin notice class
		$admin_notice_file = $plugin_path . '/includes/admin/cev-pro-admin-notice.php';
		if (file_exists($admin_notice_file)) {
			require_once $admin_notice_file;
		}

		// Load installation class - handles plugin activation and setup tasks
		$install_file = $plugin_path . '/includes/cev-pro-installation.php';
		if (file_exists($install_file)) {
			require_once $install_file;
			if (class_exists('CEV_Pro_Installation')) {
				$this->install = CEV_Pro_Installation::get_instance(__FILE__);
			}
		}
	}
	
	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private function is_wc_active() {
		// Ensure the is_plugin_active function is available
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Check if WooCommerce is active
		$is_active = is_plugin_active('woocommerce/woocommerce.php');

		// If WooCommerce is not active, defer notice to admin_notices hook
		if (false === $is_active) {
			add_action('admin_notices', array($this, 'notice_activate_wc'));
		}

		return $is_active;
	}

	/**
	 * Display an admin notice if WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	public function notice_activate_wc() {
		// Store translated message in a variable to ensure it runs on admin_notices
		$message = sprintf(
			/* translators: %s: search WooCommerce plugin link */
			esc_html__('Please install and activate %1$sWooCommerce%2$s plugin for the Customer Email Verification PRO to work.', 'customer-email-verification'),
			'<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins')) . '">',
			'</a>'
		);
		?>
		<div class="error">
			<p><?php echo wp_kses_post($message); ?></p>
		</div>
		<?php
	}

	/**
	 * Include necessary plugin files and initialize classes
	 * 
	 * Loads all required class files and creates instances of each component.
	 * This method is called only when WooCommerce is active.
	 *
	 * @since 1.0.0
	 */	
	public function includes() {
		// Load signup verification process
		require_once $this->get_plugin_path() . '/includes/signup-verification/class-cev-signup-verification.php';
		$this->signup = CEV_Signup_Verification::get_instance();

		// Load email preview feature for front-end
		require_once $this->get_plugin_path() . '/includes/class-wc-customer-email-verification-preview-front.php';
		$this->preview = WC_Customer_Email_Verification_Preview_Pro::get_instance();
		
		// Load edit account verification class (after WooCommerce is loaded).
		require_once $this->get_plugin_path() . '/includes/edit-account/class-cev-edit-account-verification.php';
		CEV_Edit_Account_Verification::get_instance();

		// Load checkout verification class
		require_once $this->get_plugin_path() . '/includes/checkout-verification/class-cev-checkout-verification.php';
		$this->checkout_verification = CEV_Checkout_Verification::get_instance();
	
		// Load social login support for guest users
		require_once $this->get_plugin_path() . '/includes/cev-pro-social-login.php';
		$this->cev_pro_guest_user = WC_Customer_Email_Verification_Social_Login::get_instance();

		// Load login authentication handling
		require_once $this->get_plugin_path() . '/includes/login-authentication/class-cev-login-authentication.php';
		$this->cev_pro_login_authentication = WC_Customer_Email_Verification_Login_Authentication::get_instance();

		// Load checkout block functionality (only if checkout verification is enabled).
		if ( 1 == cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			require_once $this->get_plugin_path() . '/includes/checkout-verification/cev-pro-checkout-block.php';
			$this->cev_pro_checkout_block = WC_Customer_Email_Verification_Checkout_Block::get_instance();
		}

		// Instantiate enqueue handler for scripts and styles
		require_once $this->get_plugin_path() . '/includes/cev-pro-enqueue.php';
		$this->enqueue = new CEV_Enqueue_Pro();

		// Load hooks class - handles WordPress hooks and filters for plugin functionality
		require_once $this->get_plugin_path() . '/includes/cev-pro-hooks.php';
		$this->hooks = new CEV_Pro_Hooks($this);
	}
	
	/**
	 * Initialize plugin hooks and filters
	 * 
	 * Sets up plugin-specific hooks including action links and activation hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Prevent duplicate registration of action links filter
		if ( ! self::$action_links_registered ) {
		// Add custom action links to the plugin page (Settings link)
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this , 'my_plugin_action_links' ));
			self::$action_links_registered = true;
		}
	
		// Register activation hook to handle plugin activation tasks
		register_activation_hook( __FILE__, array( $this,'on_activation_cev' ) );
	}
	/**
	 * Handle actions on plugin activation
	 * 
	 * Deactivates previous versions of the plugin and sets a transient notice.
	 * This prevents conflicts when multiple versions are installed.
	 *
	 * @since 2.4
	 */
	public function on_activation_cev() {
		// Deactivate older versions of the Customer Email Verification plugin
		deactivate_plugins( 'customer-email-verification-for-woocommerce/customer-email-verification-for-woocommerce.php' );
		deactivate_plugins( 'customer-email-verification-pro/customer-email-verification-pro.php' );

		// Register the login-authentication endpoint then flush so the URL works immediately.
		add_rewrite_endpoint( 'login-authentication', EP_PAGES );
		flush_rewrite_rules();

		// Set a transient notice for the free version (shown for 3 seconds)
		set_transient( 'free_cev_plugin', 'notice', 3 );

		// Prevent showing the plugin notice again
		update_option( 'cev_pro_plugin_notice_ignore', 'true' );
	}

	/**
	 * Add plugin action links.
	 *
	 * Add a link to the settings page on the plugins.php page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $links List of existing plugin action links.
	 * @return array         List of modified plugin action links.
	 */
	public function my_plugin_action_links( $links ) {
		// Prevent duplicate Settings links
		$settings_url = esc_url( admin_url( '/admin.php?page=customer-email-verification-for-woocommerce' ) );
		$settings_text = esc_html__( 'Settings', 'woocommerce' );
		$settings_link = '<a href="' . $settings_url . '">' . $settings_text . '</a>';
		
		// Check if Settings link already exists to prevent duplicates
		$link_exists = false;
		foreach ( $links as $link ) {
			if ( false !== strpos( $link, $settings_url ) ) {
				$link_exists = true;
				break;
			}
		}
		
		if ( ! $link_exists ) {
			$links = array_merge( array( $settings_link ), $links );
		}
		
		return $links;
	}

	/**
	 * Check if the specified user is an administrator
	 * 
	 * Used to skip email verification requirements for admin users,
	 * as they typically don't need to verify their email addresses.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The WordPress user ID to check
	 * @return bool True if user is an administrator, false otherwise
	 */
	public function is_admin_user( $user_id ) {
		// Get user object by ID
		$user = get_user_by( 'id', $user_id );
		
		// Return false if user doesn't exist
		if ( !$user ) {
			return false;
		}
		
		// Get user roles
		$roles = $user->roles;
		
		// Check if user has administrator role
		if ( in_array( 'administrator', (array) $roles ) ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Perform actions when all plugins are loaded.
	 */
	public function on_plugins_loaded() {
		// Allow custom HTML tags in email content (moved to functions file)
		if ( isset( cev_pro()->function ) ) {
			add_filter('wp_kses_allowed_html', array(cev_pro()->function, 'my_allowed_tags'));

			// Allow safe CSS styles in emails
			add_filter('safe_style_css', array(cev_pro()->function, 'safe_style_css_callback'), 10, 1);
		}

		// Defer customizer loading to init
		add_action('init', array($this, 'load_customizer'));
	}

	/**
	 * Load customizer files on init to avoid early translation calls.
	 */
	public function load_customizer() {
		require_once $this->get_plugin_path() . '/includes/customizer/cev-customizer.php';
		$this->customizer = CEV_Customizer::get_instance();

		require_once $this->get_plugin_path() . '/includes/customizer/cev-customizer-options.php';
		$this->customizer_options = CEV_Customizer_Options::get_instance();
	}

	/**
	 * Load the plugin text domain for translations.
	 */
	public function load_pro_textdomain() {
		load_plugin_textdomain('customer-email-verification', false, dirname(plugin_basename($this->plugin_file)) . '/lang/');
	}
	
	/**
	 * Gets the absolute plugin path without a trailing slash
	 * 
	 * Example: /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin path
	 */
	public function get_plugin_path() {
		if (isset($this->plugin_path)) {
			return $this->plugin_path;
		}

		$this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));

		return $this->plugin_path;
	}
	
	/**
	 * Gets the absolute plugin URL
	 * 
	 * Example: https://your-site.com/wp-content/plugins/plugin-directory/
	 *
	 * @since 1.0.0
	 *
	 * @return string The absolute plugin URL
	 */	
	public function plugin_dir_url() {
		return plugin_dir_url( __FILE__ );
	}

}

/**
* Returns an instance of customer_email_verification_pro.
*
* @since 1.0.0
* @version 1.0.0
*
* @return customer_email_verification_pro.
*/
function cev_pro() {
	static $instance;

	if ( ! isset( $instance ) ) {		
		$instance = new Customer_Email_Verification_Pro();
	}

	return $instance;
}

/**
* Register this class globally.
*
* Backward compatibility.
*/
cev_pro();
/**
 * Declare compatibility for custom order tables with WooCommerce.
 *
 * Ensures that the plugin declares compatibility with WooCommerce's custom order tables feature.
 */
add_action('before_woocommerce_init', function() {
	// Check if WooCommerce FeaturesUtil class is available
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		// Declare compatibility with the custom order tables feature
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

if ( ! function_exists( 'zorem_tracking' ) ) {
	function zorem_tracking() {
		require_once dirname( __FILE__ ) . '/zorem-tracking/zorem-tracking.php';
		$plugin_name             = 'CEV Pro (WC.com)';
		$plugin_slug             = 'cev-pro-wccom';
		$user_id                 = '1';
		$setting_page_type       = 'top-level';
		$setting_page_location   = 'A custom top-level admin menu (admin.php)';
		$parent_menu_type        = '';
		$menu_slug               = 'customer-email-verification-for-woocommerce';
		$plugin_id               = '22';
		$zorem_tracking          = WC_Trackers::get_instance( $plugin_name, $plugin_slug, $user_id,
			$setting_page_type, $setting_page_location, $parent_menu_type, $menu_slug, $plugin_id );

		return $zorem_tracking;
	}
	zorem_tracking();
}
