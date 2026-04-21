<?php
/**
 * Login Authentication Handler
 *
 * Handles login authentication with OTP verification for unrecognized logins.
 * Manages device/location tracking, OTP generation, email sending, and verification.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Customer_Email_Verification_Login_Authentication Class
 *
 * Manages login authentication with OTP verification.
 */
class WC_Customer_Email_Verification_Login_Authentication {

	/**
	 * Instance of this class.
	 *
	 * @var WC_Customer_Email_Verification_Login_Authentication
	 */
	private static $instance;

	/**
	 * Basic browser detection patterns.
	 *
	 * @var array<string, string>
	 */
	private $basic_browser = array(
		'Trident\/7.0' => 'Internet Explorer 11',
		'Beamrise'     => 'Beamrise',
		'Opera'        => 'Opera',
		'OPR'          => 'Opera',
		'Shiira'       => 'Shiira',
		'Chimera'      => 'Chimera',
		'Phoenix'      => 'Phoenix',
		'Firebird'     => 'Firebird',
		'Camino'       => 'Camino',
		'Netscape'     => 'Netscape',
		'OmniWeb'      => 'OmniWeb',
		'Konqueror'    => 'Konqueror',
		'icab'         => 'iCab',
		'Lynx'         => 'Lynx',
		'Links'        => 'Links',
		'hotjava'      => 'HotJava',
		'amaya'        => 'Amaya',
		'IBrowse'      => 'IBrowse',
		'iTunes'       => 'iTunes',
		'Silk'         => 'Silk',
		'Dillo'        => 'Dillo',
		'Maxthon'      => 'Maxthon',
		'Arora'        => 'Arora',
		'Galeon'       => 'Galeon',
		'Iceape'       => 'Iceape',
		'Iceweasel'    => 'Iceweasel',
		'Midori'       => 'Midori',
		'QupZilla'     => 'QupZilla',
		'Namoroka'     => 'Namoroka',
		'NetSurf'      => 'NetSurf',
		'BOLT'         => 'BOLT',
		'EudoraWeb'    => 'EudoraWeb',
		'shadowfox'    => 'ShadowFox',
		'Swiftfox'     => 'Swiftfox',
		'Uzbl'         => 'Uzbl',
		'UCBrowser'    => 'UCBrowser',
		'Kindle'       => 'Kindle',
		'wOSBrowser'   => 'wOSBrowser',
		'Epiphany'     => 'Epiphany',
		'SeaMonkey'    => 'SeaMonkey',
		'Avant Browser' => 'Avant Browser',
		'Firefox'      => 'Firefox',
		'Chrome'       => 'Google Chrome',
		'MSIE'         => 'Internet Explorer',
		'Internet Explorer' => 'Internet Explorer',
		'Safari'       => 'Safari',
		'Mozilla'      => 'Mozilla',
	);

	/**
	 * Basic platform detection patterns.
	 *
	 * @var array<string, string>
	 */
	private $basic_platform = array(
		'windows'     => 'Windows',
		'iPad'        => 'iPad',
		'iPod'        => 'iPod',
		'iPhone'      => 'iPhone',
		'mac'         => 'Apple',
		'android'     => 'Android',
		'linux'       => 'Linux',
		'Nokia'       => 'Nokia',
		'BlackBerry'  => 'BlackBerry',
		'FreeBSD'     => 'FreeBSD',
		'OpenBSD'     => 'OpenBSD',
		'NetBSD'      => 'NetBSD',
		'UNIX'        => 'UNIX',
		'DragonFly'   => 'DragonFlyBSD',
		'OpenSolaris' => 'OpenSolaris',
		'SunOS'       => 'SunOS',
		'OS\/2'       => 'OS/2',
		'BeOS'        => 'BeOS',
		'win'         => 'Windows',
		'Dillo'       => 'Linux',
		'PalmOS'      => 'PalmOS',
		'RebelMouse'  => 'RebelMouse',
	);

	/**
	 * Initialize the main plugin function.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Get the class instance.
	 *
	 * @return WC_Customer_Email_Verification_Login_Authentication
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * AJAX handler instance.
	 *
	 * @var CEV_Login_Authentication_Ajax
	 */
	private $ajax_handler;

	/**
	 * Initialize hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		if ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'cev_enable_login_authentication', 0 ) ) {
			add_action( 'woocommerce_created_customer', array( $this, 'add_last_login_details_to_new_customers' ), 10, 3 );
			add_action( 'wp_login', array( $this, 'cev_woocommerce_account_login_check' ), 10, 3 );
			add_action( 'wp', array( $this, 'check_user_and_redirect_to_endpoint' ) );
			add_action( 'wp', array( $this, 'cev_resend_login_otp_email' ), 1 );
			add_filter( 'cev_login_auth_message', array( $this, 'cev_login_auth_message_callback' ), 10, 2 );

			// Initialize AJAX handler.
			require_once cev_pro()->get_plugin_path() . '/includes/login-authentication/class-cev-login-authentication-ajax.php';
			$this->ajax_handler = new CEV_Login_Authentication_Ajax( $this );
		}
	}

	/**
	 * Add last login details to new customers.
	 *
	 * @param int    $user_id            User ID.
	 * @param array  $new_customer_data   New customer data.
	 * @param string $password_generated Generated password.
	 * @return void
	 */
	public function add_last_login_details_to_new_customers( $user_id, $new_customer_data, $password_generated ) {
		$login_details = $this->get_current_login_details();

		if ( empty( $login_details ) ) {
			return;
		}

		$this->update_user_login_details( $user_id, $login_details );
	}

	/**
	 * Check login and send OTP if needed.
	 *
	 * @param string $user_login User login name.
	 * @return void
	 */
	public function cev_woocommerce_account_login_check( $user_login ) {
		// The wp_login hook fires on all logins, so we don't need strict form field checks.
		// Just verify we have a valid user login parameter.
		if ( empty( $user_login ) ) {
			return;
		}

		$user = get_user_by( 'login', $user_login );
		if ( ! $user ) {
			return;
		}

		$user_id    = $user->ID;
		$user_email = $user->user_email;

		// Only check for customer accounts, skip admin users.
		if ( cev_pro()->admin->is_admin_user( $user_id ) ) {
			return;
		}

		$verified = cev_pro()->function->check_email_verify( $user_email );
		if ( false === $verified ) {
			return;
		}

		$login_authentication_enabled = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'cev_enable_login_authentication', 0 ) );
		$email_otp_enabled             = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_otp_for_account', 1 ) );

		$login_details = $this->get_current_login_details();
		if ( empty( $login_details ) ) {
			return;
		}

		$user_last_login_details = get_user_meta( $user_id, 'cev_last_login_detail', true );
		$user_last_login_time    = get_user_meta( $user_id, 'cev_last_login_time', true );

		if ( $login_authentication_enabled && $email_otp_enabled ) {
			$this->handle_otp_verification_flow( $user_id, $user_email, $login_details, $user_last_login_details, $user_last_login_time );
		} elseif ( $login_authentication_enabled ) {
			$this->handle_authentication_email_flow( $user_id, $user_email, $login_details, $user_last_login_details, $user_last_login_time );
		} else {
			$this->update_user_login_details( $user_id, $login_details );
		}
	}

	/**
	 * Handle OTP verification flow.
	 *
	 * @param int    $user_id              User ID.
	 * @param string $user_email           User email address.
	 * @param array  $login_details        Current login details.
	 * @param array  $user_last_login_details Last login details.
	 * @param string $user_last_login_time Last login time.
	 * @return void
	 */
	private function handle_otp_verification_flow( $user_id, $user_email, $login_details, $user_last_login_details, $user_last_login_time ) {
		$should_send_otp = $this->should_send_login_otp( $login_details, $user_last_login_details, $user_last_login_time );

		if ( $should_send_otp ) {
			$this->send_and_store_login_otp( $user_id, $user_email, $login_details );
		} else {
			$this->update_user_login_details( $user_id, $login_details );
		}
	}

	/**
	 * Handle authentication email flow (without OTP).
	 *
	 * @param int    $user_id              User ID.
	 * @param string $user_email           User email address.
	 * @param array  $login_details        Current login details.
	 * @param array  $user_last_login_details Last login details.
	 * @param string $user_last_login_time Last login time.
	 * @return void
	 */
	private function handle_authentication_email_flow( $user_id, $user_email, $login_details, $user_last_login_details, $user_last_login_time ) {
		$should_send_email = $this->should_send_authentication_email( $login_details, $user_last_login_details, $user_last_login_time );

		$this->update_user_login_details( $user_id, $login_details );

		if ( $should_send_email ) {
			$this->send_login_authentication_email( $user_id, $user_email );
		}
	}

	/**
	 * Determine if login OTP should be sent.
	 *
	 * @param array  $login_details        Current login details.
	 * @param array  $user_last_login_details Last login details.
	 * @param string $user_last_login_time Last login time.
	 * @return bool
	 */
	private function should_send_login_otp( $login_details, $user_last_login_details, $user_last_login_time ) {
		// Ensure user_last_login_details is an array.
		if ( ! is_array( $user_last_login_details ) ) {
			$user_last_login_details = array();
		}

		$enable_time_check    = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_login_time', 1 ) );
		$time_threshold_days   = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_last_login_more_then_time', 15 );
		$day_difference       = $this->get_day_difference( gmdate( 'Y-m-d' ), $user_last_login_time );

		if ( $enable_time_check && $day_difference > $time_threshold_days ) {
			return true;
		}

		$enable_device_check = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_device', 1 ) );
		if ( $enable_device_check ) {
			$last_device    = isset( $user_last_login_details['last_login_device'] ) ? $user_last_login_details['last_login_device'] : '';
			$current_device = isset( $login_details['last_login_device'] ) ? $login_details['last_login_device'] : '';
			
			// If no previous device data exists (first login or missing data), trigger OTP.
			if ( empty( $last_device ) ) {
				return true;
			}
			
			// If device changed, trigger OTP.
			if ( ! empty( $current_device ) && $current_device !== $last_device ) {
				return true;
			}
		}

		$enable_location_check = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_location', 1 ) );
		if ( $enable_location_check ) {
			$last_postal    = isset( $user_last_login_details['last_login_postal'] ) ? $user_last_login_details['last_login_postal'] : '';
			$current_postal = isset( $login_details['last_login_postal'] ) ? $login_details['last_login_postal'] : '';
			
			// If no previous location data exists (first login or missing data), trigger OTP.
			if ( empty( $last_postal ) ) {
				return true;
			}
			
			// If location changed, trigger OTP.
			if ( ! empty( $current_postal ) && $current_postal !== $last_postal ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if authentication email should be sent.
	 *
	 * @param array  $login_details        Current login details.
	 * @param array  $user_last_login_details Last login details.
	 * @param string $user_last_login_time Last login time.
	 * @return bool
	 */
	private function should_send_authentication_email( $login_details, $user_last_login_details, $user_last_login_time ) {
		// Ensure user_last_login_details is an array.
		if ( ! is_array( $user_last_login_details ) ) {
			$user_last_login_details = array();
		}

		$enable_time_check    = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_login_time', 1 ) );
		$time_threshold_days   = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_last_login_more_then_time', 15 );
		$day_difference       = $this->get_day_difference( gmdate( 'Y-m-d' ), $user_last_login_time );

		if ( $enable_time_check && $day_difference > $time_threshold_days ) {
			return true;
		}

		$enable_device_check = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_device', 1 ) );
		if ( $enable_device_check ) {
			$last_device    = isset( $user_last_login_details['last_login_device'] ) ? $user_last_login_details['last_login_device'] : '';
			$current_device = isset( $login_details['last_login_device'] ) ? $login_details['last_login_device'] : '';
			
			// If no previous device data exists (first login or missing data), trigger email.
			if ( empty( $last_device ) ) {
				return true;
			}
			
			// If device changed, trigger email.
			if ( ! empty( $current_device ) && $current_device !== $last_device ) {
				return true;
			}
		}

		$enable_location_check = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_location', 1 ) );
		if ( $enable_location_check ) {
			$last_postal    = isset( $user_last_login_details['last_login_postal'] ) ? $user_last_login_details['last_login_postal'] : '';
			$current_postal = isset( $login_details['last_login_postal'] ) ? $login_details['last_login_postal'] : '';
			
			// If no previous location data exists (first login or missing data), trigger email.
			if ( empty( $last_postal ) ) {
				return true;
			}
			
			// If location changed, trigger email.
			if ( ! empty( $current_postal ) && $current_postal !== $last_postal ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Send and store login OTP.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $user_email   User email address.
	 * @param array  $login_details Login details.
	 * @return void
	 */
	private function send_and_store_login_otp( $user_id, $user_email, $login_details ) {
		$login_otp = cev_pro()->function->generate_verification_pin();

		update_user_meta( $user_id, 'cev_login_otp', $login_otp );
		update_user_meta( $user_id, 'cev_login_otp_required', '1' );

		// Save login details even when OTP is required, so we have a record for comparison.
		// These will be updated again after OTP verification to ensure accuracy.
		$this->update_user_login_details( $user_id, $login_details );

		$email_common = cev_pro()->function;
		$email_common->wuev_user_id  = $user_id;
		$email_common->email_type    = 'login_otp';
		$email_common->login_ip      = isset( $login_details['last_login_ip'] ) ? $login_details['last_login_ip'] : '';
		$email_common->login_time    = current_time( 'mysql' );
		$email_common->login_browser = isset( $login_details['last_login_browser'] ) ? $login_details['last_login_browser'] : '';
		$email_common->login_device  = isset( $login_details['last_login_device'] ) ? $login_details['last_login_device'] : '';

		$this->send_login_otp_email( $user_email, $login_otp );
	}

	/**
	 * Get current login details (IP, device, browser, location).
	 *
	 * @return array Login details array or empty array on failure.
	 */
	public function get_current_login_details() {
		$user_ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$user_agent     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		
		// If no IP or user agent, return empty array.
		if ( empty( $user_ip ) && empty( $user_agent ) ) {
			return array();
		}
		
		$parsed_details = $this->cev_parse_user_agent( $user_agent );

		$login_device  = isset( $parsed_details['platform'] ) ? $parsed_details['platform'] : '';
		$login_browser = isset( $parsed_details['browser'] ) ? $parsed_details['browser'] : '';

		// Try to get location data, but don't fail if it's unavailable.
		$location_data = $this->get_location_data( $user_ip );
		
		// Build login details array even if location data is unavailable.
		$login_details = array(
			'last_login_device'  => $login_device,
			'last_login_browser' => $login_browser,
			'last_login_ip'      => $user_ip,
			'last_login_city'    => '',
			'last_login_region'  => '',
			'last_login_country' => '',
			'last_login_postal'  => '',
		);
		
		// Add location data if available.
		if ( false !== $location_data && is_object( $location_data ) ) {
			$login_details['last_login_city']    = isset( $location_data->city ) ? $location_data->city : '';
			$login_details['last_login_region']  = isset( $location_data->region ) ? $location_data->region : '';
			$login_details['last_login_country'] = isset( $location_data->country ) ? $location_data->country : '';
			$login_details['last_login_postal']  = isset( $location_data->postal ) ? $location_data->postal : '';
		}

		return $login_details;
	}

	/**
	 * Get location data from IP address.
	 *
	 * @param string $ip_address IP address.
	 * @return object|false Location data object or false on failure.
	 */
	private function get_location_data( $ip_address ) {
		if ( empty( $ip_address ) ) {
			return false;
		}

		$url      = esc_url_raw( "http://ipinfo.io/{$ip_address}/json" );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 5,
				'sslverify'   => false,
				'httpversion' => '1.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return false;
		}

		$data = json_decode( $body );
		if ( null === $data ) {
			return false;
		}

		return $data;
	}

	/**
	 * Update user login details in user meta.
	 *
	 * @param int   $user_id       User ID.
	 * @param array $login_details Login details array.
	 * @return void
	 */
	public function update_user_login_details( $user_id, $login_details ) {
		update_user_meta( $user_id, 'cev_last_login_detail', $login_details );
		update_user_meta( $user_id, 'cev_last_login_time', current_time( 'mysql' ) );
	}

	/**
	 * Get day difference between two dates.
	 *
	 * @param string $current_date    Current date in Y-m-d format.
	 * @param string $last_login_date Last login date.
	 * @return int Day difference (positive or negative).
	 */
	public function get_day_difference( $current_date, $last_login_date ) {
		if ( empty( $last_login_date ) ) {
			return 999; // Return large number if no last login date.
		}

		$date1 = date_create( $last_login_date );
		$date2 = date_create( $current_date );

		if ( false === $date1 || false === $date2 ) {
			return 999;
		}

		$diff = date_diff( $date1, $date2 );
		return (int) $diff->format( '%R%a' );
	}

	/**
	 * Send login OTP email.
	 *
	 * @param string $recipient Recipient email address.
	 * @param string $login_otp Login OTP code.
	 * @return void
	 */
	public function send_login_otp_email( $recipient, $login_otp ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_common = cev_pro()->function;
		// Expose login OTP on context so {cev_user_verification_pin} (and legacy {login_otp}) merge tags work.
		$email_common->login_otp = $login_otp;

		$email_subject = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_otp_email_subject', cev_pro()->customizer_options->defaults['cev_verification_login_otp_email_subject'] );
		$email_subject = cev_pro()->function->maybe_parse_merge_tags( $email_subject, $email_common );

		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_otp_email_heading', cev_pro()->customizer_options->defaults['cev_verification_login_otp_email_heading'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, $email_common );

		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_otp_email_content', cev_pro()->customizer_options->defaults['cev_verification_login_otp_email_content'] );
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, $email_common );

		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_otp_footer_content', cev_pro()->customizer_options->defaults['cev_login_otp_footer_content'] );

		$mailer = WC()->mailer();
		$email  = new WC_Email();
		$email->id = 'CEV_Login_OTP';

		$template_path = $this->get_email_template_path();
		$template_args = array(
			'email_heading'  => $email_heading,
			'content'        => $email_content,
			'footer_content' => $footer_content,
			'login_otp'      => $login_otp,
		);

		ob_start();
		wc_get_template( 'emails/cev-login-authentication.php', $template_args, 'customer-email-verification/', $template_path );
		$content = ob_get_clean();

		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		add_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		$result = wp_mail( $recipient, $email_subject, $message, $email->get_headers() );

		remove_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		return $result;
	}

	/**
	 * Send login authentication email.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $recipient Recipient email address.
	 * @return void
	 */
	public function send_login_authentication_email( $user_id, $recipient ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_common = cev_pro()->function;
		$email_common->email_type  = 'login_authentication';
		$email_common->wuev_user_id = $user_id;

		$email_subject = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_auth_email_subject', cev_pro()->customizer_options->defaults['cev_verification_login_auth_email_subject'] );
		$email_subject = cev_pro()->function->maybe_parse_merge_tags( $email_subject, $email_common );

		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_auth_email_heading', cev_pro()->customizer_options->defaults['cev_verification_login_auth_email_heading'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, $email_common );

		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_auth_email_content', cev_pro()->customizer_options->defaults['cev_verification_login_auth_email_content'] );
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, $email_common );

		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_footer_content', cev_pro()->customizer_options->defaults['cev_login_auth_footer_content'] );

		$mailer = WC()->mailer();
		$email  = new WC_Email();
		$email->id = 'CEV_Login_Authentication';

		$template_path = $this->get_email_template_path();
		$template_args = array(
			'email_heading'  => $email_heading,
			'content'        => $email_content,
			'footer_content' => $footer_content,
			'login_otp'      => '',
		);

		ob_start();
		wc_get_template( 'emails/cev-login-authentication.php', $template_args, 'customer-email-verification/', $template_path );
		$content = ob_get_clean();

		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		add_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		wp_mail( $recipient, $email_subject, $message, $email->get_headers() );
	}

	/**
	 * Get email template path (local or plugin default).
	 *
	 * @return string Template path.
	 */
	private function get_email_template_path() {
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-login-authentication.php';

		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			return get_stylesheet_directory() . '/woocommerce/';
		}

		return cev_pro()->get_plugin_path() . '/templates/';
	}

	/**
	 * Check user and redirect to endpoint if OTP required.
	 *
	 * @return void
	 */
	public function check_user_and_redirect_to_endpoint() {
		if ( ! is_account_page() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user = get_user_by( 'id', get_current_user_id() );
		if ( ! $user ) {
			return;
		}

		$user_id    = $user->ID;
		$user_email = $user->user_email;

		$verified = cev_pro()->function->check_email_verify( $user_email );
		if ( false === $verified ) {
			return;
		}

		$login_authentication_enabled = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'cev_enable_login_authentication', 0 ) );
		$email_otp_enabled           = ( 1 === (int) cev_pro()->function->cev_pro_admin_settings( 'enable_email_otp_for_account', 1 ) );

		if ( ! $login_authentication_enabled || ! $email_otp_enabled ) {
			return;
		}

		if ( cev_pro()->admin->is_admin_user( $user_id ) ) {
			return;
		}

		$login_otp_required = get_user_meta( get_current_user_id(), 'cev_login_otp_required', true );
		if ( empty( $login_otp_required ) || ( '1' !== $login_otp_required && true !== $login_otp_required && 1 !== $login_otp_required ) ) {
			return;
		}

		$login_otp = get_user_meta( get_current_user_id(), 'cev_login_otp', true );
		if ( empty( $login_otp ) ) {
			return;
		}

		global $wp;
		$logout_url              = wc_get_account_endpoint_url( 'customer-logout' );
		$logout_url              = strtok( $logout_url, '?' );
		$logout_url              = rtrim( strtok( $logout_url, '?' ), '/' );
		$login_authentication_url = rtrim( wc_get_account_endpoint_url( 'login-authentication' ), '/' );
		$current_url              = rtrim( home_url( $wp->request ), '/' );

		if ( $current_url === $logout_url ) {
			return;
		}

		if ( $current_url !== $login_authentication_url ) {
			$redirect_url = wc_get_account_endpoint_url( 'login-authentication' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Information content for login authentication endpoint.
	 *
	 * @return void
	 */
	public function cev_login_authentication_endpoint_content() {
		$current_user = wp_get_current_user();
		if ( ! $current_user || ! $current_user->exists() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( cev_pro()->admin->is_admin_user( $user_id ) ) {
			return;
		}

		$login_otp_required = get_user_meta( $user_id, 'cev_login_otp_required', true );
		if ( empty( $login_otp_required ) || ( '1' !== $login_otp_required && true !== $login_otp_required && 1 !== $login_otp_required ) ) {
			return;
		}

		$customizer_options = new CEV_Customizer_Options();
		$overlay_color      = get_option(
			'cev_verification_popup_overlay_background_color',
			$customizer_options->defaults['cev_verification_popup_overlay_background_color']
		);

		// Output inline styles.
		?>
		<style>
		.cev-authorization-grid__visual{
			background: <?php echo esc_attr( cev_pro()->function->hex2rgba( $overlay_color, '0.7' ) ); ?> !important;
		}
		</style>
		<?php

		// Load customizer options for template.
		$button_color              = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_color_widget_header', '#212121' );
		$button_text_color         = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_color_widget_header', '#ffffff' );
		$widget_header_image_width = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_header_image_width', '80' );
		$button_text_font_size     = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_header_font_size', '22' );
		$toggle_switch             = cev_pro()->function->cev_pro_customizer_settings( 'sample_toggle_switch_cev', $customizer_options->defaults['sample_toggle_switch_cev'] );

		$popup_button_size         = cev_pro()->function->cev_pro_customizer_settings( 'cev_popup_button_size', $customizer_options->defaults['cev_popup_button_size'] );
		$button_text_size_header   = ( 'large' === $popup_button_size ) ? 18 : 16;
		$button_padding             = ( 'large' === $popup_button_size ) ? '15px 25px' : '12px 20px';

		$template_path = cev_pro()->get_plugin_path() . '/includes/login-authentication/views/popup-template.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
		}
	}

	/**
	 * Resend login OTP email.
	 *
	 * @return void
	 */
	public function cev_resend_login_otp_email() {
		if ( ! isset( $_GET['cev_resend_login_otp_email'] ) || '' === $_GET['cev_resend_login_otp_email'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$encoded_user_id = wc_clean( wp_unslash( $_GET['cev_resend_login_otp_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id         = base64_decode( $encoded_user_id ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		// Check if user is logged in and matches the user ID.
		if ( ! is_user_logged_in() || get_current_user_id() !== (int) $user_id ) {
			return;
		}

		$user_email = $user->user_email;

		if ( false === WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$login_otp         = get_user_meta( $user_id, 'cev_login_otp', true );
		$login_otp_required = get_user_meta( $user_id, 'cev_login_otp_required', true );

		// Check if OTP is required and exists.
		if ( empty( $login_otp_required ) || ( '1' !== $login_otp_required && true !== $login_otp_required && 1 !== $login_otp_required ) ) {
			return;
		}

		if ( empty( $login_otp ) ) {
			return;
		}

		// Prevent duplicate sends within 2 minutes using transient.
		$transient_key = 'cev_resend_login_otp_' . $user_id;
		$last_sent = get_transient( $transient_key );
		
		if ( false !== $last_sent ) {
			// Email was sent recently, don't send again.
			return;
		}

		// Send the email.
		$email_sent = $this->send_login_otp_email( $user_email, $login_otp );
		
		if ( $email_sent ) {
			// Set transient to prevent duplicate sends for 2 minutes (120 seconds).
			set_transient( $transient_key, time(), 120 );
			
			$message = __( 'A new login OTP email is sent.', 'customer-email-verification' );
			wc_add_notice( $message, 'notice' );
		}
	}


	/**
	 * Parse user agent string to extract browser and platform information.
	 *
	 * @param string|null $user_agent User agent string.
	 * @return array<string, string|null> Parsed user agent data with 'platform', 'browser', and 'version' keys.
	 */
	public function cev_parse_user_agent( $user_agent = null ) {
		$platform = null;
		$browser  = null;
		$version  = '';

		if ( empty( $user_agent ) ) {
			return array(
				'platform' => $platform,
				'browser'  => $browser,
				'version'  => $version,
			);
		}

		// Detect browser.
		foreach ( $this->basic_browser as $pattern => $name ) {
			if ( preg_match( '/' . preg_quote( $pattern, '/' ) . '/i', $user_agent, $match ) ) {
				$browser = $name;

				// Extract version number.
				$known_patterns = array( 'Version', $pattern, 'other' );
				$version_pattern = '#(?<browser>' . join( '|', $known_patterns ) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

				if ( preg_match_all( $version_pattern, $user_agent, $matches ) ) {
					$match_count = count( $matches['browser'] );

					if ( 1 === $match_count ) {
						$version = $matches['version'][0];
					} else {
						// Determine if version appears before or after browser name.
						$version_pos = strripos( $user_agent, 'Version' );
						$pattern_pos = strripos( $user_agent, $pattern );

						if ( false !== $version_pos && false !== $pattern_pos ) {
							$version = ( $version_pos < $pattern_pos ) ? $matches['version'][0] : $matches['version'][1];
						} else {
							$version = $matches['version'][0];
						}
					}
				}

				break;
			}
		}

		// Detect platform.
		foreach ( $this->basic_platform as $key => $platform_name ) {
			if ( stripos( $user_agent, $key ) !== false ) {
				$platform = $platform_name;
				break;
			}
		}

		return array(
			'platform' => $platform,
			'browser'  => $browser,
			'version'  => $version,
		);
	}

	/**
	 * Filter callback for login authentication message.
	 *
	 * @since 1.0.0
	 * @param string $message Default message text.
	 * @param string $email   Email address.
	 * @return string Filtered message.
	 */
	public function cev_login_auth_message_callback( $message, $email ) {
		$message_text = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_message', $message );
		$message_text = str_replace( '{customer_email}', $email, $message_text );

		if ( '' !== $message_text ) {
			return $message_text;
		}

		return $message;
	}
}
