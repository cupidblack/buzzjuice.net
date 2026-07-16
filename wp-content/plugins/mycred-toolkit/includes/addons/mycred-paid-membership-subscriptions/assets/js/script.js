jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        $('select.mycred-pms-subscription-options').trigger('change');
    }, 500);

    // Initial check for unique options
    $(document).on('change', 'select.mycred-pms-subscription-options', function () {
        mycred_pms_enable_disable_options($(this));
    });

    // Add More specific hook fields
    $(document).on('click', '.mycred-add-specific-pms-subscription-hook', function () {
        var parent_row = $(this).closest('.pms_subscription_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-subscription-options').trigger('change');
    });

    // Remove specific hook fields
    $(document).on('click', '.mycred-remove-specific-pms-subscription-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_subscription_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_subscription_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-subscription-options').trigger('change');
            }
        }
    });

    // Enable/Disable options
    function mycred_pms_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-subscription-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-subscription-options').each(function () {
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


    // Add More specific hook fields - Payment Hook
    $(document).on('click', '.mycred-add-specific-pms-payment-hook', function () {
        var parent_row = $(this).closest('.pms_payment_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-payment-options').trigger('change');
    });

    // Remove specific hook fields - Payment Hook
    $(document).on('click', '.mycred-remove-specific-pms-payment-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_payment_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_payment_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-payment-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Payment Hook
    function mycred_pms_payment_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-payment-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-payment-options').each(function () {
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

    // Initial check for unique options - Payment
    $(document).on('change', 'select.mycred-pms-payment-options', function () {
        mycred_pms_payment_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('select.mycred-pms-payment-options').trigger('change');

    // Add More specific hook fields - Renewal Hook
    $(document).on('click', '.mycred-add-specific-pms-renewal-hook', function () {
        var parent_row = $(this).closest('.pms_renewal_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-renewal-options').trigger('change');
    });

    // Remove specific hook fields - Renewal Hook
    $(document).on('click', '.mycred-remove-specific-pms-renewal-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_renewal_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_renewal_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-renewal-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Renewal Hook
    function mycred_pms_renewal_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-renewal-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-renewal-options').each(function () {
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

    // Initial check for unique options - Renewal
    $(document).on('change', 'select.mycred-pms-renewal-options', function () {
        mycred_pms_renewal_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('select.mycred-pms-renewal-options').trigger('change');

    // Add More specific hook fields - Change Hook
    $(document).on('click', '.mycred-add-specific-pms-change-hook', function () {
        var parent_row = $(this).closest('.pms_change_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-change-options').trigger('change');
    });

    // Remove specific hook fields - Change Hook
    $(document).on('click', '.mycred-remove-specific-pms-change-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_change_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_change_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-change-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Change Hook
    function mycred_pms_change_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-change-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-change-options').each(function () {
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

    // Initial check for unique options - Change
    $(document).on('change', 'select.mycred-pms-change-options', function () {
        mycred_pms_change_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('select.mycred-pms-change-options').trigger('change');

    // Add More specific hook fields - Cancel Hook
    $(document).on('click', '.mycred-add-specific-pms-subscription-cancel-hook', function () {
        var parent_row = $(this).closest('.pms_subscription_specific_row_cancel');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-subscription-cancel-options').trigger('change');
    });

    // Remove specific hook fields - Cancel Hook
    $(document).on('click', '.mycred-remove-specific-pms-subscription-cancel-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_subscription_specific_row_cancel').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_subscription_specific_row_cancel').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-subscription-cancel-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Cancel Hook
    function mycred_pms_cancel_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-subscription-cancel-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-subscription-cancel-options').each(function () {
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

    // Initial check for unique options - Cancel
    $(document).on('change', 'select.mycred-pms-subscription-cancel-options', function () {
        mycred_pms_cancel_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('select.mycred-pms-subscription-cancel-options').trigger('change');

    // Add More specific hook fields - Abandon Hook
    $(document).on('click', '.mycred-add-specific-pms-subscription-abandon-hook', function () {
        var parent_row = $(this).closest('.pms_subscription_specific_row_abandon');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-pms-subscription-abandon-options').trigger('change');
    });

    // Remove specific hook fields - Abandon Hook
    $(document).on('click', '.mycred-remove-specific-pms-subscription-abandon-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.pms_subscription_specific_row_abandon').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.pms_subscription_specific_row_abandon').remove();
                // Trigger change to re-enable options
                $('select.mycred-pms-subscription-abandon-options').trigger('change');
            }
        }
    });

    // Enable/Disable options - Abandon Hook
    function mycred_pms_abandon_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-pms-subscription-abandon-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-pms-subscription-abandon-options').each(function () {
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

    // Initial check for unique options - Abandon
    $(document).on('change', 'select.mycred-pms-subscription-abandon-options', function () {
        mycred_pms_abandon_enable_disable_options($(this));
    });

    // Trigger change on load to disable already selected options
    $('select.mycred-pms-subscription-abandon-options').trigger('change');

});
