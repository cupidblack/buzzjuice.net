/**
 * Give Fee Recovery Admin JS.
 */

import {give_fee_format_amount, give_fee_unformat_amount} from './utilities'

var give_vars = window.give_vars;

jQuery( document ).ready( function( $ ) {

	var $body = $( 'body' ),
		globally_option = $( 'input[name="give_fee_recovery"]:radio' ),
		give_fee_all_fields = $( '.give_fee_all_fields' ),
		give_fee_configuration = $( '.give-forminp input[name="give_fee_configuration"]:radio' ),
		give_fee_mode = $( '.give-forminp input[name="give_fee_mode"]:radio' ),
		gateway_options = $( '.give-setting-tab-body .give_fee_gateways_fields .give_fee_gateway input:radio' ),
		give_fee_all_gateway = $( '.give_fee_all_gateway' ),
		give_fee_per_gateway = $( '.give_fee_per_gateway' ),
		give_fee_checkbox_label = $( '.give_fee_checkbox_label' ),
		give_fee_explanation = $( '.give_fee_explanation' );

	/**
	 * Show/Hide global Fee Recovery options.
	 */
	globally_option.on( 'change', function() {

		// Get the value of checked radio button.
		var value = $( 'input[name="give_fee_recovery"]:radio:checked' ).val();

		// If enable show other fields.
		if ( value === 'enabled' ) {
			give_fee_all_fields.show();
			give_fee_configuration.change();
			give_fee_mode.change();
			gateway_options.change();

		} else {
			// Otherwise, hide rest of fields.
			give_fee_all_fields.hide();
		}

	} ).change();

	/**
	 * Show/Hide Gateway options based on All Gateway/Per Form Gateway.
	 */
	give_fee_configuration.on( 'change', function() {

		// Get the value of checked radio button value of Fee Recovery.
		var global_value = $( 'input[name="give_fee_recovery"]:radio:checked' ).val(),
			value = $( 'input[name="give_fee_configuration"]:radio:checked' ).val();

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		if ( value === 'all_gateways' ) {
			give_fee_per_gateway.hide();
			give_fee_all_gateway.show();
		} else {
			give_fee_per_gateway.show();
			give_fee_all_gateway.hide();
		}

	} ).change();

	/**
	 * Show/Hide based on 'Fee Mode' option.
	 */
	give_fee_mode.on( 'change', function() {

		// Get the value of checked radio button value of Fee Recovery.
		var global_value = $( 'input[name="give_fee_recovery"]:radio:checked' ).val(),
			value = $( 'input[name="give_fee_mode"]:radio:checked' ).val();

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		if ( $( '.give_fee_mode' ).is( ':visible' ) ) {

			if ( value === 'donor_opt_in' ) {
				give_fee_checkbox_label.show();
				give_fee_explanation.hide();
			} else {
				give_fee_checkbox_label.hide();
				give_fee_explanation.show();
			}

		}

	} ).change();

	/**
	 * Show/Hide based on Per Gateway 'Fee enable'.
	 */
	gateway_options.on( 'change', function() {

		// Get the value of checked radio button value.
		var global_value = $( 'input[name="give_fee_recovery"]:radio:checked' ).val(),
			fee_enable = jQuery( '.give_fee_gateways_fields input:radio:checked' );

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		// Loop each Gateway to check fee enable option and based on it show/hide section.
		fee_enable.each( function() {

			var $this = $( this ),
				value = $this.val();

			if ( value === 'enabled' ) {
				$this.closest( 'fieldset.give_fee_gateway' ).find( '.give_fee_gateway_fee_percentage' ).show();
				$this.closest( 'fieldset.give_fee_gateway' ).find( '.give_fee_gateway_fee_base_amount' ).show();
			} else {
				$this.closest( 'fieldset.give_fee_gateway' ).find( '.give_fee_gateway_fee_percentage' ).hide();
				$this.closest( 'fieldset.give_fee_gateway' ).find( '.give_fee_gateway_fee_base_amount' ).hide();
			}
		} );
	} ).change();

	/** Per Form field setting JS script form start here. */
	var give_fee_recovery = $( '._form_give_fee_recovery_field input[name="_form_give_fee_recovery"]:radio' ),
		_form_fee_mode_field = $( '._form_give_fee_mode_field input[name="_form_give_fee_mode"]:radio' ),
		_set_per_gateway = $( '._form_give_fee_configuration_field input[name="_form_give_fee_configuration"]:radio' ),
		_gateway_fee_enable = $( '#form_fee_options .give_fee_gateways_fields .give_fee_gateway input:radio' );

	/**
	 * Show/Hide Per-Form based Fee recovery option.
	 */
	give_fee_recovery.on( 'change', function() {

		var give_fee_recovery = $( 'input[name="_form_give_fee_recovery"]:radio:checked' ).val();

		if ( 'enabled' === give_fee_recovery ) {

			give_fee_all_fields.show();
			_form_fee_mode_field.change();
			_set_per_gateway.change();

		} else {
			give_fee_all_fields.hide();
		}
	} ).change();

	/**
	 * Show/Hide Per-Form based Fee mode change event.
	 */
	_form_fee_mode_field.on( 'change', function() {

		// Get the value of checked radion button value.
		var global_value = $( 'input[name="_form_give_fee_recovery"]:radio:checked' ).val(),
			value = $( 'input[name="_form_give_fee_mode"]:radio:checked' ).val();

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		// If enabled, then show checkout label.
		if ( value === 'donor_opt_in' ) {
			give_fee_checkbox_label.show();
			give_fee_explanation.hide();
		} else {
			// Otherwise, 'Fee Explanation'.
			give_fee_checkbox_label.hide();
			give_fee_explanation.show();
		}

	} ).change();

	/**
	 * Show/Hide Per-Form based Fee Configuration change event.
	 */
	_set_per_gateway.on( 'change', function() {

		// Get the value of checked radio button value of Fee Recovery.
		var global_value = $( 'input[name="_form_give_fee_recovery"]:radio:checked' ).val(),
			value = $( 'input[name="_form_give_fee_configuration"]:radio:checked' ).val(),
			$this = $( this );

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		if ( value === 'all_gateways' ) {
			$this.addClass( 'no-border' );
			give_fee_per_gateway.hide();
			give_fee_all_gateway.show();
		} else {
			$this.removeClass( 'no-border' );
			give_fee_per_gateway.show();
			give_fee_all_gateway.hide();
		}

	} ).change();

	/**
	 * Show/hide based on Per Form Gateway based enable/disable.
	 */
	_gateway_fee_enable.on( 'change', function() {

		// Get the value of checked radio button value of Fee Recovery.
		var global_value = $( 'input[name="_form_give_fee_recovery"]:radio:checked' ).val(),
			checked_fields = $( '.give_fee_gateways_fields input:radio:checked' );

		// Return if Give Fee recovery option is disable.
		if ( global_value !== 'enabled' ) {
			return false;
		}

		checked_fields.each( function( i, e ) {
			var value = jQuery( e ).val(),
				$this = $( this );

			// If value is enable then show fields.
			if ( value === 'enabled' ) {
				$this.closest( '.give_fee_gateway' ).find( '.give_fee_percentage' ).show();
				$this.closest( '.give_fee_gateway' ).find( '.give_fee_base_amount' ).show();
			} else {
				$this.closest( '.give_fee_gateway' ).find( '.give_fee_percentage' ).hide();
				$this.closest( '.give_fee_gateway' ).find( '.give_fee_base_amount' ).hide();
			}
		} );
	} ).change();

	// Get current donation fee amount.
	var give_current_donation_fee_amount = parseFloat( give_fee_unformat_amount( $( '#give-payment-fee-amount' ).val() ) ),
		give_current_donation_total_amount = parseFloat( give_fee_unformat_amount( $( '#give-payment-total' ).val() ) );

	/**
	 * Calculate Fee difference amount and update on Donation details screen.
	 */
	$body.on( 'focusout', '#give-payment-fee-amount', function() {

		// Get fee amount value.
		var give_payment_total_wrap = $( '#give-payment-total' ),
			give_donation_fee_amount = parseFloat( give_fee_unformat_amount( $( this ).val() ) ),
			give_donation_payment_total = parseFloat( give_fee_unformat_amount( give_payment_total_wrap.val() ) ),
			give_fee_difference_amount = 0,
			dp = give_vars.currency_decimals,
			give_currency_symbol = give_vars.currency_sign;

		if ( 1 >= parseInt( dp ) ) {
			dp = 2;
		}

		give_vars.currency_sign = '';

		if ( give_current_donation_fee_amount !== give_donation_fee_amount ) {

			if ( give_donation_fee_amount > give_current_donation_fee_amount ) {

				// Calculate Fee difference.
				give_fee_difference_amount = give_donation_fee_amount - give_current_donation_fee_amount;

				// Add fee amount in payment total.
				give_donation_payment_total = give_current_donation_total_amount + give_fee_difference_amount;

			} else if ( give_donation_fee_amount < give_current_donation_fee_amount ) {

				// Calculate Fee difference.
				give_fee_difference_amount = give_current_donation_fee_amount - give_donation_fee_amount;

				// Subtract fee amount in payment total.
				give_donation_payment_total = give_current_donation_total_amount - give_fee_difference_amount;
			}

			give_payment_total_wrap.val( give_fee_format_amount( give_donation_payment_total.toFixed( dp ) ) );
		} else {

			give_payment_total_wrap.val( give_fee_format_amount( give_current_donation_total_amount.toFixed( dp ) ) );
		}
		give_vars.currency_sign = give_currency_symbol;
	} );

	var poststuff = $( '#poststuff' ),
		thousand_separator = give_vars.thousands_separator,
		decimal_separator = give_vars.decimal_separator,
		thousand_separator_count = '',
		alphabet_count = '',
		price_string = '',

		// Thousand separation limit in price depends upon decimal separator symbol.
		// If thousand separator is equal to decimal separator then price does not have more then 1 thousand separator otherwise limit is zero.
		thousand_separator_limit = ( decimal_separator === thousand_separator ? 1 : 0 );

	// Check & show message on keyup event.
	poststuff.on( 'keyup', 'input.give-fee-recovery-field', function() {
		var show_tooltip = 'hide';
		// Count thousand separator in price string.
		thousand_separator_count = ( $( this ).val().match( new RegExp( thousand_separator, 'g' ) ) || [] ).length;
		alphabet_count = ( $( this ).val().match( new RegExp( '[a-zA-Z]', 'g' ) ) || [] ).length;

		// Show Tooltip conditionally if thousand separator detected on price string.
		if (
			( - 1 !== $( this ).val().indexOf( thousand_separator ) )
			&& ( thousand_separator_limit < thousand_separator_count )
		) {
			show_tooltip = 'show';
		} else if ( alphabet_count ) {
			// Show tootip if user entered a number with alphabet letter.
			show_tooltip = 'show';
		} else {
			show_tooltip = 'hide';
		}

		$( this ).giveHintCss( show_tooltip, { label: give_vars.price_format_guide.trim() } );

		// Reset thousand separator count.
		thousand_separator_count = alphabet_count = '';
	} );

	// Format price sting of input field on focusout.
	poststuff.on( 'focusout', 'input.give-fee-recovery-field', function() {

		var dp = give_vars.currency_decimals;

		if ( 1 >= parseInt( dp ) ) {
			dp = 2;
		}

		if ( "" === $( this ).val() ) {
			price_string = give_unformat_currency( $( this ).attr( 'placeholder' ), dp );
		} else {
			price_string = give_unformat_currency( $( this ).val(), dp );
		}

		// Replace dot decimal separator with user defined decimal separator.
		price_string = price_string.replace( '.', decimal_separator );

		// Check if current number is negative or not.
		if ( - 1 !== price_string.indexOf( '-' ) ) {
			price_string = price_string.replace( '-', '' );
		}

		$( this ).giveHintCss( 'hide', { label: give_vars.price_format_guide.trim() } );

		// Update format price string in input field.
		$( this ).val( price_string );
	} );

	/**
	 * Show form fee earnings description on tools.
	 */
	$body.on( 'change', '#recount-stats-type', function() {

		var selected_type = $( 'option:selected', $( this ) ).data( 'type' ),
			selected_export_class = $( 'option:selected', $( this ) ).val();

		if ( 'recount-form' === selected_type && 'Give_Tools_Recount_Form_Fee_Earnings' === selected_export_class ) {
			$( '#' + selected_type ).hide();
			$( '#recount-form-fee-earnings' ).show();
		}

	} );

} );
