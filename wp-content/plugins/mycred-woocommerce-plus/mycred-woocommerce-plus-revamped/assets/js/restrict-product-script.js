jQuery(
	function($){

		$( '#mwp-restrict-product' ).change( function(e){

			if ( $(this).is( ':checked' ) ) {

				$('.mwp-restrict-product-inner').addClass('show');

			}
			else {

				$('.mwp-restrict-product-inner').removeClass('show');

			}

		});

		$( '#mwp-restrict-product-type' ).change( function(e){

			var basedOn = $(this).val();

			if( basedOn == 'badge' ) {

				mwp_restrict_product_unselect( $('.mwp-restrict-product-content.rank') );
				$('.mwp-restrict-product-content.badge').addClass('show');

			}
			else if( basedOn == 'rank') {

				mwp_restrict_product_unselect( $('.mwp-restrict-product-content.badge') );
				$('.mwp-restrict-product-content.rank').addClass('show');

			}
			else {

				mwp_restrict_product_unselect( $('.mwp-restrict-product-content.badge') );
				mwp_restrict_product_unselect( $('.mwp-restrict-product-content.rank') );

			}

		});

		$("#mwp-restrict-product-search").on("keyup change search", function() {
			
			var value = $(this).val().toLowerCase();
			
			$(".mwp-restrict-product-content .mwp-restrict-product-ids label:not(.mycred-toggle)").filter(function() {
				  
				$(this).closest('.form-group').toggle( $(this).text().toLowerCase().indexOf( value ) > -1 );

			});
		
		});

		function mwp_restrict_product_unselect(ele) {
			
			ele.removeClass('show');

			ele.find('.mwp-restrict-product-ids input').each(function(i, e){

				$(this).prop( 'checked', false );

			});

		}
		
	}
);
