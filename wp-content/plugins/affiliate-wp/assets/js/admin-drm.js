/* global affwpDrm */
/**
 * AffiliateWP DRM.
 *
 * Renders the DRM panel inline as a positioned div. No <dialog>,
 * no modal API, no z-index conflicts with the WP sidebar.
 *
 * @since 2.21.1
 * @since 2.32.0 Migrated to inline panel.
 */

'use strict';

( function() {

	const DISMISS_KEY      = 'affwp_drm_med_dismiss';
	const DISMISS_DURATION = 86400000;

	function isDismissed() {
		const ts = localStorage.getItem( DISMISS_KEY );
		return ts && ( Date.now() - parseInt( ts, 10 ) ) < DISMISS_DURATION;
	}

	function init() {

		const { level } = affwpDrm;
		const panel     = document.getElementById( 'affwp-drm-panel' );

		if ( ! panel ) {
			return;
		}

		// For med_level, hide if recently dismissed.
		if ( level === 'med_level' && isDismissed() ) {
			return;
		}

		// Add blur class for med_level.
		if ( level === 'med_level' ) {
			document.body.classList.add( 'affwp-drm-level-med_level' );
		}

		// Open the dialog (non-modal — sidebar stays interactive).
		panel.show();
		panel.classList.remove( 'hidden' );
		panel.classList.add( 'flex' );

		// Offset the panel to avoid covering the sidebar and admin bar.
		function updatePanelOffset() {
			const sidebar = document.getElementById( 'adminmenuwrap' );
			const toolbar = document.getElementById( 'wpadminbar' );

			panel.style.left = sidebar ? sidebar.offsetWidth + 'px' : '0';
			panel.style.top  = toolbar ? toolbar.offsetHeight + 'px' : '0';
		}

		updatePanelOffset();
		window.addEventListener( 'resize', updatePanelOffset );

		// Close button (med_level only).
		const closeBtn = document.getElementById( 'affwp-drm-close' );

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function() {
				closePanel( panel );
			} );
		}

		// Escape key.
		document.addEventListener( 'keydown', function( e ) {

			if ( e.key !== 'Escape' || ! panel.open ) {
				return;
			}

			if ( level === 'locked' ) {
				e.preventDefault();
				shakeCard( panel );
			} else {
				closePanel( panel );
			}
		} );

		// Backdrop click — click on the overlay (not the card).
		panel.addEventListener( 'pointerdown', function( e ) {

			// Only if clicking the outer wrapper or the overlay, not the card.
			const card = panel.querySelector( '.bg-white' );

			if ( card && card.contains( e.target ) ) {
				return;
			}

			if ( level === 'locked' ) {
				shakeCard( panel );
			} else {
				closePanel( panel );
			}
		} );

		// Focus the CTA (skip the close button).
		const focusTarget = panel.querySelector( 'a[href], input:not([disabled])' );

		if ( focusTarget ) {
			focusTarget.focus();
		}

		// Toggle "Already have a license key?" form.
		const toggleBtn = document.getElementById( 'affwp-drm-toggle-license-form' );

		if ( toggleBtn ) {
			toggleBtn.addEventListener( 'click', function() {
				const wrapper = panel.querySelector( '[data-license-form-wrapper]' );
				const icon    = toggleBtn.querySelector( 'svg' );

				if ( wrapper ) {
					wrapper.hidden = ! wrapper.hidden;
					icon.style.transform = wrapper.hidden ? '' : 'rotate(180deg)';
				}
			} );
		}

		handleLicenseForm( panel );
	}

	function closePanel( panel ) {
		panel.close();
		panel.classList.remove( 'flex' );
		panel.classList.add( 'hidden' );
		localStorage.setItem( DISMISS_KEY, Date.now().toString() );
		document.body.classList.remove( 'affwp-drm-level-med_level' );
	}

	function shakeCard( panel ) {
		const card = panel.querySelector( '.bg-white' );

		if ( ! card ) {
			return;
		}

		card.classList.remove( 'affwp-modal-shake' );
		void card.offsetWidth;
		card.classList.add( 'affwp-modal-shake' );
	}

	function handleLicenseForm( panel ) {

		panel.addEventListener( 'submit', function( e ) {

			if ( ! e.target.matches( '#affwp-drm-ajax-license-activation' ) ) {
				return;
			}

			e.preventDefault();

			const form       = e.target;
			const btn        = form.querySelector( 'button[type="submit"]' );
			const msgEl      = document.getElementById( 'affwp-drm-ajax-messages' );
			const licenseKey = form.querySelector( 'input[name="license_key"]' ).value;

			btn.disabled = true;

			fetch( affwpDrm.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams( {
					action: 'affiliatewp_handle_license_form_submission',
					nonce: affwpDrm.nonce,
					license_key: licenseKey,
				} ),
			} )
				.then( ( r ) => r.json() )
				.then( ( response ) => {

					if ( ! response.success ) {
						showMessage( msgEl, affwpDrm.strings.error, 'error' );
						return;
					}

					const status = response?.data?.license_data?.license;

					switch ( status ) {
						case 'valid':
							showMessage( msgEl, affwpDrm.strings.success, 'success' );
							setTimeout( () => {
								localStorage.removeItem( DISMISS_KEY );
								location.reload();
							}, 3000 );
							break;

						case 'expired':
							showMessage( msgEl, affwpDrm.strings.expired, 'error' );
							break;

						case 'invalid':
							showMessage( msgEl, affwpDrm.strings.invalid, 'error' );
							break;

						default:
							showMessage( msgEl, affwpDrm.strings.error, 'error' );
					}
				} )
				.catch( () => {
					showMessage( msgEl, affwpDrm.strings.error, 'error' );
				} )
				.finally( () => {
					btn.disabled = false;
				} );
		} );
	}

	function showMessage( el, message, type ) {
		if ( ! el ) {
			return;
		}
		el.className = 'mt-2 text-sm ' + ( type === 'success' ? 'text-green-600' : 'text-red-600' );
		el.innerHTML = message;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
