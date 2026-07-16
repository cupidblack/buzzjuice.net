jQuery(document).ready(() => {
    // ========== FORM SUBMISSION HOOK ==========
    
    // Add More button - Clone the specific form row
    jQuery(document).on('click', '.mycred-add-specific-arforms-hook', function () {
        var hook = jQuery(this).closest('.arforms_submit_custom_hook_class').clone();
        
        // Clear the values in the cloned row
        hook.find('input.mycred-arforms-creds').val('0');
        hook.find('input.mycred-arforms-log').val('%plural% for ARForm Submission');
        hook.find('select.mycred-arforms-options').val('0');
        
        // Insert the cloned row after the current one
        jQuery(this).closest('.arforms_submit_custom_hook_class').after(hook);
        
        // Update disabled options to prevent duplicate form selection
        jQuery('select.mycred-arforms-options').trigger('change');
    });

    // Remove button - Delete the current row
    jQuery(document).on('click', '.mycred-remove-specific-arforms-hook', function () {
        var container = jQuery(this).closest('.hook-instance');
        
        // Only allow removal if there's more than one row
        if (container.find('.arforms_submit_custom_hook_class').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.arforms_submit_custom_hook_class').remove();
                
                // Update disabled options after removal
                jQuery('select.mycred-arforms-options').trigger('change');
            }
        } else {
            alert("You must have at least one form configuration.");
        }
    });

    // When a form is selected, disable it in other dropdowns
    jQuery(document).on('change', 'select.mycred-arforms-options', function () {
        mycred_arforms_enable_disable_options(jQuery(this));
    });

    // Initialize on page load
    jQuery('select.mycred-arforms-options').trigger('change');

    // ========== FIELD VALUE HOOK ==========
    
    // Add More button - Clone the field value row
    jQuery(document).on('click', '.mycred-add-specific-arforms-field-hook', function () {
        var hook = jQuery(this).closest('.arforms_field_value_custom_hook_class').clone();
        
        // Clear the values in the cloned row
        hook.find('input.mycred-arforms-field-creds').val('0');
        hook.find('input.mycred-arforms-field-log').val('%plural% for submitting field value');
        hook.find('select.mycred-arforms-field-form-select').val('0');
        hook.find('select.mycred-arforms-field-select').html('<option value="">Select Field</option>').val('');
        hook.find('input.mycred-arforms-field-value').val('');
        hook.find('select.mycred-arforms-field-select').removeAttr('data-selected-field');
        
        // Insert the cloned row after the current one
        jQuery(this).closest('.arforms_field_value_custom_hook_class').after(hook);
        
        // Update disabled options to prevent duplicate form selection
        jQuery('select.mycred-arforms-field-form-select').trigger('change');
    });

    // Remove button - Delete the field value row
    jQuery(document).on('click', '.mycred-remove-specific-arforms-field-hook', function () {
        var container = jQuery(this).closest('.hook-instance');
        
        // Only allow removal if there's more than one row
        if (container.find('.arforms_field_value_custom_hook_class').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.arforms_field_value_custom_hook_class').remove();
                
                // Update disabled options after removal
                jQuery('select.mycred-arforms-field-form-select').trigger('change');
            }
        } else {
            alert("You must have at least one field value configuration.");
        }
    });

    // When a form is selected in field value hook, load its fields
    jQuery(document).on('change', 'select.mycred-arforms-field-form-select', function () {
        // Update disabled options to prevent duplicate form selection
        mycred_arforms_field_value_enable_disable_options(jQuery(this));
        
        var formId = jQuery(this).val();
        var fieldSelect = jQuery(this).closest('.arforms_field_value_custom_hook_class').find('select.mycred-arforms-field-select');
        
        // Ensure field dropdown exists
        if (fieldSelect.length === 0) {
            console.error('Field dropdown not found');
            return;
        }
        
        var selectedField = fieldSelect.attr('data-selected-field');
        
        // Load fields via AJAX (for both specific form and "Any Form")
        mycred_arforms_load_fields(formId, fieldSelect, selectedField);
    });

    // Trigger form change on page load to populate fields for existing configurations
    // This will load fields for both specific forms and "Any Form" selections
    jQuery('select.mycred-arforms-field-form-select').each(function() {
        var formSelect = jQuery(this);
        var formId = formSelect.val();
        var fieldSelect = formSelect.closest('.arforms_field_value_custom_hook_class').find('select.mycred-arforms-field-select');
        
        // Load fields for both specific form and "Any Form" (0)
        if (fieldSelect.length > 0) {
            var selectedField = fieldSelect.attr('data-selected-field');
            // Trigger change to load fields (works for both specific form and "Any Form")
            formSelect.trigger('change');
        }
    });
    
    // Initialize disabled options on page load for field value hook
    if (jQuery('select.mycred-arforms-field-form-select').length > 0) {
        mycred_arforms_field_value_enable_disable_options(jQuery('select.mycred-arforms-field-form-select').first());
    }
});

/**
 * Enable/Disable form options to prevent duplicate selection
 */
function mycred_arforms_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    
    // Collect all selected form IDs
    container.find('select.mycred-arforms-options').each(function () {
        var selectedValue = jQuery(this).val();
        if (selectedValue && selectedValue != '0') {
            selected.push(selectedValue);
        }
    });
    
    // Disable selected options in other dropdowns
    container.find('select.mycred-arforms-options').each(function () {
        var currentSelect = jQuery(this);
        var currentValue = currentSelect.val();
        
        currentSelect.find('option').each(function () {
            var optionValue = jQuery(this).attr('value');
            
            // Don't disable the "Select Form" option or the currently selected value
            if (optionValue != '0' && optionValue != currentValue) {
                if (selected.includes(optionValue)) {
                    jQuery(this).attr('disabled', 'disabled');
                } else {
                    jQuery(this).removeAttr('disabled');
                }
            }
        });
    });
}

/**
 * Enable/Disable form options in field value hook to prevent duplicate selection
 */
function mycred_arforms_field_value_enable_disable_options(ele) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    
    // Collect all selected form IDs
    container.find('select.mycred-arforms-field-form-select').each(function () {
        var selectedValue = jQuery(this).val();
        if (selectedValue && selectedValue != '0') {
            selected.push(selectedValue);
        }
    });
    
    // Disable selected options in other dropdowns
    container.find('select.mycred-arforms-field-form-select').each(function () {
        var currentSelect = jQuery(this);
        var currentValue = currentSelect.val();
        
        currentSelect.find('option').each(function () {
            var optionValue = jQuery(this).attr('value');
            
            // Don't disable the "Any Form" option or the currently selected value
            if (optionValue != '0' && optionValue != currentValue) {
                if (selected.includes(optionValue)) {
                    jQuery(this).attr('disabled', 'disabled');
                } else {
                    jQuery(this).removeAttr('disabled');
                }
            }
        });
    });
}

/**
 * Load form fields via AJAX
 */
function mycred_arforms_load_fields(formId, fieldSelect, selectedField) {
    // Ensure field dropdown exists
    if (fieldSelect.length === 0) {
        console.error('Field dropdown not found in mycred_arforms_load_fields');
        return;
    }
    
    // Show loading state
    fieldSelect.html('<option value="">Loading fields...</option>');
    
    // Use the localized AJAX URL if available, otherwise fall back to ajaxurl
    var ajaxUrl = (typeof mycred_arforms_ajax !== 'undefined' && mycred_arforms_ajax.ajax_url) 
        ? mycred_arforms_ajax.ajax_url 
        : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
    
    var ajaxNonce = (typeof mycred_arforms_ajax !== 'undefined' && mycred_arforms_ajax.nonce) 
        ? mycred_arforms_ajax.nonce 
        : '';
    
    jQuery.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'mycred_arforms_get_form_fields',
            form_id: formId,
            nonce: ajaxNonce
        },
        success: function(response) {
            // Always start with "Select Field" option
            var options = '<option value="">Select Field</option>';
            
            if (response && response.success && response.data && response.data.fields && response.data.fields.length > 0) {
                jQuery.each(response.data.fields, function(index, field) {
                    var selected = (selectedField && selectedField == field.id) ? 'selected' : '';
                    var displayText = field.name + ' (' + field.type + ')';
                    
                    // If form_name exists (when "Any Form" is selected), include form name in display
                    if (field.form_name) {
                        displayText = field.form_name + ' - ' + displayText;
                    }
                    
                    options += '<option value="' + field.id + '" ' + selected + '>' + 
                               displayText + 
                               '</option>';
                });
            } else {
                // If no fields found, still show "Select Field" option
                options += '<option value="" disabled>No fields found</option>';
            }
            
            fieldSelect.html(options);
            
            // Clear the data attribute after use
            if (selectedField) {
                fieldSelect.removeAttr('data-selected-field');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error loading fields:', status, error, xhr);
            // Always show "Select Field" option even on error
            fieldSelect.html('<option value="">Select Field</option><option value="" disabled>Error loading fields</option>');
        }
    });
}
