jQuery(document).ready(function($){

	var ajaxurl = custom_product_tab_url.admin_url;

	var nonce 	= custom_product_tab_url.nonce;

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

	multiple_roles();

	new_cbox();

	enable_specific_product();

	$(document).on('change','input[name=multiple_roles]', multiple_roles );

	$(document).on('click','.new_cbox', new_cbox );

	$(document).on('click', '.select_taxonomyies', select_cat );

	$(document).on('change','.af_subscription_status', af_subscription_status );
	
	$(document).on('change','.af_membership_status', af_membership_statuses );

	$(document).on('click','.enable_specific_product', enable_specific_product );

	function enable_specific_product() {

		let select_radio_btn  = $('.enable_specific_product:checked').val();

		if ('purchase_product' == select_radio_btn ) {	

			$('.af_arc_choose_products, .af_arc_products_matching').fadeIn('fast');

			new_cbox();

			$('.af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_select_taxonomy, .af_arc_no_of_products, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide();

		}else if ('number_products' == select_radio_btn ) {

			$('.af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide();

			$('.af_arc_no_of_products').fadeIn('fast');

		}else if ('price_range' == select_radio_btn || 'total_spend' == select_radio_btn ) {

			$('.af_arc_price_range').fadeIn('fast');

			$('.af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide();

		}else if ('product_cat_tag' == select_radio_btn ) {

			$('.af_arc_select_taxonomy').fadeIn('fast');

			select_cat();

			$( '.af_arc_price_range, .af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_choose_products, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_domain_url, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide();

		}else if ('email_domain_v' == select_radio_btn ) {

			$('.af_arc_domain_url').fadeIn('fast');

			$('.af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide();


		}else if ('sub_prod' == select_radio_btn ) {

			$('.af_arc_sub_prod').fadeIn('fast');

			af_subscription_status();

			$('.af_arc_sub_status, .af_arc_sub_specific_prod').fadeIn('fast');

			$('.af_arc_mem_no_of_days, .af_arc_specific_membership, .af_arc_duration_role, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_mem_no_of_days').hide();

		}else if ('memberships' == select_radio_btn ) {

			$('.af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_prod, .af_arc_mem_no_of_days').fadeIn('fast');

			af_membership_statuses();

			$('.af_arc_sub_prod, .af_arc_sub_no_of_days, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_duration_role, .af_arc_sub_specific_prod, .af_arc_sub_status').hide();

		}else{

			$('.af_arc_mem_status, .af_arc_mem_prod, .af_arc_mem_no_of_days, .af_arc_choose_products, .af_arc_select_taxonomy, .af_arc_products_matching, .af_arc_product_counter, .af_arc_no_of_products, .af_arc_price_range, .af_arc_category, .af_arc_tag, .af_arc_domain_url, .af_arc_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_mem_no_of_days').hide()
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

		console.log(select_cat);

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

			$('.af_arc_from_this_role, .af_arc_to_this_role').fadeIn('fast');
			
			$('.af_arc_additional_role, .af_arc_current_role, .pur_sub_prod, .af_arc_sub_specific_prod, .af_arc_sub_prod, .af_arc_sub_status, .af_arc_sub_no_of_days, .af_arc_specific_membership, .af_arc_mem_status, .af_arc_mem_no_of_days, .af_arc_sub_specific_prod, .af_arc_sub_prod, .af_arc_sub_prod, .af_arc_sub_no_of_days, .af_arc_mem_prod').hide();

		}else{

			$('.af_arc_additional_role, .af_arc_current_role, .pur_sub_prod').fadeIn('fast');

			enable_specific_product();
			
			$('.af_arc_from_this_role, .af_arc_to_this_role').hide();
		}
	}
});