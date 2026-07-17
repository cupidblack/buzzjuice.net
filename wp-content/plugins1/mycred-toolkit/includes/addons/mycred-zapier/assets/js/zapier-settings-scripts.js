jQuery(document).ready(function () {
	jQuery('.earn_points_checkbox').on('change',show_hook_url_field);
	jQuery('.deduct_points_checkbox').on('change',show_hook_url_field);
	jQuery('.earn_badge_points_checkbox').on('change',show_hook_url_field);
	jQuery('.earn_ranks_points_checkbox').on('change',show_hook_url_field);
	jQuery('.deduct_ranks_points_checkbox').on('change',show_hook_url_field);

	//show_hook_url_field();
	function show_hook_url_field(){
		if(jQuery(this).prop("checked")){
		  jQuery(this).parents('.zapier-row').find('.zapier-hook-url').show(); 
		}else{
			jQuery(this).parents('.zapier-row').find('.zapier-hook-url').hide();
		}
	}
	jQuery('.earn_points_checkbox').trigger('change');
	jQuery('.deduct_points_checkbox').trigger('change');
	jQuery('.earn_badge_points_checkbox').trigger('change');
	jQuery('.earn_ranks_points_checkbox').trigger('change');
	jQuery('.deduct_ranks_points_checkbox').trigger('change');


	jQuery('#generate-zapier-api-key').click(function(){
		var key_in_hex = MYCRED_ZAPIER.user + MYCRED_ZAPIER.pass;
		var access_key = window.btoa(key_in_hex);
		jQuery('#zapierprefsmycredzapierapikey').val(access_key);
    });
});

//jQuery('document').ready(function(){

    

    //jQuery('#generate-zapier-api-key').click(function(){
        
        //var random_key = Math.floor(Math.random() * 90000) + 10000;
        //var key_in_hex = MYCRED_ZAPIER.user + MYCRED_ZAPIER.pass;
        //var access_key = window.btoa(key_in_hex);
        //var zapier_api_key = access_key.replace(/[^0-9]/g, '');
        //zapier_api_key = parseInt(zapier_api_key,10);
        //zapier_api_key = zapier_api_key == '' ? random_key : zapier_api_key;
        //jQuery('#generalmycredzapierapikeyzapierapikey').val(zapier_api_key);

    //});

//});
