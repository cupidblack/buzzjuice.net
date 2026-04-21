<?php
/**
 * Customizer Options Class
 *
 * This class manages all default values and option definitions for the
 * Customer Email Verification customizer interface. It provides methods
 * to generate default settings and register customizer panels, sections,
 * and fields for popup styles, popup content, email styles, and email content.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Customizer_Options Class
 *
 * Handles customizer default values and option definitions.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */
class CEV_Customizer_Options {

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Customizer_Options|null Class Instance
	 */
	private static $instance = null;

	/**
	 * Default values for all customizer options.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Cached value of the cev_pro_customizer option to avoid repeated DB reads.
	 *
	 * @var array|null
	 */
	private $option_cache = null;

	/**
	 * Get the class instance.
	 *
	 * Singleton pattern to ensure only one instance exists.
	 *
	 * @since 1.0.0
	 * @return CEV_Customizer_Options Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class.
	 *
	 * Sets up default values and initializes hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->defaults = $this->cev_generate_defaults();
		$this->init();
	}

	/**
	 * Initialize hooks and filters.
	 *
	 * Registers all customizer option filters that add panels, sections,
	 * and fields to the customizer interface.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Register customizer panel options.
		add_filter( 'cev_customizer_email_options', array( $this, 'cev_customizer_panel_options' ), 10, 2 );
		
		// Register popup style options.
		add_filter( 'cev_customizer_email_options', array( $this, 'cev_customizer_popup_style_options' ), 10, 2 );
		
		// Register popup content options.
		add_filter( 'cev_customizer_email_options', array( $this, 'cev_customizer_popup_content_options' ), 10, 2 );
		
		// Register email style options.
		add_filter( 'cev_customizer_email_options', array( $this, 'cev_customizer_email_style_options' ), 20, 2 );
		
		// Register email content options.
		add_filter( 'cev_customizer_email_options', array( $this, 'cev_customizer_email_content_options' ), 30, 2 );
	}

	/**
	 * Generate default values for all customizer options.
	 *
	 * Returns an array of default values for all customizer settings including
	 * popup styles, email styles, content messages, and button configurations.
	 *
	 * @since 1.0.0
	 * @return array Associative array of default option values.
	 */
	public function cev_generate_defaults() {
		$cev_default = array(
			// Email style defaults.
			'cev_widget_content_width_style'              => '650',
			'cev_content_align_style'                     => 'left',
			'cev_widget_content_padding_style'            => '30',
			'cev_verification_content_background_color'  => '#fafafa',
			'cev_verification_content_border_color'       => '#e0e0e0',
			'cev_verification_content_font_color'          => '#333333',
			'cev_verification_image_content'              => '',
			'cev_email_content_widget_header_image_width'  => '80',
			'cev_header_content_font_size'                => '18',
			'cev_button_text_font_size'                   => '14',
			'cev_button_padding_size'                     => '15',
			'cev_new_email_link_color'                     => '#0052ff',
			'cev_email_verification_button_size'         => 'normal',
			'cev_content_widget_type'                     => 'registration',
			
			// Email content defaults - Registration.
			'cev_verification_email_heading'              => __( 'Verify Your Email Address', 'customer-email-verification' ),
			'cev_verification_email_subject'              => __( 'Please Verify Your Email Address on {site_title}', 'customer-email-verification' ),
			'cev_verification_email_body'                 => __( 'Thank you for signing up for {site_title}, to activate your account, we need to verify your email address.<p>Your verification code: <strong>{cev_user_verification_pin}</strong></p>', 'customer-email-verification' ),
			
			// Email content defaults - Checkout.
			'cev_verification_email_heading_che'          => __( 'Verify Your Email Address', 'customer-email-verification' ),
			'cev_verification_email_subject_che'          => __( 'Please Verify Your Email Address on {site_title}', 'customer-email-verification' ),
			'cev_verification_email_body_che'            => __( 'To complete your order on {site_title}, please confirm your email address. This ensures we have the right email in case we need to contact you.<p>Your verification code: {cev_user_verification_pin}</p>', 'customer-email-verification' ),
			
			// Email content defaults - Edit Account.
			'cev_verification_email_heading_ed'           => __( 'You recently changed the email address {site_title}', 'customer-email-verification' ),
			'cev_verification_email_subject_ed'           => __( 'Please Verify Your Email Address on {site_title}', 'customer-email-verification' ),
			'cev_verification_email_body_ed'              => __( 'To complete your order on {site_title}, please confirm your email address. This ensures we have the right email in case we need to contact you.<p>Your verification code: {cev_user_verification_pin}</p>', 'customer-email-verification' ),
			
			'cev_header_button_size_pro'                  => '14px',
			
			// Footer content defaults.
			'cev_new_verification_Footer_content'         => '',
			'cev_new_verification_Footer_content_che'     => '',
			
			// Popup style defaults.
			'cev_verification_image'                      => cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification.svg',
			'cev_widget_header_image_width'                => '80',
			'cev_button_text_header_font_size'             => '18',
			'cev_button_color_widget_header'               => '#2296f3',
			'cev_button_text_color_widget_header'          => '#ffffff',
			'cev_button_text_size_widget_header'           => '14',
			'cev_verification_popup_background_color'      => '#f5f5f5',
			'cev_verification_popup_overlay_background_color' => '#ffffff',
			'cev_widget_content_width'                    => '440',
			'cev_content_align'                            => 'Center',
			'cev_button_padding_size_widget_header'        => '15',
			'cev_widget_content_padding'                   => '30',
			'sample_toggle_switch_cev'                     => '0',
			'cev_verification_hide_otp_section'            => '0',
			'cev_verification_widget_hide_otp_section'     => '0',
			'cev_login_hide_otp_section'                   => '0',
			'cev_popup_button_size'                        => 'normal',
			'cev_widget_type'                              => 'popup_registration',
			
			// Popup content defaults - Registration.
			'cev_verification_header'                     => __( 'Verify its you.', 'customer-email-verification' ),
			'cev_verification_message'                    => __( 'We sent verification code to  {customer_email}. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification' ),
			'cev_verification_widget_footer'               => __( "Didn't receive an email? <strong>{cev_resend_verification}</strong>", 'customer-email-verification' ),
			
			// Popup content defaults - Login Authentication.
			'cev_login_auth_header'                       => __( 'Verify its you.', 'customer-email-verification' ),
			'cev_login_auth_message'                      => __( 'We sent verification code to  {customer_email}. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification' ),
			'cev_login_auth_widget_footer'                => __( "Didn't receive an email? <strong>{cev_resend_verification}</strong>", 'customer-email-verification' ),
			'cev_login_auth_button_text'                 => __( 'Verify Code', 'customer-email-verification' ),
			
			// Popup content defaults - Checkout Email Popup.
			'cev_header_text_checkout'                    => __( 'Verify its you.', 'customer-email-verification' ),
			'cev_verification_widget_message_checkout'    => __( 'Please verify your email address to proceed to checkout.', 'customer-email-verification' ),
			'cev_verification_widget_message_footer_checkout_pro' => __( 'Already have an account?', 'customer-email-verification' ) . '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">' . __( ' Login now', 'customer-email-verification' ) . '</a>',
			'cev_verification_header_button_text'          => __( 'Verify Code', 'customer-email-verification' ),
			
			// Popup content defaults - Checkout OTP Popup.
			'cev_header_text_checkout_otp'                 => __( 'Verify its you.', 'customer-email-verification' ),
			'cev_verification_widget_message_checkout_otp' => __( 'We sent verification code to {customer_email}. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification' ),
			'cev_verification_widget_footer_checkout_otp'  => __( "Didn't receive an email? <strong>{cev_resend_verification}</strong>", 'customer-email-verification' ),
			
			// Login authentication email defaults.
			'cev_verification_login_auth_email_subject'   => __( 'New sign-in from {login_browser} on {login_device}', 'customer-email-verification' ),
			'cev_verification_login_auth_email_heading'    => __( 'New sign-in from {login_browser} on {login_device}', 'customer-email-verification' ),
			'cev_verification_login_auth_email_content'   => __( "Hi {cev_display_name},

There was a new login to your {site_title} account from {login_browser} on {login_device}
			
We wanted to make sure it was you. Please check the details below:
			
<strong>Device:</strong> {login_browser}, {login_device}
<strong>Date:</strong> {login_time}
<strong>IP:</strong> {login_ip}

If you don't recognize this activity, please change your password right away.

Thanks", 'customer-email-verification' ),
			'cev_login_auth_footer_content'                => '',
			
			// Login OTP email defaults.
			'cev_verification_login_otp_email_subject'    => __( 'New sign-in from {login_browser} on {login_device}', 'customer-email-verification' ),
			'cev_verification_login_otp_email_heading'    => __( 'New sign-in from {login_browser} on {login_device}', 'customer-email-verification' ),
			'cev_verification_login_otp_email_content'    => __( "Hi {cev_display_name},

There was a new login to your {site_title} account from {login_browser} on {login_device}

We wanted to make sure it was you. Please verify your account using this OTP:

{login_otp}

If you don't recognize this activity, please change your password right away.

Thanks", 'customer-email-verification' ),
			'cev_login_otp_footer_content'                => '',
		);

		return $cev_default;
	}

	/**
	 * Register customizer panel options.
	 *
	 * Adds main panels and sub-panels to the customizer interface including
	 * Design panel, Popup Style, Popup Content, Email Style, and Email Content sections.
	 *
	 * @since 1.0.0
	 * @param array  $settings Existing settings array.
	 * @param string $preview  Preview type (e.g., 'popup_registration', 'email_registration').
	 * @return array Modified settings array with panel definitions.
	 */
	public function cev_customizer_panel_options( $settings, $preview ) {
		// Main Design panel.
		$settings['cev_verification_design_panel'] = array(
			'title'   => esc_html__( 'Design', 'customer-email-verification' ),
			'type'    => 'panel',
			'preview' => 'popup_registration',
		);

		// Popup Style sub-panel.
		$settings['cev_verification_popup_style'] = array(
			'title'   => esc_html__( 'Popup Style', 'customer-email-verification' ),
			'type'    => 'sub-panel',
			'parent'  => 'cev_verification_design_panel',
			'preview' => 'popup_registration',
		);

		// Popup Content panel.
		$settings['cev_verificaion_popup_message'] = array(
			'title'   => esc_html__( 'Popup Content', 'customer-email-verification' ),
			'type'    => 'panel',
			'preview' => 'popup_registration',
		);

		// Email Style sub-panel.
		$settings['cev_email_style_section'] = array(
			'title'   => esc_html__( 'Email Style', 'customer-email-verification' ),
			'type'    => 'sub-panel',
			'parent'  => 'cev_verification_design_panel',
			'preview' => 'email_registration',
		);

		// Email Content panel.
		$settings['cev_email_content'] = array(
			'title'   => esc_html__( 'Email Content', 'customer-email-verification' ),
			'type'    => 'panel',
			'preview' => 'email_registration',
		);

		return $settings;
	}

	/**
	 * Register popup style options.
	 *
	 * Adds style-related fields for popup customization including colors,
	 * dimensions, alignment, and header settings.
	 *
	 * @since 1.0.0
	 * @param array  $settings Existing settings array.
	 * @param string $preview  Preview type.
	 * @return array Modified settings array with popup style field definitions.
	 */
	public function cev_customizer_popup_style_options( $settings, $preview ) {
		// Generate font size options array (10px to 30px).
		$font_size_array_cev = array( '' => __( 'Select', 'customer-email-verification' ) );
		for ( $i = 10; $i <= 30; $i++ ) {
			$font_size_array_cev[ $i ] = $i . 'px';
		}

		// Overlay Background Color.
		$settings['cev_verification_popup_overlay_background_color'] = array(
			'title'       => esc_html__( 'Overlay Background Color', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'color',
			'default'     => $this->get_option_val( 'cev_verification_popup_overlay_background_color' ),
			'show'        => true,
			'option_name' => 'cev_verification_popup_overlay_background_color',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Widget Background Color.
		$settings['cev_verification_popup_background_color'] = array(
			'title'       => esc_html__( 'Widget Background Color', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'color',
			'default'     => $this->get_option_val( 'cev_verification_popup_background_color' ),
			'show'        => true,
			'option_name' => 'cev_verification_popup_background_color',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Content Alignment.
		$settings['cev_content_align'] = array(
			'title'       => esc_html__( 'Content Align', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_content_align' ),
			'options'     => array(
				'center' => __( 'Center', 'customer-email-verification' ),
				'left'   => __( 'Left', 'customer-email-verification' ),
			),
			'show'        => true,
			'option_name' => 'cev_content_align',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Content Width.
		$settings['cev_widget_content_width'] = array(
			'title'       => esc_html__( 'Content Width', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_widget_content_width' ),
			'show'        => true,
			'option_name' => 'cev_widget_content_width',
			'option_type' => 'key',
			'min'         => '300',
			'max'         => '600',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Content Padding.
		$settings['cev_widget_content_padding'] = array(
			'title'       => esc_html__( 'Content Padding', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_widget_content_padding' ),
			'show'        => true,
			'option_name' => 'cev_widget_content_padding',
			'option_type' => 'key',
			'min'         => '10',
			'max'         => '100',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Popup Header Section Title.
		$settings['cev_verification_widget_style_Popup_Header'] = array(
			'title'  => esc_html__( 'Popup Header', 'customer-email-verification' ),
			'parent' => 'cev_verification_popup_style',
			'type'   => 'title',
			'show'   => true,
		);

		// Header Image.
		$settings['cev_verification_image'] = array(
			'title'       => esc_html__( 'Header Image', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'media',
			'show'        => true,
			'option_type' => 'key',
			'desc'        => '',
			'option_name' => 'cev_verification_image',
			'default'     => $this->get_option_val( 'cev_verification_image' ),
			'refresh'     => true,
		);

		// Image Width.
		$settings['cev_widget_header_image_width'] = array(
			'title'       => esc_html__( 'Image Width', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_widget_header_image_width' ),
			'show'        => true,
			'option_name' => 'cev_widget_header_image_width',
			'option_type' => 'key',
			'min'         => '25',
			'max'         => '250',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Header Font Size.
		$settings['cev_button_text_header_font_size'] = array(
			'title'       => esc_html__( 'Header Font Size', 'customer-email-verification' ),
			'parent'      => 'cev_verification_popup_style',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_button_text_header_font_size' ),
			'options'     => $font_size_array_cev,
			'show'        => true,
			'option_name' => 'cev_button_text_header_font_size',
			'option_type' => 'key',
			'refresh'     => true,
		);

		return $settings;
	}

	/**
	 * Register popup content options.
	 *
	 * Adds content-related fields for popup customization including header text,
	 * messages, footer content, and button text for different popup types
	 * (registration, login auth, checkout).
	 *
	 * @since 1.0.0
	 * @param array  $settings Existing settings array.
	 * @param string $preview  Preview type.
	 * @return array Modified settings array with popup content field definitions.
	 */
	public function cev_customizer_popup_content_options( $settings, $preview ) {
		// Popup Type selector.
		$settings['cev_widget_type'] = array(
			'title'       => esc_html__( 'Popup Type', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_widget_type' ),
			'options'     => array(
				'popup_registration' => __( 'Registration Popup', 'customer-email-verification' ),
				'popup_login_auth'   => __( 'Login Authentication Popup', 'customer-email-verification' ),
				'popup_checkout'      => __( 'Checkout Email Popup', 'customer-email-verification' ),
				'popup_checkout_otp'  => __( 'Checkout OTP Popup', 'customer-email-verification' ),
			),
			'show'        => true,
			'option_name' => 'cev_widget_type',
			'option_type' => 'key',
			'previewType' => true,
		);

		// Registration Popup - Header Text.
		$settings['cev_verification_header'] = array(
			'title'       => esc_html__( 'Header Text', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_header' ),
			'placeholder' => isset( $this->defaults['cev_verification_header'] ) ? $this->defaults['cev_verification_header'] : esc_html__( 'Header Text', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_header',
			'option_type' => 'key',
			'class'       => 'popup_registration_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Registration Popup - Message.
		$settings['cev_verification_message'] = array(
			'title'       => esc_html__( 'Message', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_message' ),
			'placeholder' => isset( $this->defaults['cev_verification_message'] ) ? $this->defaults['cev_verification_message'] : esc_html__( 'Message', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_message',
			'option_type' => 'key',
			'class'       => 'popup_registration_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Registration Popup - Footer Content.
		$settings['cev_verification_widget_footer'] = array(
			'title'       => esc_html__( 'Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_widget_footer' ),
			'placeholder' => isset( $this->defaults['cev_verification_widget_footer'] ) ? $this->defaults['cev_verification_widget_footer'] : esc_html__( 'Footer Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_widget_footer',
			'option_type' => 'key',
			'class'       => 'popup_registration_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login Authentication Popup - Header Text.
		$settings['cev_login_auth_header'] = array(
			'title'       => esc_html__( 'Header Text', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_login_auth_header' ),
			'placeholder' => isset( $this->defaults['cev_login_auth_header'] ) ? $this->defaults['cev_login_auth_header'] : esc_html__( 'Header Text', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_login_auth_header',
			'option_type' => 'key',
			'class'       => 'popup_login_auth_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login Authentication Popup - Message.
		$settings['cev_login_auth_message'] = array(
			'title'       => esc_html__( 'Message', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_login_auth_message' ),
			'placeholder' => isset( $this->defaults['cev_login_auth_message'] ) ? $this->defaults['cev_login_auth_message'] : esc_html__( 'Message', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_login_auth_message',
			'option_type' => 'key',
			'class'       => 'popup_login_auth_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login Authentication Popup - Footer Content.
		$settings['cev_login_auth_widget_footer'] = array(
			'title'       => esc_html__( 'Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_login_auth_widget_footer' ),
			'placeholder' => isset( $this->defaults['cev_login_auth_widget_footer'] ) ? $this->defaults['cev_login_auth_widget_footer'] : esc_html__( 'Footer Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_login_auth_widget_footer',
			'option_type' => 'key',
			'class'       => 'popup_login_auth_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout Popup - Header Text.
		$settings['cev_header_text_checkout'] = array(
			'title'       => esc_html__( 'Header Text', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_header_text_checkout' ),
			'placeholder' => isset( $this->defaults['cev_header_text_checkout'] ) ? $this->defaults['cev_header_text_checkout'] : esc_html__( 'Header Text', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_header_text_checkout',
			'option_type' => 'key',
			'class'       => 'popup_checkout_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout Popup - Widget Content.
		$settings['cev_verification_widget_message_checkout'] = array(
			'title'       => esc_html__( 'Widget Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_widget_message_checkout' ),
			'placeholder' => isset( $this->defaults['cev_verification_widget_message_checkout'] ) ? $this->defaults['cev_verification_widget_message_checkout'] : esc_html__( 'Widget Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_widget_message_checkout',
			'option_type' => 'key',
			'class'       => 'popup_checkout_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout Popup - Footer Content.
		$settings['cev_verification_widget_message_footer_checkout_pro'] = array(
			'title'       => esc_html__( 'Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_widget_message_footer_checkout_pro' ),
			'placeholder' => isset( $this->defaults['cev_verification_widget_message_footer_checkout_pro'] ) ? $this->defaults['cev_verification_widget_message_footer_checkout_pro'] : esc_html__( 'Footer Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_widget_message_footer_checkout_pro',
			'option_type' => 'key',
			'class'       => 'popup_checkout_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout OTP Popup - Header Text.
		$settings['cev_header_text_checkout_otp'] = array(
			'title'       => esc_html__( 'Header Text', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_header_text_checkout_otp' ),
			'placeholder' => isset( $this->defaults['cev_header_text_checkout_otp'] ) ? $this->defaults['cev_header_text_checkout_otp'] : esc_html__( 'Header Text', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_header_text_checkout_otp',
			'option_type' => 'key',
			'class'       => 'popup_checkout_otp_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout OTP Popup - Widget Content.
		$settings['cev_verification_widget_message_checkout_otp'] = array(
			'title'       => esc_html__( 'Widget Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_widget_message_checkout_otp' ),
			'placeholder' => isset( $this->defaults['cev_verification_widget_message_checkout_otp'] ) ? $this->defaults['cev_verification_widget_message_checkout_otp'] : esc_html__( 'Widget Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_widget_message_checkout_otp',
			'option_type' => 'key',
			'class'       => 'popup_checkout_otp_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout OTP Popup - Footer Content.
		$settings['cev_verification_widget_footer_checkout_otp'] = array(
			'title'       => esc_html__( 'Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_verificaion_popup_message',
			'default'     => $this->get_option_val( 'cev_verification_widget_footer_checkout_otp' ),
			'placeholder' => isset( $this->defaults['cev_verification_widget_footer_checkout_otp'] ) ? $this->defaults['cev_verification_widget_footer_checkout_otp'] : esc_html__( 'Footer Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_widget_footer_checkout_otp',
			'option_type' => 'key',
			'class'       => 'popup_checkout_otp_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Available Variables Info Block.
		$available_variables = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}<br>{customer_email}<br>{cev_resend_verification}</code>';

		// Remove {customer_email} and {cev_resend_verification} for registration popup and checkout email popup.
		if ( isset( $_GET['preview'] ) && ( 'popup_registration' === $_GET['preview'] || 'popup_checkout' === $_GET['preview'] ) ) {
			$available_variables = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}</code>';
		}
		
		// For checkout OTP popup, show all variables.
		if ( isset( $_GET['preview'] ) && 'popup_checkout_otp' === $_GET['preview'] ) {
			$available_variables = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}<br>{customer_email}<br>{cev_resend_verification}</code>';
		}

		$settings['cev_widzet_code_block'] = array(
			'title'   => esc_html__( 'Available Variables', 'customer-email-verification' ),
			'parent'  => 'cev_verificaion_popup_message',
			'default' => $available_variables,
			'type'    => 'codeinfo',
			'show'    => true,
			'class'   => 'popup_registration_sub_menu popup_login_auth_sub_menu popup_checkout_sub_menu popup_checkout_otp_sub_menu all_status_submenu',
		);


		return $settings;
	}

	/**
	 * Register email style options.
	 *
	 * Adds style-related fields for email customization including content width,
	 * alignment, padding, colors, and header image settings.
	 *
	 * @since 1.0.0
	 * @param array  $settings Existing settings array.
	 * @param string $preview  Preview type.
	 * @return array Modified settings array with email style field definitions.
	 */
	public function cev_customizer_email_style_options( $settings, $preview ) {
		// Generate font size options array (10px to 30px).
		$font_size_array_cev = array( '' => __( 'Select', 'customer-email-verification' ) );
		for ( $i = 10; $i <= 30; $i++ ) {
			$font_size_array_cev[ $i ] = $i . 'px';
		}

		// Content Width.
		$settings['cev_widget_content_width_style'] = array(
			'title'       => esc_html__( 'Content width', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_widget_content_width_style' ),
			'show'        => false,
			'option_name' => 'cev_widget_content_width_style',
			'option_type' => 'key',
			'min'         => '400',
			'max'         => '1000',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Content Alignment.
		$settings['cev_content_align_style'] = array(
			'title'       => esc_html__( 'Content align', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_content_align_style' ),
			'options'     => array(
				'center' => __( 'Center', 'customer-email-verification' ),
				'left'   => __( 'Left', 'customer-email-verification' ),
			),
			'show'        => true,
			'option_name' => 'cev_content_align_style',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Content Padding.
		$settings['cev_widget_content_padding_style'] = array(
			'title'       => esc_html__( 'Content Padding', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_widget_content_padding_style' ),
			'show'        => false,
			'option_name' => 'cev_widget_content_padding_style',
			'option_type' => 'key',
			'min'         => '10',
			'max'         => '100',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Background Color.
		$settings['cev_verification_content_background_color'] = array(
			'title'       => esc_html__( 'Background Color', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'color',
			'default'     => $this->get_option_val( 'cev_verification_content_background_color' ),
			'show'        => true,
			'option_name' => 'cev_verification_content_background_color',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Border Color.
		$settings['cev_verification_content_border_color'] = array(
			'title'       => esc_html__( 'Border Color', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'color',
			'default'     => $this->get_option_val( 'cev_verification_content_border_color' ),
			'show'        => true,
			'option_name' => 'cev_verification_content_border_color',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Font Color.
		$settings['cev_verification_content_font_color'] = array(
			'title'       => esc_html__( 'Font Color', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'color',
			'default'     => $this->get_option_val( 'cev_verification_content_font_color' ),
			'show'        => true,
			'option_name' => 'cev_verification_content_font_color',
			'option_type' => 'key',
			'refresh'     => true,
		);

		// Widget Header Section Title.
		$settings['cev_verification_widget_style_content_Header'] = array(
			'title'  => esc_html__( 'Widget Header', 'customer-email-verification' ),
			'parent' => 'cev_email_style_section',
			'type'   => 'title',
			'show'   => true,
		);

		// Header Image.
		$settings['cev_verification_image_content'] = array(
			'title'       => esc_html__( 'Header image', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'media',
			'show'        => true,
			'option_type' => 'key',
			'desc'        => '',
			'option_name' => 'cev_verification_image_content',
			'default'     => $this->get_option_val( 'cev_verification_image_content' ),
			'refresh'     => true,
		);

		// Image Width.
		$settings['cev_email_content_widget_header_image_width'] = array(
			'title'       => esc_html__( 'Image Width', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'range',
			'default'     => $this->get_option_val( 'cev_email_content_widget_header_image_width' ),
			'show'        => true,
			'option_name' => 'cev_email_content_widget_header_image_width',
			'option_type' => 'key',
			'min'         => '50',
			'max'         => '300',
			'unit'        => 'px',
			'refresh'     => true,
		);

		// Header Font Size.
		$settings['cev_header_content_font_size'] = array(
			'title'       => esc_html__( 'Header font size', 'customer-email-verification' ),
			'parent'      => 'cev_email_style_section',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_header_content_font_size' ),
			'options'     => $font_size_array_cev,
			'show'        => true,
			'option_name' => 'cev_header_content_font_size',
			'option_type' => 'key',
			'refresh'     => true,
		);

		return $settings;
	}

	/**
	 * Register email content options.
	 *
	 * Adds content-related fields for email customization including email type selector,
	 * subject lines, headings, body content, and footer content for different email types
	 * (registration, checkout, edit account, login OTP, login auth).
	 *
	 * @since 1.0.0
	 * @param array  $settings Existing settings array.
	 * @param string $preview  Preview type.
	 * @return array Modified settings array with email content field definitions.
	 */
	public function cev_customizer_email_content_options( $settings, $preview ) {
		// Email Type selector.
		$settings['email_type'] = array(
			'title'       => esc_html__( 'Email Type', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'type'        => 'select',
			'default'     => $this->get_option_val( 'cev_content_widget_type' ),
			'options'     => array(
				'email_registration'  => __( 'Registration', 'customer-email-verification' ),
				'email_checkout'      => __( 'Checkout', 'customer-email-verification' ),
				'email_edit_account'  => __( 'Edit Account Email', 'customer-email-verification' ),
				'email_login_otp'     => __( 'New Login OTP', 'customer-email-verification' ),
				'email_login_auth'    => __( 'New Login Authentication', 'customer-email-verification' ),
			),
			'show'        => true,
			'option_name' => 'email_type',
			'option_type' => 'key',
			'previewType' => true,
		);

		// Registration Email - Subject.
		$settings['cev_verification_email_subject'] = array(
			'title'       => esc_html__( 'Email Subject', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_subject' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_subject'] ) ? $this->defaults['cev_verification_email_subject'] : esc_html__( 'Email Subject', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_subject',
			'option_type' => 'key',
			'class'       => 'email_registration_sub_menu all_status_submenu',
		);

		// Edit Account Email - Subject.
		$settings['cev_verification_email_subject_ed'] = array(
			'title'       => esc_html__( 'Email Subject', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_subject_ed' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_subject_ed'] ) ? $this->defaults['cev_verification_email_subject_ed'] : esc_html__( 'Email Subject', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_subject_ed',
			'option_type' => 'key',
			'class'       => 'email_edit_account_sub_menu all_status_submenu',
		);

		// Registration Email - Heading.
		$settings['cev_verification_email_heading'] = array(
			'title'       => esc_html__( 'Email Heading', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_heading' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_heading'] ) ? $this->defaults['cev_verification_email_heading'] : esc_html__( 'Email Heading', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_heading',
			'option_type' => 'key',
			'class'       => 'email_registration_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Edit Account Email - Heading.
		$settings['cev_verification_email_heading_ed'] = array(
			'title'       => esc_html__( 'Email Heading', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_heading_ed' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_heading_ed'] ) ? $this->defaults['cev_verification_email_heading_ed'] : esc_html__( 'Email Heading', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_heading_ed',
			'option_type' => 'key',
			'class'       => 'email_edit_account_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Registration Email - Body Content.
		$settings['cev_verification_email_body'] = array(
			'title'       => esc_html__( 'Verification Message', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_body' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_body'] ) ? $this->defaults['cev_verification_email_body'] : esc_html__( 'Verification Message', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_email_body',
			'option_type' => 'key',
			'class'       => 'email_registration_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Edit Account Email - Body Content.
		$settings['cev_verification_email_body_ed'] = array(
			'title'       => esc_html__( 'Verification Message', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_body_ed' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_body_ed'] ) ? $this->defaults['cev_verification_email_body_ed'] : esc_html__( 'Verification Message', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_email_body_ed',
			'option_type' => 'key',
			'class'       => 'email_edit_account_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout Email - Subject.
		$settings['cev_verification_email_subject_che'] = array(
			'title'       => esc_html__( 'Email Subject', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_subject_che' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_subject_che'] ) ? $this->defaults['cev_verification_email_subject_che'] : esc_html__( 'Email Subject', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_subject_che',
			'option_type' => 'key',
			'class'       => 'email_checkout_sub_menu all_status_submenu',
		);

		// Checkout Email - Heading.
		$settings['cev_verification_email_heading_che'] = array(
			'title'       => esc_html__( 'Email Heading', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_heading_che' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_heading_che'] ) ? $this->defaults['cev_verification_email_heading_che'] : esc_html__( 'Email Heading', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_email_heading_che',
			'option_type' => 'key',
			'class'       => 'email_checkout_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Checkout Email - Body Content.
		$settings['cev_verification_email_body_che'] = array(
			'title'       => esc_html__( 'Verification Message', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_email_body_che' ),
			'placeholder' => isset( $this->defaults['cev_verification_email_body_che'] ) ? $this->defaults['cev_verification_email_body_che'] : esc_html__( 'Verification Message', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_email_body_che',
			'option_type' => 'key',
			'class'       => 'email_checkout_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login OTP Email - Subject.
		$settings['cev_verification_login_otp_email_subject'] = array(
			'title'       => esc_html__( 'Email Subject', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_otp_email_subject' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_otp_email_subject'] ) ? $this->defaults['cev_verification_login_otp_email_subject'] : esc_html__( 'Email Subject', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_login_otp_email_subject',
			'option_type' => 'key',
			'class'       => 'email_login_otp_sub_menu all_status_submenu',
		);

		// Login OTP Email - Heading.
		$settings['cev_verification_login_otp_email_heading'] = array(
			'title'       => esc_html__( 'Email Heading', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_otp_email_heading' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_otp_email_heading'] ) ? $this->defaults['cev_verification_login_otp_email_heading'] : esc_html__( 'Email Heading', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_login_otp_email_heading',
			'option_type' => 'key',
			'class'       => 'email_login_otp_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login OTP Email - Body Content.
		$settings['cev_verification_login_otp_email_content'] = array(
			'title'       => esc_html__( 'Email Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_otp_email_content' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_otp_email_content'] ) ? $this->defaults['cev_verification_login_otp_email_content'] : esc_html__( 'Email Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_login_otp_email_content',
			'option_type' => 'key',
			'class'       => 'email_login_otp_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login Auth Email - Subject.
		$settings['cev_verification_login_auth_email_subject'] = array(
			'title'       => esc_html__( 'Email Subject', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_auth_email_subject' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_auth_email_subject'] ) ? $this->defaults['cev_verification_login_auth_email_subject'] : esc_html__( 'Email Subject', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_login_auth_email_subject',
			'option_type' => 'key',
			'class'       => 'email_login_auth_sub_menu all_status_submenu',
		);

		// Login Auth Email - Heading.
		$settings['cev_verification_login_auth_email_heading'] = array(
			'title'       => esc_html__( 'Email Heading', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_auth_email_heading' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_auth_email_heading'] ) ? $this->defaults['cev_verification_login_auth_email_heading'] : esc_html__( 'Email Heading', 'customer-email-verification' ),
			'type'        => 'text',
			'show'        => true,
			'option_name' => 'cev_verification_login_auth_email_heading',
			'option_type' => 'key',
			'class'       => 'email_login_auth_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Login Auth Email - Body Content.
		$settings['cev_verification_login_auth_email_content'] = array(
			'title'       => esc_html__( 'Email Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_verification_login_auth_email_content' ),
			'placeholder' => isset( $this->defaults['cev_verification_login_auth_email_content'] ) ? $this->defaults['cev_verification_login_auth_email_content'] : esc_html__( 'Email Content', 'customer-email-verification' ),
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_verification_login_auth_email_content',
			'option_type' => 'key',
			'class'       => 'email_login_auth_sub_menu all_status_submenu',
			'refresh'     => true,
		);

		// Available Variables Info Block.
		$email_type = $this->get_option_val( 'email_type' );
		// When previewing a specific email type in the Customizer, use the
		// preview key if it points to an email context. This ensures the
		// "Available Variables" box matches the selected Email Type even
		// before the setting is saved.
		if ( ! empty( $preview ) && 0 === strpos( $preview, 'email_' ) ) {
			$email_type = $preview;
		}
		// Default variables for registration/checkout/edit-account emails.
		$available_email_vars = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}<br>{cev_user_verification_pin}<br>{cev_display_name}<br>{login_browser}<br>{login_device}<br>{login_time}<br>{login_ip}</code>';

		// New Login OTP email variables - use {login_otp} and {change_password}.
		if ( 'email_login_otp' === $email_type ) {
			$available_email_vars = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}<br>{login_otp}<br>{change_password}<br>{cev_display_name}<br>{login_browser}<br>{login_device}<br>{login_time}<br>{login_ip}</code>';
		}

		// New Login Authentication email variables.
		if ( 'email_login_auth' === $email_type ) {
			$available_email_vars = '<code>You can use HTML tags : &lt;a&gt;, &lt;strong&gt;, &lt;i&gt; and placeholders:{site_title}<br>{cev_display_name}<br>{login_browser}<br>{login_device}<br>{login_time}<br>{login_ip}<br>{change_password}</code>';
		}

		$settings['cev_email_code_block'] = array(
			'title'   => esc_html__( 'Available Variables', 'customer-email-verification' ),
			'parent'  => 'cev_email_content',
			'default' => $available_email_vars,
			'type'    => 'codeinfo',
			'show'    => true,
			'refresh' => true,
		);

		// Footer Content Section Title.
		$settings['cev_verification_footer_content'] = array(
			'title'  => esc_html__( 'Footer Content', 'customer-email-verification' ),
			'parent' => 'cev_email_content',
			'type'   => 'title',
			'show'   => true,
		);

		// Registration Email - Footer Content.
		$settings['cev_new_verification_Footer_content'] = array(
			'title'       => esc_html__( 'Addition Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'    => $this->get_option_val( 'cev_new_verification_Footer_content' ),
			'placeholder' => '',
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_new_verification_Footer_content',
			'option_type' => 'key',
			'refresh'     => true,
			'class'       => 'email_registration_sub_menu all_status_submenu',
		);

		// Checkout Email - Footer Content.
		$settings['cev_new_verification_Footer_content_che'] = array(
			'title'       => esc_html__( 'Addition Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_new_verification_Footer_content_che' ),
			'placeholder' => '',
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_new_verification_Footer_content_che',
			'option_type' => 'key',
			'refresh'     => true,
			'class'       => 'email_checkout_sub_menu all_status_submenu',
		);

		// Login OTP Email - Footer Content.
		$settings['cev_login_otp_footer_content'] = array(
			'title'       => esc_html__( 'Addition Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_login_otp_footer_content' ),
			'placeholder' => '',
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_login_otp_footer_content',
			'option_type' => 'key',
			'refresh'     => true,
			'class'       => 'email_login_otp_sub_menu all_status_submenu',
		);

		// Login Auth Email - Footer Content.
		$settings['cev_login_auth_footer_content'] = array(
			'title'       => esc_html__( 'Addition Footer Content', 'customer-email-verification' ),
			'parent'      => 'cev_email_content',
			'default'     => $this->get_option_val( 'cev_login_auth_footer_content' ),
			'placeholder' => '',
			'type'        => 'textarea',
			'show'        => true,
			'option_name' => 'cev_login_auth_footer_content',
			'option_type' => 'key',
			'refresh'     => true,
			'class'       => 'email_login_auth_sub_menu all_status_submenu',
		);

		return $settings;
	}

	/**
	 * Get option value with fallback to default.
	 *
	 * Retrieves an option value from the consolidated customizer settings.
	 * If the option doesn't exist or is empty, returns the default value from the defaults array.
	 *
	 * @since 1.0.0
	 * @param string $key Option key to retrieve.
	 * @return mixed Option value or default value if not set.
	 */
	public function get_option_val( $key ) {
		// Get consolidated customizer settings (cached to avoid repeated DB reads).
		if ( null === $this->option_cache ) {
			$this->option_cache = get_option( 'cev_pro_customizer', array() );
		}
		$customizer_settings = $this->option_cache;
		
		// Check if key exists in consolidated settings.
		if ( is_array( $customizer_settings ) && isset( $customizer_settings[ $key ] ) ) {
			$value = $customizer_settings[ $key ];
		} else {
			// Fallback to default if not in consolidated settings.
			$value = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : '';
		}

		// Fallback to default if value is empty.
		if ( '' === $value && isset( $this->defaults[ $key ] ) ) {
			$value = $this->defaults[ $key ];
		}

		return $value;
	}
}
