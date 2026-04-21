<?php
if (!defined('ABSPATH')) {
	exit;
}

class CEV_Pro_Installation {
	public $plugin_file;
	/**
	 * Initialize the main plugin function
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		register_activation_hook($this->plugin_file, array($this, 'on_install_cev'));		
		add_action( 'init', array( $this, 'on_update_cev' ) );
	}

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;

	/**
	 * Get the class instance
	 *
	 * @param string $plugin_file
	 * @return CEV_Pro_Installation
	 */
	public static function get_instance( $plugin_file ) {
		if (null === self::$instance) {
			self::$instance = new self($plugin_file);
		}

		return self::$instance;
	}
	public function on_install_cev() {
		$this->create_user_log_table();
	
	}

	/**
	 * Create user log table
	 */
	public function create_user_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cev_user_log';		
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $table_name ) ) ) {	
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT,
				email varchar(100) NOT NULL,
				pin varchar(100) NOT NULL,
				verified varchar(10) NOT NULL,
				secret_code varchar(100) NOT NULL,
				last_updated datetime NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
	
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);
		}
	}

	/**
	 * Handle plugin update tasks.
	 *
	 * Runs migration logic when plugin version changes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function on_update_cev() {
		if ( ! is_admin() ) {
			return;
		}

		$current_version = get_option( 'cev_pro_update_version', '1.0' );

		// Version 1.1 migration.
		if ( version_compare( $current_version, '1.1', '<' ) ) {
			$this->create_user_log_table();
			update_option( 'cev_verification_message', 'We sent verification code. To verify your email address, please check your inbox and enter the code below.' );
			// Note: Migration code uses update_option directly to set defaults during migration
			$settings = get_option( 'cev_pro_admin_settings', array() );
			$settings['cev_verification_code_length'] = '1';
			update_option( 'cev_pro_admin_settings', $settings );
			update_option( 'cev_verification_email_body', 'Thank you for signing up for {site_title}, to activate your account, we need to verify your email address. <p>Your verification code: <strong>{cev_user_verification_pin}</strong></p>' );
			update_option( 'cev_pro_update_version', '1.1' );
		}

		// Version 2.9.0 migration: Consolidate customizer settings into single option.
		if ( version_compare( $current_version, '1.3', '<' ) ) {
			$this->migrate_settings_to_single_option();
			$this->migrate_customizer_settings_to_single_option();
			update_option( 'cev_pro_update_version', '1.3' );
		}
	}

	/**
	 * Migrate all individual settings options to a single consolidated option.
	 *
	 * Collects all plugin settings from individual options, stores them in
	 * a single serialized option 'cev_pro_admin_settings', and deletes the
	 * old individual options to clean up the database.
	 *
	 * @since 2.9
	 * @return void
	 */
	private function migrate_settings_to_single_option() {
		// List of all plugin settings option keys to migrate.
		$settings_keys = $this->get_all_settings_keys();

		// Check if migration has already been completed.
		$migrated_option = get_option( 'cev_pro_admin_settings', false );
		$existing_settings = array();
		if ( false !== $migrated_option && is_array( $migrated_option ) ) {
			// Migration already completed, but ensure all keys are present.
			$existing_settings = $migrated_option;
		}

		// Collect all existing individual option values.
		$migrated_settings = $existing_settings;
		foreach ( $settings_keys as $key ) {
			$individual_value = get_option( $key, null );
			// Migrate individual option value if it exists and not already in consolidated option.
			if ( null !== $individual_value && ! isset( $migrated_settings[ $key ] ) ) {
				$migrated_settings[ $key ] = $individual_value;
			}
		}

		// Save consolidated settings (even if empty, to mark migration as started).
		update_option( 'cev_pro_admin_settings', $migrated_settings );

		// Delete old individual options after successful migration.
		// Only delete if we successfully saved consolidated settings.
		$verify_consolidated = get_option( 'cev_pro_admin_settings', false );
		if ( false !== $verify_consolidated && is_array( $verify_consolidated ) ) {
			foreach ( $settings_keys as $key ) {
				delete_option( $key );
			}
			// Mark migration as completed.
			update_option( 'cev_settings_migration_completed', true );
		}
	}

	/**
	 * Get list of all plugin settings option keys.
	 *
	 * Returns an array of all option keys used by the plugin for settings.
	 * This list is used during migration to collect and consolidate settings.
	 *
	 * @since 2.9
	 * @return array Array of setting option keys.
	 */
	private function get_all_settings_keys() {
		return array(
			// Main verification settings.
			'cev_enable_email_verification',
			'cev_enable_email_verification_checkout',
			'cev_verification_checkout_dropdown_option',
			'cev_create_an_account_during_checkout',
			'cev_enable_email_verification_cart_page',
			'cev_enable_email_verification_free_orders',
			'cev_disable_wooCommerce_store_api',

			// General settings.
			'cev_verification_code_length',
			'cev_verification_code_expiration',
			'cev_redirect_limit_resend',
			'cev_resend_limit_message',
			'cev_verification_success_message',

			// Login authentication settings.
			'cev_enable_login_authentication',
			'enable_email_otp_for_account',
			'enable_email_auth_for_new_device',
			'enable_email_auth_for_new_location',
			'enable_email_auth_for_login_time',
			'cev_last_login_more_then_time',
		);
	}

	/**
	 * Migrate customizer settings to consolidated option.
	 *
	 * Collects all customizer settings from individual options (keys from cev_generate_defaults),
	 * stores them in a single serialized option 'cev_pro_customizer', and deletes the
	 * old individual options to clean up the database.
	 *
	 * @since 2.9
	 * @return void
	 */
	private function migrate_customizer_settings_to_single_option() {
		// Get all customizer option keys from defaults.
		$customizer_keys = $this->get_customizer_option_keys();

		// Check if migration has already been completed.
		$existing_customizer = get_option( 'cev_pro_customizer', false );
		$migrated_customizer = array();
		
		if ( false !== $existing_customizer && is_array( $existing_customizer ) ) {
			// Migration already completed, but ensure all keys are present.
			$migrated_customizer = $existing_customizer;
		}

		// Collect all existing individual customizer option values.
		foreach ( $customizer_keys as $key ) {
			$individual_value = get_option( $key, null );
			// Migrate individual option value if it exists and not already in consolidated option.
			if ( null !== $individual_value && ! isset( $migrated_customizer[ $key ] ) ) {
				$migrated_customizer[ $key ] = $individual_value;
			}
		}

		// Save consolidated customizer settings (even if empty, to mark migration as started).
		update_option( 'cev_pro_customizer', $migrated_customizer );

		// Delete old individual options after successful migration.
		// Only delete if we successfully saved consolidated settings.
		$verify_consolidated = get_option( 'cev_pro_customizer', false );
		if ( false !== $verify_consolidated && is_array( $verify_consolidated ) ) {
			foreach ( $customizer_keys as $key ) {
				delete_option( $key );
			}
			// Mark migration as completed.
			update_option( 'cev_customizer_migration_completed', true );
		}
	}

	/**
	 * Get list of all customizer option keys from cev_generate_defaults().
	 *
	 * Returns an array of all option keys used by the customizer.
	 * These are the keys defined in CEV_Customizer_Options::cev_generate_defaults().
	 *
	 * @since 2.9
	 * @return array Array of customizer option keys.
	 */
	private function get_customizer_option_keys() {
		return array(
			// Email style defaults.
			'cev_widget_content_width_style',
			'cev_content_align_style',
			'cev_widget_content_padding_style',
			'cev_verification_content_background_color',
			'cev_verification_content_border_color',
			'cev_verification_content_font_color',
			'cev_verification_image_content',
			'cev_email_content_widget_header_image_width',
			'cev_header_content_font_size',
			'cev_button_text_font_size',
			'cev_button_padding_size',
			'cev_content_widget_type',
			
			// Email content defaults - Registration.
			'cev_verification_email_heading',
			'cev_verification_email_subject',
			'cev_verification_email_body',
			
			// Email content defaults - Checkout.
			'cev_verification_email_heading_che',
			'cev_verification_email_subject_che',
			'cev_verification_email_body_che',
			
			// Email content defaults - Edit Account.
			'cev_verification_email_heading_ed',
			'cev_verification_email_subject_ed',
			'cev_verification_email_body_ed',
			
			'cev_header_button_size_pro',
			
			// Footer content defaults.
			'cev_new_verification_Footer_content',
			'cev_new_verification_Footer_content_che',
			
			// Popup style defaults.
			'cev_verification_image',
			'cev_widget_header_image_width',
			'cev_button_text_header_font_size',
			'cev_button_color_widget_header',
			'cev_button_text_color_widget_header',
			'cev_button_text_size_widget_header',
			'cev_verification_popup_background_color',
			'cev_verification_popup_overlay_background_color',
			'cev_widget_content_width',
			'cev_content_align',
			'cev_button_padding_size_widget_header',
			'cev_widget_content_padding',
			'sample_toggle_switch_cev',
			'cev_verification_hide_otp_section',
			'cev_verification_widget_hide_otp_section',
			'cev_login_hide_otp_section',
			'cev_popup_button_size',
			'cev_widget_type',
			
			// Popup content defaults - Registration.
			'cev_verification_header',
			'cev_verification_message',
			'cev_verification_widget_footer',
			
			// Popup content defaults - Login Authentication.
			'cev_login_auth_header',
			'cev_login_auth_message',
			'cev_login_auth_widget_footer',
			'cev_login_auth_button_text',
			
			// Popup content defaults - Checkout.
			'cev_header_text_checkout',
			'cev_verification_widget_message_checkout',
			'cev_verification_widget_message_footer_checkout_pro',
			'cev_verification_header_button_text',
			
			// Login authentication email defaults.
			'cev_verification_login_auth_email_subject',
			'cev_verification_login_auth_email_heading',
			'cev_verification_login_auth_email_content',
			'cev_login_auth_footer_content',
			
			// Login OTP email defaults.
			'cev_verification_login_otp_email_subject',
			'cev_verification_login_otp_email_heading',
			'cev_verification_login_otp_email_content',
			'cev_login_otp_footer_content',
		);
	}
}
