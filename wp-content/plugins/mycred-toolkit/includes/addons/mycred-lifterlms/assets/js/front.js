
jQuery(document).ready(function() {

      jQuery('.llms-checkout-confirm > strong').hide();
      jQuery('.current-balance-lifterlms').hide();

jQuery('input[type="radio"]').change(function() {
                if (jQuery(this).val() === 'mycred') {
                    jQuery('.llms-checkout-confirm > strong').show();
                    jQuery('.current-balance-lifterlms').show();
                    
                } else {
                    jQuery('.llms-checkout-confirm > strong').hide();
                    jQuery('.current-balance-lifterlms').hide();
                }
            });
});