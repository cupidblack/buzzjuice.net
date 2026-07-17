jQuery(document).ready(function() {

    var profitSharing = lifterlms_mycred.profit_sharing;
    var exchangeRate  = lifterlms_mycred.exchange_rate;
    var decimals      = lifterlms_mycred.decimals;

    var exchangeRateInput = jQuery("#llms_gateway_mycred_lifterlms_exchange_rate");
    var profitSharingInput = jQuery("#llms_gateway_mycred_lifterlms_profit_sharing");

    function validateInput(input) {
        var inputValue = input.val().trim(); // Remove leading/trailing spaces

        // Check if inputValue is a valid number and not negative
        if (!/^\d+(\.\d+)?$/.test(inputValue) || parseFloat(inputValue) < 0) {
            input.val(""); // Clear the input value
        }
    }

    exchangeRateInput.blur(function () {
        validateInput(exchangeRateInput);
    });

    profitSharingInput.blur(function () {
        validateInput(profitSharingInput);
    });
   

});
