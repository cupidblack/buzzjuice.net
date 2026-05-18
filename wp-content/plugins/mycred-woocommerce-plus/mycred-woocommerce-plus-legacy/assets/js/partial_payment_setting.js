jQuery(document).ready(function($) {
	
	function checkAndHideNextRow() {
		var selectedValue = $('#mycred_partial_payments_woo\\[change_position\\]').val();
		if (selectedValue === 'cart') {
			$('tr').has('#mycred_partial_payments_woo\\[change_position\\]').next('tr').hide();
		} else {
			$('tr').has('#mycred_partial_payments_woo\\[change_position\\]').next('tr').show();
		}
	}

	$(window).on('load', function() {
		checkAndHideNextRow();
		

	});

	$('#mycred_partial_payments_woo\\[change_position\\]').change(function() {
		checkAndHideNextRow();
	});
	// jQuery('#mycred_partial_payments_woo\\[step\\]').on('input', function() {
	// 	let value = jQuery(this).val();
	// 	value = value.replace(/[^0-9]/g, '');
	// 	jQuery(this).val(value);
	// });
	jQuery('#mycred_partial_payments_account_woo\\[number\\]').on('input', function() {
		let value = jQuery(this).val();
		value = value.replace(/[^0-9]/g, '');
		jQuery(this).val(value);
	});
	
	if ($('input[name="mycred_partial_payment_switch"]:checked').val() === 'disable') {
		$('.form-table:not(:first)').hide(); 
	}
	
	$('input[name="mycred_partial_payment_switch"]').change(function() {
		if ($(this).val() === 'enable_partial_payment') {
			$('p.text-alignment-woocommerce-plus').hide(); 
		} else {
			$('p.text-alignment-woocommerce-plus').show(); 
		}
	});

	$('input[name="mycred_partial_payment_switch"]').change(function() {
		if ($(this).val() === 'enable_coupons' ) {
			
			$('td.formsep hr').eq(0).hide();
		} else {
			$('td.formsep hr').show();
		}
	});

	$('input[name="mycred_partial_payment_switch"]:checked').change();
});
jQuery( document ).ready(
	function() {

		hide_setting();

		jQuery( document ).on(
			'change',
			'input:radio[name=mycred_partial_payment_switch] ',
			function(){

				hide_setting();

			}
			);
	}
	);

function hide_setting(){

	if (jQuery( 'input[name="mycred_partial_payment_switch"]:checked' ).val() == 'disable') {

		jQuery( '.disabled' ).closest( 'tr' ).hide();
		jQuery( '#partial_payment_checkout_option-description' ).hide();
		jQuery( '#mainform' ).children( '#partial_payment_options-description' ).eq(1).hide();
		jQuery( '#mainform' ).children( 'h2' ).eq(1).hide();
		jQuery( '#mainform' ).children( 'h2' ).eq(2).hide();
		jQuery(  '.formsep' ).hide();
		jQuery('.form-table tbody tr:eq(15)').hide();
		jQuery('.form-table tbody tr:eq(18)').hide();
		jQuery('.form-table tbody tr:eq(24)').hide();
		jQuery('.form-table tbody tr:eq(3)').hide();
	} else {

		if (jQuery( 'input[name="mycred_partial_payment_switch"]:checked' ).val() == 'enable_partial_payment') {
			
			jQuery( '.disabled' ).closest( 'tr' ).show();
			jQuery( '.coupon' ).closest( 'tr' ).hide();
			jQuery( '#partial_payment_checkout_option-description' ).show();
			jQuery( '#mainform' ).children( '#partial_payment_options-description' ).eq(1).show();
			jQuery( '#mainform' ).children( 'h2' ).eq(1).show();
			jQuery( '#mainform' ).children( 'h2' ).eq(2).show();
			jQuery( '.formsep' ).show();

		} else {

			jQuery( '.coupon' ).closest( 'tr' ).show();
		}
		if ( jQuery( 'input[name="mycred_partial_payment_switch"]:checked' ).val() == 'enable_coupons' ) {

			jQuery( '.disabled' ).closest( 'tr' ).show();
			jQuery( '.partial' ).closest( 'tr' ).hide();
			jQuery( '#partial_payment_checkout_option-description' ).show();
			jQuery( '#mainform' ).children( '#partial_payment_options-description' ).eq(1).show();
			jQuery( '#mainform' ).children( 'h2' ).eq(1).show();
			jQuery( '#mainform' ).children( 'h2' ).eq(2).show();
			jQuery( '.formsep' ).show();

		} else {

			jQuery( '.partial' ).closest( 'tr' ).show();
		}		
	}
}
