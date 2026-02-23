
jQuery(document).ready(function($){
	
	var admin_url = custom_product_tab_url.admin_url;
	
	var nonce     = custom_product_tab_url.nonce;

	// jQuery('.sub_specific_products').select2({

	// 	multiple: true,
		
	// 	placeholder: 'Choose Subscriptions',
	// });
	
	// jQuery('.specific_memberships').select2();
	

	
	jQuery('.select_two_class').each(function () {

		var $select = jQuery(this);
		
		$select.select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				type: 'POST',
				delay: 250,

				data: function (params) {
					return {
						q: params.term,
						action: 'af_urs_live_search',
						nonce: nonce,
						search_type: $select.data('type')
					};
				},

				processResults: function (data) {
					var options = [];

					if (data) {
						jQuery.each(data, function (index, text) {
							options.push({ id: text[0], text: text[1] });
						});
					}

					return { results: options };
				},

				cache: true
			},

			multiple: true,
			placeholder: $select.data('placeholder') || 'Choose Products'
		});

	});

});