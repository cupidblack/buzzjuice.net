/* global affwp_notifications_vars, fetch, document, window, Alpine */

/**
 * Handles the in-plugin notifications inbox.
 *
 * Sets up AlpineJS store and methods for notifications.
 *
 * @since 2.9.5
 */

document.addEventListener( 'alpine:init', function() {
	/**
	 * Notifications panel handler.
	 */
	Alpine.store( 'affwpNotifications', {
		/**
		 * Checks if the panel is open.
		 *
		 * @since 2.9.5
		 *
		 * @type {boolean}
		 */
		isPanelOpen: false,

		/**
		 * Checks if notifications are loaded.
		 *
		 * @since 2.9.5
		 *
		 * @type {boolean}
		 */
		notificationsLoaded: false,

		/**
		 * Gets the number of active notifications.
		 *
		 * @since 2.9.5
		 *
		 * @type {number}
		 */
		numberActiveNotifications: 0,

		/**
		 * Active notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type {Array}
		 */
		activeNotifications: [],

		/**
		 * Inactive notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type {Array}
		 */
		inactiveNotifications: [],

		/**
		 * Whether all notifications are being dismissed.
		 *
		 * @since 2.30.0
		 *
		 * @type {boolean}
		 */
		isDismissingAll: false,

		/**
		 * Init.
		 *
		 * Initializes the AlpineJS instance.
		 *
		 * @since 2.9.5
		 */
		init() {
			/*
			 * The bubble starts out hidden until AlpineJS is initialized. Once it is, we remove
			 * the hidden class. This prevents a flash of the bubble's visibility in the event that there
			 * are no notifications.
			 */
			const notificationCountBubble = document.querySelector( '#affwp-notification-button .affwp-number' );

			if ( notificationCountBubble ) {
				notificationCountBubble.classList.remove( 'affwp-hidden' );
			}

			document.addEventListener( 'keydown', ( e ) => {
				if ( e.key !== 'Escape' ) {
					return;
				}

				this.closePanel();
			} );

			// Clicking the Notifications sub-menu opens the panel directly
			// on the current page instead of navigating away.
			const notifMenuLink = document.querySelector(
				'#toplevel_page_affiliate-wp a[href*="affiliate-wp-notifications"]'
			);

			if ( notifMenuLink ) {
				notifMenuLink.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.openPanel();
				} );
			}
		},

		/**
		 * API Request.
		 *
		 * Gets the data for the notifications.
		 *
		 * @since 2.9.5
		 *
		 * @param {string} endpoint An endpoint, e.g. /notifications.
		 * @param {string} method   The method: POST, PUT, DELETE, GET, PATCH.
		 */
		apiRequest( endpoint, method ) {
			return fetch( affwp_notifications_vars.restBase + endpoint, {
				method,
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': affwp_notifications_vars.restNonce,
				},
			} ).then( ( response ) => {
				if ( ! response.ok ) {
					return Promise.reject( response );
				}

				/*
				 * Returning response.text() instead of response.json() because dismissing
				 * a notification doesn't return a JSON response, so response.json() will break.
				 */
				return response.text();
			} ).then( ( data ) => {
				return data ? JSON.parse( data ) : null;
			} );
		},

		/**
		 * Open panel.
		 *
		 * Opens the panel and gets notification data.
		 *
		 * @since 2.9.5
		 * @since 2.32.0 Added body class management for Notifications sub-menu.
		 */
		openPanel() {
			const panelHeader = document.getElementById( 'affwp-notifications-header' );

			this.isPanelOpen = true;

			if ( this.notificationsLoaded && panelHeader ) {
				panelHeader.focus();
				return;
			}

			// Request notification data.
			this.apiRequest( '/notifications', 'GET' )
				.then( ( data ) => {
					this.activeNotifications = data.active;
					this.numberActiveNotifications = this.activeNotifications.length;
					this.inactiveNotifications = data.dismissed;
					this.notificationsLoaded = true;

					if ( panelHeader ) {
						panelHeader.focus();
					}

					} )
				.catch( ( error ) => {
				if ( window.console && console.error ) {
					console.error( 'AffiliateWP: Failed to fetch notifications.', error );
				}
			} );
		},

		/**
		 * Close panel.
		 *
		 * Closes the panel.
		 *
		 * @since 2.9.5
		 */
		closePanel() {
			if ( ! this.isPanelOpen ) {
				return;
			}

			this.isPanelOpen = false;

			const notificationButton = document.getElementById( 'affwp-notification-button' );

			if ( ! notificationButton ) {
				return;
			}

			notificationButton.focus();
		},

		/**
		 * Dismiss.
		 *
		 * Dismisses a notification from the inbox.
		 *
		 * @since 2.9.5
		 * @since 2.32.0 Added body class removal and non-dismissible notification support.
		 *
		 * @param {Object} event The event object.
		 * @param {number} index The index of the notification to be removed.
		 */
		dismiss( event, index ) {
			if ( 'undefined' === typeof this.activeNotifications[ index ] ) {
				return;
			}

			const notification = this.activeNotifications[ index ];

			// Non-dismissible notifications cannot be dismissed.
			if ( false === notification.dismissible ) {
				return;
			}

			event.target.disabled = true;

			// Remove notification from the active list.
			this.apiRequest( '/notifications/' + notification.id, 'DELETE' )
				.then( () => {
					this.activeNotifications.splice( index, 1 );
					this.numberActiveNotifications = this.activeNotifications.length;

					// Hide the Notifications sub-menu when no notifications remain.
					if ( 0 === this.numberActiveNotifications ) {
						this.hideNotificationsMenu();
					}
				} )
				.catch( () => {
					event.target.disabled = false;
				} );
		},

		/**
		 * Dismiss all active notifications at once.
		 *
		 * Non-dismissible notifications (e.g. sunset warnings) are preserved.
		 *
		 * @since 2.30.0
		 * @since 2.32.0 Preserves non-dismissible notifications after bulk dismiss.
		 *
		 * @param {Object} event The event object.
		 */
		dismissAll( event ) {
			if (
				this.isDismissingAll ||
				! this.activeNotifications.length
			) {
				return;
			}

			this.isDismissingAll = true;

			if ( event && event.target ) {
				event.target.disabled = true;
			}

			this.apiRequest( '/notifications', 'DELETE' )
				.then( () => {
					// Keep any non-dismissible notifications (e.g. sunset warnings).
					const pinned = this.activeNotifications.filter(
						( n ) => false === n.dismissible
					);

					this.activeNotifications = pinned;
					this.numberActiveNotifications = pinned.length;

					if ( 0 === this.numberActiveNotifications ) {
						this.hideNotificationsMenu();
					}
				} )
				.catch( ( error ) => {
					if ( window.console && console.error ) {
						console.error( 'AffiliateWP notifications: dismiss-all request failed', error );
					}
				} )
				.finally( () => {
					this.isDismissingAll = false;

					if ( event && event.target ) {
						event.target.disabled = false;
					}
				} );
		},

		/**
		 * Initialize notifications and optionally auto-open the panel.
		 *
		 * Called from the notification button's x-init to set the initial count.
		 * If the user landed on the Notifications page directly, redirects to
		 * the Overview page and auto-opens the panel there. Also auto-opens
		 * on the Overview page when there are active notifications.
		 *
		 * @since 2.32.0
		 *
		 * @param {number} numberActiveNotifications The initial active notification count.
		 */
		onInit( numberActiveNotifications ) {
			this.numberActiveNotifications = numberActiveNotifications;

			// If the user landed on the Notifications page directly (e.g. bookmark
			// or before JS could intercept the click), redirect to Overview and
			// auto-open the panel there so they never see a blank page.
const isNotificationsPage = new URLSearchParams( window.location.search ).get( 'page' ) === 'affiliate-wp-notifications';

			if ( isNotificationsPage ) {
				if ( numberActiveNotifications ) {
					window.location.replace( affwp_notifications_vars.overviewUrl + '#notifications' );
				} else {
					window.location.replace( affwp_notifications_vars.overviewUrl );
				}
				return;
			}

			// Auto-open when arriving via the #notifications hash (set by the redirect above)
			// or when landing on the Overview page with active notifications.
			const isOverviewPage = new URLSearchParams( window.location.search ).get( 'page' ) === 'affiliate-wp';
			const shouldAutoOpen = '#notifications' === window.location.hash || isOverviewPage;

			if ( shouldAutoOpen && numberActiveNotifications ) {
				// Clean up the hash so a page refresh doesn't re-open.
				if ( '#notifications' === window.location.hash ) {
					history.replaceState( null, '', window.location.pathname + window.location.search );
				}

				this.openPanel();
			}
		},

		/**
		 * Hides the Notifications sub-menu item from the sidebar.
		 *
		 * Called when all notifications have been dismissed so the menu
		 * item disappears without requiring a page reload.
		 *
		 * @since 2.32.0
		 */
		hideNotificationsMenu() {
			const link = document.querySelector(
				'#toplevel_page_affiliate-wp a[href*="affiliate-wp-notifications"]'
			);

			if ( link && link.parentElement ) {
				link.parentElement.style.display = 'none';
			}
		},
	} );
} );
