/* global affiliate_wp_cookie_consent */

/**
 * Handles installation of the WPConsent plugin on the Cookie Consent product recommendation page.
 *
 * @since 2.32.0
 */

( function( document, window, $ ) {
	'use strict';

	/**
	 * Elements.
	 *
	 * @since 2.32.0
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 2.32.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.32.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.32.0
		 */
		ready: function() {

			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 2.32.0
		 */
		initVars: function() {

			el = {
				$stepInstall: $( 'section.step-install' ),
				$stepInstallNum: $( 'section.step-install .num img' ),
				$stepSetup: $( 'section.step-setup' ),
				$stepSetupNum: $( 'section.step-setup .num img' )
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 2.32.0
		 */
		events: function() {

			// Step 'Install' button click.
			el.$stepInstall.on( 'click', 'button', app.stepInstallClick );

			// Step 'Setup' button click.
			el.$stepSetup.on( 'click', 'button', app.gotoURL );

			// Close lightbox when clicking the image.
			$( document ).on( 'click', '.lity-content', function() {
				$( this ).closest( '.lity' ).find( '.lity-close' ).trigger( 'click' );
			} );
		},

		/**
		 * Step 'Install' button click.
		 *
		 * @since 2.32.0
		 */
		stepInstallClick: function() {

			var $btn       = $( this ),
				action     = $btn.attr( 'data-action' ),
				plugin     = $btn.attr( 'data-plugin' ),
				ajaxAction = '';

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			switch ( action ) {
				case 'activate':
					ajaxAction = 'affwp_activate_plugin';
					$btn.text( affiliate_wp_cookie_consent.activating );
					break;

				case 'install':
					ajaxAction = 'affwp_install_plugin';
					$btn.text( affiliate_wp_cookie_consent.installing );
					break;

				case 'goto-url':
					window.location.href = $btn.attr( 'data-url' );
					return;

				default:
					return;
			}

			$btn.addClass( 'disabled' );
			app.showSpinner( el.$stepInstallNum );

			$.post(
				affiliate_wp_cookie_consent.ajax_url,
				{
					action: ajaxAction,
					nonce: affiliate_wp_cookie_consent.nonce,
					plugin: plugin
				}
			)
				.done(
					function( res ) {
						app.stepInstallDone( res, $btn, action );
					}
				)
				.always(
					function() {
						app.hideSpinner( el.$stepInstallNum );
					}
				);
		},

		/**
		 * Done part of the step 'Install'.
		 *
		 * @since 2.32.0
		 *
		 * @param {object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepInstallClick() function).
		 */
		stepInstallDone: function( res, $btn, action ) {

			if ( 'install' === action ? res.success && res.data.is_activated : res.success ) {

				el.$stepInstallNum.attr(
					'src',
					el.$stepInstallNum
						.attr( 'src' )
						.replace( 'step-1.', 'step-complete.' )
				);

				$btn.addClass( 'grey' )
					.removeClass( 'button-primary' )
					.text( affiliate_wp_cookie_consent.activated );

				// Enable Step 2.
				el.$stepSetup.removeClass( 'grey' );
				el.$stepSetup.find( 'button' )
					.removeClass( 'grey disabled' )
					.addClass( 'button-primary' )
					.text( affiliate_wp_cookie_consent.launch_setup_wizard );

				return;
			}

			var activationFail = ('install' === action && res.success && ! res.data.is_activated) || 'activate' === action,
				url            = ! activationFail ? affiliate_wp_cookie_consent.manual_install_url : affiliate_wp_cookie_consent.manual_activate_url,
				msg            = ! activationFail ? affiliate_wp_cookie_consent.error_could_not_install : affiliate_wp_cookie_consent.error_could_not_activate,
				btn            = ! activationFail ? affiliate_wp_cookie_consent.download_now : affiliate_wp_cookie_consent.plugins_page;

			$btn.removeClass( 'grey disabled' )
				.text( btn )
				.attr( 'data-action', 'goto-url' )
				.attr( 'data-url', url )
				.after( '<p class="error">' + msg + '</p>' );
		},

		/**
		 * Go to URL by click on the button.
		 *
		 * @since 2.32.0
		 */
		gotoURL: function() {

			var $btn = $( this );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			window.location.href = $btn.attr( 'data-url' );
		},

		/**
		 * Display spinner.
		 *
		 * @since 2.32.0
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		showSpinner: function( $el ) {

			$el.siblings( '.loader' )
				.removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 2.32.0
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		hideSpinner: function( $el ) {

			$el.siblings( '.loader' )
				.addClass( 'hidden' );
		}
	};

	// Provide access to public functions/properties.
	return app;

} ( document, window, jQuery ) ).init();
