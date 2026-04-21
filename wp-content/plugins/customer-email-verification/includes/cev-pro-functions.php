<?php
/**
 * Shared Utility Functions for Customer Email Verification
 *
 * This file contains common utility functions used across multiple modules
 * of the Customer Email Verification plugin. These functions handle:
 * - Email verification status checks
 * - Verification PIN generation
 * - Resend limit checking and management
 * - User meta operations for verification
 *
 * @package Customer_Email_Verification
 * @since 2.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Functions_Pro Class
 *
 * Singleton class containing utility methods for Customer Email Verification.
 * Handles email verification status checks, PIN generation, resend limits,
 * and user meta operations.
 *
 * @package Customer_Email_Verification
 * @since 2.8.6
 */
class CEV_Functions_Pro {

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Functions_Pro|null Class Instance
	 */
	private static $instance = null;
	public $wuev_user_id = null;
	public $email_type = null;
	public $login_ip = null;
	public $login_time = null;
	public $login_browser = null;
	public $login_device = null;
	public $wuev_myaccount_page_id = null;
	public $registered_user_email = null;
	/**
	 * Login OTP value used for merge tags in login/auth emails.
	 *
	 * @since 2.8.13
	 * @var string|null
	 */
	public $login_otp = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 2.8.6
	 */
	private function __construct() {
		// Private constructor to enforce singleton pattern.
	}

	/**
	 * Get the class instance.
	 *
	 * @since 2.8.6
	 * @return CEV_Functions_Pro Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 2.8.6
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since 2.8.6
	 */
	public function __wakeup() {
		// Prevent unserializing.
	}

	/**
	 * Convert a hex color string to an RGB(A) string.
	 *
	 * Example usage:
	 * - hex2rgba('#ff0000') → 'rgb(255,0,0)'
	 * - hex2rgba('#ff0000', 0.5) → 'rgba(255,0,0,0.5)'
	 *
	 * @param string     $color   Hexadecimal color code (e.g., "#ff0000" or "ff0000").
	 * @param float|bool $opacity Optional. Opacity level (0 to 1). Default is false (returns RGB).
	 *
	 * @return string RGB or RGBA color string.
	 */
	public function hex2rgba( $color, $opacity = false ) {
		$default = 'rgba(116,194,225,0.7)'; // Default fallback color.

		// Return default if no color is provided.
		if ( empty( $color ) ) {
			return $default;
		}

		// Remove '#' if present.
		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}

		// Validate color format and extract RGB components.
		if ( 6 === strlen( $color ) ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( 3 === strlen( $color ) ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		// Convert hex values to decimal RGB.
		$rgb = array_map( 'hexdec', $hex );

		// Determine if opacity is set (RGBA or RGB).
		if ( false !== $opacity ) {
			$opacity = ( abs( $opacity ) > 1 ) ? 1.0 : $opacity;
			return 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		}

		return 'rgb(' . implode( ',', $rgb ) . ')';
	}

	/**
	 * Check if an email address is verified.
	 *
	 * For registered users, checks the 'customer_email_verified' user meta.
	 * For guest users, checks if they have completed orders with valid statuses.
	 *
	 * @since 2.8.6
	 * @param string $email Email address to check.
	 * @return bool True if verified, false otherwise.
	 */
	public function check_email_verify( $email ) {
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		$user = get_user_by( 'email', $email );

		// For registered users, check user meta.
		if ( $user ) {
			$user_id  = $user->ID;
			$verified = get_user_meta( $user_id, 'customer_email_verified', true );
			return ( 'true' === $verified );
		}

		// For guest users, check if they have completed orders.
		$valid_statuses = array( 'wc-processing', 'wc-completed', 'wc-on-hold' );
		$orders         = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => 1,
				'status'        => $valid_statuses,
			)
		);

		return ! empty( $orders );
	}

	/**
	 * Generate a verification PIN code.
	 *
	 * Generates a random numeric PIN based on the configured code length setting.
	 * Default length is 4 digits, but can be 4 or 6 digits based on admin settings.
	 *
	 * @since 2.8.6
	 * @return string Generated PIN code.
	 */
	public function generate_verification_pin() {
		$code_length = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', '1' );
		$code_length = (string) $code_length; // Ensure string type for consistent comparison

		if ( '1' == $code_length ) {
			$digits = 4;
		} elseif ( '2' == $code_length ) {
			$digits = 6;
		} else {
			$digits = 4; // Default to 4 digits if invalid setting
		}

		$pin = '';
		for ( $i = 0; $i < $digits; $i++ ) {
			// Generate a random number between 0 and 9.
			$pin .= random_int( 0, 9 );
		}

		return $pin;
	}

	/**
	 * Check if resend limit has been reached for a user.
	 *
	 * Compares the user's resend count against the configured limit.
	 * Returns true if the user has reached or exceeded the limit.
	 *
	 * @since 2.8.6
	 * @param int $user_id User ID to check.
	 * @return bool True if limit reached, false otherwise.
	 */
	public function is_resend_limit_reached( $user_id ) {
		// Validate user ID is a positive integer.
		if ( empty( $user_id ) || $user_id <= 0 || ! is_numeric( $user_id ) ) {
			return false;
		}

		// Check if user exists.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$resend_times = (int) get_user_meta( $user_id, 'cev_user_resend_times', true );
		$limit        = (int) $this->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );

		return $resend_times >= $limit;
	}

	/**
	 * Get resend count for a user.
	 *
	 * @since 2.8.6
	 * @param int $user_id User ID.
	 * @return int Current resend count.
	 */
	public function get_resend_count( $user_id ) {
		// Validate user ID is a positive integer.
		if ( empty( $user_id ) || $user_id <= 0 || ! is_numeric( $user_id ) ) {
			return 0;
		}

		// Check if user exists.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return 0;
		}

		return (int) get_user_meta( $user_id, 'cev_user_resend_times', true );
	}

	/**
	 * Increment resend counter for a user.
	 *
	 * @since 2.8.6
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function increment_resend_counter( $user_id ) {
		// Validate user ID is a positive integer.
		if ( empty( $user_id ) || $user_id <= 0 || ! is_numeric( $user_id ) ) {
			return;
		}

		// Check if user exists.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		$resend_times = $this->get_resend_count( $user_id );
		update_user_meta( $user_id, 'cev_user_resend_times', $resend_times + 1 );
	}

	/**
	 * Get a plugin admin setting value from the consolidated settings option.
	 *
	 * Retrieves a setting value from the consolidated 'cev_pro_admin_settings' option.
	 * Falls back to individual option if migration hasn't been completed yet
	 * (backward compatibility). This function should be used instead of get_option()
	 * for all plugin admin settings.
	 *
	 * @since 2.9
	 * @param string $key     Setting key to retrieve.
	 * @param mixed  $default Default value if setting doesn't exist.
	 * @return mixed Setting value or default.
	 */
	public function cev_pro_admin_settings( $key, $default = false ) {
		$consolidated_settings = get_option( 'cev_pro_admin_settings', array() );

		// If consolidated settings exist and key is present, return it.
		if ( is_array( $consolidated_settings ) && isset( $consolidated_settings[ $key ] ) ) {
			return $consolidated_settings[ $key ];
		}

		// Fallback to individual option for backward compatibility.
		return get_option( $key, $default );
	}

	/**
	 * Get a customizer setting value from the consolidated customizer option.
	 *
	 * Retrieves a setting value from the consolidated 'cev_pro_customizer' option.
	 * Falls back to individual option if migration hasn't been completed yet
	 * (backward compatibility). This function should be used instead of get_option()
	 * for all customizer settings.
	 *
	 * If the retrieved value is empty, null, or whitespace-only, it will automatically
	 * fall back to the provided default value to ensure emails always have valid content.
	 *
	 * @since 2.9.0
	 * @param string $key     Setting key to retrieve.
	 * @param mixed  $default Default value if setting doesn't exist or is empty.
	 * @return mixed Setting value or default.
	 */
	public function cev_pro_customizer_settings( $key, $default = false ) {
		$consolidated_settings = get_option( 'cev_pro_customizer', array() );
		$value = false;

		// If consolidated settings exist and key is present, get the value.
		if ( is_array( $consolidated_settings ) && isset( $consolidated_settings[ $key ] ) ) {
			$value = $consolidated_settings[ $key ];
		} else {
			// Fallback to individual option for backward compatibility.
			$value = get_option( $key, false );
		}

		// If value is empty, null, or whitespace-only, fall back to default.
		// This ensures emails always have valid content (subject, heading, body).
		if ( false === $value || null === $value || '' === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Send verification code email
	 *
	 * Generates a verification PIN, stores it in user meta, and sends an email
	 * with the verification code to the recipient.
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address to send verification code to.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public function code_mail_sender( $recipient ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return false;
		}
		
		$user_id = $this->wuev_user_id;
		if ( empty( $user_id ) || ! get_user_by( 'ID', $user_id ) ) {
			return false;
		}
		
		$verification_pin = $this->generate_verification_pin();
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		
		$expire_time = $this->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );
		
		if ( empty( $expire_time ) ) {
			$expire_time = 'never';
		}
		
		// Calculate enddate - handle 'never' case properly.
		if ( 'never' === $expire_time ) {
			$enddate = 0; // Never expires.
		} else {
			$enddate = time() + (int) $expire_time;
		}
		
		$verification_data = array(
			'pin' => $verification_pin, 
			'startdate' => time(),
			'enddate' => $enddate,
		);		

		update_user_meta( $user_id, 'cev_email_verification_pin', $verification_data );
		
		$result        = false;
		$email_subject = $this->cev_pro_customizer_settings( 'cev_verification_email_subject', $CEV_Customizer_Options->defaults['cev_verification_email_subject'] );
		$email_subject = $this->maybe_parse_merge_tags( $email_subject, $this );
		$email_heading = $this->cev_pro_customizer_settings( 'cev_verification_email_heading', $CEV_Customizer_Options->defaults['cev_verification_email_heading'] );		
		
		$mailer = WC()->mailer();
	
		$content = $this->cev_pro_customizer_settings( 'cev_verification_email_body', $CEV_Customizer_Options->defaults['cev_verification_email_body'] );
		$content = $this->maybe_parse_merge_tags( $content, $this );
		$footer_content = $this->cev_pro_customizer_settings( 'cev_new_verification_Footer_content', '' );
		
		$email_content = '';
		
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			$email_content .= wc_get_template_html( 'emails/cev-email-verification.php', array(
				'email_heading' => $email_heading,
				'content' => $content,
				'footer_content' => $footer_content,
			), 'customer-email-verification/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$email_content .= wc_get_template_html( 'emails/cev-email-verification.php', array( 
				'email_heading' => $email_heading,
				'content' => $content,
				'footer_content' => $footer_content,					
			), 'customer-email-verification/', cev_pro()->get_plugin_path() . '/templates/' );
		}
		
		// create a new email
		$email = new WC_Email();
		$email->id = 'CEV_Registration_Verification';
		$email_body = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );
		$email_body = apply_filters( 'wc_cev_decode_html_content', $email_body );
		
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
				
		$result = wp_mail( $recipient, $email_subject, $email_body, $email->get_headers() );

		return $result;
	}

	/**
	 * Maybe try and parse content to found the merge tags.
	 *
	 * Converts merge tags to their actual values in email content.
	 * Supports both {{tag}} and {tag} formats.
	 * This is a consolidated method moved from multiple classes to eliminate duplication.
	 *
	 * @since 2.8.6
	 * @param string $content Content to parse.
	 * @param object $context_object Object that contains get_all_tags() method and merge tag callback methods.
	 * @return string Parsed content with merge tags replaced.
	 */
	public function maybe_parse_merge_tags( $content = '', $context_object = null ) {
		if ( empty( $content ) ) {
			return $content;
		}

		// If no context object provided, use the functions class as default.
		if ( null === $context_object ) {
			$context_object = $this;
		}

		// Determine context type for get_all_tags.
		// Use 'full' context to allow all merge tags (login_browser, login_device, etc.) to work.
		// The individual methods will handle context-specific logic internally.
		$context_type = 'full';

		// Get all available merge tags from functions class.
		$get_all      = $this->get_all_tags( $context_type );
		$get_all_tags = wp_list_pluck( $get_all, 'tag' );

		if ( empty( $get_all_tags ) || ! is_array( $get_all_tags ) ) {
			return $content;
		}

		// Parse both {{tag}} and {tag} formats in a single pass.
		$content = $this->parse_merge_tags_format( $content, $get_all_tags, $context_object );

		return $content;
	}

	/**
	 * Parse merge tags in both {{tag}} and {tag} formats.
	 *
	 * Helper method for maybe_parse_merge_tags(). Optimized to parse both formats in a single pass.
	 *
	 * @since 2.8.6
	 * @param string $content        Content to parse.
	 * @param array  $tags           Array of tag names.
	 * @param object $context_object Object that contains merge tag callback methods.
	 * @return string Parsed content.
	 */
	private function parse_merge_tags_format( $content, $tags, $context_object ) {
		foreach ( $tags as $tag ) {
			// Simplified regex patterns without unnecessary capture groups.
			$patterns = array(
				'/\{{' . preg_quote( $tag, '/' ) . '\}}/',  // {{tag}} format.
				'/\{' . preg_quote( $tag, '/' ) . '\}/',    // {tag} format.
			);

			foreach ( $patterns as $pattern ) {
				$matches = array();
				preg_match_all( $pattern, $content, $matches );

				if ( ! empty( $matches[0] ) && is_array( $matches[0] ) ) {
					foreach ( $matches[0] as $exact_match ) {
						$old_match = $exact_match;
						$single    = str_replace( array( '{{', '}}', '{', '}' ), '', $old_match );

						// Unified method resolution - check functions class first.
						if ( method_exists( $this, $single ) ) {
							// Check if method is static (like site_title).
							$reflection = new ReflectionMethod( $this, $single );
							if ( $reflection->isStatic() ) {
								// Call static method.
								try {
									$get_parsed_value = call_user_func( array( get_class( $this ), $single ) );
								} catch ( Exception $e ) {
									// Log error and skip this tag.
									error_log( 'CEV: Error parsing merge tag ' . $single . ': ' . $e->getMessage() );
									continue;
								}
							} else {
								// Call instance method with context object.
								try {
									$get_parsed_value = call_user_func( array( $this, $single ), $context_object );
								} catch ( Exception $e ) {
									// Log error and skip this tag.
									error_log( 'CEV: Error parsing merge tag ' . $single . ': ' . $e->getMessage() );
									continue;
								}
							}

							// Validate return type is string.
							if ( ! is_string( $get_parsed_value ) ) {
								$get_parsed_value = (string) $get_parsed_value;
							}

							$content = str_replace( $old_match, $get_parsed_value, $content );
						} elseif ( method_exists( $context_object, $single ) ) {
							// Fallback: Check if callback method exists in context object (for backward compatibility).
							try {
								$get_parsed_value = call_user_func( array( $context_object, $single ) );
								// Validate return type is string.
								if ( ! is_string( $get_parsed_value ) ) {
									$get_parsed_value = (string) $get_parsed_value;
								}
								$content = str_replace( $old_match, $get_parsed_value, $content );
							} catch ( Exception $e ) {
								// Log error and skip this tag.
								error_log( 'CEV: Error parsing merge tag ' . $single . ': ' . $e->getMessage() );
								continue;
							}
						}
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Get all available merge tags.
	 *
	 * Returns array of merge tags based on context type.
	 * This is a consolidated method moved from multiple classes to eliminate duplication.
	 *
	 * @since 2.8.6
	 * @param string $context_type Type of context: 'full' for all tags (default), 'checkout' for checkout-specific tags only.
	 * @return array Array of merge tags with name and tag keys.
	 */
	public function get_all_tags( $context_type = 'full' ) {
		if ( 'checkout' === $context_type ) {
			// Checkout-specific tags (limited set).
			return array(
				array(
					'name' => __( 'Verification Pin', 'customer-email-verification' ),
					'tag'  => 'cev_user_verification_pin',
				),
				array(
					'name' => __( 'Site Title', 'customer-email-verification' ),
					'tag'  => 'site_title',
				),
			);
		}

		// Full set of tags (default).
		return array(
			array(
				'name' => __( 'User login', 'customer-email-verification' ),
				'tag'  => 'cev_user_login',
			),
			array(
				'name' => __( 'User display name', 'customer-email-verification' ),
				'tag'  => 'cev_display_name',
			),
			array(
				'name' => __( 'User email', 'customer-email-verification' ),
				'tag'  => 'cev_user_email',
			),
			array(
				'name' => __( 'Verification Pin', 'customer-email-verification' ),
				'tag'  => 'cev_user_verification_pin',
			),
			array(
				'name' => __( 'Site Title', 'customer-email-verification' ),
				'tag'  => 'site_title',
			),
			array(
				'name' => __( 'Try Again', 'customer-email-verification' ),
				'tag'  => 'cev_resend_verification',
			),
			array(
				'name' => __( 'Login IP', 'customer-email-verification' ),
				'tag'  => 'login_ip',
			),
			array(
				'name' => __( 'Login Time', 'customer-email-verification' ),
				'tag'  => 'login_time',
			),
			array(
				'name' => __( 'Login Browser', 'customer-email-verification' ),
				'tag'  => 'login_browser',
			),
			array(
				'name' => __( 'Login Device', 'customer-email-verification' ),
				'tag'  => 'login_device',
			),
			array(
				'name' => __( 'Cancel Verification', 'customer-email-verification' ),
				'tag'  => 'cev_cancel_verification',
			),
			array(
				'name' => __( 'Login OTP', 'customer-email-verification' ),
				'tag'  => 'login_otp',
			),
			array(
				'name' => __( 'Change Password Button', 'customer-email-verification' ),
				'tag'  => 'change_password',
			),
		);
	}

	/**
	 * Return Email Verification pin (alias method that routes based on context).
	 * 
	 * This method routes to the appropriate PIN retrieval method based on the context object type.
	 * For backward compatibility with merge tag {cev_user_verification_pin}.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object to determine user type.
	 * @return string Verification pin HTML.
	 */
	public function cev_user_verification_pin( $context_object = null ) {
		// Validate context object - if null or not an object, default to registered user context.
		if ( null === $context_object || ! is_object( $context_object ) ) {
			$context_object = $this;
		}

		// If a login OTP value is present on the context (e.g. for login OTP
		// emails), prefer that directly. This allows {cev_user_verification_pin}
		// to be used for login OTP emails and previews.
		if ( isset( $context_object->login_otp ) && '' !== $context_object->login_otp ) {
			return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $context_object->login_otp ) . '</span></span>';
		}
		
		// Check if this is a checkout preview context (has session data but not a CEV_Checkout_Email instance).
		$is_checkout_preview = ( isset( $context_object->is_checkout_preview ) && $context_object->is_checkout_preview );
		
		// Route to appropriate method based on context object type.
		// CEV_Checkout_Email indicates guest user during checkout.
		if ( is_a( $context_object, 'CEV_Checkout_Email' ) || $is_checkout_preview ) {
			// Guest user context - get PIN from WooCommerce session.
			return $this->cev_guest_user_verification_pin();
		} else {
			// Registered user context - get PIN from database or user meta.
			return $this->cev_registered_user_verification_pin( $context_object );
		}
	}

	/**
	 * Return verification PIN for registered users.
	 * 
	 * Gets PIN from database (cev_user_log table) or user meta for registered users.
	 * This is a consolidated method moved from Email Common class to eliminate duplication.
	 *
	 * @since 2.8.6
	 * @param object $context_object Context object that contains registered_user_email and wuev_user_id properties.
	 * @return string Verification PIN wrapped in span tag.
	 */
	public function cev_registered_user_verification_pin( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}

		$user_email = isset( $context_object->registered_user_email ) ? $context_object->registered_user_email : null;
		
		if ( empty( $user_email ) ) {
			return '<span></span>';
		}
		
		global $wpdb;
		
		// Optimized: Single query to get PIN from database instead of two queries.
		try {
			$verification_pin = $wpdb->get_var( $wpdb->prepare( "SELECT pin FROM {$wpdb->prefix}cev_user_log WHERE email = %s LIMIT 1", $user_email ) );
		} catch ( Exception $e ) {
			error_log( 'CEV: Database error getting PIN from cev_user_log: ' . $e->getMessage() );
			$verification_pin = null;
		}
		
		if ( $verification_pin ) {
			// PIN found in database.
			return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $verification_pin ) . '</span></span>';
		}
		
		// PIN not found in database, check user meta.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( empty( $user_id ) ) {
			return '<span></span>';
		}
		
		try {
			$cev_email_verification_pin = get_user_meta( $user_id, 'cev_email_verification_pin', true );
			if ( ! empty( $cev_email_verification_pin ) && isset( $cev_email_verification_pin['pin'] ) ) {
				$verification_pin = $cev_email_verification_pin['pin'];
				return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $verification_pin ) . '</span></span>';
			}
		} catch ( Exception $e ) {
			error_log( 'CEV: Error getting PIN from user meta: ' . $e->getMessage() );
		}
		
		// PIN not found, generate new one.
		$verification_pin = $this->generate_verification_pin();
		return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $verification_pin ) . '</span></span>';
	}

	/**
	 * Return Email Verification pin for guest users.
	 * 
	 * Gets PIN from WooCommerce session data for guest users during checkout.
	 * This is a consolidated method moved from Checkout Email class to eliminate duplication.
	 *
	 * @since 2.8.6
	 * @return string Verification pin HTML.
	 */
	public function cev_guest_user_verification_pin() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '';
		}

		$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
		$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

		if ( empty( $cev_user_verification_data ) || ! isset( $cev_user_verification_data->pin ) ) {
			return '';
		}

		$pin = base64_decode( $cev_user_verification_data->pin ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $pin ) . '</span></span>';
	}

	/**
	 * Return cancel verification link.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @return string Cancel verification link HTML.
	 */
	public function cev_cancel_verification() {
		ob_start(); 
		?>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="">
				<?php esc_html_e( 'Cancel Verification', 'customer-email-verification' ); ?>
			</a>
			<?php 
		$logout = ob_get_clean();
		return $logout;
	}

	/**
	 * Return user login name.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id property.
	 * @return string User login name.
	 */
	public function cev_user_login( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$user = get_userdata( $context_object->wuev_user_id );
		if ( ! $user ) {
			return '';
		}
		return $user->user_login;
	}

	/**
	 * Return user email address.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id property.
	 * @return string User email address.
	 */
	public function cev_user_email( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$user = get_userdata( $context_object->wuev_user_id );
		if ( ! $user ) {
			return '';
		}
		return $user->user_email;
	}

	/**
	 * Return user display name.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id property.
	 * @return string User display name.
	 */
	public function cev_display_name( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		// For checkout emails (guest users), return site title or empty.
		$email_type = isset( $context_object->email_type ) ? $context_object->email_type : null;
		if ( 'checkout' == $email_type ) {
			// For guest users, return site title as display name.
			return get_bloginfo( 'name' );
		}
		// For registered users, get from user data.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( ! empty( $user_id ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				return $user->display_name;
			}
		}
		return '';
	}

	/**
	 * Return site title.
	 * 
	 * Returns the WordPress site name for use in merge tags.
	 * This is a static method that doesn't require context.
	 *
	 * @since 2.8.6
	 * @return string Site title/name.
	 */
	public static function site_title() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Return resend verification link.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_myaccount_page_id property.
	 * @return string Resend verification link HTML.
	 */
	public function cev_resend_verification( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		
		$resend_limit_reached = $this->is_resend_limit_reached( get_current_user_id() );
		if ( 'login-authentication' == WC()->query->get_current_endpoint() ) {
			$resend_email_link = add_query_arg( array('cev_resend_login_otp_email' => base64_encode( get_current_user_id() ),), get_the_permalink( $context_object->wuev_myaccount_page_id ) );
		} else {
			$resend_email_link = add_query_arg( array('cev_redirect_limit_resend' => base64_encode( get_current_user_id() ),), get_the_permalink( $context_object->wuev_myaccount_page_id ) );
		}
		
		// On account pages, logged-in users navigate via URL to MY ACCOUNT to trigger resend.
		// Everywhere else (cart, checkout, etc.) use AJAX resend — works for both logged-in and guest.
		if ( is_user_logged_in() && is_account_page() ) {
			ob_start(); 
			$class = ( $resend_limit_reached ) ? 'cev-try-again-disable' : '';	
			?>
			<a href="<?php echo esc_html( $resend_email_link ); ?>" class="cev-link-try-again <?php echo esc_html( $class ); ?>"><?php esc_html_e( 'Try Again', 'customer-email-verification' ); ?></a>
			<?php 
			$try_again_url = ob_get_clean();
			return $try_again_url;
		} else { 
			$class = ( $resend_limit_reached ) ? 'cev-try-again-disable' : '';	
			if ( is_account_page() ) {
				ob_start(); 
				?>
				<a href="#" class="cev-link-try-again send_again_link  <?php echo esc_html( $class ); ?>"><?php esc_html_e( 'Try Again', 'customer-email-verification' ); ?></a>
				<?php 
				$try_again_url = ob_get_clean();
				return $try_again_url;
			} else {
				ob_start(); 
				?>
				<a href="#" class="cev-link-try-again resend_verification_code_guest_user  <?php echo esc_html( $class ); ?>" data-nonce="<?php esc_html_e( wp_create_nonce( 'wc_cev_email_guest_user' ) ); ?>"><?php esc_html_e( 'Try Again', 'customer-email-verification' ); ?></a>
				<?php 
				$try_again_url = ob_get_clean();
				return $try_again_url;
			}
		}		
	}


	/**
	 * Return login IP address.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id, email_type, and login_ip properties.
	 * @return string Login IP address.
	 */
	public function login_ip( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$email_type = isset( $context_object->email_type ) ? $context_object->email_type : null;
		// For login_otp and checkout email types, use context object properties.
		if ( 'login_otp' == $email_type || 'checkout' == $email_type ) {
			if ( isset( $context_object->login_ip ) ) {
				return $context_object->login_ip;
			}
		}
		// For registered users, get from user meta.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( ! empty( $user_id ) ) {
			$user_last_login_details = get_user_meta( $user_id, 'cev_last_login_detail', true );
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details['last_login_ip'] ) ) {
				return $user_last_login_details['last_login_ip'];
			}
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details[0]['last_login_ip'] ) ) {
				return $user_last_login_details[0]['last_login_ip'];
			}
		}
		// Default fallback.
		return '192.0.2.0';
	}

	/**
	 * Return login time.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id, email_type, and login_time properties.
	 * @return string Login time.
	 */
	public function login_time( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$email_type = isset( $context_object->email_type ) ? $context_object->email_type : null;
		// For login_otp and checkout email types, use context object properties.
		if ( 'login_otp' == $email_type || 'checkout' == $email_type ) {
			if ( isset( $context_object->login_time ) ) {
				return $context_object->login_time;
			}
		}
		// For registered users, get from user meta.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( ! empty( $user_id ) ) {
			$user_last_login_time = get_user_meta( $user_id, 'cev_last_login_time', true );
			if ( ! empty( $user_last_login_time ) ) {
				return $user_last_login_time;
			}
		}
		// Default fallback.
		return current_time( 'mysql' );
	}

	/**
	 * Return login browser.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id, email_type, and login_browser properties.
	 * @return string Login browser name.
	 */
	public function login_browser( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$email_type = isset( $context_object->email_type ) ? $context_object->email_type : null;
		// For login_otp and checkout email types, use context object properties.
		if ( 'login_otp' == $email_type || 'checkout' == $email_type ) {
			if ( isset( $context_object->login_browser ) ) {
				return $context_object->login_browser;
			}
		}
		// For registered users, get from user meta.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( ! empty( $user_id ) ) {
			$user_last_login_details = get_user_meta( $user_id, 'cev_last_login_detail', true );
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details['last_login_browser'] ) ) {
				return $user_last_login_details['last_login_browser'];
			}
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details[0]['last_login_browser'] ) ) {
				return $user_last_login_details[0]['last_login_browser'];
			}
		}
		// Default fallback.
		return 'Chrome';
	}

	/**
	 * Return login device.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param object|null $context_object Context object with wuev_user_id, email_type, and login_device properties.
	 * @return string Login device name.
	 */
	public function login_device( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}
		$email_type = isset( $context_object->email_type ) ? $context_object->email_type : null;
		// For login_otp and checkout email types, use context object properties.
		if ( 'login_otp' == $email_type || 'checkout' == $email_type ) {
			if ( isset( $context_object->login_device ) ) {
				return $context_object->login_device;
			}
		}
		// For registered users, get from user meta.
		$user_id = isset( $context_object->wuev_user_id ) ? $context_object->wuev_user_id : null;
		if ( ! empty( $user_id ) ) {
			$user_last_login_details = get_user_meta( $user_id, 'cev_last_login_detail', true );
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details['last_login_device'] ) ) {
				return $user_last_login_details['last_login_device'];
			}
			if ( is_array( $user_last_login_details ) && isset( $user_last_login_details[0]['last_login_device'] ) ) {
				return $user_last_login_details[0]['last_login_device'];
			}
		}
		// Default fallback.
		return 'Windows';
	}

	/**
	 * Return login OTP value for {login_otp} merge tag.
	 *
	 * @since 2.9.0
	 * @param object|null $context_object Context object with login_otp property.
	 * @return string Styled OTP box HTML or empty string.
	 */
	public function login_otp( $context_object = null ) {
		if ( null === $context_object ) {
			$context_object = $this;
		}

		if ( isset( $context_object->login_otp ) && '' !== $context_object->login_otp ) {
			return '<span style="display:inline-block; width:100%; max-width:300px; background-color:#f8f8fc; border:2px dashed #d0d0e0; border-radius:14px; padding:20px 16px 24px 16px; text-align:center; margin:16px 0; font-weight:normal; box-sizing:border-box;"><span style="display:block; font-size:12px; font-weight:600; color:#8888a0; text-transform:uppercase; letter-spacing:2px; font-family:Arial,Helvetica,sans-serif; margin-bottom:12px;">Your OTP Code</span><span style="display:block; font-size:36px; font-weight:800; color:#1a1a2e; letter-spacing:8px; font-family:Courier,monospace;">' . esc_html( $context_object->login_otp ) . '</span></span>';
		}

		return '';
	}

	/**
	 * Return a Change Password button for {change_password} merge tag.
	 *
	 * @since 2.9.0
	 * @param object|null $context_object Unused, kept for signature consistency.
	 * @return string HTML anchor button linking to WooCommerce change password page.
	 */
	public function change_password( $context_object = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		// Get WooCommerce "Edit account" endpoint URL.
		$change_password_url = '';

		if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
			$change_password_url = wc_get_account_endpoint_url( 'edit-account' );
		}

		if ( empty( $change_password_url ) ) {
			$account_page_id = get_option( 'woocommerce_myaccount_page_id' );
			if ( $account_page_id ) {
				$change_password_url = get_permalink( $account_page_id );
			}
		}

		if ( empty( $change_password_url ) ) {
			return '';
		}

		$label = __( 'Change Password', 'customer-email-verification' );

		// Basic button-style link for emails.
		return '<a href="' . esc_url( $change_password_url ) . '" class="cev-change-password-button" style="display:inline-block;padding:10px 20px;background-color:#2271b1;color:#ffffff;text-decoration:none;border-radius:3px;">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Decode HTML content for email display.
	 * 
	 * Removes script tags, decodes HTML entities, and removes backslashes.
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param string $content Content to decode.
	 * @return string Decoded content.
	 */
	public function wc_cev_decode_html_content( $content ) {
		if ( empty( $content ) ) {
			return '';
		}
		$content = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $content );
		return html_entity_decode( stripslashes( $content ) );
	}

	/**
	 * Get the from name for outgoing emails.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @return string From name.
	 */
	public function get_from_name() {
		$from_name = apply_filters( 'woocommerce_email_from_name', get_option( 'woocommerce_email_from_name' ), $this );
		$from_name = apply_filters( 'cev_email_from_name', $from_name, $this );
		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @return string From address.
	 */
	public function get_from_address() {
		$from_address = apply_filters( 'woocommerce_email_from_address', get_option( 'woocommerce_email_from_address' ), $this );
		$from_address = apply_filters( 'cev_email_from_address', $from_address, $this );
		return sanitize_email( $from_address );
	}

	/**
	 * Allow style tags in HTML content.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param array $tags Allowed HTML tags.
	 * @return array Modified tags array.
	 */
	public function my_allowed_tags( $tags ) {
		$tags['style'] = array( 'type' => true );
		return $tags;
	}

	/**
	 * Allow display CSS style property.
	 * 
	 * Consolidated method moved from Email Common class.
	 *
	 * @since 2.8.6
	 * @param array $styles Safe CSS styles.
	 * @return array Modified styles array.
	 */
	public function safe_style_css_callback( $styles ) {
		$styles[] = 'display';
		return $styles;
	}

	/**
	 * Set up checkout email context properties for merge tag parsing.
	 * 
	 * Sets browser, device, IP, time, and other properties needed for merge tags
	 * like {login_browser}, {login_device}, {login_ip}, {login_time}, etc.
	 * This is a consolidated method moved from CEV_Checkout_Email class.
	 *
	 * @since 2.8.6
	 * @param object $context_object Context object to set properties on (typically CEV_Checkout_Email instance).
	 * @return void
	 */
	public function set_checkout_email_context_properties( $context_object ) {
		if ( ! is_object( $context_object ) ) {
			return;
		}

		// Set email type to allow merge tag methods to use context properties.
		$context_object->email_type = 'checkout';
		
		// Get user agent and parse browser/device info.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		
		// Detect browser.
		if ( strpos( $user_agent, 'Chrome' ) !== false ) {
			$context_object->login_browser = 'Chrome';
		} elseif ( strpos( $user_agent, 'Firefox' ) !== false ) {
			$context_object->login_browser = 'Firefox';
		} elseif ( strpos( $user_agent, 'Safari' ) !== false ) {
			$context_object->login_browser = 'Safari';
		} elseif ( strpos( $user_agent, 'Edge' ) !== false ) {
			$context_object->login_browser = 'Edge';
		} elseif ( strpos( $user_agent, 'Opera' ) !== false ) {
			$context_object->login_browser = 'Opera';
		} else {
			$context_object->login_browser = 'Unknown';
		}
		
		// Detect device.
		if ( strpos( $user_agent, 'Mobile' ) !== false || strpos( $user_agent, 'Android' ) !== false || strpos( $user_agent, 'iPhone' ) !== false ) {
			$context_object->login_device = 'Mobile';
		} elseif ( strpos( $user_agent, 'Tablet' ) !== false || strpos( $user_agent, 'iPad' ) !== false ) {
			$context_object->login_device = 'Tablet';
		} else {
			$context_object->login_device = 'Desktop';
		}
		
		// Get IP address.
		$context_object->login_ip = $this->get_client_ip();
		
		// Set login time.
		$context_object->login_time = current_time( 'mysql' );
		
		// Set user ID to null for guest users (merge tag methods will handle this).
		$context_object->wuev_user_id = null;
	}

	/**
	 * Get client IP address.
	 * 
	 * Consolidated method moved from CEV_Checkout_Email class.
	 * Handles various proxy headers and validates IP addresses.
	 *
	 * @since 2.8.6
	 * @return string IP address.
	 */
	public function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_REAL_IP',        // Nginx proxy.
			'HTTP_X_FORWARDED_FOR',  // Proxy.
			'REMOTE_ADDR',           // Standard.
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (from X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP.
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '192.0.2.0'; // Default fallback.
	}
}

/**
 * Get the CEV_Functions_Pro instance.
 *
 * Convenience function to get the singleton instance.
 *
 * @since 2.8.6
 * @return CEV_Functions_Pro Class instance.
 */
function cev_functions_pro() {
	return CEV_Functions_Pro::get_instance();
}

