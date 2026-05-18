jQuery(function(jQuery) {
    function updateMycredFields() {
        var $mycredFieldsWrapper = jQuery('div.mycred-woo-fields-wrapper.mycred-wooplus');
        var $selectedGateway = jQuery('input[name="radio-control-wc-payment-method-options"]:checked');

        if ($mycredFieldsWrapper.length) {
            $mycredFieldsWrapper.hide();

            if ($selectedGateway.length) {
                var selectedGatewayValue = $selectedGateway.val();
                var $selectedWrapper = $mycredFieldsWrapper.filter('[data-type="' + selectedGatewayValue + '"]');
                $selectedWrapper.show();
            }
        }
    }

    jQuery(window).on('load', function() {
        updateMycredFields();
    });

    jQuery(document.body).on('change', 'input[name="radio-control-wc-payment-method-options"]', function() {
        updateMycredFields();
    });

    jQuery(document.body).on('updated_checkout', function() {
        updateMycredFields();
    });
});

jQuery(document).ready(function () {
    jQuery('.delete_coupon_button').on('click', function () {
        var form = jQuery(this).closest('.delete_coupon_form');
        var confirmDelete = confirm('Are you sure you want to delete this coupon?');

        if (confirmDelete) {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'delete_coupon',
                value: '1'
            }).appendTo(form);

            form.submit();

            setTimeout(function () {
                window.location.reload();
            }, 1000);
        }
    });
});

jQuery(document).ready(function() {
    const $emailInput = jQuery('#email-input');
    const $emailError = jQuery('#email-error');

    $emailInput.on('input', function() {
        if (this.checkValidity()) {
            $emailError.hide();
        } else {
            $emailError.show();
        }
    });

    const $form = $emailInput.closest('form');
    if ($form.length) {
        $form.on('submit', function(event) {
            if (!$emailInput[0].checkValidity()) {
                $emailError.show();
                event.preventDefault();
            }
        });
    }
});
