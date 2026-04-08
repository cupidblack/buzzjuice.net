jQuery(document).ready(function () {
    
	
	jQuery('.widget-liquid-right select.mycred-za-meeting-id').each(function(){
		if(jQuery(this).val() ==999999) 
		jQuery('.mycred-add-za-specific-hook').attr('disabled', 'disabled');
	}) 
	
	
	jQuery(document).on( 'click', '.mycred-add-za-specific-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input.mycred-za-creds').val('10');
        hook.find('input.mycred-za-log').val('%plural% for joining zoom meeting');
		hook.find('select.mycred-za-meeting-id').find('option[value="999999"]').attr('disabled', 'disabled');
       jQuery(this).closest('.widget-content').append( hook );
	   
	   jQuery('.widget-liquid-right select.mycred-za-meeting-id').each(function(){
			//console.log('working');
			if(jQuery(this).val() ==999999) 
			jQuery('.mycred-add-za-specific-hook').attr('disabled', 'disabled');
		}) 
	   
	}); 
    jQuery(document).on( 'click', '.mycred-za-remove-specific-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();
            } 
        }
    }); 

    jQuery(document).on('change', 'select.mycred-za-meeting-id', function(){
        console.log( jQuery(this).val() );
        jQuery('select.mycred-za-meeting-id').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
		if(jQuery(this).val() == 999999){
			jQuery('.mycred-add-za-specific-hook').attr('disabled', 'disabled');
		}else{
			jQuery('.mycred-add-za-specific-hook').removeAttr('disabled');
		}
	});
	
	
	
	
});
