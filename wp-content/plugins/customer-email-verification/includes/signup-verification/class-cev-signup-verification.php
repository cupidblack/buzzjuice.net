<?php
/**
 * Signup Verification Class for Customer Email Verification
 *
 * Main class that orchestrates the signup verification process including
 * OTP generation, email sending, verification checks, CAPTCHA validation,
 * and AJAX handlers.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Signup_Verification Class
 *
 * Manages signup verification process including email validation,
 * OTP generation and verification, CAPTCHA support, and AJAX handlers.
 */
class CEV_Signup_Verification {

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Signup_Verification|null Class Instance
	 */
	private static $instance = null;

	/**
	 * CAPTCHA validator instance.
	 *
	 * @var CEV_Signup_Captcha
	 */
	private $captcha_validator;

	/**
	 * Email sender instance.
	 *
	 * @var CEV_Signup_Email
	 */
	private $email_sender;

	/**
	 * AJAX handler instance.
	 *
	 * @var CEV_Signup_Ajax
	 */
	private $ajax_handler;

	/**
	 * Initialize the main plugin function.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Load required class files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies() {
		$plugin_path = cev_pro()->get_plugin_path();
		require_once $plugin_path . '/includes/class-cev-ajax-response.php';
		require_once $plugin_path . '/includes/signup-verification/cev-pro-signup-captcha.php';
		require_once $plugin_path . '/includes/signup-verification/cev-pro-signup-email.php';
		require_once $plugin_path . '/includes/signup-verification/cev-pro-signup-ajax.php';
	}

	/**
	 * Initialize component instances.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_components() {
		$this->captcha_validator = new CEV_Signup_Captcha();
		$this->email_sender      = new CEV_Signup_Email();
		$this->ajax_handler      = new CEV_Signup_Ajax( $this->captcha_validator, $this->email_sender );
	}

	/**
	 * Get the class instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Signup_Verification Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks and actions.
	 *
	 * Registers WordPress hooks for template display and AJAX handlers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Display OTP popup on My Account login form.
		add_action( 'woocommerce_before_customer_login_form', array( $this, 'add_otp_popup_to_my_account' ) );

		// Register AJAX handlers for email checking, OTP verification, and resending.
		add_action( 'wp_ajax_nopriv_check_email_exists', array( $this->ajax_handler, 'check_email_exists' ) );
		add_action( 'wp_ajax_verify_otp', array( $this->ajax_handler, 'verify_otp' ) );
		add_action( 'wp_ajax_nopriv_verify_otp', array( $this->ajax_handler, 'verify_otp' ) );
		add_action( 'wp_ajax_resend_otp', array( $this->ajax_handler, 'resend_otp' ) );
		add_action( 'wp_ajax_nopriv_resend_otp', array( $this->ajax_handler, 'resend_otp' ) );
	}

	/**
	 * Add OTP popup to My Account page.
	 *
	 * Loads and displays the signup verification popup template
	 * on the WooCommerce My Account login form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_otp_popup_to_my_account() {
		$template_path = cev_pro()->get_plugin_path() . '/includes/signup-verification/views/cev-signup-popup.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
		}
	}

	/**
	 * Send signup verification email to recipient.
	 *
	 * Wrapper method for backward compatibility.
	 * Delegates to the email sender class.
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address to send verification to.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public function send_signup_verification_email( $recipient ) {
		return $this->email_sender->send_verification_email( $recipient );
	}

}
