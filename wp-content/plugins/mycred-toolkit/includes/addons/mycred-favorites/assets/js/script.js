jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        $('select.mycred-favorites-options').trigger('change');
        $('select.mycred-favorites-author-options').trigger('change');
        $('select.mycred-favorites-unfavorite-options').trigger('change');
        $('select.mycred-favorites-author-unfavorite-options').trigger('change');
    }, 500);

    // Initial check for unique options - User Favorites
    $(document).on('change', 'select.mycred-favorites-options', function () {
        mycred_favorites_enable_disable_options($(this));
    });

    // Initial check for unique options - Author Favorites
    $(document).on('change', 'select.mycred-favorites-author-options', function () {
        mycred_favorites_author_enable_disable_options($(this));
    });

    // Initial check for unique options - Unfavorite
    $(document).on('change', 'select.mycred-favorites-unfavorite-options', function () {
        mycred_favorites_unfavorite_enable_disable_options($(this));
    });

    // Initial check for unique options - Author Unfavorite
    $(document).on('change', 'select.mycred-favorites-author-unfavorite-options', function () {
        mycred_favorites_author_unfavorite_enable_disable_options($(this));
    });

    // Add More specific hook fields - User Favorites
    $(document).on('click', '.mycred-add-specific-favorites-hook', function () {
        var parent_row = $(this).closest('.favorites_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-favorites-options').trigger('change');
    });

    // Remove specific hook fields - User Favorites
    $(document).on('click', '.mycred-remove-specific-favorites-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.favorites_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.favorites_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-favorites-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - Author Favorites
    $(document).on('click', '.mycred-add-specific-favorites-author-hook', function () {
        var parent_row = $(this).closest('.favorites_author_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-favorites-author-options').trigger('change');
    });

    // Remove specific hook fields - Author Favorites
    $(document).on('click', '.mycred-remove-specific-favorites-author-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.favorites_author_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.favorites_author_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-favorites-author-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - Unfavorite
    $(document).on('click', '.mycred-add-specific-favorites-unfavorite-hook', function () {
        var parent_row = $(this).closest('.favorites_unfavorite_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-favorites-unfavorite-options').trigger('change');
    });

    // Remove specific hook fields - Unfavorite
    $(document).on('click', '.mycred-remove-specific-favorites-unfavorite-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.favorites_unfavorite_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.favorites_unfavorite_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-favorites-unfavorite-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - Author Unfavorite
    $(document).on('click', '.mycred-add-specific-favorites-author-unfavorite-hook', function () {
        var parent_row = $(this).closest('.favorites_author_unfavorite_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0'); // Reset select

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-favorites-author-unfavorite-options').trigger('change');
    });

    // Remove specific hook fields - Author Unfavorite
    $(document).on('click', '.mycred-remove-specific-favorites-author-unfavorite-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.favorites_author_unfavorite_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.favorites_author_unfavorite_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-favorites-author-unfavorite-options').trigger('change');
            }
        }
    });

    // User Favorites - Enable/Disable options
    function mycred_favorites_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-favorites-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-favorites-options').each(function () {
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

    // Author Favorites - Enable/Disable options
    function mycred_favorites_author_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-favorites-author-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-favorites-author-options').each(function () {
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

    // Unfavorite - Enable/Disable options
    function mycred_favorites_unfavorite_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-favorites-unfavorite-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-favorites-unfavorite-options').each(function () {
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

    // Author Unfavorite - Enable/Disable options
    function mycred_favorites_author_unfavorite_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-favorites-author-unfavorite-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-favorites-author-unfavorite-options').each(function () {
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

