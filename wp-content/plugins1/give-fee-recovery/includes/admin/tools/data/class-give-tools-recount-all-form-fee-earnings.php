<?php
/**
 * Recount all forms fee earnings.
 *
 * This class handles batch processing of recounting form fee earnings for all forms.
 *
 * @subpackage Admin/Tools/Give_Tools_Recount_All_Form_Fee_Earnings
 * @copyright  Copyright (c) 2016, GiveWP
 * @license    https://opensource.org/licenses/gpl-license GNU Public License
 * @since      1.5.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Tools_Recount_All_Form_Fee_Earnings Class
 *
 * @since 1.5.1
 */
class Give_Tools_Recount_All_Form_Fee_Earnings extends Give_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @since 1.5.1
	 * @var   string
	 */
	public $export_type = '';

	/**
	 * Allows for a non-form batch processing to be run.
	 *
	 * @since 1.5.1
	 * @var   bool
	 */
	public $is_void = true;

	/**
	 * Sets the number of items to pull on each step
	 *
	 * @since 1.5.1
	 * @var   int
	 */
	public $per_step = 30;

	/**
	 * Display message on completing recount process
	 *
	 * @since 1.5.1
	 * @var   string
	 */
	public $message = '';

	/**
	 * Sets donation form id for recalculation
	 *
	 * @since 1.5.1
	 * @var   int
	 */
	protected $form_id = 0;

	/**
	 * Is Recount process completed
	 *
	 * @since 1.5.1
	 * @var   bool
	 */
	public $done = false;

	/**
	 * Constructor.
	 */
	public function __construct( $_step = 1 ) {
		parent::__construct( $_step );

		$this->is_writable = true;
	}

	/**
	 * Get the recount all stats data
	 *
	 * @access public
	 * @since  1.5.1
     * @since 1.9.7 Set default fee amount to zero instead of empty string.
	 *
	 * @return bool
	 */
	public function get_data() {

		/**
		 * Update Payment statuses.
		 *
		 * @since 1.5.1
		 */
		$accepted_statuses = apply_filters( 'give_fee_recount_accepted_statuses', array( 'publish', 'give_subscription' ) );

		$totals = $this->get_stored_data( 'give_fee_temp_recount_all_form_stats' );
		if ( false === $totals ) {
			$totals = array();
		}

		$all_forms = $this->get_stored_data( 'give_fee_temp_form_ids' );
		$payments  = $this->get_stored_data( 'give_fee_temp_all_payments_data' );

		if ( empty( $payments ) ) {
			$args = apply_filters( 'give_fee_recount_form_fee_earnings_args', array(
				'give_forms' => $all_forms,
				'number'     => $this->per_step,
				'status'     => $accepted_statuses,
				'paged'      => $this->step,
				'output'     => '',
				'fields'     => 'ids',
			) );

			$payments_query = new Give_Payments_Query( $args );
			$payments       = $payments_query->get_payments();
		}

		if ( ! empty( $payments ) ) {

			// Loop through payments.
			foreach ( $payments as $payment_id ) {

				// Get the payment ID.
				$payment_id = absint( $payment_id );

				// Ensure acceptable status only.
				if ( ! in_array( get_post_status( $payment_id ), $accepted_statuses ) ) {
					continue;
				}

				$form_id    = give_get_payment_form_id( $payment_id );
				$fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );

				/**
				 * Update Fee amount.
				 *
				 * @since 1.5.1
				 *
				 * @param int $payment_id
				 */
				$fee_amount = apply_filters( 'give_fee_recovery_fee_amount', $fee_amount ?: 0, $payment_id );
				$fee_amount = ! empty( $fee_amount ) ? $fee_amount : 0;

				// Set Total Form Fee Earnings.
				if ( ! isset( $totals[ $form_id ]['form_fee_earnings'] ) ) {
					$totals[ $form_id ]['form_fee_earnings'] = 0;
				}

				// Store Form fee earnings.
				$totals[ $form_id ]['form_fee_earnings'] += $fee_amount;

			} // End Foreach().

			$this->store_data( 'give_fee_temp_recount_all_form_stats', $totals );

			return true;
		} // End if().

		foreach ( $totals as $key => $stats ) {
			give_update_meta( $key, '_give_form_fee_earnings', give_sanitize_amount_for_db( $stats['form_fee_earnings'] ) );
		}

		return false;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.5.1
	 * @return int
	 */
	public function get_percentage_complete() {

		$total = $this->get_stored_data( 'give_fee_recount_all_form_earnings' );

		if ( false === $total ) {
			$this->pre_fetch();
			$total = $this->get_stored_data( 'give_fee_recount_all_form_earnings' );
		}

		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the payments export
	 *
	 * @since 1.5.1
	 *
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->form_id = isset( $request['form_id'] ) ? sanitize_text_field( $request['form_id'] ) : false;
	}

	/**
	 * Process a step
	 *
	 * @since 1.5.1
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( esc_html__( 'You do not have permission to recount stats.', 'give-fee-recovery' ), esc_html__( 'Error', 'give-fee-recovery' ), array( 'response' => 403 ) );
		}

		$had_data = $this->get_data();

		if ( $had_data ) {
			$this->done = false;

			return true;
		} else {
			$this->delete_data( 'give_fee_recount_all_form_earnings' );
			$this->delete_data( 'give_fee_temp_recount_all_form_stats' );
			$this->delete_data( 'give_fee_temp_payment_items' );
			$this->delete_data( 'give_fee_temp_form_ids' );
			$this->done    = true;
			$this->message = esc_html__( 'All Form fee earnings successfully recounted.', 'give-fee-recovery' );

			return false;
		}
	}

	/**
	 * Set headers.
	 *
	 * @since  1.5.1
	 */
	public function headers() {
		give_ignore_user_abort();
	}

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since  1.5.1
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		give_die();
	}

	/**
	 * Pre Fetch Data
	 *
	 * @access public
	 * @since  1.5.1
	 */
	public function pre_fetch() {

		if ( 1 == $this->step ) {
			$this->delete_data( 'give_fee_temp_recount_all_form_fee_earnings' );
			$this->delete_data( 'give_fee_temp_recount_all_form_stats' );
			$this->delete_data( 'give_fee_temp_payment_items' );
			$this->delete_data( 'give_fee_temp_all_payments_data' );
		}

		/**
		 * Update Payment statuses.
		 *
		 * @since 1.5.1
		 */
		$accepted_statuses = apply_filters( 'give_fee_recount_accepted_statuses', array( 'publish', 'give_subscription' ) );
		$total             = $this->get_stored_data( 'give_fee_temp_recount_all_form_fee_earnings' );

		if ( false === $total ) {

			$payment_items = $this->get_stored_data( 'give_fee_temp_payment_items' );

			if ( false === $payment_items ) {
				$payment_items = array();
				$this->store_data( 'give_fee_temp_payment_items', $payment_items );
			}

			$args = array(
				'post_status'    => 'publish',
				'post_type'      => 'give_forms',
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			);

			$all_forms = get_posts( $args );

			$this->store_data( 'give_fee_temp_form_ids', $all_forms );

			$args = apply_filters( 'give_fee_recount_all_form_earnings_args', array(
				'give_forms' => $all_forms,
				'number'     => $this->per_step,
				'status'     => $accepted_statuses,
				'page'       => $this->step,
				'fields'     => 'ids',
				'output'     => '',
			) );

			$payments_query = new Give_Payments_Query( $args );
			$payments       = $payments_query->get_payments();

			$total = count( $payments );

			$this->store_data( 'give_fee_temp_all_payments_data', $payments );

			if ( $payments ) {

				foreach ( $payments as $payment_id ) {

					$form_id = give_get_payment_form_id( $payment_id );
					if ( ! in_array( get_post_status( $payment_id ), $accepted_statuses ) ) {
						continue;
					}

					if ( ! array_key_exists( get_post_status( $payment_id ), $payment_items ) ) {
						$payment_items[ $payment_id ] = array(
							'id'         => $form_id,
							'payment_id' => $payment_id,
							'price'      => give_get_payment_total( $payment_id ),
						);
					}
				}
			}
			$this->store_data( 'give_fee_temp_payment_items', $payment_items );
			$this->store_data( 'give_fee_recount_all_form_earnings', $total );
		}

	}

	/**
	 * Given a key, get the information from the Database Directly
	 *
	 * @since  1.5.1
	 *
	 * @param  string $key The option_name
	 *
	 * @return mixed       Returns the data from the database
	 */
	private function get_stored_data( $key ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare(
			"SELECT option_value FROM $wpdb->options WHERE option_name = '%s'", $key
		) );
		if ( empty( $value ) ) {
			return false;
		}

		$maybe_json = json_decode( $value );

		if ( ! is_null( $maybe_json ) ) {
			$value = json_decode( $value, true );
		}

		return $value;
	}

	/**
	 * Give a key, store the value
	 *
	 * @since  1.5.1
	 *
	 * @param  string $key   The option_name
	 * @param  mixed  $value The value to store
	 *
	 * @return void
	 */
	private function store_data( $key, $value ) {
		global $wpdb;

		$value = is_array( $value ) ? wp_json_encode( $value ) : esc_attr( $value );

		$data = array(
			'option_name'  => $key,
			'option_value' => $value,
			'autoload'     => 'no',
		);

		$formats = array(
			'%s',
			'%s',
			'%s',
		);

		$wpdb->replace( $wpdb->options, $data, $formats );
	}

	/**
	 * Delete an option
	 *
	 * @since  1.5.1
	 *
	 * @param  string $key The option_name to delete
	 *
	 * @return void
	 */
	private function delete_data( $key ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $key ) );
	}

}
