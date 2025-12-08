<?php

namespace GiveFeeRecovery\Service;

use GiveFeeRecovery\Helpers\Form\Form;
use GiveFeeRecovery\Repositories\Settings;

class AddFeeToDonationAmount {
	/**
	 * Add calculated fee and update total.
	 *
	 * @since 1.9.1
	 *
	 * @param  string  $donationAmount  Donation total amount.
	 *
	 * @return string
	 */
	public function __invoke( $donationAmount ) {
		$formId = isset( $_POST['give-form-id'] ) ?
			absint( $_POST['give-form-id'] ) :
			absint( $_POST['give_form_id'] );

		if ( ! Form::canRecoverFee( $formId ) ) {
			return $donationAmount;
		}

		$selectedPaymentMethod = isset( $_POST['payment-mode'] ) ?
			give_clean( $_POST['payment-mode'] ) :
			give_clean( $_POST['give_payment_mode'] );

		$form_currency      = give_get_currency( $formId );
		$feeConfig          = Settings::getSettingsForFeeCalculation( $formId, $selectedPaymentMethod );

		if ( $feeConfig && $this->hasDonorPermissionToAddFee( $formId ) ) {
			return (string) CalculateNetDonationAmount::get( $donationAmount, $form_currency, $feeConfig );
		}

		return $donationAmount;
	}

	/**
	 * @since 1.9.1
	 *
	 * @param  int  $formId
	 *
	 * @return bool
	 */
	private function hasDonorPermissionToAddFee( $formId ) {
		$hasDonorGrant = isset( $_POST['give-fee-mode-enable'] ) ?
			filter_var( $_POST['give-fee-mode-enable'], FILTER_VALIDATE_BOOLEAN ) :
			false;

		if ( $hasDonorGrant ) {
			return $hasDonorGrant;
		}

		$donorFeeOptInType = '';

		switch ( Form::getFeeConfigType( $formId ) ) {
			case 'global':
				$donorFeeOptInType = give_get_option( 'give_fee_mode' );
				break;

			case 'form':
				$donorFeeOptInType = give_get_meta( $formId, '_form_give_fee_mode', true );
				break;
		}

		return give_is_setting_enabled( $donorFeeOptInType, 'forced_opt_in' );
	}
}
