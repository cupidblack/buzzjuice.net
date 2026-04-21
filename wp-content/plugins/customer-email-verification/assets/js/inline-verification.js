/**
 * Customer Email Verification PRO - Inline Verification JavaScript (Modified)
 *
 * Handles inline email verification on checkout page with OTP button,
 * verification code input, and AJAX interactions.
 *
 * Improvements:
 * - Defensive guards for missing `cev_ajax_object`
 * - No-op fallbacks for block/unblock when jquery.blockUI missing
 * - Delegated fallback click handler for dynamic #get_otp_btn
 * - Console logs and try/catch to prevent silent failures
 *
 * @package Customer_Email_Verification_Pro
 * @since 1.0.0
 */

(function($) {
	'use strict';

	// Ensure jQuery is available
	if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
		return;
	}

	// WordPress i18n for translations (if wp is available)
	var __ = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__) ? wp.i18n.__ : function(s) { return s; };

	/**
	 * Response code constants for standardized AJAX responses.
	 *
	 * @type {Object}
	 */
	const RESPONSE_CODES = {
		EMAIL_SENT: 'email_sent',
		ALREADY_VERIFIED: 'already_verified',
		VERIFIED: 'verified',
		SESSION_VERIFIED: 'session_verified',
		ALREADY_SENT: 'already_sent',
		LIMIT_REACHED: 'limit_reached',
	};

	/**
	 * AJAX Response Handler
	 */
	const AjaxResponseHandler = {
		getErrorMessage: function(response, defaultMessage) {
			defaultMessage = defaultMessage || 'An error occurred. Please try again.';
			if (response && response.error && response.error.message) {
				return response.error.message;
			}
			if (response && response.data && response.data.message) {
				return response.data.message;
			}
			if (response && response.message) {
				return response.message;
			}
			return defaultMessage;
		},
		isSuccess: function(response) {
			return response && response.success === true;
		},
		getCode: function(response) {
			return (response && response.code) ? response.code : null;
		},
		getData: function(response) {
			return (response && response.data) ? response.data : {};
		},
		hasCode: function(response, code) {
			return this.getCode(response) === code;
		},
	};

	// Cache selectors (may update later if dynamic)
	var $getOtpBtn = $('#get_otp_btn');
	var $emailInput = $('#billing_email');
	var $billingEmailField = $('#billing_email_field');
	var CREATE_ACCOUNT_SELECTOR = '#createaccount, input[name="createaccount"]';

	/**
	 * Track verified emails during the session
	 * This allows us to restore verification status if user changes email back to a previously verified one
	 */
	var verifiedEmails = [];

	/**
	 * Normalize int flags to avoid NaN issues.
	 * @param {*} value
	 * @return {number}
	 */
	function normalizeFlag(value) {
		var parsed = parseInt(value, 10);
		return isNaN(parsed) ? 0 : parsed;
	}

	/**
	 * Determine if verification should only run when the Create Account option is selected.
	 * @return {boolean}
	 */
	function shouldRespectCreateAccountChoice() {
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object) {
			return false;
		}
		var requireSetting = normalizeFlag(cev_ajax_object.cev_create_an_account_during_checkout);
		var guestCheckoutEnabled = normalizeFlag(cev_ajax_object.guest_checkout_enabled);
		return requireSetting === 1 && guestCheckoutEnabled === 1;
	}

	/**
	 * Locate the Create Account checkbox on the checkout form.
	 * @return {jQuery}
	 */
	function getCreateAccountCheckbox() {
		var $checkbox = $(CREATE_ACCOUNT_SELECTOR);
		if ($checkbox.length > 1) {
			var $visible = $checkbox.filter(':visible');
			if ($visible.length) {
				return $visible;
			}
			return $checkbox.first();
		}
		return $checkbox;
	}

	/**
	 * Check whether the Create Account option is currently selected.
	 * If the checkbox is not present, treat it as selected (account creation enforced).
	 * @return {boolean}
	 */
	function isCreateAccountOptionChecked() {
		var $checkbox = getCreateAccountCheckbox();
		if (!$checkbox.length) {
			return true;
		}
		return $checkbox.is(':checked');
	}

	/**
	 * Update OTP button visibility based on verification status and Create Account selection.
	 */
	function updateOtpButtonVisibility() {
		$getOtpBtn = $('#get_otp_btn');
		if (!$getOtpBtn.length) {
			return;
		}

		if (isEmailVerified(getEmail())) {
			$getOtpBtn.hide();
			return;
		}

		if (shouldRespectCreateAccountChoice() && !isCreateAccountOptionChecked()) {
			$getOtpBtn.hide();
			return;
		}

		$getOtpBtn.show();
	}

	/**
	 * Ensure OTP UI reflects Create Account selection requirements.
	 */
	function handleCreateAccountRequirementChange() {
		if (!shouldRespectCreateAccountChoice()) {
			updateOtpButtonVisibility();
			return;
		}

		if (!isCreateAccountOptionChecked()) {
			$('.cev_pro_append, .cev_inline_code_sent, .cev_inline_success_msg, .cev_inline_error_msg, .cev-hide-desc').remove();
		}

		updateOtpButtonVisibility();
	}

	/**
	 * Bind Create Account toggle watcher.
	 */
	function setupCreateAccountWatcher() {
		$(document).off('change.cevCreateAccount', CREATE_ACCOUNT_SELECTOR);

		if (!shouldRespectCreateAccountChoice()) {
			updateOtpButtonVisibility();
			return;
		}

		$(document).on('change.cevCreateAccount', CREATE_ACCOUNT_SELECTOR, function() {
			handleCreateAccountRequirementChange();
		});

		handleCreateAccountRequirementChange();
	}

	/**
	 * Check if an email is in the verified emails list
	 * @param {string} email - Email address to check
	 * @return {boolean}
	 */
	function isEmailVerified(email) {
		if (!email) return false;
		var normalizedEmail = email.trim().toLowerCase();
		return verifiedEmails.indexOf(normalizedEmail) !== -1;
	}

	/**
	 * Add an email to the verified emails list
	 * @param {string} email - Email address to add
	 */
	function addVerifiedEmail(email) {
		if (!email) return;
		var normalizedEmail = email.trim().toLowerCase();
		if (verifiedEmails.indexOf(normalizedEmail) === -1) {
			verifiedEmails.push(normalizedEmail);
		}
	}

	/**
	 * Remove an email from the verified emails list (when user changes to different email)
	 * @param {string} email - Email address to remove
	 */
	function removeVerifiedEmail(email) {
		if (!email) return;
		var normalizedEmail = email.trim().toLowerCase();
		var index = verifiedEmails.indexOf(normalizedEmail);
		if (index !== -1) {
			verifiedEmails.splice(index, 1);
		}
	}

	/**
	 * Show "already verified" message and hide OTP button for a verified email
	 * @param {string} email - The verified email address
	 */
	function showAlreadyVerifiedMessage(email) {
		$('.cev-hide-desc').remove();
		$('.cev_pro_append').remove();
		$('.cev_inline_code_sent').remove();
		
		var allReadyEmailVerifiedMsg = (typeof cev_ajax_object !== 'undefined' && cev_ajax_object.all_ready_email_verified_msg)
			? cev_ajax_object.all_ready_email_verified_msg
			: __("Email address is already verified", "customer-email-verification");
		
		$('<small class="cev-hide-desc" style="color: green; font-size:0.875rem; display:block;">' + allReadyEmailVerifiedMsg + '</small>').insertAfter($emailInput);
		$getOtpBtn.hide();
		$emailInput.css('margin', '0');
	}

	/**
	 * Reset verification status when email changes to a non-verified email
	 */
	function resetVerificationStatus() {
		$('.cev-hide-desc').remove();
		$('.cev_pro_append').remove();
		$('.cev_inline_code_sent').remove();
		$('.cev_inline_success_msg, .cev_inline_error_msg, .cev_email_error, .cev_limit_message').remove();
		$('.cev_verification__failure_code_checkout').hide().text('');
		updateOtpButtonVisibility();
	}

	/**
	 * Safe block/unblock fallbacks if blockUI is not present
	 */
	if (!$.fn.block) {
		$.fn.block = function() { return this; };
	}
	if (!$.fn.unblock) {
		$.fn.unblock = function() { return this; };
	}

	/**
	 * Safely read the billing email value and normalize to a trimmed string.
	 * @return {string}
	 */
	function getEmail() {
		if ($emailInput && $emailInput.length) {
			var v = $emailInput.val();
			if (typeof v === 'string') {
				return v.trim();
			}
			if (v) {
				return String(v).trim();
			}
		}
		return '';
	}

	function showError($element) {
		$element.css('border-color', '#dc2626');
	}

	function hideError($element) {
		$element.css('border-color', '#d1d5db');
	}

	function showSuccessMessage(message, $targetElement) {
		$('.cev_inline_success_msg').remove();
		if (message && $targetElement && $targetElement.length) {
			var $successMsg = $('<small class="cev_inline_success_msg" style="color:#10b981; font-size:0.875rem; display:block; margin-top:5px;">' + message + '</small>');
			$targetElement.after($successMsg);

			setTimeout(function() {
				$successMsg.fadeOut(200, function() {
					$(this).remove();
				});
			}, 3000);
		}
	}

	function showErrorMessage(message, $targetElement) {
		$('.cev_email_error, .cev_inline_error_msg').remove();
		if (message && $targetElement && $targetElement.length) {
			var $errorMsg = $('<p class="cev_inline_error_msg" style="color:#dc2626; margin-top:5px; font-size:0.875rem;">' + message + '</p>');
			if ($targetElement.is('input')) {
				$targetElement.closest('p, div').length ?
					$targetElement.closest('p, div').after($errorMsg) :
					$targetElement.after($errorMsg);
			} else {
				$targetElement.append($errorMsg);
			}
		}
	}

	function showLimitError(message) {
		var fallbackMessage = message || __("You have reached the resend limit. Please try again later.", "customer-email-verification");
		var $limit = $('.cev_limit_message');
		if ($limit.length) {
			$limit.text(fallbackMessage).css({
				'display': 'block',
				'color': '#dc2626',
				'margin-top': '5px'
			}).show();
		} else {
			showErrorMessage(fallbackMessage, $billingEmailField);
		}
	}

	function getVerificationSentMessage(customMessage) {
		if (customMessage) {
			return customMessage;
		}
		if (typeof cev_ajax_object !== 'undefined' && cev_ajax_object.verification_send_msg) {
			return cev_ajax_object.verification_send_msg;
		}
		return __("Verification code sent successfully", "customer-email-verification");
	}

	function isValidEmail(email) {
		var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return regex.test(email);
	}

	function blockCheckout(selector) {
		try {
			$(selector).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		} catch (err) {
			// Block UI not available
		}
	}

	function unblockCheckout(selector) {
		try {
			$(selector).unblock();
		} catch (err) {
			// Unblock UI not available
		}
	}

	/**
	 * Start resend timer for verification code
	 */
	function startResendTimer() {
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object) {
			setTimeout(startResendTimer, 500);
			return;
		}

		var resendDelay = 30; // Default to 30 seconds
		var $resendButton = $('.resend_verification_code_inline_chekout_user');
		var $timerDisplay = $('.cev_resend_timer');
		var tpl = cev_ajax_object.count_down_text || 'You can resend the code in';

		$resendButton.addClass('disabled').css('pointer-events', 'none').hide();
		var timeLeft = resendDelay;
		$timerDisplay.show().text(tpl + ' ' + timeLeft + 's');

		var timer = setInterval(function() {
			timeLeft--;
			$timerDisplay.show().text(tpl + ' ' + timeLeft + 's');

			if (timeLeft <= 0) {
				clearInterval(timer);
				$resendButton.removeClass('disabled').css('pointer-events', 'auto').show();
				$timerDisplay.hide();
			}
		}, 1000);
	}

	/**
	 * Initialize OTP button handler (original approach)
	 */
	function initOtpButton() {
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object) {
			setTimeout(initOtpButton, 500);
			return;
		}

		// ensure we re-query in case button was not found earlier
		$getOtpBtn = $('#get_otp_btn');
		if (!$getOtpBtn.length) {
			setTimeout(initOtpButton, 500);
			return;
		}

		// Provide a local debounce fallback if underscore (`_`) is not available on the page.
		var debounceFn = (window._ && _.debounce) ? _.debounce : function(func, wait) {
			var timeout;
			return function() {
				var context = this, args = arguments;
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					func.apply(context, args);
				}, wait);
			};
		};

		// attach the click handler with namespace to avoid conflicts
		$getOtpBtn.off('click.cev').on('click.cev', debounceFn(function(e) {
			e.preventDefault();
			e.stopPropagation(); // Prevent delegated handler from also running

			try {
				blockCheckout('.woocommerce-checkout');

				// Clear previous error messages
				$('.cev_email_error').remove();

				// Check if account creation is required
				if (shouldRespectCreateAccountChoice() && !isCreateAccountOptionChecked()) {
					$('.cev_pro_append').remove();
					unblockCheckout('.woocommerce-checkout');
					return;
				}

				var email = getEmail();

				var emailRequiredMsg = (typeof cev_ajax_object.email_required_msg !== 'undefined' && cev_ajax_object.email_required_msg)
					? cev_ajax_object.email_required_msg
					: __("Email is required for verification", "customer-email-verification");

				var invalidEmailMsg = (typeof cev_ajax_object.invalid_email_msg !== 'undefined' && cev_ajax_object.invalid_email_msg)
					? cev_ajax_object.invalid_email_msg
					: __("Email is Invalid", "customer-email-verification");

				if (!email) {
					$billingEmailField.append('<p class="cev_email_error" style="color:#dc2626; margin-top:5px; font-size:0.875rem;">' + emailRequiredMsg + '</p>');
					unblockCheckout('.woocommerce-checkout');
					return;
				} else if (!isValidEmail(email)) {
					$billingEmailField.append('<p class="cev_email_error" style="color:#dc2626; margin-top:5px; font-size:0.875rem;">' + invalidEmailMsg + '</p>');
					unblockCheckout('.woocommerce-checkout');
					return;
				}

				var ajaxData = {
					action: 'send_email_on_chekout_page',
					email: email,
					security: cev_ajax_object.checkout_send_verification_email
				};

				$.ajax({
					url: cev_ajax_object.ajax_url,
					type: 'post',
					dataType: 'json',
					data: ajaxData,
					success: function(response) {
						$('.cev_email_error, .cev_inline_error_msg, .cev_inline_success_msg').remove();

						if (response && response.success === true) {
							$('.cev_pro_append').remove();

							var resendLabel = (typeof cev_ajax_object.resend_verification_msg !== 'undefined' && cev_ajax_object.resend_verification_msg)
								? cev_ajax_object.resend_verification_msg
								: __("Resend Verification Code", "customer-email-verification");

							var verifyButtonText = (typeof cev_ajax_object.verify_button_text !== 'undefined' && cev_ajax_object.verify_button_text)
								? cev_ajax_object.verify_button_text
								: __("Verify", "customer-email-verification");

							var cancelLabel = (typeof cev_ajax_object.cancel_verification_text !== 'undefined' && cev_ajax_object.cancel_verification_text)
								? cev_ajax_object.cancel_verification_text
								: __("Cancel Verification", "customer-email-verification");

							const responseData = AjaxResponseHandler.getData(response);
							var successMessage = responseData.message || response.message || '';

							if (AjaxResponseHandler.hasCode(response, RESPONSE_CODES.ALREADY_VERIFIED) || responseData.isAlreadyVerified === true) {
								// Add email to verified emails list
								addVerifiedEmail(email);
								$emailInput.css('margin', '0');
								var allReadyEmailVerifiedMsg = successMessage || 
									((typeof cev_ajax_object.all_ready_email_verified_msg !== 'undefined' && cev_ajax_object.all_ready_email_verified_msg)
										? cev_ajax_object.all_ready_email_verified_msg
										: __("Email address is already verified", "customer-email-verification"));
								$('.cev_pro_append').remove();
								$('.cev-hide-desc').remove();
								$('<small class="cev-hide-desc" style="color: green; font-size:0.875rem; display:block;">' + allReadyEmailVerifiedMsg + '</small>').insertAfter($emailInput);
								$getOtpBtn.hide();
								unblockCheckout('.woocommerce-checkout');
								$('.cev-hide-desc').show();
								return;
							}

							const needsVerification = responseData.needsVerification === true || responseData.isEmailSent === true;
							const isCheckoutWcActive = responseData.isCheckoutWcActive === true;

							if (needsVerification) {
								var verificationHtml = '';

								if (isCheckoutWcActive) {
									verificationHtml = '<div class="cev_pro_append row cfw-input-wrap-row">' +
										'<div class="col-lg-12">' +
										'<div class="cfw-input-wrap cfw-text-input" style="margin-top:1rem;">' +
										'<label style="display:block; font-weight:500; color:#1f2937; font-size:0.875rem;">' + cev_ajax_object.email_verification_code_label + '</label>' +
										'<input type="text" class="input-text cev_pro_chekout_input" name="cev_billing_email" inputmode="numeric" style="width:100%; padding:0.5rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; margin-bottom:15px;">' +
										'<small class="cev_verification__failure_code_checkout" style="display:none; color:#dc2626; font-size:0.75rem;">' + cev_ajax_object.verification_code_error_msg + '</small>' +
										'<button type="button" class="woocommerce-Button button verify_account_email_chekout_page" name="verify_account_email" value="verify" id="verify_account_email">' + verifyButtonText + '</button>' +
										'<a href="#" class="a_cev_resend_checkout resend_verification_code_inline_chekout_user" name="resend_verification_code" wp_nonce="' + cev_ajax_object.wc_cev_email_guest_user + '" style="display:none;">' + resendLabel + '</a>' +
										'<a href="#" class="a_cev_cancel_checkout cancel_verification_checkout" style="margin-left:0.5rem; font-size:0.875rem; color:#dc2626;">' + cancelLabel + '</a>' +
										'<span class="cev_resend_timer" style="display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;"></span>' +
										'</div>' +
										'</div>' +
										'</div>';

									$billingEmailField.parent('.cfw-input-wrap-row').after(verificationHtml);
								} else {
									verificationHtml = '<div class="cev_pro_append" style="margin-top:1rem;">' +
										'<label class="jquery_color_css" style="display:block; font-weight:500; color:#1f2937; font-size:0.875rem;">' + cev_ajax_object.email_verification_code_label + '</label>' +
										'<input type="text" class="input-text cev_pro_chekout_input" name="cev_billing_email" inputmode="numeric" style="width:100%; padding:0.5rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; margin-bottom:15px;">' +
										'<small class="cev_verification__failure_code_checkout" style="display:none; color:#dc2626; font-size:0.75rem;">' + cev_ajax_object.verification_code_error_msg + '</small>' +
										'<button type="button" class="woocommerce-Button button verify_account_email_chekout_page" name="verify_account_email" value="verify" id="verify_account_email">' + verifyButtonText + '</button>' +
										'<a href="#" class="a_cev_resend_checkout resend_verification_code_inline_chekout_user" name="resend_verification_code" wp_nonce="' + cev_ajax_object.wc_cev_email_guest_user + '" style="display:none;">' + resendLabel + '</a>' +
										'<a href="#" class="a_cev_cancel_checkout cancel_verification_checkout" style="margin-left:0.5rem; font-size:0.875rem; color:#dc2626;">' + cancelLabel + '</a>' +
										'<span class="cev_resend_timer" style="display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;"></span>' +
										'</div>';

									$emailInput.closest('p').after(verificationHtml);
									$emailInput.css('margin', '0');
								}

								$getOtpBtn.hide();

								var sendSuccessMsg = getVerificationSentMessage(successMessage);
								showSuccessMessage(sendSuccessMsg, $emailInput);

								$(document).on('input', '.cev_pro_chekout_input', function() {
									var $verifyButton = $('#verify_account_email');
									if ($(this).val().trim().length > 0) {
										$verifyButton.css('display', 'inline-block');
									} else {
										$verifyButton.hide();
									}
								});

								startResendTimer();
							}

							unblockCheckout('.woocommerce-checkout');
							$('.cev-hide-desc').show();
						} else {
							var errorMessage = AjaxResponseHandler.getErrorMessage(response, 'An error occurred. Please try again.');
							const responseData = AjaxResponseHandler.getData(response);
							if (response && response.error && response.error.code === 'limit_reached') {
								// Force use of dedicated limit message, ignore generic text.
								showLimitError(null);
							} else {
								if (responseData.message) {
									errorMessage = responseData.message;
								}
								showErrorMessage(errorMessage, $billingEmailField);
							}
							unblockCheckout('.woocommerce-checkout');
						}
					},
					error: function(xhr, status, error) {
						var errorMessage = 'An error occurred. Please try again.';

						if (xhr && xhr.responseJSON) {
							if (xhr.responseJSON.error && xhr.responseJSON.error.message) {
								errorMessage = xhr.responseJSON.error.message;
							} else if (xhr.responseJSON.message) {
								errorMessage = xhr.responseJSON.message;
							} else if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
								errorMessage = xhr.responseJSON.data.message;
							}
						}

						showErrorMessage(errorMessage, $billingEmailField);
						unblockCheckout('.woocommerce-checkout');
					}
				});
			} catch (err) {
				unblockCheckout('.woocommerce-checkout');
			}
		}, 500));

		// Mark that original handler is attached
		$getOtpBtn.data('cev-attached', true);
	}

	/**
	 * Delegated fallback click handler:
	 * This ensures that if #get_otp_btn is added dynamically or original attachment fails,
	 * clicks are still handled and user sees meaningful messages.
	 * 
	 * Note: This handler will only run if the original handler hasn't been attached.
	 * The original handler uses namespace 'click.cev' and calls stopPropagation, so it won't conflict.
	 */
	$(document).on('click', '#get_otp_btn', function(e) {
		var $btn = $(this);
		
		// Check if original handler is attached (it uses namespace 'click.cev')
		// If the button has the data attribute, the original handler should handle it
		// Also check if event was already handled (isDefaultPrevented or isPropagationStopped)
		if ($btn.data('cev-attached') || e.isDefaultPrevented() || e.isPropagationStopped()) {
			return; // Let the original handler process it or event already handled
		}

		// If we get here, the original handler wasn't attached, so use fallback
		e.preventDefault();
		e.stopPropagation();

		// Basic sanity check for cev_ajax_object
		if (typeof cev_ajax_object === 'undefined' || !cev_ajax_object) {
			showErrorMessage('Verification script misconfigured. Contact admin.', $billingEmailField);
			return false;
		}

		// Minimal fallback flow: validate email and call ajax endpoint
		var email = getEmail();
		
		if (!email) {
			var emailRequiredMsg = cev_ajax_object.email_required_msg || __("Email is required for verification","customer-email-verification");
			showErrorMessage(emailRequiredMsg, $billingEmailField);
			return false;
		}
		if (!isValidEmail(email)) {
			var invalidEmailMsg = cev_ajax_object.invalid_email_msg || __("Email is Invalid","customer-email-verification");
			showErrorMessage(invalidEmailMsg, $billingEmailField);
			return false;
		}

		blockCheckout('.woocommerce-checkout');

		$.ajax({
			url: cev_ajax_object.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'send_email_on_chekout_page',
				email: email,
				security: cev_ajax_object.checkout_send_verification_email
			},
			success: function(resp) {
				unblockCheckout('.woocommerce-checkout');

				if (resp && resp.success) {
					const responseData = AjaxResponseHandler.getData(resp);
					
					// Check if email is already verified
					if (AjaxResponseHandler.hasCode(resp, RESPONSE_CODES.ALREADY_VERIFIED) || responseData.isAlreadyVerified === true) {
						addVerifiedEmail(email);
						showAlreadyVerifiedMessage(email);
					} else {
						var successMsg = getVerificationSentMessage((resp.data && resp.data.message) ? resp.data.message : resp.message);
						showSuccessMessage(successMsg, $emailInput);
						// Try to initialize the proper handler now
						initOtpButton();
					}
				} else {
					var errorMsg = AjaxResponseHandler.getErrorMessage(resp, 'Error sending verification');
					showErrorMessage(errorMsg, $billingEmailField);
				}
			},
			error: function(xhr, status, error) {
				unblockCheckout('.woocommerce-checkout');
				var errorMsg = 'Network or server error';
				if (xhr && xhr.responseJSON) {
					errorMsg = AjaxResponseHandler.getErrorMessage(xhr.responseJSON, errorMsg);
				}
				showErrorMessage(errorMsg, $billingEmailField);
			}
		});

		return false;
	});

	/**
	 * Handle resend verification code click
	 */
	$(document).on('click', '.resend_verification_code_inline_chekout_user', function(e) {
		e.preventDefault();

		blockCheckout('.woocommerce-checkout');

		var $el = $(this);
		var nonce = $el.attr('wp_nonce') || $el.data('nonce') || $el.attr('data-nonce') || $el.data('wp_nonce') || (cev_ajax_object && cev_ajax_object.wc_cev_email_guest_user) || (cev_ajax_object && cev_ajax_object.checkout_send_verification_email) || '';

		var ajaxData = {
			action: 'checkout_page_send_verification_code',
			email: getEmail(),
			wp_nonce: nonce
		};

		$.ajax({
			url: cev_ajax_object ? cev_ajax_object.ajax_url : '',
			type: 'post',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				$('.cev_inline_error_msg, .cev_inline_success_msg').remove();

				if (response && response.success === true) {
					const responseData = AjaxResponseHandler.getData(response);
					var successMessage = getVerificationSentMessage(responseData.message || response.message);

					if ((responseData && responseData.limit_reached === true) || (response.data && response.data.limit_reached === true)) {
						$('.a_cev_resend_checkout').css({
							'pointer-events': 'none',
							'opacity': '0.5'
						});
						showLimitError(successMessage);
					}

					showSuccessMessage(successMessage, $emailInput);
					unblockCheckout('.woocommerce-checkout');
					startResendTimer();
				} else {
					var errorMessage = AjaxResponseHandler.getErrorMessage(response, 'An error occurred. Please try again.');
					const responseData = AjaxResponseHandler.getData(response);
					if (response && response.error && response.error.code === 'limit_reached') {
						$('.a_cev_resend_checkout').css({
							'pointer-events': 'none',
							'opacity': '0.5'
						});
						// Use dedicated limit-reached text instead of generic error.
						showLimitError(null);
					} else {
						if (responseData.message) {
							errorMessage = responseData.message;
						}
						showErrorMessage(errorMessage, $emailInput);
					}
					unblockCheckout('.woocommerce-checkout');
				}
			},
			error: function(xhr, status, error) {
				var errorMessage = 'An error occurred. Please try again.';
				if (xhr && xhr.responseJSON) {
					if (xhr.responseJSON.error && xhr.responseJSON.error.message) {
						errorMessage = xhr.responseJSON.error.message;
					} else if (xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					} else if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMessage = xhr.responseJSON.data.message;
					}
				}
				showErrorMessage(errorMessage, $emailInput);
				unblockCheckout('.woocommerce-checkout');
			}
		});

		return false;
	});

	/**
	 * Handle cancel verification click
	 */
	$(document).on('click', '.cancel_verification_checkout', function(e) {
		e.preventDefault();

		$('.cev_pro_append').remove();
		$('.cev_inline_code_sent').remove();
		$('.cev_inline_success_msg, .cev_inline_error_msg, .cev_email_error').remove();
		$('.cev_limit_message').text('').hide();
		$('.cev_verification__failure_code_checkout').hide().text('');
		$('.cev-hide-desc').remove();

		$getOtpBtn = $('#get_otp_btn');
		updateOtpButtonVisibility();
		$emailInput.val('');
		unblockCheckout('.woocommerce-checkout');

		return false;
	});

	/**
	 * Handle verify code click
	 */
	$(document).on('click', '.verify_account_email_chekout_page', function(e) {
		e.preventDefault();

		var $checkoutPageBillingEmailVerify = $('.cev_pro_chekout_input');
		var $errorMsgElement = $('.cev_verification__failure_code_checkout');

		blockCheckout('.woocommerce-checkout');

		$errorMsgElement.hide();
		hideError($checkoutPageBillingEmailVerify);

		if ($checkoutPageBillingEmailVerify.val() === '') {
			showError($checkoutPageBillingEmailVerify);
			var emptyCodeMsg = (typeof cev_ajax_object !== 'undefined' && cev_ajax_object.please_enter_code_msg) ? cev_ajax_object.please_enter_code_msg : __("Please enter the verification code", "customer-email-verification");
			$errorMsgElement.text(emptyCodeMsg).show().css('color', '#dc2626');
			unblockCheckout('.woocommerce-checkout');
		} else {
			hideError($checkoutPageBillingEmailVerify);

			var ajaxData = {
				action: 'checkout_page_verify_code',
				pin: $checkoutPageBillingEmailVerify.val().trim(),
				security: (cev_ajax_object && cev_ajax_object.checkout_verify_code) ? cev_ajax_object.checkout_verify_code : ''
			};

			$.ajax({
				url: cev_ajax_object ? cev_ajax_object.ajax_url : '',
				type: 'post',
				dataType: 'json',
				data: ajaxData,
				success: function(response) {
					$('.cev_inline_error_msg, .cev_inline_success_msg').remove();

					if (AjaxResponseHandler.isSuccess(response) && AjaxResponseHandler.hasCode(response, RESPONSE_CODES.VERIFIED)) {
						// Add email to verified emails list
						var verifiedEmail = getEmail();
						addVerifiedEmail(verifiedEmail);
						
						$('.cev_pro_append').remove();
						$emailInput.css({
							'border-color': '#ddd',
							'color': '#333'
						});

						const responseData = AjaxResponseHandler.getData(response);
						var successMessage = responseData.message || response.message || 
							((typeof cev_ajax_object !== 'undefined' && cev_ajax_object.email_verified_msg) ? cev_ajax_object.email_verified_msg : __("Email verified successfully", "customer-email-verification"));

						unblockCheckout('.woocommerce-checkout');
						$('<small class="cev-hide-desc" style="color: green; font-size:0.875rem; display:block;">' + successMessage + '</small>').insertAfter($emailInput);
						$emailInput.css('margin', '0');
						$('.cev_inline_code_sent').remove();
						$errorMsgElement.hide();
						$getOtpBtn.hide();
					} else {
						var errorMessage = (cev_ajax_object && cev_ajax_object.invalid_verification_code_msg) || (cev_ajax_object && cev_ajax_object.verification_code_error_msg) || __("Invalid verification code", "customer-email-verification");

						if (response && response.error && response.error.message) {
							errorMessage = response.error.message;
						} else if (response && response.message) {
							errorMessage = response.message;
						} else {
							const responseData = AjaxResponseHandler.getData(response);
							if (responseData.message) {
								errorMessage = responseData.message;
							}
						}

						unblockCheckout('.woocommerce-checkout');
						$errorMsgElement.text(errorMessage).show().css('color', '#dc2626');
						showError($checkoutPageBillingEmailVerify);
						$('.cev_pro_chekout_input').css('margin', '0');
						$('.cev-hide-desc').remove();
					}
				},
				error: function(xhr, status, error) {
					var errorMessage = (cev_ajax_object && cev_ajax_object.verification_error_msg) || __("An error occurred while verifying. Please try again.", "customer-email-verification");

					if (xhr && xhr.responseJSON) {
						if (xhr.responseJSON.error && xhr.responseJSON.error.message) {
							errorMessage = xhr.responseJSON.error.message;
						} else if (xhr.responseJSON.message) {
							errorMessage = xhr.responseJSON.message;
						} else if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
							errorMessage = xhr.responseJSON.data.message;
						}
					}

					if (!xhr.responseJSON || (!xhr.responseJSON.error && !xhr.responseJSON.message && !xhr.responseJSON.data)) {
						if (status === 'timeout') {
							errorMessage = (cev_ajax_object && cev_ajax_object.network_error_msg) || __("Request timed out. Please check your connection and try again.", "customer-email-verification");
						} else if (status === 'error' && xhr.status === 0) {
							errorMessage = (cev_ajax_object && cev_ajax_object.network_error_msg) || __("Network error. Please check your connection and try again.", "customer-email-verification");
						} else if (xhr.status >= 500) {
							errorMessage = (cev_ajax_object && cev_ajax_object.server_error_msg) || __("Server error. Please try again later.", "customer-email-verification");
						} else if (xhr.status >= 400 && xhr.status < 500) {
							errorMessage = (cev_ajax_object && cev_ajax_object.invalid_response_msg) || __("Invalid request. Please try again.", "customer-email-verification");
						}
					}

					$errorMsgElement.text(errorMessage).show().css('color', '#dc2626');
					showError($checkoutPageBillingEmailVerify);
					unblockCheckout('.woocommerce-checkout');
				}
			});
		}

		return false;
	});

	/**
	 * Mutation observer for dynamically added #get_otp_btn
	 */
	function observeOtpButton() {
		if (!document.body) {
			if (document.readyState === 'loading') {
				$(document).on('DOMContentLoaded', observeOtpButton);
				return;
			}
			setTimeout(observeOtpButton, 100);
			return;
		}

		var observer = new MutationObserver(function(mutations, observerInstance) {
			var $btn = $('#get_otp_btn');
			if ($btn.length) {
				$getOtpBtn = $btn; // update cached reference
				initOtpButton();
				handleCreateAccountRequirementChange();
				observerInstance.disconnect();
			}
		});

		if (document.body && document.body.nodeType === Node.ELEMENT_NODE) {
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		} else {
			setTimeout(observeOtpButton, 100);
		}
	}

	/**
	 * Initialize the inline verification functionality
	 */
	function initializeInlineVerification() {
		// Clean old elements
		$('.cev_pro_append').remove();
		$('.cev_inline_code_sent').remove();

		// Re-query selectors in case DOM changed
		$getOtpBtn = $('#get_otp_btn');
		$emailInput = $('#billing_email');
		$billingEmailField = $('#billing_email_field');

		setupCreateAccountWatcher();

		// Try init or observe
		if ($getOtpBtn.length) {
			initOtpButton();
			if (typeof cev_ajax_object !== 'undefined' && cev_ajax_object.isVerified && getEmail() === cev_ajax_object.verifiedEmail) {
				$getOtpBtn.hide();
			}
		} else {
			observeOtpButton();
		}

		// Initialize verified emails list with any existing verified email
		if (typeof cev_ajax_object !== 'undefined' && cev_ajax_object.isVerified && cev_ajax_object.verifiedEmail) {
			addVerifiedEmail(cev_ajax_object.verifiedEmail);
			var currentEmail = getEmail();
			if (currentEmail && currentEmail.trim().toLowerCase() === cev_ajax_object.verifiedEmail.trim().toLowerCase()) {
				showAlreadyVerifiedMessage(currentEmail);
			}
		}

		$emailInput.off('input.cev_email_change').on('input.cev_email_change', function() {
			// Clear all inline messages and verification UI when user edits email.
			$('.cev_inline_success_msg, .cev_inline_error_msg, .cev_email_error').remove();
			$('.cev_limit_message').text('').hide();
			$('.cev_verification__failure_code_checkout').hide().text('');
			$('.cev_pro_append').remove();

			var currentVal = getEmail();
			var normalizedCurrentVal = currentVal ? currentVal.trim().toLowerCase() : '';
			
			// Check if current email is in verified emails list
			if (normalizedCurrentVal && isEmailVerified(normalizedCurrentVal)) {
				// Email is verified, show message and hide button
				showAlreadyVerifiedMessage(normalizedCurrentVal);
			} else if (normalizedCurrentVal) {
				// Email changed to a non-verified email, reset verification status
				resetVerificationStatus();
			} else {
				// Empty email, just show button
				resetVerificationStatus();
			}
		});
	}

	// Initialize immediately and also on DOM ready
	// Function to safely initialize
	function safeInitialize() {
		try {
			initializeInlineVerification();
		} catch (err) {
			// Error during initialization
		}
	}

	// Wait for DOM to be ready
	$(document).ready(function() {
		safeInitialize();
	});

	// Also try immediately in case DOM is already ready
	if (document.readyState === 'complete' || document.readyState === 'interactive') {
		setTimeout(safeInitialize, 50);
	} else {
		// If still loading, wait a bit and try
		setTimeout(function() {
			if (document.readyState === 'complete' || document.readyState === 'interactive') {
				safeInitialize();
			}
		}, 500);
	}

	// Re-initialize after WooCommerce checkout updates
	$(document).on('updated_checkout', function() {
		initializeInlineVerification();
	});

})(jQuery);
