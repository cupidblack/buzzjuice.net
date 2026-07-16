<?php
/**
 * Batch Process Class
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Admin/Batch Process
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if AffiliateWP is active.
if ( ! class_exists( 'Affiliate_WP' ) ) {
	return;
}

// Only define the class if the parent class exists.
if ( class_exists( 'Affiliate_WP_Utilities_Batch_Process' ) ) {
	/**
	 * AffiliateWP_Stripe_Payouts_Batch_Process Class
	 *
	 * @since 2.29.0
	 */
	class AffiliateWP_Stripe_Payouts_Batch_Process extends Affiliate_WP_Utilities_Batch_Process {

		/**
		 * The batch process ID
		 *
		 * @since 2.29.0
		 * @var string
		 */
		public $batch_id = 'stripe_payouts';

		/**
		 * The percentage completed
		 *
		 * @since 2.29.0
		 * @var int
		 */
		public $percentage = 0;

		/**
		 * Process bulk payouts
		 *
		 * @since 2.29.0
		 * @return void
		 */
		public function process_batch() {
			// Get the next batch
			$items = $this->get_items();

			if ( ! empty( $items ) ) {
				// Process each referral
				foreach ( $items as $referral ) {
					if ( ! affwp_get_affiliate( $referral->affiliate_id ) ) {
						$this->log( sprintf( __( 'Affiliate #%d does not exist, skipping.', 'affiliate-wp' ), $referral->affiliate_id ) );
						continue;
					}

					// Skip if the referral is not unpaid
					if ( 'unpaid' !== $referral->status ) {
						$this->log( sprintf( __( 'Referral #%d is not unpaid, skipping.', 'affiliate-wp' ), $referral->referral_id ) );
						continue;
					}

					// Skip if the affiliate is not connected to Stripe
					if ( ! affwp_stripe_payouts_is_affiliate_connected( $referral->affiliate_id ) ) {
						$this->log( sprintf( __( 'Affiliate #%d is not connected to Stripe, skipping.', 'affiliate-wp' ), $referral->affiliate_id ) );
						continue;
					}

					// Process the payment
					$result = $this->process_payment( $referral );

					if ( is_wp_error( $result ) ) {
						$this->log( sprintf( __( 'Error paying referral #%1$d: %2$s', 'affiliate-wp' ), $referral->referral_id, $result->get_error_message() ) );
					} else {
						$this->log( sprintf( __( 'Referral #%1$d successfully paid via Stripe. Transfer ID: %2$s', 'affiliate-wp' ), $referral->referral_id, $result['transfer_id'] ) );
					}
				}

				// Get a new batch
				$this->get_items();
			}
		}

		/**
		 * Get the items for processing
		 *
		 * @since 2.29.0
		 * @return array The items to process
		 */
		public function get_items() {
			$items = $this->get_stored_items();

			if ( ! empty( $items ) ) {
				return $items;
			}

			// Get all unpaid referrals
			$args = [
				'number'  => 25,
				'status'  => 'unpaid',
				'orderby' => 'date',
				'order'   => 'ASC',
				'date'    => [
					'start' => $this->step > 1 ? '' : $this->data['from'],
					'end'   => $this->step > 1 ? '' : $this->data['to'],
				],
			];

			// Filter by affiliate if specified
			if ( ! empty( $this->data['affiliate_id'] ) ) {
				$validated_affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $this->data['affiliate_id'] );
				if ( ! is_wp_error( $validated_affiliate_id ) ) {
					$args['affiliate_id'] = $validated_affiliate_id;
				} else {
					$this->log( sprintf( __( 'Invalid affiliate ID specified: %s', 'affiliate-wp' ), $this->data['affiliate_id'] ) );
				}
			}

			// Get the referrals
			$referrals = affiliate_wp()->referrals->get_referrals( $args );

			if ( empty( $referrals ) ) {
				return [];
			}

			// Store the items
			$this->store_items( $referrals );

			// Return the items
			return $referrals;
		}

		/**
		 * Process a single payment
		 *
		 * @since 2.29.0
		 * @param object $referral The referral object
		 * @return array|WP_Error The result of the payment
		 */
		private function process_payment( $referral ) {
			// Log balance state before attempting payout
			if ( function_exists( 'affwp_stripe_payouts_log_balance_state' ) ) {
				affwp_stripe_payouts_log_balance_state( $referral->amount );
			}

			// Check platform balance before processing
			$balance_check = affwp_stripe_payouts_verify_sufficient_funds( $referral->amount );
			if ( is_wp_error( $balance_check ) ) {
				affwp_stripe_payouts_log_error(
					sprintf( 'Insufficient funds for referral #%d', $referral->referral_id ),
					[ 'error' => $balance_check->get_error_message() ]
				);
				return $balance_check;
			}

			// Initialize Stripe
			affwp_stripe_payouts_init_api();

			try {
				// Get Stripe account IDs
				$admin_account_id     = affwp_stripe_payouts_get_admin_account_id();
				$affiliate_account_id = affwp_stripe_payouts_get_affiliate_account_id( $referral->affiliate_id );

				// Get the amount in cents
				$amount = affwp_stripe_payouts_format_amount( $referral->amount );

				// Create a transfer
				$transfer = \Stripe\Transfer::create(
					[
						'amount'         => $amount,
						'currency'       => affwp_get_currency(),
						'destination'    => $affiliate_account_id,
						'transfer_group' => 'affwp_referral_' . $referral->referral_id,
						'metadata'       => [
							'referral_id'  => $referral->referral_id,
							'affiliate_id' => $referral->affiliate_id,
							'amount'       => $referral->amount,
							'description'  => sprintf( __( 'Referral #%d Payment (Batch Process)', 'affiliate-wp' ), $referral->referral_id ),
						],
					]
				);

				// Mark the referral as paid
				if ( $transfer && isset( $transfer->id ) ) {
					// Update the referral
					affwp_set_referral_status( $referral->referral_id, 'paid' );

					// Add a meta for the Stripe transfer ID
					affwp_add_referral_meta( $referral->referral_id, 'stripe_transfer_id', $transfer->id );

					// Return success
					return [
						'success'     => true,
						'message'     => sprintf( __( 'Referral #%d successfully paid via Stripe.', 'affiliate-wp' ), $referral->referral_id ),
						'transfer_id' => $transfer->id,
					];
				} else {
					throw new Exception( __( 'Stripe transfer was not created.', 'affiliate-wp' ) );
				}
			} catch ( Exception $e ) {
				// Log the error
				affwp_stripe_payouts_log_error(
					'Batch payout error: ' . $e->getMessage(),
					[
						'referral_id'  => $referral->referral_id,
						'affiliate_id' => $referral->affiliate_id,
						'amount'       => $referral->amount,
					]
				);

				// Return error
				return new WP_Error( 'stripe_error', $e->getMessage() );
			}
		}

		/**
		 * Calculate the percentage complete
		 *
		 * @since 2.29.0
		 * @return int The percentage complete
		 */
		public function get_percentage_complete() {
			return $this->percentage;
		}

		/**
		 * Define the form for this batch process
		 *
		 * @since 2.29.0
		 * @return array The form fields
		 */
		public function form() {
			// Check if Stripe is configured and admin is connected
			if ( ! affwp_stripe_payouts_is_configured() || ! affwp_stripe_payouts_is_admin_connected() ) {
				$html  = '<p>';
				$html .= __( 'Stripe is not fully configured or not connected. Please configure Stripe in the settings tab and connect your Stripe account.', 'affiliate-wp' );
				$html .= ' <a href="' . admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts' ) . '">' . __( 'Go to Stripe Settings', 'affiliate-wp' ) . '</a>';
				$html .= '</p>';

				return $html;
			}

			// Get the form fields
			$fields = [
				'from'         => [
					'label'       => __( 'From', 'affiliate-wp' ),
					'description' => __( 'The start date for referrals to process.', 'affiliate-wp' ),
					'type'        => 'date',
					'default'     => date( 'Y-m-d', strtotime( '-1 month' ) ),
				],
				'to'           => [
					'label'       => __( 'To', 'affiliate-wp' ),
					'description' => __( 'The end date for referrals to process.', 'affiliate-wp' ),
					'type'        => 'date',
					'default'     => date( 'Y-m-d' ),
				],
				'affiliate_id' => [
					'label'       => __( 'Affiliate', 'affiliate-wp' ),
					'description' => __( 'The affiliate to process payouts for. Leave blank for all affiliates.', 'affiliate-wp' ),
					'type'        => 'select',
					'options'     => $this->get_affiliates_with_stripe(),
					'default'     => '',
				],
				'warning'      => [
					'content' => '<div class="notice notice-warning inline"><p>' . __( 'Warning: This will process all unpaid referrals for the selected date range and affiliate(s), sending payouts via Stripe. Please confirm before proceeding.', 'affiliate-wp' ) . '</p></div>',
					'type'    => 'content',
				],
			];

			return $fields;
		}

		/**
		 * Get affiliates who have connected their Stripe accounts
		 *
		 * @since 2.29.0
		 * @return array The affiliates
		 */
		private function get_affiliates_with_stripe() {
			global $wpdb;

			// Get all active affiliates
			$affiliates = affiliate_wp()->affiliates->get_affiliates(
				[
					'status'  => 'active',
					'number'  => -1,
					'orderby' => 'name',
					'order'   => 'ASC',
				]
			);

			// Initialize options array with empty option
			$options = [
				'' => __( 'All Stripe-Connected Affiliates', 'affiliate-wp' ),
			];

			if ( ! empty( $affiliates ) ) {
				foreach ( $affiliates as $affiliate ) {
					// Check if affiliate has a Stripe account connected
					if ( affwp_stripe_payouts_is_affiliate_connected( $affiliate->affiliate_id ) ) {
						$user_id = affwp_get_affiliate_user_id( $affiliate->affiliate_id );
						$user    = get_userdata( $user_id );

						if ( $user ) {
							$name = $affiliate->name;
							if ( empty( $name ) && $user ) {
								$name = $user->display_name;
							}
							$options[ $affiliate->affiliate_id ] = sprintf( '#%d - %s', $affiliate->affiliate_id, $name );
						}
					}
				}
			}

			return $options;
		}

		/**
		 * Process a step
		 *
		 * @since 2.29.0
		 * @return bool Whether the step was processed
		 */
		public function process_step() {
			// Ensure that the admin has a valid Stripe connection
			if ( ! affwp_stripe_payouts_is_admin_connected() ) {
				wp_die( __( 'Stripe account is not connected. Please connect your Stripe account in the settings tab.', 'affiliate-wp' ) );
			}

			// Get the batch items
			$items = $this->get_items();

			if ( empty( $items ) ) {
				return false;
			}

			// Process a batch
			$this->process_batch();

			// Update the percentage
			$total = affiliate_wp()->referrals->count(
				[
					'status'       => 'unpaid',
					'date'         => [
						'start' => $this->data['from'],
						'end'   => $this->data['to'],
					],
					'affiliate_id' => ! empty( $this->data['affiliate_id'] ) && ! is_wp_error( affwp_stripe_payouts_validate_affiliate_id( $this->data['affiliate_id'] ) ) ? absint( $this->data['affiliate_id'] ) : 0,
				]
			);

			if ( $total > 0 ) {
				// Calculate the percentage based on remaining items
				$remaining = affiliate_wp()->referrals->count(
					[
						'status'       => 'unpaid',
						'date'         => [
							'start' => $this->data['from'],
							'end'   => $this->data['to'],
						],
						'affiliate_id' => ! empty( $this->data['affiliate_id'] ) && ! is_wp_error( affwp_stripe_payouts_validate_affiliate_id( $this->data['affiliate_id'] ) ) ? absint( $this->data['affiliate_id'] ) : 0,
					]
				);

				$this->percentage = 100 - ( ( $remaining / $total ) * 100 );
			} else {
				$this->percentage = 100;
			}

			return true;
		}

		/**
		 * Cancel the batch process
		 *
		 * @since 2.29.0
		 * @return void
		 */
		public function cancel_process() {
			$this->delete_data( 'items' );
			$this->delete_data();
			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-reports' ) );
			exit;
		}

		/**
		 * Complete the batch process
		 *
		 * @since 2.29.0
		 * @return void
		 */
		public function finish() {
			// Delete data and display success message
			$this->delete_data( 'items' );
			$this->delete_data();

			// Display a success message
			affiliate_wp()->notices->add( 'stripe_payouts_batch_complete', __( 'Stripe payouts completed successfully!', 'affiliate-wp' ), 'success' );

			// Redirect to the reports page
			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-reports&tab=payouts' ) );
			exit;
		}
	}
}
