jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        $('select.mycred-presto-player-options').trigger('change');
        $('select.mycred-presto-player-percent-options').trigger('change');
        $('select.mycred-presto-player-percent-range-options').trigger('change');
    }, 500);

    // Initial check for unique options - Original hook
    $(document).on('change', 'select.mycred-presto-player-options', function () {
        mycred_presto_player_enable_disable_options($(this));
    });

    // Initial check for unique options - Percent hook
    $(document).on('change', 'select.mycred-presto-player-percent-options', function () {
        mycred_presto_player_percent_enable_disable_options($(this));
    });

    // Initial check for unique options - Percent Range hook
    $(document).on('change', 'select.mycred-presto-player-percent-range-options', function () {
        mycred_presto_player_percent_range_enable_disable_options($(this));
    });

    // Add More specific hook fields - Original hook
    $(document).on('click', '.mycred-add-specific-presto-player-hook', function () {
        var parent_row = $(this).closest('.presto_player_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val(''); // Just in case
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-presto-player-options').trigger('change');
    });

    // Remove specific hook fields - Original hook
    $(document).on('click', '.mycred-remove-specific-presto-player-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.presto_player_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.presto_player_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-presto-player-options').trigger('change');
            }
        } else {
            // If it's the last one, just clear it (optional)
        }
    });

    // Add More specific hook fields - Percent hook
    $(document).on('click', '.mycred-add-specific-presto-player-percent-hook', function () {
        var parent_row = $(this).closest('.presto_player_percent_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('50'); // Default percent
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-presto-player-percent-options').trigger('change');
    });

    // Remove specific hook fields - Percent hook
    $(document).on('click', '.mycred-remove-specific-presto-player-percent-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.presto_player_percent_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.presto_player_percent_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-presto-player-percent-options').trigger('change');
            }
        } else {
            // If it's the last one, just clear it (optional)
        }
    });

    // Add More specific hook fields - Percent Range hook
    $(document).on('click', '.mycred-add-specific-presto-player-percent-range-hook', function () {
        var parent_row = $(this).closest('.presto_player_percent_range_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        // Set default values for min/max percent
        clone.find('input[type="number"]').each(function (index) {
            if (index === 0) {
                $(this).val('25'); // Default min percent
            } else {
                $(this).val('75'); // Default max percent
            }
        });
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-presto-player-percent-range-options').trigger('change');
    });

    // Remove specific hook fields - Percent Range hook
    $(document).on('click', '.mycred-remove-specific-presto-player-percent-range-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.presto_player_percent_range_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.presto_player_percent_range_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-presto-player-percent-range-options').trigger('change');
            }
        } else {
            // If it's the last one, just clear it (optional)
        }
    });

    // Enable/disable options for original hook
    function mycred_presto_player_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-presto-player-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-presto-player-options').each(function () {
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

    // Enable/disable options for percent hook
    function mycred_presto_player_percent_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-presto-player-percent-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-presto-player-percent-options').each(function () {
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

    // Enable/disable options for percent range hook
    function mycred_presto_player_percent_range_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-presto-player-percent-range-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-presto-player-percent-range-options').each(function () {
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
