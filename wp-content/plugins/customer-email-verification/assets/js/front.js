/**
 * Customer Email Verification - Frontend JavaScript
 *
 * Handles OTP input, form submissions, email verification, and AJAX interactions
 * for the Customer Email Verification plugin.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Response code constants for standardized AJAX responses.
	 *
	 * @type {Object}
	 */
	const RESPONSE_CODES = {
		EMAIL_SENT: 'email_sent',
		ALREADY_VERIFIED: 'already_verified',
		VERIFIED: 'verified',
		LOGIN_USER: 'login_user',
		SESSION_VERIFIED: 'session_verified',
		ALREADY_SENT: 'already_sent',
		LIMIT_REACHED: 'limit_reached',
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
			
			if (response && response.error && typeof response.error.message === 'string' && response.error.message.length > 0) {
				return response.error.message;
			}
			
			return defaultMessage;
		},

		/**
		 * Extract success message from response.
		 *
		 * @param {Object} response - AJAX response object.
		 * @param {string} defaultMessage - Default success message if none found.
		 * @return {string} Success message.
		 */
		getSuccessMessage: function(response, defaultMessage) {
			defaultMessage = defaultMessage || '';

			if (response && typeof response.message === 'string' && response.message.length > 0) {
				return response.message;
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

		/**
		 * Check if response has specific code.
		 *
		 * @param {Object} response - AJAX response object.
		 * @param {string} code - Code to check.
		 * @return {boolean} True if code matches.
		 */
		hasCode: function(response, code) {
			return this.getCode(response) === code;
		},
	};

	/**
	 * UI Update Helper
	 *
	 * Provides methods for updating UI elements consistently.
	 */
	const UIHelper = {
		/**
		 * Update email display in verification section.
		 *
		 * @param {string} emailAddress - Email address to display.
		 */
		updateEmailDisplay: function(emailAddress) {
			if (!emailAddress) {
				return;
			}

			// Update email in the span with class cev-customer-email-display.
			// Try multiple selectors to find the email display element.
			const $emailSpan = $('.cev-customer-email-display');
			if ($emailSpan.length) {
				$emailSpan.html(emailAddress);
				return;
			}

			// Fallback: try to replace {customer_email} in the entire message text.
			let $description = $('.cev-show-reg-content .cev-authorization__description');
			if (!$description.length) {
				// Try alternative selector for checkout popup.
				$description = $('.cev-authorization__description');
			}

			if ($description.length && emailAddress) {
				let messageText = $description.html();
				// Replace both {customer_email} and {{customer_email}} placeholders.
				messageText = messageText.replace(/\{\{customer_email\}\}/g, '<strong>' + emailAddress + '</strong>');
				messageText = messageText.replace(/\{customer_email\}/g, '<strong>' + emailAddress + '</strong>');
				// Also update any existing email span.
				messageText = messageText.replace(/<span class="cev-customer-email-display">[^<]*<\/span>/g, '<span class="cev-customer-email-display">' + emailAddress + '</span>');
				$description.html(messageText);
			}
		},

		/**
		 * Show verification code input section.
		 */
		showVerificationSection: function() {
			$('.otp-container').show();
			$('.cev-authorization__heading').hide();
			$('.cev-show-reg-content').show();

			// Hide all footers while the resend timer is running. The timer
			// itself is shown via the .cev_resend_timer span, and once the
			// countdown completes, startResendTimer() will reveal the
			// .cev_resend_link_front footer (with the "Try again" link).
			$('.cev-authorization__footer.cev_send_email_span_tag').hide();
			$('.cev-authorization__footer.cev-rge-content').hide();

			$('.cev-hide-success-message').hide();
		},

		/**
		 * Disable resend button and show limit message.
		 *
		 * @param {string} message - Limit message to display.
		 */
		handleResendLimit: function(message) {
			$('.resend_verification_code_guest_user').css('pointer-events', 'none');
			$('.cev-send-verification-code').css({
				'pointer-events': 'none',
				'cursor': 'not-allowed',
				'background-color': '#bdbdbd'
			});
			
			const $limitMessage = $('.cev_limit_message');
			if ($limitMessage.length) {
				if (message) {
					$limitMessage.text(message).show();
				} else {
					$limitMessage.show();
				}
			}
		},

		/**
		 * Show error message in alert.
		 *
		 * @param {string} message - Error message to display.
		 */
		showErrorAlert: function(message) {
			alert(message);
		},
	};

	/**
	 * OTP Input Handler
	 *
	 * Creates a reusable OTP input handler for different input selectors.
	 *
	 * @param {string} inputSelector - CSS selector for OTP input fields
	 * @param {string} submitButtonId - ID of the submit button to trigger
	 */
	function initOtpInputHandler(inputSelector, submitButtonId) {
		var $inputs = $(inputSelector);

		if (!$inputs.length) {
			return;
		}

		/**
		 * Handle manual typing in OTP inputs
		 */
		$inputs.on('input', function() {
			var $input = $(this);
			var value = $input.val();
			var index = $inputs.index(this);

			if (value.length === 1) {
				if (index < $inputs.length - 1) {
					$inputs.eq(index + 1).focus();
				} else {
					$('#' + submitButtonId).trigger('click');
				}
			}
		});

		/**
		 * Handle paste event for OTP inputs
		 */
		$inputs.on('paste', function(e) {
			e.preventDefault();
			var pasteData = (e.originalEvent || e).clipboardData.getData('text');
			var chars = pasteData.replace(/\D/g, '').split(''); // Keep only digits

			$inputs.each(function(i) {
				$(this).val(chars[i] || '');
			});

			if (chars.length >= $inputs.length) {
				$('#' + submitButtonId).trigger('click');
			} else {
				$inputs.eq(chars.length).focus();
			}
		});

		/**
		 * Handle backspace key for navigation between inputs
		 */
		$inputs.on('keydown', function(e) {
			var $input = $(this);
			var index = $inputs.index(this);

			// Backspace key code is 8
			if (e.keyCode === 8 && $input.val().length === 0) {
				if (index > 0) {
					$inputs.eq(index - 1).focus();
				}
			}
		});

		/**
		 * Restrict input to digits only
		 */
		$inputs.on('keypress', function(e) {
			var charCode = (e.which) ? e.which : e.keyCode;
			// Only allow digits (48-57 are ASCII codes for 0-9)
			if (charCode < 48 || charCode > 57) {
				e.preventDefault();
			}
		});
	}

	/**
	 * Show error styling on element
	 *
	 * @param {jQuery} $element - jQuery element to style
	 */
	function showError($element) {
		$element.css('border-color', 'red');
	}

	/**
	 * Hide error styling on element
	 *
	 * @param {jQuery} $element - jQuery element to style
	 */
	function hideError($element) {
		$element.css('border-color', '');
	}

	/**
	 * Handle checkout send verification code response.
	 *
	 * @param {Object} response - AJAX response object.
	 * @param {string} emailAddress - Email address used in request.
	 */
	function handleCheckoutSendCodeResponse(response, emailAddress) {
		if (!AjaxResponseHandler.isSuccess(response)) {
			handleCheckoutSendCodeError(response);
			return;
		}

		// Handle already verified case.
		if (AjaxResponseHandler.hasCode(response, RESPONSE_CODES.ALREADY_VERIFIED)) {
			location.reload();
			return;
		}

		// Show verification section.
		const responseData = AjaxResponseHandler.getData(response);
		const displayEmail = (responseData.emailAddress && typeof responseData.emailAddress === 'string')
			? responseData.emailAddress
			: emailAddress;
		
		// Show section first, then update email to ensure elements are visible.
		UIHelper.showVerificationSection();
		// Use setTimeout to ensure DOM is updated before updating email.
		setTimeout(function() {
			UIHelper.updateEmailDisplay(displayEmail);
		}, 0);

		// Handle resend limit.
		if (responseData.limitReached === true) {
			UIHelper.handleResendLimit();
		}

		startResendTimer();
	}

	/**
	 * Handle checkout send verification code error response.
	 *
	 * @param {Object} response - AJAX response object.
	 */
	function handleCheckoutSendCodeError(response) {
		const errorMessage = AjaxResponseHandler.getErrorMessage(response);
		const responseData = AjaxResponseHandler.getData(response);
		const errorCode = (response && response.error) ? response.error.code : null;

		if (errorCode === RESPONSE_CODES.LIMIT_REACHED) {
			UIHelper.handleResendLimit(errorMessage);
		} else {
			UIHelper.showErrorAlert(errorMessage);
		}
	}

	/**
	 * Handle checkout verify code response.
	 *
	 * @param {Object} response - AJAX response object.
	 */
	function handleCheckoutVerifyCodeResponse(response) {
		if (AjaxResponseHandler.isSuccess(response) && 
			AjaxResponseHandler.hasCode(response, RESPONSE_CODES.VERIFIED)) {
			location.reload();
		} else {
			const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Invalid verification code.');
			$('.error_mesg').text(errorMessage).show();
		}
	}

	/**
	 * Handle custom email verification response.
	 *
	 * @param {Object} response - AJAX response object.
	 * @param {string} emailAddress - Email address used in request.
	 * @param {Object} context - Context object with lastVerifiedEmail and isProcessing.
	 */
	function handleCustomEmailVerificationResponse(response, emailAddress, context) {
		if (!AjaxResponseHandler.isSuccess(response)) {
			handleCustomEmailVerificationError(response, context);
			return;
		}

		const responseCode = AjaxResponseHandler.getCode(response);
		
		switch (responseCode) {
			case RESPONSE_CODES.EMAIL_SENT:
				localStorage.setItem('performActions', 'true');
				localStorage.setItem('cev_pin_email', emailAddress);
				location.reload();
				break;
				
			case RESPONSE_CODES.ALREADY_VERIFIED:
				// Update last verified email in context.
				if (context && typeof context.lastVerifiedEmail !== 'undefined') {
					context.lastVerifiedEmail = emailAddress;
				}
				location.reload();
				break;
				
			case RESPONSE_CODES.LOGIN_USER:
				// User is logged in, no action needed.
				if (context && typeof context.isProcessing !== 'undefined') {
					context.isProcessing = false;
				}
				break;
				
			default:
				// Unknown success code, log for debugging.
				console.warn('Unknown response code:', responseCode);
				break;
		}
	}

	/**
	 * Handle custom email verification error response.
	 *
	 * @param {Object} response - AJAX response object.
	 * @param {Object} context - Context object with isProcessing.
	 */
	function handleCustomEmailVerificationError(response, context) {
		const errorCode = (response && response.error) ? response.error.code : null;
		const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Verification failed.');

		if (errorCode === RESPONSE_CODES.LIMIT_REACHED) {
			UIHelper.handleResendLimit(errorMessage);
		}
		
		// Update processing flag in context.
		if (context && typeof context.isProcessing !== 'undefined') {
			context.isProcessing = false;
		}
	}

	/**
	 * Validate email address format
	 *
	 * @param {string} email - Email address to validate
	 * @return {boolean} True if email is valid, false otherwise
	 */
	function validateEmail(email) {
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		return regex.test(email);
	}

	/**
	 * Block UI element with loading overlay
	 *
	 * @param {jQuery|string} selector - Element to block
	 */
	function blockElement(selector) {
		$(selector).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	}

	/**
	 * Unblock UI element
	 *
	 * @param {jQuery|string} selector - Element to unblock
	 */
	function unblockElement(selector) {
		$(selector).unblock();
	}

	// ============================================================================
	// OTP INPUT HANDLERS
	// ============================================================================

	/**
	 * Initialize OTP input handlers for different contexts
	 */
	$(document).ready(function() {
		// OTP input for email verification
		initOtpInputHandler('.otp-input', 'pin_verification_button');

		// OTP input for login authentication
		initOtpInputHandler('.otp-input-login', 'loginSubmitPinButton');

		// OTP input for checkout verification
		initOtpInputHandler('.otp-input', 'cev_submit_pin_button');
	});

	// ============================================================================
	// LOGIN AUTHENTICATION WITH OTP
	// ============================================================================

	/**
	 * Initialize login authentication popup
	 */
	$(document).ready(function() {
		// Start resend timer when login auth popup is shown
		if ($('.cev_login_authentication_form').length > 0) {
			startResendTimer();
		}

		// Handle resend OTP for login authentication via AJAX
		$(document).on('click', '.cev_resend_link_front', function(e) {
			e.preventDefault();

			// Only handle on the login authentication page.
			// Checkout guest resend is handled by .resend_verification_code_guest_user handler.
			if (!$('.cev_login_authentication_form').length) {
				return;
			}

			var $resendLink = $(this);

			if ($resendLink.hasClass('disabled')) {
				return false;
			}

			// Check if AJAX object and nonce are available
			if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object || !cev_ajax_object.cev_resend_login_otp_nonce) {
				console.error('AJAX error: cev_ajax_object or cev_resend_login_otp_nonce is not defined.');
				alert('An error occurred. Please refresh the page and try again.');
				return false;
			}

			// Disable the resend link
			$resendLink.addClass('disabled').css('pointer-events', 'none');

			// Show loading state
			var originalText = $resendLink.text();
			$resendLink.text('Sending...');

			$.ajax({
				url: cev_ajax_object.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'cev_resend_login_otp',
					cev_resend_login_otp: cev_ajax_object.cev_resend_login_otp_nonce
				},
				success: function(response) {
					if (AjaxResponseHandler.isSuccess(response)) {
						// Restore button text
						$resendLink.text(originalText);
						
						// Show success message
						var successMessage = AjaxResponseHandler.getSuccessMessage(response, 'A new login OTP email has been sent.');
						
						// Hide error message if visible
						$('.cev-pin-verification__failure').hide();
						
						// Show success notification
						var $successContainer = $('.cev-pin-verification__success');
						if ($successContainer.length) {
							$successContainer
								.find('.js-pincode-success-message')
								.text(successMessage);
							$successContainer.show();
							
							// Hide success message after 5 seconds
							setTimeout(function() {
								$successContainer.fadeOut();
							}, 5000);
						}

						// Restart the timer
						startResendTimer();
					} else {
						// Hide success message if visible
						$('.cev-pin-verification__success').hide();
						
						// Show error message
						var errorMessage = AjaxResponseHandler.getErrorMessage(
							response,
							'Failed to resend OTP. Please try again.'
						);
						
						if ($('.cev-pin-verification__failure').length) {
							$('.cev-pin-verification__failure')
								.find('.js-pincode-error-message')
								.text(errorMessage)
								.parent()
								.parent()
								.show();
						}

						// Check if it's a resend limit error - if so, disable button permanently
						var errorCode = response && response.error && response.error.code ? response.error.code : '';
						if (errorCode === 'resend_limit_reached') {
							// Disable resend button permanently
							$resendLink
								.addClass('disabled')
								.css('pointer-events', 'none')
								.css('opacity', '0.5')
								.text(originalText);
						} else {
							// Re-enable the resend link for other errors
							$resendLink.removeClass('disabled').css('pointer-events', 'auto').css('opacity', '1').text(originalText);
						}
					}
				},
				error: function(jqXHR, exception) {
					console.error('AJAX error:', jqXHR.status, exception);
					
					// Hide success message if visible
					$('.cev-pin-verification__success').hide();
					
					var errorMessage = (jqXHR.responseJSON && AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON))
						? AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON, 'An error occurred. Please try again.')
						: 'An error occurred. Please try again.';
					
					if ($('.cev-pin-verification__failure').length) {
						$('.cev-pin-verification__failure')
							.find('.js-pincode-error-message')
							.text(errorMessage)
							.parent()
							.parent()
							.show();
					}

					// Re-enable the resend link
					$resendLink.removeClass('disabled').css('pointer-events', 'auto').text(originalText);
				}
			});

			return false;
		});
	});

	/**
	 * Handle login authentication with OTP
	 */
	$(document).on('click', '#loginSubmitPinButton', function(e) {
		e.preventDefault();

		var error = false;
		var cevPin = '';

		// Collect OTP input values
		$('.otp-input-login').each(function() {
			cevPin += $(this).val();
		});

		// Validate OTP length
		if (cevPin.length < 4) {
			$('.required-filed').html('<span class="cev-error-display">4-digits code*</span>');
			showError($('.otp-input-login').first());
			
			// Hide success message if visible
			$('.cev-pin-verification__success').hide();
			
			// Show error message in popup
			var errorMsg = 'Please enter the complete ' + cevPin.length + '-digit code.';
			if ($('.cev-pin-verification__failure').length) {
				$('.cev-pin-verification__failure')
					.find('.js-pincode-error-message')
					.text(errorMsg)
					.parent()
					.parent()
					.show();
			}
			
			error = true;
		} else {
			hideError($('.otp-input-login').first());
			// Hide error message when valid input is entered
			$('.cev-pin-verification__failure').hide();
		}

		if (error === true) {
			return false;
		}

		blockElement('.cev_login_authentication_form');

		$.ajax({
			url: cev_ajax_object.ajax_url,
			data: {
				action: 'cev_login_auth_with_otp',
				cev_login_otp: cevPin,
				cev_login_auth_with_otp: cev_ajax_object.cev_login_auth_with_otp_nonce
			},
			type: 'POST',
			dataType: 'json',
			success: function(response) {
				if (AjaxResponseHandler.isSuccess(response)) {
					// Hide any error messages
					$('.cev-pin-verification__failure').hide();
					
					// Show success message
					const successMessage = AjaxResponseHandler.getSuccessMessage(response, 'OTP verified successfully!');
					if ($('.cev-pin-verification__success').length) {
						$('.cev-pin-verification__success')
							.find('.js-pincode-success-message')
							.text(successMessage)
							.parent()
							.parent()
							.show();
					}
					
					// OTP verified successfully - redirect to URL from response data
					const responseData = AjaxResponseHandler.getData(response);
					if (responseData && responseData.url) {
						setTimeout(function() {
						window.location.href = responseData.url;
						}, 1000);
					} else {
						// Fallback: reload page if no URL provided
						setTimeout(function() {
						window.location.reload();
						}, 1000);
					}
				} else {
					// Hide success message if visible
					$('.cev-pin-verification__success').hide();
					
					// Show error message in popup
					const errorMessage = AjaxResponseHandler.getErrorMessage(
						response,
						'Invalid OTP code. Please try again.'
					);
					
					if ($('.cev-pin-verification__failure').length) {
						$('.cev-pin-verification__failure')
							.find('.js-pincode-error-message')
							.text(errorMessage)
							.parent()
							.parent()
							.show();
					}
					
					// Also show in error_mesg if it exists (for backward compatibility)
					$('.error_mesg').html('<span class="cev-error-display">' + errorMessage + '</span>').show();
				}
				unblockElement('.cev_login_authentication_form');
			},
			error: function(jqXHR, exception) {
				console.error('AJAX error:', jqXHR.status, exception);
				
				// Hide success message if visible
				$('.cev-pin-verification__success').hide();
				
				const errorMessage = (jqXHR.responseJSON && AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON))
					? AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON, 'An error occurred. Please try again.')
					: 'An error occurred. Please try again.';
				
				// Show error message in popup
				if ($('.cev-pin-verification__failure').length) {
					$('.cev-pin-verification__failure')
						.find('.js-pincode-error-message')
						.text(errorMessage)
						.parent()
						.parent()
						.show();
				}
				
				// Also show in error_mesg if it exists (for backward compatibility)
				$('.error_mesg').html('<span class="cev-error-display">' + errorMessage + '</span>').show();
				unblockElement('.cev_login_authentication_form');
			}
		});

		return false;
	});

	// ============================================================================
	// CHECKOUT VERIFICATION - SEND CODE
	// ============================================================================

	/**
	 * Handle send verification code on checkout page
	 */
	$(document).on('click', '.cev-send-verification-code', function(e) {
		e.preventDefault();

		var error = false;
		var $cevPinEmail = $('#cev_pin_email');
		var email = $cevPinEmail.val().trim();

		// Validate email is not empty
		if (email === '') {
			showError($cevPinEmail);
			error = true;
		} else {
			hideError($cevPinEmail);
		}

		// Validate email format
		if (!validateEmail(email)) {
			showError($cevPinEmail);
			error = true;
		} else {
			hideError($cevPinEmail);
		}

		if (error === true) {
			return false;
		}

		blockElement('.cev-authorization');

		var ajaxData = {
			action: 'checkout_page_send_verification_code',
			email: email,
			wp_nonce: $(this).attr('wp_nonce')
		};

		$.ajax({
			url: cev_ajax_object.ajax_url,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				handleCheckoutSendCodeResponse(response, email);
				unblockElement('.cev-authorization');
			},
			error: function(jqXHR, exception) {
				console.error('AJAX error:', jqXHR.status, exception);
				const errorMessage = AjaxResponseHandler.getErrorMessage(
					jqXHR.responseJSON,
					'An error occurred. Please try again.'
				);
				UIHelper.showErrorAlert(errorMessage);
				unblockElement('.cev-authorization');
			}
		});

		return false;
	});

	// ============================================================================
	// CHECKOUT VERIFICATION - VERIFY CODE
	// ============================================================================

	/**
	 * Handle verify code on checkout page
	 */
	$(document).on('click', '#cev_submit_pin_button', function(e) {
		e.preventDefault();

		var $cevPinEmail = $('#cev_pin_email');
		var otp = '';

		// Collect OTP input values
		$('.otp-input').each(function() {
			otp += $(this).val();
		});

		blockElement('.cev_pin_verification_form_pro');

		var ajaxData = {
			action: 'checkout_page_verify_code',
			email: $cevPinEmail.val(),
			pin: otp,
			security: cev_ajax_object.checkout_verify_code
		};

		$.ajax({
			url: cev_ajax_object.ajax_url,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				handleCheckoutVerifyCodeResponse(response);
				unblockElement('.cev_pin_verification_form_pro');
			},
			error: function(jqXHR, exception) {
				console.error('AJAX error:', jqXHR.status, exception);
				const errorMessage = AjaxResponseHandler.getErrorMessage(
					jqXHR.responseJSON,
					'An error occurred. Please try again.'
				);
				$('.error_mesg').text(errorMessage).show();
				unblockElement('.cev_pin_verification_form_pro');
			}
		});

		return false;
	});

	// ============================================================================
	// MY ACCOUNT VERIFICATION
	// ============================================================================

	/**
	 * Handle verify email on edit account page
	 */
	$(document).on('click', '.verify_account_email_my_account', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var $button = $(this);
		
		// Check if AJAX object is available
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object || !cev_ajax_object.ajax_url) {
			console.error('CEV: cev_ajax_object is not defined or missing ajax_url');
			alert('An error occurred. Please refresh the page and try again.');
			return false;
		}
		
		// Prevent duplicate calls - disable button if already processing
		if ($button.prop('disabled') || $button.hasClass('processing')) {
			return false;
		}

		var error = false;
		var $myAccountEmailVerification = $('#my_account_email_verification');
		var pin = $myAccountEmailVerification.val().trim();

		if (pin === '') {
			showError($myAccountEmailVerification);
			error = true;
			$('.cev-pin-verification__failure_code').hide();
			return false;
		} else {
			hideError($myAccountEmailVerification);

			// Disable button and mark as processing
			$button.prop('disabled', true).addClass('processing');
			blockElement('.woocommerce-MyAccount-content');

			var ajaxData = {
				action: 'verify_email_on_edit_account',
				pin: pin,
				wp_nonce: $button.attr('wp_nonce')
			};

			$.ajax({
				url: cev_ajax_object.ajax_url,
				type: 'post',
				dataType: 'json',
				data: ajaxData,
				success: function(response) {
					if (AjaxResponseHandler.isSuccess(response)) {
						// Show success message if available
						const responseData = AjaxResponseHandler.getData(response);
						const message = response.message || responseData.message || 'Email verified successfully.';
						if (message) {
							$('.cev-pin-verification__failure_code')
								.removeClass('cev-error-display')
								.addClass('cev-success-display')
								.text(message)
								.show()
								.css('color', 'green');
						}
						// Reload page after short delay to show message
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						// Handle error response
						const errorCode = AjaxResponseHandler.getCode(response);
						const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Verification failed.');
						
						$('.cev-pin-verification__failure_code')
							.removeClass('cev-success-display')
							.addClass('cev-error-display')
							.text(errorMessage)
							.show()
							.css('color', 'red');
					}
					
					// Re-enable button
					$button.prop('disabled', false).removeClass('processing');
					unblockElement('.woocommerce-MyAccount-content');
				},
				error: function(jqXHR, exception) {
					console.error('AJAX error:', jqXHR.status, exception);
					const errorMessage = (jqXHR.responseJSON && AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON))
						? AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON, 'An error occurred. Please try again.')
						: 'An error occurred. Please try again.';
					
					$('.cev-pin-verification__failure_code')
						.removeClass('cev-success-display')
						.addClass('cev-error-display')
						.text(errorMessage)
						.show()
						.css('color', 'red');
					
					// Re-enable button
					$button.prop('disabled', false).removeClass('processing');
					unblockElement('.woocommerce-MyAccount-content');
				}
			});
		}

		return false;
	});

	/**
	 * Handle resend verification code on edit account page
	 */
	$(document).on('click', '.resend_verification_code_my_account', function(e) {
		e.preventDefault();

		var $link = $(this);
		
		// Prevent duplicate calls - disable link if already processing or limit reached
		if ($link.hasClass('processing') || $link.hasClass('cev_disibale_values')) {
			return false;
		}

		// Disable link and mark as processing
		$link.addClass('processing');
		blockElement('.woocommerce-MyAccount-content');

		var ajaxData = {
			action: 'resend_verification_code_on_edit_account',
			wp_nonce: $('.verify_account_email_my_account').attr('wp_nonce')
		};

		$.ajax({
			url: cev_ajax_object.ajax_url,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				if (AjaxResponseHandler.isSuccess(response)) {
					const responseData = AjaxResponseHandler.getData(response);
					
					// Check if limit reached after this resend
					if (responseData.resendLimitReached === true) {
						$link.addClass('cev_disibale_values').css({
							'pointer-events': 'none',
							'cursor': 'not-allowed',
							'opacity': '0.5'
						});
					}
					
					// Show success indicator
					$('.show_cev_resend_yes').show();
					
					// Show success message if available
					if (response.message) {
						// You can add a success message display here if needed
						console.log('Resend success:', response.message);
					}
				} else {
					// Handle error response
					const errorCode = AjaxResponseHandler.getCode(response);
					const errorMessage = AjaxResponseHandler.getErrorMessage(response, 'Failed to resend verification code.');
					
					// Handle limit_reached error specifically
					if (errorCode === RESPONSE_CODES.LIMIT_REACHED) {
						$link.addClass('cev_disibale_values').css({
							'pointer-events': 'none',
							'cursor': 'not-allowed',
							'opacity': '0.5'
						});
					}
					
					// Show error message
					console.error('Resend error:', errorMessage);
				}
				
				// Re-enable link
				$link.removeClass('processing');
				unblockElement('.woocommerce-MyAccount-content');
				startResendTimer();
			},
			error: function(jqXHR, exception) {
				console.error('AJAX error:', jqXHR.status, exception);
				const errorMessage = (jqXHR.responseJSON && AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON))
					? AjaxResponseHandler.getErrorMessage(jqXHR.responseJSON, 'An error occurred. Please try again.')
					: 'An error occurred. Please try again.';
				console.error('Resend error:', errorMessage);
				
				// Re-enable link
				$link.removeClass('processing');
				unblockElement('.woocommerce-MyAccount-content');
			}
		});

		return false;
	});

	// ============================================================================
	// GUEST USER VERIFICATION
	// ============================================================================

	/**
	 * Handle "Already have verification code?" link click
	 */
	$(document).on('click', '.cev_already_verify', function(e) {
		e.preventDefault();

		$('.otp-container').show();
		$('.cev-authorization__heading').hide();
		$('.cev-show-reg-content').show();
		$('.cev-authorization__footer').hide();
		$('.cev-rge-content').show();
		$('.cev-hide-success-message').hide();
	});

	/**
	 * Handle back button click in checkout verification popup.
	 *
	 * This is scoped to the checkout verification form so it does not
	 * interfere with other popups that also use the `.back_btn` class.
	 * It resets the popup to the initial email input state and clears
	 * all input values and messages.
	 */
	$(document).on('click', '.cev_pin_verification_form_pro .back_btn', function(e) {
		e.preventDefault();

		var $form = $(this).closest('.cev_pin_verification_form_pro');

		if (!$form.length) {
			return;
		}

		// Hide OTP / confirmation sections.
		$form.find('.cev-show-reg-content').hide();
		$form.find('.otp-container').hide();
		$form.find('.error_mesg').hide().text('');

		// Show the initial email input section.
		$form.find('.cev-authorization__heading').not('.cev-show-reg-content').show();
		$form.find('.cev-hide-success-message').show();
		$form.find('.cev-authorization__footer.cev_send_email_span_tag').show();
		$form.find('.cev-authorization__footer.cev-rge-content').hide();

		// Clear all inputs (email + OTP fields) within the form.
		$form.find('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea')
			.val('');

		// Hide and clear any additional limit / success / timer messages.
		$form.find('.cev_limit_message, .cev_resend_timer, .resend_sucsess')
			.text('')
			.hide();

		// Focus back on the email input if it exists.
		var $emailInput = $form.find('#cev_pin_email');
		if ($emailInput.length) {
			$emailInput.focus();
		}
	});

	/**
	 * Handle resend verification code for guest user
	 */
	$(document).on('click', '.resend_verification_code_guest_user', function(e) {
		e.preventDefault();

		var $cevPinEmail = $('#cev_pin_email');
		var email = $cevPinEmail.val().trim();

		blockElement('.resend_verification_code_guest_user');

		var ajaxData = {
			action: 'resend_verification_code_guest_user',
			email: email,
			wp_nonce: $(this).data('nonce')
		};

		$.ajax({
			url: cev_ajax_object.ajax_url,
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				// Use the same response handler to ensure email is updated.
				handleCheckoutSendCodeResponse(response, email);
				unblockElement('.resend_verification_code_guest_user');
			},
			error: function(jqXHR, exception) {
				console.error('AJAX error:', jqXHR.status, exception);
				const errorMessage = AjaxResponseHandler.getErrorMessage(
					jqXHR.responseJSON,
					'Failed to resend verification code.'
				);
				UIHelper.showErrorAlert(errorMessage);
				unblockElement('.resend_verification_code_guest_user');
			}
		});

		return false;
	});

	// ============================================================================
	// VALIDATION ERROR HANDLING
	// ============================================================================

	/**
	 * Check for validation error field and reload page if found
	 */
	document.addEventListener('DOMContentLoaded', function() {
		var validationErrorField = document.querySelector('input[name="validation_error"]');
		if (validationErrorField) {
			// Reload page after 1 second to show validation error
			setTimeout(function() {
				location.reload();
			}, 1000);
		}
	});

	// ============================================================================
	// CHECKOUT EMAIL VERIFICATION (DROPDOWN OPTION)
	// ============================================================================

	/**
	 * Handle automatic email verification on checkout when dropdown option is enabled
	 */
	if (cev_ajax_object.cev_verification_checkout_dropdown_option === 1 && cev_ajax_object.cev_enable_email_verification_checkout === 1) {
		$(function() {
			var lastVerifiedEmail = cev_ajax_object.verifiedEmail || '';
			var isProcessing = false;
			var lastEmailValue = '';

			/**
			 * Perform AJAX call to verify email
			 *
			 * @param {string} email - Email address to verify
			 */
			function performAjaxCall(email) {
				// Prevent multiple simultaneous calls
				if (isProcessing) {
					return;
				}

				// Don't process if email is empty or same as last verified
				if (!email || email === lastVerifiedEmail) {
					return;
				}

				isProcessing = true;

				$.ajax({
					url: cev_ajax_object.ajax_url,
					method: 'POST',
					dataType: 'json',
					data: {
						action: 'custom_email_verification',
						email: email,
						wp_nonce: cev_ajax_object.wc_cev_email_guest_user
					},
					success: function(response) {
						const context = {
							lastVerifiedEmail: lastVerifiedEmail,
							isProcessing: isProcessing
						};
						handleCustomEmailVerificationResponse(response, email, context);
						
						// Update context variables after handler.
						if (context.lastVerifiedEmail !== lastVerifiedEmail) {
							lastVerifiedEmail = context.lastVerifiedEmail;
						}
						if (context.isProcessing !== isProcessing) {
							isProcessing = context.isProcessing;
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error:', error);
						isProcessing = false;
					}
				});
			}

			/**
			 * Handle email change event
			 */
			function handleEmailChange() {
				var $emailInput = $('#billing_email');
				if ($emailInput.length === 0) {
					return;
				}

				var emailValue = $emailInput.val().trim();

				// Validate email format
				if (!emailValue || !validateEmail(emailValue)) {
					return;
				}

				// Check if user was previously verified
				var wasVerified = cev_ajax_object.isVerified || (lastVerifiedEmail && lastVerifiedEmail.length > 0);

				// Only process if email is different from last value and user was verified
				if (emailValue !== lastEmailValue && wasVerified && emailValue !== lastVerifiedEmail) {
					lastEmailValue = emailValue;
					performAjaxCall(emailValue);
				} else if (emailValue) {
					lastEmailValue = emailValue;
				}
			}

			/**
			 * Attach email change handler
			 */
			function attachEmailChangeHandler() {
				var $emailInput = $('#billing_email');

				if ($emailInput.length === 0) {
					return;
				}

				// Remove any existing handlers to prevent duplicates
				$emailInput.off('change.popupverification blur.popupverification');

				// Store initial email value
				lastEmailValue = $emailInput.val() || '';

				// Listen for changes to the email input field
				$emailInput.on('change.popupverification blur.popupverification', function() {
					handleEmailChange();
				});
			}

			// Use event delegation for dynamically added elements
			$(document).on('change.popupverification blur.popupverification', '#billing_email', function() {
				handleEmailChange();
			});

			// Attach handler on page load
			attachEmailChangeHandler();

			// Re-attach handler after WooCommerce checkout updates
			$(document).on('updated_checkout', function() {
				var $emailInput = $('#billing_email');
				if ($emailInput.length > 0) {
					lastEmailValue = $emailInput.val() || '';
				}
				attachEmailChangeHandler();
			});
		});

		/**
		 * Restore verification state after page reload
		 */
		$(document).ready(function() {
			var performActions = localStorage.getItem('performActions');

			if (performActions === 'true') {
				var cevPinEmail = localStorage.getItem('cev_pin_email');

				// Show verification code section
				$('.cev-show-reg-content .cev-authorization__description span').html(cevPinEmail);
				$('.otp-container').show();
				$('.cev-authorization__heading').hide();
				$('.cev-show-reg-content').show();
				$('.cev-authorization__footer').hide();
				$('.cev-rge-content').show();
				$('.cev-hide-success-message').hide();

				// Clear localStorage
				localStorage.removeItem('performActions');
				localStorage.removeItem('cev_pin_email');
			}
		});
	}

	// ============================================================================
	// RESEND TIMER
	// ============================================================================

	/**
	 * Start resend timer for verification code
	 */
	function startResendTimer() {
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object) {
			console.warn('cev_ajax_object not defined for timer, retrying in 500ms');
			setTimeout(startResendTimer, 500);
			return;
		}

		var tpl = cev_ajax_object.count_down_text || 'You can resend the code in';
		var resendDelay = 30; // Default to 30 seconds
		var $resendButton = $('.cev_resend_link_front');
		var $timerDisplay = $('.cev_resend_timer');

		$resendButton.addClass('disabled').css('pointer-events', 'none').hide();
		var timeLeft = resendDelay;
		$timerDisplay.show().text(tpl + ' ' + timeLeft + 's');

		var timer = setInterval(function() {
			timeLeft--;
			$timerDisplay.text(tpl + ' ' + timeLeft + 's');

			if (timeLeft <= 0) {
				clearInterval(timer);
				$resendButton.removeClass('disabled').css('pointer-events', 'auto').show();
				$timerDisplay.hide();
			}
		}, 1000);
	}

	// Expose functions globally if needed
	window.showError = showError;
	window.hideError = hideError;
	window.validateEmail = validateEmail;
	window.startResendTimer = startResendTimer;

})(jQuery);
