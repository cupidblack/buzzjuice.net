jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        $('select.mycred-geodirectory-review-options').trigger('change');
    }, 500);

    // Initial check for unique options - Review Posted
    $(document).on('change', 'select.mycred-geodirectory-review-options', function () {
        mycred_geodirectory_review_enable_disable_options($(this));
    });

    // Add More specific hook fields - Review Posted
    $(document).on('click', '.mycred-add-specific-geodirectory-review-hook', function () {
        var parent_row = $(this).closest('.geodirectory_review_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-geodirectory-review-options').trigger('change');
    });

    // Remove specific hook fields - Review Posted
    $(document).on('click', '.mycred-remove-specific-geodirectory-review-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.geodirectory_review_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.geodirectory_review_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-geodirectory-review-options').trigger('change');
            }
        }
    });

    // Review Posted - Enable/Disable options
    function mycred_geodirectory_review_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-geodirectory-review-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-geodirectory-review-options').each(function () {
            var current_select = $(this);
            var current_val = current_select.val();

            // Loop through options
            current_select.find('option').each(function () {
                var option_val = $(this).attr('value');

                // If this option is selected elsewhere, disable it
                // BUT if it is the currently selected value of THIS select, keep it enabled
                if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                    $(this).attr('disabled', 'disabled');
                } else {
                    $(this).removeAttr('disabled');
                }
            });
        });
    }

});

