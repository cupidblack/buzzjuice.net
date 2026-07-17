jQuery(document).ready(function () {
    jQuery(document).on( 'click', '.mycred-add-spi', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input#mycred-pref-hooks-complete-pay-creds').val('10');
        hook.find('input#mycred-pref-hooks-complete-pay-limit').val('0');
        hook.find('input#mycred-pref-hooks-complete-pay-log').val('%plural% for completing a purchase');
        hook.find('select#mycred-pref-hooks-complete-pay-form').val('');
        jQuery(this).closest('.widget-content').append( hook );
    });
    jQuery(document).on( 'click', '.mycred-remove-spi', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook instance?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();
            }
        }
    });
});