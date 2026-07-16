jQuery(document).ready(function ($) {

    // Trigger change on widget click
    $(document).on('click', '#widget-mycred-hook_asgaros_reply_topic', function (e) {
        $('select.user_select_topic').trigger('change');
    });

    // ========== SPECIFIC TOPIC SECTION ==========
    
    // Add More button functionality for topic section
    $(document).on('click', '.mycred-add-specific-asgaros-hook', function () {
        var hook = $(this).closest('.asgaros_reply_custom_hook_class').clone();

        // Clear values in cloned element
        hook.find('input.mycred-asgaros-creds').val('0');
        hook.find('input.mycred-asgaros-log').val('%plural% for replying to a topic');
        hook.find('select.user_select_topic').val('0');

        // Insert after current element
        $(this).closest('.asgaros_reply_custom_hook_class').after(hook);

        // Trigger change to update disabled options
        $('select.user_select_topic').trigger('change');
    });

    // Remove button functionality for topic section
    $(document).on('click', '.mycred-remove-specific-asgaros-hook', function () {
        var container = $(this).closest('.hook-instance');

        // Only allow removal if more than one hook exists
        if (container.find('.asgaros_reply_custom_hook_class').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.asgaros_reply_custom_hook_class').remove();
                $('select.user_select_topic').trigger('change');
            }
        } else {
            alert("You must have at least one topic configuration.");
        }
    });

    // ========== SPECIFIC FORUM SECTION ==========
    
    // Add More button functionality for forum section
    $(document).on('click', '.mycred-add-specific-asgaros-forum-hook', function () {
        var hook = $(this).closest('.asgaros_forum_reply_custom_hook_class').clone();

        // Clear values in cloned element
        hook.find('input.mycred-asgaros-forum-creds').val('0');
        hook.find('input.mycred-asgaros-forum-log').val('%plural% for replying to a topic in a forum');
        hook.find('select.user_select_forum').val('0');

        // Insert after current element
        $(this).closest('.asgaros_forum_reply_custom_hook_class').after(hook);

        // Trigger change to update disabled options
        $('select.user_select_forum').trigger('change');
    });

    // Remove button functionality for forum section
    $(document).on('click', '.mycred-remove-specific-asgaros-forum-hook', function () {
        var container = $(this).closest('.hook-instance');

        // Only allow removal if more than one hook exists
        if (container.find('.asgaros_forum_reply_custom_hook_class').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                $(this).closest('.asgaros_forum_reply_custom_hook_class').remove();
                $('select.user_select_forum').trigger('change');
            }
        } else {
            alert("You must have at least one forum configuration.");
        }
    });

    // Handle dropdown change to manage disabled options for topics
    $(document).on('change', 'select.user_select_topic', function () {
        mycred_asgaros_enable_disable_options($(this));
    });

    // Handle dropdown change to manage disabled options for forums
    $(document).on('change', 'select.user_select_forum', function () {
        mycred_asgaros_forum_enable_disable_options($(this));
    });

    /**
     * Enable/Disable dropdown options to prevent duplicate selections for topics
     */
    function mycred_asgaros_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.user_select_topic').each(function () {
            var selectedValue = $(this).val();
            if (selectedValue && selectedValue != '0') {
                selected.push(selectedValue);
            }
        });

        // Disable selected options in other dropdowns
        container.find('select.user_select_topic').each(function () {
            var currentSelect = $(this);
            var currentValue = currentSelect.val();

            // Reset all options first
            currentSelect.find('option').removeAttr('disabled');

            // Disable options that are selected in other dropdowns
            selected.forEach(function (value) {
                if (value !== currentValue && value !== '0') {
                    currentSelect.find('option[value="' + value + '"]').attr('disabled', 'disabled');
                }
            });
        });
    }

    /**
     * Enable/Disable forum dropdown options to prevent duplicate selections
     */
    function mycred_asgaros_forum_enable_disable_options(ele) {
        var selected = [];
        var container = ele.closest('.hook-instance');

        // Collect all selected values
        container.find('select.user_select_forum').each(function () {
            var selectedValue = $(this).val();
            if (selectedValue && selectedValue != '0') {
                selected.push(selectedValue);
            }
        });

        // Disable selected options in other dropdowns
        container.find('select.user_select_forum').each(function () {
            var currentSelect = $(this);
            var currentValue = currentSelect.val();

            // Reset all options first
            currentSelect.find('option').removeAttr('disabled');

            // Disable options that are selected in other dropdowns
            selected.forEach(function (value) {
                if (value !== currentValue && value !== '0') {
                    currentSelect.find('option[value="' + value + '"]').attr('disabled', 'disabled');
                }
            });
        });
    }

    // Initialize on page load
    if ($('select.user_select_topic').length > 0) {
        $('select.user_select_topic').first().trigger('change');
    }
    
    if ($('select.user_select_forum').length > 0) {
        $('select.user_select_forum').first().trigger('change');
    }
});
