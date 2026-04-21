/**
 * Customer Email Verification - User Admin JavaScript
 *
 * Handles user verification actions in the WordPress admin area including
 * verify, unverify, and resend email functionality.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

(function( $ ) {
	'use strict';

	/**
	 * Response code constants for standardized AJAX responses.
	 *
	 * @type {Object}
	 */
	const RESPONSE_CODES = {
		USER_VERIFIED: 'user_verified',
		USER_UNVERIFIED: 'user_unverified',
	};

	/**
	 * AJAX Response Handler
	 *
	 * Provides standardized methods for handling AJAX responses.
	 */
	const AjaxResponseHandler = {
		/**
		 * Extract error message from response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @param {string} defaultMessage - Default error message if none found.
		 * @return {string} Error message.
		 */
		getErrorMessage: function(response, defaultMessage) {
			defaultMessage = defaultMessage || 'An error occurred. Please try again.';
			
			if (response && response.error && response.error.message) {
				return response.error.message;
			}
			
			return defaultMessage;
		},

		/**
		 * Check if response indicates success.
		 *
		 * @param {Object} response - AJAX response object.
		 * @return {boolean} True if success.
		 */
		isSuccess: function(response) {
			return response && response.success === true;
		},

		/**
		 * Get response code from response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @return {string|null} Response code or null.
		 */
		getCode: function(response) {
			return (response && response.code) ? response.code : null;
		},

		/**
		 * Get data from response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @return {Object} Response data object.
		 */
		getData: function(response) {
			return (response && response.data) ? response.data : {};
		},
	};

	/**
	 * UI Helper for user admin actions
	 */
	const UIHelper = {
		/**
		 * Block element with loading overlay.
		 *
		 * @param {jQuery} $element - Element to block.
		 */
		blockElement: function($element) {
			$element.block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/**
		 * Unblock element.
		 *
		 * @param {jQuery} $element - Element to unblock.
		 */
		unblockElement: function($element) {
			$element.unblock();
		},

		/**
		 * Show error alert.
		 *
		 * @param {string} message - Error message to display.
		 */
		showErrorAlert: function(message) {
			alert(message);
		},
	};

	/**
	 * User Action Handlers
	 */
	const UserActionHandlers = {
		/**
		 * Handle unverify user action response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @param {jQuery} $button - Button element that triggered the action.
		 */
		handleUnverifyResponse: function(response, $button) {
			if (!AjaxResponseHandler.isSuccess(response)) {
				UIHelper.unblockElement($button.parent('td'));
				const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Failed to unverify user.');
				UIHelper.showErrorAlert(errorMessage);
				return;
			}

			UIHelper.unblockElement($button.parent('td'));
			$button.hide();
			const $td = $button.parent('td');
			$td.find('.cev_dashicons_icon_verify_user').show();
			$td.prev().find('.cev_verified_admin_user_action').hide();
			$td.prev().find('.cev_unverified_admin_user_action').show();
			
			$('.cev_verified_admin_user_action_single').hide();
			$('.cev_unverified_admin_user_action_single').show();
		},

		/**
		 * Handle verify user action response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @param {jQuery} $button - Button element that triggered the action.
		 */
		handleVerifyResponse: function(response, $button) {
			if (!AjaxResponseHandler.isSuccess(response)) {
				UIHelper.unblockElement($button.parent('td'));
				const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Failed to verify user.');
				UIHelper.showErrorAlert(errorMessage);
				return;
			}

			UIHelper.unblockElement($button.parent('td'));
			$button.hide();
			const $td = $button.parent('td');
			$td.find('.cev_dashicons_icon_unverify_user').show();
			$td.prev().find('.cev_verified_admin_user_action').show();
			$td.prev().find('.cev_unverified_admin_user_action').hide();
			$('.cev_verified_admin_user_action_single').show();
			$('.cev_unverified_admin_user_action_single').hide();
		},

	};

	/**
	 * Handle unverify user action
	 */
	$(document).on('click', '.cev_dashicons_icon_unverify_user', function(e) {
		e.preventDefault();
		
		const $button = $(this);
		const userId = $button.attr('id');
		const $td = $button.parent('td');
		
		UIHelper.blockElement($td);
		
		const ajaxData = {
			action: 'cev_manualy_user_verify_in_user_menu',
			id: userId,
			wp_nonce: $button.attr('wp_nonce'),
			actin_type: 'unverify_user',
		};

		$.ajax({
			url: ajaxurl,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				UserActionHandlers.handleUnverifyResponse(response, $button);
			},
			error: function(xhr, status, error) {
				UIHelper.unblockElement($td);
				const errorMessage = AjaxResponseHandler.getErrorMessage(
					xhr.responseJSON,
					'An error occurred. Please try again.'
				);
				UIHelper.showErrorAlert(errorMessage);
			}
		});
	});	

	/**
	 * Handle verify user action
	 */
	$(document).on('click', '.cev_dashicons_icon_verify_user', function(e) {
		e.preventDefault();
		
		const $button = $(this);
		const userId = $button.attr('id');
		const $td = $button.parent('td');
		
		UIHelper.blockElement($td);
		
		const ajaxData = {
			action: 'cev_manualy_user_verify_in_user_menu',
			id: userId,
			wp_nonce: $button.attr('wp_nonce'),
			actin_type: 'verify_user',
		};
		
		$.ajax({
			url: ajaxurl,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				UserActionHandlers.handleVerifyResponse(response, $button);
			},
			error: function(xhr, status, error) {
				UIHelper.unblockElement($td);
				const errorMessage = AjaxResponseHandler.getErrorMessage(
					xhr.responseJSON,
					'An error occurred. Please try again.'
				);
				UIHelper.showErrorAlert(errorMessage);
			}
		});
	});	
})(jQuery);	