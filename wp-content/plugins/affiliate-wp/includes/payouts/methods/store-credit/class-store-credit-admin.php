<?php

namespace AffiliateWP\Core\Payouts\Methods\StoreCredit;

// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store Credit Admin functionality for core integration.
 *
 * @since 2.29.0
 */
class AffiliateWP_Store_Credit_Admin {

	/**
	 * Get things started
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function __construct() {

		if ( ! affiliate_wp()->settings->get( 'store-credit' ) ) {
			return;
		}

		// Add a "Store Credit & Payout Method" columns to the affiliates admin screen.
		add_filter( 'affwp_affiliate_table_columns', [ $this, 'column_store_credit' ], 10, 3 );
		add_filter( 'affwp_affiliate_table_store_credit', [ $this, 'column_store_credit_value' ], 10, 2 );
		add_filter( 'affwp_affiliate_table_payout_method', [ $this, 'column_payment_method_value' ], 10, 2 );

		// Add a "Payout Method" column to the referrals admin screen.
		add_filter( 'affwp_referral_table_columns', [ $this, 'referrals_column_store_credit' ], 10, 3 );
		add_filter( 'affwp_referral_table_payout_method', [ $this, 'referrals_column_payment_method_value' ], 10, 2 );

		// Add the Store Credit Balance to the edit affiliate screen.
		add_action( 'affwp_edit_affiliate_end', [ $this, 'edit_affiliate_store_credit_settings' ], 10, 1 );

		// WooCommerce-only hooks.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_edit_affiliate_scripts' ] );
		add_action( 'wp_ajax_adjust_affiliate_store_credit', [ $this, 'ajax_adjust_affiliate_store_credit' ] );
		add_action( 'affwp_update_affiliate', [ $this, 'update_affiliate' ], 0 );
	}

	/**
	 * (AJAX) Adjust Store Credit for Affiliate.
	 *
	 * @since 2.29.0
	 */
	public function ajax_adjust_affiliate_store_credit() : void {

		if ( ! wp_verify_nonce(
			filter_input( INPUT_POST, 'nonce', FILTER_UNSAFE_RAW ),
			'adjust_affiliate_store_credit'
		) ) {
			return;
		}

		if ( ! current_user_can( 'manage_affiliates' ) ) {

			wp_send_json( __( 'You are not allowed to change affiliate store credit.', 'affiliate-wp' ), 200 );
			exit;
		}

		$this->adjust_store_credit(
			intval( filter_input( INPUT_POST, 'affiliate_id', FILTER_SANITIZE_NUMBER_INT ) ),
			filter_input( INPUT_POST, 'movement', FILTER_UNSAFE_RAW ),
			floatval( filter_input( INPUT_POST, 'adjustment', FILTER_UNSAFE_RAW ) )
		)
			? wp_send_json_success(
				floatval(
					affwp_store_credit_balance(
						[
							'user_id' => filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT ),
						],
						false
					)
				)
			)
			: wp_send_json_error( __( "There was an error adjusting the affiliate's store credit, please refresh the page and try again.", 'affiliate-wp' ) );

		exit;
	}

	/**
	 * Load Admin CSS for Editing an Affiliate
	 *
	 * @since 2.29.0
	 */
	public function enqueue_edit_affiliate_scripts() : void {

		if ( ! $this->is_edit_affiliate_screen() ) {
			return;
		}

		$affiliate = affwp_get_affiliate( filter_input( INPUT_GET, 'affiliate_id', FILTER_SANITIZE_NUMBER_INT ) );

		if ( false === $affiliate ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'affiliatewp-store-credit/admin/edit-affiliate',
			AFFWP_SC_CORE_PLUGIN_URL . 'assets/css/admin-store-credit.css',
			[],
			AFFWP_SC_CORE_VERSION
		);

		if ( 'woocommerce' !== affiliatewp_store_credit_get_active_integration() ) {
			return;
		}

		// JavaScript.
		wp_enqueue_script(
			'affiliatewp-store-credit/admin/adjust-store-credit',
			AFFWP_SC_CORE_PLUGIN_URL . 'assets/js/admin-store-credit.js',
			[
				'jquery-core',
				'jquery-confirm',
			],
			AFFWP_SC_CORE_VERSION,
			true
		);

		// JavaScript Data.
		wp_localize_script(
			'affiliatewp-store-credit/admin/adjust-store-credit',
			'affiliatewpAdjustAffiliateStoreCredit',
			[
				'affiliateId'            => $affiliate->affiliate_id,
				'userId'                 => $affiliate->user_id,
				'currentBalance'         => floatval(
					affwp_store_credit_balance(
						[
							'user_id' => $affiliate->user_id,
						],
						false
					)
				),
				'ajaxURL'                => admin_url( 'admin-ajax.php' ),
				'ajaxNONCE'              => wp_create_nonce( 'adjust_affiliate_store_credit' ),
				'currentUserDisplayName' => get_userdata( get_current_user_id() )->display_name ?? __( 'Unknown', 'affiliate-wp' ),

				// From /wp-admin/admin.php?page=affiliate-wp-settings&tab=advanced .
				'currency' => [
					'symbol'             => html_entity_decode( str_replace( '0', '', affwp_currency_filter( 0.00 ) ) ),
					'position'           => affiliate_wp()->settings->get( 'currency_position', 'before' ),
					'thousandsSeparator' => html_entity_decode( affiliate_wp()->settings->get( 'thousands_separator', ',' ) ),
					'decimalSeparator'   => html_entity_decode( affiliate_wp()->settings->get( 'decimal_separator', '.' ) ),
				],

				// Translations.
				'i18n' => [
					'modal' => [
						'title'          => esc_html__( 'Adjust Store Credit', 'affiliate-wp' ),
						'currentBalance' => esc_html__( 'Current Balance', 'affiliate-wp' ),
						'newBalance'     => esc_html__( 'New Balance', 'affiliate-wp' ),
						'increase'       => esc_html__( 'Increase Store Credit', 'affiliate-wp' ),
						'decrease'       => esc_html__( 'Decrease Store Credit', 'affiliate-wp' ),
						'saveAdjustment' => esc_html__( 'Save Adjustment', 'affiliate-wp' ),
						'manualIncrease' => esc_html__( 'Manual increase', 'affiliate-wp' ),
						'manualDecrease' => esc_html__( 'Manual decrease', 'affiliate-wp' ),
					],
				],
			]
		);
	}

	/**
	 * Are we editing an Affiliate?
	 *
	 * In the Admin.
	 *
	 * @since 2.29.0
	 *
	 * @return bool
	 */
	private function is_edit_affiliate_screen() : bool {

		if ( 'affiliate-wp-affiliates' !== filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW ) ) {
			return false; // Not the affiliate admin screen(s).
		}

		if ( 'edit_affiliate' !== filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW ) ) {
			return false; // Not the edit affiliate screen.
		}

		return true;
	}

	/**
	 * Add a "Store Credit & Payment Method" columns to the affiliates screen.
	 *
	 * @since 2.29.0
	 *
	 * @param array  $prepared_columns Prepared columns.
	 * @param array  $columns  The columns for this list table.
	 * @param object $instance List table instance.
	 *
	 * @return array $prepared_columns Prepared columns.
	 */
	public function column_store_credit( $prepared_columns, $columns, $instance ) {

		$offset = 6;

		$prepared_columns = array_slice( $prepared_columns, 0, $offset, true ) +
							[ 'store_credit' => __( 'Store Credit', 'affiliate-wp' ) ] +
							[ 'payout_method' => __( 'Payout Method', 'affiliate-wp' ) ] +
							array_slice( $prepared_columns, $offset, null, true );

		return $prepared_columns;
	}

	/**
	 * Show the store credit balance for each affiliate.
	 *
	 * @since 2.29.0
	 *
	 * @param string $value    The column data.
	 * @param object $affiliate The current affiliate object.
	 *
	 * @return string $value   The affiliate's store credit balance.
	 */
	public function column_store_credit_value( $value, $affiliate ) {
		$value = affwp_store_credit_balance( [ 'affiliate_id' => $affiliate->affiliate_id ] );

		return $value;
	}

	/**
	 * Show the payment method for each affiliate.
	 *
	 * @since 2.29.0
	 *
	 * @param string $value     The column data.
	 * @param object $affiliate The current affiliate object.
	 *
	 * @return string $value   The affiliate's payment method.
	 */
	public function column_payment_method_value( $value, $affiliate ) {

		$value = $this->get_payout_method( $affiliate->affiliate_id );

		return $value;
	}

	/**
	 * Adds a "Payment Method" column to the referrals screen.
	 *
	 * @since 2.29.0
	 *
	 * @param array  $prepared_columns Prepared columns.
	 * @param array  $columns  The columns for this list table.
	 * @param object $instance List table instance.
	 * @return array Prepared columns.
	 */
	public function referrals_column_store_credit( $prepared_columns, $columns, $instance ) {

		$offset = 8;

		$prepared_columns = array_slice( $prepared_columns, 0, $offset, true ) +
							[ 'payout_method' => __( 'Payout Method', 'affiliate-wp' ) ] +
							array_slice( $prepared_columns, $offset, null, true );

		return $prepared_columns;
	}

	/**
	 * Shows the payment method for each referral.
	 *
	 * @since 2.29.0
	 *
	 * @param string $value     The column data.
	 * @param object $affiliate The current affiliate object.
	 * @return string The affiliate's payment method.
	 */
	public function referrals_column_payment_method_value( $value, $referral ) {

		$value = $this->get_payout_method( $referral->affiliate_id );

		return $value;
	}

	/**
	 * Display the store credit settings.
	 *
	 * @access public
	 * @param \AffWP\Affiliate $affiliate The affiliate object being edited.
	 *
	 * @since 2.29.0
	 */
	public function edit_affiliate_store_credit_settings( $affiliate ) {
		$integration = affiliatewp_store_credit_get_active_integration();

		$balance = affwp_store_credit_balance( [ 'affiliate_id' => $affiliate->affiliate_id ] );

		$checked = affwp_get_affiliate_meta( $affiliate->affiliate_id, 'store_credit_enabled', true );

		$transactions = affwp_store_credit()->transactions->get_transactions_for_user( $affiliate->user_id );

		?>

		<!-- Store Credit Header -->
		<tr class="form-row">
			<th scope="row">
				<label for="affwp_settings[store_credit_header]"><?php esc_html_e( 'Store Credit', 'affiliate-wp' ); ?></label>
			</th>
			<td><hr></td>
		</tr>

		<?php if ( ! affiliate_wp()->settings->get( 'store-credit-all-affiliates' ) ) : ?>

			<!-- Enable Store Credit? -->
			<tr class="form-row">
				<th scope="row">
					<label for="enable_store_credit"><?php esc_html_e( 'Enable Store Credit?', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="checkbox" name="enable_store_credit" id="enable_store_credit" value="1" <?php checked( 1, $checked, true ); ?>>
						<?php esc_html_e( 'Enable payouts via store credit for this affiliate.', 'affiliate-wp' ); ?>
				</td>
			</tr>

		<?php endif; ?>

		<!-- Balance -->
		<tr class="form-row store-credit">

			<th scope="row">
				<label for="store_credit"><?php esc_html_e( 'Balance', 'affiliate-wp' ); ?></label>
			</th>

			<td>

					<p>
						<span class="balance">
							<?php echo esc_attr( false === $balance ? affwp_currency_filter( '0.00' ) : $balance ); ?>
						</span>

						<?php if ( 'woocommerce' === $integration ) : ?>

							<!-- Adjust Store Credit -->
							<a class="button button-secondary" id="adjust-store-credit">
								<?php esc_html_e( 'Adjust', 'affiliate-wp' ); ?>
							</a>
						<?php endif; ?>
					</p>
			</td>
		</tr>


		<?php if ( 'woocommerce' === $integration ) : ?>

			<!-- Adjust Store Credit -->
			<tr class="form-row store-credit-adjustments <?php echo esc_attr( ( is_array( $transactions ) && count( $transactions ) > 0 ) ? '' : 'hidden' ); ?>">

				<th scope="row">
					<label for="store_credit"><?php esc_html_e( 'Adjustments', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<table class="widefat striped adjustments-table">

						<caption class="hidden">
							<?php esc_html_e( 'Adjustments', 'affiliate-wp' ); ?>
						</caption>

						<thead>
							<tr>
								<td><?php esc_html_e( 'Date', 'affiliate-wp' ); ?></td>
								<td><?php esc_html_e( 'Adjustment', 'affiliate-wp' ); ?></td>
								<td><?php esc_html_e( 'Amount', 'affiliate-wp' ); ?></td>
								<td><?php esc_html_e( 'User', 'affiliate-wp' ); ?></td>
							</tr>
						</thead>

						<tbody>

							<?php foreach ( $transactions as $transaction ) : ?>

								<tr>
									<td><?php echo esc_html( gmdate( 'F j, Y', strtotime( $transaction->time ?? 0 ) ) ); ?></td>
									<td>
										<?php

										echo wp_kses_post(
											( function ( object $transaction ) : string {

												if (
													'manual' === ( $transaction->type ?? '' )
														&& 'decrease' === ( $transaction->movement ?? '' )
												) {
													return __( 'Manual decrease', 'affiliate-wp' );
												} elseif (
													'manual' === ( $transaction->type ?? '' )
														&& 'increase' === ( $transaction->movement ?? '' )
												) {
													return __( 'Manual increase', 'affiliate-wp' );
												} elseif (
													'manual' === ( $transaction->type ?? '' )
												) {
													return __( 'Manual', 'affiliate-wp' );
												}

												if ( 'payout' === ( $transaction->type ?? '' ) ) {

													return ( 'increase' === ( $transaction->movement ?? '' ) )

														// An increase means a payout was created, link to the payout.
														? sprintf(

															// Translators: %1$s is the ID of the payout.
															__( 'Payout %1$s created', 'affiliate-wp' ),

															// Check if the payout still exists.
															( false !== affwp_get_payout( $transaction->reference_id ?? 0 ) )

																// Link to the payout.
																? sprintf(
																	'<a href="%1$s">#%2$s</a>',
																	sprintf(
																		admin_url( 'admin.php?page=affiliate-wp-payouts&payout_id=6&action=view_payout' ),
																		$transaction->reference_id ?? 0
																	),
																	$transaction->reference_id ?? 0
																)

																// The number (payout doesn't exist).
																: sprintf(
																	'#%1$s',
																	$transaction->reference_id ?? 0
																)
														)

														// A decreased payout means the payout was deleted, show reference number only.
														: sprintf(

															// Translators: %1$s is the ID of the payout.
															__( 'Payout #%1$s deleted', 'affiliate-wp' ),
															$transaction->reference_id ?? 0
														);
												}

												if ( 'purchase' === ( $transaction->type ?? '' ) ) {

													return sprintf(

														// Make sure order #1 never breaks.
														str_replace(
															' #',
															'&nbsp;#',

															// Translators: %1$s is the ID of the order.
															__( 'Store credit applied to order %1$s', 'affiliate-wp' )
														),

														// Check if the order exists.
														( false !== wc_get_order( $transaction->reference_id ?? 0 ) )

															// Link to the order.
															? sprintf(
																'<a href="%1$s" target="_blank">#%2$s</a>',
																sprintf(
																	admin_url( 'admin.php?page=wc-orders&action=edit&id=%1$s' ),
																	$transaction->reference_id ?? 0
																),
																$transaction->reference_id ?? 0
															)

															// Just the order number (deleted).
															: sprintf(
																'&nbsp;#%s',
																$transaction->reference_id ?? 0
															)
													);
												}

												if ( 'refund' === ( $transaction->type ?? '' ) ) {
													return __( 'Refund', 'affiliate-wp' );
												}

												if ( 'renewal' === ( $transaction->type ?? '' ) ) {
													return __( 'Renewal', 'affiliate-wp' );
												}

												return __( 'Unknown', 'affiliate-wp' );
											} )( $transaction )
										);

										?>
									</td>
									<td><?php echo esc_html( affwp_currency_filter( affwp_format_amount( $transaction->to - $transaction->from ) ) ); ?></td>
									<td><?php echo esc_html( get_userdata( $transaction->by_user_id )->display_name ?? __( 'Unknown', 'affiliate-wp' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

				</td>
			</tr>
		<?php endif; ?>

		<?php
	}

	/**
	 * Save affiliate store credit option in the affiliate meta table.
	 *
	 * @since  2.29.0
	 */
	public function update_affiliate( $data ) {

		if ( empty( $data['affiliate_id'] ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_affiliates' ) ) {
			return;
		}

		$enable_store_credit = isset( $data['enable_store_credit'] ) ? $data['enable_store_credit'] : '';

		if ( $enable_store_credit ) {
			affwp_update_affiliate_meta( $data['affiliate_id'], 'store_credit_enabled', $enable_store_credit );
		} else {
			affwp_delete_affiliate_meta( $data['affiliate_id'], 'store_credit_enabled' );
		}
	}

	/**
	 * Adjust Store Credit
	 *
	 * When we save the affiliate data, we adjust the store credit balance if there
	 * is a movement and amount specified.
	 *
	 * @since 2.29.0
	 *
	 * @param int    $affiliate_id The Affiliate (ID).
	 * @param string $movement     The movement, either `increase` or `decrease`.
	 * @param float  $amount       The amount to increase or decrease by.
	 *
	 * @return bool True if it was updated, false if not.
	 */
	private function adjust_store_credit(
		int $affiliate_id,
		string $movement,
		float $amount
	) : bool {

		if ( ! in_array( $movement, [ 'increase', 'decrease' ], true ) ) {
			return false;
		}

		if ( $amount <= 0 ) {
			return false;
		}

		if ( $affiliate_id <= 0 ) {
			return false;
		}

		// Check for both namespaced (core) and non-namespaced (addon) versions
		if ( ! class_exists( 'AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_WooCommerce' )
			&& ! class_exists( 'AffiliateWP_Store_Credit_WooCommerce' ) ) {
			return false; // Store credit not active.
		}

		$affiliate = affwp_get_Affiliate( $affiliate_id );

		if ( ! is_a( $affiliate, '\AffWP\Affiliate' ) ) {
			return false;
		}

		try {
			// Try namespaced version first (core), then non-namespaced (addon)
			if ( class_exists( 'AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_WooCommerce' ) ) {
				$adjustment = \AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_WooCommerce::adjust_store_credit(
					$movement,
					$amount,
					$affiliate->user_id,
					'manual',
					$affiliate->affiliate_id,
					__METHOD__,
					get_current_user_id(), // The user who is making the adjustment.
				);
			} else {
				$adjustment = AffiliateWP_Store_Credit_WooCommerce::adjust_store_credit(
					$movement,
					$amount,
					$affiliate->user_id,
					'manual',
					$affiliate->affiliate_id,
					__METHOD__,
					get_current_user_id(), // The user who is making the adjustment.
				);
			}

			return is_numeric( $adjustment )
				|| true === $adjustment;

		} catch ( \Exception $e ) {

			affiliate_wp()->utils->log(
				"Store credit adjustment failed for affiliate with ID #{$affiliate_id} with error: {$e->getMessage()}"
			);

			return false;
		}
	}

	/**
	 * Get the payment method set for the affiliate.
	 *
	 * @since  2.29.0
	 *
	 * @param int $affiliate_id The affiliate ID
	 *
	 * @return string $payment method The payment method set for the affiliate
	 */
	public function get_payout_method( $affiliate_id = 0 ) {

		$payment_method = __( 'Cash', 'affiliate-wp' );

		$global_store_credit_enabled = affiliate_wp()->settings->get( 'store-credit-all-affiliates' );

		if ( $global_store_credit_enabled ) {

			$payment_method = __( 'Store Credit', 'affiliate-wp' );

		} else {

			$affiliate_store_credit_enabled = affwp_get_affiliate_meta( $affiliate_id, 'store_credit_enabled', true );

			if ( $affiliate_store_credit_enabled ) {

				$payment_method = __( 'Store Credit', 'affiliate-wp' );

			}
		}

		return $payment_method;
	}
}
