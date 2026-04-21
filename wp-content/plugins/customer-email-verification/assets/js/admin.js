/**
 * Customer Email Verification Admin JavaScript
 *
 * Handles all admin-side JavaScript functionality including settings forms,
 * AJAX requests, UI interactions, and data table management.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

(function( jQuery ) {
	'use strict';

	/**
	 * Configuration constants
	 */
	const CONFIG = {
		SNACKBAR_DURATION: 3000,
		DATATABLE_PAGE_LENGTH: 50,
		CHECKOUT_TYPE_INLINE: '2',
		VERIFICATION_TYPE_LINK: 'link',
		AJAX_TIMEOUT: 30000,
	};

	/**
	 * Selector constants
	 */
	const SELECTORS = {
		SETTINGS_FORM: '#cev_settings_form',
		SETTINGS_SAVE_BUTTON: '.cev_settings_save',
		SNACKBAR_CONTAINER: '.cev-snackbar-logs',
		CHECKOUT_DROPDOWN: '#cev_verification_checkout_dropdown_option',
		VERIFICATION_TYPE: '#cev_verification_type',
		LOGIN_AUTH_TOGGLE: '#cev_enable_login_authentication',
		CHECKOUT_TOGGLE: '#cev_enable_email_verification_checkout',
		USER_LOG_TABLE: '#userLogTable',
		ACTIVITY_PANEL_TAB: '#activity-panel-tab-help',
		ACTIVITY_PANEL_WRAPPER: '.woocommerce-layout__activity-panel-wrapper',
		ACTIVITY_PANEL: '.woocommerce-layout__activity-panel',
		MENU_BUTTON: '.menu-button',
		POPUP_MENU: '.popup-menu',
	};

	/**
	 * CSS class constants
	 */
	const CLASSES = {
		DISABLED: 'disabled',
		ACTIVE: 'active',
		IS_ACTIVE: 'is-active',
		IS_OPEN: 'is-open',
		IS_SWITCHING: 'is-switching',
		HOVER_ROW: 'hover-row',
	};

	/**
	 * AJAX action constants
	 */
	const AJAX_ACTIONS = {
		SETTINGS_UPDATE: 'cev_settings_form_update',
		DELETE_USER: 'delete_user',
		DELETE_USERS: 'delete_users',
	};

	/**
	 * Messages constants
	 */
	const MESSAGES = {
		SETTINGS_SAVED: 'Your Settings have been successfully saved.',
		USERS_DELETED: 'Users deleted successfully.',
		USER_DELETED: 'User deleted successfully.',
		ERROR_DELETING_USERS: 'Error deleting users.',
		ERROR_DELETING_USER: 'Error deleting user.',
		NO_USERS_SELECTED: 'No users selected for deletion',
		CONFIRM_DELETE_USERS: 'Are you sure you want to delete the selected users?',
		CONFIRM_DELETE_USER: 'Are you sure you want to delete this user?',
	};

	/**
	 * Response code constants for standardized AJAX responses.
	 *
	 * @type {Object}
	 */
	const RESPONSE_CODES = {
		SETTINGS_SAVED: 'settings_saved',
		ALL_DELETED: 'all_deleted',
		PARTIAL_DELETE: 'partial_delete',
		DELETE_FAILED: 'delete_failed',
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
			return !!(response && response.success === true);
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
	 * Snackbar notification system
	 */
	const Snackbar = {
		/**
		 * Initialize snackbar container if it doesn't exist
		 *
		 * @private
		 */
		_initContainer: function() {
			if ( jQuery( SELECTORS.SNACKBAR_CONTAINER ).length === 0 ) {
				jQuery( 'body' ).append( '<section class="cev-snackbar-logs"></section>' );
			}
		},

		/**
		 * Show success snackbar message
		 *
		 * @param {string} message - Message to display
		 */
		showSuccess: function( message ) {
			this._initContainer();
			const $snackbar = jQuery( '<article></article>' )
				.addClass( 'cev-snackbar-log cev-snackbar-log-success cev-snackbar-log-show' )
				.text( message );
			jQuery( SELECTORS.SNACKBAR_CONTAINER ).append( $snackbar );
			setTimeout( function() {
				$snackbar.remove();
			}, CONFIG.SNACKBAR_DURATION );
		},

		/**
		 * Show error/warning snackbar message
		 *
		 * @param {string} message - Message to display
		 */
		showWarning: function( message ) {
			this._initContainer();
			const $snackbar = jQuery( '<article></article>' )
				.addClass( 'cev-snackbar-log cev-snackbar-log-error cev-snackbar-log-show' )
				.html( message );
			jQuery( SELECTORS.SNACKBAR_CONTAINER ).append( $snackbar );
			setTimeout( function() {
				$snackbar.remove();
			}, CONFIG.SNACKBAR_DURATION );
		},
	};

	/**
	 * jQuery plugin extensions
	 */
	jQuery.fn.zorem_snackbar = function( msg ) {
		Snackbar.showSuccess( msg );
		return this;
	};

	jQuery.fn.zorem_snackbar_warning = function( msg ) {
		Snackbar.showWarning( msg );
		return this;
	};

	/**
	 * Check if element is in viewport
	 *
	 * @returns {boolean} True if element is in viewport
	 */
	jQuery.fn.isInViewport = function() {
		const $win = jQuery( window );
		const viewport = {
			top: $win.scrollTop(),
			bottom: $win.scrollTop() + $win.height(),
		};

		const bounds = this.offset();
		if ( ! bounds ) {
			return false;
		}

		bounds.bottom = bounds.top + this.outerHeight();

		return bounds.top >= 0 && bounds.bottom <= window.innerHeight;
	};

	/**
	 * AJAX utility functions
	 */
	const AjaxUtils = {
		/**
		 * Make AJAX request with error handling
		 *
		 * @param {Object} options - AJAX options
		 * @param {string} options.url - AJAX URL
		 * @param {string} options.type - Request type (GET/POST)
		 * @param {Object} options.data - Data to send
		 * @param {Function} options.success - Success callback
		 * @param {Function} options.error - Error callback
		 * @param {string} options.dataType - Expected data type
		 */
		request: function( options ) {
			// Get AJAX URL - check multiple sources
			let ajaxUrl = '';
			if ( typeof ajaxurl !== 'undefined' ) {
				ajaxUrl = ajaxurl;
			} else if ( typeof cev_vars !== 'undefined' && cev_vars.ajax_url ) {
				ajaxUrl = cev_vars.ajax_url;
			} else {
				ajaxUrl = typeof admin_url !== 'undefined' ? admin_url( 'admin-ajax.php' ) : '/wp-admin/admin-ajax.php';
			}

			const defaults = {
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				timeout: CONFIG.AJAX_TIMEOUT,
			};

			const settings = jQuery.extend( {}, defaults, options );

			jQuery.ajax( settings ).fail( function( xhr, status, error ) {
				console.error( 'AJAX Error:', {
					status: status,
					error: error,
					response: xhr.responseText,
				} );

				if ( typeof settings.error === 'function' ) {
					settings.error( xhr, status, error );
				}
			} );
		},
	};

	/**
	 * Settings form handler
	 */
	const SettingsForm = {
		/**
		 * Initialize settings form handlers
		 */
		init: function() {
			this.handleAutoSave();
			this.handleManualSave();
		},

		/**
		 * Handle auto-save on toggle changes
		 */
		handleAutoSave: function() {
			jQuery( document ).on(
				'change',
				'#cev_enable_email_verification, #cev_enable_email_verification_checkout, #cev_enable_login_authentication, #cev_disable_wooCommerce_store_api',
				function() {
					const $form = jQuery( SELECTORS.SETTINGS_FORM );
					if ( $form.length === 0 ) {
						return;
					}

					AjaxUtils.request( {
						data: $form.serialize(),
						success: function( response ) {
							// Check for success - handle both AjaxResponseHandler and direct response checks
							const isSuccess = ( typeof AjaxResponseHandler !== 'undefined' && AjaxResponseHandler.isSuccess( response ) ) 
								|| ( response && ( response.success === true || response.success === 'true' ) );
							
							if ( isSuccess ) {
								// Extract message from response.data.message or response.message
								const responseData = ( typeof AjaxResponseHandler !== 'undefined' ) 
									? AjaxResponseHandler.getData( response ) 
									: ( response && response.data ) ? response.data : {};
								const message = ( responseData && responseData.message ) || response.message || MESSAGES.SETTINGS_SAVED;
								Snackbar.showSuccess( message );
							} else {
								const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
									? AjaxResponseHandler.getErrorMessage( response, 'Failed to save settings.' )
									: ( response && response.error && response.error.message ) ? response.error.message : 'Failed to save settings.';
								Snackbar.showWarning( errorMessage );
							}
						},
						error: function( xhr, status, error ) {
							const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
								? AjaxResponseHandler.getErrorMessage( xhr.responseJSON, 'Failed to save settings.' )
								: ( xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message ) ? xhr.responseJSON.error.message : 'Failed to save settings.';
							Snackbar.showWarning( errorMessage );
						},
					} );
				}
			);
		},

		/**
		 * Handle manual save button click
		 */
		handleManualSave: function() {
			// Handle form submission (triggered by button click or form submit)
			jQuery( document ).on( 'submit', SELECTORS.SETTINGS_FORM, function( e ) {
				e.preventDefault();
				e.stopPropagation();

				const $form = jQuery( this );
				const $button = jQuery( SELECTORS.SETTINGS_SAVE_BUTTON );
				const originalText = $button.length > 0 ? $button.text() : 'Save';

				// Prevent multiple submissions
				if ( $button.length > 0 && $button.prop( 'disabled' ) ) {
					return false;
				}

				// Update UI state
				let $spinner = null;
				if ( $button.length > 0 ) {
					$button.prop( 'disabled', true ).text( 'Saving...' );

					$spinner = jQuery( '<span class="spinner is-active" style="margin-left:6px; vertical-align:middle;"></span>' );
					$button.next( '.spinner' ).remove();
					$button.after( $spinner );
				}

				// Make AJAX request
				AjaxUtils.request( {
					data: $form.serialize(),
					success: function( response ) {
						if ( $spinner ) {
							$spinner.remove();
						}
						if ( $button.length > 0 ) {
							$button.prop( 'disabled', false ).text( originalText );
						}
						$form.find( '.spinner' ).removeClass( CLASSES.ACTIVE );
						
						// Check for success - handle both AjaxResponseHandler and direct response checks
						const isSuccess = ( typeof AjaxResponseHandler !== 'undefined' && AjaxResponseHandler.isSuccess( response ) ) 
							|| ( response && ( response.success === true || response.success === 'true' ) );
						
						if ( isSuccess ) {
							// Extract message from response.data.message or response.message
							const responseData = ( typeof AjaxResponseHandler !== 'undefined' ) 
								? AjaxResponseHandler.getData( response ) 
								: ( response && response.data ) ? response.data : {};
							const message = ( responseData && responseData.message ) || response.message || MESSAGES.SETTINGS_SAVED;
							Snackbar.showSuccess( message );
						} else {
							const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
								? AjaxResponseHandler.getErrorMessage( response, 'Failed to save settings. Please try again.' )
								: ( response && response.error && response.error.message ) ? response.error.message : 'Failed to save settings. Please try again.';
							Snackbar.showWarning( errorMessage );
						}
					},
					error: function( xhr, status, error ) {
						if ( $button.length > 0 ) {
							$spinner.remove();
							$button.prop( 'disabled', false ).text( originalText );
						}
						
						const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
							? AjaxResponseHandler.getErrorMessage( xhr.responseJSON, 'Failed to save settings. Please try again.' )
							: ( xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message ) ? xhr.responseJSON.error.message : 'Failed to save settings. Please try again.';
						Snackbar.showWarning( errorMessage );
					},
				} );

				return false;
			} );
		},
	};

	/**
	 * Field visibility and state management
	 */
	const FieldManager = {
		/**
		 * Initialize field managers
		 */
		init: function() {
			this.handleCheckoutDropdown();
			this.handleVerificationType();
			this.handleCheckoutToggle();
			this.handleLoginAuthToggle();
			this.handleCartPageVisibility();
			this.initializeCheckoutDependentFields();
			this.initializeLoginAuthDependentFields();
		},

		/**
		 * Check if create account flag is yes
		 *
		 * @param {*} flag - Flag value to check
		 * @returns {boolean} True if flag is yes/1/true
		 */
		isCreateAccountFlagYes: function( flag ) {
			if ( flag === undefined || flag === null ) {
				return false;
			}
			const flagStr = String( flag ).toLowerCase();
			return flagStr === 'yes' || flagStr === '1' || flagStr === 'true';
		},

		/**
		 * Update create account field visibility based on checkout dropdown
		 *
		 * @param {string} selectedValue - Selected dropdown value
		 */
		updateCreateAccountVisibility: function( selectedValue ) {
			const valNum = parseInt( selectedValue, 10 );
			const isInline = ! isNaN( valNum ) && valNum === 2;
			const isPopup = ! isNaN( valNum ) && valNum === 1;

			if ( isInline ) {
				// Hide cart page field and show create account field for Inline type
				jQuery( '#cev_enable_email_verification_cart_page' ).closest( 'li' ).hide();
				// Show free orders option for Inline type
				jQuery( '#cev_enable_email_verification_free_orders' ).closest( 'li' ).show();

				const $createAccountField = jQuery( '#cev_create_an_account_during_checkout' );
				if ( $createAccountField.length ) {
					const shouldShow = this.isCreateAccountFlagYes(
						typeof cev_vars !== 'undefined' ? cev_vars.cev_create_an_account_during_checkout : false
					);
					$createAccountField.closest( 'li' ).toggle( shouldShow );
				}
			} else if ( isPopup ) {
				// Hide only free orders field for Popup type, show cart page option
				jQuery( '#cev_enable_email_verification_free_orders' ).closest( 'li' ).hide();
				jQuery( '#cev_create_an_account_during_checkout' ).closest( 'li' ).hide();
				jQuery( '#cev_enable_email_verification_cart_page' ).closest( 'li' ).show();
			} else {
				// Default: show cart page, hide create account
				jQuery( '#cev_create_an_account_during_checkout' ).closest( 'li' ).hide();
				jQuery( '#cev_enable_email_verification_cart_page' ).closest( 'li' ).show();
			}
		},

		/**
		 * Handle checkout dropdown changes
		 */
		handleCheckoutDropdown: function() {
			const self = this;

			// On change
			jQuery( document ).on( 'change', SELECTORS.CHECKOUT_DROPDOWN, function() {
				self.updateCreateAccountVisibility( jQuery( this ).val() );

				// Toggle cart values visibility
				const isInline = jQuery( this ).val() === CONFIG.CHECKOUT_TYPE_INLINE;
				jQuery( '.cev_cart_values_hide' ).toggle( ! isInline );
			} );

			// Run on page load
			const initialValue = jQuery( SELECTORS.CHECKOUT_DROPDOWN ).val();
			if ( initialValue ) {
				this.updateCreateAccountVisibility( initialValue );
			}
		},

		/**
		 * Handle verification type changes
		 */
		handleVerificationType: function() {
			jQuery( document ).on( 'change', SELECTORS.VERIFICATION_TYPE, function() {
				const isLinkType = jQuery( this ).val() === CONFIG.VERIFICATION_TYPE_LINK;
				const $lengthField = jQuery( '#cev_verification_code_length' );
				const $expirationField = jQuery( '#cev_verification_code_expiration' );

				$lengthField.prop( 'disabled', isLinkType ).closest( 'li' ).toggleClass( CLASSES.DISABLED, isLinkType );
				$expirationField.prop( 'disabled', isLinkType ).closest( 'li' ).toggleClass( CLASSES.DISABLED, isLinkType );
			} );
		},

		/**
		 * Update dependent fields based on checkout verification toggle state
		 *
		 * @param {boolean} isChecked - Whether checkout verification is enabled
		 */
		updateCheckoutDependentFields: function( isChecked ) {
			const $createAccountField = jQuery( '#cev_create_an_account_during_checkout' );
			const $cartPageField = jQuery( '#cev_enable_email_verification_cart_page' );
			const $verificationTypeField = jQuery( SELECTORS.VERIFICATION_TYPE );
			const $checkoutDropdownField = jQuery( SELECTORS.CHECKOUT_DROPDOWN );
			const $freeOrdersField = jQuery( '#cev_enable_email_verification_free_orders' );

			// Handle free orders checkbox
			if ( $freeOrdersField.length ) {
				if ( ! isChecked ) {
					// Uncheck and disable when checkout verification is disabled
					$freeOrdersField.prop( 'checked', false ).prop( 'disabled', true );
					$freeOrdersField.closest( 'label' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when checkout verification is enabled
					$freeOrdersField.prop( 'disabled', false );
					$freeOrdersField.closest( 'label' ).removeClass( CLASSES.DISABLED );
				}
			}

			// Handle checkout verification type dropdown
			if ( $checkoutDropdownField.length ) {
				if ( ! isChecked ) {
					// Disable when checkout verification is disabled
					$checkoutDropdownField.prop( 'disabled', true );
					$checkoutDropdownField.closest( 'li' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when checkout verification is enabled
					$checkoutDropdownField.prop( 'disabled', false );
					$checkoutDropdownField.closest( 'li' ).removeClass( CLASSES.DISABLED );
				}
			}

			// Handle create account checkbox
			if ( $createAccountField.length ) {
				if ( ! isChecked ) {
					// Uncheck and disable when checkout verification is disabled
					$createAccountField.prop( 'checked', false ).prop( 'disabled', true );
					$createAccountField.closest( 'label' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when checkout verification is enabled
					$createAccountField.prop( 'disabled', false );
					$createAccountField.closest( 'label' ).removeClass( CLASSES.DISABLED );
				}
			}

			// Handle cart page checkbox
			if ( $cartPageField.length ) {
				if ( ! isChecked ) {
					// Uncheck and disable when checkout verification is disabled
					$cartPageField.prop( 'checked', false ).prop( 'disabled', true );
					$cartPageField.closest( 'label' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when checkout verification is enabled
					$cartPageField.prop( 'disabled', false );
					$cartPageField.closest( 'label' ).removeClass( CLASSES.DISABLED );
				}
			}

			// Handle verification type dropdown
			if ( $verificationTypeField.length ) {
				if ( ! isChecked ) {
					// Disable when checkout verification is disabled
					$verificationTypeField.prop( 'disabled', true );
					$verificationTypeField.closest( 'li' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when checkout verification is enabled
					$verificationTypeField.prop( 'disabled', false );
					$verificationTypeField.closest( 'li' ).removeClass( CLASSES.DISABLED );
				}
			}
		},

		/**
		 * Handle checkout toggle changes
		 */
		handleCheckoutToggle: function() {
			const self = this;
			jQuery( document ).on( 'change', SELECTORS.CHECKOUT_TOGGLE, function() {
				const isChecked = jQuery( this ).prop( 'checked' );
				self.updateCheckoutDependentFields( isChecked );
			} );
		},

		/**
		 * Update dependent fields based on login authentication toggle state
		 *
		 * @param {boolean} isChecked - Whether login authentication is enabled
		 */
		updateLoginAuthDependentFields: function( isChecked ) {
				const fieldsToToggle = [
					'#enable_email_otp_for_account',
					'#enable_email_auth_for_new_device',
					'#enable_email_auth_for_new_location',
					'#enable_email_auth_for_login_time',
				];

				fieldsToToggle.forEach( function( selector ) {
				const $field = jQuery( selector );
				if ( ! isChecked ) {
					// Uncheck and disable when login authentication is disabled
					$field.prop( 'checked', false ).prop( 'disabled', true );
					$field.closest( 'label' ).addClass( CLASSES.DISABLED );
				} else {
					// Enable when login authentication is enabled
					$field.prop( 'disabled', false );
					$field.closest( 'label' ).removeClass( CLASSES.DISABLED );
				}
			} );

			// Handle dropdown inside checkbox_select
			const $lastLoginTime = jQuery( '#cev_last_login_more_then_time' );
			if ( ! isChecked ) {
				$lastLoginTime.prop( 'disabled', true );
			} else {
				$lastLoginTime.prop( 'disabled', false );
			}

				jQuery( 'p#login_auth_desc' ).toggleClass( CLASSES.DISABLED, ! isChecked );
		},

		/**
		 * Handle login authentication toggle changes
		 */
		handleLoginAuthToggle: function() {
			const self = this;
			jQuery( document ).on( 'change', SELECTORS.LOGIN_AUTH_TOGGLE, function() {
				const isChecked = jQuery( this ).prop( 'checked' );
				self.updateLoginAuthDependentFields( isChecked );
			} );
		},

		/**
		 * Handle cart page visibility on page load
		 */
		handleCartPageVisibility: function() {
			jQuery( document ).ready( function() {
				const checkoutValue = jQuery( SELECTORS.CHECKOUT_DROPDOWN ).val();
				if ( checkoutValue === CONFIG.CHECKOUT_TYPE_INLINE ) {
					jQuery( '#cev_enable_email_verification_cart_page' ).closest( 'li' ).hide();
				}
			} );
		},

		/**
		 * Initialize checkout verification dependent fields on page load
		 */
		initializeCheckoutDependentFields: function() {
			const self = this;
			jQuery( document ).ready( function() {
				const isChecked = jQuery( SELECTORS.CHECKOUT_TOGGLE ).prop( 'checked' );
				self.updateCheckoutDependentFields( isChecked );
			} );
		},

		/**
		 * Initialize login authentication dependent fields on page load
		 */
		initializeLoginAuthDependentFields: function() {
			const self = this;
			jQuery( document ).ready( function() {
				const isChecked = jQuery( SELECTORS.LOGIN_AUTH_TOGGLE ).prop( 'checked' );
				self.updateLoginAuthDependentFields( isChecked );
			} );
		},
	};


	/**
	 * User log table management
	 */
	const UserLogTable = {
		table: null,

		/**
		 * Initialize DataTable
		 */
		init: function() {
			const $table = jQuery( SELECTORS.USER_LOG_TABLE );
			if ( $table.length === 0 ) {
				return;
			}

			this.table = $table.DataTable( {
				searching: false,
				lengthChange: false,
				pageLength: CONFIG.DATATABLE_PAGE_LENGTH,
				columnDefs: [
					{
						orderable: false,
						targets: [ 0, 1, 2, 3 ],
					},
					{
						width: '20px',
						targets: 0,
					},
					{
						className: 'text-right',
						targets: -1,
					},
					{
						targets: '_all',
						createdCell: function( td, cellData, rowData, row, col ) {
							if ( col === 3 ) {
								jQuery( td ).css( 'text-align', 'right' );
							}
							if ( row === 0 ) {
								jQuery( td ).css( 'width', col === 0 ? '20px' : '300px' );
							}
						},
					},
				],
				rowCallback: function( row ) {
					jQuery( row ).hover(
						function() {
							jQuery( this ).addClass( CLASSES.HOVER_ROW );
						},
						function() {
							jQuery( this ).removeClass( CLASSES.HOVER_ROW );
						}
					);
				},
			} );

			this.handleTabNavigation();
			this.handleSelectAll();
			this.handleBulkActions();
			this.handleDeleteButton();
		},

		/**
		 * Handle tab navigation
		 */
		handleTabNavigation: function() {
			jQuery( document ).on( 'click', '.cev_tab_input', function() {
				const tab = jQuery( this ).data( 'tab' );
				const url =
					window.location.protocol +
					'//' +
					window.location.host +
					window.location.pathname +
					'?page=customer-email-verification-for-woocommerce&tab=' +
					tab;
				window.history.pushState( { path: url }, '', url );
			} );
		},

		/**
		 * Handle select all checkbox
		 */
		handleSelectAll: function() {
			const selectAll = document.getElementById( 'select_all' );
			if ( selectAll ) {
				selectAll.addEventListener( 'click', function() {
					const checkboxes = document.querySelectorAll( '.row_checkbox' );
					checkboxes.forEach( function( checkbox ) {
						checkbox.checked = this.checked;
					}, this );
				} );
			}
		},

		/**
		 * Handle bulk actions
		 */
		handleBulkActions: function() {
			const self = this;
			jQuery( '.apply_bulk_action' ).on( 'click', function() {
				const action = jQuery( '#bulk_action' ).val();
				if ( action === 'delete' ) {
					const selectedIds = [];
					jQuery( '.row_checkbox:checked' ).each( function() {
						selectedIds.push( jQuery( this ).val() );
					} );

					if ( selectedIds.length > 0 ) {
						if ( confirm( MESSAGES.CONFIRM_DELETE_USERS ) ) {
							self.deleteUsers( selectedIds );
						}
					} else {
						alert( MESSAGES.NO_USERS_SELECTED );
					}
				}
			} );
		},

		/**
		 * Handle delete button click
		 */
		handleDeleteButton: function() {
			const self = this;
			jQuery( document ).on( 'click', '.delete_button', function() {
				const $button = jQuery( this );
				const userId = $button.data( 'id' );

				if ( confirm( MESSAGES.CONFIRM_DELETE_USER ) ) {
					self.deleteUser( userId, $button );
				}
			} );
		},

		/**
		 * Delete single user
		 *
		 * @param {string} userId - User ID to delete
		 * @param {jQuery} $button - Delete button element
		 */
		deleteUser: function( userId, $button ) {
			if ( ! this.table ) {
				return;
			}

			AjaxUtils.request( {
				url: typeof cev_vars !== 'undefined' ? cev_vars.ajax_url : ajaxurl,
				data: {
					action: AJAX_ACTIONS.DELETE_USER,
					nonce: typeof cev_vars !== 'undefined' ? cev_vars.delete_user_nonce : '',
					id: userId,
				},
				success: ( response ) => {
					if ( typeof AjaxResponseHandler !== 'undefined' && AjaxResponseHandler.isSuccess( response ) ) {
						this.table.row( $button.closest( 'tr' ) ).remove().draw();
						const message = response.message || MESSAGES.USER_DELETED;
						Snackbar.showSuccess( message );
					} else {
						const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
							? AjaxResponseHandler.getErrorMessage( response, MESSAGES.ERROR_DELETING_USER )
							: ( response && response.error && response.error.message ) ? response.error.message : MESSAGES.ERROR_DELETING_USER;
						Snackbar.showWarning( errorMessage );
					}
				},
				error: ( xhr, status, error ) => {
					const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
						? AjaxResponseHandler.getErrorMessage( xhr.responseJSON, MESSAGES.ERROR_DELETING_USER )
						: ( xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message ) ? xhr.responseJSON.error.message : MESSAGES.ERROR_DELETING_USER;
					Snackbar.showWarning( errorMessage );
				},
			} );
		},

		/**
		 * Delete multiple users
		 *
		 * @param {Array} selectedIds - Array of user IDs to delete
		 */
		deleteUsers: function( selectedIds ) {
			if ( ! this.table ) {
				return;
			}

			AjaxUtils.request( {
				url: typeof cev_vars !== 'undefined' ? cev_vars.ajax_url : ajaxurl,
				data: {
					action: AJAX_ACTIONS.DELETE_USERS,
					nonce: typeof cev_vars !== 'undefined' ? cev_vars.delete_user_nonce : '',
					ids: selectedIds,
				},
				success: ( response ) => {
					if ( typeof AjaxResponseHandler !== 'undefined' && AjaxResponseHandler.isSuccess( response ) ) {
						// Remove successfully deleted rows
						const responseData = AjaxResponseHandler.getData( response );
						if ( responseData.deletedIds ) {
							responseData.deletedIds.forEach( ( id ) => {
								this.table.row( jQuery( 'input[value="' + id + '"]' ).closest( 'tr' ) ).remove().draw();
							} );
						} else {
							// Fallback: remove all selected IDs
							selectedIds.forEach( ( id ) => {
								this.table.row( jQuery( 'input[value="' + id + '"]' ).closest( 'tr' ) ).remove().draw();
							} );
						}
						const message = response.message || MESSAGES.USERS_DELETED;
						Snackbar.showSuccess( message );
					} else {
						const responseData = ( typeof AjaxResponseHandler !== 'undefined' ) ? AjaxResponseHandler.getData( response ) : ( response && response.data ) ? response.data : {};
						let errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
							? AjaxResponseHandler.getErrorMessage( response, MESSAGES.ERROR_DELETING_USERS )
							: ( response && response.error && response.error.message ) ? response.error.message : MESSAGES.ERROR_DELETING_USERS;
						if ( errorMessage === MESSAGES.ERROR_DELETING_USERS && responseData.messages && responseData.messages.length > 0 ) {
							errorMessage = responseData.messages[0];
						}
						Snackbar.showWarning( errorMessage );
					}
				},
				error: ( xhr, status, error ) => {
					const errorMessage = ( typeof AjaxResponseHandler !== 'undefined' )
						? AjaxResponseHandler.getErrorMessage( xhr.responseJSON, MESSAGES.ERROR_DELETING_USERS )
						: ( xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message ) ? xhr.responseJSON.error.message : MESSAGES.ERROR_DELETING_USERS;
					Snackbar.showWarning( errorMessage );
				},
			} );
		},
	};

	/**
	 * Activity panel handler
	 */
	const ActivityPanel = {
		/**
		 * Initialize activity panel handlers
		 */
		init: function() {
			jQuery( document ).on( 'click', SELECTORS.ACTIVITY_PANEL_TAB, function() {
				jQuery( this ).addClass( CLASSES.IS_ACTIVE );
				jQuery( SELECTORS.ACTIVITY_PANEL_WRAPPER ).addClass( CLASSES.IS_OPEN + ' ' + CLASSES.IS_SWITCHING );
			} );

			jQuery( document ).on( 'click', function( event ) {
				const $trigger = jQuery( SELECTORS.ACTIVITY_PANEL );
				if ( ! $trigger.is( event.target ) && ! $trigger.has( event.target ).length ) {
					jQuery( SELECTORS.ACTIVITY_PANEL_TAB ).removeClass( CLASSES.IS_ACTIVE );
					jQuery( SELECTORS.ACTIVITY_PANEL_WRAPPER ).removeClass( CLASSES.IS_OPEN + ' ' + CLASSES.IS_SWITCHING );
				}
			} );
		},
	};

	/**
	 * Popup menu handler
	 */
	const PopupMenu = {
		/**
		 * Initialize popup menu
		 */
		init: function() {
			const menuButton = document.querySelector( SELECTORS.MENU_BUTTON );
			const popupMenu = document.querySelector( SELECTORS.POPUP_MENU );

			if ( ! menuButton || ! popupMenu ) {
				return;
			}

			// Toggle menu visibility on button click
			menuButton.addEventListener( 'click', function( e ) {
				e.preventDefault();
				e.stopPropagation();
				const isVisible = popupMenu.style.display === 'block';
				popupMenu.style.display = isVisible ? 'none' : 'block';
			} );

			// Close menu when clicking outside
			document.addEventListener( 'click', function( e ) {
				if ( menuButton && popupMenu && ! menuButton.contains( e.target ) && ! popupMenu.contains( e.target ) ) {
					popupMenu.style.display = 'none';
				}
			} );
		},
	};

	/**
	 * Miscellaneous handlers
	 */
	const MiscHandlers = {
		/**
		 * Initialize miscellaneous handlers
		 */
		init: function() {
			this.handleDelayAccountCheck();
			this.initTooltips();
		},

		/**
		 * Handle delay account check radio buttons
		 */
		handleDelayAccountCheck: function() {
			jQuery( document ).on( 'click', '.delay_account_check', function() {
				if ( jQuery( this ).prop( 'checked' ) === true ) {
					jQuery( '.delay_account_check' ).prop( 'checked', false );
					jQuery( this ).prop( 'checked', true );
				}
			} );
		},

		/**
		 * Initialize tooltips
		 */
		initTooltips: function() {
			jQuery( document ).ready( function() {
				if ( typeof jQuery.fn.tipTip !== 'undefined' ) {
					jQuery( '.woocommerce-help-tip' ).tipTip();
				}
			} );
		},
	};

	/**
	 * Main initialization
	 */
	jQuery( document ).ready( function() {
		SettingsForm.init();
		FieldManager.init();
		UserLogTable.init();
		ActivityPanel.init();
		PopupMenu.init();
		MiscHandlers.init();
	} );
})( jQuery );
