<?php
/**
 * Admin Settings Class for Customer Email Verification
 *
 * Handles all settings-related functionality including form rendering,
 * field definitions, AJAX save operations, and settings updates.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load AJAX response helper class.
if ( ! class_exists( 'CEV_Ajax_Response' ) ) {
	$plugin_instance = cev_pro();
	if ( $plugin_instance ) {
		$plugin_path = $plugin_instance->get_plugin_path();
		require_once $plugin_path . '/includes/class-cev-ajax-response.php';
	}
}

/**
 * CEV_Pro_Admin_Settings Class
 *
 * Manages all admin settings for the Customer Email Verification plugin.
 * This includes defining settings fields, rendering form elements, and
 * handling AJAX requests to save settings.
 */
class CEV_Pro_Admin_Settings {

	/**
	 * Instance of this class (Singleton pattern).
	 *
	 * @var CEV_Pro_Admin_Settings
	 */
	private static $instance;

	/**
	 * Consolidated admin settings option key.
	 *
	 * All plugin settings are stored in this single serialized option.
	 *
	 * @var string
	 */
	const CONSOLIDATED_SETTINGS_OPTION = 'cev_pro_admin_settings';

	/**
	 * Verification type option name.
	 *
	 * @var string
	 */
	const VERIFICATION_TYPE_OPTION = 'cev_verification_type';

	/**
	 * Verification email body option name.
	 *
	 * @var string
	 */
	const VERIFICATION_EMAIL_BODY_OPTION = 'cev_verification_email_body';

	/**
	 * AJAX action for settings form update.
	 *
	 * @var string
	 */
	const AJAX_ACTION_UPDATE = 'cev_settings_form_update';

	/**
	 * Nonce action for settings form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'cev_settings_form_nonce';

	/**
	 * Field types that don't require a label.
	 *
	 * @var array
	 */
	const FIELD_TYPES_WITHOUT_LABEL = array( 'desc', 'checkbox', 'checkbox_select', 'toogel', 'separator' );


	/**
	 * Checkout verification type: Popup.
	 *
	 * @var int
	 */
	const CHECKOUT_TYPE_POPUP = 1;

	/**
	 * Checkout verification type: Inline.
	 *
	 * @var int
	 */
	const CHECKOUT_TYPE_INLINE = 2;

	/**
	 * Get the singleton instance of this class.
	 *
	 * Ensures that only one instance of the class is created throughout
	 * the WordPress request lifecycle (Singleton pattern).
	 *
	 * @since 1.0.0
	 * @return CEV_Pro_Admin_Settings The single instance of this class.
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
	 * Registers WordPress hooks for AJAX handlers and other functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Register AJAX handler for updating the settings form.
		add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE, array( $this, 'cev_settings_form_update_fun' ) );
	}

	/**
	 * Get the path to a view template file.
	 *
	 * @since 1.0.0
	 * @param string $template_name The name of the template file (without .php extension).
	 * @return string The full path to the template file.
	 */
	private function get_view_path( $template_name ) {
		$plugin_instance = cev_pro();
		$plugin_path     = $plugin_instance->get_plugin_path();
		return $plugin_path . '/includes/admin/views/' . $template_name . '.php';
	}

	/**
	 * Get a setting value from the consolidated settings option.
	 *
	 * Retrieves a setting value from the consolidated 'cev_pro_admin_settings' option.
	 * Falls back to individual option if migration hasn't been completed yet
	 * (backward compatibility).
	 *
	 * @since 2.9
	 * @param string $key     Setting key to retrieve.
	 * @param mixed  $default Default value if setting doesn't exist.
	 * @return mixed Setting value or default.
	 */
	private function get_setting( $key, $default = false ) {
		$consolidated_settings = get_option( self::CONSOLIDATED_SETTINGS_OPTION, array() );

		// If consolidated settings exist and key is present, return it.
		if ( is_array( $consolidated_settings ) && isset( $consolidated_settings[ $key ] ) ) {
			return $consolidated_settings[ $key ];
		}

		// Fallback to individual option for backward compatibility.
		return get_option( $key, $default );
	}

	/**
	 * Update a setting value in the consolidated settings option.
	 *
	 * Updates a setting value in the consolidated 'cev_pro_admin_settings' option.
	 * Reads existing settings, merges the new value, and saves back.
	 *
	 * @since 2.9
	 * @param string $key   Setting key to update.
	 * @param mixed  $value Value to set.
	 * @return bool True if option was updated, false otherwise.
	 */
	private function update_setting( $key, $value ) {
		$consolidated_settings = get_option( self::CONSOLIDATED_SETTINGS_OPTION, array() );

		// Ensure we have an array.
		if ( ! is_array( $consolidated_settings ) ) {
			$consolidated_settings = array();
		}

		// Update the setting value.
		$consolidated_settings[ $key ] = $value;

		// Save consolidated settings.
		return update_option( self::CONSOLIDATED_SETTINGS_OPTION, $consolidated_settings );
	}

	/**
	 * Retrieve settings data for email verification configurations.
	 *
	 * Returns an array of form field definitions for the main verification
	 * settings including signup verification, checkout verification, and
	 * related options.
	 *
	 * @since 1.0.0
	 * @return array Array of configuration options for email verification.
	 */
	public function get_cev_settings_data_new() {
		// Check if account creation during checkout is enabled.
		$create_account_during_checkout = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$checkout_dropdown_val          = absint( $this->get_setting( 'cev_verification_checkout_dropdown_option', 0 ) );

		// Define checkout verification types.
		$checkout_verification_types = array(
			self::CHECKOUT_TYPE_POPUP  => __( 'Popup', 'customer-email-verification' ),
			self::CHECKOUT_TYPE_INLINE => __( 'Inline', 'customer-email-verification' ),
		);

		// Build the settings form data.
		$form_data = array(
			// Enable signup verification toggle.
			'cev_enable_email_verification' => array(
				'type'    => 'toogel',
				'title'   => __( 'Enable Signup Verification', 'customer-email-verification' ),
				'show'    => true,
				'class'   => 'toogel',
				'name'    => 'cev_enable_email_verification',
				'id'      => 'cev_enable_email_verification',
				'Default' => 1,
			),

			// Separator for visual separation.
			'cev_settings_separator_1'      => array(
				'type' => 'separator',
				'id'   => 'cev_settings_separator_1',
				'show' => true,
			),

			// Enable checkout verification toggle.
			'cev_enable_email_verification_checkout' => array(
				'type'    => 'toogel',
				'title'   => __( 'Enable Checkout Verification', 'customer-email-verification' ),
				'show'    => true,
				'class'   => 'toogel',
				'name'    => 'cev_enable_email_verification_checkout',
				'id'      => 'cev_enable_email_verification_checkout',
				'Default' => 0,
			),

			// Checkout verification type dropdown.
			'cev_verification_checkout_dropdown_option' => array(
				'type'    => 'dropdown',
				'title'   => __( 'Checkout Verification Type', 'customer-email-verification' ),
				'options' => $checkout_verification_types,
				'show'    => true,
				'class'   => 'cev-skip-bottom-padding',
			),
		);

		// Add conditional field for account creation during checkout.
		$form_data['cev_create_an_account_during_checkout'] = array(
			'type'             => 'checkbox',
			'title'            => __( 'Require email verification only when Create an account during checkout is selected', 'customer-email-verification' ),
			'show'             => true,
			'class'            => 'toogel cev-create-account-field',
			'name'             => 'cev_create_an_account_during_checkout',
			'id'               => 'cev_create_an_account_during_checkout',
			'Default'          => 0,
			'initially_visible' => ( $create_account_during_checkout && self::CHECKOUT_TYPE_INLINE === $checkout_dropdown_val ) ? 'yes' : 'no',
		);

		// Enable email verification on cart page.
		$form_data['cev_enable_email_verification_cart_page'] = array(
			'type'  => 'checkbox',
			'title' => __( 'Enable the email verification on the cart page', 'customer-email-verification' ),
			'show'  => true,
			'id'    => 'cev_enable_email_verification_cart_page',
		);

		// Require checkout verification only for free orders.
		$form_data['cev_enable_email_verification_free_orders'] = array(
			'type'    => 'checkbox',
			'title'   => __( 'Require checkout verification only for free orders', 'customer-email-verification' ),
			'show'    => true,
			'id'      => 'cev_enable_email_verification_free_orders',
			'Default' => 0,
		);

		// Separator.
		$form_data['cev_settings_separator_10'] = array(
			'type' => 'separator',
			'id'   => 'cev_settings_separator_10',
			'show' => true,
		);

		// Disable WooCommerce Store API Checkout.
		$form_data['cev_disable_wooCommerce_store_api'] = array(
			'type'    => 'toogel',
			'title'   => __( 'Disable WooCommerce Store API Checkout', 'customer-email-verification' ),
			'show'    => true,
			'class'   => 'toogel',
			'name'    => 'cev_disable_wooCommerce_store_api',
			'id'      => 'cev_disable_wooCommerce_store_api',
			'Default' => 0,
		);

		return $form_data;
	}

	/**
	 * Retrieve general settings data for email verification.
	 *
	 * Returns an array of form field definitions for general verification
	 * settings including OTP length, expiration, resend limits, and messages.
	 *
	 * @since 1.0.0
	 * @return array Array of general configuration options.
	 */
	public function get_cev_general_settings_data() {
		// Define OTP length options.
		$otp_lengths = array(
			'1' => __( '4-digit', 'customer-email-verification' ),
			'2' => __( '6-digit', 'customer-email-verification' ),
		);

		// Define OTP expiration options (in seconds).
		$otp_expirations = array(
			'never'   => __( 'Never', 'customer-email-verification' ),
			'600'     => __( '10 min', 'customer-email-verification' ),
			'900'     => __( '15 min', 'customer-email-verification' ),
			'1800'    => __( '30 min', 'customer-email-verification' ),
			'3600'    => __( '1 Hour', 'customer-email-verification' ),
			'86400'   => __( '24 Hours', 'customer-email-verification' ),
			'259200'  => __( '72 Hours', 'customer-email-verification' ),
		);

		// Define resend limit options.
		$resend_limits = array(
			'1' => __( 'Allow 1 Attempt', 'customer-email-verification' ),
			'3' => __( 'Allow 3 Attempts', 'customer-email-verification' ),
			'0' => __( 'Disable Resend', 'customer-email-verification' ),
		);

		// Build form data array.
		$form_data = array(
			// OTP length dropdown.
			'cev_verification_code_length' => array(
				'type'    => 'dropdown',
				'title'   => __( 'OTP Length', 'customer-email-verification' ),
				'options' => $otp_lengths,
				'show'    => true,
			),

			// OTP expiration dropdown.
			'cev_verification_code_expiration' => array(
				'type'    => 'dropdown',
				'title'   => __( 'OTP Expiration', 'customer-email-verification' ),
				'options' => $otp_expirations,
				'show'    => true,
				'tooltip' => __( 'Choose if you wish to set expiry time to the OTP.', 'customer-email-verification' ),
			),

			// Separator for visual separation.
			'cev_settings_separator_3' => array(
				'type' => 'separator',
				'id'   => 'cev_settings_separator_3',
				'show' => true,
			),

			// Resend limit dropdown.
			'cev_redirect_limit_resend' => array(
				'type'    => 'dropdown',
				'title'   => __( 'Verification Email Resend Limit', 'customer-email-verification' ),
				'options' => $resend_limits,
				'show'    => true,
				'class'   => 'redirect_page',
			),

			// Resend limit message textarea.
			'cev_resend_limit_message' => array(
				'type'        => 'textarea',
				'title'       => __( 'Resend Limit Message', 'customer-email-verification' ),
				'show'        => true,
				'tooltip'     => __( 'Limit the number of resend attempts.', 'customer-email-verification' ),
				'placeholder' => __( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' ),
				'Default'     => '',
				'class'       => 'cev_text_design top',
			),

			// Email verification success message.
			'cev_verification_success_message' => array(
				'type'        => 'textarea',
				'title'       => __( 'Email Verification Success Message', 'customer-email-verification' ),
				'show'        => true,
				'tooltip'     => __( 'Message shown after successful verification.', 'customer-email-verification' ),
				'placeholder' => __( 'Your email is verified!', 'customer-email-verification' ),
				'Default'     => '',
			),
		);

		return $form_data;
	}

	/**
	 * Retrieve settings data for login OTP configuration.
	 *
	 * Returns an array of form field definitions for login authentication
	 * settings including OTP verification for unrecognized logins and
	 * conditions for triggering verification.
	 *
	 * @since 1.0.0
	 * @return array Array of settings configuration for login OTP.
	 */
	public function get_cev_settings_data_login_otp() {
		// Build form data array.
		$form_data = array(
			// Toggle for enabling login authentication.
			'cev_enable_login_authentication' => array(
				'type'  => 'toogel',
				'title' => __( 'Enable Login Authentication', 'customer-email-verification' ),
				'show'  => true,
				'class' => 'toogel cev_enable_login_authentication',
				'name'  => 'cev_enable_login_authentication',
				'id'    => 'cev_enable_login_authentication',
				'value' => '1',
			),

			// Checkbox for requiring OTP verification for unrecognized logins.
			'enable_email_otp_for_account' => array(
				'type'    => 'checkbox',
				'title'   => __( 'Require OTP verification for unrecognized login', 'customer-email-verification' ),
				'show'    => true,
				'id'      => 'enable_email_otp_for_account',
				'Default' => 1,
			),

			// Separator for visual separation.
			'cev_settings_separator_4' => array(
				'type' => 'separator',
				'id'   => 'cev_settings_separator_4',
				'show' => true,
			),

			// Description for unrecognized login conditions.
			'login_auth_desc' => array(
				'type'  => 'desc',
				'title' => __( 'Unrecognized Login Conditions:', 'customer-email-verification' ),
				'show'  => true,
				'id'    => 'login_auth_desc',
			),

			// Checkbox for login authentication based on new device.
			'enable_email_auth_for_new_device' => array(
				'type'    => 'checkbox',
				'title'   => __( 'Login from a new device', 'customer-email-verification' ),
				'show'    => true,
				'id'      => 'enable_email_auth_for_new_device',
				'Default' => 1,
			),

			// Checkbox for login authentication based on new location.
			'enable_email_auth_for_new_location' => array(
				'type'    => 'checkbox',
				'title'   => __( 'Login from a new location', 'customer-email-verification' ),
				'show'    => true,
				'id'      => 'enable_email_auth_for_new_location',
				'Default' => 1,
			),

			// Checkbox select for login authentication based on last login time.
			'enable_email_auth_for_login_time' => array(
				'type'   => 'checkbox_select',
				'title'  => __( 'Last login more than', 'customer-email-verification' ),
				'select' => array(
					'options' => array(
						15 => __( '15 Days', 'customer-email-verification' ),
						30 => __( '30 Days', 'customer-email-verification' ),
						60 => __( '60 Days', 'customer-email-verification' ),
					),
					'id' => 'cev_last_login_more_then_time',
				),
				'show'    => true,
				'id'      => 'enable_email_auth_for_login_time',
				'Default' => 1,
				'class'   => 'enable_email_auth_for_login_time',
			),
		);

		return $form_data;
	}

	/**
	 * Generate HTML for the settings fields based on the provided settings array.
	 *
	 * Renders form fields in an unordered list structure, handling different
	 * field types (dropdown, checkbox, toggle, textarea, etc.) and applying
	 * conditional logic for visibility and disabled states.
	 *
	 * @since 1.0.0
	 * @param array $fields Settings fields array with field definitions.
	 * @return void
	 */
	public function render_settings_fields( $fields ) {
		// Prepare disabled states for all fields.
		$disabled_states = array();

		// Include settings fields list template.
		$template_path = $this->get_view_path( 'fields/settings-fields-list' );
		if ( file_exists( $template_path ) ) {
			$field_types_without_label = self::FIELD_TYPES_WITHOUT_LABEL;
			$settings_instance = $this;
			include $template_path;
		}
	}


	/**
	 * Render field label with optional tooltip.
	 *
	 * @since 1.0.0
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	private function render_field_label( $field, $disabled ) {
		$template_path = $this->get_view_path( 'fields/field-label' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render field based on its type.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	private function render_field_by_type( $field_id, $field, $disabled ) {
		switch ( $field['type'] ) {
			case 'dropdown':
				$this->render_dropdown_field( $field_id, $field, $disabled );
				break;

			case 'multiple_select':
				$this->render_multiple_select_field( $field_id, $field );
				break;

			case 'checkbox':
				$this->render_checkbox_field( $field_id, $field, $disabled );
				break;

			case 'toogel':
				$this->render_toggle_field( $field_id, $field );
				break;

			case 'textarea':
				$this->render_textarea_field( $field_id, $field );
				break;

			case 'checkbox_select':
				$this->render_checkbox_select_field( $field_id, $field, $disabled );
				break;

			case 'desc':
				$this->render_description_field( $field_id, $field, $disabled );
				break;

			case 'separator':
				$this->render_separator();
				break;

			default:
				$this->render_text_input_field( $field_id, $field );
				break;
		}
	}

	/**
	 * Render description field.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	private function render_description_field( $field_id, $field, $disabled ) {
		$template_path = $this->get_view_path( 'fields/field-description' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render separator element.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_separator() {
		$template_path = $this->get_view_path( 'fields/field-separator' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render checkbox_select field.
	 *
	 * Renders a checkbox with an associated select dropdown, typically used
	 * for conditional settings with multiple options.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	public function render_checkbox_select_field( $field_id, $field, $disabled ) {
		$template_path = $this->get_view_path( 'fields/field-checkbox-select' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render dropdown field.
	 *
	 * Renders a select dropdown with options from the field definition.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	public function render_dropdown_field( $field_id, $field, $disabled ) {
		$template_path = $this->get_view_path( 'fields/field-dropdown' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render multiple select field.
	 *
	 * Renders a multi-select dropdown for selecting multiple options,
	 * typically used for role selection.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @return void
	 */
	public function render_multiple_select_field( $field_id, $field ) {
		$template_path = $this->get_view_path( 'fields/field-multiple-select' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render checkbox field.
	 *
	 * Renders a standard checkbox input with label.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @param string $disabled Disabled state attribute.
	 * @return void
	 */
	public function render_checkbox_field( $field_id, $field, $disabled ) {
		$template_path = $this->get_view_path( 'fields/field-checkbox' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render toggle field.
	 *
	 * Renders a toggle switch (styled checkbox) with label.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @return void
	 */
	public function render_toggle_field( $field_id, $field ) {
		$template_path = $this->get_view_path( 'fields/field-toggle' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render textarea field.
	 *
	 * Renders a textarea input with optional placeholder.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @return void
	 */
	public function render_textarea_field( $field_id, $field ) {
		$template_path = $this->get_view_path( 'fields/field-textarea' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render text input field.
	 *
	 * Renders a standard text input with optional placeholder.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field    Field definition array.
	 * @return void
	 */
	public function render_text_input_field( $field_id, $field ) {
		$template_path = $this->get_view_path( 'fields/field-text-input' );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Handle AJAX request to update Customer Email Verification settings form.
	 *
	 * Verifies nonce, sanitizes input, and updates all settings fields
	 * including general settings, login OTP settings, and verification
	 * email body based on verification type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cev_settings_form_update_fun() {
		try {
			// Verify nonce for security.
			if ( empty( $_POST ) || ! check_admin_referer( self::NONCE_ACTION, self::NONCE_ACTION ) ) {
				CEV_Ajax_Response::send_nonce_error();
				return;
			}

			// Sanitize POST data after nonce verification.
			$sanitized_post = array();
			foreach ( $_POST as $key => $value ) {
				if ( is_array( $value ) ) {
					$sanitized_post[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
				} else {
					$sanitized_post[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}

			// Fetch all settings data.
			$general_settings        = $this->get_cev_settings_data_new();
			$login_otp_settings     = $this->get_cev_settings_data_login_otp();
			$general_settings_data  = $this->get_cev_general_settings_data();

			// Update toggle/select fields that need special handling.
			$this->update_toggle_fields( $sanitized_post );

			// Update all settings sections.
			$this->update_settings( $general_settings, $sanitized_post );
			$this->update_settings( $login_otp_settings, $sanitized_post );
			$this->update_settings( $general_settings_data, $sanitized_post );

			// Update verification email body based on verification type.
			$this->update_verification_email_body( $sanitized_post );

			// Send success response.
			CEV_Ajax_Response::send_success(
				array( 'isUpdated' => true ),
				__( 'Settings saved successfully.', 'customer-email-verification' ),
				'settings_saved'
			);
		} catch ( Exception $e ) {
			error_log( 'CEV Admin Settings Update Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Update toggle fields that need special handling.
	 *
	 * @since 1.0.0
	 * @param array $sanitized_data Sanitized POST data.
	 * @return void
	 */
	private function update_toggle_fields( $sanitized_data ) {
		$toggle_fields = array(
			'cev_enable_email_verification',
			'cev_enable_email_verification_checkout',
			'cev_enable_login_authentication',
			self::VERIFICATION_TYPE_OPTION,
		);

		foreach ( $toggle_fields as $field ) {
			if ( isset( $sanitized_data[ $field ] ) ) {
				$value = $sanitized_data[ $field ];
				// Handle array values from checkboxes (hidden input + checkbox both submit).
				if ( is_array( $value ) ) {
					// Take the last value (checkbox value '1' if checked, or '0' if unchecked).
					$value = end( $value );
				}
				$this->update_setting( $field, $value );
			}
		}
	}

	/**
	 * Update verification email body based on verification type.
	 *
	 * @since 1.0.0
	 * @param array $sanitized_data Sanitized POST data.
	 * @return void
	 */
	private function update_verification_email_body( $sanitized_data ) {
		if ( isset( $sanitized_data[ self::VERIFICATION_TYPE_OPTION ] ) ) {
			$verification_type = $sanitized_data[ self::VERIFICATION_TYPE_OPTION ];
			// Handle array values from checkboxes (hidden input + checkbox both submit).
			if ( is_array( $verification_type ) ) {
				$verification_type = end( $verification_type );
			}
			$email_body = $this->get_verification_email_body( $verification_type );
			$this->update_setting( self::VERIFICATION_EMAIL_BODY_OPTION, $email_body );
		}
	}

	/**
	 * Update settings from a given settings array and request data.
	 *
	 * Iterates through settings array and updates each field value from
	 * the request data. Handles special field types like multiple_select
	 * and checkbox_select differently.
	 *
	 * @since 1.0.0
	 * @param array $settings The settings data array.
	 * @param array $request  The request data (typically $_POST).
	 * @return void
	 */
	private function update_settings( $settings, $request ) {
		foreach ( $settings as $key => $field ) {
			if ( isset( $request[ $key ] ) ) {
				// Handle multiple select fields.
				if ( isset( $field['type'] ) && 'multiple_select' === $field['type'] ) {
					$this->update_multiple_select_field( $key, $field, $request );
				} elseif ( isset( $field['type'] ) && 'checkbox_select' === $field['type'] ) {
					$this->update_checkbox_select_field( $key, $field, $request );
				} else {
					// Handle checkbox/toggle fields that may have duplicate values.
					$value = $request[ $key ];
					if ( is_array( $value ) ) {
						// For checkbox/toggle fields, take the last value (checkbox value '1' if checked).
						$value = end( $value );
					}
					// Update standard fields (data is already sanitized).
					$this->update_setting( $key, $value );
				}
			} elseif ( isset( $field['type'] ) && 'multiple_select' === $field['type'] ) {
				// Clear multiple select field if not set.
				$this->update_setting( $key, '' );
			} elseif ( isset( $field['type'] ) && ( 'checkbox' === $field['type'] || 'toogel' === $field['type'] ) ) {
				// Set unchecked checkbox/toggle to 0 if not in request.
				$this->update_setting( $key, 0 );
			}
		}
	}

	/**
	 * Update a multiple select field.
	 *
	 * Processes selected options from a multiple select field and saves
	 * them as an array with keys representing options and values (1 or 0)
	 * indicating selection status.
	 *
	 * @since 1.0.0
	 * @param string $key     The field key.
	 * @param array  $field   The field data.
	 * @param array  $request The request data.
	 * @return void
	 */
	private function update_multiple_select_field( $key, $field, $request ) {
		if ( ! isset( $request[ $key ] ) || ! is_array( $request[ $key ] ) ) {
			return;
		}

		// Initialize all options as unselected.
		$roles = array();
		foreach ( $field['options'] as $option_key => $option_val ) {
			$roles[ $option_key ] = 0;
		}

		// Mark selected options (data is already sanitized).
		$selected_options = $request[ $key ];
		foreach ( $selected_options as $selected_option ) {
			if ( isset( $roles[ $selected_option ] ) ) {
				$roles[ $selected_option ] = 1;
			}
		}

		$this->update_setting( $key, $roles );
	}

	/**
	 * Update a checkbox_select field.
	 *
	 * Updates both the checkbox state and the associated select field value.
	 *
	 * @since 1.0.0
	 * @param string $key     The field key.
	 * @param array  $field   The field data.
	 * @param array  $request The request data.
	 * @return void
	 */
	private function update_checkbox_select_field( $key, $field, $request ) {
		// Update checkbox value (data is already sanitized).
		if ( isset( $request[ $key ] ) ) {
			$value = $request[ $key ];
			// Handle array values from checkboxes (hidden input + checkbox both submit).
			if ( is_array( $value ) ) {
				$value = end( $value );
			}
			$this->update_setting( $key, $value );
		} else {
			// Set unchecked checkbox to 0.
			$this->update_setting( $key, 0 );
		}

		// Update associated select field if present (data is already sanitized).
		if ( isset( $field['select']['id'] ) && isset( $request[ $field['select']['id'] ] ) ) {
			$select_value = $request[ $field['select']['id'] ];
			// Handle array values if present.
			if ( is_array( $select_value ) ) {
				$select_value = end( $select_value );
			}
			$this->update_setting( $field['select']['id'], $select_value );
		}
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
}
