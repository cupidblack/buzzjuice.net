jQuery(document).ready(function($){

	var ajaxurl = custom_product_tab_url.admin_url;

	var nonce 	= custom_product_tab_url.nonce;

	$(document).on('change','#af_rs_and_or-id',function(){
		and_or_rule_fun();
		
	})
	
	function and_or_rule_fun(){
		
		const and_or_rule = $('#af_rs_and_or-id').val();
			
		if('and' == and_or_rule){
			$('.pur_sub_prod').hide();
			$('.af_rc_membership').hide();

			$('.enable_specific_product').each(function () {
				enable_specific_product($(this));
			});

		} else {
			$('.pur_sub_prod').show();
			$('.af_rc_membership').show();

			$('.enable_specific_product').each(function () {
				enable_specific_product($(this));
			});
		}
	}

	setTimeout(() => {
		and_or_rule_fun();
		multiple_roles();
	}, 500);

	// $(document).on('click','input[name=multiple_roles]', function(){

	// 	multiple_roles();

	// 	var selected_val  = $("input[name='multiple_roles']:checked").val();

	// 	var specific_memberships = $('.specific_memberships').val();

	// 	jQuery.ajax({

	// 		url: ajaxurl,

	// 		type: 'POST',

	// 		data: {

	// 			action        			: 'af_urs_memberships_optn',

	// 			nonce 		  			: nonce,

	// 			selected_val 			: selected_val,

	// 			specific_memberships 	: JSON.stringify(specific_memberships),
	// 		},

	// 		success: function(data){

	// 			$('.specific_memberships').html( data );
				
	// 			$('.specific_memberships').select2();
	// 		}
	// 	});
	// });



	new_cbox();

	$('.enable_specific_product').each(function () {

		enable_specific_product($(this));
	});


	$(document).on('change','input[name=multiple_roles]', multiple_roles );

	$(document).on('click','.new_cbox', new_cbox );

	$(document).on('click', '.select_taxonomyies', select_cat );

	$(document).on('change','.af_subscription_status', af_subscription_status );
	
	$(document).on('change','.af_membership_status', af_membership_statuses );

	$(document).on('click', '.enable_specific_product', function () {
		
    	enable_specific_product($(this)); 
	});

	function enable_specific_product(current_input) {

		let select_radio_btn = current_input.val();
		
			if ('purchase_product' == select_radio_btn ) {			
				
				if(current_input.is(':checked')){
					
					$('#enable_specific_product_id').slideDown();

				} else {
				
					$('#enable_specific_product_id').slideUp();
				}

				// new_cbox();
			}
				
			if ('number_products' == select_radio_btn ) {
			
				if(current_input.is(':checked')){
					$('#af_arc_no_of_products_id').slideDown('fast');

				} else {
					$('#af_arc_no_of_products_id').slideUp('fast');
				}
			}

			if ('price_range' == select_radio_btn ) {

				if(current_input.is(':checked')){
					$('#af_arc_price_range_id').slideDown('fast');

				} else {
					$('#af_arc_price_range_id').slideUp('fast');
				}
				
			} 
			if ('total_spend' == select_radio_btn ) {

				if(current_input.is(':checked')){
					$('#af_arc_total_spend_id').slideDown('fast');

				} else {
					$('#af_arc_total_spend_id').slideUp('fast');
				}

			} 
				
			if ('product_cat_tag' == select_radio_btn ) {

				if(current_input.is(':checked')){
					$('#af_arc_select_taxonomy_id').slideDown('fast');
					select_cat();

				} else {
					$('#af_arc_select_taxonomy_id').slideUp('fast');
				}

			}
			if ('email_domain_v' == select_radio_btn ) {

				if(current_input.is(':checked')){
					$('#af_rs_email_domain').slideDown('fast');
					select_cat();

				} else {
					$('#af_rs_email_domain').slideUp('fast');
				}

			}

			if ('sub_prod' == select_radio_btn ) {
				// let multiple_roles  = $('input[name=multiple_roles]:checked').val();
				if(current_input.is(':checked') && 'single_u' !== multiple_roles){
					$('#sub_specific_products-id').slideDown('fast');
					af_subscription_status();


				} else {
					$('#sub_specific_products-id').slideUp('fast');
				}

			}
				
			if ('memberships' == select_radio_btn ) {
				// let multiple_roles  = $('input[name=multiple_roles]:checked').val();
				
				if(current_input.is(':checked') && 'single_u' !== multiple_roles){
					$('#af_arc_specific_membership-id').slideDown('fast');
					af_membership_statuses();

				} else {
					
					$('#af_arc_specific_membership-id').slideUp('fast');
				}

			}
			
	}

	function new_cbox(){
		
		let new_cbox  = $('.new_cbox:checked').val();

		if ( 'quantity' == new_cbox || 'products' == new_cbox) {
		
			$('.af_arc_product_counter').fadeIn('fast');
		
		}else{
		
			$('.af_arc_product_counter').fadeOut('fast');
		}
	}

	function af_subscription_status(){

		let af_subscription_status = $('.af_subscription_status[value="days"]').is(':checked');

		if (af_subscription_status) {

			$('.af_arc_sub_no_of_days').fadeIn('fast');

			af_subscription_status = false;

		}else{
			
			$('.af_arc_sub_no_of_days').hide();
		}
	}

	function af_membership_statuses(){

		let af_membership_status = $('.af_membership_status[value="days"]').is(':checked');		

		if (af_membership_status) {

			$('.af_arc_mem_no_of_days').fadeIn('fast');

			af_membership_status = false;
		}else{

			$('.af_arc_mem_no_of_days').hide();
		}
	}

	function select_cat(){

		var select_cat = $('input[name=select_cat]:checked').val();

		if ( 'select_taxonomy_cat' == select_cat ) {

			$('.af_arc_category').fadeIn('fast');
			
			$('.af_arc_tag').hide();

		}else{

			$('.af_arc_category').hide();
			
			$('.af_arc_tag').fadeIn('fast');
		}
	}

	function multiple_roles(){

		
		let multiple_roles  = $('input[name=multiple_roles]:checked').val();
			
		if ('single_u' == multiple_roles) {

			$('.af_arc_from_this_role,.af_arc_to_this_role').show();
			
			$('.af_arc_current_role,.af_arc_additional_role').hide();
			// $('.af_arc_additional_role').hide();

			$('.pur_sub_prod').hide();
			$('.af_rc_membership').hide();
			

		}else{

			let and_or_rule = $('#af_rs_and_or-id').val();
			if('or' == and_or_rule){
				// $('.pur_sub_prod').show();
				// $('.af_rc_membership').show();
			}

			$('.pur_sub_prod').show();
			$('.af_rc_membership').show();

			$('.af_arc_additional_role, .af_arc_current_role').show();

			$('.af_arc_from_this_role, .af_arc_to_this_role').hide();

		}
	}
});