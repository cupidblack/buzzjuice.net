<?php
/**
 * Admin: Payouts Tab
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Payouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets up the Payouts tab.
 *
 * @since 2.29.0
 */
class AffiliateWP_Admin_Payouts_Tab {

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Include the payment methods registry early.
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/class-payment-methods.php';

		// Hook to display our custom content.
		add_action( 'affwp_settings_tab_payouts_content', [ $this, 'render_payouts_content' ] );

		// Add sanitization filter for payouts tab.
		add_filter( 'affwp_settings_payouts_sanitize', [ $this, 'sanitize_payouts_settings' ] );
	}

	/**
	 * Render the custom Payouts tab content.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_payouts_content() {
		// Register all payment methods (idempotent - safe to call multiple times).
		$this->register_payment_methods();

		// Include our custom view.
		include AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/views/payouts-tab.php';
	}

	/**
	 * Register all payment methods.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	private function register_payment_methods() {

		// Stripe Payouts.
		$stripe_status = $this->get_stripe_status();
		$test_mode     = affiliate_wp()->settings->get( 'stripe_test_mode', false );

		$stripe_config = [
			'name'              => __( 'Stripe Payouts', 'affiliate-wp' ),
			'description'       => __( 'Pay affiliates directly to their bank accounts via Stripe Connect', 'affiliate-wp' ),
			'icon'              => AFFILIATEWP_PLUGIN_URL . 'assets/images/payouts/stripe-icon.svg',
			'status'            => $stripe_status,
			'type'              => 'core',
			'settings_callback' => [ $this, 'render_stripe_settings' ],
		];

		// Add custom status labels.
		if ( 'active' === $stripe_status ) {
			$stripe_config['status_label'] = $test_mode ? __( 'Sandbox Mode', 'affiliate-wp' ) : __( 'Live Mode', 'affiliate-wp' );
		} elseif ( 'setup_required' === $stripe_status ) {
			$stripe_config['status_label'] = $test_mode
				? __( 'Setup Required (Sandbox)', 'affiliate-wp' )
				: __( 'Setup Required (Live)', 'affiliate-wp' );
		}

		AffiliateWP_Payment_Methods::register( 'stripe', $stripe_config );

		// PayPal Payouts.
		$paypal_status    = $this->get_paypal_status();
		$paypal_instance  = \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts::instance();
		$paypal_test_mode = $paypal_instance->is_test_mode();

		$paypal_config = [
			'name'              => __( 'PayPal Payouts', 'affiliate-wp' ),
			'description'       => __( 'Send instant payments to affiliates via PayPal', 'affiliate-wp' ),
			'icon'              => 'payout-method-paypal-payouts',
			'status'            => $paypal_status,
			'type'              => 'core',
			'settings_callback' => [ $this, 'render_paypal_settings' ],
			'has_new_settings'  => true,
		];

		// Add custom status labels only when enabled.
		if ( 'active' === $paypal_status ) {
			$paypal_config['status_label'] = $paypal_test_mode ? __( 'Sandbox Mode', 'affiliate-wp' ) : __( 'Live Mode', 'affiliate-wp' );
		} elseif ( 'setup_required' === $paypal_status ) {
			$paypal_config['status_label'] = $paypal_test_mode
				? __( 'Setup Required (Sandbox)', 'affiliate-wp' )
				: __( 'Setup Required (Live)', 'affiliate-wp' );
		}
		// When status is 'available' (disabled but has creds), no custom label - no badge shows.

		AffiliateWP_Payment_Methods::register( 'paypal', $paypal_config );

		// Store Credit - Check if it needs to be registered.
		if ( ! AffiliateWP_Payment_Methods::exists( 'store_credit' ) ) {

			// Store Credit.
			AffiliateWP_Payment_Methods::register(
				'store_credit',
				[
					'name'              => __( 'Store Credit', 'affiliate-wp' ),
					'description'       => __( 'Pay affiliates with store credit for your shop', 'affiliate-wp' ),
					'icon'              => 'payout-method-store-credit',
					'status'            => $this->get_store_credit_status(),
					'type'              => 'core',
					'settings_callback' => [ $this, 'render_store_credit_settings' ],
				]
			);

		}

		// Payouts Service.
		$payouts_connection_status = affiliate_wp()->settings->get( 'payouts_service_connection_status', '' );
		$payouts_enabled           = affiliate_wp()->settings->get( 'enable_payouts_service', false );

		// Only show the Payouts Service if connected (grandfathering existing users).
		if ( 'active' === $payouts_connection_status ) {

			$is_payouts_sunset_passed = affwp_is_payouts_service_sunset_passed();

			// Determine status based on both connection and enabled state.
			$payouts_config = [
				'name'              => __( 'Payouts Service', 'affiliate-wp' ),
				'description'       => __( 'Pay your affiliates directly from a credit or debit card', 'affiliate-wp' ),
				'icon'              => 'payout-method-payouts-service',
				'type'              => 'core',
				'settings_callback' => [ $this, 'render_payouts_service_settings' ],
				'has_new_settings'  => true,
			];

			if ( $is_payouts_sunset_passed ) {
				// Post-shutdown: show as retired.
				$payouts_config['status']       = 'retired';
				$payouts_config['status_label'] = __( 'Retired', 'affiliate-wp' );
			} elseif ( $payouts_enabled ) {
				$payouts_config['status']       = 'active';
				$payouts_config['status_label'] = __( 'Connected', 'affiliate-wp' );
			} else {
				$payouts_config['status']       = 'available';
				$payouts_config['status_label'] = __( 'Ready', 'affiliate-wp' );
			}

			AffiliateWP_Payment_Methods::register( 'payouts_service', $payouts_config );
		}

		/**
		 * Fires after payment methods are registered.
		 *
		 * @since 2.29.0
		 */
		do_action( 'affwp_register_payment_methods' );
	}

	/**
	 * Get payment method status for providers with test/live modes.
	 *
	 * Used for Stripe and PayPal which share the same status logic.
	 *
	 * @since 2.29.0
	 *
	 * @param string $method_key  The method key (e.g., 'stripe_payouts', 'paypal_payouts').
	 * @param array  $credentials Array with 'test' and 'live' boolean values for credentials.
	 * @param bool   $test_mode   Whether test mode is enabled.
	 * @return string|null Status or null if no status should be shown.
	 */
	private function get_payment_provider_status( $method_key, $credentials, $test_mode ) {
		$is_enabled = affiliate_wp()->settings->get( $method_key, false );

		if ( ! $is_enabled ) {
			// Return 'available' so the toggle still shows but no badge is displayed.
			return 'available';
		}

		$has_test_creds = $credentials['test'];
		$has_live_creds = $credentials['live'];

		// Check credentials for current mode.
		$has_required_creds = $test_mode ? $has_test_creds : $has_live_creds;

		if ( $has_required_creds ) {
			return 'active';
		}

		// Enabled but missing credentials.
		return 'setup_required';
	}

	/**
	 * Get Stripe status.
	 *
	 * @since 2.29.0
	 *
	 * @return string|null Status or null if no status should be shown.
	 */
	private function get_stripe_status() {
		$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );

		$credentials = [
			'test' => ! empty( affiliate_wp()->settings->get( 'stripe_test_secret_key' ) ),
			'live' => ! empty( affiliate_wp()->settings->get( 'stripe_live_secret_key' ) ),
		];

		return $this->get_payment_provider_status( 'stripe_payouts', $credentials, $test_mode );
	}

	/**
	 * Get PayPal status.
	 *
	 * @since 2.29.0
	 *
	 * @return string|null Status or null if no status should be shown.
	 */
	private function get_paypal_status() {
		// Initialize PayPal instance to check credentials.
		$paypal_instance = \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts::instance();
		$test_mode       = $paypal_instance->is_test_mode();
		$payout_mode     = affiliate_wp()->settings->get( 'paypal_payout_mode', 'masspay' );

		// Determine which credentials we have.
		if ( 'api' == $payout_mode ) {
			$credentials = [
				'test' => ! empty( affiliate_wp()->settings->get( 'paypal_test_client_id' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_test_secret' ) ),
				'live' => ! empty( affiliate_wp()->settings->get( 'paypal_live_client_id' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_live_secret' ) ),
			];
		} else {
			$credentials = [
				'test' => ! empty( affiliate_wp()->settings->get( 'paypal_test_username' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_test_password' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_test_signature' ) ),
				'live' => ! empty( affiliate_wp()->settings->get( 'paypal_live_username' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_live_password' ) )
						&& ! empty( affiliate_wp()->settings->get( 'paypal_live_signature' ) ),
			];
		}

		return $this->get_payment_provider_status( 'paypal_payouts', $credentials, $test_mode );
	}

	/**
	 * Get Store Credit status.
	 *
	 * @since 2.29.0
	 *
	 * @return string Status.
	 */
	private function get_store_credit_status() {
		// Check if plugins are installed.
		$woocommerce_installed = class_exists( 'WooCommerce' );
		$edd_installed         = class_exists( 'Easy_Digital_Downloads' );

		// Check if integrations are enabled.
		$enabled_integrations = array_keys( affiliate_wp()->integrations->get_enabled_integrations() );
		$woocommerce_enabled  = in_array( 'woocommerce', $enabled_integrations );
		$edd_enabled          = in_array( 'edd', $enabled_integrations );

		// Check if we have any integration enabled.
		$has_integration = ( $woocommerce_installed && $woocommerce_enabled ) || ( $edd_installed && $edd_enabled );

		// If plugins are installed but integration not enabled, show "Setup Required".
		if ( ( $woocommerce_installed || $edd_installed ) && ! $has_integration ) {
			return 'setup_required'; // This will show "Setup Required" badge.
		}

		// If no plugins installed at all.
		if ( ! $woocommerce_installed && ! $edd_installed ) {
			return 'available'; // Can be installed.
		}

		// If integration is enabled, check if Store Credit itself is enabled.
		if ( $has_integration ) {
			return affiliate_wp()->settings->get( 'store-credit' ) ? 'active' : 'available';
		}

		return 'available';
	}

	/**
	 * Sanitize payouts settings.
	 *
	 * @since 2.29.0
	 *
	 * @param array $input The input settings array.
	 * @return array The sanitized settings.
	 */
	public function sanitize_payouts_settings( $input ) {
		// Define checkbox fields that need to be sanitized.
		$checkbox_fields = [
			'store-credit',
			'store-credit-all-affiliates',
			'store-credit-change-payment-method',
			'store-credit-woocommerce-subscriptions',
			'manual_payouts',
			'enable_payouts_service',
			'paypal_payouts',
			'stripe_payouts',
			'stripe_test_mode',
			'stripe_cross_border_payouts',
		];

		// Ensure checkbox values are boolean
		foreach ( $checkbox_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				// Convert to boolean: '1' or 1 becomes true, everything else false
				$input[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
			} else {
				// If not set, it means checkbox was unchecked
				$input[ $field ] = 0;
			}
		}

		// Sanitize Stripe text fields
		$stripe_text_fields = [
			'stripe_test_secret_key',
			'stripe_live_secret_key',
			'stripe_webhook_secret',
		];

		foreach ( $stripe_text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$input[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		return $input;
	}

	/**
	 * Render Manual Payouts settings.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_manual_settings() {
		?>
		<div class="overflow-hidden">
			<div class="space-y-4">
			<!-- Hidden field to handle the checkbox value from toggle switch -->
			<input type="hidden"
					id="affwp_settings_manual_payouts"
					name="affwp_settings[manual_payouts]"
					value="<?php echo affiliate_wp()->settings->get( 'manual_payouts' ) ? '1' : ''; ?>">

			<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-blue-800">
							<?php esc_html_e( 'Manual Payouts Configuration', 'affiliate-wp' ); ?>
						</h3>
						<p class="mt-2 text-blue-700">
							<?php esc_html_e( 'Allow marking referrals as paid manually. Useful for payments made via bank transfer, check, or other offline methods. Use the toggle switch above to enable or disable this payment method.', 'affiliate-wp' ); ?>
						</p>
					</div>
				</div>
			</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Payouts Service settings.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_payouts_service_settings() {
		// Get current settings
		$description       = affiliate_wp()->settings->get( 'payouts_service_description', '' );
		$notice            = affiliate_wp()->settings->get( 'payouts_service_notice', '' );
		$connection_status = affiliate_wp()->settings->get( 'payouts_service_connection_status', '' );

		// Non-connected sites: show "no longer accepting new connections" message and return early.
		if ( 'active' !== $connection_status ) {
			$is_past_sunset = affwp_is_payouts_service_sunset_passed();
			?>
			<div class="max-w-3xl">
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
					<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
					</svg>
					<h3 class="mt-4 text-base font-semibold text-gray-900">
						<?php
						if ( $is_past_sunset ) {
							esc_html_e( 'Payouts Service Has Been Shut Down', 'affiliate-wp' );
						} else {
							esc_html_e( 'Payouts Service Unavailable', 'affiliate-wp' );
						}
						?>
					</h3>
					<p class="mt-2 text-sm text-gray-600">
						<?php
						if ( $is_past_sunset ) {
							esc_html_e( 'The Payouts Service was shut down on September 30, 2026. Please configure another payout method above.', 'affiliate-wp' );
						} else {
							esc_html_e( 'The Payouts Service is no longer accepting new connections and will be shut down on September 30, 2026. Please configure another payout method above.', 'affiliate-wp' );
						}
						?>
					</p>
					<p class="mt-3">
						<?php
						affwp_link(
							[
								'text'     => __( 'Learn more', 'affiliate-wp' ),
								'href'     => affwp_utm_link( AFFWP_PAYOUTS_SERVICE_SUNSET_URL, 'payouts-service-sunset', 'disabled-registration' ),
								'external' => true,
								'variant'  => 'primary',
							]
						);
						?>
					</p>
				</div>
			</div>
			<?php
			return;
		}

		// Build connection status with button styling
		$connection_button_html = '';
		if ( 'active' === $connection_status ) {
			$payouts_service_disconnect_url = wp_nonce_url( add_query_arg( [ 'affwp_action' => 'payouts_service_disconnect' ] ), 'payouts_service_disconnect', 'payouts_service_disconnect_nonce' );
			$connection_button_html         = '<div class="space-y-3">';
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= '<p class="text-sm text-green-700 font-medium flex items-center gap-2">';
			$connection_button_html .= '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
			$connection_button_html .= sprintf( __( 'Your website is connected to the %s', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME );
			$connection_button_html .= '</p>';
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= affwp_render_button(
				[
					'text'    => sprintf( __( 'Disconnect from %s', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
					'variant' => 'secondary',
					'href'    => $payouts_service_disconnect_url,
				]
			);
			$connection_button_html .= '</div>';
		} elseif ( 'inactive' === $connection_status ) {
			$payouts_service_reconnect_url = wp_nonce_url( add_query_arg( [ 'affwp_action' => 'payouts_service_reconnect' ] ), 'payouts_service_reconnect', 'payouts_service_reconnect_nonce' );
			$connection_button_html        = '<div class="space-y-3">';
			$connection_button_html       .= '<p class="text-sm text-orange-700 font-medium flex items-center gap-2">';
			$connection_button_html       .= '<svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
			$connection_button_html       .= __( 'Connection inactive', 'affiliate-wp' );
			$connection_button_html       .= '</p>';
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= affwp_render_button(
				[
					'text'    => sprintf( __( 'Reconnect to %s', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
					'variant' => 'primary',
					'href'    => $payouts_service_reconnect_url,
				]
			);
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= '<p class="text-sm text-gray-500">';
			$connection_button_html .= sprintf( __( 'Have questions about connecting with the %s? See the ', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME );
			$connection_button_html .= affwp_render_link(
				[
					'text'     => __( 'documentation', 'affiliate-wp' ),
					'href'     => PAYOUTS_SERVICE_DOCS_URL,
					'external' => true,
					'variant'  => 'inline',
				]
			);
			$connection_button_html .= '.</p>';
			$connection_button_html .= '</div>';
		} else {
			$payouts_service_connect_url = add_query_arg(
				[
					'affwp_version' => AFFILIATEWP_VERSION,
					'site_url'      => home_url(),
					'redirect_url'  => urlencode( affwp_admin_url( 'settings', [ 'tab' => 'payouts' ] ) ),
					'token'         => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 100, wp_rand(), STR_PAD_BOTH ),
				],
				PAYOUTS_SERVICE_URL . '/connect-site'
			);
			$connection_button_html      = '<div class="space-y-3">';
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= affwp_render_button(
				[
					'text'    => sprintf( __( 'Connect to %s', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
					'variant' => 'primary',
					'size'    => 'md',
					'href'    => $payouts_service_connect_url,
				]
			);
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			$connection_button_html .= '<p class="text-sm text-gray-500">';
			$connection_button_html .= sprintf( __( 'Have questions about connecting with the %s? See the ', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME );
			$connection_button_html .= affwp_render_link(
				[
					'text'     => __( 'documentation', 'affiliate-wp' ),
					'href'     => PAYOUTS_SERVICE_DOCS_URL,
					'external' => true,
					'variant'  => 'inline',
				]
			);
			$connection_button_html .= '.</p>';
			$connection_button_html .= '</div>';
		}
		?>
		<div class="max-w-3xl">

			<!-- Header Section -->
			<div class="mb-8">
				<h3 class="text-xl font-semibold text-gray-900 mb-2"><?php _e( 'Payouts Service Configuration', 'affiliate-wp' ); ?></h3>

				<!-- Payouts Service Sunset Warning -->
				<div class="bg-yellow-50 border border-amber-200 rounded-lg p-4 mt-3 mb-4">
					<div class="flex">
						<div class="flex-shrink-0">
							<svg class="h-5 w-5 text-yellow-700" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
							</svg>
						</div>
						<div class="ml-3">
							<p class="text-sm font-medium text-yellow-700">
								<?php
								if ( affwp_is_payouts_service_sunset_passed() ) {
									esc_html_e( 'The Payouts Service was shut down on September 30, 2026.', 'affiliate-wp' );
								} else {
									esc_html_e( 'The Payouts Service is shutting down on September 30, 2026.', 'affiliate-wp' );
								}
								?>
							</p>
							<p class="mt-1 text-sm text-yellow-700">
								<?php
								if ( affwp_is_payouts_service_sunset_passed() ) {
									esc_html_e( 'Please switch to another payout method to continue paying your affiliates. Your existing data will not be affected.', 'affiliate-wp' );
								} else {
									esc_html_e( 'Please switch to another payout method before then. Your existing data will not be affected.', 'affiliate-wp' );
								}
								echo ' ';
								affwp_link(
									[
										'text'     => __( 'Learn more', 'affiliate-wp' ),
										'href'     => affwp_utm_link( AFFWP_PAYOUTS_SERVICE_SUNSET_URL, 'payouts-service-sunset', 'config-panel-warning' ),
										'external' => true,
										'variant'  => 'inline',
									]
								);
								?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Main Settings -->
			<div class="bg-white rounded-lg border border-gray-200">

				<!-- Connection Status -->
				<div class="border-b border-gray-200 last:border-b-0">
					<div class="p-6">
						<h4 class="text-base font-medium text-gray-900 mb-3"><?php _e( 'Connection Status', 'affiliate-wp' ); ?></h4>
						<?php echo $connection_button_html; // Already escaped by affwp_render_button() and affwp_render_link(). ?>
					</div>
				</div>

				<!-- Registration Form Description -->
				<div class="border-b border-gray-200 last:border-b-0">
					<div class="p-6">
						<label for="affwp_settings_payouts_service_description" class="block text-base font-medium text-gray-900">
							<?php _e( 'Registration Form Description', 'affiliate-wp' ); ?>
						</label>
						<div class="mt-2">
							<textarea id="affwp_settings_payouts_service_description"
										name="affwp_settings[payouts_service_description]"
										rows="4"
										class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"
										placeholder="<?php esc_attr_e( 'Explain to affiliates how to register for the Payouts Service', 'affiliate-wp' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
							<p class="mt-2 text-sm text-gray-500">
								<?php _e( 'This will be displayed above the Payouts Service registration form fields. Here you can explain to your affiliates how/why to register for the Payouts Service.', 'affiliate-wp' ); ?>
							</p>
						</div>
					</div>
				</div>

				<!-- Payouts Service Notice -->
				<div class="border-b border-gray-200 last:border-b-0">
					<div class="p-6">
						<label for="affwp_settings_payouts_service_notice" class="block text-base font-medium text-gray-900">
							<?php _e( 'Payouts Service Notice', 'affiliate-wp' ); ?>
						</label>
						<div class="mt-2">
							<textarea id="affwp_settings_payouts_service_notice"
										name="affwp_settings[payouts_service_notice]"
										rows="4"
										class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"
										placeholder="<?php esc_attr_e( 'Enter a notice for affiliates who have not registered their payout account', 'affiliate-wp' ); ?>"><?php echo esc_textarea( $notice ); ?></textarea>
							<p class="mt-2 text-sm text-gray-500">
								<?php _e( 'This will be displayed at the top of each tab of the Affiliate Area for affiliates that have not registered their payout account.', 'affiliate-wp' ); ?>
							</p>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render Stripe settings.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_stripe_settings() {
		// Include the Stripe Payouts settings panel template
		$template_path = AFFILIATEWP_PLUGIN_DIR . 'includes/payouts/methods/stripe-payouts/templates/settings-panel.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback if template doesn't exist
			?>
			<div class="overflow-hidden">
				<div class="space-y-6">
				<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
					<div class="flex">
						<div class="flex-shrink-0">
							<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
							</svg>
						</div>
						<div class="ml-3">
							<h3 class="text-sm font-medium text-yellow-800">
								<?php esc_html_e( 'Configuration Error', 'affiliate-wp' ); ?>
							</h3>
							<p class="mt-2 text-sm text-yellow-700">
								<?php esc_html_e( 'The Stripe Payouts settings template could not be loaded. Please check your installation.', 'affiliate-wp' ); ?>
							</p>
						</div>
					</div>
				</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render PayPal settings.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_paypal_settings() {
		// Initialize PayPal instance and call its render method.
		$paypal_instance = \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts::instance();
		if ( $paypal_instance && method_exists( $paypal_instance, 'render_settings_panel' ) ) {
			$paypal_instance->render_settings_panel();
		}
	}

	/**
	 * Render Store Credit settings.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function render_store_credit_settings() {
		// Include the Store Credit settings panel template
		$template_path = AFFILIATEWP_PLUGIN_DIR . 'includes/payouts/methods/store-credit/templates/settings-panel.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

// Initialize the class.
new AffiliateWP_Admin_Payouts_Tab();
