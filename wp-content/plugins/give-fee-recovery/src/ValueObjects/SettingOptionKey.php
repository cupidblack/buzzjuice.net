<?php

namespace GiveFeeRecovery\ValueObjects;

/**
 * Class SettingOptionKey
 * @package GiveFeeRecovery\ValueObjects
 */
class SettingOptionKey {

	/**
	 * @since 1.9.2
	 *
	 * @param  string  $selectedPaymentMethod
	 * @param  bool  $hasFeeForSelectedPaymentMethod
	 *
	 * @return array
	 */
	public static function getGlobalSettingOptionKeysForFee(
		$selectedPaymentMethod = '',
		$hasFeeForSelectedPaymentMethod = false
	) {
		$feePercentageOptionKey       = 'give_fee_percentage';
		$additionalFeeAmountOptionKey = 'give_fee_base_amount';
		$maximumFeeAmountOptionKey    = 'give_fee_maximum_fee_amount';

		if ( $hasFeeForSelectedPaymentMethod ) {
			$feePercentageOptionKey       = "give_fee_gateway_fee_percentage_$selectedPaymentMethod";
			$additionalFeeAmountOptionKey = "give_fee_gateway_fee_base_amount_$selectedPaymentMethod";
			$maximumFeeAmountOptionKey    = "give_fee_gateway_fee_maximum_fee_amount_$selectedPaymentMethod";
		}

		return [
			'fee_percentage'        => $feePercentageOptionKey,
			'additional_fee_amount' => $additionalFeeAmountOptionKey,
			'maximum_fee_amount'    => $maximumFeeAmountOptionKey,
		];
	}

	/**
	 * @since 1.9.2
	 *
	 * @param  string  $selectedPaymentMethod
	 * @param  bool  $hasFeeForSelectedPaymentMethod
	 *
	 * @return array
	 */
	public static function getFormSettingOptionKeysForFee(
		$selectedPaymentMethod = '',
		$hasFeeForSelectedPaymentMethod = false
	) {
		$feePercentageOptionKey       = '_form_give_fee_percentage';
		$additionalFeeAmountOptionKey = '_form_give_fee_base_amount';
		$maximumFeeAmountOptionKey    = '_form_give_fee_maximum_fee_amount';

		if ( $hasFeeForSelectedPaymentMethod ) {
			$feePercentageOptionKey       = "_form_gateway_fee_percentage_$selectedPaymentMethod";
			$additionalFeeAmountOptionKey = "_form_gateway_fee_base_amount_$selectedPaymentMethod";
			$maximumFeeAmountOptionKey    = "_form_gateway_fee_maximum_fee_amount_$selectedPaymentMethod";
		}

		return [
			'fee_percentage'        => $feePercentageOptionKey,
			'additional_fee_amount' => $additionalFeeAmountOptionKey,
			'maximum_fee_amount'    => $maximumFeeAmountOptionKey,
		];
	}
}
