

(function(jQuery) {

	var Decimals      = parseInt( myCREDPartial.decimals );
	var Exchange      = parseFloat( myCREDPartial.rate );
	var MinPayment    = 0;
	var MaxPayment    = 0;
	var CartTotal     = 0;
	var Template      = myCREDPartial.format;
	var OurUpdate     = false;
	var CouponRemoval = 'no';
	var AmountToPay   = 0;

	var parseBaseValues = function() {

		if ( Decimals > 0 ) {
			MinPayment = parseFloat( myCREDPartial.min ).toFixed( 2 );
			MaxPayment = parseFloat( myCREDPartial.max ).toFixed( 2 );
		} else {
			MinPayment = parseInt( myCREDPartial.min );
			MaxPayment = parseInt( myCREDPartial.max );
		}

		CartTotal = parseFloat( myCREDPartial.total ).toFixed( 2 );

		return true;

	};

	jQuery(window).on('load', function() {
		jQuery(document.body).trigger("wc_fragment_refresh");
	});

	jQuery( document ).ready(
		function() {
			parseBaseValues();

			jQuery( document ).on(
				'focusout',
				'#mycred-range-selector input',
				function(){

					var selectedAmount = parseFloat( jQuery( this ).val() );
					var maxVal         = parseFloat( jQuery( this ).attr( "max" ) );
					if ( selectedAmount > maxVal ) {
						jQuery( this ).val( maxVal );
						jQuery( this ).change();
					}

				}
				);

			jQuery( document ).on(
				'change',
				'#mycred-range-selector input',
				function(){

					var selectedamount = jQuery( this ).val();

					if ( Decimals > 0 ) {
						selectedamount = parseFloat( selectedamount ).toFixed( 2 );
					} else {
						selectedamount = parseInt( selectedamount );
					}

					AmountToPay = selectedamount;

					if ( selectedamount == 0 ) {
						jQuery( '#mycred-range-action input' ).attr( 'disabled', 'disabled' );
					} else {
						jQuery( '#mycred-range-action input' ).removeAttr( 'disabled' );
					}

					jQuery( '#mycred-partial-payment-total h2 span' ).text( selectedamount );

					function formatNumber (num) {
						return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, "," );
					}
					var newdiscount      = selectedamount * Exchange;
					newdiscount 	 = newdiscount.toFixed( 2 );
					newdiscount 	 = formatNumber( newdiscount );
					var DiscountFormat   = myCREDPartial.format;
					var DiscountTemplate = DiscountFormat.replace( /COST/i, newdiscount );

					jQuery( '#mycred-partial-payment-total p span.amount' ).text( DiscountTemplate );

				}
				);

			jQuery( document ).on(
				'click',
				'#mycred-range-action input',
				function(e){

					console.log( 'Attempting new partial payment' );

					e.preventDefault();
					
					var pointType = jQuery("input:hidden#mycred-woo-partial-point-type").val();

					jQuery( '.woocommerce-error, .woocommerce-message' ).remove();

					jQuery.ajax(
					{
						type       : "POST",
						data       : {
							action : 'mycred_new_partial_payment',
							token  : myCREDPartial.token,
							amount : AmountToPay,
							type   : pointType

						},
						dataType   : "JSON",
						url        : myCREDPartial.ajaxurl,
						beforeSend : function() {

							jQuery( '#mycred-range-action input' ).attr( 'disabled', 'disabled' );

						},
						success    : function( response ) {
							if ( response.success === true ) {
								jQuery(document.body).trigger("wc_fragment_refresh");
								if ( response.data ) {
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
				'click',
				'#mycred-coupon-action input',
				function(e){
					console.log( 'Attempting new mycred coupon' );
					e.preventDefault();
					jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
					var couponAmount = parseFloat(jQuery('.mycred-coupon-amount').val());
					var couponAmountMin = parseFloat(jQuery('.mycred-coupon-amount').attr('min'));
					if (couponAmount < couponAmountMin) {
						jQuery('.mycred-coupon-amount')[0].reportValidity();
						return;
					}
					jQuery.ajax(
					{
						type       : "POST",
						data       : {
							action : 'mycred_coupon_ajax',
							token  : myCREDPartial.token,
							amount : couponAmount,
						},
						dataType   : "JSON",
						url        : myCREDPartial.ajaxurl,
						success    : function( response ) {
							if ( response.success === true ) {
								jQuery(document.body).trigger("wc_fragment_refresh");
								jQuery('.mycred-coupon-code').html( `Your coupon code is: <strong>${response.data.coupon_code}<strong>` );
							}
						},
						complete : function() {
							jQuery( "[name='update_cart']" ).prop( 'disabled', false ).trigger( "click" );
							jQuery( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
							wp.data.dispatch('wc/store/cart').invalidateResolutionForStore();

						}
					}
					);

				}
				);

		}
		);

})( jQuery );