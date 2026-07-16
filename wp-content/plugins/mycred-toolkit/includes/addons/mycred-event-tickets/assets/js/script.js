jQuery(document).ready(function ($) {

	// Trigger change on load to disable already selected options (RSVP)
	setTimeout(function () {
		$('select.mycred-et-rsvp-options').trigger('change');
		$('select.mycred-et-purchase-options').trigger('change');
	}, 500);

	$(document).on('change', 'select.mycred-et-rsvp-options', function () {
		mycred_et_enable_disable_options($(this), 'mycred-et-rsvp-options');
	});
	$(document).on('change', 'select.mycred-et-purchase-options', function () {
		mycred_et_enable_disable_options($(this), 'mycred-et-purchase-options');
	});

	function mycred_et_enable_disable_options($current_select, selectorClass) {
		var selected = $current_select.val();
		$('select.' + selectorClass).each(function () {
			var $sel = $(this);
			if ($sel.get(0) === $current_select.get(0)) {
				return true;
			}
			$sel.find('option').prop('disabled', false);
			if (selected && selected !== '0') {
				$sel.find('option[value="' + selected + '"]').prop('disabled', true);
			}
		});
	}

	// RSVP: Add More / Remove specific event row
	$(document).on('click', '.mycred-add-specific-et-rsvp-hook', function () {
		var parent_row = $(this).closest('.et_rsvp_specific_row');
		var clone = parent_row.clone();
		clone.find('input[type="text"]').val('');
		clone.find('select').val('0');
		parent_row.after(clone);
		$('select.mycred-et-rsvp-options').trigger('change');
	});
	$(document).on('click', '.mycred-remove-specific-et-rsvp-hook', function () {
		var container = $(this).closest('.hook-instance');
		if (container.find('.et_rsvp_specific_row').length > 1) {
			var dialog = confirm("Are you sure you want to remove this row?");
			if (dialog === true) {
				$(this).closest('.et_rsvp_specific_row').remove();
				$('select.mycred-et-rsvp-options').trigger('change');
			}
		}
	});

	// Purchase: Add More / Remove specific event row
	$(document).on('click', '.mycred-add-specific-et-purchase-hook', function () {
		var parent_row = $(this).closest('.et_purchase_specific_row');
		var clone = parent_row.clone();
		clone.find('input[type="text"]').val('');
		clone.find('select').val('0');
		parent_row.after(clone);
		$('select.mycred-et-purchase-options').trigger('change');
	});
	$(document).on('click', '.mycred-remove-specific-et-purchase-hook', function () {
		var container = $(this).closest('.hook-instance');
		if (container.find('.et_purchase_specific_row').length > 1) {
			var dialog = confirm("Are you sure you want to remove this row?");
			if (dialog === true) {
				$(this).closest('.et_purchase_specific_row').remove();
				$('select.mycred-et-purchase-options').trigger('change');
			}
		}
	});

});
