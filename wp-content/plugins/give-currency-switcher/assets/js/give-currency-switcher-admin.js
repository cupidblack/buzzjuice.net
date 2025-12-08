/**
 * Give - Currency Switcher Admin JS script.
 *
 * @package    Give_Currency_Switcher
 * @author     GiveWP <info@givewp.com>
 */

/* globals Give */
jQuery( function( $ ) {
	/**
	 * Currency switcher admin js script.
	 * @since 1.0
	 *
	 * @type {{init: init, init_chosen: init_chosen}}
	 */
	var Currency_Switch_Admin = {

		/**
		 * Initialization of the script.
		 * @since 1.0
		 */
		init: function() {
			this.init_chosen();
			this.handle_show_hide();
			this.disable_default_currency();
			this.toggle_update_custom_prices();
			this.exchange_rate_table_tooltip();

			// Lock or unlock exchange rate field.
			this.toggle_exchange_rate_field();

			$( 'tr.exchange-rate-row input.set_manually' ).on( 'change', this.toggle_exchange_rate_field );

			// Update Exchange rate.
			$( 'button#cs-update-exchange-rates' ).on( 'click', this.update_exchange_rate );
			$( 'input[name="_give_price_option"],input[name="_give_currency_price"]' ).on( 'change', this.toggle_custom_amount ).change();
			$( '#_give_donation_levels_field' ).find( '.give-add-repeater-field-section-row' ).on( 'click', this.toggle_update_custom_prices );

			// Toggle list table rows on small screen in Give Setting page.
			$( '.give-setting-tab-body .exchange-rate-row' ).on( 'click', '.toggle-row', function() {
				$( this ).closest( 'tr' ).toggleClass( 'is-expanded' );
			} );

			// Add/remove from supported currencies list.
			$( 'input[name="cs_supported_currency[]"]' ).on( 'change', this.updateDefaultCurrencyList );
		},

		/**
		 * Add tooltip to the exchange rates table cols "Decimal Number" & "Rate Markup".
		 */
		exchange_rate_table_tooltip: function() {
			// Add tooltip msg to the number of decimal setting on exchange rate table.
			$( 'div.currency-switcher-table' ).each( function() {
				var
					table_this = jQuery( this ),
					number_decimal_col = table_this.find( 'th.column-number_decimal' ),
					rate_markup_col = table_this.find( 'th.column-rate_markup' );

				var
					number_decimal_text = number_decimal_col.html(),
					rate_markup_text = rate_markup_col.html();

				// Remove text from this col.
				number_decimal_col.html( '' );
				rate_markup_col.html( '' );

				// Add tooltip to decimal number col.
				$( '<span/>', {
					'class': 'give-tooltip hint--top hint--medium hint--bounce give-cs-table-col',
					'aria-label': cs_admin_vars.decimal_number_help,
					'text': number_decimal_text
				} ).appendTo( number_decimal_col );

				// Add tooltip to the rate markup.
				$( '<span/>', {
					'class': 'give-tooltip hint--top hint--medium hint--bounce give-cs-table-col',
					'aria-label': cs_admin_vars.rate_markup_help,
					'text': rate_markup_text
				} ).appendTo( rate_markup_col );
			} );
		},

		/**
		 * Toggle variable price.
		 * @since 1.0
		 */
		toggle_update_custom_prices: function( e ) {

			// Wait, till new level field append.
			setTimeout( function() {
				var repeater_field = '';

				if ( $( this ).hasClass( 'give-add-repeater-field-section-row' ) ) {
					repeater_field = $( this ).closest( '.give_options_panel' ).find( '#_give_donation_levels_field' );
				} else {
					repeater_field = $( '#_give_donation_levels_field' );
				}

				// Go through each of the donation level.
				repeater_field.find( 'tr.give-row' ).not( '.give-template' ).each( function( index, element ) {

					if ( 0 === $( this ).find( '.cs_custom_price_option:checked' ).length ) {
						$( this ).find( '.cs_custom_price_option' ).attr( 'checked', 'checked' );
					}

					// Bind toggle function.
					$( this ).find( '.cs_custom_price_option' ).on( 'change', Currency_Switch_Admin.toggle_handle_variable_prices ).change();
				} );
			}, 100 );
		},

		/**
		 * Lock or unlock exchange rate field.
		 * @since 1.0
		 */
		toggle_exchange_rate_field: function() {
			$( 'div.cs_exchange_rates' ).find( 'tr.exchange-rate-row' ).each( function( i, em ) {
				$exchange_rate_field = $( this ).find( 'input.exchange_rate' );

				// Change if input field is already in <span> wrap or not.
				if ( ! $exchange_rate_field.closest( 'tr' ).find( 'td.column-exchange_rates span.cs-exchange-rate-field-wrap' ).length > 0 ) {

					// Wrap the exchange rate input field.
					$exchange_rate_field.wrap( '<span class="cs-exchange-rate-field-wrap"></span>' );
				}

				// Get the parent selector.
				var $parent_selector = $( this ).closest( 'tr' ).find( 'td.column-exchange_rates span.cs-exchange-rate-field-wrap' );

				if ( $( this ).find( 'input.set_manually' ).is( ':checked' ) ) {

					// Remove readonly.
					$exchange_rate_field.removeAttr( 'readonly' );

					// Remove lock icon.
					$parent_selector.find( 'a' ).remove();
				} else {

					// Make it readonly.
					$exchange_rate_field.attr( 'readonly', 'readonly' );

					// Create DOM Element.
					var lock_span = $( '<a>', {
						class: 'give-icon-locked-anchor', html:
							$( '<i>', { class: 'give-icon give-icon-locked' } )
					} );

					// Append lock symbol.
					$parent_selector.append( lock_span );
				}
			} );
		},

		/**
		 * Set default currency selected and disabled.
		 * @since 1.0
		 */
		disable_default_currency: function() {
			$( 'input[name="cs_supported_currency[]"][value="' + cs_admin_vars.default_currency + '"]' ).attr( 'checked', 'checked' ).addClass( 'disabled' ).on( 'click', function( e ) {
				e.stopPropagation();
				return false;
			} );
		},

		/**
		 * Render all the multi-check field to chosen.
		 * @since 1.0
		 */
		init_chosen: function() {
			$( '.cs-chosen-field' ).chosen( {
				width: '70%'
			} );
		},

		/**
		 * Show/Hide the custom price option in variable price list.
		 *
		 * @since 1.0
		 * @param e
		 */
		toggle_handle_variable_prices: function( e ) {
			var custom_price_option = $( this ).closest( 'li' ).find( 'input.cs_custom_price_option[type="radio"]:checked' ).val(),
				currency_amount_container = $( this ).closest( 'div.give-row-body' ).find( '.cs_custom_prices_wrap' );

			// Hide/Show custom price option.
			if ( 'enabled' === custom_price_option ) {
				currency_amount_container.removeClass( 'give-hidden' );
			} else if ( 'disabled' === custom_price_option ) {
				currency_amount_container.addClass( 'give-hidden' );
			}
		},

		/**
		 * Handle Show/Hide functionality in the admin setting backend.
		 * @since 1.0
		 */
		handle_show_hide: function() {

			var currency_switcher_enable = $( 'input[name="cs_status"]:radio' ),
				cs_exchange_rate_enable = $( 'input[name="cs_exchange_rates_update"]:radio' ),
				cs_geo_location_enable = $( 'input[name="cs_geolocation_state"]:radio' ),
				cs_exchange_app_key = $( 'select[name="cs_exchange_rates_providers"]' ),
				cs_fields = $( '.cs_general_fields' ),
				cs_exchange_fields = $( '.cs_exchange_rates_fields' ),
				cs_geo_fields = $( '.cs_geo_location_fields' );

			/**
			 * Show/Hide Currency Switcher field options.
			 */
			currency_switcher_enable.on( 'change', function() {
				// Get the checked value.
				var selected_value = $( 'input[name="cs_status"]:radio:checked' ).val(),
					setting_tab = $( '.give-settings-setting-page' ).find( 'ul.subsubsub' ).find( 'li:eq(1),li:eq(2) ' );

				// If enable show other fields.
				if ( 'enabled' === selected_value ) {
					cs_fields.show();
					setting_tab.show();
					$( '.currency_switcher_geolocation_tab, .currency_switcher_gateway_tab' ).show();
				} else {
					cs_fields.hide();
					setting_tab.hide();
					$( '.currency_switcher_geolocation_tab, .currency_switcher_gateway_tab' ).hide();
				}
			} ).change();

			/**
			 *  Show/Hide Exchange Rates field options.
			 */
			cs_exchange_rate_enable.on( 'change', function( e ) {

				if ( ! $( 'input[name="cs_exchange_rates_update"]' ).is( ':visible' ) && $( '.post-body' ).is( ':visible' ) ) {
					return false;
				}

				// Get the checked value.
				var selected_value = $( 'input[name="cs_exchange_rates_update"]:checked' ).val();

				// If enable show other fields.
				if ( 'enabled' === selected_value ) {
					cs_exchange_fields.show();
				} else {
					cs_exchange_fields.hide();
				}

				cs_exchange_app_key.change();

			} ).change();

			/**
			 *  Show/Hide Geo location fields.
			 */
			cs_geo_location_enable.on( 'change', function() {
				// Get the checked value.
				var selected_value = $( 'input[name="cs_geolocation_state"]:checked' ).val();
				// If enable show other fields.
				if ( 'enabled' === selected_value ) {
					cs_geo_fields.show();
				} else {
					cs_geo_fields.hide();
				}
			} ).change();

			/**
			 * Open Exchange Rates API Key
			 * Show/hide depend on "Exchange Rates Provider".
			 */
			cs_exchange_app_key.on( 'change', function() {

				var chosen = $( 'select[name="cs_exchange_rates_providers"]' ).val(),
					automatic_update_stat = $( '.cs_exchange_rates_update input[name="cs_exchange_rates_update"]:checked' ).val(),
					cs_automatic_update = $( '.cs_exchange_rates_update' ),
					cs_update_interval = $( '.cs_exchange_rates_interval' );

				if ( '0' === chosen ) {
					cs_automatic_update.hide();
					cs_update_interval.hide();
				} else {
					cs_automatic_update.show();
				}

				// Show hide update interval option.
				cs_update_interval.toggle( 'enabled' === automatic_update_stat && '0' !== chosen );

				if ( 'open-exchange-rates' === chosen ) {
					$( '.cs_open_exchanges_app_id' ).show();
				} else {
					$( '.cs_open_exchanges_app_id' ).hide();
				}

				if ( 'fixer' === chosen ) {
					$( '.cs_fixer_access_key' ).show();
				} else {
					$( '.cs_fixer_access_key' ).hide();
				}
			} ).change();
		},

		/**
		 * Toggle custom amount field
		 *
		 * @since 1.0
		 * @param e
		 */
		toggle_custom_amount: function( e ) {

			var field_name = $( this ).attr( 'name' ),
				custom_option = $( 'input[name="_give_currency_price"]:checked' ).val(),
				donation_option = $( 'input[name="_give_price_option"]:checked' ).val(),
				custom_price_field = $( this ).closest( '.give_options_panel' ).find( '._give_currency_price_field, #_give_cs_custom_prices_field' ),
				set_amount_fields = $( this ).closest( '.give_options_panel' ).find( '#_give_cs_custom_prices_field' );

			if ( '_give_price_option' === field_name ) {

				if ( 'set' === donation_option ) {
					custom_price_field.show();
					if ( 'enabled' === custom_option ) {
						set_amount_fields.show();
					} else {
						set_amount_fields.hide();
					}
				} else {
					custom_price_field.hide();
				}
			}

			if ( '_give_currency_price' === field_name ) {

				if ( 'enabled' === custom_option && 'set' === donation_option ) {
					set_amount_fields.show();
				} else {
					set_amount_fields.hide();
				}
			}
		},

		/**
		 * Update exchange rate option through the API.
		 */
		update_exchange_rate: function( e ) {
			// Disable default action.
			e.preventDefault();

			if ( $( this ).hasClass( 'disabled' ) )
				return false;

			// Get the exchange rates section.
			var rates_table = $( this ).closest( '.currency-switcher-table.cs_exchange_rates' );

			// Disable the button and whole rates table.
			rates_table.addClass( 'currency-switcher-disabled-section' );

			// Store current exchange rate values.
			var current_values = {};

			// Get the current values from the exchange rates table.
			rates_table.find( 'tr.exchange-rate-row' ).map( function() {
				// Store it as array values.
				current_values[ $( this ).data( 'currency' ) ] = {
					'exchange_rate': $( this ).find( 'input.exchange_rate' ).val(),
					'set_manually': $( this ).find( 'input.set_manually' ).is( ':checked' ) ? 1 : 0,
					'rate_markup': $( this ).find( 'input.rate_markup' ).val(),
				};
			} ).get();

			var post_data = {
				'nonce': cs_admin_vars.update_exchange_nonce,
				'action': 'cs_update_exchange_rates',
				'exchange_rate_inputs': JSON.stringify( current_values ),
				'setting_type': $( this ).data( 'type' ),
				'form_id': $( this ).data( 'formid' )
			};

			var loader = $( this ).closest( '.give-cs-exchange-rate-table-bottom' ).find( '.give-cs-loading-animation' );
			loader.removeClass( 'give-hidden' );

			$.ajax( {
				type: 'POST',
				url: cs_admin_vars.ajax_url,
				data: post_data,
				success: function( response ) {
					var alert_msg = '';
					loader.addClass( 'give-hidden' );
					// If rates successfully fetched.

					if ( true === response.success && response.data.rates ) {

						// Set rates to the exchange rate field.
						$.each( response.data.rates, function( currency, exchange_rates ) {
							// If blank.
							if ( false === exchange_rates || 0 === exchange_rates ) {
								return true;
							}

							// Get the rate field.
							var rate_field = $( 'tr.exchange-rate-row[data-currency="' + currency + '"]' );

							if ( ! rate_field.find( 'input.set_manually' ).is( ':checked' ) ) {
								// Update the rate.
								rate_field.find( 'input.exchange_rate' ).val( exchange_rates );
							}
						} );

						// Show message.
						alert_msg = ( response.data.missed_currency.length > 0 )
							? cs_admin_vars.messages.missed_rates_for + response.data.missed_currency.join( ', ' )
							: cs_admin_vars.messages.exchange_rate_update;

					} else if ( false === response.success ) {
						// Store error message.
						alert_msg = response.error_message;
					}

					// Show alert msg.
					if ( '' !== alert_msg ) {
						if ( true === response.success ) {
							new Give.modal.GiveSuccessAlert({
								modalContent:{
									title: give_vars.success,
									desc: alert_msg,
									cancelBtnTitle: give_vars.ok,
								}
							}).render();
						} else {
							new Give.modal.GiveErrorAlert({
								modalContent:{
									title: give_vars.error,
									desc: alert_msg,
									cancelBtnTitle: give_vars.ok,
								}
							}).render();
						}
					}

					// Unblock the section.
					rates_table.removeClass( 'currency-switcher-disabled-section' );
				}
			} );

		},

		/**
		 * Updates the default currency list when supported
		 * currencies are added/removed.
		 */
		updateDefaultCurrencyList: function( e ) {

			var label = $(this).closest( 'label' ).text(),
			    value = $(this).val();

			var default_currency = $( '#give_cs_default_currency' );

			if ( $(this).is(":checked") ) {
				default_currency.append( $( '<option></option>' )
					.attr( 'value', value )
					.text( label )
				);
			} else {
				$( '#give_cs_default_currency option[value="' + value + '"]' ).remove()
			}
		}
	};

	// Initialize the currency switcher script.
	Currency_Switch_Admin.init();
} );
