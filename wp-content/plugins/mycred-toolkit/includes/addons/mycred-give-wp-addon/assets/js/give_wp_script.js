jQuery(document).ready(function () {
    jQuery('.widget-liquid-right select.mycred-gwp-form-id').each(function(){
		if(jQuery(this).val() ==999999) 
		jQuery('.mycred-add-gwp-specific-hook').attr('disabled', 'disabled');
	}) 
	jQuery(document).on( 'click', '.mycred-add-gwp-specific-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input.mycred-gwp-creds').val('10');
		hook.find('input.mycred-gwp-log').val('%plural% for specific form completing.');
		hook.find('select.mycred-gwp-form-id').val('0');
		hook.find('select.mycred-gwp-form-id').find('option[value="999999"]').remove();
        jQuery(this).closest('.widget-content').append( hook );
	}); 
    jQuery(document).on( 'click', '.mycred-gwp-remove-specific-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();
            } 
        }
    }); 
	jQuery(document).on('change', 'select.mycred-gwp-form-id', function(){
		jQuery('select.mycred-gwp-form-id').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
		if(jQuery(this).val() == 999999){
			jQuery('.mycred-add-gwp-specific-hook').attr('disabled', 'disabled');
		}else{
			jQuery('.mycred-add-gwp-specific-hook').removeAttr('disabled');
		}
	});
	
});
