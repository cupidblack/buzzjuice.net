<?php
/**
 * Checkout Validation Handler for Customer Email Verification
 *
 * Handles checkout validation to ensure email verification is completed
 * before allowing order placement.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Checkout_Validation Class
 *
 * Manages checkout validation for email verification.
 */
class CEV_Checkout_Validation {

	/**
	 * Checkout verification instance.
	 *
	 * @var CEV_Checkout_Verification
	 */
	private $checkout_verification;

	/**
	 * Initialize the validation handler.
	 *
	 * @since 1.0.0
	 * @param CEV_Checkout_Verification $checkout_verification Checkout verification instance.
	 */
	public function __construct( CEV_Checkout_Verification $checkout_verification ) {
		$this->checkout_verification = $checkout_verification;
	}

	/**
	 * Check email verification on checkout process.
	 *
	 * Validates that guest users have verified their email before allowing
	 * order placement. Adds validation error if email is not verified.
	 *
	 * @since 1.0.0
	 * @param array    $fields Checkout fields.
	 * @param WP_Error $errors Validation errors.
	 * @return void
	 */
	public function after_checkout_validation( $fields, $errors ) {
		if ( '1' !== cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout' ) ) {
			return;
		}

		// --- Logged-in user path ---
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			// Already verified via permanent user meta — allow checkout.
			if ( 'true' === get_user_meta( $user_id, 'customer_email_verified', true ) ) {
				return;
			}

			// Respect free orders exemption.
			$free_orders_exempt = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );
			$cart_snapshot_li   = WC()->session->get( 'cev_cart_total_at_otp_send' );
			$cart_subtotal_li   = ! is_null( $cart_snapshot_li ) ? (float) $cart_snapshot_li : WC()->cart->subtotal;
			if ( 1 === (int) $free_orders_exempt && $cart_subtotal_li > 0 ) {
				return;
			}

			// Accept session-based verification (set during this checkout session via inline/popup AJAX).
			$billing_email = isset( $fields['billing_email'] ) ? $fields['billing_email'] : '';
			if ( ! empty( $billing_email ) && $this->is_email_verified_in_session( $billing_email ) ) {
				return;
			}

			// Block checkout — user has not verified their email.
			$errors->add( 'validation', __( 'Please verify your email address.', 'customer-email-verification' ) );
			echo '<input type="hidden" name="validation_error" value="1">';
			return;
		}

		// --- Guest user path ---
		// Check if verification is needed based on order total.
		if ( ! $this->needs_verification_for_order( $fields ) ) {
			return;
		}

		// Verify email is verified in session.
		if ( ! $this->is_email_verified_in_session( $fields['billing_email'] ) ) {
			$message = __( 'Please verify your email address.', 'customer-email-verification' );
			$errors->add( 'validation', $message );
			echo '<input type="hidden" name="validation_error" value="1">';
		}
	}

	/**
	 * Determine if verification is needed for this order.
	 *
	 * @since 1.0.0
	 * @param array $fields Checkout fields.
	 * @return bool True if verification is needed, false otherwise.
	 */
	private function needs_verification_for_order( $fields ) {
		$require_create_account_setting = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_create_an_account_during_checkout', 0 );
		$guest_checkout_enabled        = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );

		if ( $require_create_account_setting && $guest_checkout_enabled ) {
			$create_account_checked = false;

			if ( isset( $fields['createaccount'] ) ) {
				$create_account_checked = wc_string_to_bool( $fields['createaccount'] );
			} elseif ( isset( $_POST['createaccount'] ) ) {
				// Verify nonce for checkout form submission.
				if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
					$create_account_checked = wc_string_to_bool( wc_clean( wp_unslash( $_POST['createaccount'] ) ) );
				}
			}

			if ( ! $create_account_checked ) {
				return false;
			}
		}

		$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );

		// Use snapshotted cart total (captured when OTP was sent) to prevent cart manipulation bypass.
		// Falls back to live cart if no snapshot exists (e.g. non-inline flows).
		$cart_snapshot  = WC()->session->get( 'cev_cart_total_at_otp_send' );
		$order_subtotal = ! is_null( $cart_snapshot ) ? (float) $cart_snapshot : WC()->cart->subtotal;

		// If free orders are exempt and order has value, no verification needed.
		if ( 1 === (int) $cev_enable_email_verification_free_orders && $order_subtotal > 0 ) {
			return false;
		}

		// If order is free and free orders are exempt, verification is needed.
		if ( 0 === (int) $order_subtotal && 1 === (int) $cev_enable_email_verification_free_orders ) {
			return true;
		}

		// If free orders are not exempt, verification is always needed.
		if ( 1 !== (int) $cev_enable_email_verification_free_orders ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if email is verified in session.
	 *
	 * @since 1.0.0
	 * @param string $billing_email Billing email from checkout.
	 * @return bool True if email is verified, false otherwise.
	 */
	private function is_email_verified_in_session( $billing_email ) {
		$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data' );
		$cev_user_verified_data      = ! is_null( $cev_user_verified_data_raw ) ? json_decode( $cev_user_verified_data_raw ) : null;

		if ( ! isset( $cev_user_verified_data->email ) || ! isset( $cev_user_verified_data->verified ) ) {
			return false;
		}

		return ( $cev_user_verified_data->email === $billing_email && 1 === (int) $cev_user_verified_data->verified );
	}
}
