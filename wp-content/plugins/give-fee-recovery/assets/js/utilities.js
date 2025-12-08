/**
 * Give Fees Recovery Add-on file that contain function that are been used in frontend and backend both.
 */

import accounting from 'accounting'

/**
 * Calculate Fee.
 *
 * @param percentage
 * @param base_amount
 * @param give_total
 * @param give_fee_disable
 *
 * @returns {number}
 */
export function give_fee_calculate( percentage, base_amount, give_total, give_fee_disable ) {
	var fee = 0;
	if ( '' !== percentage && '' !== base_amount && false === give_fee_disable ) {
		// Calculate Fee based on Flat or not.
		if ( percentage > 0 && base_amount > 0 ) {
			fee = give_fee_formula( percentage, base_amount, give_total );
		} else {
			fee = give_fee_flat_formula( percentage, base_amount, give_total );
		}
	}
	return fee;
}

/**
 * Calculate Fee Formula.
 *
 * @param percentage
 * @param additionalFeeAmount
 * @param donationAmount
 *
 * @returns {number}
 */
export function give_fee_formula( percentage, additionalFeeAmount, donationAmount ) {

	// Parse the value as float.
	percentage          = parseFloat( percentage );
	additionalFeeAmount = parseFloat( additionalFeeAmount );
	donationAmount      = parseFloat( donationAmount );

	// Calculate Fee based on new Formula.
	const totalWithFee = ( donationAmount + additionalFeeAmount ) / ( 1 - ( percentage / 100 ) );

	// Return Fee Amount.
	return totalWithFee - donationAmount;
}

/**
 * Calculate Fee with Flat formula.
 *
 * @param percentage
 * @param additionalFeeAmount
 * @param donationAmount
 *
 * @returns {*}
 */
export function give_fee_flat_formula( percentage, additionalFeeAmount, donationAmount ) {

	// Parse the value as float.
	percentage          = parseFloat( percentage );
	additionalFeeAmount = parseFloat( additionalFeeAmount );
	donationAmount      = parseFloat( donationAmount );

	// Calculate Fee based on Flat Formula.
	return donationAmount * ( percentage / 100 ) + additionalFeeAmount;
}

/**
 * Unformat Currency.
 *
 * @param price
 * @param decimal_separator
 * @returns {number}
 */
export function give_fee_unformat_amount( price, decimal_separator ) {

	if ( decimal_separator ) {
		return Math.abs( parseFloat( accounting.unformat( price, decimal_separator ) ) );
	}

	if ( 'undefined' !== typeof( give_global_vars ) && 'undefined' !== typeof( give_global_vars.decimal_separator ) ) {
		return Math.abs( parseFloat( accounting.unformat( price, give_global_vars.decimal_separator ) ) );
	} else if ( 'undefined' !== typeof( give_vars ) && 'undefined' !== typeof( give_vars.decimal_separator ) ) {
		return Math.abs( parseFloat( accounting.unformat( price, give_vars.decimal_separator ) ) );
	}
}

/**
 * Helper function to get the formatted amount.
 *
 * @param {string/number} amount
 * @param {jQuery} form
 */
export function give_fee_format_amount( amount, form ) {

	var vars,
		precision;

	if ( 'undefined' !== typeof(give_global_vars) ) {
		vars = give_global_vars;
		precision = Give.form.fn.getInfo( 'number_decimals', form );

		// Get currency symbol by form.
		vars[ 'currency_sign' ] = Give.form.fn.getInfo( 'currency_symbol', form );
		vars[ 'decimal_separator' ] = Give.form.fn.getInfo( 'decimal_separator', form );
		vars[ 'thousands_separator' ] = Give.form.fn.getInfo( 'thousands_separator', form );
		vars[ 'currency_code' ] = Give.form.fn.getInfo( 'currency_code', form );
	} else {
		vars = give_vars;
		precision = give_vars.currency_decimals;
		vars[ 'decimal_separator' ] = vars.decimal_separator;
		vars[ 'currency_code' ] = give_fee_recovery_object.give_fee_currency_code;
	}

	var give_fee_zero_based_currency = give_fee_recovery_object.give_fee_zero_based_currency,
		zero_based_currencies = JSON.parse( give_fee_zero_based_currency );

	// Set precision.
	if ( 1 >= parseInt( precision ) && - 1 === jQuery.inArray( vars.currency_code, zero_based_currencies ) ) {
		precision = 2;
	}

	if ( - 1 !== jQuery.inArray( vars.currency_code, zero_based_currencies ) ) {
		precision = 0;
	}

	// Set the custom amount input value format properly.
	var format_args = {
		symbol: vars.currency_sign,
		decimal: vars.decimal_separator,
		thousand: vars.thousands_separator,
		precision: precision,
		format: 'before' === vars.currency_pos ? '%s%v' : '%v%s'
	};
	return accounting.formatMoney( amount, format_args ); // Get the formatted currency value.
}
