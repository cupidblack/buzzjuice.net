/**
 * Signup Verification Script
 *
 * Handles the OTP verification process during user registration.
 * Manages email validation, CAPTCHA checks, OTP input, and form submission.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

(function() {
	'use strict';

	/**
	 * Main signup verification handler
	 */
	jQuery(document).ready(function() {
		// Check if cev_ajax is defined.
		if (typeof cev_ajax === 'undefined') {
			console.error('cev_ajax is not defined! Script may not be properly enqueued.');
			return;
		}
		
		// Only proceed if email verification is enabled.
		if (!cev_ajax.enableEmailVerification) {
			return;
		}

		var otpVerified = false;
		var $registerForm = jQuery('.woocommerce-form-register');

		/**
		 * Display error message to user
		 *
		 * @param {string} message Error message to display.
		 */
		function displayError(message) {
			var $form = jQuery(cev_ajax.cev_Validation_error_position_id || '.woocommerce-form-register');
			$form.prepend('<div class="woocommerce-error" role="alert">' + message + '</div>');
		}

		/**
		 * Clear all error messages
		 */
		function clearErrors() {
			jQuery('.woocommerce-error').remove();
		}

		/**
		 * Ensure email_verification hidden field exists in form
		 *
		 * @param {jQuery} $form Form element.
		 * @param {string} value Value to set.
		 */
		function ensureEmailVerificationField($form, value) {
			var $field = $form.find('input[name="email_verification"]');
			if ($field.length === 0) {
				$form.append('<input type="hidden" name="email_verification" value="' + value + '">');
			} else {
				$field.val(value);
			}
		}

		/**
		 * Submit registration form after OTP verification
		 *
		 * @param {jQuery} $form Form element to submit.
		 */
		function submitRegistrationForm($form) {
			// Mark OTP as verified FIRST - this is critical.
			otpVerified = true;

			// Set email verification field to true BEFORE removing class.
			ensureEmailVerificationField($form, 'true');

			// Get the register button.
			var $registerButton = $form.find('button[name="register"]');
			
			if (!$registerButton.length) {
				console.error('Register button not found in form');
				return;
			}
			
			// Remove the popup class to allow normal form submission.
			$registerButton.removeClass('email_verification_popup');

			// Hide popup.
			jQuery('#otp-popup').hide();
			jQuery('.cev_loading_overlay').css('display', 'none');

			// Remove our specific event handler completely.
			jQuery(document).off('click.email_verification');

			// Remove any submit handlers that might prevent submission.
			$form.off('submit.email_verification');

			// Wait a moment for DOM to update, then click the register button.
			// This ensures WooCommerce's form handler processes it correctly.
			setTimeout(function() {
				// Verify the button still exists and doesn't have the popup class.
				if (!$registerButton.length) {
					console.error('Register button not found');
					return;
				}

				// Verify email_verification field is still set.
				var emailVerificationValue = $form.find('input[name="email_verification"]').val();
				
				if (emailVerificationValue !== 'true') {
					console.warn('Email verification field not set correctly, setting it now...');
					ensureEmailVerificationField($form, 'true');
				}

				// Trigger click on the register button using jQuery.
				// This will trigger WooCommerce's normal registration flow.
				$registerButton.trigger('click');
			}, 500);
		}

		// Initialize: Set email_verification field to false and add popup class.
		if ($registerForm.length) {
			ensureEmailVerificationField($registerForm, 'false');
			$registerForm.find('button[name="register"]').addClass('email_verification_popup');
		}

		/**
		 * Handle register button click event
		 */
		jQuery(document).on('click.email_verification', '.email_verification_popup', function(e) {
			// If OTP is already verified, allow normal form submission.
			if (otpVerified) {
				return;
			}

			// Prevent default form submission.
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();

			clearErrors();

			var $button = jQuery(this);
			var $form = $button.closest('form');
			var email = $form.find('#reg_email').val();
			var password = $form.find('#reg_password').val();

			// Show loading overlay.
			jQuery('.cev_loading_overlay').css('display', 'block');

			// Validate email.
			if (!email) {
				displayError(cev_ajax.cev_email_validation || 'Email is required.');
				jQuery('.cev_loading_overlay').css('display', 'none');
				return;
			}

			// Validate password if password setup link is disabled.
			if (cev_ajax.password_setup_link_enabled === 'no') {
				if (!password) {
					displayError(cev_ajax.cev_password_validation || 'Password is required.');
					jQuery('.cev_loading_overlay').css('display', 'none');
					return;
				}
			}

			// Validate CAPTCHA if present.
			var captchaValid = true;
			var captchaError = '';

			// Check for Google reCAPTCHA.
			if ($form.find('.g-recaptcha-response').length) {
				var recaptchaResponse = $form.find('.g-recaptcha-response').val();
				if (!recaptchaResponse) {
					captchaValid = false;
					captchaError = 'Please complete the CAPTCHA';
				}
			}

			// Check for Contact Form 7 CAPTCHA.
			if ($form.find('[name="cf7-captcha"]').length) {
				if (!$form.find('[name="cf7-captcha"]').val()) {
					captchaValid = false;
					captchaError = 'Please complete the CAPTCHA';
				}
			}

			// Check for Captcha plugin.
			if ($form.find('[name="cptch_number"]').length) {
				if (!$form.find('[name="cptch_number"]').val()) {
					captchaValid = false;
					captchaError = 'Please complete the CAPTCHA';
				}
			}

			// Check for hCaptcha.
			if ($form.find('[name="h-captcha-response"]').length) {
				var hcaptchaResponse = $form.find('[name="h-captcha-response"]').val();
				if (!hcaptchaResponse) {
					captchaValid = false;
					captchaError = 'Please complete the CAPTCHA';
				}
			}

			if (!captchaValid) {
				displayError(captchaError);
				jQuery('.cev_loading_overlay').css('display', 'none');
				return;
			}

			// AJAX request to check if email exists and send OTP.
			jQuery.ajax({
				url: cev_ajax.ajax_url,
				type: 'POST',
				data: $registerForm.serialize() + '&action=check_email_exists&nonce=' + cev_ajax.nonce,
				dataType: 'json',
				success: function(response) {
					jQuery('.cev_loading_overlay').css('display', 'none');

					// Handle standardized success response.
					if (response && response.success === true) {
						if (response.data && response.data.exists) {
							displayError(response.message || cev_ajax.cev_email_exists_validation || 'An account with this email already exists.');
						} else if (response.data && response.data.not_valid) {
							displayError(response.message || cev_ajax.cev_valid_email_validation || 'Please enter a valid email address.');
						} else if (response.data && response.data.already_verify) {
							// Email already verified, proceed with registration.
							submitRegistrationForm($form);
						} else if (response.code === 'email_sent' || 
						           (response.data && response.data.success) ||
						           (response.data && response.data.emailAddress)) {
							// Get email from response or form (optional - may be empty)
							var emailAddress = '';
							if (response.data && response.data.emailAddress) {
								emailAddress = response.data.emailAddress;
							} else if (email && typeof email === 'string' && email.length > 0) {
								emailAddress = email;
							}
							
							// Update message with actual email address if available
							var $messageElement = jQuery('.cev-authorization__description');
							if ($messageElement.length) {
								var messageText = $messageElement.html();
								
								// Only replace {customer_email} if email is available
								if (emailAddress && emailAddress.length > 0) {
									// Replace {customer_email} and {{customer_email}} placeholders with actual email
									// Use both formats to handle any variations
									messageText = messageText.replace(/\{\{customer_email\}\}/g, '<strong>' + emailAddress + '</strong>');
									messageText = messageText.replace(/\{customer_email\}/g, '<strong>' + emailAddress + '</strong>');
								} else {
									// If email not available, remove the placeholder or leave it as is
									// Optionally remove the placeholder to avoid showing "{customer_email}"
									messageText = messageText.replace(/\{\{customer_email\}\}/g, '');
									messageText = messageText.replace(/\{customer_email\}/g, '');
								}
								
								$messageElement.html(messageText);
							}
							
							// Show OTP popup and start resend timer.
							// Check for email_sent code, data.success, or emailAddress in data.
							jQuery('#otp-popup').show();
							startResendTimer();
						} else if (response.data && response.data.validation === false) {
							displayError(response.message || 'Validation failed.');
						} else {
							// If we have success but no specific condition matched, show popup anyway.
							// This handles edge cases where response format might vary.
							if (response.success && response.data) {
								jQuery('#otp-popup').show();
								startResendTimer();
							}
						}
					} else {
						// Handle error response.
						var errorMessage = 'An error occurred. Please try again.';
						if (response && response.error) {
							errorMessage = response.error.message || errorMessage;
						} else if (response && response.data && response.data.message) {
							errorMessage = response.data.message;
						}
						displayError(errorMessage);
					}
				},
				error: function(xhr, status, error) {
					jQuery('.cev_loading_overlay').css('display', 'none');
					
					// Try to parse error response.
					var errorMessage = 'An error occurred. Please try again.';
					try {
						if (xhr.responseText) {
							var errorResponse = JSON.parse(xhr.responseText);
							if (errorResponse && errorResponse.error) {
								errorMessage = errorResponse.error.message || errorMessage;
							} else if (errorResponse && errorResponse.data && errorResponse.data.message) {
								errorMessage = errorResponse.data.message;
							}
						}
					} catch (e) {
						console.error('Failed to parse error response:', e);
					}
					
					displayError(errorMessage);
					console.error('AJAX error:', error);
				}
			});
		});

		/**
		 * Handle OTP input fields
		 */
		var $otpInputs = jQuery('.otp-input');

		// Handle manual typing in OTP fields.
		$otpInputs.on('input', function() {
			var $input = jQuery(this);
			var value = $input.val();
			var index = $otpInputs.index(this);

			if (value.length === 1) {
				// Move to next field if available.
				if (index < $otpInputs.length - 1) {
					$otpInputs.eq(index + 1).focus();
				} else {
					// Last field filled, trigger verification.
					jQuery('#verify-otp-button').trigger('click');
				}
			}
		});

		// Handle paste event for OTP fields.
		$otpInputs.on('paste', function(e) {
			e.preventDefault();
			var pasteData = (e.originalEvent || e).clipboardData.getData('text');
			var chars = pasteData.replace(/\D/g, '').split(''); // Keep only digits.

			$otpInputs.each(function(i) {
				jQuery(this).val(chars[i] || '');
			});

			if (chars.length >= $otpInputs.length) {
				// All fields filled, trigger verification.
				jQuery('#verify-otp-button').trigger('click');
			} else {
				// Focus on next empty field.
				$otpInputs.eq(chars.length).focus();
			}
		});

		// Handle backspace key in OTP fields.
		$otpInputs.on('keydown', function(e) {
			var $input = jQuery(this);
			var index = $otpInputs.index(this);

			if (e.keyCode === 8 && $input.val().length === 0) {
				// Move to previous field on backspace.
				if (index > 0) {
					$otpInputs.eq(index - 1).focus();
				}
			}
		});

		// Only allow digits in OTP fields.
		$otpInputs.on('keypress', function(e) {
			var charCode = (e.which) ? e.which : e.keyCode;
			if (charCode < 48 || charCode > 57) {
				e.preventDefault();
			}
		});

		/**
		 * Handle OTP verification button click
		 */
		jQuery('#verify-otp-button').on('click', function(e) {
			e.preventDefault();
			
			/**
			 * Get OTP value from all input fields
			 *
			 * @return {string} Combined OTP value.
			 */
			function getOtpValue() {
				var otp = '';
				jQuery('.otp-input').each(function() {
					otp += jQuery(this).val();
				});
				return otp;
			}

			var $form = $registerForm.length ? $registerForm : jQuery('.email_verification_popup').closest('form');
			var email = $form.find('#reg_email').val();
			var otp = getOtpValue();

			// Check if cev_ajax is defined.
			if (typeof cev_ajax === 'undefined') {
				console.error('cev_ajax is not defined! Check if script is properly enqueued.');
				jQuery('.error_mesg').text('Configuration error. Please refresh the page and try again.').css('display', 'block');
				return;
			}

			// Validate inputs.
			if (!email || !otp) {
				console.error('Missing email or OTP');
				jQuery('.error_mesg').text('Please enter the OTP code.').css('display', 'block');
				return;
			}

			// Clear previous errors.
			clearErrors();
			jQuery('.error_mesg').text('').css('display', 'none');

			// Show loading overlay.
			jQuery('.cev_loading_overlay').css('display', 'block');

			// Verify OTP using AJAX.
			jQuery.ajax({
				url: cev_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'verify_otp',
					otp: otp,
					email: email,
					nonce: cev_ajax.nonce
				},
				dataType: 'json',
				success: function(response) {
					// Always hide loader first to prevent it from staying visible.
					jQuery('.cev_loading_overlay').css('display', 'none');

					// Handle standardized success response.
					// Check for verified in multiple places for backward compatibility:
					// 1. response.code === 'verified' (new format)
					// 2. response.data.verified === true (legacy format)
					// 3. response.success === true with verified code
					var isVerified = false;
					if (response && response.success === true) {
						if (response.code === 'verified' || 
						    (response.data && response.data.verified === true) ||
						    (response.data && response.data.verified === 'true')) {
							isVerified = true;
						}
					}

					if (isVerified) {
						// OTP verified successfully.
						jQuery('.error_mesg').css('display', 'none');

						// Get success message from response, settings, or use default.
						var successMessage = response.message 
							|| (typeof cev_ajax !== 'undefined' && cev_ajax.success_message) 
							|| 'Your email is verified successfully.';

						// Show success message briefly.
						var $successMsg = jQuery('<p class="success-message" role="status" aria-live="polite">' + successMessage + '</p>')
							.insertBefore($form)
							.css({
								'color': 'green',
								'margin-top': '10px',
								'text-align': 'center'
							});

						// Submit the registration form after a short delay.
						setTimeout(function() {
							$successMsg.remove();
							submitRegistrationForm($form);
						}, 500);
					} else {
						// Handle error response.
						var errorMessage = 'OTP verification failed. Please try again.';
						if (response && response.error) {
							errorMessage = response.error.message || errorMessage;
						} else if (response && response.data && response.data.message) {
							errorMessage = response.data.message;
						} else if (response && response.message) {
							errorMessage = response.message;
						}
						jQuery('.error_mesg').text(errorMessage).css('display', 'block');
					}
				},
				error: function(xhr, status, error) {
					console.error('OTP verification error:', error);
					
					jQuery('.cev_loading_overlay').css('display', 'none');
					
					// Try to parse error response.
					var errorMessage = 'An error occurred while verifying OTP. Please try again.';
					try {
						if (xhr.responseText) {
							var errorResponse = JSON.parse(xhr.responseText);
							if (errorResponse && errorResponse.error) {
								errorMessage = errorResponse.error.message || errorMessage;
							} else if (errorResponse && errorResponse.data && errorResponse.data.message) {
								errorMessage = errorResponse.data.message;
							}
						}
					} catch (e) {
						console.error('Failed to parse error response:', e);
					}
					jQuery('.error_mesg').text(errorMessage).css('display', 'block');
				}
			});
		});

		/**
		 * Flag to prevent duplicate resend requests.
		 * @type {boolean}
		 */
		var resendRequestInProgress = false;

		/**
		 * Handle resend OTP button click (Try Again)
		 * Handles clicks on the footer or any links inside it, or send_again_link
		 */
		jQuery(document).on('click', '.cev-authorization__footer.cev-rge-content, .cev-authorization__footer.cev-rge-content a, .send_again_link', function(e) {
			e.preventDefault();

			// Only handle on signup/registration pages.
			// On checkout, front.js handles resend via .resend_verification_code_guest_user.
			if (!$registerForm.length) {
				return;
			}

			e.stopPropagation();
			e.stopImmediatePropagation();

			// Prevent duplicate requests.
			if (resendRequestInProgress) {
				return false;
			}

			var $form = $registerForm.length ? $registerForm : jQuery('.woocommerce-form-register__submit').closest('form');
			var email = $form.find('#reg_email').val();

			if (!email) {
				// Show error in popup.
				jQuery('.error_mesg').text('Email is required.').css('display', 'block');
				return false;
			}

			// Mark request as in progress and disable button.
			resendRequestInProgress = true;
			var $resendButton = jQuery('.cev-authorization__footer');
			$resendButton.addClass('disabled').css('pointer-events', 'none').prop('disabled', true);

			// Clear previous messages.
			jQuery('.error_mesg').text('').css('display', 'none');
			jQuery('.resend_sucsess').hide();

			// Show loading overlay.
			jQuery('.cev_loading_overlay').css('display', 'block');

			jQuery.ajax({
				url: cev_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'resend_otp',
					email: email,
					nonce: cev_ajax.nonce
				},
				dataType: 'json',
				success: function(response) {
					// Reset request flag and re-enable button.
					resendRequestInProgress = false;
					$resendButton.removeClass('disabled').css('pointer-events', 'auto').prop('disabled', false);

					jQuery('.cev_loading_overlay').css('display', 'none');

					// Clear any previous error messages.
					jQuery('.error_mesg').text('').css('display', 'none');
					jQuery('.resend_sucsess').hide();

					// Handle standardized success response.
					if (response && response.success === true) {
						// Email sent successfully - show success message.
						var successMessage = response.message 
							|| (cev_ajax.resend_success_message) 
							|| 'Verification email sent successfully';
						
						jQuery('.resend_sucsess').text(successMessage).show();
						
						// Hide success message after 5 seconds.
						setTimeout(function() {
							jQuery('.resend_sucsess').hide();
						}, 5000);

						// Restart resend timer.
						startResendTimer();
					} else {
						// Handle error response.
						var errorMessage = 'Failed to resend OTP. Please try again.';
						
						// Check for limit_reached error code.
						if (response && response.error && response.error.code === 'limit_reached') {
							// Resend limit reached - show configured limit message.
							errorMessage = response.error.message 
								|| (cev_ajax.resend_limit_message) 
								|| 'Too many attempts, please contact us for further assistance';
						} else if (response && response.error) {
							// Other error with message.
							errorMessage = response.error.message || errorMessage;
						} else if (response && response.data && response.data.limit_reached) {
							// Legacy format support.
							errorMessage = response.data.message 
								|| (cev_ajax.resend_limit_message) 
								|| 'Too many attempts, please contact us for further assistance';
						} else if (response && response.data && response.data.message) {
							errorMessage = response.data.message;
						} else if (response && response.message) {
							errorMessage = response.message;
						}
						
						jQuery('.error_mesg').text(errorMessage).css('display', 'block');
					}
				},
				error: function(xhr, status, error) {
					// Reset request flag and re-enable button.
					resendRequestInProgress = false;
					$resendButton.removeClass('disabled').css('pointer-events', 'auto').prop('disabled', false);

					jQuery('.cev_loading_overlay').css('display', 'none');
					
					// Try to parse error response for limit message.
					var errorMessage = 'An error occurred. Please try again.';
					var isLimitReached = false;
					try {
						if (xhr.responseText) {
							var errorResponse = JSON.parse(xhr.responseText);
							if (errorResponse && errorResponse.error) {
								// Standardized error format.
								if (errorResponse.error.code === 'limit_reached') {
									isLimitReached = true;
									errorMessage = errorResponse.error.message 
										|| (cev_ajax.resend_limit_message) 
										|| 'Too many attempts, please contact us for further assistance';
								} else {
									errorMessage = errorResponse.error.message || errorMessage;
								}
							} else if (errorResponse && errorResponse.data) {
								// Legacy format support.
								if (errorResponse.data.limit_reached) {
									isLimitReached = true;
									errorMessage = errorResponse.data.message 
										|| (cev_ajax.resend_limit_message) 
										|| 'Too many attempts, please contact us for further assistance';
								} else if (errorResponse.data.message) {
									errorMessage = errorResponse.data.message;
								}
							}
						}
					} catch (e) {
						console.error('Failed to parse error response:', e);
					}
					
					// Show error message in popup.
					jQuery('.error_mesg').text(errorMessage).css('display', 'block');
					if (!isLimitReached) {
						console.error('AJAX error:', error);
					}
				},
				complete: function() {
					// Ensure flag is reset even if there's an unexpected error.
					resendRequestInProgress = false;
					$resendButton.removeClass('disabled').css('pointer-events', 'auto').prop('disabled', false);
				}
			});
		});

		/**
		 * Handle back button click to close popup and reset state.
		 *
		 * This clears all input values and removes any error/success messages
		 * so the customer sees a clean form when they open the popup again.
		 */
		jQuery(document).on('click', '.back_btn', function(e) {
			e.preventDefault();

			var $popup = jQuery(this).closest('#otp-popup');

			if ($popup.length) {
				// Hide the OTP popup.
				$popup.hide();

				// Clear all input fields within the popup.
				$popup.find('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea')
					.val('');

				// Hide and clear error/success messages within the popup.
				$popup.find('.error_mesg, .success-message, .resend_sucsess')
					.text('')
					.hide();

				// Reset resend timer display.
				$popup.find('.cev_resend_timer').text('').hide();
			}

			// Clear any validation error styles.
			clearErrors();
		});
	});

	/**
	 * Start resend timer countdown
	 */
	function startResendTimer() {
		if (typeof cev_ajax === 'undefined' || !cev_ajax) {
			console.warn('cev_ajax object not defined for timer, retrying in 500ms');
			setTimeout(startResendTimer, 500);
			return;
		}

		// Get resend delay from settings (OTP expiration time) or use default.
		var resendDelay = parseInt(cev_ajax.resend_delay, 10) || 30; // Default to 30 seconds.
		var $resendButton = jQuery('.cev-authorization__footer');
		var $timerDisplay = jQuery('.cev_resend_timer');
		var countdownText = cev_ajax.count_down_text || 'You can resend the code in';

		var timeLeft = resendDelay;

		// Disable and hide resend button initially.
		$resendButton.addClass('disabled').css('pointer-events', 'none').hide();
		$timerDisplay.show().text(countdownText + ' ' + timeLeft + 's');

		var timer = setInterval(function() {
			timeLeft--;
			$timerDisplay.text(countdownText + ' ' + timeLeft + 's');

			if (timeLeft <= 0) {
				clearInterval(timer);
				$resendButton.removeClass('disabled').css('pointer-events', 'auto').show();
				$timerDisplay.hide();
			}
		}, 1000);
	}

})(jQuery);
