jQuery(function () {
  jQuery(document).on("click", ".widgetopts-notice .notice-dismiss", function () {
    jQuery.ajax({
      url: widgetopts10n.ajax_url,
      type: "POST",
      data: {
        method: "delete_widgetopts_update_transient",
        action: "widgetopts_ajax_settings",
        nonce: jQuery('input[name="widgetopts-settings-nonce"]').val(),
      },
    });
  });

  jQuery(document).on("change", ".widgetopts-settings-section #widgetopts-visibility-extended-taxonomy", function () {
    if (jQuery(this).is(":checked")) {
      jQuery(".widgetopts-settings-section .widgetopts-settings-taxonomy-checkbox").show();
    } else {
      jQuery(".widgetopts-settings-section .widgetopts-settings-taxonomy-checkbox").hide();
    }
  });

  /* start custom sidebar events */
  jQuery(document).on("click", ".widgetopts-settings-section .widgetopts-add-new-custom-sidebar", function () {
    jQuery("#widgetopts-custom-sidebar-name-temp").val("");
    jQuery("#widgetopts-custom-sidebar-id-temp").val("");
    jQuery("#widgetopts-custom-sidebar-description-temp").val("");
    jQuery(this).prop("disabled", true).parents(".widgetopts-settings-section").find("#widgetopts-temp-add-to-list").addClass("new").text("Add").parents(".widgetopts-settings-section").find(".widgetopts-custom-sidebar-field-container").show();
  });

  jQuery(document).on("click", ".widgetopts-settings-section .widgetopts-edit-custom-sidebar", function () {
    let _parent = jQuery(this).parents(".widgetops-cs-label");
    let key = _parent.data("key");

    jQuery("#widgetopts-custom-sidebar-name-temp").val(_parent.find(`#widgetopts-custom-sidebar-name-${key}`).val());
    jQuery("#widgetopts-custom-sidebar-id-temp").val(_parent.find(`#widgetopts-custom-sidebar-id-${key}`).val());
    jQuery("#widgetopts-custom-sidebar-description-temp").val(_parent.find(`#widgetopts-custom-sidebar-description-${key}`).val());

    jQuery(this).parents(".widgetopts-settings-section").find("#widgetopts-temp-add-to-list").removeClass("new").data("key", key).text("Save").parents(".widgetopts-settings-section").find(".widgetopts-custom-sidebar-field-container").show();
  });

  jQuery(document).on("click", ".widgetopts-settings-section .widgetopts-delete-custom-sidebar", function () {
    let isConfirmed = confirm("Are you sure you want to remove this widget area? All widgets assigned to it will also be deleted. You must click 'Save Settings' to apply these changes.");
    if (isConfirmed) {
      jQuery(this).parents(".widgetops-cs-label").next("hr").remove();
      jQuery(this).parents(".widgetops-cs-label").remove();
    }
  });

  jQuery(document).on("click", ".widgetopts-settings-section .widgetopts-toggle-custom-sidebar-info", function () {
    jQuery(this).parents(".widgetops-cs-label").find(".widgetopts-cs-toggle-info").toggle();
  });

  jQuery(document).on("change", "#widgetopts-custom-sidebar-name-temp", function () {
    if (jQuery("#widgetopts-temp-add-to-list").hasClass("new")) {
      let name = jQuery(this).val().toLowerCase().trim().replace(/ /gi, "_");
      jQuery("#widgetopts-custom-sidebar-id-temp").val(name);
    }
  });

  jQuery(document).on("click", "#widgetopts-temp-add-to-list, #widgetopts-temp-cancel-add-to-list", function () {
    if (jQuery("#widgetopts-custom-sidebar-name-temp").val().trim() == "" && jQuery(this).data("purpose") == "add") return;

    if (jQuery(this).data("purpose") == "add") {
      if (jQuery(this).hasClass("new")) {
        add_custom_sidebar_list();
      } else {
        update_custom_sidebar_list(jQuery(this).data("key"));
      }
    }

    jQuery(this).parents(".widgetopts-settings-section").find(".widgetopts-add-new-custom-sidebar").prop("disabled", false).parents(".widgetopts-settings-section").find(".widgetopts-custom-sidebar-field-container").hide();
  });

  function add_custom_sidebar_list() {
    let key = 1;
    let name = jQuery("#widgetopts-custom-sidebar-name-temp").val();
    let id = jQuery("#widgetopts-custom-sidebar-id-temp").val();
    let desc = jQuery("#widgetopts-custom-sidebar-description-temp").val();
    if (jQuery(".widgetopts-custom-sidebar-list-container .widgetops-cs-label").length >= 1) {
      key = get_incremented_key(jQuery(".widgetopts-custom-sidebar-list-container .widgetops-cs-label"));
    } else {
      jQuery(".widgetopts-custom-sidebar-list-container").show();
    }

    jQuery(".widgetopts-custom-sidebar-list-container .widgetopts-custom-sidebar-list-container-td").append(`<p class="widgetops-cs-label" data-key="${key}">
            <input type="checkbox" id="widgetopts-custom-sidebar-status-${key}" name="custom_sidebar[${key}][status]" value="1" checked />
            <span style="margin-bottom: 16px; display: inline-block;">${name}</span>
            <input type="hidden" id="widgetopts-custom-sidebar-name-${key}" name="custom_sidebar[${key}][name]" value="${name}" />
            <input type="hidden" id="widgetopts-custom-sidebar-id-${key}" name="custom_sidebar[${key}][id]" value="${id}_${key}" />
            <input type="hidden" id="widgetopts-custom-sidebar-description-${key}" name="custom_sidebar[${key}][description]" value="${desc}" />
            <span style="float: right;"><a class="widgetopts-edit-custom-sidebar" style="padding: 5px; cursor: pointer; margin-right: 5px;"><span class="dashicons dashicons-edit"></span></a><a class="widgetopts-delete-custom-sidebar" style="padding: 5px; cursor: pointer; color: #dc3545;"><span class="dashicons dashicons-trash"></span></a><a class="widgetopts-toggle-custom-sidebar-info" style="padding: 5px; cursor: pointer;"><span class="dashicons dashicons-arrow-down-alt2"></span></a></span>
            <span class="widgetopts-cs-toggle-info" style="display:none;min-height: 100px; width: 100%;">
							<span style="display:block;min-height: 100px;border: 1px solid #dcdcde;padding: 10px; margin-bottom: 16px; ">${desc}</span>
							<span style="display:block; margin-bottom: 16px; " class="widgetopts-shortcode-example"><code>[do_sidebar name="${name}"]</code></span>
						</span>
            </p><hr/>`);
  }

  function update_custom_sidebar_list(key) {
    let name = jQuery("#widgetopts-custom-sidebar-name-temp").val();
    let id = jQuery("#widgetopts-custom-sidebar-id-temp").val();
    let desc = jQuery("#widgetopts-custom-sidebar-description-temp").val();
    let status = jQuery(`#widgetopts-custom-sidebar-status-${key}`).is(":checked");

    jQuery(`.widgetopts-custom-sidebar-list-container .widgetopts-custom-sidebar-list-container-td .widgetops-cs-label[data-key=${key}]`).html(`
            <input type="checkbox" id="widgetopts-custom-sidebar-status-${key}" name="custom_sidebar[${key}][status]" value="1" ${status == 1 ? "checked" : ""} />
            <span style="margin-bottom: 16px; display: inline-block;">${name}</span>
            <input type="hidden" id="widgetopts-custom-sidebar-name-${key}" name="custom_sidebar[${key}][name]" value="${name}" />
            <input type="hidden" id="widgetopts-custom-sidebar-id-${key}" name="custom_sidebar[${key}][id]" value="${id}" />
            <input type="hidden" id="widgetopts-custom-sidebar-description-${key}" name="custom_sidebar[${key}][description]" value="${desc}" />
            <span style="float: right;"><a class="widgetopts-edit-custom-sidebar" style="padding: 5px; cursor: pointer; margin-right: 5px;"><span class="dashicons dashicons-edit"></span></a><a class="widgetopts-delete-custom-sidebar" style="padding: 5px; cursor: pointer; color: #dc3545;"><span class="dashicons dashicons-trash"></span></a><a class="widgetopts-toggle-custom-sidebar-info" style="padding: 5px; cursor: pointer;"><span class="dashicons dashicons-arrow-down-alt2"></span></a></span>
            <span class="widgetopts-cs-toggle-info" style="display:none;min-height: 100px; width: 100%;">
							<span style="display:block;min-height: 100px;border: 1px solid #dcdcde;padding: 10px; margin-bottom: 16px; ">${desc}</span>
							<span style="display:block; margin-bottom: 16px; " class="widgetopts-shortcode-example"><code>[do_sidebar name="${name}"]</code></span>
						</span>
          `);
  }

  function get_incremented_key(selectors) {
    let key = 0;
    selectors.each(function () {
      let _key = jQuery(this).data("key");
      key = Number(_key) > key ? Number(_key) : key;
    });
    return key + 1;
  }
  /* end custom sidebar events */

  jQuery(document).on("change", '.widget-opts-logic textarea[name="extended_widget_opts[class][logic]"], .widget-opts-logic textarea', function () {
    checkForDangerousPatterns(this);
  });

  jQuery(document).on("keyup", '.widget-opts-logic textarea[name="extended_widget_opts[class][logic]"], .widget-opts-logic textarea', function () {
    partialCheckForDangerousPatterns(this);
  });

  jQuery(document).on("click", ".widgetopts-tab-panel.tab-logic button", function () {
    checkForDangerousPatterns(jQuery('.widget-opts-logic textarea[name="extended_widget_opts[class][logic]"]'));
  });

  function checkForDangerousPatterns(that) {
    let expression = jQuery(that).val();
    jQuery.ajax({
      type: "POST",
      url: widgetopts10n.ajax_url,
      data: {
        action: "widgetopts_ajax_validate_expression",
        nonce: widgetopts10n.validate_expression_nonce,
        expression: expression,
      },
      dataType: "json",
      success: function (response) {
        if (response.valid) {
          if (jQuery(".wopts-warning-message").length !== 0) {
            jQuery(".wopts-warning-message").remove();
          }
        } else {
          if (jQuery(".wopts-warning-message").length === 0) {
            if (response.message != "") {
              jQuery(that).after(`<p class="wopts-warning-message" style="font-size: 11px;">Warning: <span style="color: red;">${response.message}</span></p>`);
            }
          }
        }
      },
      error: function () {},
    });
  }

  function partialCheckForDangerousPatterns(that) {
    const dangerousPatterns = [
      // Database-related keywords
      { pattern: /\b(insert|update|delete|replace|select|drop|alter|truncate|grant|revoke)\b/i, message: "Potential SQL injection detected." },

      // WordPress-specific database functions
      { pattern: /\b(wp_insert_post|wp_update_post|wp_delete_post|wp_insert_user|wp_update_user|wp_delete_user|add_option|update_option|delete_option|wpdb)\b/i, message: "Unsafe WordPress database functions found." },

      // PHP file manipulation functions
      { pattern: /\b(file_put_contents|file_get_contents|fopen|fwrite|unlink|rename|chmod|chown|chgrp|copy|scandir)\b/i, message: "File system manipulation functions are not allowed." },

      // External connections
      { pattern: /\b(wp_remote_get|wp_remote_post|curl_init|curl_exec|curl_setopt|open_basedir|fsockopen|proc_nice|stream_socket_server|stream_socket_client)\b/i, message: "Potential remote execution functions detected." },

      // Execution function
      { pattern: /\b(eval|assert|system|exec|shell_exec|passthru|proc_open|popen|pcntl_exec|dl|include|require|include_once|require_once)\b/i, message: "Execution functions are not allowed." },

      // Encoding/decoding functions
      { pattern: /\b(base64_decode|hex2bin|mb_decode_mimeheader|str_rot13)\b/i, message: "Encoding/decoding functions that may be used for obfuscation are not allowed." },

      // Dynamic function execution
      { pattern: /\b(call_user_func|call_user_func_array|create_function|compact|extract|parse_str|ReflectionClass|ReflectionMethod|ReflectionProperty)\b/i, message: "Dynamic function execution is not allowed." },

      // Remote execution functions
      { pattern: /\b(str_replace|str_ireplace|preg_replace|preg_replace_callback|preg_replace_callback_array)\b/i, message: "String replacement functions are restricted due to potential obfuscation." },

      //Dynamic PHP variable call
      { pattern: /\[\s*[\'"]?[a-zA-Z0-9_]+\s*\.\s*[\'"]?[a-zA-Z0-9_]+\s*\]/i, message: "Concatenated function execution is not allowed." },
      { pattern: /(?:\(\$[a-zA-Z_]\w*\)|\$[a-zA-Z_]\w*)(?=\s*\()/g, message: "Potential function name obfuscation detected." },
      { pattern: /\b(str_replace|preg_replace|preg_replace_callback|preg_replace_callback_array)\s*\(\s*[\'"]\s*\.\s*[\'"]/, message: "Potential function name obfuscation detected." },

      //Backtick
      { pattern: /`[^`]*`/, message: "Backticks execution is not allowed." },

      { pattern: /\\x(?:[0-9A-F]{2})+/i, message: "Hexadecimal escape sequences detected." },
      { pattern: /\\u(?:[0-9A-F]{4})+/i, message: "Unicode escape sequences detected." },
      { pattern: /\$\w+\s*\[\s*[\'"]?\d+[\'"]?\s*\]\s*\(/, message: "Dynamic function execution using arrays is not allowed." },
    ];

    let input = jQuery(that).val();
    let safe = true;

    for (const { pattern, message } of dangerousPatterns) {
      if (pattern.test(input)) {
        safe = false;
        if (jQuery(".wopts-warning-message").length === 0) {
          jQuery(that).after(`<p class="wopts-warning-message" style="font-size: 11px;">Warning: <span style="color: red;">${message}</span></p>`);
        }
        break;
      }
    }

    if (safe === true) {
      if (jQuery(".wopts-warning-message").length !== 0) {
        jQuery(".wopts-warning-message").remove();
      }
    }
  }
});
