
jQuery(document).ready(function($){
	
	var admin_url = custom_product_tab_url.admin_url;
	
	var nonce     = custom_product_tab_url.nonce;

	jQuery('.sub_specific_products').select2({

		multiple: true,
		
		placeholder: 'Choose Subscriptions',
	});
	
	jQuery('.specific_memberships').select2();
	
	jQuery('.select_two_class').select2({
	
		ajax: {
			
			url: ajaxurl, // AJAX URL is predefined in WordPress admin.
			
			dataType: 'json',
			
			type: 'POST',
			
			delay: 250, // Delay in ms while typing when to perform a AJAX search.
			
			data: function (params) {
			
				return {
			
					q: params.term, // search query
			
					action: 'af_urs_live_search', // AJAX action for admin-ajax.php.
			
					nonce: nonce, // AJAX nonce for admin-ajax.php.

					search_type: $(this).data('type')
			
				};
			
			},
			
			processResults: function ( data ) {
			
				var options = [];
			
				if (data ) {
			
					 // data is the array of arrays, and each of them contains ID and the Label of the option.
					$.each(
			
						data, function ( index, text ) {
			
							// do not forget that "index" is just auto incremented value.
							options.push({ id: text[0], text: text[1]  });
			
						}
			
					);
				}
				
				return {
				
					results: options
				
				};
			
			},
			
			cache: true
		
		},
		
		multiple: true,
		
		placeholder: 'Choose Products',
		
		// minimumInputLength: 3 // the minimum of symbols to input before perform a search.
	
	});

});