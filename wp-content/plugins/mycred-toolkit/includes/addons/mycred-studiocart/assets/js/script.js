jQuery(document).ready(function ($) {

    var selectors = 'select.mycred-studiocart-options, select.mycred-studiocart-order-options, select.mycred-studiocart-refund-options, select.mycred-studiocart-subscription-canceled-options';

    // Initial check on load
    setTimeout(function () {
        $(selectors).each(function () {
            mycred_studiocart_enable_disable_options($(this));
        });
    }, 500);

    // Add More specific hook fields
    $(document).on('click', '.mycred-add-specific-studiocart-hook, .mycred-add-specific-studiocart-order-hook, .mycred-add-specific-studiocart-refund-hook, .mycred-add-specific-studiocart-subscription-canceled-hook', function () {
        var $wrapper = $(this).closest('.hook-instance');
        var row_class = '';

        if ($(this).hasClass('mycred-add-specific-studiocart-hook')) row_class = '.studiocart_specific_row';
        else if ($(this).hasClass('mycred-add-specific-studiocart-order-hook')) row_class = '.studiocart_order_specific_row';
        else if ($(this).hasClass('mycred-add-specific-studiocart-refund-hook')) row_class = '.studiocart_refund_specific_row';
        else if ($(this).hasClass('mycred-add-specific-studiocart-subscription-canceled-hook')) row_class = '.studiocart_subscription_canceled_specific_row';

        var $row = $(this).closest(row_class);
        var $newRow = $row.clone();

        $newRow.find('input').val('');
        $newRow.find('select').val('0');

        // Re-enable all options in the new row's dropdown before appending
        $newRow.find('option').removeAttr('disabled');

        $row.after($newRow);

        // Trigger change to update disabled states across all selects in this instance
        $newRow.find('select').trigger('change');
    });

    // Remove specific hook fields
    $(document).on('click', '.mycred-remove-specific-studiocart-hook, .mycred-remove-specific-studiocart-order-hook, .mycred-remove-specific-studiocart-refund-hook, .mycred-remove-specific-studiocart-subscription-canceled-hook', function () {
        var $wrapper = $(this).closest('.hook-instance');
        var row_class = '';

        if ($(this).hasClass('mycred-remove-specific-studiocart-hook')) row_class = '.studiocart_specific_row';
        else if ($(this).hasClass('mycred-remove-specific-studiocart-order-hook')) row_class = '.studiocart_order_specific_row';
        else if ($(this).hasClass('mycred-remove-specific-studiocart-refund-hook')) row_class = '.studiocart_refund_specific_row';
        else if ($(this).hasClass('mycred-remove-specific-studiocart-subscription-canceled-hook')) row_class = '.studiocart_subscription_canceled_specific_row';

        var rowCount = $wrapper.find(row_class).length;

        if (rowCount > 1) {
            if (confirm('Are you sure you want to remove this row?')) {
                var $select = $(this).closest(row_class).find('select');
                $(this).closest(row_class).remove();

                // Trigger update on remaining selects
                $wrapper.find(selectors).first().trigger('change');
            }
        } else {
            alert('At least one row is required.');
        }
    });

    // Dropdown change handler
    $(document).on('change', selectors, function () {
        mycred_studiocart_enable_disable_options($(this));
    });

    /**
     * Prevents duplicate product selection by disabling already selected options
     */
    function mycred_studiocart_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Determine which group of selects we are looking at
        var select_selector = '';
        if (ele.hasClass('mycred-studiocart-options')) select_selector = 'select.mycred-studiocart-options';
        else if (ele.hasClass('mycred-studiocart-order-options')) select_selector = 'select.mycred-studiocart-order-options';
        else if (ele.hasClass('mycred-studiocart-refund-options')) select_selector = 'select.mycred-studiocart-refund-options';
        else if (ele.hasClass('mycred-studiocart-subscription-canceled-options')) select_selector = 'select.mycred-studiocart-subscription-canceled-options';

        if (!select_selector) return;

        // Collect all currently selected values in this container
        container.find(select_selector).each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select in the same group and disable options selected elsewhere
        container.find(select_selector).each(function () {
            var $current_select = $(this);
            var current_val = $current_select.val();

            $current_select.find('option').each(function () {
                var option_val = $(this).val();

                if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                    $(this).attr('disabled', 'disabled');
                } else {
                    $(this).removeAttr('disabled');
                }
            });
        });
    }

});
