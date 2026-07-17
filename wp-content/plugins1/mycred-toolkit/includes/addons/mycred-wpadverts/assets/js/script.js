jQuery(document).ready(function ($) {

    // Add More specific hook fields - Contact Author Hook
    $(document).on('click', '.wpadverts-add-specific-contact-hook', function () {
        var parent_row = $(this).closest('.wpadverts_contact_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('.wpadverts-contact-options').trigger('change');
    });

    // Remove specific hook fields - Contact Author Hook
    $(document).on('click', '.wpadverts-remove-specific-contact-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.wpadverts_contact_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.wpadverts_contact_specific_row').remove();
                // Trigger change to re-enable options
                $('.wpadverts-contact-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Contact Author Hook
    function wpadverts_contact_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('.wpadverts-contact-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('.wpadverts-contact-options').each(function () {
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

    // Initial check for unique options - Contact Author
    $(document).on('change', '.wpadverts-contact-options', function () {
        wpadverts_contact_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('.wpadverts-contact-options').trigger('change');

    // Add More specific hook fields - Author Receives Message Hook
    $(document).on('click', '.wpadverts-add-specific-receive-hook', function () {
        var parent_row = $(this).closest('.wpadverts_receive_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('.wpadverts-receive-options').trigger('change');
    });

    // Remove specific hook fields - Author Receives Message Hook
    $(document).on('click', '.wpadverts-remove-specific-receive-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.wpadverts_receive_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.wpadverts_receive_specific_row').remove();
                // Trigger change to re-enable options
                $('.wpadverts-receive-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Author Receives Message Hook
    function wpadverts_receive_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('.wpadverts-receive-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('.wpadverts-receive-options').each(function () {
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

    // Initial check for unique options - Author Receives Message
    $(document).on('change', '.wpadverts-receive-options', function () {
        wpadverts_receive_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('.wpadverts-receive-options').trigger('change');

    // Add More specific hook fields - Renew Advert Hook
    $(document).on('click', '.wpadverts-add-specific-renew-hook', function () {
        var parent_row = $(this).closest('.wpadverts_renew_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('.wpadverts-renew-options').trigger('change');
    });

    // Remove specific hook fields - Renew Advert Hook
    $(document).on('click', '.wpadverts-remove-specific-renew-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.wpadverts_renew_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.wpadverts_renew_specific_row').remove();
                // Trigger change to re-enable options
                $('.wpadverts-renew-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Renew Advert Hook
    function wpadverts_renew_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('.wpadverts-renew-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('.wpadverts-renew-options').each(function () {
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

    // Initial check for unique options - Renew Advert
    $(document).on('change', '.wpadverts-renew-options', function () {
        wpadverts_renew_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('.wpadverts-renew-options').trigger('change');

});
