<?php
/**
 * PayPal Payouts Admin Class
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/PayPalPayouts
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

class AffiliateWP_PayPal_Payouts_Admin {

	/**
	 * API instance
	 */
	private $api;

	/**
	 * Get things started
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function __construct() {

		// Initialize API based on mode
		$mode = affiliate_wp()->settings->get( 'paypal_payout_mode', 'masspay' );

		switch ( $mode ) {
			case 'masspay':
				$this->api = new \AffiliateWP_PayPal_MassPay();
				break;

			case 'api':
			default:
				$this->api = new \AffiliateWP_PayPal_API();
				break;
		}

		$this->api->credentials = affiliate_wp_paypal()->get_api_credentials();

		// Settings registration.
		$version = get_option( 'affwp_version' );

		// Keeps compatibility with older versions of AffiliateWP.
		if ( version_compare( $version, '2.18.0', '<' ) ) {
			add_filter( 'affwp_settings_tabs', [ $this, 'setting_tab' ] );
			add_filter( 'affwp_settings', [ $this, 'register_settings_legacy' ] );
		} else {
			// Use the new section system.
			add_filter( 'affwp_settings_commissions', [ $this, 'register_settings' ] );
			add_action( 'affiliatewp_after_register_admin_sections', [ $this, 'register_section' ] );
		}

		// Payouts screen functionality.
		if ( affiliate_wp_paypal()->has_2_4() ) {
			add_action( 'affwp_preview_payout_note_paypal', [ $this, 'preview_payout_note' ] );
			add_filter( 'affwp_preview_payout_data_paypal', [ $this, 'preview_payout_data' ] );
			add_filter( 'affwp_preview_payout_invalid_affiliates_paypal', [ $this, 'preview_payout_invalid_affiliates' ], 10, 2 );
			add_action( 'affwp_process_payout_paypal', [ $this, 'process_bulk_paypal_payout' ], 10, 6 );
		}

		// Referrals admin screen functionality.
		if ( $this->should_display_pay_now_links() ) {
			add_filter( 'affwp_referral_row_actions', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'affwp_referrals_bulk_actions', [ $this, 'bulk_actions' ], 10, 2 );

			add_action( 'affwp_pay_now', [ $this, 'process_pay_now' ] );
			add_action( 'affwp_referrals_do_bulk_action_pay_now', [ $this, 'process_bulk_action_pay_now' ] );
		}

		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		if ( ! affiliate_wp_paypal()->has_2_4() ) {
			add_action( 'affwp_referrals_page_buttons', [ $this, 'bulk_pay_form' ] );
			add_action( 'affwp_process_bulk_paypal_payout', [ $this, 'process_bulk_paypal_payout_legacy' ] );
		}
	}

	/**
	 * Register the new settings tab
	 *
	 * @since 2.29.0
	 *
	 * @param array $tabs The array of tabs.
	 *
	 * @return array
	 */
	public function setting_tab( array $tabs ) : array {
		$tabs['paypal'] = __( 'PayPal Payouts', 'affiliate-wp' );
		return $tabs;
	}

	/**
	 * Return the list of settings.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	private function get_settings() : array {

		return [
			'paypal_payout_mode'    => [
				'name'    => __( 'Payout API to Use', 'affiliate-wp' ),
				'desc'    => __( 'Select the payout method you wish to use. PayPal MassPay is an older technology not available to all accounts. See <a href="https://affiliatewp.com/docs/paypal-payouts-installation-and-usage/" target="_blank" rel="noopener noreferrer">documentation</a> for assistance.', 'affiliate-wp' ),
				'type'    => 'select',
				'options' => [
					'api'     => __( 'API Application', 'affiliate-wp' ),
					'masspay' => __( 'MassPay', 'affiliate-wp' ),
				],
			],
			'paypal_test_mode'      => [
				'name' => __( 'Test Mode', 'affiliate-wp' ),
				'desc' => __( 'Check this box if you would like to use PayPal Payouts in Test Mode', 'affiliate-wp' ),
				'type' => 'checkbox',
			],
			'paypal_api_header'     => [
				'name' => __( 'PayPal API Application Credentials', 'affiliate-wp' ),
				'desc' => __( 'Enter your PayPal API Application credentials.', 'affiliate-wp' ),
				'type' => 'header',
			],
			'paypal_live_client_id' => [
				'name' => __( 'Client ID', 'affiliate-wp' ),
				'desc' => __( 'Enter your PayPal Application\'s Client ID. Create or retrieve these from <a href="https://developer.paypal.com/home/" target="_blank">PayPal\'s Developer portal</a>.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_live_secret'    => [
				'name' => __( 'Secret', 'affiliate-wp' ),
				'desc' => __( 'Enter your PayPal Application\'s Secret.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_test_client_id' => [
				'name' => __( 'Test Client ID', 'affiliate-wp' ),
				'desc' => __( 'Enter your Sandbox PayPal Application\'s Client ID.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_test_secret'    => [
				'name' => __( 'Test Secret', 'affiliate-wp' ),
				'desc' => __( 'Enter your Sandbox PayPal Application\'s Secret.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_masspay_header' => [
				'name' => __( 'PayPal MassPay Credentials', 'affiliate-wp' ),
				'desc' => __( 'Enter your Test API Username.', 'affiliate-wp' ),
				'type' => 'header',
			],
			'paypal_test_username'  => [
				'name' => __( 'Test API Username', 'affiliate-wp' ),
				'desc' => __( 'Enter your Test API Username.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_test_password'  => [
				'name' => __( 'Test API Password', 'affiliate-wp' ),
				'desc' => __( 'Enter your Test API Password.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_test_signature' => [
				'name' => __( 'Test API Signature', 'affiliate-wp' ),
				'desc' => __( 'Enter your Test API Signature.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_live_username'  => [
				'name' => __( 'Live API Username', 'affiliate-wp' ),
				'desc' => __( 'Enter your Live API Username.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_live_password'  => [
				'name' => __( 'Live API Password', 'affiliate-wp' ),
				'desc' => __( 'Enter your Live API Password.', 'affiliate-wp' ),
				'type' => 'text',
			],
			'paypal_live_signature' => [
				'name' => __( 'Live API Signature', 'affiliate-wp' ),
				'desc' => __( 'Enter your Live API Signature.', 'affiliate-wp' ),
				'type' => 'text',
			],
		];
	}

	/**
	 * Register the settings for our PayPal Payouts tab.
	 *
	 * @since 2.29.0
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return array
	 */
	public function register_settings( array $settings ) : array {

		return array_merge_recursive(
			$settings,
			$this->get_settings()
		);
	}

	/**
	 * Register the settings for our PayPal Payouts tab for older AffiliateWP versions.
	 *
	 * @since 2.29.0
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return array
	 */
	public function register_settings_legacy( array $settings ) : array {

		$settings['paypal'] = $this->get_settings();

		return $settings;
	}

	/**
	 * Register the settings section.
	 *
	 * @since 2.29.0
	 */
	public function register_section() {

		if ( ! method_exists( affiliate_wp()->settings, 'register_section' ) ) {
			return; // It is an old AffiliateWP and do not have support for sections.
		}

		affiliate_wp()->settings->register_section(
			'commissions',
			'paypal_payouts',
			__( 'PayPal Payouts Payment Method', 'affiliate-wp' ),
			apply_filters(
				'affiliatewp_register_section_paypal_payouts',
				[
					'paypal_payout_mode',
					'paypal_test_mode',
					'paypal_api_header',
					'paypal_live_client_id',
					'paypal_live_secret',
					'paypal_test_client_id',
					'paypal_test_secret',
					'paypal_masspay_header',
					'paypal_test_username',
					'paypal_test_password',
					'paypal_test_signature',
					'paypal_live_username',
					'paypal_live_password',
					'paypal_live_signature',
				]
			),
			'',
			[
				'required_field' => 'paypal_payouts',
				'value'          => true,
			],
		);
	}



	/**
	 * Add a note to the Payout preview page for a PayPal payout.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function preview_payout_note() {
		?>
		<h2><?php esc_html_e( 'Note', 'affiliate-wp' ); ?></h2>
		<p><?php esc_html_e( 'If you receive a "denied" receipt from PayPal after processing a payout, the affiliate&#8217;s account may be suspended, cannot receive your site&#8217;s currency, or cannot receive payments from your country.', 'affiliate-wp' ); ?></p>
		<p><?php esc_html_e( 'If the affiliate does not have a PayPal account they will receive a PayPal invitation to create an account. If the affiliate does not accept the invitation, the funds will be returned to your PayPal account.', 'affiliate-wp' ); ?></p>
		<p><?php esc_html_e( 'You must have a sufficient balance already present in your PayPal account to cover the payouts being processed.', 'affiliate-wp' ); ?></p>
		<?php
	}

	/**
	 * Displays the list of valid affiliates that can be paid out via PayPal.
	 *
	 * @since 2.29.0
	 *
	 * @param array $data Payout data.
	 * @return array (Maybe) filtered payout data.
	 */
	public function preview_payout_data( $data ) {

		foreach ( $data as $affiliate_id => $payout_data ) {

			$affiliate = affwp_get_affiliate( $affiliate_id );

			if ( ! $affiliate->user ) {
				unset( $data[ $affiliate_id ] );
			}
		}

		return $data;
	}

	/**
	 * Filter out the list of invalid affiliates on the payout preview page.
	 *
	 * @since 2.29.0
	 *
	 * @param array $invalid_affiliates Invalid affiliates.
	 * @param array $data               Payout data.
	 * @return array Modified array of invalid affiliates.
	 */
	public function preview_payout_invalid_affiliates( $invalid_affiliates, $data ) {

		foreach ( $data as $affiliate_id => $payout_data ) {

			$affiliate = affwp_get_affiliate( $affiliate_id );

			if ( ! $affiliate->user ) {
				$invalid_affiliates[ $affiliate_id ] = 'user_account_deleted';
			}
		}

		return $invalid_affiliates;
	}

	/**
	 * Payout referrals in bulk for a specified timeframe via PayPal.
	 *
	 * @since 2.29.0
	 *
	 * @param string $start         Referrals start date.
	 * @param string $end           Referrals end date data.
	 * @param float  $minimum       Minimum payout.
	 * @param int    $affiliate_id  Affiliate ID.
	 * @param string $payout_method Payout method.
	 * @param bool   $bypass_holding Bypass holding period.
	 *
	 * @return void
	 */
	public function process_bulk_paypal_payout( $start, $end, $minimum, $affiliate_id, $payout_method, $bypass_holding ) {

		if ( ! current_user_can( 'manage_payouts' ) ) {
			wp_die( __( 'You do not have permission to process payouts', 'affwp-paypal-payouts' ) );
		}

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			wp_die( __( 'Please enter your API credentials in Affiliates &rarr; Settings &rarr; PayPal Payouts before attempting to process payments', 'affwp-paypal-payouts' ) );
		}

		$args = [
			'status'       => 'unpaid',
			'number'       => -1,
			'affiliate_id' => $affiliate_id,
			'date'         => [
				'start' => $start,
				'end'   => $end,
			],
		];

		// Final  affiliate / referral data to be paid out.
		$data = [];

		// The affiliates that have earnings to be paid.
		$affiliates = [];

		// Retrieve the referrals from the database.
		$referrals = affiliate_wp()->referrals->get_referrals( $args );

		if ( $referrals ) {

			foreach ( $referrals as $referral ) {

				// If not bypassing the holding period, skip referrals within the holding period.
				if ( ! $bypass_holding && affwp_is_referral_within_holding_period( $referral ) ) {
					continue;
				}

				$affiliate = affwp_get_affiliate( $referral->affiliate_id );

				if ( ! $affiliate->user ) {
					continue;
				}

				if ( in_array( $referral->affiliate_id, $affiliates ) ) {

					// Add the amount to an affiliate that already has a referral in the export.
					$amount = $data[ $referral->affiliate_id ]['amount'] + $referral->amount;

					$data[ $referral->affiliate_id ]['amount']      = $amount;
					$data[ $referral->affiliate_id ]['referrals'][] = $referral->referral_id;

				} else {

					$email = affwp_get_affiliate_payment_email( $referral->affiliate_id );

					$data[ $referral->affiliate_id ] = [
						'email'     => $email,
						'amount'    => $referral->amount,
						'currency'  => ! empty( $referral->currency ) ? $referral->currency : affwp_get_currency(),
						'referrals' => [ $referral->referral_id ],
					];

					$affiliates[] = $referral->affiliate_id;

				}
			}

			$payouts = [];

			$i = 0;

			foreach ( $data as $affiliate_id => $payout ) {

				if ( $minimum > 0 && $payout['amount'] < $minimum ) {

					// Ensure the minimum amount was reached.
					unset( $data[ $affiliate_id ] );

					// Skip to the next affiliate.
					continue;
				}

				$payouts[ $affiliate_id ] = [
					'email'       => $payout['email'],
					'amount'      => $payout['amount'],
					/* translators: 1: Referrals start date, 2: Referrals end date, 3: Home URL */
					'description' => sprintf( __( 'Payment for referrals between %1$s and %2$s from %3$s', 'affwp-paypal-payouts' ), $start, $end, home_url() ),
					'referrals'   => $payout['referrals'],
				];

				++$i;
			}

			$redirect_args = [
				'affwp_notice' => 'paypal_bulk_pay_success',
			];

			$success = $this->api->send_bulk_payment( $payouts );

			if ( is_wp_error( $success ) ) {

				$redirect_args['affwp_notice'] = 'paypal_error';
				$redirect_args['message']      = $success->get_error_message();
				$redirect_args['code']         = $success->get_error_code();

			} else {

				// We now know which referrals should be marked as paid.
				foreach ( $payouts as $affiliate_id => $payout ) {
					if ( function_exists( 'affwp_add_payout' ) ) {
						affwp_add_payout(
							[
								'affiliate_id'  => $affiliate_id,
								'referrals'     => $payout['referrals'],
								'amount'        => $payout['amount'],
								'payout_method' => 'paypal',
							]
						);
					} else {
						foreach ( $payout['referrals'] as $referral ) {
							affwp_set_referral_status( $referral, 'paid' );
						}
					}
				}
			}

			$redirect = affwp_admin_url( 'referrals', $redirect_args );

			// A header is used here instead of wp_redirect() due to the esc_url() bug that removes [] from URLs.
			header( 'Location:' . $redirect );
			exit;

		}
	}

	/**
	 * Determines whether to display Pay Now action links.
	 *
	 * This check is based on the logic that initiating a payment "now" should only
	 * be possible if 'paypal' payout method is enabled.
	 *
	 * @since 2.29.0
	 *
	 * @return bool True if the Pay Now links should be displayed, otherwise false.
	 */
	public function should_display_pay_now_links() {

		// Compat for pre-AffiliateWP 2.4.
		if ( ! affiliate_wp_paypal()->has_2_4() ) {
			return true;
		}

		$enabled_payout_methods = affwp_get_enabled_payout_methods();

		if ( in_array( 'paypal', $enabled_payout_methods ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add new action links to the referral actions column
	 *
	 * @access public
	 * @since 2.29.0
	 * @return array
	 */
	public function action_links( $links, $referral ) {

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			return $links;
		}

		if (
			'unpaid' !== $referral->status
			|| ! current_user_can( 'manage_referrals' )
			|| empty( affwp_get_affiliate_payment_email( $referral->affiliate_id ) )
		) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%1$s" class="%2$s">%3$s</a>',
			esc_url(
				add_query_arg(
					[
						'affwp_action' => 'pay_now',
						'referral_id'  => $referral->referral_id,
						'affiliate_id' => $referral->affiliate_id,
					]
				)
			),
			esc_attr( 'affwp-pay-now-via-paypal' ),
			esc_html( 'Pay Now via PayPal', 'affiliate-wp' )
		);

		return $links;
	}

	/**
	 * Register a new bulk action
	 *
	 * @access public
	 * @since 2.29.0
	 * @return array
	 */
	public function bulk_actions( $actions ) {

		if ( affiliate_wp_paypal()->has_api_credentials() ) {

			$actions['pay_now'] = __( 'Pay Now via PayPal', 'affiliate-wp' );

		}

		return $actions;
	}

	/**
	 * Render the Bulk Pay section
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function bulk_pay_form() {

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			return;
		}
		?>
		<script>
		jQuery(document).ready(function($) {
			// Show referral export form
			$('.affwp-referrals-paypal-payout-toggle').click(function() {
				$('.affwp-referrals-paypal-payout-toggle').toggle();
				$('#affwp-referrals-paypal-payout-form').slideToggle();
			});
			$('#affwp-referrals-paypal-payout-form').submit(function() {
				if( ! confirm( "<?php _e( 'Are you sure you want to payout referrals for the specified time frame via Paypal?', 'affiliate-wp' ); ?>" ) ) {
					return false;
				}
			});
		});
		</script>
		<button class="button-primary affwp-referrals-paypal-payout-toggle"><?php _e( 'Bulk Pay via Paypal', 'affiliate-wp' ); ?></button>
		<button class="button-primary affwp-referrals-paypal-payout-toggle" style="display:none"><?php _e( 'Close', 'affiliate-wp' ); ?></button>
		<form id="affwp-referrals-paypal-payout-form" class="affwp-gray-form" style="display:none;" action="<?php echo admin_url( 'admin.php?page=affiliate-wp-referrals' ); ?>" method="post">
			<p>
				<input type="text" class="affwp-datepicker" autocomplete="off" name="from" placeholder="<?php _e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
				<input type="text" class="affwp-datepicker" autocomplete="off" name="to" placeholder="<?php _e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
				<input type="text" class="affwp-text" name="minimum" placeholder="<?php esc_attr_e( 'Minimum amount', 'affiliate-wp' ); ?>"/>
				<input type="hidden" name="affwp_action" value="process_bulk_paypal_payout"/>
				<input type="submit" value="<?php _e( 'Process Payout via Paypal', 'affiliate-wp' ); ?>" class="button-secondary"/>
				<p><?php printf( __( 'This will send payments via Paypal for all unpaid referrals in the specified timeframe.', 'affiliate-wp' ), admin_url( 'admin.php?page=affiliate-wp-tools&tab=export_import' ) ); ?></p>
			</p>
		</form>
		<?php
	}

	/**
	 * Process a single referral payment
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function process_pay_now( $data ) {

		$referral_id = absint( $data['referral_id'] );

		if ( empty( $referral_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_referrals' ) ) {
			wp_die( __( 'You do not have permission to process payments', 'affiliate-wp' ) );
		}

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			wp_die( __( 'Please enter your API credentials in Affiliates > Settings > PayPal Payouts before attempting to process payments', 'affiliate-wp' ) );
		}

		$transfer = $this->pay_referral( $referral_id );

		if ( is_wp_error( $transfer ) ) {

			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=paypal_error&message=' . urlencode( $transfer->get_error_message() ) . '&code=' . urlencode( $transfer->get_error_code() ) ) );
			exit;

		}

		wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=paypal_success&referral=' . $referral_id ) );
		exit;
	}

	/**
	 * Process a referral payment for a bulk payout
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function process_bulk_action_pay_now( $referral_id ) {

		if ( empty( $referral_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_referrals' ) ) {
			return;
		}

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			wp_die( __( 'Please enter your API credentials in Affiliates > Settings > PayPal Payouts before attempting to process payments', 'affiliate-wp' ) );
		}

		$transfer = $this->pay_referral( $referral_id );
	}

	/**
	 * Payouts referrals in bulk for a specified timeframe (Legacy method for pre-2.4)
	 *
	 * All referrals are summed and then paid as a single transfer for each affiliate
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function process_bulk_paypal_payout_legacy() {

		if ( ! current_user_can( 'manage_referrals' ) ) {
			wp_die( __( 'You do not have permission to process payments', 'affiliate-wp' ) );
		}

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			wp_die( __( 'Please enter your API credentials in Affiliates > Settings > PayPal Payouts before attempting to process payments', 'affiliate-wp' ) );
		}

		$start = ! empty( $_POST['from'] ) ? sanitize_text_field( $_POST['from'] ) : false;
		$end   = ! empty( $_POST['to'] ) ? sanitize_text_field( $_POST['to'] ) : false;

		$args = [
			'status' => 'unpaid',
			'date'   => [
				'start' => $start,
				'end'   => $end,
			],
			'number' => -1,
		];

		// Final  affiliate / referral data to be paid out
		$data = [];

		// The affiliates that have earnings to be paid
		$affiliates = [];

		// Retrieve the referrals from the database
		$referrals = affiliate_wp()->referrals->get_referrals( $args );

		// The minimum payout amount
		$minimum = ! empty( $_POST['minimum'] ) ? sanitize_text_field( affwp_sanitize_amount( $_POST['minimum'] ) ) : 0;

		if ( $referrals ) {

			foreach ( $referrals as $referral ) {

				if ( in_array( $referral->affiliate_id, $affiliates ) ) {

					// Add the amount to an affiliate that already has a referral in the export

					$amount = $data[ $referral->affiliate_id ]['amount'] + $referral->amount;

					$data[ $referral->affiliate_id ]['amount']      = $amount;
					$data[ $referral->affiliate_id ]['referrals'][] = $referral->referral_id;

				} else {

					$user_name = affwp_get_affiliate_username( $referral->affiliate_id );

					// Skip if affiliate user account has been deleted.
					if ( ! $user_name ) {
						continue;
					}

					$email = affwp_get_affiliate_payment_email( $referral->affiliate_id );

					$data[ $referral->affiliate_id ] = [
						'email'     => $email,
						'amount'    => $referral->amount,
						'currency'  => ! empty( $referral->currency ) ? $referral->currency : affwp_get_currency(),
						'referrals' => [ $referral->referral_id ],
					];

					$affiliates[] = $referral->affiliate_id;

				}
			}

			$payouts = [];

			$i = 0;
			foreach ( $data as $affiliate_id => $payout ) {

				if ( $minimum > 0 && $payout['amount'] < $minimum ) {

					// Ensure the minimum amount was reached

					unset( $data[ $affiliate_id ] );

					// Skip to the next affiliate
					continue;

				}

				$payouts[ $affiliate_id ] = [
					'email'       => $payout['email'],
					'amount'      => $payout['amount'],
					'description' => sprintf( __( 'Payment for referrals between %1$s and %2$s from %3$s', 'affiliate-wp' ), $start, $end, home_url() ),
					'referrals'   => $payout['referrals'],
				];
				++$i;
			}

			$redirect_args = [
				'affwp_notice' => 'paypal_bulk_pay_success',
			];

			if ( empty( $payouts ) ) {
				$redirect_args['affwp_notice'] = 'paypal_bulk_pay_empty_error';

				$redirect = affwp_admin_url( 'referrals', $redirect_args );

				wp_redirect( $redirect );
				exit;
			}

			$success = $this->api->send_bulk_payment( $payouts );

			if ( is_wp_error( $success ) ) {

				$redirect_args['affwp_notice'] = 'paypal_error';
				$redirect_args['message']      = $success->get_error_message();
				$redirect_args['code']         = $success->get_error_code();

			} else {

				// We now know which referrals should be marked as paid
				foreach ( $payouts as $affiliate_id => $payout ) {
					if ( function_exists( 'affwp_add_payout' ) ) {
						affwp_add_payout(
							[
								'affiliate_id'  => $affiliate_id,
								'referrals'     => $payout['referrals'],
								'amount'        => $payout['amount'],
								'payout_method' => 'PayPal',
							]
						);
					} else {
						foreach ( $payout['referrals'] as $referral ) {
							affwp_set_referral_status( $referral, 'paid' );
						}
					}
				}
			}

			$redirect = affwp_admin_url( 'referrals', $redirect_args );

			// A header is used here instead of wp_redirect() due to the esc_url() bug that removes [] from URLs
			header( 'Location:' . $redirect );
			exit;

		}
	}

	/**
	 * Pay a referral
	 *
	 * @access public
	 * @since 2.29.0
	 * @return string
	 */
	private function pay_referral( $referral_id = 0 ) {

		if ( empty( $referral_id ) ) {
			return false;
		}

		$referral = affwp_get_referral( $referral_id );

		if ( ! affiliate_wp_paypal()->has_api_credentials() ) {
			return new WP_Error( 'missing_api_keys', __( 'Please enter your API credentials in Affiliates > Settings > PayPal Payouts before attempting to process payments', 'affiliate-wp' ) );
		}

		if ( empty( $referral ) ) {
			return new WP_Error( 'invalid_referral', __( 'The specified referral does not exist', 'affiliate-wp' ) );
		}

		if ( empty( $referral->affiliate_id ) ) {
			return new WP_Error( 'no_affiliate', __( 'There is no affiliate connected to this referral', 'affiliate-wp' ) );
		}

		if ( 'unpaid' != $referral->status ) {
			return new WP_Error( 'referral_not_unpaid', __( 'A payment cannot be processed for this referral since it is not marked as Unpaid', 'affiliate-wp' ) );
		}

		$user_name = affwp_get_affiliate_username( $referral->affiliate_id );

		if ( ! $user_name ) {
			return new WP_Error( 'user_account_deleted', __( 'This affiliate user account has been deleted', 'affiliate-wp' ) );
		}

		$email = affwp_get_affiliate_payment_email( $referral->affiliate_id );

		if ( empty( $email ) ) {
			return new WP_Error( 'no_email', __( 'This affiliate account does not have a Paypal email attached', 'affiliate-wp' ) );
		}

		$transfer    = false;
		$api_keys    = affiliate_wp_paypal()->get_api_credentials();
		$description = sprintf( __( 'Payment for referral #%1$d, %2$s', 'affiliate-wp' ), $referral_id, $referral->description );

		return $this->api->send_payment( [ 'email' => $email, 'amount' => $referral->amount, 'description' => $description, 'referral_id' => $referral_id ] );
	}

	/**
	 * Admin notices for success and error messages
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function admin_notices() {

		if ( empty( $_REQUEST['affwp_notice'] ) ) {
			return;
		}

		$affiliates  = ! empty( $_REQUEST['affiliate'] ) ? $_REQUEST['affiliate'] : 0;
		$referral_id = ! empty( $_REQUEST['referral'] ) ? absint( $_REQUEST['referral'] ) : 0;
		$transfer_id = ! empty( $_REQUEST['transfer'] ) ? sanitize_text_field( $_REQUEST['transfer'] ) : '';
		$message     = ! empty( $_REQUEST['message'] ) ? urldecode( $_REQUEST['message'] ) : '';
		$code        = ! empty( $_REQUEST['code'] ) ? urldecode( $_REQUEST['code'] ) . ' ' : '';

		switch ( $_REQUEST['affwp_notice'] ) {

			case 'paypal_success':
				echo '<div class="updated"><p>' . sprintf( __( 'Referral #%d paid out via PayPal successfully', 'affiliate-wp' ), $referral_id, $transfer_id, $transfer_id ) . '</p></div>';
				break;

			case 'paypal_bulk_pay_success':
				echo '<div class="updated"><p>' . __( 'Referrals paid out via PayPal successfully', 'affiliate-wp' ) . '</p></div>';
				break;

			case 'paypal_error':
				echo '<div class="error"><p><strong>' . __( 'Error:', 'affiliate-wp' ) . '</strong>&nbsp;' . $code . esc_html( $message ) . '</p></div>';
				break;

			case 'paypal_bulk_pay_empty_error':
				echo '<div class="error"><p>' . __( 'Error: No referrals are available to be paid out via PayPal', 'affiliate-wp' ) . '</p></div>';
				break;

		}
	}
}
