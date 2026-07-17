

jQuery(document).on( 'change', '.mycred-frm-form-actions', function(e) {

    mycred_frm_hook_fields_display(jQuery(e.target).val(), jQuery(this));
    
});

jQuery(document).on( 'change', '.mycred-frm-quiz-actions', function(e) {

    mycred_frm_quiz_hook_fields_display(jQuery(e.target).val(), jQuery(this));
    
});

jQuery(document).on( 'change', '.mycred-frm-poll-actions', function(e) {

    mycred_frm_poll_hook_fields_display(jQuery(e.target).val(), jQuery(this));
    
});


jQuery(document).ready(function() {
    if (jQuery('.mycred-frm-form-actions').length) {
        jQuery('.mycred-frm-form-actions').each(function() {
            mycred_frm_hook_fields_display(jQuery(this).val(), jQuery(this));
        });
    }

    if (jQuery('.mycred-frm-quiz-actions').length) {
        jQuery('.mycred-frm-quiz-actions').each(function() {
            mycred_frm_quiz_hook_fields_display(jQuery(this).val(), jQuery(this));
        });
    }

    if (jQuery('.mycred-frm-poll-actions').length) {
        jQuery('.mycred-frm-poll-actions').each(function() {
            mycred_frm_poll_hook_fields_display(jQuery(this).val(), jQuery(this));
        });
    }
});



  	jQuery(document).on( 'click', '.mycred-frm-add-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input#mycred-pref-hooks-frm-form-submit-creds').val('1');
		hook.find('input#mycred-pref-hooks-frm-form-submit-limit').val('0');
		hook.find('select.mycred-frm-form-actions option:first').prop('selected', true);
        hook.find('select.mycred-frm-forms option:first').prop('selected', true);
        hook.find('input.frm-forms-fields-name').val('');
        hook.find('input.frm-forms-fields-val').val('');
        jQuery(this).closest('.widget-content').append( hook );
        jQuery(this).remove();
	}); 

    jQuery(document).on( 'click', '.mycred-frm-quiz-add-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input#mycred-pref-hooks-frm-quiz-submit-creds').val('1');
		hook.find('input#mycred-pref-hooks-frm-quiz-submit-limit').val('0');
		hook.find('select.mycred-frm-quiz-actions option:first').prop('selected', true);
        hook.find('select.mycred-frm-quizzes option:first').prop('selected', true);
        hook.find('input.frm-quiz-fields-name').val('');
        hook.find('input.frm-quiz-fields-val').val('');
        jQuery(this).closest('.widget-content').append( hook );
        jQuery(this).remove();
	}); 

    jQuery(document).on( 'click', '.mycred-frm-poll-add-hook', function() {
        var hook = jQuery(this).closest('.hook-instance').clone();
        hook.find('input#mycred-pref-hooks-frm-poll-submit-creds').val('1');
		hook.find('input#mycred-pref-hooks-frm-poll-submit-limit').val('0');
		hook.find('select.mycred-frm-poll-actions option:first').prop('selected', true);
        hook.find('select.mycred-frm-polls option:first').prop('selected', true);
        hook.find('input.frm-poll-fields-name').val('');
        hook.find('input.frm-poll-fields-val').val('');
        jQuery(this).closest('.widget-content').append( hook );
        jQuery(this).remove();
	}); 


    jQuery(document).on( 'click', '.mycred-frm-remove-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();

                var lastDiv = jQuery('.form-group.mycred-frm-specific-hook-actions').last();
                if (!lastDiv.find(':button.mycred-frm-add-hook').length) {
                    lastDiv.append('<button class="button button-small mycred-frm-add-hook" type="button">Add More</button>');
                }
            } 
        }
    }); 

    jQuery(document).on( 'click', '.mycred-frm-quiz-remove-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();

                var lastDiv = jQuery('.form-group.mycred-frm-specific-hook-actions').last();
                if (!lastDiv.find(':button.mycred-frm-quiz-add-hook').length) {
                    lastDiv.append('<button class="button button-small mycred-frm-quiz-add-hook" type="button">Add More</button>');
                }
            } 
        }
    }); 

    jQuery(document).on( 'click', '.mycred-frm-poll-remove-hook', function() {
        var container = jQuery(this).closest('.widget-content');
        if ( container.find('.hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.hook-instance').remove();

                var lastDiv = jQuery('.form-group.mycred-frm-specific-hook-actions').last();
                if (!lastDiv.find(':button.mycred-frm-poll-add-hook').length) {
                    lastDiv.append('<button class="button button-small mycred-frm-poll-add-hook" type="button">Add More</button>');
                }
            } 
        }
    }); 




  function mycred_frm_hook_fields_display(selected, jq_obj) {

    if (selected == 'frm_submit_frm') {
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms').hide();
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms-fields').hide();
    } else if (selected == 'frm_submit_spec_frm') {
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms').show();
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms-fields').hide();
    } else if (selected == 'frm_submit_spec_field_frm') {
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms').hide();
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms-fields').show();
    } else if (selected == 'frm_submit_spec_field_spec_frm') {
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms').show();
        jq_obj.closest('.mycred-frm-form-fields').find('.frm-forms-fields').show();
    } 
  }

  function mycred_frm_quiz_hook_fields_display(selected, jq_obj) {

    if (selected == 'frm_submit_quiz' || selected == 'frm_pass_a_quiz' || selected == 'frm_fail_a_quiz' ) {
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quizzes').hide();
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quiz-fields').hide();
    } else if (selected == 'frm_submit_spec_quiz' || selected == 'pass_a_specific_quiz' || selected == 'fail_a_specific_quiz') {
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quizzes').show();
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quiz-fields').hide();
    } else if (selected == 'frm_submit_spec_field_quiz') {
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quizzes').hide();
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quiz-fields').show();
    } else if (selected == 'frm_submit_spec_field_spec_quiz') {
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quizzes').show();
        jq_obj.closest('.mycred-frm-quiz-fields').find('.frm-quiz-fields').show();
    } 
  }


  function mycred_frm_poll_hook_fields_display(selected, jq_obj) {

    if (selected == 'frm_vote_on_poll') {
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-polls').hide();
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-poll-fields').hide();
    } else if (selected == 'frm_vote_on_spec_poll') {
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-polls').show();
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-poll-fields').hide();
    } else if (selected == 'frm_submit_spec_field_poll') {
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-polls').hide();
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-poll-fields').show();
    } else if (selected == 'frm_submit_spec_field_spec_poll') {
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-polls').show();
        jq_obj.closest('.mycred-frm-poll-fields').find('.frm-poll-fields').show();
    } 
  }