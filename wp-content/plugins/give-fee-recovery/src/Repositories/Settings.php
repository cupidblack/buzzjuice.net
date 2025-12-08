<?php

namespace GiveFeeRecovery\Repositories;

use GiveFeeRecovery\Helpers\Form\Form;
use GiveFeeRecovery\ValueObjects\DefaultFeeSetting;
use GiveFeeRecovery\ValueObjects\SettingOptionKey;

/**
 * Class Settings
 * @package GiveFeeRecovery\Repositories
 * @since 1.9.1
 */
class Settings {
	/**
	 * This helper fn will be used to get maximum fee amount.
	 *
	 * @param  string  $selectedGateway  Selected Payment Gateway.
	 *
	 * @param  int  $formId  Donation Form ID.
	 *
	 * @return string
	 */
	public static function getMaximumFeeAmount( $formId = 0, $selectedGateway = '' ) {
		if ( Form::hasCustomFeeSettings( $formId ) ) {
			$amount = $selectedGateway ?
				give_get_meta(
					$formId,
					"_form_gateway_fee_maximum_fee_amount_$selectedGateway",
					true,
					(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
				) :
				give_get_meta(
					$formId,
					'_form_give_fee_maximum_fee_amount',
					true,
					(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
				);
		} else {
			$amount = $selectedGateway ?
				give_get_option(
					"give_fee_gateway_fee_maximum_fee_amount_$selectedGateway",
					(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
				) :
				give_get_option(
					'give_fee_maximum_fee_amount',
					(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
				);
		}

		$floatAmount = (float) $amount;

		return ! empty( $floatAmount ) ? give_sanitize_amount_for_db( $amount ) : '0';
	}

	/**
	 * This function return fee settings (percentage, additional amount, maximum fee amount) fon donation form for
	 * fee calculation.
	 *
	 * @since 1.9.1
	 * @since 1.9.2 Modify function access to static
	 * @return string[]
	 */
	public static function getSettingsForFeeCalculation( $formId, $selectedPaymentMethod = '' ) {
		$self          = new static();
		$result        = [];
		$feeConfigType = Form::getFeeConfigType( $formId );

		if ( ! $feeConfigType ) {
			return $result;
		}

		switch ( $feeConfigType ) {
			case  'global':
				return $self->getGlobalFeeConfig( $selectedPaymentMethod );

			case 'form':
				return $self->getFormFeeConfig( $formId, $selectedPaymentMethod );

			default:
				return $result;
		}
	}

	/**
	 * @since 1.9.2
	 *
	 * @param  string  $selectedPaymentMethod
	 *
	 * @return array
	 */
	private function getGlobalFeeConfig( $selectedPaymentMethod ) {
		$result     = [];
		$hasPaymentMethodSetting = give_is_setting_enabled( give_get_option( "give_fee_gateway_fee_enable_option_$selectedPaymentMethod" ) ) &&
			give_is_setting_enabled( give_get_option( 'give_fee_configuration' ), 'per_gateway' );
		$optionKeys = SettingOptionKey::getGlobalSettingOptionKeysForFee(
			$selectedPaymentMethod,
			$hasPaymentMethodSetting
		);

		$result['fee_percentage']        = give_get_option(
			$optionKeys['fee_percentage'],
			(string) DefaultFeeSetting::percentage()->getValue()
		);
		$result['additional_fee_amount'] = give_get_option(
			$optionKeys['additional_fee_amount'],
			(string) DefaultFeeSetting::additionalAmount()->getValue()
		);
		$result['maximum_fee_amount']    = give_get_option(
			$optionKeys['maximum_fee_amount'],
			(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
		);

		return $result;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param  int  $formId
	 * @param  string  $selectedPaymentMethod
	 *
	 * @return array
	 */
	private function getFormFeeConfig( $formId, $selectedPaymentMethod ) {
		$result     = [];
		$optionKeys = SettingOptionKey::getFormSettingOptionKeysForFee(
			$selectedPaymentMethod,
			Form::hasPaymentGatewayFeeSetting( $formId, $selectedPaymentMethod )
		);

		$result['fee_percentage']        = give_get_meta(
			$formId,
			$optionKeys['fee_percentage'],
			true,
			(string) DefaultFeeSetting::percentage()->getValue()
		);
		$result['additional_fee_amount'] = give_get_meta(
			$formId,
			$optionKeys['additional_fee_amount'],
			true,
			(string) DefaultFeeSetting::additionalAmount()->getValue()
		);
		$result['maximum_fee_amount']    = give_get_meta(
			$formId,
			$optionKeys['maximum_fee_amount'],
			true,
			(string) DefaultFeeSetting::maximumFeeAmount()->getValue()
		);

		return $result;
	}
}
