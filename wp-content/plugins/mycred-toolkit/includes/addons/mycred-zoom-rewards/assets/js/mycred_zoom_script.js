/**
 * Select Post Id - AJAX
 */

jQuery('body').on('click', '.btn-join-link-shortcode, .btn-join-link, .btn-start-link', function() {
	var post_id = jQuery('#post_id').val();
	var meeting_id = jQuery('#meeting_id').val();
	var data = {
		'action': 'myCred_za_save_entry',
		'post_id': post_id,
		'meeting_id': meeting_id,
	};
	jQuery.post(mycred_za_frontend_scripts_obj.ajax_url, data, function(response) { 
		console.log(response);
	}); 
	// wp ajax
});