jQuery(document).ready(() => {
	jQuery(document).on( 'click', '.mycred-add-specific-form-submit-hook', function() {
        var hook = jQuery(this).closest('.form_submit_custom_hook_class').clone();
        hook.find('input.mycred-form-submit-creds').val();
        hook.find('input.mycred-form-submit-log').val();
        jQuery(this).closest('.form_submit_custom_hook_class').after( hook );
       
    }); 
    jQuery(document).on( 'click', '.mycred-remove-form-submit-hook', function() {
        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.form_submit_custom_hook_class').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.form_submit_custom_hook_class').remove();
                jQuery('select.user_select_post').trigger('change');
            } 
        }
    }); 
    jQuery(document).on( 'click', '.mycred-add-specific-form-specific-field-value-hook', function() {
        var hook = jQuery(this).closest('.form_specific_field_value_custom_hook_class').clone();
        hook.find('input.mycred-form-specific-field-value-creds').val();
        hook.find('input.mycred-form-specific-field-value-log').val();
        jQuery(this).closest('.form_specific_field_value_custom_hook_class').after( hook );
       
    }); 
    jQuery(document).on( 'click', '.mycred-remove-form-specific-field-value-hook', function() {
        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.form_specific_field_value_custom_hook_class').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.form_specific_field_value_custom_hook_class').remove();
            } 
        }
    }); 
    jQuery(document).on( 'click', '.mycred-add-specific-form-specific-field-value-specific-form-hook', function() {
        var hook = jQuery(this).closest('.form_specific_field_value_specific_form_custom_hook_class').clone();
        hook.find('input.mycred-form-specific-field-value-specific-form-creds').val();
        hook.find('input.mycred-form-specific-field-value-specific-value-log').val();
        jQuery(this).closest('.form_specific_field_value_specific_form_custom_hook_class').after( hook );
       
    }); 
    jQuery(document).on( 'click', '.mycred-remove-form-specific-field-value-specific-form-hook', function() {
        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.form_specific_field_value_specific_form_custom_hook_class').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.form_specific_field_value_specific_form_custom_hook_class').remove();
                jQuery('select.user_select_post_specific_form').trigger('change');
            } 
        }
    }); 
     jQuery(document).on('change', 'select.user_select_post', function(){   
        ml_user_form_submit_enable_disable_options( jQuery(this) );
    });

     jQuery(document).on('change', 'select.user_select_post_specific_form', function(){   
        ml_user_form_submit_enable_disable_specific_form_options( jQuery(this) );
    });
});


function ml_user_form_submit_enable_disable_options( ele ) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    container.find('select.user_select_post').each(function () {
        container.find('select.user_select_post').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        selected.push( jQuery(this).val() );
    });
    container.find('option').each(function () {     
        if( ! selected.includes( jQuery(this).attr('value')) ) {
            container.find('select.user_select_post').find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
        }
    });
}

 function ml_user_form_submit_enable_disable_specific_form_options( ele ) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    container.find('select.user_select_post_specific_form').each(function () {
        container.find('select.user_select_post_specific_form').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        selected.push( jQuery(this).val() );
    });
    container.find('option').each(function () {     
        if( ! selected.includes( jQuery(this).attr('value')) ) {
            container.find('select.user_select_post_specific_form').find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
        }
    });
}