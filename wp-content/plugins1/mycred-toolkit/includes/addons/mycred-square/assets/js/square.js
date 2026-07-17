(function ( $ ) {
	'use strict';
	const appId = myAjax.application_id;
	const locationId = myAjax.location_id; 
	
	async function initializeCard(payments) {
		const card = await payments.card();
		setTimeout(function(){ 	
			card.attach('#ms-card-container'); 

			const cardButton = document.getElementById(
				'sq-creditcard' 
			);
			 async function handlePaymentMethodSubmission(event, paymentMethod) {
				event.preventDefault();
				var amount = jQuery('#amount').val();
				var amount01 = amount.toString();
				amount = amount.toString();
				var currency = myAjax.currency;
				var points = myAjax.points_amount;
				var final_points = amount;
				amount = (amount01 * points).toString();
				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					//place_order.disabled = true;
					// jQuery('.wpforms-submit').prop('disabled', true);
					const token = await tokenize(paymentMethod);
					let verificationToken = await verifyBuyer(payments, token, 'CHARGE');
					if(token){
						//TODO: Move existing Fetch API call here
						jQuery.noConflict();
						jQuery.ajax({
							type: "post",
							url: myAjax.ajaxurl,
							data: {
								action: "mcs_payment_process",
								// cardData: cardData,
								nonce: token,
								amount: jQuery('#amount').val(),
								buyerVerification_token: verificationToken,
								currency: currency,
								points: final_points,

							},

							success: function (response) {
								if (response.trim() == 'Purchase completed') {
									jQuery("#nonce-form").hide();
									jQuery("#sq-ccbox").append("<center><h2>Thank You payment successfully paid </h2></center>");
									/*          document.getElementById("nonce-form").reset();*/
									if(myAjax.thankyou_page != 0 && myAjax.thankyou_page != null) {
										window.location = myAjax.thankyou_page;

									}
								} else {

								jQuery("#nonce-form").hide();
								jQuery("#sq-ccbox").append("<center><h2> Failed Try Again </h2></center>");

								/*      document.getElementById("nonce-form").reset();*/
								}
							},
							error: function (response) {
								jQuery("#nonce-form").hide();
								jQuery("#sq-ccbox").append("<center><h2> Failed Try Again </h2></center>");

							}
						});
						// $form.submit();
					} else {
						var html = '';

						html += '<ul class="_error error">';

						// handle errors
						jQuery(errors).each(function (index, error) {
							html += '<li>' + error.message + '</li>';
						});

						html += '</ul>';

						// append it to DOM
						jQuery('#sq-ccbox #error').eq(0).html(html);
						jQuery("#sq-creditcard").text('Payment');
						var errorDiv = document.getElementById('errors');
					}
					
				} catch (e) {
					// jQuery('.wpforms-submit').prop('disabled', false);
					console.error(e.message);

				}
			}
			cardButton.addEventListener('click', async function (event) {
				jQuery("#sq-creditcard").text('Please Wait...');
				await handlePaymentMethodSubmission(event, card);
			})
		}, 2000);
		return card; 
    }
	async function verifyBuyer(payments, sourceId, intten = null) {
		var amount = jQuery('#amount').val();
		var amount01 = amount.toString();
		amount = amount.toString();
		var currency = myAjax.currency;
		var points = myAjax.points_amount;
		var final_points = amount;
		amount = (amount01 * points).toString();
		// if(jQuery( '#sq-card-saved' ).is(":checked")){
			// var intten = 'STORE';
		// } else if(square_params.subscription) {
			// var intten = 'STORE';
		// } else if(
		// jQuery( '._wcf_flow_id' ).val() != null ||  
		// jQuery( '._wcf_flow_id' ).val() != undefined || 
		
		// jQuery( '._wcf_checkout_id' ).val() != null ||  
		// jQuery( '._wcf_checkout_id' ).val() != undefined 
		// ) {
			// var intten = 'STORE';
		// } else if(jQuery( '.is_preorder' ).val()) {
			// var intten = 'STORE';
		// } else {
			// var intten = 'CHARGE';
		// }
		const verificationDetails = {
			intent: intten, 
			amount: amount, 
			currencyCode: currency, 
			billingContact: {}
		};
		
		// const verificationResults = payments.verifyBuyer(
			// sourceId,
			// verificationDetails

		// );
		const verificationResults = await payments.verifyBuyer(
			sourceId,
			verificationDetails
		);
		
		return verificationResults.token;
	}

	// This function tokenizes a payment method. 
	// The â€˜errorâ€™ thrown from this async function denotes a failed tokenization,
	// which is due to buyer error (such as an expired card). It is up to the
	// developer to handle the error and provide the buyer the chance to fix
	// their mistakes.
	async function tokenize(paymentMethod) {
		const tokenResult = await paymentMethod.tokenize();
		if (tokenResult.status === 'OK') {
			return tokenResult.token;
		} else {
			let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
			if (tokenResult.errors) {
				errorMessage += ` and errors: ${JSON.stringify(
				 tokenResult.errors
				)}`;
			}
			throw new Error(errorMessage);
		}
	}
	
	// document.addEventListener('DOMContentLoaded', async function () {
	jQuery(document).ready(function(){
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		const payments = window.Square.payments(appId, locationId);

		let card;
		// jQuery( 'form.form.myCRED-buy-form' ).on( 'submit', function( event ) {
			// if (jQuery("#buycred-checkout-form")[0] == null){
				// try {
					// card = initializeCard(payments);
				// } catch (e) {
					// console.error('Initializing Card failed', e);
					// return;
				// }
			// }
		// });
		
		jQuery( 'form.form.myCRED-buy-form' ).on( 'submit', function( event ) {
			// console.log(jQuery( "select[name*='mycred_buy']" ).val());
			if (jQuery( "input[name*='mycred_buy']" ).val() === 'mycred_square'  || jQuery( "select[name*='mycred_buy']" ).val() === 'mycred_square'  ) {
				event.preventDefault();
				jQuery.noConflict();
				if(jQuery( "input[name*='amount']" ).val() != null) {
					if (myAjax.checkoutoption == 'popup') {
						// buildsquarefields(myAjax);
						setTimeout(function () {
							if (myAjax.application_id) {
								try {
									card = initializeCard(payments);
								} catch (e) {
									console.error('Initializing Card failed', e);
									return;
								}
							} else {
								alert("Connect your Square account or submit sandbox credentials before using myCred Square.");
							}
						}, 1000); 
					} else {
					   alert("buyCRED square work on popup check your buyCRED setting.");

					}
				}

			}

		});
		 if (jQuery("#buycred-checkout-form")[0] == null){
		   if( jQuery('#ms-card-container') ){
			   if(myAjax.application_id ) {
				 //  if (myAjax.checkoutoption == 'popup') {
						try {
							card = initializeCard(payments);
						} catch (e) {
							console.error('Initializing Card failed', e);
							return;
						}
				  // } else {
					   //alert("buyCRED square work on popup check your buyCRED setting.");
				  // }
			   }
		   }
        }
	});
	
	jQuery(document).on('click','.mycred_square_close_btn',function(e){
        e.preventDefault();
        if(myAjax.application_id) {
            
		// if(jQuery('#ms-card-container').html().length > 1){
			// card.destroy();
		// }
            if(myAjax.cancel_page != 0 && myAjax.cancel_page != null  ) {
                window.location = myAjax.cancel_page;
            }
            }
    });
	if (jQuery("#buycred-checkout-form")[0] == null){
       // jQuery('.mycred_square_close_btn').hide(); 
    } else{
        jQuery('.mycred_square_close_btn').show();
    }
}( jQuery ) );
	
