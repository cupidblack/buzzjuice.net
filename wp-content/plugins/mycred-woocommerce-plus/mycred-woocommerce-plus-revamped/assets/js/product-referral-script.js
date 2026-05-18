jQuery( function($){

	$('.mwp-prod-ref-generator-btn').click(function(e){

		var product_id = $('.mwp-prod-ref-generator-products').val();
		var uid 	   = $('.mwp-prod-ref-generator-uid').val();

		if ( product_id == 0 ) {
			alert( 'Select Product to generate referral link' );
			return;
		}

		var __this = $(this);

		mwp_prod_ref_spinner( __this );

		$.post( 
			mwp_product_ref_data.ajaxurl, 
			{
				'action'  : 'mwp_generate_product_referral',
				'nonce'   : $('#mwp-prod-referral-nonce').val(),
				'prod_id' : product_id,
				'uid'     : uid
			}, 
			function( data ) {

				if ( data.status == 'fail' ) {
					$('.mwp-prod-ref-generator-links').removeClass( 'show' );
					alert( data.msg );
				}
				else {
					$('.mwp-prod-ref-generator-links-url').html( data.data.product_url );
					$('.mwp-prod-ref-generator-ref-url').val( data.data.referral_url );
					$('.mwp-prod-ref-generator-links').addClass( 'show' );
				}

				mwp_prod_ref_spinner( __this, false );

			}
		);

	});

	$('.mwp-prod-ref-clipboard').click(function(e){

		$(".mwp-prod-ref-generator-ref-url").select();

		document.execCommand('copy');

	});
	
});


function mwp_prod_ref_spinner( ele, status = true ) {
	
	if ( status ) {

		ele.addClass( 'mwp-button-spinner' );
		ele.attr('disabled', 'disabled');

	}
	else {

		ele.removeClass( 'mwp-button-spinner' );
		ele.removeAttr( 'disabled', 'disabled' );

	}

}