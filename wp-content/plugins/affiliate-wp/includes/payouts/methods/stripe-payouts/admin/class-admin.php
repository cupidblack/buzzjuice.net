<?php
/**
 * Admin Class
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Admin
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Admin Class
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Admin {

	/**
	 * Referrals that need Stripe payment modals
	 *
	 * @since 2.29.0
	 * @var array
	 */
	private $referrals_needing_modals = [];

	/**
	 * Setup the admin class
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Add notice for Stripe connection.
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		// Add details to affiliate screen
		add_action( 'affwp_edit_affiliate_end', [ $this, 'affiliate_stripe_connection_details' ] );

		// Add Stripe payment details to view payout screen
		add_action( 'affwp_edit_payout_end', [ $this, 'payout_stripe_payment_details' ] );

		// Add Stripe connection status column to affiliates table
		add_filter( 'affwp_affiliate_table_columns', [ $this, 'add_stripe_status_column' ], 10, 3 );
		add_filter( 'affwp_affiliate_table_stripe_status', [ $this, 'render_stripe_status_column' ], 10, 2 );

		// Make Stripe status column sortable
		add_filter( 'affwp_affiliate_table_sortable_columns', [ $this, 'add_stripe_status_sortable_column' ] );

		// Add pay button to referrals.
		add_filter( 'affwp_referral_row_actions', [ $this, 'add_pay_action' ], 10, 2 );

		// Add batch export option
		add_filter( 'affwp_batch_export_types', [ $this, 'add_batch_export_type' ] );

		// Add bulk actions for referrals page
		add_filter( 'affwp_referrals_bulk_actions', [ $this, 'add_referrals_bulk_actions' ] );
		add_action( 'affwp_referrals_do_bulk_action_pay_now_stripe', [ $this, 'process_bulk_action_pay_now' ], 10, 1 );

		// Add Stripe as a payout method for Pay Affiliates screen
		add_filter( 'affwp_payout_methods', [ $this, 'add_payout_method' ] );
		add_filter( 'affwp_is_payout_method_enabled', [ $this, 'is_stripe_enabled' ], 10, 2 );
		add_action( 'affwp_preview_payout_note_stripe', [ $this, 'preview_payout_note' ] );
		add_filter( 'affwp_preview_payout_data_stripe', [ $this, 'preview_payout_data' ] );
		add_filter( 'affwp_preview_payout_invalid_affiliates_stripe', [ $this, 'preview_payout_invalid_affiliates' ], 10, 2 );
		add_action( 'affwp_process_payout_stripe', [ $this, 'process_bulk_stripe_payout' ], 10, 6 );

		// AJAX handlers
		add_action( 'wp_ajax_affwp_stripe_payouts_pay_affiliate', [ $this, 'process_payout_ajax' ] );
		add_action( 'wp_ajax_affwp_stripe_payouts_handle_platform_change', [ $this, 'handle_platform_change_ajax' ] );

		add_action( 'wp_ajax_affwp_test_stripe_connection', [ $this, 'test_stripe_connection_ajax' ] );
		add_action( 'wp_ajax_affwp_stripe_check_balance', [ $this, 'check_balance_ajax' ] );
		add_action( 'wp_ajax_affwp_stripe_create_test_charge', [ $this, 'create_test_charge_ajax' ] );
		add_action( 'wp_ajax_affwp_stripe_check_affiliate_capability', [ $this, 'check_affiliate_capability_ajax' ] );
		add_action( 'wp_ajax_affwp_stripe_disconnect_affiliate', [ $this, 'disconnect_affiliate_ajax' ] );

		// Scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Add modals to footer on referrals page
		add_action( 'admin_footer', [ $this, 'render_stripe_payment_modals' ] );

		// Save platform account ID when settings are saved
		add_filter( 'affwp_settings_payouts_sanitize', [ $this, 'save_platform_account_id' ], 10, 1 );
	}

	/**
	 * Display admin notices
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function admin_notices() {
		// Only show notices on the referrals page.
		if ( empty( $_REQUEST['affwp_notice'] ) ) {
			return;
		}

		$referral_id = ! empty( $_REQUEST['referral'] ) ? absint( $_REQUEST['referral'] ) : 0;
		$message     = ! empty( $_REQUEST['message'] ) ? urldecode( $_REQUEST['message'] ) : '';
		$code        = ! empty( $_REQUEST['code'] ) ? urldecode( $_REQUEST['code'] ) . ' ' : '';

		switch ( $_REQUEST['affwp_notice'] ) {

			case 'stripe_success':
				// Get referral details for enhanced message.
				if ( $referral_id ) {
					$referral = affwp_get_referral( $referral_id );
					if ( $referral ) {
						$affiliate          = affwp_get_affiliate( $referral->affiliate_id );
						$affiliate_username = affwp_get_affiliate_username( $referral->affiliate_id );
						$amount             = affwp_currency_filter( affwp_format_amount( $referral->amount ) );

						echo '<div class="updated"><p>' .
							sprintf(
								__( 'Successfully paid %1$s to %2$s (Referral #%3$d) via Stripe', 'affiliate-wp' ),
								$amount,
								$affiliate_username,
								$referral_id
							) .
						'</p></div>';
					} else {
						// Fallback if referral not found
						echo '<div class="updated"><p>' . sprintf( __( 'Referral #%d paid out via Stripe successfully', 'affiliate-wp' ), $referral_id ) . '</p></div>';
					}
				} else {
					echo '<div class="updated"><p>' . __( 'Payment processed via Stripe successfully', 'affiliate-wp' ) . '</p></div>';
				}
				break;

			case 'stripe_bulk_success':
				// Try to get count and total from query params if available
				$count = ! empty( $_REQUEST['count'] ) ? absint( $_REQUEST['count'] ) : 0;
				$total = ! empty( $_REQUEST['total'] ) ? urldecode( $_REQUEST['total'] ) : '';

				if ( $count && $total ) {
					echo '<div class="updated"><p>' .
						sprintf(
							__( 'Successfully paid out %1$d referral(s) totaling %2$s via Stripe', 'affiliate-wp' ),
							$count,
							$total
						) .
					'</p></div>';
				} else {
					echo '<div class="updated"><p>' . __( 'Referrals paid out via Stripe successfully', 'affiliate-wp' ) . '</p></div>';
				}
				break;

			case 'stripe_error':
				echo '<div class="error"><p><strong>' . __( 'Error:', 'affiliate-wp' ) . '</strong>&nbsp;' . $code . esc_html( $message ) . '</p></div>';
				break;

			case 'stripe_bulk_mixed':
				// Mixed results - some succeeded, some failed
				echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
				break;

			case 'stripe_bulk_empty_error':
				echo '<div class="error"><p>' . __( 'Error: No referrals are available to be paid out via Stripe', 'affiliate-wp' ) . '</p></div>';
				break;

			case 'stripe_disconnected':
				echo '<div class="notice notice-info is-dismissible"><p>' . __( 'Affiliate disconnected from Stripe successfully.', 'affiliate-wp' ) . '</p></div>';
				break;

			// Legacy notices for backward compatibility
			case 'payout_success':
				echo '<div class="updated"><p>' . __( 'Payout successfully sent to affiliate via Stripe!', 'affiliate-wp' ) . '</p></div>';
				break;

			case 'payout_error':
				$error = ! empty( $_GET['payout_error'] ) ? affwp_stripe_payouts_sanitize_error_message( urldecode( $_GET['payout_error'] ) ) : $message;
				echo '<div class="error"><p>' . sprintf( __( 'Error sending payout: %s', 'affiliate-wp' ), esc_html( $error ) ) . '</p></div>';
				break;
		}

		// Show message on affiliate edit screen for Stripe connection
		if ( isset( $_GET['page'] ) && 'affiliate-wp-affiliates' === $_GET['page'] &&
			isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {

			// Validate affiliate_id parameter if present
			if ( isset( $_GET['affiliate_id'] ) ) {
				$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
				if ( is_wp_error( $affiliate_id ) ) {
					// Invalid affiliate ID, don't show notices
					return;
				}
			}

			if ( isset( $_GET['stripe_connected'] ) ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e( 'Affiliate successfully connected to Stripe!', 'affiliate-wp' ); ?></p>
				</div>
				<?php
			}

			if ( isset( $_GET['stripe_disconnected'] ) ) {
				?>
				<div class="notice notice-info is-dismissible">
					<p><?php _e( 'Affiliate disconnected from Stripe.', 'affiliate-wp' ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Display Stripe connection details on the affiliate edit screen
	 *
	 * @since 2.29.0
	 * @param object $affiliate The affiliate object
	 * @return void
	 */
	public function affiliate_stripe_connection_details( $affiliate ) {
		// Only show if Stripe is configured.
		if ( ! affwp_stripe_payouts_is_configured() ) {
			return;
		}

		// Validate affiliate object and ID
		if ( ! $affiliate || ! isset( $affiliate->affiliate_id ) ) {
			return;
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $affiliate->affiliate_id );
		if ( is_wp_error( $affiliate_id ) ) {
			return;
		}

		$is_connected = affwp_stripe_payouts_is_affiliate_connected( $affiliate_id );
		$account_id   = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );
		?>

		<!-- Stripe Payouts Header -->
		<tr class="form-row">
			<th scope="row">
				<label><?php _e( 'Stripe Payouts', 'affiliate-wp' ); ?></label>
			</th>
			<td><hr></td>
		</tr>

		<tr class="form-row">
			<th scope="row"></th>
			<td>
				<?php
				// Get account details for enhanced display
				$account_details             = null;
				$requirements                = [];
				$has_requirements            = false;
				$has_capability_restrictions = false;
				$restricted_capabilities     = [];

				if ( $is_connected && function_exists( 'affwp_stripe_payouts_init_api' ) ) {
					affwp_stripe_payouts_init_api();
					try {
						$account_details = \Stripe\Account::retrieve( $account_id );
						if ( ! empty( $account_details->requirements->currently_due ) ) {
							$requirements     = $account_details->requirements->currently_due;
							$has_requirements = true;
						}

						// Get additional requirements data for enhanced display
						$past_due_requirements = ! empty( $account_details->requirements->past_due ) ? $account_details->requirements->past_due : [];
						$current_deadline      = ! empty( $account_details->requirements->current_deadline ) ? $account_details->requirements->current_deadline : null;
						$requirement_errors    = ! empty( $account_details->requirements->errors ) ? $account_details->requirements->errors : [];

						// Check for capability restrictions
						if ( isset( $account_details->capabilities ) ) {
							foreach ( $account_details->capabilities as $capability => $status ) {
								if ( $status !== 'active' ) {
									$has_capability_restrictions            = true;
									$restricted_capabilities[ $capability ] = $status;
								}
							}
						}
					} catch ( \Exception $e ) {
						// Silently fail
					}
				}

				// Get detailed status for the header badge
				$header_status        = 'not_connected';
				$header_badge_text    = __( 'Not Connected', 'affiliate-wp' );
				$header_badge_variant = 'secondary';

				if ( $is_connected ) {
					// Use the same status determination logic as the column
					$status_data   = $this->get_detailed_stripe_account_status( $account_id, $affiliate_id );
					$header_status = $status_data['status'];

					switch ( $header_status ) {
						case 'active':
							$header_badge_text    = __( 'Enabled', 'affiliate-wp' );
							$header_badge_variant = 'success';
							break;
						case 'action_required':
						case 'restricted':
							$header_badge_text    = __( 'Restricted', 'affiliate-wp' );
							$header_badge_variant = 'danger';
							break;
						case 'pending':
							$header_badge_text    = __( 'In review', 'affiliate-wp' );
							$header_badge_variant = 'warning';
							break;
						case 'suspended':
							$header_badge_text    = __( 'Rejected', 'affiliate-wp' );
							$header_badge_variant = 'danger';
							break;
						default:
							// Fallback to Enabled if status is unknown but connected
							$header_badge_text    = __( 'Enabled', 'affiliate-wp' );
							$header_badge_variant = 'success';
							break;
					}
				}

				// Build the accordion header with status badge
				ob_start();
				?>
				<div class="flex justify-between items-center py-2">
					<div class="flex-1">
						<div class="flex gap-3 items-center">
							<?php
							$affiliate_name = affwp_get_affiliate_name( $affiliate_id );
							if ( ! empty( trim( $affiliate_name ) ) ) :
								?>
								<span class="text-sm font-medium text-gray-700">
									<?php echo esc_html( $affiliate_name ); ?>
								</span>
							<?php endif; ?>
							<?php
							// Add tooltip for restricted status
							$tooltip_html = '';
							if ( $header_badge_text === __( 'Restricted', 'affiliate-wp' ) ) {
								if ( ! empty( $past_due_requirements ) ) {
									$tooltip_text = __( 'The account has overdue requirements that must be resolved', 'affiliate-wp' );
								} elseif ( ! empty( $requirements ) ) {
									$tooltip_text = __( 'The account owner needs to provide more information to Stripe', 'affiliate-wp' );
								} else {
									$tooltip_text = __( 'The account has restrictions that need to be resolved', 'affiliate-wp' );
								}
								$tooltip_html = affwp_tooltip( $tooltip_text );
							}

							// Build badge arguments
							$badge_args = [
								'text'    => $header_badge_text,
								'variant' => $header_badge_variant,
								'size'    => 'xs',
							];

							if ( $tooltip_html ) {
								$badge_args['attributes'] = [
									'data-tooltip-html' => esc_attr( $tooltip_html ),
									'style'             => 'cursor: help;',
								];
							}

							affwp_badge( $badge_args );
							?>
						</div>
						<?php if ( $is_connected && ! empty( $account_id ) ) : ?>
							<div class="mt-1">
								<code class="text-sm text-gray-500"><?php echo esc_html( $account_id ); ?></code>
							</div>
						<?php endif; ?>
					</div>
					<?php
					// Use the configure button component with dynamic text
					affwp_configure_button(
						[
							'text'      => __( 'Details', 'affiliate-wp' ),
							'panel_var' => 'accordion_stripe_details',
							'variant'   => 'secondary',
							'size'      => 'sm',
							'icon'      => 'chevron-down',
						]
					);
					?>
				</div>
				<?php
				$header_content = ob_get_clean();

				// Build the accordion content
				ob_start();
				?>
				<div class="px-4 pt-4 pb-4 border-t border-gray-200">
					<?php if ( $is_connected ) : ?>
						<!-- Connected State Content -->
						<?php if ( isset( $account_details ) && $account_details ) : ?>
							<!-- Account Overview Section -->
							<div class="space-y-4">
								<!-- Stripe Dashboard Link -->
								<div>
									<h4 class="mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase"><?php _e( 'Account Details', 'affiliate-wp' ); ?></h4>
									<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
										<div class="text-sm text-gray-600">
											<?php echo esc_html( ucfirst( $account_details->type ) ); ?> •
											<?php echo esc_html( strtoupper( $account_details->country ) ); ?>
											<?php if ( isset( $account_details->created ) && $account_details->created ) : ?>
												•
												<?php
												$connected_date = date_i18n( 'M j, Y', $account_details->created );
												printf( __( 'Connected %s', 'affiliate-wp' ), esc_html( $connected_date ) );
												?>
											<?php endif; ?>
										</div>
										<?php
										$stripe_dashboard_url = affwp_stripe_payouts_is_test_api_mode()
											? 'https://dashboard.stripe.com/test/connect/accounts/' . $account_id
											: 'https://dashboard.stripe.com/connect/accounts/' . $account_id;
										?>
										<div class="flex gap-2">
											<?php
											affwp_button(
												[
													'text' => __( 'View in Stripe', 'affiliate-wp' ),
													'href' => $stripe_dashboard_url,
													'variant' => 'secondary',
													'size' => 'sm',
													'icon' => 'external-link',
													'attributes' => [
														'target' => '_blank',
														'rel' => 'noopener noreferrer',
													],
												]
											);

											// Disconnect button with custom outline styling
											affwp_button(
												[
													'text' => __( 'Disconnect', 'affiliate-wp' ),
													'size' => 'sm',
													'icon' => 'x-circle',
													'class' => 'border border-red-600 text-red-600 bg-white hover:bg-red-50 transition-colors duration-200',
													'attributes' => [
														'onclick' => 'window.affwpShowDisconnectModal_' . $affiliate_id . '()',
													],
												]
											);
											?>
										</div>
								</div>

								<!-- Last Updated Info -->
								<?php
								$requirements_meta = affwp_get_affiliate_meta( $affiliate_id, 'stripe_requirements', true );
								if ( ! empty( $requirements_meta['last_updated'] ) ) :
									$last_updated = date_i18n(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$requirements_meta['last_updated']
									);
									?>
									<div class="mt-2 text-xs text-gray-500">
										<?php
										printf(
											__( 'Last updated: %s', 'affiliate-wp' ),
											esc_html( $last_updated )
										);
										?>
									</div>
								<?php endif; ?>

								<!-- Account Status Section (Payouts & Payments - matching Stripe's order) -->
								<div class="mt-6">
									<h4 class="mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase"><?php _e( 'Account Status', 'affiliate-wp' ); ?></h4>
									<div class="space-y-2">
										<?php
										// Get capability status for more detailed information
										$capability_status = [];
										if ( function_exists( 'affwp_stripe_payouts_check_affiliate_payment_capability' ) ) {
											$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );
											if ( ! is_wp_error( $capability_check ) ) {
												$capability_status = $capability_check;
											}
										}

										// Format deadline if available
										$deadline_text = '';
										if ( ! empty( $capability_status['current_deadline'] ) ) {
											$days_until = ceil( ( $capability_status['current_deadline'] - time() ) / 86400 );
											if ( $days_until > 0 && $days_until <= 7 ) {
												$deadline_text = sprintf( _n( 'in %d day', 'in %d days', $days_until, 'affiliate-wp' ), $days_until );
											} elseif ( $days_until > 7 ) {
												$deadline_text = sprintf( __( 'by %s', 'affiliate-wp' ), date_i18n( get_option( 'date_format' ), $capability_status['current_deadline'] ) );
											}
										}
										?>

										<!-- Payouts Status (First, matching Stripe) -->
										<div class="flex items-center gap-2 text-sm">
											<span class="text-gray-600"><?php _e( 'Payouts', 'affiliate-wp' ); ?>:</span>
											<?php if ( $account_details->payouts_enabled ) : ?>
												<span class="text-gray-700"><?php _e( 'active', 'affiliate-wp' ); ?></span>
											<?php else : ?>
												<span class="text-red-600 font-medium"><?php _e( 'paused', 'affiliate-wp' ); ?></span>
											<?php endif; ?>
										</div>

										<!-- Payments Status (Second, matching Stripe) -->
										<div class="flex items-center gap-2 text-sm">
											<span class="text-gray-600"><?php _e( 'Payments', 'affiliate-wp' ); ?>:</span>
											<?php if ( ! $account_details->charges_enabled ) : ?>
												<span class="text-red-600 font-medium"><?php _e( 'paused', 'affiliate-wp' ); ?></span>
											<?php elseif ( ! empty( $capability_status['payments_pausing_soon'] ) ) : ?>
												<span class="text-amber-600 font-medium flex items-center gap-1">
													<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
													</svg>
													<?php
													echo __( 'paused soon', 'affiliate-wp' );
													if ( $deadline_text ) {
														echo ' <span class="text-xs text-gray-500">(' . $deadline_text . ')</span>';
													}
													?>
												</span>
											<?php else : ?>
												<span class="text-gray-700"><?php _e( 'active', 'affiliate-wp' ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>

							<?php if ( $has_requirements || ! empty( $past_due_requirements ) ) : ?>
								<?php
								// Calculate unique requirements count
								$unique_requirements = array_unique( array_merge( $requirements, $past_due_requirements ) );
								$total_requirements  = count( $unique_requirements );
								?>
								<div class="mt-6">
									<!-- Actions Required Header -->
									<div class="flex items-center justify-between mb-2">
										<div class="flex items-center gap-2">
											<h4 class="text-xs font-medium tracking-wider text-gray-500 uppercase">
												<?php _e( 'Actions Required', 'affiliate-wp' ); ?>
											</h4>
											<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white">
												<?php echo esc_html( $total_requirements ); ?>
											</span>
										</div>
										<?php if ( $current_deadline ) : ?>
											<div class="text-xs text-gray-500">
												<?php
												$deadline_date = date_i18n( get_option( 'date_format' ), $current_deadline );
												printf( __( 'Due by %s', 'affiliate-wp' ), $deadline_date );
												?>
											</div>
										<?php endif; ?>
									</div>

									<!-- Requirements List (Single Section) -->
									<div class="p-3 bg-gray-50 rounded-md">
										<ul class="space-y-2">
											<?php
											// Combine all requirements, overdue first, removing duplicates
											$all_requirements  = [];
											$seen_requirements = [];

											// Add overdue requirements first
											foreach ( $past_due_requirements as $req ) {
												if ( ! in_array( $req, $seen_requirements ) ) {
													$all_requirements[]  = [ 'name' => $req, 'overdue' => true ];
													$seen_requirements[] = $req;
												}
											}

											// Add currently due requirements (if not already added as overdue)
											foreach ( $requirements as $req ) {
												if ( ! in_array( $req, $seen_requirements ) ) {
													$all_requirements[]  = [ 'name' => $req, 'overdue' => false ];
													$seen_requirements[] = $req;
												}
											}

											foreach ( $all_requirements as $req_data ) :
												$requirement = $req_data['name'];
												$is_overdue  = $req_data['overdue'];

												// Find if there's an error for this requirement
												$error_reason = null;
												foreach ( $requirement_errors as $error ) {
													if ( isset( $error->requirement ) && $error->requirement === $requirement ) {
														$error_reason = isset( $error->reason ) ? $error->reason : null;
														break;
													}
												}
												?>
												<li class="flex items-start gap-2">
													<div class="flex-1">
														<div class="flex items-center gap-2">
															<span class="text-sm font-medium text-gray-700">
																<?php echo esc_html( $this->format_requirement_name( $requirement ) ); ?>
															</span>
															<?php if ( $is_overdue ) : ?>
																<?php
																affwp_badge(
																	[
																		'text'    => __( 'Overdue', 'affiliate-wp' ),
																		'variant' => 'danger',
																		'size'    => 'xs',
																	]
																);
																?>
															<?php endif; ?>
														</div>
														<?php if ( $error_reason ) : ?>
															<div class="mt-1 text-sm text-gray-500"><?php echo esc_html( $error_reason ); ?></div>
														<?php endif; ?>
													</div>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
							<?php endif; ?>

							<?php
							// Get stored capabilities data
							$stored_capabilities = affwp_get_affiliate_meta( $affiliate_id, 'stripe_capabilities', true );

							// If no stored capabilities, try to get them from the account object
							if ( empty( $stored_capabilities ) && isset( $account_details->capabilities ) ) {
								$stored_capabilities = [];
								foreach ( $account_details->capabilities as $capability => $status ) {
									$stored_capabilities[ $capability ] = $status;
								}
							}

							/**
							 * Filter the capabilities data before display.
							 *
							 * @since 2.29.0
							 * @param array  $stored_capabilities The capabilities data
							 * @param int    $affiliate_id       The affiliate ID
							 * @param object $account_details    The Stripe account details object
							 */
							$stored_capabilities = apply_filters(
								'affwp_stripe_payouts_capabilities_data',
								$stored_capabilities,
								$affiliate_id,
								$account_details
							);
							?>

							<?php if ( ! empty( $stored_capabilities ) ) : ?>
								<?php
								// Group capabilities by status
								$grouped_capabilities = [
									'active'      => [],
									'inactive'    => [],
									'pending'     => [],
									'unrequested' => [],
								];

								foreach ( $stored_capabilities as $capability => $status ) {
									$formatted_name = $this->format_capability_name( $capability );
									if ( $status === 'active' ) {
										$grouped_capabilities['active'][] = $formatted_name;
									} elseif ( $status === 'inactive' ) {
										$grouped_capabilities['inactive'][] = $formatted_name;
									} elseif ( $status === 'pending' ) {
										$grouped_capabilities['pending'][] = $formatted_name;
									} else {
										$grouped_capabilities['unrequested'][] = $formatted_name;
									}
								}
								?>
								<div class="mt-6">
									<h4 class="mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase">
										<?php _e( 'Capabilities', 'affiliate-wp' ); ?>
									</h4>
									<div class="space-y-3">
										<?php if ( ! empty( $grouped_capabilities['inactive'] ) ) : ?>
											<div class="flex items-start space-x-2">
												<!-- Red circle with dash icon -->
												<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-red-500" fill="currentColor">
													<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 8Z"></path>
													<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
												</svg>
												<div>
													<span class="text-sm font-medium text-gray-900"><?php _e( 'Paused', 'affiliate-wp' ); ?></span>
													<div class="text-sm text-gray-600">
														<?php echo esc_html( implode( ', ', $grouped_capabilities['inactive'] ) ); ?>
													</div>
												</div>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $grouped_capabilities['pending'] ) ) : ?>
											<div class="flex items-start space-x-2">
												<!-- Amber clock icon -->
												<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-amber-500" fill="currentColor">
													<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
													<path d="M8 4.5a.75.75 0 0 1 .75.75v2.69l1.78 1.78a.75.75 0 1 1-1.06 1.06l-2-2A.75.75 0 0 1 7.25 8V5.25A.75.75 0 0 1 8 4.5Z"></path>
												</svg>
												<div>
													<span class="text-sm font-medium text-gray-900"><?php _e( 'Pending', 'affiliate-wp' ); ?></span>
													<div class="text-sm text-gray-600">
														<?php echo esc_html( implode( ', ', $grouped_capabilities['pending'] ) ); ?>
													</div>
												</div>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $grouped_capabilities['active'] ) ) : ?>
											<div class="flex items-start space-x-2">
												<!-- Green circle with check icon -->
												<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-green-600" fill="currentColor">
													<path fill-rule="evenodd" clip-rule="evenodd" d="M12.28 5.22a.75.75 0 0 1 0 1.06l-4.75 4.75a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 0 1 1.06-1.06L7 9.44l4.22-4.22a.75.75 0 0 1 1.06 0Z"></path>
													<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
												</svg>
												<div>
													<span class="text-sm font-medium text-gray-900"><?php _e( 'Active', 'affiliate-wp' ); ?></span>
													<div class="text-sm text-gray-600">
														<?php echo esc_html( implode( ', ', $grouped_capabilities['active'] ) ); ?>
													</div>
												</div>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $grouped_capabilities['unrequested'] ) ) : ?>
											<div class="flex items-start space-x-2">
												<!-- Gray circle with dash icon -->
												<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-gray-400" fill="currentColor">
													<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 8Z"></path>
													<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
												</svg>
												<div>
													<span class="text-sm font-medium text-gray-900"><?php _e( 'Not enabled', 'affiliate-wp' ); ?></span>
													<div class="text-sm text-gray-600">
														<?php echo esc_html( implode( ', ', $grouped_capabilities['unrequested'] ) ); ?>
													</div>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php elseif ( $has_capability_restrictions ) : ?>
								<!-- Fallback to old display if no full capabilities data -->
								<div class="p-3 mt-4 bg-amber-50 rounded-md border border-amber-200">
									<p class="mb-2 text-sm font-medium text-amber-800">
										<?php _e( 'Restricted Capabilities:', 'affiliate-wp' ); ?>
									</p>
									<ul class="space-y-1 text-sm text-amber-700">
										<?php foreach ( $restricted_capabilities as $capability => $status ) : ?>
											<li class="flex items-start">
												<span class="mr-2 text-amber-500">•</span>
												<span>
													<?php
													echo esc_html( $this->format_capability_name( $capability ) . ' - ' . ucfirst( $status ) );
													?>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php
							// Show notice if capabilities are empty and we're in test mode
							if ( empty( $stored_capabilities ) && affwp_stripe_payouts_is_test_api_mode() ) :
								?>
								<div class="p-3 mt-4 bg-gray-50 rounded-md border border-gray-200">
									<div class="flex items-start space-x-2">
										<?php
										echo \AffiliateWP\Utils\Icons::generate(
											'information-circle',
											'',
											[
												'class' => 'w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5',
											]
										);
										?>
										<div>
											<p class="mb-1 text-sm font-medium text-gray-700">
												<?php _e( 'Capabilities Not Available in Test Mode', 'affiliate-wp' ); ?>
											</p>
											<p class="text-sm text-gray-600">
												<?php _e( 'Individual payment method capabilities are not provided by Stripe for Express accounts in test mode. This is a Stripe API limitation. In production, payment method statuses will be displayed here.', 'affiliate-wp' ); ?>
											</p>
										</div>
									</div>
								</div>
							<?php endif; ?>

						<?php else : ?>
							<!-- Fallback if no account details available -->
							<p class="text-sm text-gray-600">
								<?php _e( 'Account ID:', 'affiliate-wp' ); ?>
								<code class="px-2 py-0.5 bg-gray-100 rounded"><?php echo esc_html( $account_id ); ?></code>
							</p>
						<?php endif; ?>

					<?php else : ?>
						<!-- Not Connected State Content -->
						<div class="text-sm text-gray-600">
							<p><?php _e( 'Stripe account not connected. This affiliate will receive payouts via your default method.', 'affiliate-wp' ); ?></p>
						</div>
					<?php endif; ?>

					<?php
					/**
					 * Fires after the Stripe account details section.
					 *
					 * @since 2.29.0
					 * @param int    $affiliate_id    The affiliate ID
					 * @param object $account_details The Stripe account details object (may be null)
					 */
					do_action( 'affwp_stripe_payouts_after_account_details', $affiliate_id, $account_details );
					?>
				</div>
				<?php
				$accordion_content = ob_get_clean();
				?>

				<div id="stripe-payouts" class="max-w-3xl">
					<?php
					// Render the accordion using the UI component
					affwp_accordion(
						[
							'id'            => 'stripe_details',
							'header'        => $header_content,
							'content'       => $accordion_content,
							'default_open'  => false,
							'clickable'     => true, // Make entire header clickable like payment methods
							'alpine_var'    => 'accordion_stripe_details',
							'persist_key'   => 'edit_affiliate_stripe_payouts', // Persist the accordion state
							'class'         => 'affwp-ui bg-white rounded-lg border border-gray-200',
							'header_class'  => 'px-4 py-3 transition-colors duration-200',
							'content_class' => '',
						]
					);

					// Add disconnect confirmation modal if connected
					if ( $is_connected ) {
						$modal_id = 'stripe-disconnect-modal-' . $affiliate_id;

						// Build modal content
						ob_start();
						?>
						<div class="p-4 bg-red-50 border border-red-200 rounded-md">
							<p class="text-sm text-red-800">
								<strong><?php _e( 'Warning:', 'affiliate-wp' ); ?></strong>
								<?php _e( 'Once disconnected, this affiliate cannot reconnect with the same Stripe Express account. They will need to create a new Express account to receive payouts again.', 'affiliate-wp' ); ?>
							</p>
						</div>
						<p class="mt-4 text-sm text-gray-600">
							<?php _e( 'This action will:', 'affiliate-wp' ); ?>
						</p>
						<ul class="mt-2 space-y-1 text-sm text-gray-600 list-disc list-inside">
							<li><?php _e( 'Remove the Stripe connection for this affiliate', 'affiliate-wp' ); ?></li>
							<li><?php _e( 'Prevent them from receiving Stripe payouts', 'affiliate-wp' ); ?></li>
							<li><?php _e( 'Require creation of a new Express account if they want to reconnect', 'affiliate-wp' ); ?></li>
						</ul>
						<?php
						$modal_content = ob_get_clean();

						// Render the modal
						affwp_modal(
							[
								'id'             => $modal_id,
								'title'          => __( 'Disconnect Stripe Account?', 'affiliate-wp' ),
								'content'        => $modal_content,
								'variant'        => 'danger',
								'size'           => 'lg',
								'icon'           => 'x-circle',
								'footer_actions' => [
									[
										'text'    => __( 'Cancel', 'affiliate-wp' ),
										'variant' => 'secondary',
									],
									[
										'text'       => __( 'Disconnect Account', 'affiliate-wp' ),
										'variant'    => 'danger',
										'attributes' => [
											'onclick' => sprintf(
												"affwpStripeDisconnect(%d, '%s')",
												$affiliate_id,
												wp_create_nonce( 'affwp_stripe_disconnect_' . $affiliate_id )
											),
										],

									],
								],
							]
						);

						// Add JavaScript to handle modal and disconnect
						?>
						<script>
						window.affwpShowDisconnectModal_<?php echo $affiliate_id; ?> = function() {
							// Use Alpine's $store to trigger the modal
							if (window.Alpine && window.Alpine.store) {
								Alpine.store('modals').open('<?php echo esc_js( $modal_id ); ?>');
							}
						};

						function affwpStripeDisconnect(affiliateId, nonce) {
							jQuery.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'affwp_stripe_disconnect_affiliate',
									affiliate_id: affiliateId,
									nonce: nonce
								},
								success: function(response) {
									if (response.success) {
										if (response.data.redirect) {
											window.location.href = response.data.redirect;
										} else {
											location.reload();
										}
									} else {
										alert(response.data.message || 'Error disconnecting affiliate');
										if (window.Alpine && window.Alpine.store) {
											Alpine.store('modals').close('<?php echo esc_js( $modal_id ); ?>');
										}
									}
								},
								error: function() {
									alert('Error disconnecting affiliate');
									if (window.Alpine && window.Alpine.store) {
										Alpine.store('modals').close('<?php echo esc_js( $modal_id ); ?>');
									}
								}
							});
						}
						</script>
						<?php
					}
					?>
				</div>
			</td>
		</tr>

		<?php
	}


	/**
	 * Display Stripe payment details on the view payout screen
	 *
	 * @since 2.29.0
	 * @param object $payout The payout object
	 */
	public function payout_stripe_payment_details( $payout ) {
		// Only show for Stripe payouts
		if ( ! $payout || $payout->payout_method !== 'stripe' ) {
			return;
		}

		// Get all referrals in this payout - referrals are stored as comma-separated string
		$referral_ids = ! empty( $payout->referrals ) ? array_map( 'intval', explode( ',', $payout->referrals ) ) : [];
		if ( empty( $referral_ids ) ) {
			return;
		}

		// Aggregate transfer data from all referrals
		$transfers = $this->get_payout_stripe_transfers( $referral_ids );
		if ( empty( $transfers ) ) {
			return;
		}

		// Calculate aggregate status
		$aggregate_status = $this->get_aggregate_transfer_status( $transfers );
		$total_amount     = array_sum( wp_list_pluck( $transfers, 'amount' ) );

		// Get test mode status for URL generation
		$is_test_mode = affwp_stripe_payouts_is_testing_mode();

		?>
		<tr class="form-row">
			<th scope="row">
				<?php _e( 'Stripe Transfer ID', 'affiliate-wp' ); ?>
			</th>
			<td>
				<?php if ( count( $transfers ) === 1 ) : ?>
					<!-- Single Transfer -->
					<?php $transfer = $transfers[0]; ?>
					<a href="<?php echo esc_url( $is_test_mode ? 'https://dashboard.stripe.com/test/transfers/' . $transfer['transfer_id'] : 'https://dashboard.stripe.com/transfers/' . $transfer['transfer_id'] ); ?>"
						target="_blank" title="<?php esc_attr_e( 'View transfer in Stripe dashboard', 'affiliate-wp' ); ?>">
						<code><?php echo esc_html( $transfer['transfer_id'] ); ?></code>
					</a>
				<?php else : ?>
					<!-- Multiple Transfers -->
					<p><?php printf( _n( '%d transfer', '%d transfers', count( $transfers ), 'affiliate-wp' ), count( $transfers ) ); ?></p>
					<ul style="margin: 10px 0; list-style: none; padding-left: 0;">
						<?php foreach ( $transfers as $transfer ) : ?>
							<li style="margin-bottom: 5px;">
								<a href="<?php echo esc_url( $is_test_mode ? 'https://dashboard.stripe.com/test/transfers/' . $transfer['transfer_id'] : 'https://dashboard.stripe.com/transfers/' . $transfer['transfer_id'] ); ?>"
									target="_blank" title="<?php esc_attr_e( 'View transfer in Stripe dashboard', 'affiliate-wp' ); ?>">
									<code><?php echo esc_html( $transfer['transfer_id'] ); ?></code>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get Stripe transfer data for all referrals in a payout
	 *
	 * @since 2.29.0
	 * @param array $referral_ids Array of referral IDs
	 * @return array Array of transfer data
	 */
	private function get_payout_stripe_transfers( $referral_ids ) {
		$transfers = [];

		foreach ( $referral_ids as $referral_id ) {
			$referral = affwp_get_referral( $referral_id );
			if ( ! $referral ) {
				continue;
			}

			// Check for transfer data
			$transfer_id  = affwp_stripe_payouts_get_referral_transfer_id( $referral_id );
			$has_failed   = affwp_stripe_payouts_referral_has_failed_transfer( $referral_id );
			$has_reversed = affwp_stripe_payouts_referral_has_reversed_transfer( $referral_id );

			if ( $transfer_id || $has_failed || $has_reversed ) {
				$status      = 'completed';
				$status_text = __( 'Completed', 'affiliate-wp' );

				if ( $has_failed ) {
					$status      = 'failed';
					$status_text = __( 'Failed', 'affiliate-wp' );
					$transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_failed', true );
				} elseif ( $has_reversed ) {
					$status      = 'reversed';
					$status_text = __( 'Reversed', 'affiliate-wp' );
					$transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_reversed', true );
				}

				$transfers[] = [
					'referral_id' => $referral_id,
					'transfer_id' => $transfer_id,
					'amount'      => $referral->amount,
					'status'      => $status,
					'status_text' => $status_text,
				];
			}
		}

		return $transfers;
	}

	/**
	 * Get aggregate status for multiple transfers
	 *
	 * @since 2.29.0
	 * @param array $transfers Array of transfer data
	 * @return array Aggregate status information
	 */
	private function get_aggregate_transfer_status( $transfers ) {
		$statuses  = wp_list_pluck( $transfers, 'status' );
		$completed = array_filter(
			$statuses,
			function ( $status ) {
				return $status === 'completed';
			}
		);
		$failed    = array_filter(
			$statuses,
			function ( $status ) {
				return $status === 'failed';
			}
		);
		$reversed  = array_filter(
			$statuses,
			function ( $status ) {
				return $status === 'reversed';
			}
		);

		$total           = count( $transfers );
		$completed_count = count( $completed );
		$failed_count    = count( $failed );
		$reversed_count  = count( $reversed );

		if ( $completed_count === $total ) {
			return [
				'class'         => 'completed',
				'text'          => __( 'All Transfers Completed', 'affiliate-wp' ),
				'has_issues'    => false,
				'issue_message' => '',
			];
		} elseif ( $failed_count > 0 || $reversed_count > 0 ) {
			$issue_parts = [];
			if ( $failed_count > 0 ) {
				$issue_parts[] = sprintf( _n( '%d failed transfer', '%d failed transfers', $failed_count, 'affiliate-wp' ), $failed_count );
			}
			if ( $reversed_count > 0 ) {
				$issue_parts[] = sprintf( _n( '%d reversed transfer', '%d reversed transfers', $reversed_count, 'affiliate-wp' ), $reversed_count );
			}

			return [
				'class'         => 'mixed',
				'text'          => __( 'Mixed Status', 'affiliate-wp' ),
				'has_issues'    => true,
				'issue_message' => sprintf( __( 'This payout has %s. Please review individual transfers.', 'affiliate-wp' ), implode( ' and ', $issue_parts ) ),
			];
		} else {
			return [
				'class'         => 'completed',
				'text'          => __( 'Transfers Completed', 'affiliate-wp' ),
				'has_issues'    => false,
				'issue_message' => '',
			];
		}
	}

	/**
	 * Add Stripe connection status column to the affiliates table
	 *
	 * @since 2.29.0
	 * @param array  $prepared_columns Prepared columns
	 * @param array  $columns Original columns
	 * @param object $list_table_instance List table instance
	 * @return array Modified columns
	 */
	public function add_stripe_status_column( $prepared_columns, $columns, $list_table_instance ) {
		// Only add if Stripe is configured
		if ( ! affwp_stripe_payouts_is_configured() ) {
			return $prepared_columns;
		}

		// Insert the Stripe status column after the status column
		$new_columns = [];
		foreach ( $prepared_columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'status' === $key ) {
				$new_columns['stripe_status'] = __( 'Stripe Status', 'affiliate-wp' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render the Stripe connection status column content
	 *
	 * @since 2.29.0
	 * @param string $value The column value
	 * @param object $affiliate The affiliate object
	 * @return string The column content
	 */
	public function render_stripe_status_column( $value, $affiliate ) {
		// Only show if Stripe is configured.
		if ( ! affwp_stripe_payouts_is_configured() ) {
			return '—';
		}

		$affiliate_id = $affiliate->affiliate_id;
		$is_connected = affwp_stripe_payouts_is_affiliate_connected( $affiliate_id );
		$account_id   = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

		// Not connected - just show em dash with tooltip
		if ( ! $is_connected ) {
			$tooltip_html = affwp_tooltip(
				[
					'title'   => __( 'Not Connected', 'affiliate-wp' ),
					'content' => __( 'Affiliate has not connected with Stripe', 'affiliate-wp' ),
					'type'    => 'info',
				]
			);

			return sprintf(
				'<span class="inline-block text-gray-500 cursor-help" data-tooltip-html="%s">—</span>',
				esc_attr( $tooltip_html )
			);
		}

		// Check if webhooks are configured to show data freshness
		$webhook_configured = ! empty( affwp_stripe_payouts_get_webhook_secret() );

		// Get detailed account status with affiliate ID for webhook data.
		$status_data  = $this->get_detailed_stripe_account_status( $account_id, $affiliate_id );
		$status       = $status_data['status'];
		$tooltip      = $status_data['tooltip'];
		$requirements = isset( $status_data['requirements'] ) ? $status_data['requirements'] : [];
		$capabilities = isset( $status_data['capabilities'] ) ? $status_data['capabilities'] : [];

		// Get last updated time if webhooks are configured
		$last_updated = '';
		if ( $webhook_configured ) {
			$requirements_data = affwp_get_affiliate_meta( $affiliate_id, 'stripe_requirements', true );
			if ( ! empty( $requirements_data['last_updated'] ) ) {
				$last_updated = sprintf(
					__( 'Last updated: %s ago', 'affiliate-wp' ),
					human_time_diff( $requirements_data['last_updated'], current_time( 'timestamp' ) )
				);
			}
		}

		// Get capability check for more detailed status
		$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );

		// Handle WP_Error response
		if ( is_wp_error( $capability_check ) ) {
			// Show error state with cleaner message
			$error_message = $capability_check->get_error_message();

			// Extract key suffix from error message if it contains an API key
			if ( preg_match( '/\'(sk_[a-zA-Z0-9_]+)\'/', $error_message, $matches ) ) {
				$api_key = $matches[1];
				// Get last 6 characters of the key for identification
				$key_suffix = substr( $api_key, -6 );

				// Create a cleaner error message
				$clean_message = sprintf(
					__( 'The API key ending in "%s" does not have access to the connected account or the account no longer exists. Application access may have been revoked.', 'affiliate-wp' ),
					$key_suffix
				);
			} else {
				// Fallback to original message if pattern doesn't match
				$clean_message = $error_message;
			}

			// Format the error message with Tailwind classes for proper display
			$formatted_error = sprintf(
				'<span class="text-gray-800 block">%s</span>',
				esc_html( $clean_message )
			);

			$formatted_footer = sprintf(
				'<span class="text-gray-600">%s</span>',
				__( 'This usually means the API keys have been changed or the affiliate needs to reconnect.', 'affiliate-wp' )
			);

			$tooltip_html = affwp_tooltip(
				[
					'title'   => __( 'API Key Error', 'affiliate-wp' ),
					'content' => $formatted_error,
					'type'    => 'error',
					'footer'  => $formatted_footer,
				]
			);

			// Use badge for consistency
			ob_start();
			affwp_badge(
				[
					'text'    => __( 'Error', 'affiliate-wp' ),
					'variant' => 'danger',
					'size'    => 'xs',
				]
			);
			$badge_html = ob_get_clean();

			return sprintf(
				'<span class="cursor-help inline-block" data-tooltip-html="%s">%s</span>',
				esc_attr( $tooltip_html ),
				$badge_html
			);
		}

		// Build text-based status display
		$status_text    = '';
		$text_class     = '';
		$tooltip_config = [];

		// Add account ID and View Details link to footer
		$edit_link    = admin_url( 'admin.php?page=affiliate-wp-affiliates&action=edit_affiliate&affiliate_id=' . $affiliate_id . '#stripe-payouts' );
		$footer_parts = [];

		// Add account ID in gray
		$footer_parts[] = sprintf( '<span class="text-gray-500">%s</span>', esc_html( $account_id ) );

		if ( $last_updated ) {
			$footer_parts[] = $last_updated;
		}
		$footer_parts[] = affwp_render_link(
			[
				'href' => $edit_link,
				'text' => __( 'View Details', 'affiliate-wp' ),
			]
		);
		$footer_content = implode( ' • ', $footer_parts );

		// Determine status text and styling based on capabilities
		if ( ! $capability_check['can_receive_transfers'] ) {
			// Cannot receive any payments - account is restricted
			// Use badge for consistency with Stripe's UI
			ob_start();
			affwp_badge(
				[
					'text'    => __( 'Restricted', 'affiliate-wp' ),
					'variant' => 'danger',
					'size'    => 'xs',
				]
			);
			$badge_html = ob_get_clean();

			$formatted_requirements = [];
			if ( ! empty( $requirements ) ) {
				foreach ( array_slice( $requirements, 0, 3 ) as $requirement ) {
					$formatted_requirements[] = $this->format_requirement_name( $requirement );
				}
				if ( count( $requirements ) > 3 ) {
					$formatted_requirements[] = sprintf( __( '+ %d more', 'affiliate-wp' ), count( $requirements ) - 3 );
				}
			}

			// Build detailed status info for tooltip
			$status_details   = [];
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-red-600 font-medium">%s</span></div>',
				__( 'Payouts', 'affiliate-wp' ),
				__( 'paused', 'affiliate-wp' )
			);
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-red-600 font-medium">%s</span></div>',
				__( 'Payments', 'affiliate-wp' ),
				__( 'paused', 'affiliate-wp' )
			);

			// Custom pause/restricted icon (red circle with horizontal line)
			$restricted_icon = '<svg width="20" height="20" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-red-500" fill="currentColor">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 8Z"></path>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
			</svg>';

			$tooltip_config = [
				'title'   => __( 'Restricted', 'affiliate-wp' ),
				'content' => implode( '', $status_details ),
				'items'   => ! empty( $formatted_requirements ) ? $formatted_requirements : [ __( 'Account verification required', 'affiliate-wp' ) ],
				'type'    => 'error',
				'footer'  => $footer_content,
				'icon'    => $restricted_icon,
			];

			$tooltip_html = affwp_tooltip( $tooltip_config );

			return sprintf(
				'<span class="cursor-help inline-block" data-tooltip-html="%s">%s</span>',
				esc_attr( $tooltip_html ),
				$badge_html
			);
		} elseif ( $capability_check['payouts_pausing_soon'] || $capability_check['payments_pausing_soon'] ) {
			// Pausing soon - show red badge with "Restricted" text to match Stripe
			ob_start();
			affwp_badge(
				[
					'text'    => __( 'Restricted', 'affiliate-wp' ),
					'variant' => 'danger',
					'size'    => 'xs',
				]
			);
			$badge_html = ob_get_clean();

			// Build detailed status for tooltip
			$status_details = [];
			if ( $capability_check['payouts_pausing_soon'] ) {
				$status_details[] = sprintf(
					'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-red-600 font-medium">%s</span></div>',
					__( 'Payouts', 'affiliate-wp' ),
					__( 'pausing soon', 'affiliate-wp' )
				);
			} else {
				$status_details[] = sprintf(
					'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-gray-700">%s</span></div>',
					__( 'Payouts', 'affiliate-wp' ),
					__( 'active', 'affiliate-wp' )
				);
			}

			if ( $capability_check['payments_pausing_soon'] ) {
				$status_details[] = sprintf(
					'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-red-600 font-medium">%s</span></div>',
					__( 'Payments', 'affiliate-wp' ),
					__( 'pausing soon', 'affiliate-wp' )
				);
			} else {
				$status_details[] = sprintf(
					'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-gray-700">%s</span></div>',
					__( 'Payments', 'affiliate-wp' ),
					__( 'active', 'affiliate-wp' )
				);
			}

			if ( ! empty( $capability_check['current_deadline'] ) ) {
				$deadline         = date_i18n( get_option( 'date_format' ), $capability_check['current_deadline'] );
				$status_details[] = sprintf(
					'<div class="mt-2 text-sm text-red-600">%s</div>',
					sprintf( __( 'Deadline: %s', 'affiliate-wp' ), $deadline )
				);
			}

			// Custom pause/restricted icon (red circle with horizontal line)
			$restricted_icon = '<svg width="20" height="20" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5 text-red-500" fill="currentColor">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 8Z"></path>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5A6.5 6.5 0 0 0 14.5 8c0-3.592-2.9-6.5-6.5-6.5A6.5 6.5 0 0 0 1.5 8 6.5 6.5 0 0 0 8 14.5ZM8 16a8 8 0 0 0 8-8c0-4.419-3.57-8-8-8a8 8 0 0 0-8 8 8 8 0 0 0 8 8Z"></path>
			</svg>';

			$tooltip_config = [
				'title'   => __( 'Restricted', 'affiliate-wp' ),
				'content' => implode( '', $status_details ),
				'type'    => 'error',
				'icon'    => $restricted_icon,
				'footer'  => $footer_content,
			];

			$tooltip_html = affwp_tooltip( $tooltip_config );

			return sprintf(
				'<span class="cursor-help inline-block" data-tooltip-html="%s">%s</span>',
				esc_attr( $tooltip_html ),
				$badge_html
			);
		} elseif ( ! $capability_check['can_payout'] ) {
			// Payouts paused but can receive transfers
			ob_start();
			affwp_badge(
				[
					'text'    => __( 'Payouts Paused', 'affiliate-wp' ),
					'variant' => 'warning',
					'size'    => 'xs',
				]
			);
			$badge_html = ob_get_clean();

			// Build detailed status for tooltip
			$status_details   = [];
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-orange-600 font-medium">%s</span></div>',
				__( 'Payouts', 'affiliate-wp' ),
				__( 'paused', 'affiliate-wp' )
			);
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-gray-700">%s</span></div>',
				__( 'Payments', 'affiliate-wp' ),
				__( 'active', 'affiliate-wp' )
			);

			$tooltip_config = [
				'title'   => __( 'Payouts Paused', 'affiliate-wp' ),
				'content' => implode( '', $status_details ) . '<p class="mt-2 text-sm">' . __( 'Can receive transfers but cannot withdraw funds to bank', 'affiliate-wp' ) . '</p>',
				'type'    => 'warning',
				'footer'  => $footer_content,
			];

			$tooltip_html = affwp_tooltip( $tooltip_config );

			return sprintf(
				'<span class="cursor-help inline-block" data-tooltip-html="%s">%s</span>',
				esc_attr( $tooltip_html ),
				$badge_html
			);
		} else {
			// Everything active - show success badge
			ob_start();
			affwp_badge(
				[
					'text'    => __( 'Enabled', 'affiliate-wp' ),
					'variant' => 'success',
					'size'    => 'xs',
				]
			);
			$badge_html = ob_get_clean();

			// Build detailed status for tooltip
			$status_details   = [];
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-gray-700">%s</span></div>',
				__( 'Payouts', 'affiliate-wp' ),
				__( 'active', 'affiliate-wp' )
			);
			$status_details[] = sprintf(
				'<div class="flex items-center gap-2"><span class="text-gray-600">%s:</span><span class="text-gray-700">%s</span></div>',
				__( 'Payments', 'affiliate-wp' ),
				__( 'active', 'affiliate-wp' )
			);

			$tooltip_config = [
				'title'   => __( 'Enabled', 'affiliate-wp' ),
				'content' => implode( '', $status_details ),
				'type'    => 'success',
				'footer'  => $footer_content,
			];

			$tooltip_html = affwp_tooltip( $tooltip_config );

			return sprintf(
				'<span class="cursor-help inline-block" data-tooltip-html="%s">%s</span>',
				esc_attr( $tooltip_html ),
				$badge_html
			);
		}

		// This should never be reached now, but keeping as fallback
		$output = '';

		// Add webhook indicator if not configured
		if ( ! $webhook_configured && $is_connected ) {
			$info_svg  = \AffiliateWP\Utils\Icons::generate(
				'information-circle',
				'',
				[
					'width'  => '12',
					'height' => '12',
				]
			);
			$info_html = sprintf(
				'<span class="affwp-status-icon" style="color: #6b7280; cursor: help; display: inline-block; margin-left: 4px;" data-tooltip-html="%s">%s</span>',
				esc_attr( affwp_tooltip( __( 'Enable webhooks for real-time updates', 'affiliate-wp' ) ) ),
				$info_svg
			);
			$output   .= $info_html;
		}

		return $output;
	}

	/**
	 * Format requirement name for display
	 *
	 * @since 2.29.0
	 * @param string $requirement The requirement key
	 * @return string Formatted requirement name
	 */
	private function format_requirement_name( $requirement ) {
		$requirements_map = [
			'individual.verification.document'            => __( 'Identity verification', 'affiliate-wp' ),
			'individual.verification.additional_document' => __( 'Additional document', 'affiliate-wp' ),
			'individual.address.line1'                    => __( 'Address', 'affiliate-wp' ),
			'individual.dob.day'                          => __( 'Date of birth', 'affiliate-wp' ),
			'individual.ssn_last_4'                       => __( 'SSN last 4', 'affiliate-wp' ),
			'business_profile.url'                        => __( 'Business website', 'affiliate-wp' ),
			'external_account'                            => __( 'Bank account', 'affiliate-wp' ),
			'tos_acceptance.date'                         => __( 'Terms acceptance', 'affiliate-wp' ),
		];

		return isset( $requirements_map[ $requirement ] ) ? $requirements_map[ $requirement ] : $requirement;
	}

	/**
	 * Format capability name for display
	 *
	 * @since 2.29.0
	 * @param string $capability The capability key
	 * @return string Formatted capability name
	 */
	private function format_capability_name( $capability ) {
		$capability_map = [
			'card_payments'                => __( 'Card payments', 'affiliate-wp' ),
			'transfers'                    => __( 'Transfers', 'affiliate-wp' ),
			'afterpay_clearpay_payments'   => __( 'Afterpay/Clearpay', 'affiliate-wp' ),
			'eps_payments'                 => __( 'EPS payments', 'affiliate-wp' ),
			'giropay_payments'             => __( 'Giropay', 'affiliate-wp' ),
			'bancontact_payments'          => __( 'Bancontact', 'affiliate-wp' ),
			'ideal_payments'               => __( 'iDEAL', 'affiliate-wp' ),
			'p24_payments'                 => __( 'Przelewy24', 'affiliate-wp' ),
			'sepa_debit_payments'          => __( 'SEPA Direct Debit', 'affiliate-wp' ),
			'sofort_payments'              => __( 'Sofort', 'affiliate-wp' ),
			'us_bank_account_ach_payments' => __( 'ACH payments', 'affiliate-wp' ),
			'klarna_payments'              => __( 'Klarna', 'affiliate-wp' ),
			'link_payments'                => __( 'Link', 'affiliate-wp' ),
			'apple_pay'                    => __( 'Apple Pay', 'affiliate-wp' ),
			'google_pay'                   => __( 'Google Pay', 'affiliate-wp' ),
		];

		// Clean up the capability name if not in map
		if ( isset( $capability_map[ $capability ] ) ) {
			return $capability_map[ $capability ];
		}

		// Convert snake_case to Title Case as fallback
		$formatted = str_replace( '_', ' ', $capability );
		$formatted = ucwords( $formatted );
		return $formatted;
	}

	/**
	 * Add Stripe status column to sortable columns
	 *
	 * @since 2.29.0
	 * @param array $columns The sortable columns
	 * @return array Modified sortable columns
	 */
	public function add_stripe_status_sortable_column( $columns ) {
		// Only add if Stripe is configured
		if ( ! affwp_stripe_payouts_is_configured() ) {
			return $columns;
		}

		$columns['stripe_status'] = [ 'stripe_status', false ];
		return $columns;
	}

	/**
	 * Get detailed Stripe account status for an affiliate
	 *
	 * @since 2.29.0
	 * @param string $account_id The Stripe account ID
	 * @return array Array with 'status' and 'tooltip' keys
	 */
	private function get_detailed_stripe_account_status( $account_id, $affiliate_id = 0 ) {
		if ( empty( $account_id ) ) {
			return [
				'status'       => 'not_connected',
				'tooltip'      => __( 'No Stripe account connected', 'affiliate-wp' ),
				'requirements' => [],
			];
		}

		// Cache the status for performance (5 minute cache)
		$cache_key     = 'affwp_stripe_detailed_status_' . $account_id;
		$cached_status = get_transient( $cache_key );

		if ( false !== $cached_status ) {
			return $cached_status;
		}

		// First check if we have webhook-stored requirements data
		if ( $affiliate_id ) {
			$stored_requirements = affwp_get_affiliate_meta( $affiliate_id, 'stripe_requirements', true );
			$stored_capabilities = affwp_get_affiliate_meta( $affiliate_id, 'stripe_capabilities', true );

			if ( $stored_requirements && isset( $stored_requirements['last_updated'] ) ) {
				// Use stored data if it's less than 1 hour old
				if ( ( current_time( 'timestamp' ) - $stored_requirements['last_updated'] ) < HOUR_IN_SECONDS ) {
					$status_data = $this->determine_status_from_requirements( $stored_requirements );
					// Add capabilities to status data
					if ( ! empty( $stored_capabilities ) ) {
						$status_data['capabilities'] = $stored_capabilities;
					}
					set_transient( $cache_key, $status_data, 5 * MINUTE_IN_SECONDS );
					return $status_data;
				}
			}
		}

		// Default status
		$status_data = [
			'status'       => 'active',
			'tooltip'      => __( 'Account active and ready for payouts', 'affiliate-wp' ),
			'requirements' => [],
		];

		try {
			// Initialize Stripe API
			if ( ! affwp_stripe_payouts_init_api() ) {
				return $status_data;
			}

			// Get account details
			$account = \Stripe\Account::retrieve( $account_id );

			// Extract capabilities if available
			$capabilities = [];
			if ( isset( $account->capabilities ) ) {
				foreach ( $account->capabilities as $capability => $status ) {
					$capabilities[ $capability ] = $status;
				}
			}

			// Determine status based on account properties
			if ( isset( $account->requirements->disabled_reason ) && ! empty( $account->requirements->disabled_reason ) ) {
				// Account is restricted/suspended
				$disabled_reason = $account->requirements->disabled_reason;

				if ( in_array( $disabled_reason, [ 'rejected.fraud', 'rejected.terms_of_service', 'rejected.listed' ] ) ) {
					$status_data = [
						'status'       => 'suspended',
						'tooltip'      => sprintf( __( 'Account suspended: %s', 'affiliate-wp' ), $disabled_reason ),
						'requirements' => [],
						'capabilities' => $capabilities,
					];
				} else {
					$status_data = [
						'status'       => 'restricted',
						'tooltip'      => sprintf( __( 'Account restricted: %s', 'affiliate-wp' ), $disabled_reason ),
						'requirements' => [],
						'capabilities' => $capabilities,
					];
				}
			} elseif ( ! empty( $account->requirements->currently_due ) ) {
				// Account has requirements that need to be completed
				$requirements_count = count( $account->requirements->currently_due );
				$status_data        = [
					'status'       => 'action_required',
					'tooltip'      => sprintf(
						_n(
							'%d requirement needs completion',
							'%d requirements need completion',
							$requirements_count,
							'affiliate-wp'
						),
						$requirements_count
					),
					'requirements' => $account->requirements->currently_due,
					'capabilities' => $capabilities,
				];
			} elseif ( ! $account->details_submitted ) {
				// Account connected but onboarding not complete
				$status_data = [
					'status'       => 'pending',
					'tooltip'      => __( 'Onboarding not completed', 'affiliate-wp' ),
					'requirements' => [],
					'capabilities' => $capabilities,
				];
			} elseif ( ! $account->payouts_enabled ) {
				// Account complete but payouts not enabled
				$status_data = [
					'status'       => 'restricted',
					'tooltip'      => __( 'Payouts not enabled', 'affiliate-wp' ),
					'requirements' => [],
					'capabilities' => $capabilities,
				];
			} elseif ( $account->payouts_enabled && $account->charges_enabled ) {
				// Account is fully active
				$status_data = [
					'status'       => 'active',
					'tooltip'      => __( 'Account active and ready for payouts', 'affiliate-wp' ),
					'requirements' => [],
					'capabilities' => $capabilities,
				];
			} else {
				// Connected but some limitations - default to active
				$status_data = [
					'status'       => 'active',
					'tooltip'      => __( 'Account active', 'affiliate-wp' ),
					'requirements' => [],
					'capabilities' => $capabilities,
				];
			}
		} catch ( \Exception $e ) {
			// If we can't check the account status, default to active
			$status_data = [
				'status'       => 'active',
				'tooltip'      => __( 'Account connected', 'affiliate-wp' ),
				'requirements' => [],
			];
		}

		// Cache for 5 minutes
		set_transient( $cache_key, $status_data, 5 * MINUTE_IN_SECONDS );

		return $status_data;
	}

	/**
	 * Determine status from stored requirements data
	 *
	 * @since 2.29.0
	 * @param array $requirements The stored requirements data
	 * @return array Status data
	 */
	private function determine_status_from_requirements( $requirements ) {
		$status_data = [
			'status'       => 'active',
			'tooltip'      => __( 'Account active and ready for payouts', 'affiliate-wp' ),
			'requirements' => [],
		];

		if ( ! empty( $requirements['disabled_reason'] ) ) {
			$disabled_reason = $requirements['disabled_reason'];
			if ( in_array( $disabled_reason, [ 'rejected.fraud', 'rejected.terms_of_service', 'rejected.listed' ] ) ) {
				$status_data = [
					'status'       => 'suspended',
					'tooltip'      => sprintf( __( 'Account suspended: %s', 'affiliate-wp' ), $disabled_reason ),
					'requirements' => [],
				];
			} else {
				$status_data = [
					'status'       => 'restricted',
					'tooltip'      => sprintf( __( 'Account restricted: %s', 'affiliate-wp' ), $disabled_reason ),
					'requirements' => [],
				];
			}
		} elseif ( ! empty( $requirements['currently_due'] ) ) {
			$requirements_count = count( $requirements['currently_due'] );
			$status_data        = [
				'status'       => 'action_required',
				'tooltip'      => sprintf(
					_n(
						'%d requirement needs completion',
						'%d requirements need completion',
						$requirements_count,
						'affiliate-wp'
					),
					$requirements_count
				),
				'requirements' => $requirements['currently_due'],
			];
		} elseif ( empty( $requirements['details_submitted'] ) ) {
			$status_data = [
				'status'       => 'pending',
				'tooltip'      => __( 'Onboarding not completed', 'affiliate-wp' ),
				'requirements' => [],
			];
		} elseif ( empty( $requirements['payouts_enabled'] ) ) {
			$status_data = [
				'status'       => 'restricted',
				'tooltip'      => __( 'Payouts not enabled', 'affiliate-wp' ),
				'requirements' => [],
			];
		} elseif ( ! empty( $requirements['payouts_enabled'] ) && ! empty( $requirements['charges_enabled'] ) ) {
			$status_data = [
				'status'       => 'active',
				'tooltip'      => __( 'Account active and ready for payouts', 'affiliate-wp' ),
				'requirements' => [],
			];
		}

		return $status_data;
	}


	/**
	 * Add the Pay via Stripe action to the referral actions
	 *
	 * @access public
	 * @since 2.29.0
	 * @param array           $actions The current actions
	 * @param \AffWP\Referral $referral The referral object
	 * @return array The modified actions
	 */
	public function add_pay_action( $actions, $referral ) {
		// Only add the action for unpaid referrals
		if ( 'unpaid' !== $referral->status ) {
			return $actions;
		}

		// Check if Stripe is configured and admin is connected
		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			return $actions;
		}

		// Check if affiliate is connected to Stripe
		$affiliate_id = $referral->affiliate_id;
		if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
			return $actions;
		}

		// Check if this referral has been previously paid via Stripe
		$has_stripe_payment    = affwp_stripe_payouts_referral_has_stripe_payment( $referral->referral_id );
		$has_failed_transfer   = affwp_stripe_payouts_referral_has_failed_transfer( $referral->referral_id );
		$has_reversed_transfer = affwp_stripe_payouts_referral_has_reversed_transfer( $referral->referral_id );

		if ( $has_stripe_payment ) {
			// This referral was previously paid via Stripe, show appropriate messaging
			$transfer_id = affwp_stripe_payouts_get_referral_transfer_id( $referral->referral_id );

			if ( $has_failed_transfer || $has_reversed_transfer ) {
				// Show retry button for failed or reversed transfers
				$retry_reason = $has_failed_transfer
					? __( 'Previous Stripe transfer failed - click to retry payment', 'affiliate-wp' )
					: __( 'Previous Stripe transfer was reversed - click to retry payment', 'affiliate-wp' );

				$affiliate_username = affwp_get_affiliate_username( $referral->affiliate_id );
				// Escape values for use in JavaScript function call
				$actions[] = sprintf(
					'<a href="#"
						x-data
						@click.prevent="window.affwpStripePayment.openModal(%d, %d, %f, &quot;%s&quot;, &quot;%s&quot;, &quot;%s&quot;)"
						class="affwp-stripe-pay-button affwp-stripe-retry"
						data-referral-id="%s"
						data-amount="%s"
						data-affiliate-id="%s"
						data-stripe-state="%s"
						data-transfer-id="%s"
						title="%s">%s</a>',
					$referral->referral_id,
					$referral->affiliate_id,
					floatval( $referral->amount ),
					esc_js( $affiliate_username ),
					esc_js( $has_failed_transfer ? 'failed' : 'reversed' ),
					esc_js( $transfer_id ),
					esc_attr( $referral->referral_id ),
					esc_attr( $referral->amount ),
					esc_attr( $affiliate_id ),
					esc_attr( $has_failed_transfer ? 'failed' : 'reversed' ),
					esc_attr( $transfer_id ),
					esc_attr( $retry_reason ),
					esc_html__( 'Pay via Stripe', 'affiliate-wp' )
				);
			}
		} else {
			// Normal case - show regular pay button with Alpine
			$affiliate_username = affwp_get_affiliate_username( $referral->affiliate_id );

			$actions[] = sprintf(
				'<a href="#"
					x-data
					@click.prevent="window.affwpStripePayment.openModal(%d, %d, %f, &quot;%s&quot;, null, null)"
					class="affwp-stripe-pay-button"
					data-referral-id="%s"
					data-amount="%s"
					data-affiliate-id="%s">%s</a>',
				$referral->referral_id,
				$referral->affiliate_id,
				floatval( $referral->amount ),
				esc_js( $affiliate_username ),
				esc_attr( $referral->referral_id ),
				esc_attr( $referral->amount ),
				esc_attr( $affiliate_id ),
				esc_html__( 'Pay via Stripe', 'affiliate-wp' )
			);
		}

		return $actions;
	}

	/**
	 * Add Stripe payout as a batch export type
	 *
	 * @since 2.29.0
	 * @param array $export_types The current export types
	 * @return array The modified export types
	 */
	public function add_batch_export_type( $export_types ) {
		// Only add if Stripe is configured and admin is connected
		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			return $export_types;
		}

		$export_types['stripe_payouts'] = [
			'label' => __( 'Stripe Payouts', 'affiliate-wp' ),
			'class' => 'AffiliateWP_Stripe_Payouts_Batch_Process',
		];

		return $export_types;
	}

	/**
	 * Process payout via AJAX
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_payout_ajax() {
		// Turn off PHP errors
		// @ini_set( 'display_errors', 0 );

		// Validate AJAX request context
		$ajax_validation = affwp_stripe_payouts_validate_ajax_request( 'manage_affiliate_options' );
		if ( is_wp_error( $ajax_validation ) ) {
			wp_send_json_error( [ 'message' => $ajax_validation->get_error_message() ] );
		}

		// Validate nonce
		$nonce_validation = affwp_stripe_payouts_validate_nonce(
			isset( $_POST['nonce'] ) ? $_POST['nonce'] : '',
			'affwp-stripe-payouts-nonce'
		);
		if ( is_wp_error( $nonce_validation ) ) {
			wp_send_json_error( [ 'message' => $nonce_validation->get_error_message() ] );
		}

		// Rate limiting for payout operations - increased to 100 payouts per hour for legitimate admin use
		// This is just to prevent accidental spam/loops, not to restrict normal usage
		$rate_limit_check = affwp_stripe_payouts_check_rate_limit( 'payout', null, 100, 3600 );
		if ( is_wp_error( $rate_limit_check ) ) {
			wp_send_json_error( [ 'message' => $rate_limit_check->get_error_message() ] );
		}

		// Check required fields
		if ( ! isset( $_POST['referral_id'] ) || ! isset( $_POST['affiliate_id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Required fields are missing.', 'affiliate-wp' ) ] );
		}

		// Validate referral ID
		$referral_id = affwp_stripe_payouts_validate_referral_id( $_POST['referral_id'] );
		if ( is_wp_error( $referral_id ) ) {
			wp_send_json_error( [ 'message' => $referral_id->get_error_message() ] );
		}

		// Validate affiliate ID
		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_POST['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			wp_send_json_error( [ 'message' => $affiliate_id->get_error_message() ] );
		}

		// Get referral
		$referral = affwp_get_referral( $referral_id );

		// Check if referral exists
		if ( ! $referral ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Referral #%d not found.', 'affiliate-wp' ), $referral_id ) ] );
		}

		// Check if the referral is already paid
		if ( 'paid' === $referral->status ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Referral #%d is already paid.', 'affiliate-wp' ), $referral_id ) ] );
		}

		// Check if affiliate exists and is active
		$affiliate = affwp_get_affiliate( $affiliate_id );
		if ( ! $affiliate || 'active' !== $affiliate->status ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Affiliate #%d is not active.', 'affiliate-wp' ), $affiliate_id ) ] );
		}

		// Check if Stripe is configured
		if ( ! function_exists( 'affwp_stripe_payouts_is_configured' ) || ! affwp_stripe_payouts_is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'Stripe is not properly configured.', 'affiliate-wp' ) ] );
		}

		// Check if affiliate is connected to Stripe
		if ( ! function_exists( 'affwp_stripe_payouts_is_affiliate_connected' ) || ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Affiliate #%d is not connected to Stripe.', 'affiliate-wp' ), $affiliate_id ) ] );
		}

		try {
			// Initialize Stripe API
			if ( ! function_exists( 'affwp_stripe_payouts_init_api' ) ) {
				wp_send_json_error( [ 'message' => __( 'Stripe API functions not found.', 'affiliate-wp' ) ] );
			}

			affwp_stripe_payouts_init_api();

			// Get affiliate Stripe account ID
			$affiliate_account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

			// Check if we're in test mode
			$is_test_mode = affwp_stripe_payouts_is_testing_mode();

			// No need to check platform account restrictions - transfers come from platform

			// Check if affiliate account is connected
			if ( empty( $affiliate_account_id ) ) {
				wp_send_json_error( [ 'message' => sprintf( __( 'Affiliate #%d Stripe account not found.', 'affiliate-wp' ), $affiliate_id ) ] );
			}

			// Check affiliate payment capabilities using our new helper function
			$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );

			if ( is_wp_error( $capability_check ) ) {
				// Handle error in capability check
				wp_send_json_error( [ 'message' => $capability_check->get_error_message() ] );
			}

			// Check if affiliate can receive transfers
			if ( ! $capability_check['can_receive_transfers'] ) {
				// Cannot receive transfers at all - block payment
				wp_send_json_error(
					[
						'message' => $capability_check['error_message'] ?: sprintf(
							__( 'Cannot pay affiliate #%d - their Stripe account cannot receive transfers.', 'affiliate-wp' ),
							$affiliate_id
						),
					]
				);
			}

			// If they can receive transfers but not payout, log a warning but allow payment
			if ( ! $capability_check['can_payout'] && $capability_check['warning_message'] ) {
				// Warning logged - capability check passed but with restrictions
			}

			// Format amount properly (convert to cents for non-zero-decimal currencies)
			try {
				// Get the platform account balance to determine the currency we have funds in
				$balance = \Stripe\Balance::retrieve();

				// Account info retrieved for validation
				$debug_account = \Stripe\Account::retrieve();

				// Find the currency with available funds
				$currency         = null;
				$available_amount = 0;
				foreach ( $balance->available as $bal ) {
					if ( $bal->amount > 0 ) {
						$currency         = $bal->currency;
						$available_amount = $bal->amount;
						break;
					}
				}

				// If no currency found with available funds, fall back to AffiliateWP currency
				if ( ! $currency ) {
					$currency = strtolower( affwp_get_currency() );
				}
			} catch ( \Exception $e ) {
				// If we can't get the balance, fall back to AffiliateWP currency
				$currency = strtolower( affwp_get_currency() );
			}

			$amount = affwp_stripe_payouts_format_amount( $referral->amount );

			// Set up the transfer arguments
			$transfer_args = [
				'amount'      => $amount,
				'currency'    => $currency,
				'destination' => $affiliate_account_id,
				'description' => sprintf( __( 'Referral #%d payment', 'affiliate-wp' ), $referral_id ),
				'metadata'    => [
					'referral_id'  => $referral_id,
					'affiliate_id' => $affiliate_id,
				],
			];

			// TEST MODE: Allow testing transfer failures with special description
			$test_failure_mode = ( $is_test_mode && $referral->description === 'TEST_FAIL' );
			if ( $test_failure_mode && function_exists( 'affwp_stripe_payouts_log_error' ) ) {
				affwp_stripe_payouts_log_error(
					'TEST MODE: Bypassing preflight checks for TEST_FAIL referral',
					[
						'referral_id' => $referral_id,
						'amount'      => $referral->amount,
					]
				);
			}

			// Create transfer from platform account to affiliate

			// Send the transfer from platform account to affiliate
			$transfer = \Stripe\Transfer::create(
				$transfer_args,
				[
					'idempotency_key' => 'affwp-stripe-payouts-transfer-' . $referral_id . '-' . time(),
				]
			);

			// Mark the referral as paid
			if ( $transfer && isset( $transfer->id ) ) {
				// Update the referral
				affwp_set_referral_status( $referral_id, 'paid' );

				// Add a meta for the Stripe transfer ID
				affwp_add_referral_meta( $referral_id, 'stripe_transfer_id', $transfer->id );

				// Create a new payout record.
				if ( function_exists( 'affwp_add_payout' ) ) {
					affwp_add_payout(
						[
							'affiliate_id'  => $affiliate_id,
							'referrals'     => [ $referral_id ],
							'amount'        => $referral->amount,
							'payout_method' => 'stripe',
							'status'        => 'paid',
							'date'          => current_time( 'mysql' ),
						]
					);
				}

				// Send success response
				wp_send_json_success(
					[
						'message'     => sprintf( __( 'Referral #%d successfully paid via Stripe.', 'affiliate-wp' ), $referral_id ),
						'transfer_id' => $transfer->id,
					]
				);
			}

			wp_send_json_error( [ 'message' => __( 'Stripe transfer was not created.', 'affiliate-wp' ) ] );

		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			// Enhanced logging with full Stripe error details
			if ( function_exists( 'affwp_stripe_payouts_log_error' ) ) {
				$error_details = [
					'referral_id'   => isset( $referral_id ) ? $referral_id : 0,
					'affiliate_id'  => isset( $affiliate_id ) ? $affiliate_id : 0,
					'amount'        => isset( $referral ) ? $referral->amount : 0,
					'error_message' => $e->getMessage(),
					'stripe_code'   => method_exists( $e, 'getStripeCode' ) ? $e->getStripeCode() : null,
					'http_status'   => method_exists( $e, 'getHttpStatus' ) ? $e->getHttpStatus() : null,
					'request_id'    => method_exists( $e, 'getRequestId' ) ? $e->getRequestId() : null,
					'decline_code'  => method_exists( $e, 'getDeclineCode' ) ? $e->getDeclineCode() : null,
				];

				// Extra detailed logging in test mode
				if ( $is_test_mode && function_exists( 'affwp_stripe_payouts_log_error' ) ) {
					affwp_stripe_payouts_log_error( 'TEST MODE - Full Stripe Error Response', $error_details );
				}

				affwp_stripe_payouts_log_error(
					'Payout error: ' . $e->getMessage(),
					$error_details
				);
			}

			// Trigger failure email notification with full Stripe error details
			if ( isset( $affiliate_id ) && isset( $referral ) ) {
				$error_data = [
					'error_message' => $e->getMessage(),
					'error_code'    => method_exists( $e, 'getStripeCode' ) && $e->getStripeCode() ? $e->getStripeCode() : ( $e->getCode() ? $e->getCode() : 'unknown' ),
					'referral_id'   => isset( $referral_id ) ? $referral_id : 0,
					'stripe_code'   => method_exists( $e, 'getStripeCode' ) ? $e->getStripeCode() : null,
					'decline_code'  => method_exists( $e, 'getDeclineCode' ) ? $e->getDeclineCode() : null,
				];
				do_action( 'affwp_stripe_transfer_failed', $affiliate_id, $referral, $error_data );
			}

			// Check for specific error types and provide better error messages
			$error_message = $e->getMessage();

			// Check if it's an insufficient funds error
			if ( strpos( $error_message, 'insufficient' ) !== false && strpos( $error_message, 'available funds' ) !== false ) {
				// Check the actual balance to help with debugging
				try {
					$balance = \Stripe\Balance::retrieve();

					$available = 0;
					$pending   = 0;

					// Get available and pending balances in the currency we're using
					foreach ( $balance->available as $bal ) {
						if ( $bal->currency === $currency ) {
							$available = $bal->amount;
						}
					}

					foreach ( $balance->pending as $bal ) {
						if ( $bal->currency === $currency ) {
							$pending = $bal->amount;
						}
					}

					// Check if we're in test mode
					$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );

					// Format the error message with balance details
					$error_message = sprintf(
						__( 'You have insufficient available funds in your Stripe platform account. Available balance: %1$s %2$s, Pending balance: %3$s %4$s. The transfer requires %5$s %6$s. In test mode, visit the Stripe settings page and create a test charge to your platform account.', 'affiliate-wp' ),
						$available / 100,
						strtoupper( $currency ),
						$pending / 100,
						strtoupper( $currency ),
						$amount / 100,
						strtoupper( $currency )
					);

					// Prepare structured response for insufficient funds
					$error_response = [
						'message'    => $error_message,
						'error_type' => 'insufficient_funds',
						'details'    => [
							'available'    => $available / 100,
							'pending'      => $pending / 100,
							'required'     => $amount / 100,
							'needed'       => ( $amount - $available ) / 100,
							'currency'     => strtoupper( $currency ),
							'test_mode'    => $test_mode,
							'settings_url' => admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts' ),
						],
					];

					// Send structured error response
					wp_send_json_error( $error_response );
					return;

				} catch ( \Exception $balance_e ) {
					// If we can't get the balance, use the standard error message
					$error_message = __( 'You have insufficient available funds in your Stripe platform account. Your Stripe dashboard shows you have funds, but they may be pending or on hold. To add funds in test mode, visit the Stripe settings page and create a test charge to your platform account.', 'affiliate-wp' );

					// Still send structured response even without balance details
					$error_response = [
						'message'    => $error_message,
						'error_type' => 'insufficient_funds',
						'details'    => [
							'test_mode'    => affiliate_wp()->settings->get( 'stripe_test_mode', false ),
							'settings_url' => admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts' ),
						],
					];

					wp_send_json_error( $error_response );
					return;
				}
			}

			// Send standard error response for other errors
			wp_send_json_error( [ 'message' => $error_message ] );
		} catch ( \Exception $e ) {
			// Log the error
			if ( function_exists( 'affwp_stripe_payouts_log_error' ) ) {
				affwp_stripe_payouts_log_error(
					'Payout error: ' . $e->getMessage(),
					[
						'referral_id'  => isset( $referral_id ) ? $referral_id : 0,
						'affiliate_id' => isset( $affiliate_id ) ? $affiliate_id : 0,
						'amount'       => isset( $referral ) ? $referral->amount : 0,
					]
				);
			}

			// Send error response
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}

		// Failsafe - should never reach here
		wp_send_json_error( [ 'message' => __( 'Unknown error processing payout.', 'affiliate-wp' ) ] );
	}









	/**
	 * Get client IP address safely
	 *
	 * @since 2.29.0
	 * @return string
	 */
	private function get_client_ip() {
		// Check for various headers that might contain the real IP
		$ip_headers = [
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				// Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP address
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fallback to localhost if no valid IP found
		return '127.0.0.1';
	}

	/**
	 * Get all affiliate Stripe account IDs in bulk to avoid N+1 queries
	 *
	 * @since 2.29.0
	 * @param array $affiliate_ids Array of affiliate IDs
	 * @return array Associative array of affiliate_id => account_id
	 */
	private function get_bulk_affiliate_stripe_accounts( $affiliate_ids ) {
		global $wpdb;

		if ( empty( $affiliate_ids ) ) {
			return [];
		}

		// Get affiliate user IDs first (affiliates are linked to WordPress users)
		$affiliate_objects = affiliate_wp()->affiliates->get_affiliates(
			[
				'affiliate_id' => $affiliate_ids,
				'number'       => -1,
				'fields'       => [ 'affiliate_id', 'user_id' ],
			]
		);

		if ( empty( $affiliate_objects ) ) {
			return [];
		}

		// Create a map of affiliate_id => user_id
		$affiliate_user_map = [];
		$user_ids           = [];
		foreach ( $affiliate_objects as $affiliate ) {
			$affiliate_user_map[ $affiliate->affiliate_id ] = $affiliate->user_id;
			$user_ids[]                                     = $affiliate->user_id;
		}

		if ( empty( $user_ids ) ) {
			return [];
		}

		// Bulk fetch Stripe account IDs from user meta
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT user_id, meta_value
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'affwp_stripe_payouts_account_id'
			AND user_id IN ($placeholders)
			AND meta_value != ''
		",
				$user_ids
			)
		);

		// Build the final map of affiliate_id => account_id
		$account_map = [];
		foreach ( $results as $result ) {
			// Find the affiliate_id for this user_id
			foreach ( $affiliate_user_map as $affiliate_id => $user_id ) {
				if ( $user_id == $result->user_id ) {
					$account_map[ $affiliate_id ] = $result->meta_value;
					break;
				}
			}
		}

		return $account_map;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Only load on AffiliateWP admin pages and ensure we're in admin
		if ( ! is_admin() || ! affwp_is_admin_page() ) {
			return;
		}

		// Enqueue scripts for tooltips on affiliates page.
		$screen = get_current_screen();

		// Add platform change handler script inline for settings page
		if ( $screen && 'affiliates_page_affiliate-wp-settings' === $screen->id ) {

			// Get current platform ID and connected affiliates count for validation
			$current_platform_id = get_option( 'affwp_stripe_platform_account_id', '' );

			// Get the current saved API keys
			$settings       = get_option( 'affwp_settings', [] );
			$saved_test_key = isset( $settings['stripe_test_secret_key'] ) ? $settings['stripe_test_secret_key'] : '';
			$saved_live_key = isset( $settings['stripe_live_secret_key'] ) ? $settings['stripe_live_secret_key'] : '';

			// Check for connected affiliates
			global $wpdb;
			$connected_count = $wpdb->get_var(
				"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
				WHERE meta_key = 'affwp_stripe_payouts_account_id'
				AND meta_value != ''"
			);

		}
	}

	/**
	 * Get the content for the payment confirmation modal
	 *
	 * @since 2.29.0
	 * @param string $stripe_logo_svg The Stripe logo SVG markup.
	 * @return string The modal content HTML.
	 */
	private function get_payment_modal_content( $stripe_logo_svg ) {
		ob_start();
		?>
		<div class="space-y-5">
			<!-- Loading state -->
			<div id="stripe-modal-loading" class="py-8">
				<div class="flex flex-col items-center justify-center space-y-4">
					<svg class="animate-spin h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					<p class="text-base text-gray-700"><?php esc_html_e( 'Verifying account status...', 'affiliate-wp' ); ?></p>
				</div>
			</div>
			<!-- Content state (hidden initially) -->
			<div id="stripe-modal-content" class="hidden space-y-5">
				<div class="text-center">
					<div class="text-4xl font-bold text-gray-900" id="stripe-modal-amount"></div>
				</div>
				<!-- Payment details list -->
				<div class="space-y-3">
					<div class="flex items-center">
						<span class="text-base text-gray-700 w-20"><?php esc_html_e( 'Referral', 'affiliate-wp' ); ?></span>
						<span class="text-base font-medium text-gray-900" id="stripe-modal-referral"></span>
					</div>
					<div class="flex items-center">
						<span class="text-base text-gray-700 w-20"><?php esc_html_e( 'Affiliate', 'affiliate-wp' ); ?></span>
						<span class="text-base font-medium text-gray-900" id="stripe-modal-affiliate"></span>
					</div>
					<div class="flex items-center">
						<span class="text-base text-gray-700 w-20"><?php esc_html_e( 'Method', 'affiliate-wp' ); ?></span>
						<div class="flex items-center space-x-1">
							<?php echo str_replace( 'class="', 'class="h-4 ', $stripe_logo_svg ); ?>
						</div>
					</div>
				</div>
				<!-- Warning section for payouts paused -->
				<div id="stripe-modal-warning" class="rounded-md bg-yellow-50 border border-yellow-200 p-3 mb-4 hidden">
					<div class="flex gap-2">
						<svg class="h-5 w-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
						</svg>
						<div class="flex-1">
							<p class="text-sm text-yellow-800" id="stripe-modal-warning-text"></p>
						</div>
					</div>
				</div>
				<!-- Error section for payment blocked -->
				<div id="stripe-modal-error" class="rounded-md bg-red-50 border border-red-200 p-3 mb-4 hidden">
					<div class="flex gap-2">
						<svg class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
						<div class="flex-1">
							<p class="text-sm text-red-800" id="stripe-modal-error-text"></p>
						</div>
					</div>
				</div>
				<!-- Transfer info -->
				<div id="stripe-modal-info">
					<p class="text-base text-gray-700">
						<?php esc_html_e( 'Transfer', 'affiliate-wp' ); ?> <strong class="text-gray-900" id="stripe-modal-amount-inline"></strong> <?php esc_html_e( 'to affiliate\'s Stripe account', 'affiliate-wp' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the content for the retry payment modal
	 *
	 * @since 2.29.0
	 * @param string $stripe_logo_svg The Stripe logo SVG markup.
	 * @return string The modal content HTML.
	 */
	private function get_retry_modal_content( $stripe_logo_svg ) {
		ob_start();
		?>
		<div class="space-y-5">
			<div class="text-center">
				<div class="text-4xl font-bold text-gray-900" id="stripe-retry-modal-amount"></div>
				<p class="mt-2 text-base text-gray-600"><?php esc_html_e( 'Previous payout failed', 'affiliate-wp' ); ?></p>
			</div>
			<!-- Payment details list -->
			<div class="space-y-3">
				<div class="flex items-center">
					<span class="text-sm text-gray-600 w-20"><?php esc_html_e( 'Referral', 'affiliate-wp' ); ?></span>
					<span class="text-sm font-medium text-gray-900" id="stripe-retry-modal-referral"></span>
				</div>
				<div class="flex items-center">
					<span class="text-sm text-gray-600 w-20"><?php esc_html_e( 'Affiliate', 'affiliate-wp' ); ?></span>
					<span class="text-sm font-medium text-gray-900" id="stripe-retry-modal-affiliate"></span>
				</div>
				<div class="flex items-center">
					<span class="text-sm text-gray-600 w-20"><?php esc_html_e( 'Method', 'affiliate-wp' ); ?></span>
					<div class="flex items-center space-x-1">
						<?php echo str_replace( 'class="', 'class="h-4 ', $stripe_logo_svg ); ?>
					</div>
				</div>
			</div>
			<div class="rounded-md bg-yellow-50 border border-yellow-200 p-3">
				<div class="flex gap-2">
					<svg class="h-5 w-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
					</svg>
					<div class="flex-1">
						<p class="text-sm text-yellow-800">
							<strong id="stripe-retry-modal-reason"></strong><br>
							<?php esc_html_e( 'Clicking "Retry Payout" will create a new transfer.', 'affiliate-wp' ); ?>
						</p>
						<p class="text-xs text-yellow-600 mt-2" id="stripe-retry-modal-transfer"></p>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Stripe payment modal for referrals page
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function render_stripe_payment_modals() {
		// Only render on referrals page
		$screen = get_current_screen();
		if ( ! $screen || 'affiliates_page_affiliate-wp-referrals' !== $screen->id ) {
			return;
		}

		// Stripe logo SVG for use in modals - larger size for better visibility
		$stripe_logo_svg = '<svg class="stripe-logo inline-block w-14 h-6 align-middle" viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg">
			<path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.86zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.9 0 1.85 6.29.97 6.29 5.88z"/>
		</svg>';

		// Register normal payment modal
		affwp_modal(
			[
				'id'             => 'stripe-payment-modal',
				'title'          => __( 'Confirm Payout', 'affiliate-wp' ),
				'icon'           => [
					'name'    => 'send-payout',
					'variant' => 'success',
				],
				'variant'        => 'success',
				'content'        => $this->get_payment_modal_content( $stripe_logo_svg ),
				'size'           => 'sm',
				'footer_actions' => [
					[
						'text'    => __( 'Cancel', 'affiliate-wp' ),
						'variant' => 'secondary',
						'size'    => 'lg',
						'action'  => 'close',
					],
					[
						'text'       => __( 'Send Payout', 'affiliate-wp' ),
						'variant'    => 'primary',
						'size'       => 'lg',
						'id'         => 'stripe-modal-button',
						'attributes' => [
							'@click' => 'window.affwpStripePayment.processPayment()',
						],
					],
				],
			]
		);

		// Register retry payment modal.
		affwp_modal(
			[
				'id'             => 'stripe-retry-payment-modal',
				'title'          => __( 'Retry Payout', 'affiliate-wp' ),
				'icon'           => [
					'name'    => 'send-payout',
					'variant' => 'warning',
				],
				'variant'        => 'warning',
				'content'        => $this->get_retry_modal_content( $stripe_logo_svg ),
				'size'           => 'sm',
				'variant'        => 'default',
				'footer_actions' => [
					[
						'text'    => __( 'Cancel', 'affiliate-wp' ),
						'variant' => 'secondary',
						'size'    => 'lg',
						'action'  => 'close',
					],
					[
						'text'       => __( 'Retry Payout', 'affiliate-wp' ),
						'variant'    => 'primary',
						'size'       => 'lg',
						'id'         => 'stripe-retry-modal-button',
						'attributes' => [
							'@click' => 'window.affwpStripePayment.processPayment()',
						],
					],
				],
			]
		);

		// Add the Alpine component for handling payments
		?>
		<script>
		// Simplified object for handling Stripe payment modals
		window.affwpStripePayment = {
			paymentData: {},
			capabilityStatus: {},

			openModal(referralId, affiliateId, amount, affiliateName, stripeState, transferId) {
				// Store the payment data
				this.paymentData = {
					referralId: referralId,
					affiliateId: affiliateId,
					amount: amount,
					affiliateName: affiliateName || 'Affiliate #' + affiliateId,
					stripeState: stripeState,
					transferId: transferId
				};

				// Format the amount with currency - add space after currency symbol
				const formattedAmount = '<?php echo affwp_get_currency(); ?> ' + parseFloat(amount).toFixed(2);

				// Determine which modal to use
				if (stripeState === 'failed' || stripeState === 'reversed') {
					// Update retry modal content
					document.getElementById('stripe-retry-modal-amount').textContent = formattedAmount;
					document.getElementById('stripe-retry-modal-affiliate').textContent = this.paymentData.affiliateName;
					document.getElementById('stripe-retry-modal-referral').textContent = '#' + this.paymentData.referralId;

					const retryReason = stripeState === 'failed'
						? '<?php echo esc_js( __( 'The previous Stripe transfer failed.', 'affiliate-wp' ) ); ?>'
						: '<?php echo esc_js( __( 'The previous Stripe transfer was reversed.', 'affiliate-wp' ) ); ?>';
					document.getElementById('stripe-retry-modal-reason').textContent = retryReason;

					if (transferId) {
						document.getElementById('stripe-retry-modal-transfer').textContent = 'Previous transfer: ' + transferId;
					} else {
						document.getElementById('stripe-retry-modal-transfer').textContent = '';
					}

					// Open retry modal immediately and focus button
					Alpine.store('modals').open('stripe-retry-payment-modal');
					// Focus button after modal is rendered
					requestAnimationFrame(() => {
						const retryButton = document.getElementById('stripe-retry-modal-button');
						if (retryButton) {
							retryButton.focus();
						}
					});
				} else {
					// Set basic modal content
					document.getElementById('stripe-modal-amount').textContent = formattedAmount;
					document.getElementById('stripe-modal-amount-inline').textContent = formattedAmount;
					document.getElementById('stripe-modal-affiliate').textContent = this.paymentData.affiliateName;
					document.getElementById('stripe-modal-referral').textContent = '#' + this.paymentData.referralId;

					// Reset modal state - show loading, hide content
					document.getElementById('stripe-modal-loading').classList.remove('hidden');
					document.getElementById('stripe-modal-content').classList.add('hidden');
					document.getElementById('stripe-modal-warning').classList.add('hidden');
					document.getElementById('stripe-modal-error').classList.add('hidden');
					document.getElementById('stripe-modal-info').classList.remove('hidden');

					// Disable pay button initially
					const payButton = document.getElementById('stripe-modal-button');
					payButton.disabled = true;
					payButton.classList.add('opacity-50', 'cursor-not-allowed');

					// Open modal immediately with loading state
					Alpine.store('modals').open('stripe-payment-modal');

					// Now check capabilities AFTER modal is open
					jQuery.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'affwp_stripe_check_affiliate_capability',
							nonce: '<?php echo wp_create_nonce( 'affwp-stripe-payouts-nonce' ); ?>',
							affiliate_id: affiliateId
						},
						dataType: 'json',
						success: (response) => {
							// Hide loading, show content
							document.getElementById('stripe-modal-loading').classList.add('hidden');
							document.getElementById('stripe-modal-content').classList.remove('hidden');

							if (response && response.success) {
								this.capabilityStatus = response.data;

								// Check capability status and show appropriate messages
								if (this.capabilityStatus.not_connected || !this.capabilityStatus.can_receive_transfers) {
									// Cannot receive transfers - show error
									document.getElementById('stripe-modal-error').classList.remove('hidden');
									document.getElementById('stripe-modal-error-text').textContent =
										this.capabilityStatus.error_message ||
										'<?php echo esc_js( __( 'This affiliate cannot receive payments at this time.', 'affiliate-wp' ) ); ?>';
									document.getElementById('stripe-modal-info').classList.add('hidden');

									// Keep pay button disabled
									payButton.disabled = true;
									payButton.classList.add('opacity-50', 'cursor-not-allowed');
								} else if (!this.capabilityStatus.can_payout && this.capabilityStatus.warning_message) {
									// Can receive but cannot payout - show warning
									document.getElementById('stripe-modal-warning').classList.remove('hidden');
									document.getElementById('stripe-modal-warning-text').textContent = this.capabilityStatus.warning_message;

									// Enable pay button and focus it
									payButton.disabled = false;
									payButton.classList.remove('opacity-50', 'cursor-not-allowed');
									requestAnimationFrame(() => {
										payButton.focus();
									});
								} else {
									// All good - enable button and focus it
									payButton.disabled = false;
									payButton.classList.remove('opacity-50', 'cursor-not-allowed');
									requestAnimationFrame(() => {
										payButton.focus();
									});
								}
							} else {
								// If capability check fails, show warning but allow payment
								document.getElementById('stripe-modal-warning').classList.remove('hidden');
								document.getElementById('stripe-modal-warning-text').textContent =
									response?.data?.message || '<?php echo esc_js( __( 'Could not verify affiliate status. Payment may fail if account is restricted.', 'affiliate-wp' ) ); ?>';

								// Enable button anyway and focus it
								payButton.disabled = false;
								payButton.classList.remove('opacity-50', 'cursor-not-allowed');
								requestAnimationFrame(() => {
									payButton.focus();
								});
							}
						},
						error: () => {
							// Hide loading, show content
							document.getElementById('stripe-modal-loading').classList.add('hidden');
							document.getElementById('stripe-modal-content').classList.remove('hidden');

							// Show warning that we couldn't verify status
							document.getElementById('stripe-modal-warning').classList.remove('hidden');
							document.getElementById('stripe-modal-warning-text').textContent =
								'<?php echo esc_js( __( 'Could not verify affiliate status. Payment may fail if account is restricted.', 'affiliate-wp' ) ); ?>';

							// Enable button anyway and focus it
							payButton.disabled = false;
							payButton.classList.remove('opacity-50', 'cursor-not-allowed');
							requestAnimationFrame(() => {
								payButton.focus();
							});
						}
					});
				}
			},

			processPayment() {
				// Find the active button (could be in either modal)
				const button = document.getElementById('stripe-modal-button') || document.getElementById('stripe-retry-modal-button');
				const originalText = button.textContent;
				button.disabled = true;
				button.innerHTML = `
					<svg class="inline-block w-4 h-4 mr-2 animate-spin" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					Processing...
				`;

				// Make AJAX request
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'affwp_stripe_payouts_pay_affiliate',
						nonce: '<?php echo wp_create_nonce( 'affwp-stripe-payouts-nonce' ); ?>',
						referral_id: this.paymentData.referralId,
						affiliate_id: this.paymentData.affiliateId,
						amount: this.paymentData.amount
					},
					dataType: 'json',
					success: (response) => {
						if (response && response.success) {
							// Show success state briefly
							button.innerHTML = `
								<svg class="inline-block w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
								</svg>
								Success!
							`;
							button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
							button.classList.add('bg-green-600');

							// Close the appropriate modal and reload after delay
							setTimeout(() => {
								const modalId = this.paymentData.stripeState ? 'stripe-retry-payment-modal' : 'stripe-payment-modal';
								Alpine.store('modals').close(modalId);
								const url = new URL(window.location.href);
								url.searchParams.set('affwp_notice', 'stripe_success');
								url.searchParams.set('referral', this.paymentData.referralId);
								window.location.href = url.toString();
							}, 1000);
						} else {
							// Show error
							alert(response?.data?.message || 'Payment failed. Please try again.');
							button.disabled = false;
							button.textContent = originalText;
						}
					},
					error: () => {
						alert('An error occurred. Please try again.');
						button.disabled = false;
						button.textContent = originalText;
					}
				});
			}
		};
		</script>
		<?php
	}

	/**
	 * Add bulk actions to the referrals table
	 *
	 * @since 2.29.0
	 * @param array $actions The current bulk actions
	 * @return array The modified bulk actions
	 */
	public function add_referrals_bulk_actions( $actions ) {
		// Only add if Stripe is configured and admin is connected
		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			return $actions;
		}

		$actions['pay_now_stripe'] = __( 'Pay Now via Stripe', 'affiliate-wp' );

		return $actions;
	}


	/**
	 * Process bulk action pay now
	 *
	 * @since 2.29.0
	 * @param int $referral_id The referral ID
	 * @return void
	 */
	public function process_bulk_action_pay_now( $referral_id ) {
		static $processed_referrals  = [];
		static $successful_referrals = [];
		static $failed_referrals     = [];
		static $total_count          = null;

		// Initialize total count
		if ( $total_count === null && isset( $_REQUEST['referral_id'] ) ) {
			$total_count = is_array( $_REQUEST['referral_id'] ) ? count( $_REQUEST['referral_id'] ) : 1;
		}

		if ( empty( $referral_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_referrals' ) ) {
			return;
		}

		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			wp_die( __( 'Please configure Stripe in Settings > Payouts before attempting to process payments', 'affiliate-wp' ) );
		}

		// Track that we've processed this referral
		$processed_referrals[] = $referral_id;

		// Get the referral
		$referral = affwp_get_referral( $referral_id );
		if ( ! $referral ) {
			$failed_referrals[ $referral_id ] = 'Referral not found';
			$this->maybe_redirect_after_bulk( $processed_referrals, $successful_referrals, $failed_referrals, $total_count );
			return;
		}

		if ( 'unpaid' !== $referral->status ) {
			$failed_referrals[ $referral_id ] = 'Referral status is ' . $referral->status . ', not unpaid';
			$this->maybe_redirect_after_bulk( $processed_referrals, $successful_referrals, $failed_referrals, $total_count );
			return;
		}

		// Check if affiliate is connected to Stripe
		$affiliate_id = $referral->affiliate_id;

		if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
			$failed_referrals[ $referral_id ] = 'Affiliate is not connected to Stripe';
			$this->maybe_redirect_after_bulk( $processed_referrals, $successful_referrals, $failed_referrals, $total_count );
			return;
		}

		// Process the payment and check the result
		$result = $this->pay_referral( $referral_id );

		if ( is_wp_error( $result ) ) {
			$failed_referrals[ $referral_id ] = $result->get_error_message();
		} else {
			$successful_referrals[] = $referral_id;
		}

		$this->maybe_redirect_after_bulk( $processed_referrals, $successful_referrals, $failed_referrals, $total_count );
	}

	/**
	 * Maybe redirect after bulk processing
	 *
	 * @since 2.29.0
	 * @param array $processed_referrals Processed referral IDs
	 * @param array $successful_referrals Successful referral IDs
	 * @param array $failed_referrals Failed referral IDs with error messages
	 * @param int   $total_count Total number of referrals to process
	 * @return void
	 */
	private function maybe_redirect_after_bulk( $processed_referrals, $successful_referrals, $failed_referrals, $total_count ) {
		if ( count( $processed_referrals ) >= $total_count ) {
			// All referrals processed, redirect with appropriate notice
			$redirect_args = [];

			if ( ! empty( $successful_referrals ) && empty( $failed_referrals ) ) {
				// All successful - calculate total amount
				$total_amount = 0;
				foreach ( $successful_referrals as $ref_id ) {
					$referral = affwp_get_referral( $ref_id );
					if ( $referral ) {
						$total_amount += $referral->amount;
					}
				}

				$redirect_args['affwp_notice'] = 'stripe_bulk_success';
				$redirect_args['count']        = count( $successful_referrals );
				$redirect_args['total']        = urlencode( affwp_currency_filter( affwp_format_amount( $total_amount ) ) );
			} elseif ( ! empty( $failed_referrals ) && empty( $successful_referrals ) ) {
				// All failed - show first error
				$first_error                   = reset( $failed_referrals );
				$redirect_args['affwp_notice'] = 'stripe_error';
				$redirect_args['message']      = urlencode( $first_error );
			} elseif ( ! empty( $successful_referrals ) && ! empty( $failed_referrals ) ) {
				// Mixed results - show detailed breakdown
				$total_amount = 0;
				foreach ( $successful_referrals as $ref_id ) {
					$referral = affwp_get_referral( $ref_id );
					if ( $referral ) {
						$total_amount += $referral->amount;
					}
				}

				// Build detailed message
				$message = sprintf(
					__( 'Successfully paid %1$d referral(s) totaling %2$s.', 'affiliate-wp' ),
					count( $successful_referrals ),
					affwp_currency_filter( affwp_format_amount( $total_amount ) )
				);

				// Add failed count and reasons
				$failed_count = count( $failed_referrals );
				if ( $failed_count > 0 ) {
					$message .= ' ' . sprintf(
						_n( '%d referral could not be paid.', '%d referrals could not be paid.', $failed_count, 'affiliate-wp' ),
						$failed_count
					);

					// Group failures by reason
					$failure_reasons = [];
					foreach ( $failed_referrals as $ref_id => $reason ) {
						if ( ! isset( $failure_reasons[ $reason ] ) ) {
							$failure_reasons[ $reason ] = 0;
						}
						++$failure_reasons[ $reason ];
					}

					// Add top failure reason
					if ( ! empty( $failure_reasons ) ) {
						$top_reason = array_keys( $failure_reasons )[0];
						$message   .= ' ' . sprintf( __( 'Most common issue: %s', 'affiliate-wp' ), $top_reason );
					}
				}

				$redirect_args['affwp_notice'] = 'stripe_bulk_mixed';
				$redirect_args['message']      = urlencode( $message );
			} else {
				// No referrals processed
				$redirect_args['affwp_notice'] = 'stripe_bulk_empty_error';
			}

			$redirect = remove_query_arg( [ 'action', 'referral_id', '_wpnonce' ] );
			$redirect = add_query_arg( $redirect_args, $redirect );
			wp_safe_redirect( $redirect );
			exit;
		}
	}


	/**
	 * Pay a single referral
	 *
	 * @since 2.29.0
	 * @param int $referral_id The referral ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	private function pay_referral( $referral_id ) {
		// Get referral
		$referral = affwp_get_referral( $referral_id );
		if ( ! $referral ) {
			return new WP_Error( 'invalid_referral', __( 'Invalid referral ID', 'affiliate-wp' ) );
		}

		// Initialize Stripe API
		if ( ! function_exists( 'affwp_stripe_payouts_init_api' ) ) {
			return new WP_Error( 'api_error', __( 'Stripe API functions not found', 'affiliate-wp' ) );
		}

		affwp_stripe_payouts_init_api();

		try {
			// Get affiliate Stripe account ID
			$affiliate_account_id = affwp_stripe_payouts_get_affiliate_account_id( $referral->affiliate_id );

			if ( empty( $affiliate_account_id ) ) {
				return new WP_Error( 'no_affiliate_account', __( 'Affiliate Stripe account not connected', 'affiliate-wp' ) );
			}

			// Check affiliate payment capabilities for consistency with individual payments
			$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $referral->affiliate_id );

			if ( is_wp_error( $capability_check ) ) {
				// Handle error in capability check
				return $capability_check;
			}

			// Check if affiliate can receive transfers
			if ( ! $capability_check['can_receive_transfers'] ) {
				// Cannot receive transfers at all - block payment
				return new WP_Error(
					'restricted_account',
					$capability_check['error_message'] ?: __( 'Affiliate Stripe account cannot receive transfers', 'affiliate-wp' )
				);
			}

			// Log warning if they can't payout but allow the transfer
			if ( ! $capability_check['can_payout'] && $capability_check['warning_message'] ) {
				// Warning noted - capability check passed but with restrictions
			}

			// Format amount
			$currency = strtolower( affwp_get_currency() );
			$amount   = affwp_stripe_payouts_format_amount( $referral->amount );

			// Attempting transfer from platform account to affiliate

			// In test mode, allow bypassing balance check
			$bypass_balance_check = false;
			if ( affwp_stripe_payouts_is_testing_mode() ) {
				// Allow admins to bypass balance check in test mode via filter
				$bypass_balance_check = apply_filters( 'affwp_stripe_payouts_bypass_test_balance_check', true );

				if ( $bypass_balance_check ) {
					// Test mode: Bypassing balance check
				}

				// Validate API keys
				$secret_key = affwp_stripe_payouts_get_secret_key();
				// API key validation completed
			}

			// Check balance before attempting transfer
			if ( ! $bypass_balance_check ) {
				try {
					// Get balance for the platform account
					$balance = \Stripe\Balance::retrieve();

					$available_balance = 0;
					foreach ( $balance->available as $bal ) {
						if ( $bal->currency === $currency ) {
							$available_balance = $bal->amount;
							break;
						}
					}

					// Balance check completed

					if ( $available_balance < $amount ) {
						// Insufficient funds detected
						// In production mode, return error for insufficient funds
						if ( ! affwp_stripe_payouts_is_testing_mode() ) {
							return new WP_Error( 'insufficient_funds', __( 'Insufficient funds in Stripe account', 'affiliate-wp' ) );
						}
					}
				} catch ( \Exception $e ) {
					// Could not check balance
				}
			} else {
				// In test mode with bypass enabled, skip balance check
				// Test mode balance bypass active
			}

			// Create transfer from platform account to affiliate connected account
			// Platform account is used automatically when no stripe_account is specified
			$transfer = \Stripe\Transfer::create(
				[
					'amount'      => $amount,
					'currency'    => $currency,
					'destination' => $affiliate_account_id,
					'description' => sprintf( __( 'Referral #%d payment', 'affiliate-wp' ), $referral_id ),
					'metadata'    => [
						'referral_id'  => $referral_id,
						'affiliate_id' => $referral->affiliate_id,
					],
				],
				[
					'idempotency_key' => 'affwp-stripe-payouts-transfer-' . $referral_id . '-' . time(),
				]
			);

			// Mark as paid
			if ( $transfer && isset( $transfer->id ) ) {
				affwp_set_referral_status( $referral_id, 'paid' );
				affwp_add_referral_meta( $referral_id, 'stripe_transfer_id', $transfer->id );

				// Trigger success email notification
				$transfer_data = [
					'transfer_id' => $transfer->id,
					'referral_id' => $referral_id,
				];
				do_action( 'affwp_stripe_payout_success', $referral->affiliate_id, $referral, $transfer_data );

				// Create payout record
				if ( function_exists( 'affwp_add_payout' ) ) {
					$payout_id = affwp_add_payout(
						[
							'affiliate_id'  => $referral->affiliate_id,
							'referrals'     => [ $referral_id ],
							'amount'        => $referral->amount,
							'payout_method' => 'stripe',
							'status'        => 'paid',
						]
					);
				}

				return true;
			}

			return new WP_Error( 'transfer_failed', __( 'Stripe transfer was not created', 'affiliate-wp' ) );

		} catch ( \Exception $e ) {
			// Transfer failed with error

			// Trigger failure email notification
			if ( isset( $referral ) ) {
				$error_data = [
					'error_message' => $e->getMessage(),
					'error_code'    => method_exists( $e, 'getCode' ) && $e->getCode() ? $e->getCode() : 'unknown',
					'referral_id'   => $referral_id,
				];
				do_action( 'affwp_stripe_payout_failed', $referral->affiliate_id, $referral, $error_data );
			}

			return new WP_Error( 'stripe_error', $e->getMessage() );
		}
	}

	/**
	 * Add Stripe as a payout method
	 *
	 * @since 2.29.0
	 * @param array $payout_methods Current payout methods
	 * @return array Modified payout methods
	 */
	public function add_payout_method( $payout_methods ) {
		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			$payout_methods['stripe'] = sprintf(
				__( 'Stripe - <a href="%s">Enable and/or configure Stripe Payouts</a> to enable this payout method', 'affiliate-wp' ),
				affwp_admin_url( 'settings', [ 'tab' => 'payouts#stripe' ] )
			);
		} else {
			$payout_methods['stripe'] = __( 'Stripe', 'affiliate-wp' );
		}

		return $payout_methods;
	}

	/**
	 * Check if Stripe payout method is enabled
	 *
	 * @since 2.29.0
	 * @param bool   $enabled       True if enabled
	 * @param string $payout_method The payout method
	 * @return bool True if enabled
	 */
	public function is_stripe_enabled( $enabled, $payout_method ) {
		if ( 'stripe' === $payout_method && ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) ) {
			$enabled = false;
		}

		return $enabled;
	}

	/**
	 * Add a note to the payout preview page for Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function preview_payout_note() {
		// Optional: Add any notes specific to Stripe payouts
	}

	/**
	 * Update the payout preview data for Stripe
	 *
	 * @since 2.29.0
	 * @param array $data The preview payout data
	 * @return array (Maybe) filtered payout data
	 */
	public function preview_payout_data( $data ) {
		// Show error notice if Stripe is not configured
		if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
			?>
			<div class="notice notice-error">
				<p>
				<?php
				printf(
					__( 'Please <a href="%s">configure Stripe</a> before attempting to process payments', 'affiliate-wp' ),
					affwp_admin_url( 'settings', [ 'tab' => 'payouts#stripe' ] )
				);
				?>
				</p>
			</div>
			<?php
		}

		// Filter out affiliates without valid user accounts or who cannot receive payments
		foreach ( $data as $affiliate_id => $payout_data ) {
			$affiliate = affwp_get_affiliate( $affiliate_id );

			// Remove if no valid user account
			if ( ! $affiliate || ! $affiliate->user ) {
				unset( $data[ $affiliate_id ] );
				continue;
			}

			// Check if affiliate can receive Stripe payments
			if ( affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
				$capability = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );

				// Remove affiliates who cannot receive payments
				if ( ! $capability['can_pay'] ) {
					unset( $data[ $affiliate_id ] );
				}
			}
		}

		return $data;
	}

	/**
	 * Add invalid affiliates for a Stripe payout
	 *
	 * @since 2.29.0
	 * @param array $invalid_affiliates Invalid affiliates
	 * @param array $data               Payout preview data
	 * @return array Invalid affiliates
	 */
	public function preview_payout_invalid_affiliates( $invalid_affiliates, $data ) {
		// Don't reset the array - preserve existing invalid affiliates
		// The $data array structure is affiliate_id => array('amount' => X)
		foreach ( $data as $affiliate_id => $payout_data ) {
			// Check if affiliate exists
			$user_name = affwp_get_affiliate_username( $affiliate_id );
			if ( ! $user_name ) {
				$invalid_affiliates[ $affiliate_id ] = __( 'This affiliate user account has been deleted', 'affiliate-wp' );
				continue;
			}

			// Check if affiliate is connected to Stripe
			if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
				$invalid_affiliates[ $affiliate_id ] = __( 'Not connected to Stripe', 'affiliate-wp' );
				continue;
			}

			// Check affiliate payment capabilities
			$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );

			if ( is_wp_error( $capability_check ) ) {
				// Handle API errors gracefully
				$invalid_affiliates[ $affiliate_id ] = sprintf(
					__( 'Stripe account error: %s', 'affiliate-wp' ),
					$capability_check->get_error_message()
				);
			} elseif ( ! $capability_check['can_receive_transfers'] ) {
				// Cannot receive transfers at all
				if ( ! empty( $capability_check['disabled_reason'] ) ) {
					// Map disabled reasons to user-friendly messages
					$reason_map = [
						'requirements.past_due'     => __( 'Account restricted - verification required', 'affiliate-wp' ),
						'rejected.fraud'            => __( 'Account rejected for fraud', 'affiliate-wp' ),
						'rejected.terms_of_service' => __( 'Account rejected - terms of service violation', 'affiliate-wp' ),
						'platform_paused'           => __( 'Platform account paused', 'affiliate-wp' ),
						'rejected.other'            => __( 'Account rejected by Stripe', 'affiliate-wp' ),
					];

					$reason = isset( $reason_map[ $capability_check['disabled_reason'] ] )
						? $reason_map[ $capability_check['disabled_reason'] ]
						: sprintf( __( 'Account restricted (%s)', 'affiliate-wp' ), $capability_check['disabled_reason'] );

					$invalid_affiliates[ $affiliate_id ] = $reason;
				} else {
					$invalid_affiliates[ $affiliate_id ] = __( 'Account cannot receive payments', 'affiliate-wp' );
				}
			} elseif ( $capability_check['payments_pausing_soon'] || $capability_check['payouts_pausing_soon'] ) {
				// Account is pausing soon - add as warning but still allow payment
				// You might want to log this or show a warning, but not block the payment
				// For now, we'll allow it but you could add a warning system here
			}
			// If can_receive_transfers is true and not pausing soon, affiliate is valid for payment
		}

		return $invalid_affiliates;
	}

	/**
	 * Process bulk Stripe payout
	 *
	 * @since 2.29.0
	 * @param string   $start          Referrals start date
	 * @param string   $end            Referral end date
	 * @param string   $minimum        Minimum payout amount
	 * @param int|bool $affiliate_id   Affiliate ID
	 * @param string   $payout_method  Payout method
	 * @param bool     $bypass_holding Whether to bypass holding period
	 * @return void
	 */
	public function process_bulk_stripe_payout( $start, $end, $minimum, $affiliate_id, $payout_method, $bypass_holding ) {

		// Initialize Stripe API
		if ( ! function_exists( 'affwp_stripe_payouts_init_api' ) ) {
			wp_die( __( 'Stripe API functions not found', 'affiliate-wp' ) );
		}

		affwp_stripe_payouts_init_api();

		// Get referrals to be paid
		$args = [
			'status'       => 'unpaid',
			'date'         => [
				'start' => $start,
				'end'   => $end,
			],
			'number'       => -1,
			'affiliate_id' => $affiliate_id,
		];

		$referrals = affiliate_wp()->referrals->get_referrals( $args );

		if ( empty( $referrals ) ) {
			$redirect_args['affwp_notice'] = 'stripe_bulk_pay_empty_error';
			$redirect                      = affwp_admin_url( 'referrals', $redirect_args );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Group referrals by affiliate and filter
		$payouts            = [];
		$redirect_args      = [];
		$skipped_affiliates = [];

		foreach ( $referrals as $referral ) {
			// Skip if in holding period and not bypassing
			if ( ! $bypass_holding && affwp_is_referral_within_holding_period( $referral ) ) {
				continue;
			}

			// Check if affiliate is connected to Stripe
			if ( ! affwp_stripe_payouts_is_affiliate_connected( $referral->affiliate_id ) ) {
				continue;
			}

			// Check if affiliate can receive payments
			$capability = affwp_stripe_payouts_check_affiliate_payment_capability( $referral->affiliate_id );
			if ( ! $capability['can_pay'] ) {
				$skipped_affiliates[ $referral->affiliate_id ] = $capability['reason'];
				continue;
			}

			if ( ! isset( $payouts[ $referral->affiliate_id ] ) ) {
				$payouts[ $referral->affiliate_id ] = [
					'amount'     => 0,
					'referrals'  => [],
					'account_id' => affwp_stripe_payouts_get_affiliate_account_id( $referral->affiliate_id ),
				];
			}

			$payouts[ $referral->affiliate_id ]['amount']     += $referral->amount;
			$payouts[ $referral->affiliate_id ]['referrals'][] = $referral->referral_id;
		}

		// Filter out payouts below minimum
		foreach ( $payouts as $aff_id => $payout ) {
			if ( $minimum > 0 && $payout['amount'] < $minimum ) {
				unset( $payouts[ $aff_id ] );
			}
		}

		if ( empty( $payouts ) ) {
			$redirect_args['affwp_notice'] = 'stripe_bulk_pay_empty_error';
			$redirect                      = affwp_admin_url( 'referrals', $redirect_args );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Process each payout
		$currency   = strtolower( affwp_get_currency() );
		$successful = [];
		$failed     = [];

		// Processing payouts

		foreach ( $payouts as $affiliate_id => $payout ) {
			try {
				// Double-check capability before processing payment
				$capability = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );
				if ( ! $capability['can_pay'] ) {
					// Skipping payment - affiliate not eligible
					$failed[ $affiliate_id ] = $capability['reason'];
					continue;
				}

				// Create transfer from platform account
				$amount = affwp_stripe_payouts_format_amount( $payout['amount'] );
				// Creating Stripe transfer

				// Build a more descriptive message based on whether dates are provided
				if ( $start && $end ) {
					$description = sprintf(
						__( 'Payment for referrals between %1$s and %2$s from %3$s', 'affiliate-wp' ),
						$start,
						$end,
						home_url()
					);
				} elseif ( $start ) {
					$description = sprintf(
						__( 'Payment for referrals from %1$s from %2$s', 'affiliate-wp' ),
						$start,
						home_url()
					);
				} elseif ( $end ) {
					$description = sprintf(
						__( 'Payment for referrals through %1$s from %2$s', 'affiliate-wp' ),
						$end,
						home_url()
					);
				} else {
					$description = sprintf(
						__( 'Payment for all unpaid referrals from %s', 'affiliate-wp' ),
						home_url()
					);
				}

				$transfer = \Stripe\Transfer::create(
					[
						'amount'      => $amount,
						'currency'    => $currency,
						'destination' => $payout['account_id'],
						'description' => $description,
						'metadata'    => [
							'affiliate_id' => $affiliate_id,
							'referral_ids' => implode( ',', $payout['referrals'] ),
						],
					]
				);

				if ( $transfer && isset( $transfer->id ) ) {
					// Stripe transfer successful
					$successful[ $affiliate_id ] = $payout;

					// Create payout record
					$payout_id = affwp_add_payout(
						[
							'affiliate_id'  => $affiliate_id,
							'referrals'     => $payout['referrals'],
							'amount'        => $payout['amount'],
							'payout_method' => 'stripe',
						]
					);
					// Created payout record
				}
			} catch ( \Exception $e ) {
				// Stripe transfer failed
				$failed[ $affiliate_id ] = $e->getMessage();
			}
		}

		// Set redirect notice
		if ( ! empty( $successful ) ) {
			$redirect_args['affwp_notice'] = 'stripe_bulk_pay_success';
		} else {
			$redirect_args['affwp_notice'] = 'stripe_bulk_pay_error';
		}

		$redirect = affwp_admin_url( 'payouts', $redirect_args );
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Handle platform change AJAX request
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function handle_platform_change_ajax() {
		// Check permissions
		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'affiliate-wp' ) ] );
		}

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp-stripe-payouts-nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed', 'affiliate-wp' ) ] );
		}

		$action = isset( $_POST['platform_action'] ) ? sanitize_text_field( $_POST['platform_action'] ) : '';

		// Get the platform change info
		$platform_info = get_transient( 'affwp_stripe_platform_changed' );

		if ( ! $platform_info ) {
			wp_send_json_error( [ 'message' => __( 'Platform change information expired. Please save settings again.', 'affiliate-wp' ) ] );
		}

		$response = [];

		switch ( $action ) {
			case 'clear':
				// Clear all connections
				$cleared = affwp_stripe_payouts_clear_all_connections();
				delete_transient( 'affwp_stripe_platform_changed' );

				$response = [
					'success' => true,
					'message' => sprintf(
						__( 'Cleared %d affiliate Stripe connections. Affiliates will need to reconnect.', 'affiliate-wp' ),
						$cleared
					),
					'cleared' => $cleared,
				];
				break;

			case 'keep':
				// Keep connections but mark for validation
				update_option( 'affwp_stripe_platform_needs_validation', true );
				delete_transient( 'affwp_stripe_platform_changed' );

				$response = [
					'success'           => true,
					'message'           => __( 'Keeping existing connections. Some may fail if accounts don\'t exist in the new platform.', 'affiliate-wp' ),
					'validation_needed' => true,
				];
				break;

			case 'validate':
				// Validate a sample of connections
				$sample_valid   = 0;
				$sample_invalid = 0;
				$sample_size    = min( 5, $platform_info['affected_affiliates'] );

				// Get a sample of connected affiliates
				global $wpdb;
				$sample_users = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$wpdb->usermeta}
						WHERE meta_key = 'affwp_stripe_payouts_account_id'
						AND meta_value != ''
						LIMIT %d",
						$sample_size
					)
				);

				foreach ( $sample_users as $user_id ) {
					$affiliate_id = affwp_get_affiliate_id( $user_id );
					if ( $affiliate_id && affwp_stripe_payouts_validate_affiliate_connection( $affiliate_id ) ) {
						++$sample_valid;
					} else {
						++$sample_invalid;
					}
				}

				$response = [
					'success'          => true,
					'message'          => sprintf(
						__( 'Validation sample: %1$d valid, %2$d invalid out of %3$d tested.', 'affiliate-wp' ),
						$sample_valid,
						$sample_invalid,
						$sample_size
					),
					'valid'            => $sample_valid,
					'invalid'          => $sample_invalid,
					'total_tested'     => $sample_size,
					'total_affiliates' => $platform_info['affected_affiliates'],
				];
				break;

			default:
				$response = [
					'success' => false,
					'message' => __( 'Invalid action specified', 'affiliate-wp' ),
				];
		}

		wp_send_json( $response );
	}



	/**
	 * Handle AJAX request to test Stripe connection
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function test_stripe_connection_ajax() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp_test_stripe_connection' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'affiliate-wp' ) ] );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'affiliate-wp' ) ] );
		}

		$mode       = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'test';
		$secret_key = isset( $_POST['secret_key'] ) ? sanitize_text_field( $_POST['secret_key'] ) : '';

		if ( empty( $secret_key ) ) {
			wp_send_json_error( [ 'message' => __( 'Secret key is required', 'affiliate-wp' ) ] );
		}

		// Validate key format
		$expected_prefix = $mode === 'test' ? 'sk_test_' : 'sk_live_';
		if ( strpos( $secret_key, $expected_prefix ) !== 0 ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Invalid key format. Expected key starting with %s', 'affiliate-wp' ), $expected_prefix ) ] );
		}

		// Check cache first (5-minute cache)
		$cache_key = 'affwp_stripe_verify_' . substr( md5( $secret_key ), 0, 8 );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_send_json_success( $cached );
		}

		// Test the connection using Stripe API
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			// Load Stripe SDK if not already loaded.
			$vendor_path = AFFILIATEWP_PLUGIN_DIR . 'vendor/autoload.php';
			if ( file_exists( $vendor_path ) ) {
				require_once $vendor_path;
			} else {
				wp_send_json_error( [ 'message' => __( 'Stripe SDK not found', 'affiliate-wp' ) ] );
			}
		}

		try {
			\Stripe\Stripe::setApiKey( $secret_key );

			// Try to retrieve account to test the connection
			$account = \Stripe\Account::retrieve();

			if ( $account && $account->id ) {
				// Prepare success response with account details
				$result = [
					'message'   => __( 'Connection successful!', 'affiliate-wp' ),
					'accountId' => $account->id,
					'keyLast4'  => substr( $secret_key, -4 ),
					'mode'      => $mode,
				];

				// Cache the result for 5 minutes
				set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );

				// Check if this is a different account (for platform change detection)
				// Use the correct option name for manually entered API keys
				$stored_account_id = get_option( 'affwp_stripe_platform_account_id', '' );

				// Also check the transient that stores the temporary account ID during verification
				$temp_account_id = get_transient( 'affwp_stripe_temp_account_id_' . $mode );

				// If we have a stored account ID, compare it with the new one
				if ( ! empty( $stored_account_id ) && $stored_account_id !== $account->id ) {
					$result['account_changed'] = true;
					$result['old_account']     = $stored_account_id;
				} elseif ( ! empty( $temp_account_id ) && $temp_account_id !== $account->id ) {
					// Check against the temporary account ID from a previous verification
					$result['account_changed'] = true;
					$result['old_account']     = $temp_account_id;
				}

				// Store the new account ID temporarily for comparison during the session
				// This allows detecting changes before the settings are saved
				set_transient( 'affwp_stripe_temp_account_id_' . $mode, $account->id, HOUR_IN_SECONDS );

				wp_send_json_success( $result );
			} else {
				wp_send_json_error( [ 'message' => __( 'Could not verify connection', 'affiliate-wp' ) ] );
			}
		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();

			// Provide more user-friendly error messages
			if ( strpos( $error_message, 'Invalid API Key' ) !== false ) {
				$error_message = __( 'Invalid API key. Please check your key and try again.', 'affiliate-wp' );
			} elseif ( strpos( $error_message, 'No such' ) !== false ) {
				$error_message = __( 'Authentication failed. Please verify your API key.', 'affiliate-wp' );
			}

			wp_send_json_error( [ 'message' => $error_message ] );
		}
	}

	/**
	 * Handle AJAX request to check Stripe balance
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function check_balance_ajax() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp_stripe_check_balance' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'affiliate-wp' ) ] );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'affiliate-wp' ) ] );
		}

		// Check if admin has API credentials (bypass toggle check for balance operations)
		if ( ! function_exists( 'affwp_stripe_payouts_is_admin_connected' ) || ! affwp_stripe_payouts_is_admin_connected( false ) ) {
			// Get more specific error message based on what's missing
			$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );
			$mode      = $test_mode ? 'test' : 'live';
			$key_name  = $test_mode ? __( 'Test Secret Key', 'affiliate-wp' ) : __( 'Live Secret Key', 'affiliate-wp' );

			wp_send_json_error(
				[
					'message' => sprintf(
						__( 'Please add your Stripe %s in the settings above to check your balance.', 'affiliate-wp' ),
						$key_name
					),
				]
			);
		}

		try {
			// Check platform balance and get formatted data
			$balance_data = affwp_stripe_payouts_check_platform_balance();

			if ( is_wp_error( $balance_data ) ) {
				wp_send_json_error( [ 'message' => $balance_data->get_error_message() ] );
			}

			// Cache the balance for 1 hour
			affwp_stripe_payouts_set_cached_balance( $balance_data, 3600 );

			// Format response
			$response = [
				'balance'   => $balance_data,
				'formatted' => affwp_stripe_payouts_format_balance_amount( $balance_data ),
				'html'      => '',
			];

			// Build HTML message
			$message = '<strong>' . __( 'Available Balances:', 'affiliate-wp' ) . '</strong><br>';

			if ( empty( $balance_data['available'] ) ) {
				$message .= __( 'No available funds.', 'affiliate-wp' ) . '<br>';
			} else {
				foreach ( $balance_data['available'] as $bal ) {
					$message .= sprintf(
						'%s %s<br>',
						number_format( $bal['amount'] / 100, 2 ),
						strtoupper( $bal['currency'] )
					);
				}
			}

			$message .= '<br><strong>' . __( 'Pending Balances:', 'affiliate-wp' ) . '</strong><br>';

			if ( empty( $balance_data['pending'] ) ) {
				$message .= __( 'No pending funds.', 'affiliate-wp' ) . '<br>';
			} else {
				foreach ( $balance_data['pending'] as $bal ) {
					if ( $bal['amount'] > 0 ) {
						$message .= sprintf(
							'%s %s<br>',
							number_format( $bal['amount'] / 100, 2 ),
							strtoupper( $bal['currency'] )
						);
					}
				}
			}

			$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );
			if ( $test_mode ) {
				$message .= '<br><em>' . __( 'Note: You are in test mode. To add funds to your available balance, use the test charge feature below.', 'affiliate-wp' ) . '</em>';
			}

			$response['html'] = $message;

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Handle AJAX request to create test charge
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function create_test_charge_ajax() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp_stripe_create_test_charge' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'affiliate-wp' ) ] );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'affiliate-wp' ) ] );
		}

		// Check if in test mode
		$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );
		if ( ! $test_mode ) {
			wp_send_json_error( [ 'message' => __( 'Test charges can only be created in test mode', 'affiliate-wp' ) ] );
		}

		// Get and validate amount
		$amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 10;
		if ( $amount <= 0 || $amount > 10000 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid amount. Please enter a value between $1 and $10,000.', 'affiliate-wp' ) ] );
		}

		// Check if the process_test_charge_ajax method exists
		if ( method_exists( $this, 'process_test_charge_ajax' ) ) {
			// Set the amount in POST for the method to use
			$_POST['amount'] = $amount;
			$_POST['nonce']  = wp_create_nonce( 'affwp-stripe-payouts-nonce' );
			$this->process_test_charge_ajax();
			return; // The method will handle the JSON response
		}

		// Fallback: Try to create test charge directly
		try {
			// Initialize Stripe API
			if ( function_exists( 'affwp_stripe_payouts_init_api' ) ) {
				$api_initialized = affwp_stripe_payouts_init_api();

				// Check if API was successfully initialized (has API key)
				if ( ! $api_initialized ) {
					wp_send_json_error( [ 'message' => __( 'Please add your Stripe Test Secret Key in the settings above to use test funds.', 'affiliate-wp' ) ] );
					return;
				}

				// Format amount for Stripe (convert to cents)
				$currency      = strtolower( affwp_get_currency() );
				$stripe_amount = intval( $amount * 100 );

				// Create test charge
				$payment_intent = \Stripe\PaymentIntent::create(
					[
						'amount'               => $stripe_amount,
						'currency'             => $currency,
						'payment_method_types' => [ 'card' ],
						'confirm'              => true,
						'payment_method'       => 'pm_card_bypassPending',
						'description'          => sprintf( __( 'Test charge for adding available balance', 'affiliate-wp' ) ),
						'off_session'          => true,
					]
				);

				// Clear the cached balance since we just added funds
				affwp_stripe_payouts_clear_cached_balance();

				// Get the updated balance after the charge
				$new_balance       = affwp_stripe_payouts_check_platform_balance();
				$formatted_balance = is_wp_error( $new_balance ) ? '$0.00' : affwp_stripe_payouts_format_balance_amount( $new_balance );

				// Cache the new balance
				if ( ! is_wp_error( $new_balance ) ) {
					affwp_stripe_payouts_set_cached_balance( $new_balance, 3600 );
				}

				wp_send_json_success(
					[
						'message'      => sprintf(
							__( 'Test charge created successfully! $%s has been added to your test balance.', 'affiliate-wp' ),
							number_format( $amount, 2 )
						),
						'new_balance'  => $formatted_balance,
						'balance_data' => $new_balance,
					]
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}

		wp_send_json_error( [ 'message' => __( 'Test charge creation not available', 'affiliate-wp' ) ] );
	}

	/**
	 * Handle AJAX request to check affiliate payment capability
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function check_affiliate_capability_ajax() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp-stripe-payouts-nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'affiliate-wp' ) ] );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_referrals' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'affiliate-wp' ) ] );
		}

		// Get affiliate ID
		$affiliate_id = isset( $_POST['affiliate_id'] ) ? absint( $_POST['affiliate_id'] ) : 0;
		if ( ! $affiliate_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid affiliate ID', 'affiliate-wp' ) ] );
		}

		// Check if affiliate is connected
		if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
			wp_send_json_success(
				[
					'can_receive_transfers' => false,
					'can_payout'            => false,
					'error_message'         => __( 'This affiliate is not connected to Stripe. They need to connect their Stripe account before they can receive payments.', 'affiliate-wp' ),
					'not_connected'         => true,
				]
			);
		}

		// Check capability
		$capability_check = affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id );

		if ( is_wp_error( $capability_check ) ) {
			wp_send_json_error( [ 'message' => $capability_check->get_error_message() ] );
		}

		// Return capability status
		wp_send_json_success( $capability_check );
	}

	/**
	 * Process disconnect affiliate AJAX request
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function disconnect_affiliate_ajax() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'affwp_stripe_disconnect_' . $_POST['affiliate_id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed', 'affiliate-wp' ) ] );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_affiliates' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'affiliate-wp' ) ] );
		}

		// Get affiliate ID
		$affiliate_id = isset( $_POST['affiliate_id'] ) ? absint( $_POST['affiliate_id'] ) : 0;
		if ( ! $affiliate_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid affiliate ID', 'affiliate-wp' ) ] );
		}

		// Disconnect the affiliate
		$result = affwp_stripe_payouts_disconnect_affiliate( $affiliate_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success(
			[
				'message'  => __( 'Affiliate disconnected from Stripe successfully', 'affiliate-wp' ),
				'redirect' => add_query_arg(
					[
						'affwp_notice' => 'stripe_disconnected',
						'affiliate_id' => $affiliate_id,
						'action'       => 'edit_affiliate',
					],
					admin_url( 'admin.php?page=affiliate-wp-affiliates' )
				),
			]
		);
	}

	/**
	 * Save platform account ID when settings are saved
	 *
	 * @since 2.29.0
	 * @param array $input Settings input
	 * @return array Modified settings
	 */
	public function save_platform_account_id( $input ) {
		// Get the mode (test or live)
		$test_mode = ! empty( $input['stripe_test_mode'] );

		// Get the appropriate secret key
		$secret_key = $test_mode ?
			( ! empty( $input['stripe_test_secret_key'] ) ? $input['stripe_test_secret_key'] : '' ) :
			( ! empty( $input['stripe_live_secret_key'] ) ? $input['stripe_live_secret_key'] : '' );

		if ( ! empty( $secret_key ) ) {
			// Load Stripe SDK if needed
			if ( ! class_exists( '\Stripe\Stripe' ) ) {
				$vendor_path = AFFILIATEWP_PLUGIN_DIR . 'vendor/autoload.php';
				if ( file_exists( $vendor_path ) ) {
					require_once $vendor_path;
				}
			}

			try {
				\Stripe\Stripe::setApiKey( $secret_key );
				$account = \Stripe\Account::retrieve();

				if ( $account && $account->id ) {
					// Always update the platform ID to match the current key
					update_option( 'affwp_stripe_platform_account_id', $account->id );
				}
			} catch ( \Exception $e ) {
				// Log the error but don't stop the settings from saving
				affwp_stripe_payouts_log_error( 'Failed to verify Stripe account during settings save: ' . $e->getMessage() );
			}
		}

		return $input;
	}
}
