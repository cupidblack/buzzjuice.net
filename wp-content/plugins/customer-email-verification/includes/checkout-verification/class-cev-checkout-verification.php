<?php
/**
 * Checkout Verification Class for Customer Email Verification
 *
 * Main class that orchestrates the checkout verification process including
 * email verification, OTP generation, verification checks, and AJAX handlers.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Checkout_Verification Class
 *
 * Manages checkout verification process including email validation,
 * OTP generation and verification, guest user handling, and AJAX handlers.
 */
class CEV_Checkout_Verification {

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Checkout_Verification|null Class Instance
	 */
	private static $instance = null;

	/**
	 * AJAX handler instance.
	 *
	 * @var CEV_Checkout_Ajax
	 */
	private $ajax_handler;

	/**
	 * Email sender instance.
	 *
	 * @var CEV_Checkout_Email
	 */
	private $email_handler;

	/**
	 * Validation handler instance.
	 *
	 * @var CEV_Checkout_Validation
	 */
	private $validation_handler;

	/**
	 * Initialize the main plugin function.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
		if ( did_action( 'woocommerce_init' ) ) {
			$this->init();
		} else {
			add_action( 'woocommerce_init', array( $this, 'init' ) );
		}
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
		require_once $plugin_path . '/includes/checkout-verification/cev-pro-checkout-ajax.php';
		require_once $plugin_path . '/includes/checkout-verification/cev-pro-checkout-email.php';
		require_once $plugin_path . '/includes/checkout-verification/cev-pro-checkout-validation.php';
	}

	/**
	 * Initialize component instances.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_components() {
		$this->ajax_handler       = new CEV_Checkout_Ajax( $this );
		$this->email_handler      = new CEV_Checkout_Email( $this );
		$this->validation_handler = new CEV_Checkout_Validation( $this );
	}

	/**
	 * Get the class instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Checkout_Verification Class instance.
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
	 * Registers WordPress hooks for checkout verification functionality.
	 * Only runs if checkout verification is enabled.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Feature gate: Only initialize if checkout verification is enabled.
		if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			return;
		}

		// Block checkout for guest users without verification.
		add_action( 'wp_footer', array( $this, 'disable_checkout_page_for_guest_user' ) );

		// Block checkout for logged-in unverified users (popup mode).
		add_action( 'wp_footer', array( $this, 'disable_checkout_page_for_logged_in_user' ) );

		// Checkout validation.
		add_action( 'woocommerce_after_checkout_validation', array( $this->validation_handler, 'after_checkout_validation' ), 10, 2 );

		// Inline verification display.
		add_action( 'wp_footer', array( $this, 'cev_display_inline_verification_on_checkout' ), 100 );
		add_action( 'wp_loaded', array( $this, 'handle_inline_verification_display' ) );
	}

	/**
	 * Get AJAX handler instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Checkout_Ajax AJAX handler instance.
	 */
	public function get_ajax_handler() {
		return $this->ajax_handler;
	}

	/**
	 * Get email handler instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Checkout_Email Email handler instance.
	 */
	public function get_email_handler() {
		return $this->email_handler;
	}

	/**
	 * Get validation handler instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Checkout_Validation Validation handler instance.
	 */
	public function get_validation_handler() {
		return $this->validation_handler;
	}

	/**
	 * Block cart and checkout page for guest users without verification.
	 *
	 * Displays verification popup for guest users if they haven't verified
	 * their email and checkout verification is enabled.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function disable_checkout_page_for_guest_user() {
		// Early returns for various conditions.
		if ( is_user_logged_in() ) {
			return;
		}
		if ( ! is_checkout() && ! is_cart() ) {
			return;
		}
		if ( is_order_received_page() ) {
			return;
		}

		$cev_enable_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
		if ( 1 != $cev_enable_email_verification_checkout ) {
			return;
		}

		$cev_inline_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
		if ( 2 === (int) $cev_inline_email_verification_checkout ) {
			return;
		}

		// Check if verification is needed for guest user.
		if ( ! $this->needs_verification_for_guest_user() ) {
			return;
		}

		// Display verification popup.
		$this->render_guest_verification_popup();
	}

	/**
	 * Block checkout for logged-in users whose email is not verified (popup mode).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function disable_checkout_page_for_logged_in_user() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! is_checkout() && ! is_cart() ) {
			return;
		}
		if ( is_order_received_page() ) {
			return;
		}

		$cev_enable = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
		if ( 1 != $cev_enable ) {
			return;
		}

		// Respect the cart page setting — only show on cart if that option is enabled.
		$cart_enabled = '1' === cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_cart_page' );
		if ( is_cart() && ! $cart_enabled ) {
			return;
		}

		// Only for popup mode — inline mode is handled by other methods.
		$mode = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
		if ( 2 === $mode ) {
			return;
		}

		// Skip if user is already verified.
		if ( 'true' === get_user_meta( get_current_user_id(), 'customer_email_verified', true ) ) {
			return;
		}

		// Respect free orders exemption.
		$free_orders_exempt = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );
		if ( 1 === (int) $free_orders_exempt && WC()->cart->total > 0 ) {
			return;
		}

		// Render the popup (reuses guest popup — same template).
		$this->render_guest_verification_popup();

		// Pre-fill the email input with account email and make it read-only.
		$user_email = wp_get_current_user()->user_email;
		?>
		<script>
		jQuery(document).ready(function($) {
			setTimeout(function() {
				var $input = $('#cev_pin_email');
				if ($input.length) {
					$input.val('<?php echo esc_js( $user_email ); ?>');
					$input.prop('readonly', true);
				}
			}, 100);
		});
		</script>
		<?php
	}

	/**
	 * Determine if guest user needs verification.
	 *
	 * @since 1.0.0
	 * @return bool True if verification is needed, false otherwise.
	 */
	private function needs_verification_for_guest_user() {
		// Check if checkout verification is enabled for this page.
		$checkout_enabled = '1' === cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout' );
		$cart_enabled     = '1' === cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_cart_page' );

		if ( is_checkout() && ! $checkout_enabled ) {
			return false;
		}
		if ( is_cart() && ( ! $cart_enabled || ! $checkout_enabled ) ) {
			return false;
		}

		// Check session data for verified email.
		$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data' );
		$cev_user_verified_data     = ! is_null( $cev_user_verified_data_raw ) ? json_decode( $cev_user_verified_data_raw ) : null;
		$customer_email             = WC()->customer->get_billing_email();

		// If email is already verified in session, no verification needed.
		if ( isset( $cev_user_verified_data->email ) && $cev_user_verified_data->email === $customer_email && 1 === (int) $cev_user_verified_data->verified ) {
			return false;
		}

		// Check free orders setting.
		$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );
		$order_subtotal                             = WC()->cart->total;

		// If order has value and free orders are exempt, no verification needed.
		if ( $order_subtotal > 0 && 1 === (int) $cev_enable_email_verification_free_orders ) {
			return false;
		}

		return true;
	}

	/**
	 * Render guest verification popup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_guest_verification_popup() {
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$cev_verification_overlay_color = get_option(
			'cev_verification_popup_overlay_background_color',
			$CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color']
		);
		?>
		<style>
		.cev-authorization-grid__visual{
			background: <?php echo esc_html( cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' ) ); ?> !important;
		}
		</style>
		<?php
		require_once cev_pro()->get_plugin_path() . '/includes/checkout-verification/views/verify_checkout_guest_user.php';
	}

	/**
	 * Handle inline verification display.
	 *
	 * Determines when to show inline verification and adds the OTP button
	 * after the email field if needed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_inline_verification_display() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		if ( ! $this->should_show_inline_verification() ) {
			return;
		}

		// Add the Send Verification Code button after email field.
		add_action( 'woocommerce_form_field', array( $this, 'cev_inject_otp_button_after_email' ), 10, 4 );
	}

	/**
	 * Determine if inline verification should be shown.
	 *
	 * @since 1.0.0
	 * @return bool True if inline verification should be shown, false otherwise.
	 */
	private function should_show_inline_verification() {
		$order_subtotal                            = WC()->cart->get_subtotal();
		$cev_enable_email_verification_checkout     = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
		$cev_inline_email_verification_checkout    = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
		$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );

		// For logged-in users.
		if ( is_user_logged_in() ) {
			// Already verified — no inline needed.
			if ( 'true' === get_user_meta( get_current_user_id(), 'customer_email_verified', true ) ) {
				return false;
			}
			// Inline mode must be enabled.
			if ( 1 !== (int) $cev_enable_email_verification_checkout
				|| 2 !== (int) $cev_inline_email_verification_checkout ) {
				return false;
			}
			// Respect free orders exemption.
			if ( $order_subtotal > 0 && 1 === (int) $cev_enable_email_verification_free_orders ) {
				return false;
			}
			return true;
		}

		// Check if inline verification mode is enabled.
		if ( 1 !== (int) $cev_enable_email_verification_checkout || 2 !== (int) $cev_inline_email_verification_checkout ) {
			return false;
		}

		// Determine if verification is needed based on order total.
		$need_inline_verification = false;
		if ( $order_subtotal > 0 && 1 !== (int) $cev_enable_email_verification_free_orders ) {
			$need_inline_verification = true;
		} elseif ( 0 === (int) $order_subtotal && 1 === (int) $cev_enable_email_verification_checkout && 2 === (int) $cev_inline_email_verification_checkout ) {
			$need_inline_verification = true;
		}

		return $need_inline_verification;
	}

	/**
	 * Inject OTP button after email field.
	 *
	 * Adds a "Send Verification Code" button after the billing email field
	 * for guest users when inline verification is enabled.
	 *
	 * @since 1.0.0
	 * @param string $field Field HTML.
	 * @param string $key   Field key.
	 * @param array  $args  Field arguments.
	 * @param mixed  $value Field value.
	 * @return string Modified field HTML.
	 */
	public function cev_inject_otp_button_after_email( $field, $key, $args, $value ) {
		if ( 'billing_email' !== $key ) {
			return $field;
		}
		// For logged-in users, only skip if email is already verified.
		if ( is_user_logged_in()
			&& 'true' === get_user_meta( get_current_user_id(), 'customer_email_verified', true )
		) {
			return $field;
		}

		$button_text = __( 'Send Verification Code', 'customer-email-verification' );
		$button_text = apply_filters( 'cev_get_otp_button_text', $button_text );

		$button_html = '<button type="button" id="get_otp_btn" class="button get-otp-btn" style="margin-top:8px; display:inline-block;">' . esc_html( $button_text ) . '</button>';

		$field = str_replace( '</p>', $button_html . '</p>', $field );

		return $field;
	}

	/**
	 * Display inline verification on checkout.
	 *
	 * Adds inline verification fields and scripts to the checkout page
	 * when verification is needed for guest users.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cev_display_inline_verification_on_checkout() {
		// Early returns for various conditions.
		if ( ! is_checkout() && ! is_cart() ) {
			return;
		}

		$cev_enable_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
		if ( 1 != $cev_enable_email_verification_checkout ) {
			return;
		}

		$cev_inline_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
		if ( 1 === (int) $cev_inline_email_verification_checkout ) {
			return;
		}

		$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );
		$order_subtotal                            = WC()->cart->subtotal;

		if ( $order_subtotal > 0 && 1 === (int) $cev_enable_email_verification_free_orders ) {
			return;
		}

		// For logged-in users check user meta; for guests check session.
		if ( is_user_logged_in() ) {
			if ( 'true' === get_user_meta( get_current_user_id(), 'customer_email_verified', true ) ) {
				return; // Already verified — nothing to show.
			}
		} elseif ( $this->is_guest_email_verified() ) {
			return;
		}

		// Display inline verification fields if verification data exists.
		$this->render_inline_verification_fields();
	}

	/**
	 * Check if guest user's email is already verified.
	 *
	 * @since 1.0.0
	 * @return bool True if email is verified, false otherwise.
	 */
	private function is_guest_email_verified() {
		$billing_email = WC()->customer->get_billing_email();
		$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data' );
		$cev_user_verified_data     = ! is_null( $cev_user_verified_data_raw ) ? json_decode( $cev_user_verified_data_raw ) : null;

		if ( isset( $cev_user_verified_data->email ) ) {
			$verified_email = $cev_user_verified_data->email;
			if ( $verified_email === $billing_email && true === $cev_user_verified_data->verified ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render inline verification fields.
	 *
	 * Outputs JavaScript to inject verification fields into the checkout form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_inline_verification_fields() {
		$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
		$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;
		$billing_email                   = WC()->customer->get_billing_email();

		if ( ! isset( $cev_user_verification_data->email ) ) {
			return;
		}

		$session_email = $cev_user_verification_data->email;
		if ( $session_email !== $billing_email ) {
			return;
		}

		// Get resend limit information.
		$cev_redirect_limit_resend = cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );
		$cev_guest_user_resend_times = isset( $cev_user_verification_data->cev_guest_user_resend_times ) ? $cev_user_verification_data->cev_guest_user_resend_times : 0;
		$cev_resend_limit_reached = ( $cev_guest_user_resend_times >= $cev_redirect_limit_resend + 1 ) ? 'true' : 'false';

		$resend_class = ( 'true' === $cev_resend_limit_reached ) ? 'a_cev_resend_checkout' : 'resend_verification_code_inline_chekout_user';
		$wp_nonce     = wp_create_nonce( 'wc_cev_email_guest_user' );

		// Check if CheckoutWC plugin is active for different markup.
		$is_checkout_wc = is_plugin_active( 'checkout-for-woocommerce/checkout-for-woocommerce.php' );

		$this->output_inline_verification_script( $resend_class, $wp_nonce, $is_checkout_wc );
	}

	/**
	 * Output inline verification JavaScript.
	 *
	 * @since 1.0.0
	 * @param string $resend_class   CSS class for resend link.
	 * @param string $wp_nonce       WordPress nonce for AJAX.
	 * @param bool   $is_checkout_wc Whether CheckoutWC plugin is active.
	 * @return void
	 */
	private function output_inline_verification_script( $resend_class, $wp_nonce, $is_checkout_wc ) {
		$email_verification_code_label = __( 'Email verification code', 'customer-email-verification' );
		$verification_code_error_msg    = __( 'Verification code does not match', 'customer-email-verification' );
		$verify_button_text            = __( 'Verify', 'customer-email-verification' );
		$resend_verification_label     = __( 'Resend verification code', 'customer-email-verification' );

		?>
		<script>
		jQuery(document).ready(function() {
			setTimeout(function() {
				var resend_class = '<?php echo esc_js( $resend_class ); ?>';
				var wp_nonce = '<?php echo esc_js( $wp_nonce ); ?>';
				var email_verification_code_label = "<?php echo esc_js( $email_verification_code_label ); ?>";
				var verification_code_error_msg = "<?php echo esc_js( $verification_code_error_msg ); ?>";
				var verify_button_text = "<?php echo esc_js( $verify_button_text ); ?>";
				var resend_verification_label = "<?php echo esc_js( $resend_verification_label ); ?>";
				<?php if ( $is_checkout_wc ) : ?>
					jQuery( "#billing_email_field" ).parent('.cfw-input-wrap-row').after( "<div class='row cfw-input-wrap-row'><div class='col-lg-12'><div class='cfw-input-wrap cfw-text-input'><label class=''>"+email_verification_code_label+"</label> <input type='text' class='input-text garlic-auto-save cev_pro_chekout_input' name='cev_billing_email' placeholder='"+email_verification_code_label+"' aria-describedby='parsley-id-5' data-parsley-trigger='keyup change focusout'></input><span class='cev_verification__failure_code_checkout' style='display:none; color:red'>"+verification_code_error_msg+"</span> <div class='cev_pro_chekout_button'> <button type='button' class='woocommerce-Button button verify_account_email_chekout_page' name='verify_account_email' value='verify'>"+verify_button_text+"</button> <a href='javaScript:void(0);' class='"+resend_class+"' name='resend_verification_code' wp_nonce='"+wp_nonce+"'>"+resend_verification_label+"</a></div></div></div></div>" );
				<?php else : ?>
					jQuery( "#billing_email_field" ).after( "<div class='cev_pro_append'><label class='jquery_color_css'> "+email_verification_code_label+"</label> <input type='text' class='input-text cev_pro_chekout_input' name='cev_billing_email' style= 'margin-bottom: 0;'></input><span class='cev_verification__failure_code_checkout' style='display:none; color:red'>"+verification_code_error_msg+"</span> <div class='cev_pro_chekout_button'> <button type='button' class='woocommerce-Button button verify_account_email_chekout_page' name='verify_account_email' value='verify'>"+verify_button_text+"</button> <a href='javaScript:void(0);' class='"+resend_class+"' name='resend_verification_code' wp_nonce='"+wp_nonce+"'>"+resend_verification_label+"</a></div></div>" );
				<?php endif; ?>
			}, 500);
			jQuery(".cev_inline_code_sent").remove();
		});
		</script>
		<?php
		$this->output_back_button_reload_script();
	}

	/**
	 * Output script to reload page on back button.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function output_back_button_reload_script() {
		?>
		<script>
		if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
			location.reload();
		}
		</script>
		<?php
	}

}
