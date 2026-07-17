jQuery( document ).ready( function() {

      jQuery(document).on('click', '#widget-mycred-hook_totalsurvey', function(e) {
        jQuery('select.mycred_total_survey_select').trigger('change');

    });

    jQuery(document).on('click', '.mycred-add-specific-survey-hook', function() {

        var hook = jQuery(this).closest('.survey_custom_hook_class').clone();
        var log = jQuery('#mycred_totalsurvey_default_log').html();
        hook.find('input.mycred-totalsurvey-specific-creds').val('0');
   
        var limit_select = hook.find('.mycred_totalsurvey_limit_select select');
        var limit_input = hook.find('.mycred_totalsurvey_limit_select input').val( '0' );
        jQuery( limit_input ).hide();
        jQuery( limit_select ).on( 'change', function(){
            jQuery( limit_input ).show();
        } );
        limit_select.find('option:first').prop('selected', true); // Select the first option manually
        hook.find('input.mycred-totalsurvey-specific-logs').val(log);
        hook.find('select.mycred_total_survey_select').val('0');
        jQuery(this).closest('.survey_custom_hook_class').after(hook);
        jQuery('select.mycred_total_survey_select').trigger('change');
    });


    jQuery(document).on( 'click', '.mycred-remove-survey-specific-hook', function() {

        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.survey_custom_hook_class').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.survey_custom_hook_class').remove();
                jQuery('select.mycred_total_survey_select').trigger('change');
            } 
        }
    }); 

    jQuery( document ).on( 'change', '.mycred_total_survey_select', function() {
       
        mts_enable_disable_options( jQuery( this ),'.mycred_total_survey_select' );
    } );

    function mts_enable_disable_options( ele ) {
        
        var selected = [];
        var container = ele.closest('.hook-instance');
        container.find('select.mycred_total_survey_select').each(function () {
            if(jQuery(this).val() == 0) {
                return;
            }
            container.find('select.mycred_total_survey_select').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
            selected.push( jQuery(this).val() );
        });

        container.find('option').each(function () {     
            if( ! selected.includes( jQuery(this).attr('value')) ) {
                container.find('select.mycred_total_survey_select').find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
            }
        });
    }
} );