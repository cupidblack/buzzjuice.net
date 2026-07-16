jQuery(document).ready(function ($) {

    // Trigger change on load to disable already selected options
    setTimeout(function () {
        $('select.mycred-fluent-community-options').trigger('change');
    }, 500);

    // Initial check for unique options
    $(document).on('change', 'select.mycred-fluent-community-options', function () {
        mycred_fluent_community_enable_disable_options($(this));
    });

    // Add More specific hook fields (Creation)
    $(document).on('click', '.mycred-add-specific-fluent-community-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Deletion)
    $(document).on('click', '.mycred-add-specific-fluent-community-delete-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_delete');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Reaction)
    $(document).on('click', '.mycred-add-specific-fluent-community-reaction-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_reaction');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Join Space)
    $(document).on('click', '.mycred-add-specific-fluent-community-join-space-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_join_space');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Request Join Space)
    $(document).on('click', '.mycred-add-specific-fluent-community-request-join-space-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_request_join_space');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Leave Space)
    $(document).on('click', '.mycred-add-specific-fluent-community-leave-space-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_leave_space');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Comment)
    $(document).on('click', '.mycred-add-specific-fluent-community-comment-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_comment');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Delete Comment)
    $(document).on('click', '.mycred-add-specific-fluent-community-delete-comment-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_delete_comment');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Add More specific hook fields (Delete Space)
    $(document).on('click', '.mycred-add-specific-fluent-community-delete-space-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_delete_space');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Remove specific hook fields (Creation)
    $(document).on('click', '.mycred-remove-specific-fluent-community-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Deletion)
    $(document).on('click', '.mycred-remove-specific-fluent-community-delete-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_delete').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_delete').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Reaction)
    $(document).on('click', '.mycred-remove-specific-fluent-community-reaction-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_reaction').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_reaction').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Join Space)
    $(document).on('click', '.mycred-remove-specific-fluent-community-join-space-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_join_space').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_join_space').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Request Join Space)
    $(document).on('click', '.mycred-remove-specific-fluent-community-request-join-space-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_request_join_space').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_request_join_space').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Leave Space)
    $(document).on('click', '.mycred-remove-specific-fluent-community-leave-space-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_leave_space').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_leave_space').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Comment)
    $(document).on('click', '.mycred-remove-specific-fluent-community-comment-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_comment').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_comment').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Delete Comment)
    $(document).on('click', '.mycred-remove-specific-fluent-community-delete-comment-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_delete_comment').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_delete_comment').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Remove specific hook fields (Delete Space)
    $(document).on('click', '.mycred-remove-specific-fluent-community-delete-space-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_delete_space').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_delete_space').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Add More specific hook fields (Course Enrollment)
    $(document).on('click', '.mycred-add-specific-fluent-community-course-enrollment-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_course_enrollment');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');
        clone.find('input#mycred-course-enrollment-log').val('%plural% for enrolling in specific course');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Remove specific hook fields (Course Enrollment)
    $(document).on('click', '.mycred-remove-specific-fluent-community-course-enrollment-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_course_enrollment').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_course_enrollment').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Add More specific hook fields (Course Unenrollment)
    $(document).on('click', '.mycred-add-specific-fluent-community-course-unenrollment-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_course_unenrollment');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Remove specific hook fields (Course Unenrollment)
    $(document).on('click', '.mycred-remove-specific-fluent-community-course-unenrollment-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_course_unenrollment').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_course_unenrollment').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Add More specific hook fields (Delete Course)
    $(document).on('click', '.mycred-add-specific-fluent-community-delete-course-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_delete_course');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Remove specific hook fields (Delete Course)
    $(document).on('click', '.mycred-remove-specific-fluent-community-delete-course-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_delete_course').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_delete_course').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    // Add More specific hook fields (Complete Course)
    $(document).on('click', '.mycred-add-specific-fluent-community-course-completion-hook', function () {
        var parent_row = $(this).closest('.fluent_community_specific_row_course_completion');
        var clone = parent_row.clone();

        // Clear values in cloned inputs
        clone.find('input[type="text"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('select').val('0');

        // Insert after the current row
        parent_row.after(clone);

        // Trigger change to update disabled options
        $('select.mycred-fluent-community-options').trigger('change');
    });

    // Remove specific hook fields (Complete Course)
    $(document).on('click', '.mycred-remove-specific-fluent-community-course-completion-hook', function () {
        var container = $(this).closest('.hook-instance');

        if (container.find('.fluent_community_specific_row_course_completion').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.fluent_community_specific_row_course_completion').remove();
                // Trigger change to re-enable options
                $('select.mycred-fluent-community-options').trigger('change');
            }
        } else {
            alert("You must have at least one specific hook row.");
        }
    });

    function mycred_fluent_community_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.mycred-fluent-community-options').each(function () {
            var val = $(this).val();
            if (val != '0' && val != '') {
                selected.push(val);
            }
        });

        // Loop through each select to disable/enable
        container.find('select.mycred-fluent-community-options').each(function () {
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
