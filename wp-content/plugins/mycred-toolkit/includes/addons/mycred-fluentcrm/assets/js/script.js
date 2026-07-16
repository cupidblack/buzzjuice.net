jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        jQuery('select.mycred-fluentcrm-tags-options').trigger('change');
        jQuery('select.mycred-fluentcrm-tags-removed-options').trigger('change');
        jQuery('select.mycred-fluentcrm-lists-options').trigger('change');
        jQuery('select.mycred-fluentcrm-lists-removed-options').trigger('change');
    }, 500);

    // Initial check for unique options - Tag Added
    jQuery(document).on('change', 'select.mycred-fluentcrm-tags-options', function () {
        mycred_fluentcrm_tags_enable_disable_options(jQuery(this));
    });

    // Initial check for unique options - Tag Removed
    jQuery(document).on('change', 'select.mycred-fluentcrm-tags-removed-options', function () {
        mycred_fluentcrm_tags_removed_enable_disable_options(jQuery(this));
    });

    // Initial check for unique options - List Added
    jQuery(document).on('change', 'select.mycred-fluentcrm-lists-options', function () {
        mycred_fluentcrm_lists_enable_disable_options(jQuery(this));
    });

    // Initial check for unique options - List Removed
    jQuery(document).on('change', 'select.mycred-fluentcrm-lists-removed-options', function () {
        mycred_fluentcrm_lists_removed_enable_disable_options(jQuery(this));
    });

    // Add More specific hook fields - Tag Added
    jQuery(document).on('click', '.mycred-add-specific-fluentcrm-tags-hook', function () {
        var parent_row = jQuery(this).closest('.fluentcrm_tags_specific_row');
        var clone = parent_row.clone();

        // Keep points and log template from the last row; reset tag select only
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        jQuery('select.mycred-fluentcrm-tags-options').trigger('change');
    });

    // Remove specific hook fields - Tag Added
    jQuery(document).on('click', '.mycred-remove-specific-fluentcrm-tags-hook', function () {
        var container = jQuery(this).closest('.hook-instance');

        if (container.find('.fluentcrm_tags_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.fluentcrm_tags_specific_row').remove();
                // Trigger change to re-enable options
                jQuery('select.mycred-fluentcrm-tags-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - Tag Removed
    jQuery(document).on('click', '.mycred-add-specific-fluentcrm-tags-removed-hook', function () {
        var parent_row = jQuery(this).closest('.fluentcrm_tags_removed_specific_row');
        var clone = parent_row.clone();

        // Keep points and log template from the last row; reset tag select only
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        jQuery('select.mycred-fluentcrm-tags-removed-options').trigger('change');
    });

    // Remove specific hook fields - Tag Removed
    jQuery(document).on('click', '.mycred-remove-specific-fluentcrm-tags-removed-hook', function () {
        var container = jQuery(this).closest('.hook-instance');

        if (container.find('.fluentcrm_tags_removed_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.fluentcrm_tags_removed_specific_row').remove();
                // Trigger change to re-enable options
                jQuery('select.mycred-fluentcrm-tags-removed-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - List Added
    jQuery(document).on('click', '.mycred-add-specific-fluentcrm-lists-hook', function () {
        var parent_row = jQuery(this).closest('.fluentcrm_lists_specific_row');
        var clone = parent_row.clone();

        // Keep points and log template from the last row; reset list select only
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        jQuery('select.mycred-fluentcrm-lists-options').trigger('change');
    });

    // Remove specific hook fields - List Added
    jQuery(document).on('click', '.mycred-remove-specific-fluentcrm-lists-hook', function () {
        var container = jQuery(this).closest('.hook-instance');

        if (container.find('.fluentcrm_lists_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.fluentcrm_lists_specific_row').remove();
                // Trigger change to re-enable options
                jQuery('select.mycred-fluentcrm-lists-options').trigger('change');
            }
        }
    });

    // Add More specific hook fields - List Removed
    jQuery(document).on('click', '.mycred-add-specific-fluentcrm-lists-removed-hook', function () {
        var parent_row = jQuery(this).closest('.fluentcrm_lists_removed_specific_row');
        var clone = parent_row.clone();

        // Keep points and log template from the last row; reset list select only
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        jQuery('select.mycred-fluentcrm-lists-removed-options').trigger('change');
    });

    // Remove specific hook fields - List Removed
    jQuery(document).on('click', '.mycred-remove-specific-fluentcrm-lists-removed-hook', function () {
        var container = jQuery(this).closest('.hook-instance');

        if (container.find('.fluentcrm_lists_removed_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.fluentcrm_lists_removed_specific_row').remove();
                // Trigger change to re-enable options
                jQuery('select.mycred-fluentcrm-lists-removed-options').trigger('change');
            }
        }
    });

});

// Enable/Disable options function for Tag Added (defined outside ready handler like LearnDash)
function mycred_fluentcrm_tags_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');

    // Collect all selected values
    container.find('select.mycred-fluentcrm-tags-options').each(function () {
        var val = jQuery(this).val();
        if (val != '0' && val != '') {
            selected.push(val);
        }
    });

    // Loop through each select to disable/enable
    container.find('select.mycred-fluentcrm-tags-options').each(function () {
        var current_select = jQuery(this);
        var current_val = current_select.val();

        // Loop through options
        current_select.find('option').each(function () {
            var option_val = jQuery(this).attr('value');

            // If this option is selected elsewhere, disable it
            // BUT if it is the currently selected value of THIS select, keep it enabled
            if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                jQuery(this).attr('disabled', 'disabled');
            } else {
                jQuery(this).removeAttr('disabled');
            }
        });
    });
}

// Enable/Disable options function for Tag Removed (defined outside ready handler like LearnDash)
function mycred_fluentcrm_tags_removed_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');

    // Collect all selected values
    container.find('select.mycred-fluentcrm-tags-removed-options').each(function () {
        var val = jQuery(this).val();
        if (val != '0' && val != '') {
            selected.push(val);
        }
    });

    // Loop through each select to disable/enable
    container.find('select.mycred-fluentcrm-tags-removed-options').each(function () {
        var current_select = jQuery(this);
        var current_val = current_select.val();

        // Loop through options
        current_select.find('option').each(function () {
            var option_val = jQuery(this).attr('value');

            // If this option is selected elsewhere, disable it
            // BUT if it is the currently selected value of THIS select, keep it enabled
            if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                jQuery(this).attr('disabled', 'disabled');
            } else {
                jQuery(this).removeAttr('disabled');
            }
        });
    });
}

// Enable/Disable options function for List Added (defined outside ready handler like LearnDash)
function mycred_fluentcrm_lists_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');

    // Collect all selected values
    container.find('select.mycred-fluentcrm-lists-options').each(function () {
        var val = jQuery(this).val();
        if (val != '0' && val != '') {
            selected.push(val);
        }
    });

    // Loop through each select to disable/enable
    container.find('select.mycred-fluentcrm-lists-options').each(function () {
        var current_select = jQuery(this);
        var current_val = current_select.val();

        // Loop through options
        current_select.find('option').each(function () {
            var option_val = jQuery(this).attr('value');

            // If this option is selected elsewhere, disable it
            // BUT if it is the currently selected value of THIS select, keep it enabled
            if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                jQuery(this).attr('disabled', 'disabled');
            } else {
                jQuery(this).removeAttr('disabled');
            }
        });
    });
}

// Enable/Disable options function for List Removed (defined outside ready handler like LearnDash)
function mycred_fluentcrm_lists_removed_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');

    // Collect all selected values
    container.find('select.mycred-fluentcrm-lists-removed-options').each(function () {
        var val = jQuery(this).val();
        if (val != '0' && val != '') {
            selected.push(val);
        }
    });

    // Loop through each select to disable/enable
    container.find('select.mycred-fluentcrm-lists-removed-options').each(function () {
        var current_select = jQuery(this);
        var current_val = current_select.val();

        // Loop through options
        current_select.find('option').each(function () {
            var option_val = jQuery(this).attr('value');

            // If this option is selected elsewhere, disable it
            // BUT if it is the currently selected value of THIS select, keep it enabled
            if (option_val != '0' && selected.includes(option_val) && option_val != current_val) {
                jQuery(this).attr('disabled', 'disabled');
            } else {
                jQuery(this).removeAttr('disabled');
            }
        });
    });
}
