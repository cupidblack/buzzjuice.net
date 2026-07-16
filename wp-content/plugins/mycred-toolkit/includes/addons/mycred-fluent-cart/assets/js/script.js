jQuery(document).ready(function ($) {
    setTimeout(function () {
        $('select.mycred-fluentcart-products-options').trigger('change');
    }, 500);

    $(document).on('change', 'select.mycred-fluentcart-products-options', function () {
        mycred_fluentcart_enable_disable_options($(this));
    });

    $(document).on('click', '.mycred-add-specific-fluentcart-products-hook', function () {
        var parent_row = $(this).closest('.fluentcart_products_specific_row');
        var clone = parent_row.clone();

        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        parent_row.after(clone);
        $('select.mycred-fluentcart-products-options').trigger('change');
    });

    $(document).on('click', '.mycred-remove-specific-fluentcart-products-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluentcart_products_specific_row').length > 1) {
            var dialog = confirm('Are you sure you want to remove this hook?');
            if (dialog == true) {
                $(this).closest('.fluentcart_products_specific_row').remove();
                $('select.mycred-fluentcart-products-options').trigger('change');
            }
        }
    });

    function mycred_fluentcart_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        container.find('select.mycred-fluentcart-products-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        container.find('select.mycred-fluentcart-products-options').each(function () {
            var current_select = $(this);
            var current_val = current_select.val();

            current_select.find('option').each(function () {
                var option_val = $(this).attr('value');

                if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                    $(this).attr('disabled', 'disabled');
                } else {
                    $(this).removeAttr('disabled');
                }
            });
        });
    }
});

