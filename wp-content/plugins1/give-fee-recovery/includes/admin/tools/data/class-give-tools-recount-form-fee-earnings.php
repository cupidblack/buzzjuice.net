<?php
/**
 * Recount Form Fee Earnings for a Form.
 *
 * This class handles batch processing of recounting for fee earnings.
 *
 * @subpackage  Admin/Tools/Give_Tools_Recount_Form_Fee_Earnings
 * @copyright   Copyright (c) 2016, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.5.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Tools_Recount_Form_Fee_Earnings Class
 *
 * @since 1.5.1
 */
class Give_Tools_Recount_Form_Fee_Earnings extends Give_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 1.5.1
	 */
	public $export_type = '';

	/**
	 * Allows for a non-form batch processing to be run.
	 *
	 * @since  1.5.1
	 * @var boolean
	 */
	public $is_void = true;

	/**
	 * Sets the number of items to pull on each step
	 *
	 * @since  1.5.1
	 * @var integer
	 */
	public $per_step = 30;

	/**
	 * Sets the donation form ID to recalculate
	 *
	 * @since  1.5.1
	 * @var integer
	 */
	protected $form_id = null;

	/**
	 * Constructor.
	 */
	public function __construct( $_step = 1 ) {
		parent::__construct( $_step );

		$this->is_writable = true;
	}

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since  1.5.1
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

		if ( $this->step == 1 ) {
			$this->delete_data( 'give_fee_temp_recount_form_earnings' );
		}

		$totals = $this->get_stored_data( 'give_fee_temp_recount_form_earnings' );

		if ( false === $totals ) {
			$totals = array(
				'form_fee_earnings' => (float) 0,
			);
			$this->store_data( 'give_fee_temp_recount_form_earnings', $totals );
		}

		$args = apply_filters( 'give_fee_recount_form_fee_earnings_args', array(
			'give_forms' => $this->form_id,
			'number'     => $this->per_step,
			'status'     => $accepted_statuses,
			'paged'      => $this->step,
			'output'     => '',
			'fields'     => 'ids',
		) );

		$payments = new Give_Payments_Query( $args );
		$payments = $payments->get_payments();

		if ( $payments ) {
			foreach ( $payments as $payment_id ) {

				// Get the payment ID.
				$payment_id = absint( $payment_id );

				// Ensure acceptable status only.
				if ( ! in_array( get_post_status( $payment_id ), $accepted_statuses ) ) {
					continue;
				}

				// Payment form ID.
				$form_id = give_get_payment_form_id( $payment_id );

				// Ensure only payments for this form are counted.
				if ( $form_id !== absint( $this->form_id ) ) {
					continue;
				}

				$fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );

				/**
				 * Update Fee amount.
				 *
				 * @since 1.5.1
				 *
				 * @param int $payment_id
				 */
				$fee_amount = apply_filters( 'give_fee_recovery_fee_amount', $fee_amount  ?: 0, $payment_id );
				$fee_amount = ! empty( $fee_amount ) ? $fee_amount : 0;

				$totals['form_fee_earnings'] += $fee_amount;

			}

			$this->store_data( 'give_fee_temp_recount_form_earnings', $totals );

			return true;
		}

		give_update_meta( $this->form_id, '_give_form_fee_earnings', give_sanitize_amount_for_db( $totals['form_fee_earnings'] ) );

		return false;
	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.5.1
	 * @return int
	 */
	public function get_percentage_complete() {
		if ( $this->step == 1 ) {
			$this->delete_data( 'give_fee_recount_form_earnings_' . $this->form_id );
		}

		/**
		 * Update Payment statuses.
		 *
		 * @since 1.5.1
		 */
		$accepted_statuses = apply_filters( 'give_fee_recount_accepted_statuses', array( 'publish', 'give_subscription' ) );
		$total             = $this->get_stored_data( 'give_fee_recount_form_earnings_' . $this->form_id );

		if ( false === $total ) {
			$args = apply_filters( 'give_fee_recount_form_fee_earning_args', array(
				'give_forms' => $this->form_id,
				'number'     => - 1,
				'status'     => $accepted_statuses,
				'fields'     => 'ids',
			) );

			$payments = new Give_Payments_Query( $args );
			$total    = count( $payments->get_payments() );
			$this->store_data( 'give_fee_recount_form_earnings_' . $this->form_id, $total );
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
			$this->delete_data( 'give_fee_recount_form_earnings_' . $this->form_id );
			$this->delete_data( 'give_fee_temp_recount_form_earnings' );
			$this->done    = true;
			$this->message = sprintf( esc_html__( 'Form fee earnings successfully recounted for "%s".', 'give-fee-recovery' ), get_the_title( $this->form_id ) );

			return false;
		}
	}

	/**
	 * Set Header.
	 *
	 * @access public
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
