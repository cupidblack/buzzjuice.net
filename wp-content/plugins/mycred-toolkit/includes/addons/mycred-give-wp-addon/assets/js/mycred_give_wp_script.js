jQuery(document).ready(function(){
	jQuery("iframe").load(function(){
		var iframe = jQuery(this).contents();
		call_ajax(iframe);
	});
}); 
jQuery(document).ready(function(){
	jQuery("iframe").load(function(){
	var iframe = jQuery(this).contents();
		iframe.find(".give-gateway-option").click(function(){
				setTimeout(function() {
					call_ajax(iframe);
				},3000);
		});
	});
});
// Call Ajax
function call_ajax(iframe) {
	iframe.find("#give-purchase-button").click(function(){ 
		//Get Input Value
		var give_first = iframe.find('#give-first').val();
		var give_email = iframe.find('#give-email').val();
		var give_form_id = iframe.find('input[name=give-form-id]').val();
		var give_form_title = iframe.find('input[name=give-form-title]').val();
		var give_amount = iframe.find('input[name=give-amount]').val();
		//Check Validation
		if(give_first !=""){
			if(give_email !=""){
				var data = {
					'action': 'myCred_gwp_save_entry',
					'form_id': give_form_id,
					'give_form_title': give_form_title,
					'give_amount': give_amount,
				};
				jQuery.post(mycred_give_wp_frontend_scripts_obj.ajax_url, data, function(response) { 
					//console.log(response);
				});
			}
		}
	});
}






