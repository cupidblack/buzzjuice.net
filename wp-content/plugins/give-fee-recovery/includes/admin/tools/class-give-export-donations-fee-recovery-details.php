<?php
/**
 * Batch Donation Fee Export Class.
 *
 * This class handles Fee recovery export.
 *
 * @package     Give-Fee-Recovery
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2016, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Give_Export_Donations_Fee_Recovery_Details' ) ) {

	/**
	 * This class deals with the export of Fee Recovery fields.
	 *
	 * @since 1.7
	 */
	class Give_Export_Donations_Fee_Recovery_Details {


		/**
		 * Constructor function.
		 */
		public function __construct() {
			add_action( 'give_export_donation_fields', array( $this, 'add_fee_recovery_fields_to_export_list' ) );
			add_filter( 'give_export_donation_get_columns_name', array( $this, 'fee_recovery_export_donation_get_columns_name' ), 10, 2 );
			add_filter( 'give_export_donation_data', array( $this, 'fee_recovery_give_export_donation_data' ), 30, 2 );
		}

		/**
		 * Adds custom row which contains checkboxes for Fee Recovery fields.
		 *
		 * @since 1.7
		 *
		 * @return void
		 */
		public function add_fee_recovery_fields_to_export_list() {
			?>
			<tr class="give-export-option-fields give-export-option-give-fee-recovery">
				<td scope="row" class="row-title">
					<label><?php esc_html_e( 'Fee Recovery Fields:', 'give-fee-recovery' ); ?></label>
				</td>
				<td class="give-field-wrap">
					<div class="give-clearfix">
						<ul class="give-export-option-fee-recovery-fields-ul">
							<!-- Donation Fee checkbox -->
							<li class="give-export-option-start">
								<label for="give-export-fee-recovery">
									<input type="checkbox" checked
									       name="give_give_donations_export_option[fee_recovery]"
									       id="give-export-fee-recovery"><?php _e( 'Donation Fee', 'give-fee-recovery' ); ?>
								</label>
							</li>
						</ul>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Add Fee recovery columns in CSV heading.
		 *
		 * @since 1.7
		 *
		 * @param array $cols    Columns names in CSV.
		 * @param array $columns Total number of column names in CSV.
		 *
		 * @return array $cols   Columns names in CSV.
		 */
		function fee_recovery_export_donation_get_columns_name( $cols, $columns ) {
			foreach ( $columns as $key => $value ) {
				switch ( $key ) {
					case 'fee_recovery' :
						$cols['fee_recovery'] = __( 'Donation Fee', 'give-fee-recovery' );
						break;

					default:
						break;
				}
			}

			return $cols;
		}


		/**
		 * Populates the CSV rows.
		 *
		 * @since 1.7
		 *
		 * @param array        $data    Donation data.
		 * @param Give_Payment $payment Instance of Give_Payment.
		 *
		 * @return array
		 */
		public function fee_recovery_give_export_donation_data( $data, $payment ) {

			$data['fee_recovery'] = __( 'Disabled', 'give-fee-recovery' );

			// Get Give Fee amount.
			$give_fee_amount = give_get_meta( $payment->ID, '_give_fee_amount', true );
			// Check if Give fee amount is not empty.
			if ( ! empty( $give_fee_amount ) ) {
				$data['fee_recovery'] = $give_fee_amount;
			}

			return $data;
		}
	}

	new Give_Export_Donations_Fee_Recovery_Details();
}
