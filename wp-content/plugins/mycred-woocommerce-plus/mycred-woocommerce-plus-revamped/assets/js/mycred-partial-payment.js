(function(jQuery) {

	var Total      = parseInt( myCREDPartial.total );

	let exchangeRate = 1;

	jQuery(document).on('change', '#point-type-select', function() {
		var selectedOption = jQuery(this).find(':selected');
		// if ( 0 == selectedOption.val() ) {
		// 	jQuery('#mycred-range-action input').attr('disabled', 'disabled');
		// 	return;
		// }

		var selectedData = {
			action: 'mycred_get_partial_data',
			selected_pointtype: selectedOption.val()
		};

		jQuery.ajax({
			url: myCREDPartial.ajaxurl,
			type: 'POST',
			data: selectedData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {

					var data = response.data;
					var min = parseInt(data.min) || 0;

					var maxPoints = parseFloat(data.pointCost.replace(/[^0-9.]/g, '')) || 0;
					var userBalance = parseFloat(data.userBalance.replace(/[^0-9.]/g, '')) || 0;
					exchangeRate = parseFloat(data.exchangeRate) || 1;


					// Ensure maxPoints doesn't exceed userBalance
					if (userBalance < maxPoints) {
						maxPoints = userBalance;
						jQuery('.mycred-woo-order-total-value').addClass('low-balance');
					} else {
						jQuery('.mycred-woo-order-total-value').removeClass('low-balance');
					}

					// for legacy cart & checkout
					jQuery('.point-cost').find('th strong').text(data.pointCosttext);
					jQuery('.user-balance').find('th').text( data.userBalancetext );
					jQuery('.point-cost').find('td .current-balance').text(data.pointCost);
					jQuery('.user-balance').find('td .current-balance').text( data.userBalance );


					jQuery('.mycred-woo-fields-wrapper.wrapper-disabled').css('display', 'none');

					// for block cart & checkout
					jQuery('.mycred-woo-order-total-label-plus').text(data.pointCosttext);
					jQuery('.mycred-woo-total-credit-label-plus').text( data.userBalancetext );
					jQuery('.mycred-woo-order-total-value-plus').text(data.pointCost);
					jQuery('.mycred-woo-total-credit-value-plus').text(data.userBalance);

					jQuery('#mycred-range-selector input').attr('min', min).attr('max', maxPoints).val(min);

					jQuery('#mycred-partial-payment-total .point-cost-desc').text(`Minimum ${data.pointCosttext} is ${data.formatted_min} and Maximum ${data.pointCosttext} is ${data.pointCost}`);

					var discount = (min * data.exchangeRate);
					function formatNumber(num) {
						return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
					}

					var newdiscount = formatNumber(discount);
					var DiscountTemplate = myCREDPartial.format.replace(/COST/i, newdiscount);
					jQuery('#mycred-partial-payment-total p span.amount').text(DiscountTemplate);
				} else {
					console.error('Error:', response.data.message || 'Failed to fetch data');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
			}
		});
	});
	
	jQuery(window).on('load', function() {
		jQuery(document.body).trigger("wc_fragment_refresh");

		jQuery(document).ajaxComplete(function(event, xhr, settings) {
			if ( settings.url.indexOf('?wc-ajax=get_refreshed_fragments') !== -1) {
				var $pointSelect = jQuery('#point-type-select');

				if ($pointSelect.length > 0 && $pointSelect.is(':hidden') && $pointSelect.val() === "0") {
					var firstValidOption = $pointSelect.find('option').not('[value="0"]').first().val();

					if (firstValidOption) {
						$pointSelect.val(firstValidOption).trigger('change');
					}
				}
			}
		});
	});

	jQuery(document).on('click', '.woocommerce-remove-coupon', function(e) {
		jQuery(document.body).trigger('wc_fragment_refresh');
	});

	jQuery(document).on('click', '.wc-block-components-chip__remove', function(e) {
		jQuery(document.body).trigger('wc_fragment_refresh');
	});

	jQuery(document).ready(function(){

		jQuery(document.body).on('updated_shipping_method', function() {
			jQuery(document.body).trigger('wc_fragment_refresh');
		});

		jQuery(document.body).on('update_checkout', function () {
			jQuery(document.body).trigger('wc_fragment_refresh');
		});

		jQuery(document).on('change', '.wc-block-components-radio-control__input', function () {
			jQuery(document.body).trigger('wc_fragment_refresh');
		});
	});

	jQuery( document ).on(
		'click',
		'#mycred-range-action input',
		function(e){

			console.log( 'Attempting new partial payment' );

			e.preventDefault();

			var selectedOption = jQuery('#point-type-select').find(':selected');

			jQuery( '.woocommerce-error, .woocommerce-message' ).remove();

			jQuery.ajax(
			{
				type       : "POST",
				data       : {
					action : 'mycred_new_partial_payment',
					token  : myCREDPartial.token,
					amount : AmountToPay,
					type   : selectedOption.val()

				},
				dataType   : "JSON",
				url        : myCREDPartial.ajaxurl,
				beforeSend : function() {

					// jQuery( '#mycred-range-action input' ).attr( 'disabled', 'disabled' );

				},
				success    : function( response ) {
					if ( response.success === true ) {

						jQuery(document.body).trigger("wc_fragment_refresh");
						if ( response.data === true  ) {
							jQuery('#mycred-partial-payment-woo').html('');
						}
					}

				},
				complete : function() {
					OurUpdate = true;
					jQuery( "[name='update_cart']" ).prop( 'disabled', false ).trigger( "click" );
					jQuery( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
					wp.data.dispatch('wc/store/cart').invalidateResolutionForStore();

				}
			}
			);

		}
		);

	jQuery( document ).on(
		'change input',
		'#mycred-range-selector input',
		function(){

			var selectedamount = jQuery( this ).val();
			AmountToPay = selectedamount;
			var selectedOption = jQuery('#point-type-select').find(':selected');
			
			if ( selectedamount == 0 && selectedOption.val() == 0 || selectedOption.length == 0 ) {

				// jQuery( '#mycred-range-action input' ).attr( 'disabled', 'disabled' );

			} else if ( selectedOption.val() == 0 || selectedOption.length == 0 ) {

				// jQuery( '#mycred-range-action input' ).attr( 'disabled', 'disabled' );

			} else {

				jQuery( '#mycred-range-action input' ).removeAttr( 'disabled' );

			}

			jQuery( '#mycred-partial-payment-total h2 span' ).text( selectedamount );

			function formatNumber (num) {
				return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, "," );
			}

			var newdiscount      = selectedamount * exchangeRate;

			newdiscount 	 = newdiscount.toFixed( 2 );
			newdiscount 	 = formatNumber( newdiscount );
			var DiscountFormat   = myCREDPartial.format;
			var DiscountTemplate = DiscountFormat.replace( /COST/i, newdiscount );

			jQuery( '#mycred-partial-payment-total p span.amount' ).text( DiscountTemplate );
		}
		);


})( jQuery );
