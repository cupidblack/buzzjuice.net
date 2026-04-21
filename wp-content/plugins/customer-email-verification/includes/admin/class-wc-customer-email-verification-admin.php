<?php
/**
 * Admin Class for Customer Email Verification
 *
 * Handles all admin-related functionality including menu registration,
 * page rendering, settings management, and user verification checks.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Customer_Email_Verification_Admin_Pro Class
 *
 * Main admin class that manages the WordPress admin interface for the
 * Customer Email Verification plugin. This class handles menu registration,
 * page rendering, settings retrieval, and user verification status checks.
 */
class WC_Customer_Email_Verification_Admin_Pro {

	/**
	 * Stores the My Account page ID.
	 *
	 * @var int
	 */
	public $my_account_id;

	/**
	 * Instance of this class (Singleton pattern).
	 *
	 * @var WC_Customer_Email_Verification_Admin_Pro
	 */
	private static $instance;

	/**
	 * Default tab slug for the settings page.
	 *
	 * @var string
	 */
	const DEFAULT_TAB = 'email-verification';

	/**
	 * Plugin settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'customer-email-verification-for-woocommerce';

	/**
	 * Admin body class identifier.
	 *
	 * @var string
	 */
	const BODY_CLASS = 'customer-email-verification-for-woocommerce';

	/**
	 * Constructor.
	 *
	 * Initializes the class and hooks into WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Get the singleton instance of this class.
	 *
	 * Ensures that only one instance of the class is created throughout
	 * the WordPress request lifecycle (Singleton pattern).
	 *
	 * @since 1.0.0
	 * @return WC_Customer_Email_Verification_Admin_Pro The single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize hooks and filters for the plugin.
	 *
	 * Sets up all WordPress hooks, filters, and initializes related classes.
	 * This method is called on the 'init' action hook.
	 *
	 * Note: Admin menu registration is handled in CEV_Pro_Hooks class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Initialize related admin classes.
		$this->initialize_admin_classes();

		// Add custom body class on settings page.
		$this->register_admin_body_class_filter();

		// Register filters.
		add_filter( 'cev_verification_code_length', array( $this, 'cev_verification_code_length_callback' ), 10, 1 );
	}

	/**
	 * Initialize related admin classes.
	 *
	 * Checks for and initializes the settings, user management, and notice classes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize_admin_classes() {
		// Initialize settings class.
		if ( class_exists( 'CEV_Pro_Admin_Settings' ) ) {
			CEV_Pro_Admin_Settings::get_instance()->init();
		}

		// Initialize admin user management class.
		if ( class_exists( 'CEV_Pro_Admin_User' ) ) {
			CEV_Pro_Admin_User::get_instance( $this );
		}

		// Initialize admin notice class.
		if ( class_exists( 'CEV_Pro_Admin_Notice' ) ) {
			CEV_Pro_Admin_Notice::get_instance();
		}
	}

	/**
	 * Register the admin body class filter for the settings page.
	 *
	 * Adds a custom CSS class to the admin body tag when viewing the
	 * plugin's settings page for styling purposes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_admin_body_class_filter() {
		// Only add filter if we're on the settings page.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( self::PAGE_SLUG === $current_page ) {
			add_filter( 'admin_body_class', array( $this, 'cev_post_admin_body_class' ), 100 );
		}
	}

	/**
	 * Registers a custom submenu under the WooCommerce menu.
	 *
	 * Adds a new submenu page under WooCommerce in the WordPress admin
	 * for managing email verification settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_email_verification_submenu() {
		add_submenu_page(
			'woocommerce', // Parent menu slug.
			__( 'Customer Verification', 'customer-email-verification' ), // Page title.
			__( 'Email Verification', 'customer-email-verification' ), // Menu title.
			'manage_woocommerce', // Required capability.
			self::PAGE_SLUG, // Menu slug.
			array( $this, 'render_email_verification_page' ) // Callback function.
		);
	}

	/**
	 * Render the main Customer Email Verification admin page.
	 *
	 * Displays the admin interface with tabs, breadcrumbs, and includes
	 * the necessary view files for settings and user management.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_email_verification_page() {
		// Get and sanitize the current tab from URL.
		$current_tab = $this->get_current_tab();

		// Enqueue necessary scripts for the settings page.
		wp_enqueue_script( 'customer_email_verification_table_rows' );

		// Get breadcrumb text based on current tab.
		$breadcrumb_text = $this->get_breadcrumb_text( $current_tab );

		// Get plugin instance for path/URL functions.
		$plugin_instance = cev_pro();
		$plugin_path     = $plugin_instance->get_plugin_path();

		// Include page header template.
		$header_path = $plugin_path . '/includes/admin/views/page-header.php';
		if ( file_exists( $header_path ) ) {
			include $header_path;
		}

		// Include page layout template.
		$layout_path = $plugin_path . '/includes/admin/views/page-layout.php';
		if ( file_exists( $layout_path ) ) {
			$admin_instance = $this;
			include $layout_path;
		}
	}

	/**
	 * Get the current tab from the URL.
	 *
	 * Retrieves and sanitizes the 'tab' query parameter from the URL,
	 * defaulting to the default tab if not set.
	 *
	 * @since 1.0.0
	 * @return string The current tab slug.
	 */
	private function get_current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : self::DEFAULT_TAB;
		return $tab;
	}

	/**
	 * Get breadcrumb text based on the current tab.
	 *
	 * Returns the appropriate breadcrumb text for display in the page header
	 * based on which tab is currently active.
	 *
	 * @since 1.0.0
	 * @param string $tab Current tab slug.
	 * @return string Breadcrumb text.
	 */
	private function get_breadcrumb_text( $tab ) {
		$breadcrumb_map = array(
			'unverified-users' => __( 'Unverified Users', 'customer-email-verification' ),
			'add-ons'          => __( 'Go Pro', 'customer-email-verification' ),
		);

		return isset( $breadcrumb_map[ $tab ] ) ? $breadcrumb_map[ $tab ] : __( 'Settings', 'customer-email-verification' );
	}

	/**
	 * Retrieve tab configuration for the Email Verification menu.
	 *
	 * Returns an array of tab settings used to display menu tabs
	 * in the WordPress admin area. Each tab includes configuration
	 * for title, visibility, CSS classes, and data attributes.
	 *
	 * @since 1.0.0
	 * @return array Tab configuration data with keys: setting_tab, customize, user_tab.
	 */
	public function get_cev_tab_settings_data() {
		$setting_data = array(
			// Settings tab configuration.
			'setting_tab' => array(
				'title'      => __( 'Settings', 'customer-email-verification' ),
				'show'       => true,
				'class'      => 'cev_tab_label first_label',
				'data-tab'   => 'email-verification',
				'data-label' => __( 'Settings', 'customer-email-verification' ),
				'name'       => 'tabs',
			),

			// Customize tab configuration (external link).
			'customize'    => array(
				'title'      => __( 'Customize', 'customer-email-verification' ),
				'type'       => 'link',
				'link'       => admin_url( 'admin.php?page=cev_customizer&preview=email_registration' ),
				'show'       => true,
				'class'      => 'tab_label',
				'data-tab'   => 'trackship',
				'data-label' => __( 'Customize', 'customer-email-verification' ),
				'name'       => 'tabs',
			),

			// Unverified Users tab configuration.
			'user_tab'     => array(
				'title'      => __( 'Unverified Users', 'customer-email-verification' ),
				'show'       => true,
				'class'      => 'cev_tab_label',
				'data-tab'   => 'unverified-users',
				'data-label' => __( 'Unverified Users', 'customer-email-verification' ),
				'name'       => 'tabs',
			),
		);

		return $setting_data;
	}

	/**
	 * Get the settings class instance.
	 *
	 * Returns a singleton instance of the CEV_Pro_Admin_Settings class
	 * for accessing settings-related methods.
	 *
	 * @since 1.0.0
	 * @return CEV_Pro_Admin_Settings Instance of the settings class.
	 */
	public function get_settings() {
		return CEV_Pro_Admin_Settings::get_instance();
	}

	/**
	 * Retrieves settings data for new email verification configurations.
	 *
	 * Delegates to the settings class to get the configuration options
	 * for email verification settings.
	 *
	 * @since 1.0.0
	 * @return array Array of configuration options for email verification.
	 */
	public function get_cev_settings_data_new() {
		return $this->get_settings()->get_cev_settings_data_new();
	}

	/**
	 * Retrieves general settings data.
	 *
	 * Delegates to the settings class to get general configuration options.
	 *
	 * @since 1.0.0
	 * @return array Array of general configuration options.
	 */
	public function get_cev_general_settings_data() {
		return $this->get_settings()->get_cev_general_settings_data();
	}

	/**
	 * Retrieves the settings data for login OTP configuration.
	 *
	 * Delegates to the settings class to get login OTP configuration options.
	 *
	 * @since 1.0.0
	 * @return array Array of settings configuration for login OTP.
	 */
	public function get_cev_settings_data_login_otp() {
		return $this->get_settings()->get_cev_settings_data_login_otp();
	}

	/**
	 * Generate HTML for menu tabs in the admin panel.
	 *
	 * Renders the tab navigation for the admin page. Supports both
	 * regular tabs (radio buttons) and link tabs (anchor tags).
	 *
	 * @since 1.0.0
	 * @param array $tabs Array of menu tab configurations. Each tab should have:
	 *                    - title: Tab display text
	 *                    - type: 'link' for external links, otherwise radio tab
	 *                    - class: CSS classes
	 *                    - data-tab: Tab identifier
	 *                    - data-label: Accessibility label
	 *                    - name: Input name attribute
	 *                    - link: URL (for link type tabs)
	 * @return void
	 */
	public function render_menu_tabs( $tabs ) {
		// Get the currently selected tab from the URL.
		$current_tab = $this->get_current_tab();

		// Get plugin instance for path functions.
		$plugin_instance = cev_pro();
		$plugin_path     = $plugin_instance->get_plugin_path();

		// Include menu tabs template.
		$tabs_path = $plugin_path . '/includes/admin/views/menu-tabs.php';
		if ( file_exists( $tabs_path ) ) {
			// Pass variables to template.
			$current_tab = $current_tab;
			$tabs = $tabs;
			include $tabs_path;
		}
	}

	/**
	 * Generate HTML for the settings fields.
	 *
	 * Delegates to the settings class to render the settings fields
	 * based on the provided fields array.
	 *
	 * @since 1.0.0
	 * @param array $fields Settings fields array to render.
	 * @return void
	 */
	public function render_settings_fields( $fields ) {
		$this->get_settings()->render_settings_fields( $fields );
	}

	/**
	 * Get verification email body content based on verification type.
	 *
	 * Returns the appropriate email body template based on the verification
	 * method selected (OTP only).
	 *
	 * @since 1.0.0
	 * @param string $verification_type The type of verification: 'otp'.
	 * @return string The email body content with placeholders.
	 */
	private function get_verification_email_body( $verification_type ) {
		$email_templates = array(
			'otp'  => __( 'Thank you for signing up for {site_title}, to activate your account, we need to verify your email address.<p>Your verification code: <strong>{cev_user_verification_pin}</strong></p>', 'customer-email-verification' ),
		);

		// Default to OTP verification if type not found.
		$default_template = __( 'Thank you for signing up for {site_title}, to activate your account, we need to verify your email address.<p>Your verification code: <strong>{cev_user_verification_pin}</strong></p>', 'customer-email-verification' );

		return isset( $email_templates[ $verification_type ] ) ? $email_templates[ $verification_type ] : $default_template;
	}

	/**
	 * Add a link to resend the verification email on the WooCommerce login form.
	 *
	 * Appends a "Resend verification email" link to the WooCommerce login form
	 * for users who haven't verified their email address.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_woocommerce_login_form_end() {
		// Get plugin instance for path functions.
		$plugin_instance = cev_pro();
		$plugin_path     = $plugin_instance->get_plugin_path();

		// Include login form resend link template.
		$template_path = $plugin_path . '/includes/login-authentication/views/login-form-resend-link.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Check if a user is an administrator.
	 *
	 * Verifies if the specified user has the 'administrator' role.
	 * Administrators are typically exempt from email verification requirements.
	 *
	 * @since 1.0.0
	 * @param int $user_id The user ID to check.
	 * @return bool True if the user is an administrator, false otherwise.
	 */
	public function is_admin_user( $user_id ) {
		// Validate user ID.
		if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return false;
		}

		// Get user object.
		$user = get_user_by( 'id', $user_id );

		// Return false if user doesn't exist.
		if ( ! $user ) {
			return false;
		}

		// Check if user has administrator role.
		return in_array( 'administrator', (array) $user->roles, true );
	}


	/**
	 * Adds a custom CSS class to the admin body tag.
	 *
	 * Appends a plugin-specific class to the admin body tag for styling
	 * purposes when viewing the plugin's settings page.
	 *
	 * @since 1.0.0
	 * @param string $body_class Current classes applied to the admin body tag.
	 * @return string Modified classes with the custom class appended.
	 */
	public function cev_post_admin_body_class( $body_class ) {
		return $body_class . ' ' . self::BODY_CLASS;
	}

	/**
	 * Callback for the verification code length filter.
	 *
	 * Allows modification of the verification code length through
	 * the 'cev_verification_code_length' filter.
	 *
	 * @since 1.0.0
	 * @param int|string $length The current verification code length.
	 * @return int|string The filtered verification code length.
	 */
	public function cev_verification_code_length_callback( $length ) {
		// Return the length as-is (can be modified by other filters).
		return $length;
	}
}
