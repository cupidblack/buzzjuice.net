jQuery(document).ready(function () {
    
    jQuery(document).on( 'click', '.mycred-add-specific-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input.mycred-buddyboss-creds').val('10');
        hook.find('input.mycred-buddyboss-log').val('%plural% for completing a buddyboss content.');
        hook.find('select.mycred-buddyboss-content').val('0');
        hook.find('input.mycred-buddyboss-percentage').val('0');
        jQuery(this).closest('.widget-content').append( hook );
    }); 
    jQuery(document).on( 'click', '.mycred-remove-specific-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();
            } 
        }
    }); 

    jQuery(document).on('change', 'select.mycred-buddyboss-content', function(){
        console.log( jQuery(this).val() );
        jQuery('select.mycred-buddyboss-content').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        jQuery('select.mycred-buddyboss-content').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
    });
});

