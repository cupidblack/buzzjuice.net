/**
 * Global Modal Management System
 *
 * Provides centralized modal management using Alpine.store
 * Handles modal lifecycle, focus trapping, and keyboard navigation
 *
 * @since 2.29.0
 */

/* global Alpine, setTimeout */

document.addEventListener( 'alpine:init', () => {
	// Register the global modal store.
	Alpine.store( 'modals', {
		// Stack of currently open modals.
		activeModals: [],

		// Registry of all available modals.
		registry: {},

		// Currently focused element before modal opened.
		previousFocus: null,

		/**
		 * Initialize the modal system.
		 */
		init() {
			// Set up global keyboard handlers.
			document.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Escape' && this.activeModals.length > 0 ) {
					const topModal = this.activeModals[ this.activeModals.length - 1 ];
					if ( topModal && topModal.closeOnEscape !== false ) {
						e.preventDefault();
						this.close( topModal.id );
					}
				}
			} );

			// Register any modals that are already in the DOM.
			this.registerExistingModals();
		},

		/**
		 * Register a modal configuration.
		 *
		 * @param {string} id     - Unique modal identifier.
		 * @param {Object} config - Modal configuration.
		 */
		register( id, config = {} ) {
			this.registry[ id ] = {
				id,
				isOpen: false,
				closeOnEscape: config.closeOnEscape !== false,
				closeOnBackdrop: config.closeOnBackdrop !== false,
			};
		},

		/**
		 * Open a modal by ID.
		 *
		 * @param {string} id   - Modal ID to open.
		 * @param {Object} data - Optional data to pass to modal.
		 */
		open( id, data = {} ) {
			const modal = this.registry[ id ];

			if ( ! modal ) {
				// Silently fail if modal not registered.
				return;
			}

			// Store current focus.
			if ( this.activeModals.length === 0 ) {
				this.previousFocus = document.activeElement;
			}

			// Update modal state.
			modal.isOpen = true;
			modal.data = data;

			// Add to active stack.
			this.activeModals.push( modal );

			// Dispatch custom event.
			window.dispatchEvent(
				new CustomEvent( 'affwp:modal:open', {
					detail: { id, data },
				} ),
			);

			// Focus the modal after a tick.
			setTimeout( () => {
				this.focusModal( id );
			}, 0 );
		},

		/**
		 * Close a modal by ID.
		 *
		 * @param {string} id - Modal ID to close.
		 */
		close( id ) {
			const modal = this.registry[ id ];

			if ( ! modal || ! modal.isOpen ) {
				return;
			}

			// Update modal state.
			modal.isOpen = false;

			// Remove from active stack.
			const index = this.activeModals.findIndex( ( m ) => m.id === id );
			if ( index > -1 ) {
				this.activeModals.splice( index, 1 );
			}

			// Dispatch custom event.
			window.dispatchEvent(
				new CustomEvent( 'affwp:modal:close', {
					detail: { id },
				} ),
			);

			// Restore focus if no more modals.
			if ( this.activeModals.length === 0 && this.previousFocus ) {
				this.previousFocus.focus();
				this.previousFocus = null;
			} else if ( this.activeModals.length > 0 ) {
				// Focus the next modal in stack.
				const nextModal = this.activeModals[ this.activeModals.length - 1 ];
				setTimeout( () => {
					this.focusModal( nextModal.id );
				}, 0 );
			}
		},

		/**
		 * Close all open modals.
		 */
		closeAll() {
			// Close modals in reverse order.
			[ ...this.activeModals ].reverse().forEach( ( modal ) => {
				this.close( modal.id );
			} );
		},

		/**
		 * Check if a modal is open.
		 *
		 * @param {string} id - Modal ID to check.
		 * @return {boolean} True if modal is open.
		 */
		isOpen( id ) {
			return this.registry[ id ]?.isOpen || false;
		},

		/**
		 * Focus the first focusable element in a modal.
		 *
		 * @param {string} id - Modal ID to focus.
		 */
		focusModal( id ) {
			const modalEl = document.querySelector( `[data-modal-id="${ id }"]` );
			if ( ! modalEl ) {
				return;
			}

			// First, check for elements with autofocus attribute.
			const autofocusElements = modalEl.querySelectorAll( '[autofocus]' );
			if ( autofocusElements.length > 0 ) {
				autofocusElements[ 0 ].focus();
				return;
			}

			// Fall back to finding any focusable element except the close button.
			const focusable = modalEl.querySelectorAll(
				'button:not([aria-label="Close"]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);

			if ( focusable.length > 0 ) {
				focusable[ 0 ].focus();
			} else {
				// Last resort: focus any button including close.
				const anyButton = modalEl.querySelector( 'button' );
				if ( anyButton ) {
					anyButton.focus();
				} else {
					// Focus the modal itself if no focusable elements.
					modalEl.setAttribute( 'tabindex', '-1' );
					modalEl.focus();
				}
			}
		},

		/**
		 * Register modals that already exist in the DOM.
		 */
		registerExistingModals() {
			// Look for global modals rendered in footer.
			document.querySelectorAll( '[data-modal-id]' ).forEach( ( modal ) => {
				const id = modal.dataset.modalId;
				const config = modal.dataset.modalConfig;

				if ( id && ! this.registry[ id ] ) {
					this.register( id, config ? JSON.parse( config ) : {} );
				}
			} );
		},
	} );

	// Register helper Alpine data components.
	Alpine.data( 'affwpModal', ( modalId = '' ) => ( {
		modalId,

		init() {
			// Register this modal if not already registered.
			if ( modalId && ! Alpine.store( 'modals' ).registry[ modalId ] ) {
				Alpine.store( 'modals' ).register( modalId );
			}
		},

		get isOpen() {
			return Alpine.store( 'modals' ).isOpen( this.modalId );
		},

		open( data = {} ) {
			Alpine.store( 'modals' ).open( this.modalId, data );
		},

		close() {
			Alpine.store( 'modals' ).close( this.modalId );
		},
	} ) );

	// Initialize the modal store after registration.
	// This sets up keyboard handlers and registers existing modals.
	Alpine.store( 'modals' ).init();
} );

// Global helper functions for non-Alpine contexts.
window.affwpModals = {
	/**
	 * Open a modal.
	 *
	 * @param {string} id   - Modal ID.
	 * @param {Object} data - Optional data.
	 */
	open( id, data = {} ) {
		if ( typeof Alpine !== 'undefined' && Alpine.store ) {
			Alpine.store( 'modals' ).open( id, data );
		}
	},

	/**
	 * Close a modal.
	 *
	 * @param {string} id - Modal ID.
	 */
	close( id ) {
		if ( typeof Alpine !== 'undefined' && Alpine.store ) {
			Alpine.store( 'modals' ).close( id );
		}
	},

	/**
	 * Close all modals.
	 */
	closeAll() {
		if ( typeof Alpine !== 'undefined' && Alpine.store ) {
			Alpine.store( 'modals' ).closeAll();
		}
	},
};
