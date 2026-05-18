const { __, _x, _n, _nx } = wp.i18n;
jQuery( function($){
	$('.mwp-hook-reward-on-each').change(function(e){
		var selected = $(this).val();
		var label    = $(this).closest('.row').find('.mwp-hook-each-order-amt-label');
		switch( selected ) {
		  case 'fixed_rate':
		    label.html( label.data('type') );
		    break;
		  case 'percentage':
		    label.html( __( 'Percentage (%)', 'mycred-woocommerce-plus' ) );
		    break;
		  case 'exchange_rate':
		    label.html( __( 'Rate', 'mycred-woocommerce-plus' ) + ` (${label.data('type')})` );
		    break;
		  default:
		    break;
		}
	});
});
jQuery(document).ready(function () {
    jQuery(document).on( 'click', '.mycred-woo-add-specific-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input.order-range-log').val('%plural% for order range');
        hook.find('input.order-range-min').val('1');
        hook.find('input.order-range-max').val('10');
        hook.find('input.order-range-reward').val('10');
        jQuery(this).closest('.widget-content').append( hook );
    }); 
    jQuery(document).on( 'click', '.mycred-woo-remove-specific-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();
            } 
        }
    }); 
});
