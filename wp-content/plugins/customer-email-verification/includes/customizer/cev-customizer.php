<?php
/**
 * Customizer Class
 *
 * This class handles the customizer interface for Customer Email Verification.
 * It manages admin menu registration, REST API routes, script enqueuing, and
 * email/popup preview generation for the customizer interface.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Customizer Class
 *
 * Handles customizer interface functionality including admin menu, REST API,
 * script enqueuing, and preview generation.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */
class CEV_Customizer {

	/**
	 * Screen ID for the customizer page.
	 *
	 * @var string
	 */
	private static $screen_id = 'cev_customizer';

	/**
	 * Screen title for the customizer page.
	 *
	 * @var string
	 */
	private static $screen_title = 'Email Verification';

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Customizer|null Class Instance
	 */
	private static $instance = null;

	/**
	 * Get the class instance.
	 *
	 * Singleton pattern to ensure only one instance exists.
	 *
	 * @since 1.0.0
	 * @return CEV_Customizer Class instance.
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
	 * Sets up hooks and filters for the customizer interface.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks and filters.
	 *
	 * Registers all WordPress hooks and filters needed for the customizer
	 * interface including admin menu, REST API routes, script enqueuing,
	 * and AJAX handlers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Register admin menu.
		add_action( 'admin_menu', array( $this, 'register_woocommerce_menu' ), 99 );

		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'route_api_functions' ) );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'customizer_enqueue_scripts' ), 20 );

		// Add footer scripts to hide menu item.
		add_action( 'admin_footer', array( $this, 'admin_footer_enqueue_scripts' ) );

		// Register AJAX handler for email preview.
		add_action( 'wp_ajax_' . self::$screen_id . '_email_preview', array( $this, 'get_preview_func' ) );

		// Register preview content filter.
		add_filter( self::$screen_id . '_preview_content', array( $this, 'cev_customizer_preview_content' ), 10, 1 );
	}

	/**
	 * Register admin menu page.
	 *
	 * Adds a top-level menu page for the customizer interface.
	 * The menu item is hidden via CSS in admin_footer_enqueue_scripts().
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function register_woocommerce_menu() {
		add_menu_page(
			__( self::$screen_title, 'customer-email-verification' ),
			__( self::$screen_title, 'customer-email-verification' ),
			'manage_options',
			self::$screen_id,
			array( $this, 'cev_settingsPage' )
		);
	}

	/**
	 * Render the customizer settings page.
	 *
	 * Outputs a root div element where the React customizer interface
	 * will be mounted.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function cev_settingsPage() {
		echo '<div id="root"></div>';
	}

	/**
	 * Hide the customizer menu item in admin.
	 *
	 * Adds inline CSS to hide the customizer menu item from the admin menu.
	 * This is done because the customizer is accessed via a different route.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function admin_footer_enqueue_scripts() {
		echo '<style type="text/css">#toplevel_page_' . esc_html( self::$screen_id ) . ' { display: none !important; }</style>';
	}

	/**
	 * Enqueue customizer scripts and styles.
	 *
	 * Enqueues the main customizer JavaScript file and localizes script data
	 * including translations, iframe URLs, nonces, and other configuration.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function customizer_enqueue_scripts( $hook ) {
		// Get current page from query string.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Only enqueue on customizer page.
		if ( self::$screen_id !== $page ) {
			return;
		}

		// Enqueue WordPress media uploader.
		wp_enqueue_media();

		// Enqueue main customizer script.
		wp_enqueue_script(
			self::$screen_id,
			plugin_dir_url( __FILE__ ) . 'dist/main.js',
			array( 'jquery', 'wp-util', 'wp-color-picker' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'dist/main.js' ),
			true
		);

		// Localize script with data.
		wp_localize_script(
			self::$screen_id,
			self::$screen_id,
			array(
				'admin_email'            => get_option( 'admin_email' ),
				'translations'          => array(
					esc_html__( 'Save', 'react-customizer-framework' ),
					esc_html__( 'You are customizing', 'react-customizer-framework' ),
					esc_html__( 'Customizing', 'react-customizer-framework' ),
					esc_html__( 'Send Test Email', 'react-customizer-framework' ),
					esc_html__( 'Send a test email', 'react-customizer-framework' ),
					esc_html__( 'Enter Email addresses (comma separated)', 'react-customizer-framework' ),
					esc_html__( 'Send', 'react-customizer-framework' ),
					esc_html__( 'Settings Successfully Saved.', 'react-customizer-framework' ),
					esc_html__( 'Please save the changes to send test email.', 'react-customizer-framework' ),
				),
				'iframeUrl'             => array(
					'email_registration' => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=email_registration' ),
					'email_checkout'     => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=email_checkout' ),
					'email_login_otp'    => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=email_login_otp' ),
					'email_login_auth'   => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=email_login_auth' ),
					'popup_registration' => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=popup_registration' ),
					'popup_login_auth'   => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=popup_login_auth' ),
					'popup_checkout'     => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=popup_checkout' ),
					'popup_checkout_otp' => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=popup_checkout_otp' ),
					'email_edit_account' => admin_url( 'admin-ajax.php?action=' . self::$screen_id . '_email_preview&preview=email_edit_account' ),
				),
				'back_to_wordpress_link' => admin_url( 'admin.php?page=customer-email-verification-for-woocommerce' ),
				'nonce'                  => wp_create_nonce( 'ajax-nonce' ),
				'main_title'             => self::$screen_title,
				'send_test_email_btn'    => false,
				'rest_nonce'             => wp_create_nonce( 'wp_rest' ),
				'rest_base'              => esc_url_raw( rest_url() ),
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers custom REST API endpoints for retrieving settings and
	 * updating customizer options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function route_api_functions() {
		// Register GET endpoint for settings.
		register_rest_route(
			self::$screen_id,
			'settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'return_json_success_settings_route_api' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register POST endpoint for updating settings.
		register_rest_route(
			self::$screen_id,
			'store/update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_store_settings' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST API callback for retrieving settings.
	 *
	 * Returns all customizer settings as JSON for the React interface.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return WP_REST_Response|WP_Error JSON response with settings data.
	 */
	public function return_json_success_settings_route_api( $request ) {
		$preview = ! empty( $request->get_param( 'preview' ) ) ? sanitize_text_field( $request->get_param( 'preview' ) ) : '';
		return wp_send_json_success( $this->customize_setting_options_func( $preview ) );
	}

	/**
	 * Get customizer setting options.
	 *
	 * Applies filters to collect all customizer settings from registered
	 * option providers.
	 *
	 * @since 1.0.0
	 * @param string $preview Preview type (optional).
	 * @return array Array of customizer settings.
	 */
	public function customize_setting_options_func( $preview ) {
		$settings = array();
		$settings = apply_filters( self::$screen_id . '_email_options', $settings, $preview );
		return $settings;
	}

	/**
	 * AJAX handler for email preview.
	 *
	 * Handles AJAX requests to generate and return email/popup previews.
	 * Outputs the preview HTML and terminates execution.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_preview_func() {
		// Get and sanitize preview type from query string.
		$preview = isset( $_GET['preview'] ) ? wc_clean( wp_unslash( $_GET['preview'] ) ) : '';

		// Whitelist allowed preview types.
		$allowed_previews = array(
			'email_registration', 'email_checkout', 'email_login_otp', 'email_login_auth',
			'popup_registration', 'popup_login_auth', 'popup_checkout', 'popup_checkout_otp',
			'email_edit_account',
		);
		if ( ! in_array( $preview, $allowed_previews, true ) ) {
			wp_die( esc_html__( 'Invalid preview type.', 'customer-email-verification' ) );
		}

		// Check if this is a popup preview (needs full HTML page structure).
		$is_popup_preview = in_array( $preview, array( 'popup_registration', 'popup_login_auth', 'popup_checkout', 'popup_checkout_otp' ), true );

		// Get preview content.
		$content = $this->get_preview_email( $preview );

		// Check if this is a popup preview (needs full HTML page structure).
		$is_popup_preview = in_array( $preview, array( 'popup_registration', 'popup_login_auth', 'popup_checkout', 'popup_checkout_otp' ), true );

		// For popup previews, content comes from WordPress core functions (wp_head, get_footer)
		// which are already safe. We use wp_kses with expanded allowed tags for full HTML documents.
		if ( $is_popup_preview ) {
			// Add filters to allow all necessary CSS properties and bypass strict CSS value sanitization for popup previews.
			add_filter( 'safe_style_css', array( $this, 'popup_preview_safe_css' ), 10, 1 );
			add_filter( 'safecss_filter_attr', array( $this, 'popup_preview_bypass_css_sanitization' ), 10, 2 );
			
			// Use wp_kses with expanded allowed tags for full HTML document structure.
			// Start with 'post' context which includes most HTML elements (div, span, input, form, etc.)
			$allowed_html = wp_kses_allowed_html( 'post' );
			
			// Add HTML document structure tags.
			$allowed_html['html'] = array( 'lang' => true );
			$allowed_html['head'] = array();
			$allowed_html['meta'] = array( 'charset' => true, 'name' => true, 'content' => true, 'property' => true );
			$allowed_html['title'] = array();
			$allowed_html['link'] = array( 'rel' => true, 'href' => true, 'id' => true, 'media' => true, 'type' => true );
			$allowed_html['script'] = array( 'type' => true, 'src' => true, 'id' => true, 'async' => true, 'defer' => true );
			$allowed_html['style'] = array( 'type' => true, 'id' => true, 'media' => true );
			$allowed_html['body'] = array( 'class' => true, 'id' => true );
			
			// Ensure input elements are allowed with all necessary attributes (merge with existing).
			if ( ! isset( $allowed_html['input'] ) ) {
				$allowed_html['input'] = array();
			}
			$allowed_html['input'] = array_merge( $allowed_html['input'], array(
				'type' => true,
				'name' => true,
				'value' => true,
				'class' => true,
				'id' => true,
				'placeholder' => true,
				'maxlength' => true,
				'aria-label' => true,
				'inputmode' => true,
				'autocomplete' => true,
				'required' => true,
				'disabled' => true,
				'readonly' => true,
				'style' => true,
			) );
			
			// Ensure form elements are allowed (merge with existing).
			if ( ! isset( $allowed_html['form'] ) ) {
				$allowed_html['form'] = array();
			}
			$allowed_html['form'] = array_merge( $allowed_html['form'], array(
				'class' => true,
				'id' => true,
				'method' => true,
				'action' => true,
				'novalidate' => true,
				'style' => true,
			) );
			
			// Ensure button elements are allowed with all necessary attributes.
			if ( ! isset( $allowed_html['button'] ) ) {
				$allowed_html['button'] = array();
			}
			$allowed_html['button'] = array_merge( $allowed_html['button'], array(
				'type' => true,
				'class' => true,
				'id' => true,
				'name' => true,
				'value' => true,
				'disabled' => true,
				'aria-label' => true,
				'role' => true,
				'tabindex' => true,
				'style' => true,
			) );
			
			// Ensure anchor elements can have all necessary attributes.
			if ( ! isset( $allowed_html['a'] ) ) {
				$allowed_html['a'] = array();
			}
			$allowed_html['a'] = array_merge( $allowed_html['a'], array(
				'href' => true,
				'class' => true,
				'id' => true,
				'target' => true,
				'rel' => true,
				'data-nonce' => true,
				'role' => true,
				'aria-label' => true,
				'style' => true,
			) );
			
			// Ensure div and span can have role and aria attributes.
			if ( ! isset( $allowed_html['div'] ) ) {
				$allowed_html['div'] = array();
			}
			$allowed_html['div'] = array_merge( $allowed_html['div'], array(
				'role' => true,
				'aria-label' => true,
				'aria-live' => true,
				'tabindex' => true,
				'class' => true,
				'id' => true,
			) );
			
			if ( ! isset( $allowed_html['span'] ) ) {
				$allowed_html['span'] = array();
			}
			$allowed_html['span'] = array_merge( $allowed_html['span'], array(
				'role' => true,
				'aria-label' => true,
			) );
			
			// Ensure SVG elements are allowed for icons (merge with existing if any).
			if ( ! isset( $allowed_html['svg'] ) ) {
				$allowed_html['svg'] = array();
			}
			$allowed_html['svg'] = array_merge( $allowed_html['svg'], array(
				'fill' => true,
				'version' => true,
				'viewBox' => true,
				'xmlns' => true,
				'xmlns:xlink' => true,
				'aria-hidden' => true,
				'class' => true,
				'id' => true,
				'width' => true,
				'height' => true,
				'style' => true,
			) );
			
			// Ensure polygon elements are allowed.
			if ( ! isset( $allowed_html['polygon'] ) ) {
				$allowed_html['polygon'] = array();
			}
			$allowed_html['polygon'] = array_merge( $allowed_html['polygon'], array(
				'points' => true,
				'fill' => true,
				'stroke' => true,
				'stroke-width' => true,
				'style' => true,
			) );
			
			// Ensure path elements are allowed.
			if ( ! isset( $allowed_html['path'] ) ) {
				$allowed_html['path'] = array();
			}
			$allowed_html['path'] = array_merge( $allowed_html['path'], array(
				'd' => true,
				'fill' => true,
				'stroke' => true,
				'stroke-width' => true,
				'style' => true,
			) );
			
			// Ensure all elements can have style attributes (needed for inline styles).
			foreach ( $allowed_html as $tag => $attributes ) {
				if ( ! isset( $attributes['style'] ) ) {
					$allowed_html[ $tag ]['style'] = true;
				}
			}
			
			// For popup previews, we need to preserve CSS values (RGBA, !important, etc.)
			// Since content comes from trusted sources, we can bypass CSS value sanitization.
			// Extract style attributes, sanitize HTML, then restore style attributes with original values.
			$style_attrs = array();
			
			// Extract all style attributes before sanitization (handle both single and double quotes).
			// Pattern matches: style="value" or style='value'
			preg_match_all( '/style\s*=\s*(["\'])([^"\']+)\1/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
			foreach ( $matches as $index => $match ) {
				$placeholder = '___CEV_STYLE_' . $index . '___';
				$quote = $match[1][0]; // The quote character used (single or double)
				$css_value = $match[2][0]; // The CSS value
				$style_attrs[ $placeholder ] = array(
					'value' => $css_value,
					'quote' => $quote,
				);
				// Replace with placeholder
				$full_match = $match[0][0];
				$replacement = 'style=' . $quote . $placeholder . $quote;
				$content = str_replace( $full_match, $replacement, $content );
			}
			
			// Sanitize HTML (style attributes now contain placeholders that won't be sanitized as CSS).
			$sanitized = wp_kses( $content, $allowed_html );
			
			// Restore style attributes with original CSS values (bypass CSS value sanitization).
			foreach ( $style_attrs as $placeholder => $style_data ) {
				$quote     = $style_data['quote'];
				$css_value = $style_data['value'];
				$sanitized = str_replace( 'style=' . $quote . $placeholder . $quote, 'style=' . $quote . esc_attr( $css_value ) . $quote, $sanitized );
			}

			// $sanitized has already been passed through wp_kses with a carefully constructed
			// $allowed_html whitelist, and style values have been individually escaped via esc_attr().
			// Re-escaping the entire HTML with wp_kses_post() would strip necessary inline styles
			// required for an accurate popup preview in the Customizer, so we intentionally echo
			// the sanitized HTML directly here.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $sanitized;
			
			// Remove the filters after processing.
			remove_filter( 'safe_style_css', array( $this, 'popup_preview_safe_css' ), 10 );
			remove_filter( 'safecss_filter_attr', array( $this, 'popup_preview_bypass_css_sanitization' ), 10 );
		} else {
			// For email previews, use standard wp_kses_post.
			echo wp_kses_post( $content );
		}
		die();
	}

	/**
	 * Update customizer settings via REST API.
	 *
	 * Processes POST requests to update customizer settings. Handles different
	 * field types (textarea, key, array) and saves them to WordPress options.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return void Outputs JSON response and terminates execution.
	 */
	public function update_store_settings( $request ) {
		// Get preview type from request.
		$preview = ! empty( $request->get_param( 'preview' ) ) ? sanitize_text_field( $request->get_param( 'preview' ) ) : '';

		// Get all request parameters.
		$data = $request->get_params() ? $request->get_params() : array();

		// Validate data exists.
		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No data provided.', 'customer-email-verification' ) ) );
			return;
		}

		// Get all registered settings.
		$settings = $this->customize_setting_options_func( $preview );

		// Get existing consolidated customizer settings.
		$customizer_settings = get_option( 'cev_pro_customizer', array() );
		if ( ! is_array( $customizer_settings ) ) {
			$customizer_settings = array();
		}

		// Process each setting and add to consolidated array.
		foreach ( $settings as $key => $val ) {
			// Skip if setting not in data or not shown.
			if ( ! isset( $data[ $key ] ) || ( isset( $val['show'] ) && true !== $val['show'] ) ) {
				continue;
			}

			// Sanitize and store in consolidated array.
			if ( isset( $val['type'] ) && 'textarea' === $val['type'] ) {
				$customizer_settings[ $key ] = isset( $data[ $key ] ) ? wp_kses_post( wp_unslash( $data[ $key ] ) ) : '';
			} else {
				$customizer_settings[ $key ] = isset( $data[ $key ] ) ? wc_clean( wp_unslash( $data[ $key ] ) ) : '';
			}
		}

		// Save all settings to single consolidated option.
		update_option( 'cev_pro_customizer', $customizer_settings );

		// Return success response with proper structure.
		// Ensure response is sent and execution stops.
		wp_send_json_success(
			array(
				'success' => true,
				'preview' => $preview,
				'message' => __( 'Settings saved successfully.', 'customer-email-verification' ),
			)
		);
		// Exit to prevent any additional output that might break JSON response.
		exit;
	}

	/**
	 * Get preview email content.
	 *
	 * Generates preview content for emails or popups by applying filters
	 * and adding necessary styles and security filters.
	 *
	 * @since 1.0.0
	 * @param string $preview Preview type (e.g., 'email_registration', 'popup_registration').
	 * @return string Sanitized preview HTML content.
	 */
	public function get_preview_email( $preview ) {
		// Get preview content via filter.
		$content = apply_filters( self::$screen_id . '_preview_content', $preview );

		// Check if this is a popup preview (needs full HTML page structure).
		$is_popup_preview = in_array( $preview, array( 'popup_registration', 'popup_login_auth', 'popup_checkout', 'popup_checkout_otp' ), true );

		if ( $is_popup_preview ) {
			// For popup previews, add body margin style and return content as-is
			// (content already includes full HTML structure from wp_head/get_footer).
			$content = str_replace( '<body', '<style type="text/css">body{margin: 0;}</style><body', $content );
			return $content;
		}

		// For email previews, add inline styles and sanitize.
		$content .= '<style type="text/css">body{margin: 0;}</style>';

		// Add filters for allowed CSS tags and safe styles.
		add_filter( 'wp_kses_allowed_html', array( $this, 'allowed_css_tags' ) );
		add_filter( 'safe_style_css', array( $this, 'safe_style_css' ), 10, 1 );

		// Return sanitized content.
		return wp_kses_post( $content );
	}

	/**
	 * Route preview content to appropriate preview method.
	 *
	 * Determines which preview method to call based on the preview type.
	 * Routes to email previews or popup previews as needed.
	 *
	 * @since 1.0.0
	 * @param string $preview Preview type.
	 * @return string Preview HTML content.
	 */
	public function cev_customizer_preview_content( $preview ) {
		switch ( $preview ) {
			case 'email_checkout':
				return $this->preview_checkout_email();

			case 'email_registration':
				return $this->preview_account_email();

			case 'email_login_auth':
				return $this->preview_login_auth_email();

			case 'email_login_otp':
				return $this->preview_login_otp_email();

			case 'popup_registration':
				return $this->preview_popup_registration();

			case 'popup_checkout':
				return $this->preview_popup_checkout();

			case 'popup_checkout_otp':
				return $this->preview_popup_checkout_otp();

			case 'popup_login_auth':
				return $this->preview_popup_login_auth();

			case 'email_edit_account':
				return $this->preview_edit_email();

			default:
				return $this->preview_account_email();
		}
	}

	/**
	 * Generate preview for registration email.
	 *
	 * Creates a preview of the registration verification email using
	 * WooCommerce email templates and styling.
	 *
	 * @since 1.0.0
	 * @return string HTML content of the email preview.
	 */
	public function preview_account_email() {
		// Initialize WooCommerce email system.
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		// Set user ID and email for merge tag parsing.
		$user_id = 1;
		$user = get_userdata( $user_id );
		$user_email = $user ? $user->user_email : 'user@example.com';
		
		cev_pro()->function->wuev_user_id = $user_id;
		cev_pro()->function->registered_user_email = $user_email;

		// Generate a sample verification PIN for preview.
		$sample_pin = cev_pro()->function->generate_verification_pin();
		
		// Store sample PIN in user meta temporarily for preview.
		update_user_meta( $user_id, 'cev_email_verification_pin', array( 'pin' => $sample_pin ) );

		// Get email heading and parse merge tags.
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_heading', cev_pro()->customizer_options->defaults['cev_verification_email_heading'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, cev_pro()->function );

		// Get email body content.
		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_body', cev_pro()->customizer_options->defaults['cev_verification_email_body'] );

		// Initialize WooCommerce mailer.
		$mailer = WC()->mailer();

		// Create email object.
		$email      = new WC_Email();
		$email->id  = 'CEV_Registration_Verification';

		// Parse merge tags in email content.
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, cev_pro()->function );

		// Get footer content.
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content', cev_pro()->customizer_options->defaults['cev_new_verification_Footer_content'] );

		// Generate email template content.
		ob_start();
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';

		// Use local template if available, otherwise use plugin template.
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'         => $email_content,
					'footer_content'  => $footer_content,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $email_content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}
		$content = ob_get_clean();

		// Wrap content with email template and apply styles.
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		return wp_kses_post( $message );
	}

	/**
	 * Generate preview for login authentication email.
	 *
	 * Creates a preview of the login authentication email with user login
	 * details and device information.
	 *
	 * @since 1.0.0
	 * @return string HTML content of the email preview.
	 */
	public function preview_login_auth_email() {
		// Initialize WooCommerce email system.
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		// Get email heading and parse merge tags.
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_auth_email_heading', cev_pro()->customizer_options->defaults['cev_verification_login_auth_email_heading'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, cev_pro()->function );

		// Get email body content.
		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_auth_email_content', cev_pro()->customizer_options->defaults['cev_verification_login_auth_email_content'] );

		// Set user ID and get user data.
		$user_id = 1;
		cev_pro()->function->wuev_user_id = $user_id;
		$user_info                = get_userdata( $user_id );
		$user_last_login_details  = get_user_meta( $user_id, 'cev_last_login_detail' );
		$user_last_login_time     = get_user_meta( $user_id, 'cev_last_login_time', true );

		// Initialize WooCommerce mailer.
		$mailer = WC()->mailer();

		// Create email object.
		$email     = new WC_Email();
		$email->id = 'CEV_Login_Auth';

		// Parse merge tags in email content.
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, cev_pro()->function );

		// Get footer content.
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_footer_content', cev_pro()->customizer_options->defaults['cev_login_auth_footer_content'] );

		// Generate email template content.
		ob_start();
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-login-authentication.php';

		// Use local template if available, otherwise use plugin template.
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			wc_get_template(
				'emails/cev-login-authentication.php',
				array(
					'email_heading'         => $email_heading,
					'content'               => $email_content,
					'footer_content'        => $footer_content,
					'user_info'             => $user_info,
					'user_last_login_details' => $user_last_login_details,
					'user_last_login_time'  => $user_last_login_time,
					'login_otp'             => '',
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			wc_get_template(
				'emails/cev-login-authentication.php',
				array(
					'email_heading'         => $email_heading,
					'content'              => $email_content,
					'footer_content'       => $footer_content,
					'user_info'            => $user_info,
					'user_last_login_details' => $user_last_login_details,
					'user_last_login_time' => $user_last_login_time,
					'login_otp'            => '',
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}
		$content = ob_get_clean();

		// Wrap content with email template and apply styles.
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		return wp_kses_post( $message );
	}

	/**
	 * Generate preview for login OTP email.
	 *
	 * Creates a preview of the login OTP email with a generated verification code.
	 *
	 * @since 1.0.0
	 * @return string HTML content of the email preview.
	 */
	public function preview_login_otp_email() {
		// Initialize WooCommerce email system.
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		// Set user ID and get user data.
		$user_id = 1;
		cev_pro()->function->wuev_user_id = $user_id;
		$user_info                = get_userdata( $user_id );
		$user_last_login_details  = get_user_meta( $user_id, 'cev_last_login_detail' );
		$user_last_login_time     = get_user_meta( $user_id, 'cev_last_login_time', true );

		// Generate OTP code for preview and set it on context so {cev_user_verification_pin} works.
		$login_otp = cev_pro()->function->generate_verification_pin();
		cev_pro()->function->login_otp = $login_otp;

		// Get email heading and parse merge tags (OTP must be set before parsing).
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_otp_email_heading', cev_pro()->customizer_options->defaults['cev_verification_login_otp_email_heading'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, cev_pro()->function );

		// Get email body content.
		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_login_otp_email_content', cev_pro()->customizer_options->defaults['cev_verification_login_otp_email_content'] );

		// Initialize WooCommerce mailer.
		$mailer = WC()->mailer();

		// Create email object.
		$email     = new WC_Email();
		$email->id = 'CEV_Login_Auth';

		// Parse merge tags in email content (OTP is already set on context).
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, cev_pro()->function );

		// Get footer content.
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_otp_footer_content', cev_pro()->customizer_options->defaults['cev_login_otp_footer_content'] );

		// Generate email template content.
		ob_start();
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-login-authentication.php';

		// Use local template if available, otherwise use plugin template.
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			wc_get_template(
				'emails/cev-login-authentication.php',
				array(
					'email_heading'         => $email_heading,
					'content'               => $email_content,
					'footer_content'        => $footer_content,
					'user_last_login_details' => $user_last_login_details,
					'user_last_login_time'  => $user_last_login_time,
					'login_otp'             => $login_otp,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			wc_get_template(
				'emails/cev-login-authentication.php',
				array(
					'email_heading'         => $email_heading,
					'content'              => $email_content,
					'footer_content'       => $footer_content,
					'user_info'            => $user_info,
					'user_last_login_details' => $user_last_login_details,
					'user_last_login_time' => $user_last_login_time,
					'login_otp'            => $login_otp,
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}
		$content = ob_get_clean();

		// Wrap content with email template and apply styles.
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		return wp_kses_post( $message );
	}

	/**
	 * Generate preview for checkout email.
	 *
	 * Creates a preview of the checkout verification email for guest users.
	 *
	 * @since 1.0.0
	 * @return string HTML content of the email preview.
	 */
	public function preview_checkout_email() {
		// Initialize WooCommerce email system.
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		// Generate a sample verification PIN for preview.
		$sample_pin = cev_pro()->function->generate_verification_pin();
		
		// Set up session data for guest user preview.
		if ( function_exists( 'WC' ) && WC()->session ) {
			$verification_data = array(
				'pin' => base64_encode( $sample_pin ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			);
			WC()->session->set( 'cev_user_verification_data', wp_json_encode( $verification_data ) );
		}

		// Create a mock checkout email context object for preview.
		// This allows the merge tag parser to recognize it as a checkout context.
		$checkout_email_context = new stdClass();
		// Set a flag to indicate this is a checkout preview context.
		$checkout_email_context->is_checkout_preview = true;

		// Get email heading and parse merge tags.
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_heading_che', cev_pro()->customizer_options->defaults['cev_verification_email_heading_che'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, $checkout_email_context );

		// Get email body content.
		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_body_che', cev_pro()->customizer_options->defaults['cev_verification_email_body_che'] );

		// Initialize WooCommerce mailer.
		$mailer = WC()->mailer();

		// Create email object.
		$email     = new WC_Email();
		$email->id = 'CEV_Guest_User_Verification';

		// Parse merge tags in email content with checkout context.
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, $checkout_email_context );

		// Get footer content.
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content_che', cev_pro()->customizer_options->defaults['cev_new_verification_Footer_content_che'] );

		// Generate email template content.
		ob_start();
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';

		// Use local template if available, otherwise use plugin template.
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $email_content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $email_content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}
		$content = ob_get_clean();

		// Wrap content with email template and apply styles.
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		return wp_kses_post( $message );
	}

	/**
	 * Generate preview for edit account email.
	 *
	 * Creates a preview of the email verification email sent when a user
	 * changes their email address.
	 *
	 * @since 1.0.0
	 * @return string HTML content of the email preview.
	 */
	public function preview_edit_email() {
		// Initialize WooCommerce email system.
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		// Set user ID and email for merge tag parsing.
		$user_id = 1;
		$user = get_userdata( $user_id );
		$user_email = $user ? $user->user_email : 'user@example.com';
		
		cev_pro()->function->wuev_user_id = $user_id;
		cev_pro()->function->registered_user_email = $user_email;

		// Generate a sample verification PIN for preview.
		$sample_pin = cev_pro()->function->generate_verification_pin();
		
		// Store sample PIN in user meta temporarily for preview.
		update_user_meta( $user_id, 'cev_email_verification_pin', array( 'pin' => $sample_pin ) );

		// Get email heading and parse merge tags.
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_heading_ed', cev_pro()->customizer_options->defaults['cev_verification_email_heading_ed'] );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, cev_pro()->function );

		// Get email body content.
		$email_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_body_ed', cev_pro()->customizer_options->defaults['cev_verification_email_body_ed'] );

		// Initialize WooCommerce mailer.
		$mailer = WC()->mailer();

		// Create email object.
		$email     = new WC_Email();
		$email->id = 'CEV_Guest_User_Verification';

		// Parse merge tags in email content.
		$email_content = cev_pro()->function->maybe_parse_merge_tags( $email_content, cev_pro()->function );

		// Get footer content.
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content', cev_pro()->customizer_options->defaults['cev_new_verification_Footer_content'] );

		// Generate email template content.
		ob_start();
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';

		// Use local template if available, otherwise use plugin template.
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $email_content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			wc_get_template(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $email_content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}
		$content = ob_get_clean();

		// Wrap content with email template and apply styles.
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );
		$message = apply_filters( 'wc_cev_decode_html_content', $message );

		return wp_kses_post( $message );
	}

	/**
	 * Generate preview for registration popup.
	 *
	 * Outputs the registration verification popup preview by including
	 * the preview template file.
	 *
	 * @since 1.0.0
	 * @return void Outputs HTML directly.
	 */
	public function preview_popup_registration() {
		// Start output buffering to capture output.
		ob_start();
		
		// Enqueue styles first, then add inline styles, then output head.
		wp_enqueue_style( 'cev_front_style' );
		
		// Add inline styles for preview instead of outputting in template.
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
		$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
		$inline_css = "
			.cev-authorization-grid__visual {
				background-color: {$overlay_rgba} !important;
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				z-index: 5000 !important;
			}
			html {
				background: none;
			}
			footer#footer {
				display: none;
			}
			.customize-partial-edit-shortcut-button {
				display: none;
			}
		";
		wp_add_inline_style( 'cev_front_style', $inline_css );
		
		// Output head after styles are enqueued.
		wp_head();
		
		include 'preview/preview_cev_popup_page.php';
		get_footer();
		
		// Return buffered output.
		return ob_get_clean();
	}

	/**
	 * Generate preview for login authentication popup.
	 *
	 * Outputs the login authentication popup preview by including
	 * the preview template file.
	 *
	 * @since 1.0.0
	 * @return string Preview HTML content.
	 */
	public function preview_popup_login_auth() {
		// Start output buffering to capture output.
		ob_start();
		
		// Enqueue styles first, then add inline styles, then output head.
		wp_enqueue_style( 'cev_front_style' );
		
		// Add inline styles for preview instead of outputting in template.
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
		$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
		$inline_css = "
			.cev-authorization-grid__visual {
				background-color: {$overlay_rgba} !important;
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				z-index: 5000 !important;
			}
			html {
				background: none;
			}
			footer#footer {
				display: none;
			}
			.customize-partial-edit-shortcut-button {
				display: none;
			}
		";
		wp_add_inline_style( 'cev_front_style', $inline_css );
		
		// Output head after styles are enqueued.
		wp_head();
		
		// Use the preview file from customizer preview directory for consistency.
		$preview_template_path = cev_pro()->get_plugin_path() . '/includes/customizer/preview/preview_cev_popup_login_auth.php';
		if ( file_exists( $preview_template_path ) ) {
			include $preview_template_path;
		} else {
			// Fallback to login-authentication views if customizer preview doesn't exist.
			$fallback_path = cev_pro()->get_plugin_path() . '/includes/login-authentication/views/preview-popup.php';
			if ( file_exists( $fallback_path ) ) {
				include $fallback_path;
			}
		}
		get_footer();
		
		// Return buffered output.
		return ob_get_clean();
	}

	/**
	 * Generate preview for checkout popup.
	 *
	 * Outputs the checkout verification popup preview by including
	 * the preview template file and enqueuing necessary scripts.
	 *
	 * @since 1.0.0
	 * @return string Preview HTML content.
	 */
	public function preview_popup_checkout() {
		// Start output buffering to capture output.
		ob_start();
		
		// Enqueue styles and scripts first, then add inline styles, then output head.
		wp_enqueue_style( 'cev_front_style' );
		wp_enqueue_script( 'cev-pro-front-js' );
		
		// Add inline styles for preview instead of outputting in template.
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
		$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
		$inline_css = "
			.cev-authorization-grid__visual {
				background-color: {$overlay_rgba} !important;
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				z-index: 5000 !important;
			}
			html {
				background: none;
			}
			footer#footer {
				display: none;
			}
			.customize-partial-edit-shortcut-button {
				display: none;
			}
		";
		wp_add_inline_style( 'cev_front_style', $inline_css );
		
		// Output head after styles are enqueued.
		wp_head();
		
		include 'preview/verify_checkout_guest_user.php';
		get_footer();
		
		// Return buffered output.
		return ob_get_clean();
	}

	/**
	 * Generate preview for checkout OTP popup.
	 *
	 * Outputs the checkout OTP verification popup preview by including
	 * the preview template file with OTP fields displayed.
	 *
	 * @since 1.0.0
	 * @return string Preview HTML content.
	 */
	public function preview_popup_checkout_otp() {
		// Start output buffering to capture output.
		ob_start();
		
		// Enqueue styles and scripts first, then add inline styles, then output head.
		wp_enqueue_style( 'cev_front_style' );
		wp_enqueue_script( 'cev-pro-front-js' );
		
		// Add inline styles for preview instead of outputting in template.
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
		$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
		$inline_css = "
			.cev-authorization-grid__visual {
				background-color: {$overlay_rgba} !important;
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				z-index: 5000 !important;
			}
			html {
				background: none;
			}
			footer#footer {
				display: none;
			}
			.customize-partial-edit-shortcut-button {
				display: none;
			}
		";
		wp_add_inline_style( 'cev_front_style', $inline_css );
		
		// Output head after styles are enqueued.
		wp_head();
		
		include 'preview/verify_checkout_guest_user_otp.php';
		get_footer();
		
		// Return buffered output.
		return ob_get_clean();
	}

	/**
	 * Allow style tags in wp_kses.
	 *
	 * Adds 'style' tag to the list of allowed HTML tags for wp_kses.
	 * This is needed for inline CSS in email previews.
	 *
	 * @since 1.0.0
	 * @param array $tags Allowed HTML tags.
	 * @return array Modified allowed HTML tags.
	 */
	public function allowed_css_tags( $tags ) {
		$tags['style'] = array( 'type' => true );
		return $tags;
	}

	/**
	 * Add display to safe CSS styles.
	 *
	 * Adds 'display' to the list of safe CSS properties for wp_kses.
	 * This is needed for inline styles in email previews.
	 *
	 * @since 1.0.0
	 * @param array $styles Array of safe CSS style properties.
	 * @return array Modified safe CSS styles array.
	 */
	public function safe_style_css( $styles ) {
		$styles[] = 'display';
		return $styles;
	}

	/**
	 * Add all necessary CSS properties for popup previews.
	 *
	 * Expands the list of safe CSS properties to include all properties
	 * needed for popup preview rendering (background-color, position, z-index, etc.).
	 * This ensures overlay backgrounds and positioning work correctly.
	 *
	 * @since 1.0.0
	 * @param array $styles Array of safe CSS style properties.
	 * @return array Modified safe CSS styles array with all necessary properties.
	 */
	public function popup_preview_safe_css( $styles ) {
		// List of CSS properties needed for popup previews.
		$popup_css_properties = array(
			'background-color',
			'background',
			'position',
			'top',
			'left',
			'right',
			'bottom',
			'width',
			'height',
			'max-width',
			'max-height',
			'min-width',
			'min-height',
			'z-index',
			'display',
			'text-align',
			'padding',
			'padding-top',
			'padding-right',
			'padding-bottom',
			'padding-left',
			'margin',
			'margin-top',
			'margin-right',
			'margin-bottom',
			'margin-left',
			'font-size',
			'font-weight',
			'font-family',
			'color',
			'border',
			'border-radius',
			'border-width',
			'border-color',
			'border-style',
			'opacity',
			'visibility',
			'overflow',
			'overflow-x',
			'overflow-y',
			'cursor',
			'line-height',
			'text-decoration',
			'vertical-align',
		);

		// Merge with existing styles and ensure no duplicates.
		return array_unique( array_merge( (array) $styles, $popup_css_properties ) );
	}

	/**
	 * Bypass CSS value sanitization for popup previews.
	 *
	 * Since popup preview content comes from trusted sources (WordPress core functions
	 * and plugin template files), we can safely bypass strict CSS value sanitization
	 * to allow RGBA colors, !important declarations, and other complex CSS values.
	 *
	 * @since 1.0.0
	 * @param string $css CSS value to sanitize.
	 * @param string $attr Attribute name (unused).
	 * @return string Original CSS value (bypassed for popup previews).
	 */
	public function popup_preview_bypass_css_sanitization( $css, $attr ) {
		// Return CSS as-is for popup previews (bypass WordPress's strict CSS sanitization).
		// This allows RGBA values, !important, and other complex CSS values.
		return $css;
	}
}
