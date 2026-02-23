import API from '@p2p/js/api';

( function( $ ) {

	const GiveP2P = {
		init: () => {
			// Attach events
			GiveP2P.uploadImageFieldInit();
			GiveP2P.colorPickerInit();
			GiveP2P.manageCampaignURL();
			GiveP2P.editDonationFormLink();
			GiveP2P.repeaterFieldEvents();
			GiveP2P.handleFieldsVisibility();
		},

		/**
		 * Upload field
		 */
		uploadImageFieldInit: function() {
			// Open media window
			$( document ).on( 'click', '.give-p2p-image-upload-btn', function( e ) {
				e.preventDefault();

				const input = $( this );
				const mediaLibrary = wp.media( {
					library: {
						type: [ 'image' ],
					},
				} );

				mediaLibrary.on( 'select', function() {
					const attachment = mediaLibrary.state().get( 'selection' ).first().toJSON();
					const img = $( '<img />', {
						src: attachment.url,
					} );

					input.parent().find( '.give-image-field' ).val( attachment.url );
					input.parents( '.give-p2p-image-upload-field-container' ).find( '.give-p2p-image-preview-container' ).html( img );
				} );

				mediaLibrary.open();
			} );

			// Add remove icon to image
			$( document ).on( {
				mouseenter: function() {
					$( this ).find( 'img' ).after( '<div class="give-p2p-remove-image"><span class="dashicons dashicons-dismiss"></span></div>' );
				},
				mouseleave: function() {
					$( '.give-p2p-remove-image' ).remove();
				},
			}, '.give-p2p-image-preview-container' );

			// Remove image
			$( document ).on( 'click', '.give-p2p-remove-image', function( e ) {
				e.preventDefault();
				$( this ).parents( '.give-p2p-image-upload-field-container' ).find( '.give-image-field' ).val( '' );
				$( this ).parent().html( '' );
			} );
		},

		/**
		 * Color Picker
		 */
		colorPickerInit: function() {
			$( '.give-p2p-colorpicker' ).wpColorPicker();
		},

		/**
		 * Campaign title and URL
		 */
		manageCampaignURL: function() {
			function toggleUrlEditOptions() {
				$( '.give-p2p-preview-campaign-url' ).toggle();
				$( '.give-p2p-edit-campaign-url' ).toggle();
			}

			// Toggle Campaign edit URL
			$( document ).on( 'click', '.give-p2p-edit-campaign-slug-btn, .give-p2p-cancel-campaign-url-edit', toggleUrlEditOptions );

			// Save campaign URL
			$( document ).on( 'click', '.give-p2p-save-campaign-slug-btn', function( e ) {
				e.preventDefault();

				const campaignId = $( this ).data( 'campaign' );
				const campaignURLInput = $( '#give-p2p-campaign-slug' );
				const campaignPreviousURL = $( '#give-p2p-campaign-url' );

				// Handle URL change only if entered URL slug is diferent than previous one
				if ( campaignURLInput.val() === campaignPreviousURL.text() ) {
					toggleUrlEditOptions();
					return;
				}

				API.post( '/edit-campaign-url', {
					id: campaignId,
					url: campaignURLInput.val(),
				} ).then( ( { data } ) => {
					if ( data.status ) {
						campaignURLInput.val( data.url );
						$( '#give-p2p-campaign-url' ).text( data.url );
						$( '#give-p2p-campaign-preview-url' ).attr( 'href', data.previewUrl );
					} else {
						console.log( data.error );
					}

					toggleUrlEditOptions();
				} ).catch( ( error ) => {
					console.log( error );
					toggleUrlEditOptions();
				} );

			} );
		},

		/**
		 * Edit Donation Form link
		 */
		editDonationFormLink: function() {

			$( document ).on( 'change', '#give-p2p-form_id', function( e ) {
				return Math.abs( e.target.value )
					? $( '.give-p2p-edit-form-link' ).show()
					: $( '.give-p2p-edit-form-link' ).hide()
			})
			$( '#give-p2p-form_id' ).change()

			// Update url based on selected form
			$( document ).on( 'click', '.give-p2p-edit-form-link', function( e ) {
				e.preventDefault();

				const selected = $( '#give-p2p-form_id' ).val();
				const href = $( this ).attr( 'href' );

				Object.assign(
					document.createElement( 'a' ),
					{
						target: '_blank',
						href: href.replace( '{formId}', selected ),
					},
				).click();
			} );
		},

		/**
		 * Repeater field
		 */
		repeaterFieldEvents: function() {

			// Repeat block
			$( document ).on( 'click', '.give-p2p-repeat-block-btn', function( e ) {
				e.preventDefault();

				const blockId = $( this ).data( 'block' );
				const repeaterBlock = $( '.give-p2p-repeater-block' ).last();
				const clonedBlock = repeaterBlock.clone();

				$( '*', clonedBlock ).each( function() {
					const elementType = $( this ).attr( 'type' );
					const elementId = $( this ).attr( 'id' );

					if ( elementType && elementType !== 'button' ) {
						$( this ).val( '' );
					}

					if ( elementId ) {
						let newId = elementId.replace( /[0-9]+$/, ( match ) => {
							return parseInt( match ) + 1;
						} );
						$( this ).attr( 'id', newId );
					}

					if ( $( this ).hasClass( 'give-p2p-image-preview-container' ) ) {
						$( this ).html( '' );
					}
				} );

				clonedBlock.insertAfter( repeaterBlock );
			} );

			// Remove block icon
			$( document ).on( {
				mouseenter: function() {
					$( '.give-p2p-repeater-block-remove-icon', this ).show();
				},
				mouseleave: function() {
					$( '.give-p2p-repeater-block-remove-icon', this ).hide();
				},
			}, '.give-p2p-repeater-block:gt(0)' );

			// Remove block
			$( document ).on( 'click', '.give-p2p-repeater-block-remove-icon', function( e ) {
				e.preventDefault();
				$( this ).parents( '.give-p2p-repeater-block' ).remove();
			} );
		},

		/**
		 * Handle fields visibility
		 */
		handleFieldsVisibility: function() {

			function checkCondition( inputValue, operator, value ) {
				const conditions = {
					'=': () => inputValue == value,
					'!=': () => inputValue != value,
					'<': () => inputValue < value,
					'<=': () => inputValue <= value,
					'>': () => inputValue > value,
					'>=': () => inputValue >= value,
				};

				return conditions[ operator ]?.() ?? false;
			}

			function handleVisibility() {
                /**
                 * @typedef {object} Condition
                 * @property {string} type
                 * @property {string} field
                 * @property {any} value
                 * @property {string} comparisonOperator
                 * @property {string} logicalOperator
                 */
				$( '[data-field-visibility]' ).each( function() {
					const wrapper = $( this );

                    /** @type {Condition[]} */
					const conditions = wrapper.data( 'field-visibility' );

					let visible = false;

					conditions.forEach( ( condition, index ) => {
						const inputs = $( '[name="' + condition.field + '"]' );

						if ( inputs ) {
                            let tempVisible = false;
							inputs.each( function() {
								const input = $( this );
								const fieldType = input.attr( 'type' );

								if ( fieldType && ( fieldType === 'radio' || fieldType === 'checkbox' ) ) {
									if ( input.is( ':checked' ) && checkCondition( input.val(), condition.comparisonOperator, condition.value ) ) {
                                        tempVisible = true;
										return;
									}
								} else {
									if ( checkCondition( input.val(), condition.comparisonOperator, condition.value ) ) {
                                        tempVisible = true;
										return;
									}
								}
							} );

                            if( index > 0 ) {
                                // This logic handles multiple conditions on form field.

                                // This logic only handle flat visibility conditions. That means only one boolean operator ("and","or") is possible.
                                // Form field condition in backed: Give\Framework\FieldsAPI\Conditions\BasicConditions.

                                // More than one condition can be glued by only two boolean operators "and", "or"
                                let previousConditionOperator = conditions[index-1]['boolean'];
                                if( previousConditionOperator === 'and' ) {
                                    // If condition operator is "and" than final result will be combinations of all conditions results.
                                    visible = visible && tempVisible;
                                } else if ( previousConditionOperator === 'or' && tempVisible ) {
                                    // If condition operator is "or" than any truthy condition result will be final result.
                                    visible = true;
                                    return;
                                }
                            }else{
                                // Handle if form field only has one condition.
                                visible = tempVisible;
                            }
						}
					} );
					wrapper.toggle( visible );
				} );
			}

			handleVisibility();

			$( '.give-p2p-visibility-handler' ).on( 'change', handleVisibility );
		},
	};

	GiveP2P.init();

} )( jQuery );
