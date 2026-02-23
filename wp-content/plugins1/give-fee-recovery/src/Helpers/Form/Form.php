<?php

namespace GiveFeeRecovery\Helpers\Form;

use function give_get_payment_meta as getDonationMetaData;

class Form {
	/**
	 * Return whether or not form accept fee.
	 *
	 * @since 1.7.9
	 *
	 * @param $formId
	 *
	 * @return bool
	 */
	public static function canRecoverFee( $formId ) {
		return self::hasCustomFeeSettings( $formId ) || self::hasGlobalFeeSettings( $formId );
	}

	/**
	 * Return whether or not donation has fee.
	 *
	 * @since 1.7.9
	 *
	 * @param  int  $donationId
	 *
	 * @return bool
	 */
	public static function hasFeeAmount( $donationId ) {
		return (bool) getDonationMetaData( $donationId, '_give_fee_amount', true );
	}

	/**
	 * @since 1.9.1
	 *
	 * @return bool
	 */
	public static function hasCustomFeeSettings( $formId ) {
		return give_is_setting_enabled( give_get_meta( $formId, '_form_give_fee_recovery', true ) );
	}

	/**
	 * @since 1.9.1
	 *
	 * @param  int  $formId
	 *
	 * @return bool
	 */
	public static function hasGlobalFeeSettings( $formId ) {
		$optionValue = give_get_meta( $formId, '_form_give_fee_recovery', true );
		$optionValue = ! empty( $optionValue ) ? $optionValue : 'global';

		return give_is_setting_enabled( $optionValue, 'global' ) &&
		       give_is_setting_enabled( give_get_option( 'give_fee_recovery' ) );
	}

	/**
	 * @since 1.9.1
	 *
	 * @return string
	 */
	public static function getFeeConfigType( $formId ) {
		if ( self::hasCustomFeeSettings( $formId ) ) {
			return 'form';
		}

		if ( self::hasGlobalFeeSettings( $formId ) ) {
			return 'global';
		}

		return '';
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $formId
	 * @param string $selectedPaymentMethod
	 *
	 * @return bool
	 */
	public static function hasPaymentGatewayFeeSetting( $formId, $selectedPaymentMethod ){
		return give_is_setting_enabled( give_get_meta( $formId, '_form_give_fee_configuration', true ), 'per_gateway' ) &&
			give_is_setting_enabled( give_get_meta( $formId, "_form_gateway_fee_enable_$selectedPaymentMethod", true ) );
	}
}
