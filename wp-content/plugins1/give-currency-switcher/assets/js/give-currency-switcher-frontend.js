/**
 * Give - Currency Switcher Frontend JS script.
 *
 * @package    Give_Currency_Switcher
 */
var give_global_vars, Give_Currency_Switcher, give_cs_json_obj;

jQuery.noConflict();

(function ($) {
	/**
	 * Currency switcher admin js script.
	 * @since 1.0
	 *
	 * @type {{init: init, init_chosen: init_chosen}}
	 */
	Give_Currency_Switcher = {
		isMultiStepForm: false,

		/**
		 * List of Give forms.
		 */
		forms: false,

		/**
		 * Current variable price id.
		 */
		price_id: '',

		/**
		 * Initialization frontend js.
		 *
		 * @since 1.0
		 */
		init: function () {
			forms = $('form.give-form');

			this.init_switcher();

			// Remove sanitize amount when donation value is updated.
			jQuery(document).on(
				'give_donation_value_updated blur',
				'.give-donation-amount',
				this.remove_sanitize_amount
			);

			// When gateway is changed.
			jQuery(document).on('give_gateway_loaded', this.changed_payment_gateway);

			// Validate minimum price.
			jQuery(document).find('.give-donation-amount .give-text-input').on('blur', this.update_donation_amount);

			// Bind event to trigger the donation form.
			$('.give-btn-reveal').on('click', this.handle_reveal_btn);

			// Switch donation form's currency.
			$('body').on('change', '.give-cs-select-currency', this.switch_currency);

			// Do not show drop-down if currencies are not activated.
			// $( 'body' ).on( 'mousedown', 'select.give-cs-select-currency', this.chosenCurrency );

			// Fix Currency selector drop-down width in model view.
			jQuery(document).on('mfpOpen', this.adjust_dropdown_width);
			jQuery(document).on('click', '.give-card', this.adjust_dropdown_width);
			jQuery(document).on('mfpClose', this.adjust_dropdown_width_after_close);
		},

		/**
		 * Adjust width when button view modal close.
		 *
		 * @since 1.0.4
		 */
		adjust_dropdown_width_after_close: function () {
			setTimeout(function () {
				Give_Currency_Switcher.init_switcher();
			}, 10);
		},

		/**
		 * Move the Currency switcher drop-down and set it with amount input field.
		 * for all forms.
		 *
		 * @since 1.0
		 */
		init_switcher: function () {
			forms.each(function () {
				// Get the form.
				var form = $(this);

				// If using a multi-step form, store goal and income information in form attributes
				if (form.parent().hasClass('give-embed-form')) {
					Give_Currency_Switcher.isMultiStepForm = true;
					form.attr(
						'data-goal-amount',
						Number(
							form
								.find('.goal .number')
								.text()
								.replace(/[^0-9.-]+/g, '')
						)
					);
					form.attr(
						'data-income-amount',
						Number(
							form
								.find('.raised .number')
								.text()
								.replace(/[^0-9]+/g, '')
						)
					);
				}

				// Check if currency switcher is enabled.
				if (!Give_Currency_Switcher.module.isCurrencySwitcherEnable(form)) {
					return;
				}

				var cs_switcher = form.find('select.give-cs-select-currency'), // Get the currency drop-down.
					currencies = Give_Currency_Switcher.module.getSupportedCurrencies(form),
					currency_symbol = form.find('.give-currency-symbol').text();

				// If acronyms is enabled then show currency code with symbol.
				if ('enabled' === Give_Currency_Switcher.module.getValueOf('acronyms', form)) {
					var currency_code = Give.form.fn.getInfo('currency_code', form);

					// If acronyms is already there.
					currency_symbol =
						-1 === currency_symbol.indexOf(currency_code)
							? Give.form.fn.getInfo('currency_code', form) + ' ' + currency_symbol
							: currency_symbol;
				}

				// If Currency switcher is activated.
				if (
					Give_Currency_Switcher.module.isCurrencySwitcherEnable(form) &&
					1 < Object.keys(currencies).length
				) {
					// Get the clone of the currency switcher drop-down.
					var switcher_clone = cs_switcher
						.removeClass('give-hidden')
						.addClass('give-cs-mini-dropdown')
						.css({
							width: form
								.find('.give-currency-symbol')
								.addClass('give-cs-mini-dropdown-icon')
								.outerWidth(),
						})
						.clone();

					// Add currency switcher drop-down accordingly.
					if ('after' === give_global_vars.currency_pos) {
						switcher_clone.insertAfter(
							form.find(
								'.give-donation-amount input[name="give-amount"], .give-donation-amount span.give-amount-top'
							)
						);
					} else {
						switcher_clone.prependTo(form.find('.give-donation-amount'));
					}

					// Change the currency symbol and code.
					form.find('.give-currency-symbol').html(currency_symbol);

					// Update currency selector drop-down width.
					Give_Currency_Switcher.adjust_dropdown_width();

					// Remove switcher drop-down.
					cs_switcher.remove();
				} else if (
					Give_Currency_Switcher.module.isCurrencySwitcherEnable(form) &&
					1 === Object.keys(currencies).length
				) {
					// If only one currency is supported.
					form.find('.give-currency-symbol').html(currency_symbol);
				}

				if (!Give_Currency_Switcher.module.isGatewaySupportCurrency(form, currency_code)) {
					Give_Currency_Switcher.module.showCurrencyNotSupportGatewayNotice(form, currency_code);

					// Select switched currency.
					form.find('.give-cs-select-currency').val(currency_code).trigger('change');
				}
			});
		},

		/**
		 * Adjust currency switcher selector width when opening model
		 *
		 * @since 1.0.1
		 */
		adjust_dropdown_width: function () {
			$('form.give-form').each(function () {
				// Get the form.
				var form = $(this);

				if (Give_Currency_Switcher.module.isCurrencySwitcherEnable(form)) {
					// Get the current drop-down width.
					var dropdown_width = form.find('.give-currency-symbol.give-cs-mini-dropdown-icon').outerWidth();

					// Modify the width of the element.
					form.find('.give-cs-select-currency').css({width: dropdown_width});

					// Get the outer width of drop-down.
					var width = form.find('.give-cs-select-currency').outerWidth();

					// Set margin to the currency selector drop-down.
					form.find('.give-cs-mini-dropdown').css('marginRight', -Math.abs(width) + 'px');
				}
			});
		},

		/**
		 * Handle reveal and modal view.
		 * @since 1.0
		 */
		handle_reveal_btn: function () {
			$(this).parents('form').find('.give-currency-switcher-wrap').slideDown();
		},

		/**
		 * Disable switch currency drop-down if the donation amount is lower than minimum amount.
		 *
		 * @since 1.0
		 * @param e
		 */
		update_donation_amount: function (e) {
			// Get the donation form .
			var form = jQuery(e.currentTarget).closest('form');

			if (
				!Give_Currency_Switcher.module.isCurrencySwitcherEnable(form) ||
				0 >= form.find('.give-cs-select-currency').length
			) {
				return;
			}

			// If there is any custom amount already set, remove it.
			form.find('input[name="give-cs-custom-amount"]').remove();

			// Change the donation form's amount.
			Give_Currency_Switcher.module.setValueOf('give-amount', $(this).val(), form);

			// Change the donation form's amount.
			form.find('.give-final-total-amount').attr('data-total', $(this).val());

			var formPriceID = Give.form.fn.getPriceID(form),
				formCurrency = Give_Currency_Switcher.module.getValueOf('give-cs-currency', form),
				formBaseAmounts = JSON.parse(Give.form.fn.getInfo('data-give_cs_base_amounts', form)),
				donationAmount = Give_Currency_Switcher.module.getDonationAmount(
					form,
					Give.form.fn.getInfo('decimal_separator', form)
				);

			// Get the exchange rates.
			var currencyRates = Give_Currency_Switcher.module.getExchangeRates(form);

			// Convert the currency amount in base currency.
			var amountInBase =
				0 !== currencyRates[formCurrency] ? donationAmount / currencyRates[formCurrency] : donationAmount;

			// Set custom amount as 0;
			formBaseAmounts.custom = 0;

			// If the price id -1 then it is "Set donation" otherwise check for variable custom amount.
			if (
				(-1 === formPriceID && parseFloat(formBaseAmounts[0]) !== parseFloat(amountInBase)) ||
				('custom' === formPriceID && parseFloat(amountInBase) !== parseFloat(formBaseAmounts[formPriceID]))
			) {
				// Update form's custom amount.
				formBaseAmounts.custom = amountInBase;
			}

			// Update base amount.
			form.attr('data-give_cs_base_amounts', JSON.stringify(formBaseAmounts));

			// Unblock currency switcher drop-down.
			setTimeout(function () {
				0 < form.find('div.give-invalid-minimum').length
					? form.find('.give-cs-select-currency').attr('disabled', 'disabled')
					: form.find('.give-cs-select-currency').removeAttr('disabled');
			}, 400);

			// Update message.
			Give_Currency_Switcher.module.updateCurrencyMessage(form, formPriceID);

			// Change price ID.
			if (formPriceID !== Give_Currency_Switcher.price_id) {
				Give_Currency_Switcher.price_id = Give.form.fn.getPriceID(form);
			}

			// Update minimum amount.
			Give_Currency_Switcher.updateMinimumAmount(form, formPriceID);
		},

		/**
		 * When Donor Switch Currency from the donation form.
		 *
		 * @since 1.0
		 * @param e
		 */
		switch_currency: function (e) {
			// Get the donation form.
			var $form = $(this).closest('form.give-form');

			// Return, if currency switcher isn't activate.
			if (!Give_Currency_Switcher.module.isCurrencySwitcherEnable($form)) {
				return;
			}

			var new_currency = $(this).val(), // Current .
				old_currency = Give_Currency_Switcher.module.getValueOf('give-cs-form-currency', $form), // Previous.
				supported_currencies = Give_Currency_Switcher.module.getSupportedCurrencies($form), // Supported currencies.
				variable_prices = Give.form.fn.getVariablePrices($form); // Get the variable IDs.

			// Bail out if new_currency not set.
			if (null === new_currency) {
				return;
			}

			// Get the symbol of the selected currency.
			var new_currency_sign = supported_currencies[new_currency].currencySetting.symbol || ''; // New currency symbol.

			if (!Give_Currency_Switcher.module.isGatewaySupportCurrency($form, new_currency)) {
				Give_Currency_Switcher.module.showCurrencyNotSupportGatewayNotice($form, new_currency);

				// Select switched currency.
				$form.find('.give-donation-amount .give-text-input').trigger('blur');
				$form.find('#give-purchase-button').attr('disabled', true);
				return;
			}

			// Change currency symbol with currency code based on 'acronyms' option in Give's setting.
			$form.find('.give-currency-symbol').html(
				'enabled' === Give_Currency_Switcher.module.getValueOf('acronyms', $form)
					? new_currency + ' ' + new_currency_sign // eg. [ USD $ ]
					: new_currency_sign
			);

			var currencySettings = supported_currencies[new_currency].currencySetting.setting,
				thousandSeparator =
					'' !== currencySettings['thousands_separator'] ? currencySettings['thousands_separator'] : ' ',
				decimalSeparator =
					'' !== currencySettings['decimal_separator'] ? currencySettings['decimal_separator'] : '.';

			// Update form currency formatting options.
			Give.form.fn.setInfo('attr', new_currency_sign, $form, 'data-currency_symbol');
			Give.form.fn.setInfo('attr', new_currency, $form, 'data-currency_code');
			Give.form.fn.setInfo('attr', thousandSeparator, $form, 'data-thousands_separator');
			Give.form.fn.setInfo('attr', decimalSeparator, $form, 'data-decimal_separator');
			Give.form.fn.setInfo('attr', currencySettings['number_decimals'], $form, 'data-number_decimals');

			// Set new currency and exchange rate.
			Give_Currency_Switcher.module.setValueOf(
				'give-cs-exchange-rate',
				Give_Currency_Switcher.module.getExchangeRates($form)[new_currency],
				$form
			);
			Give_Currency_Switcher.module.setValueOf('give-cs-form-currency', new_currency, $form);

			// Remove currency notices if exists.
			$(this).closest('.give-display-onpage').find('.give-cs-notices').remove();

			// Bail out if, new currency and old currency same.
			if (new_currency === old_currency) {
				return;
			}

			var allSupportedCurrencies = Give_Currency_Switcher.module.getAllSupportedCurrencies(),
				decimal_separator = allSupportedCurrencies[old_currency].setting['decimal_separator'];

			// Convert single amount.
			Give_Currency_Switcher.updateSingleAmount($form, old_currency, decimal_separator);

			// Form Price ID.
			var form_price_id = Give.form.fn.getPriceID($form, false);

			// If this donation form has variable prices.
			if (-1 < form_price_id || 'custom' === form_price_id) {
				Give_Currency_Switcher.updateVariablePrices($form, form_price_id, old_currency, variable_prices);
			}

			// Trigger Give amount field.
			$form.find('.give-donation-amount .give-text-input').trigger('blur');

			// Convert Goal amounts.
			Give_Currency_Switcher.updateGoalAmount($form, new_currency, decimal_separator);

			// Update currency switched message.
			Give_Currency_Switcher.module.updateCurrencyMessage($form, form_price_id);

			// Update FeeRecovery base amount.
			Give_Currency_Switcher.module.updateFeeAmount(old_currency, new_currency, $form, decimal_separator);

			// Unset form cache.
			Give.cache['form_' + Give.form.fn.getInfo('form-id', $form)] = [];

			/**
			 * Allow developer to add custom JS when currency switched.
			 *
			 * @since 1.0
			 */
			$(document).trigger('give_cs_switched', $form);
		},

		/**
		 * Convert / Update single amount.
		 *
		 * @param form
		 * @param currency_old
		 * @param decimal_separator
		 */
		updateSingleAmount: function (form, currency_old, decimal_separator) {
			var give_total = Give_Currency_Switcher.module.getDonationAmount(form, decimal_separator), // Get the give form amount.
				currency_chosen = Give_Currency_Switcher.module.getValueOf('give-cs-currency', form), // Get the selected currency.
				give_custom_amounts = Give_Currency_Switcher.module.getCustomAmounts(form), // Get all of the custom amounts.
				converted_amount = give_custom_amounts[currency_chosen]; // Get the custom amount.

			// If custom amount isn't exists.
			if (!converted_amount) {
				// Convert amount to the selected currency.
				converted_amount = Give_Currency_Switcher.module.convertCurrency(
					give_total,
					form,
					currency_old,
					null,
					true
				);
			}

			// Update currency selector drop-down width.
			Give_Currency_Switcher.adjust_dropdown_width();

			// Format the converted amount.
			var amount_formatted = Give.fn.formatCurrency(converted_amount, {}, form);

			// Update custom amount field
			form.find('.give-amount-top').val(amount_formatted);
			form.find('span.give-amount-top').text(amount_formatted);

			// Remove custom amount field, if exists.
			form.find('span.give-cs-amount-totals').remove();

			/**
			 * Temporarily Store form's amount.
			 *
			 * data-total => Converted amount.
			 * data-sanitize-amount => Un-formatted amount without rounding off the decimal amounts.
			 * data-custom_amount => When chosen currency has custom amount then store the calculated amount to it.
			 */
			$('<span/>', {
				class: 'give-cs-amount-totals',
				'data-total': amount_formatted,
				'data-sanitize-amount': converted_amount,
				'data-custom_amount': give_custom_amounts[currency_chosen]
					? Give_Currency_Switcher.module.convertCurrency(give_total, form, currency_old)
					: '',
			}).appendTo(form);
		},

		/**
		 * Convert or Update the variable prices.
		 *
		 * @param form
		 * @param form_price_id
		 * @param currency_old
		 * @param variable_prices
		 */
		updateVariablePrices: function (form, form_price_id, currency_old, variable_prices) {
			var current_currency = Give_Currency_Switcher.module.getValueOf('give-cs-currency', form),
				variable_amounts = []; // Store new converted amounts,

			/**
			 * While rendering the donation form we are storing variable's custom and converted amount
			 * to "span.give-cs-variable-amount" DOM selector. It will needs to update according to the
			 * chosen currency.
			 */
			var variable_old_amounts = form.find('span.give-cs-variable-amount');

			// Update each variable price.
			$.each(variable_prices, function (index, variable) {
				var price_id = variable.price_id, // Get the price id.
					variable_custom_prices = Give_Currency_Switcher.module.getCustomAmounts(form, price_id), // Get the custom prices.
					variable_amount = variable.amount, // Get the variable amount.
					variable_selector = form.find('.give-donation-levels-wrap [data-price-id=' + price_id + ']');

				var previous_variable_amounts = variable_old_amounts.length
					? JSON.parse(decodeURIComponent(variable_old_amounts.attr('data-custom_amount')))
					: []; // Get the temporarily stored variable amounts.

				// Go through all old values.
				$.each(previous_variable_amounts, function (index, price_data) {
					if (price_data[price_id]) {
						variable_amount = price_data[price_id]['converted-amount'];
					}
				});

				// If this variable has custom price instead of the calculation through rates.
				var converted_var_amount = !variable_custom_prices[current_currency]
					? Give_Currency_Switcher.module.convertCurrency(
							variable_amount,
							form,
							currency_old,
							null,
							true,
							price_id
					  ) // Convert the variable amount if it has no custom amount.
					: variable_custom_prices[current_currency];

				// Update variable price.
				form.find('.give-donation-levels-wrap [data-price-id=' + price_id + ']').val(
					Give.fn.formatCurrency(converted_var_amount, {}, form)
				);

				// If display style is 'radio'.
				var label_text = variable_selector.hasClass('give-radio-input')
					? form
							.find('.give-donation-levels-wrap [data-price-id=' + price_id + ']')
							.closest('li')
							.find('label')
							.html()
					: form.find('.give-donation-levels-wrap [data-price-id=' + price_id + ']').html();

				// Change label of the variable element.
				if ('custom' !== price_id) {
					// Check if price has label.
					var parsedJSON = jQuery.parseJSON(give_cs_json_obj),
						form_id = Give.form.fn.getInfo('form-id', form),
						price_has_label = parsedJSON['form_' + form_id]['variable_prices'][price_id]['has_label'],
						decimals_enabled = parsedJSON['form_' + form_id]['decimals_enabled'];

					if (Give_Currency_Switcher.isMultiStepForm) {
						var symbol = Give.form.fn.getInfo('currency_symbol', form),
							position = Give.form.fn.getInfo('currency_position', form),
							precision = Give.form.fn.getInfo('number_decimals', form),
							amount;

						if ('enabled' === decimals_enabled && Give.fn.numberHasDecimal(converted_var_amount)) {
							amount = Give.fn.formatCurrency(
								converted_var_amount,
								{
									symbol: symbol,
									position: position,
									precision: precision,
								},
								form
							);
						} else {
							amount = Math.round(converted_var_amount);
						}

						var label_string =
							'<div class="currency currency--' + position + '">' + symbol + '</div>' + amount;

						form.find('.give-donation-levels-wrap')
							.find(
								'.give-btn-level-' +
									price_id +
									', *[for="give-radio-level-' +
									price_id +
									'"], .give-donation-level-' +
									price_id
							)
							.html(label_string);
					} else {
						var amount = Give.fn.formatCurrency(
							converted_var_amount,
							{
								symbol: Give.form.fn.getInfo('currency_symbol', form),
								position: Give.form.fn.getInfo('currency_position', form),
								precision: Give.form.fn.getInfo('number_decimals', form),
							},
							form
						);

						var label_array = label_text.split(', '),
							label_length = label_array.length;

						var label_string = amount;
						if (2 === label_length) {
							label_string = amount + ', ' + label_array[1];
						}

						if (!price_has_label) {
							form.find('.give-donation-levels-wrap')
								.find(
									'.give-btn-level-' +
										price_id +
										', *[for="give-radio-level-' +
										price_id +
										'"], .give-donation-level-' +
										price_id
								)
								.html(label_string);
						}
					}
				}

				// Trigger variable price.
				if ('custom' !== form_price_id) {
					Give.form.fn.autoSetMultiLevel(
						form.find('.give-donation-levels-wrap').find('*[data-price-id="' + form_price_id + '"]')
					);
				}

				// Create array of the new converted variable's amount.
				var amount_obj = {};

				amount_obj[price_id] = {
					'converted-amount': Give_Currency_Switcher.module.convertCurrency(
						variable_amount,
						form,
						currency_old
					), // Converted amount for this price id.
					'custom-amount': variable_custom_prices[current_currency], // Custom amount for this price id, if exists.
				};

				// Store custom amount.
				variable_amounts.push(amount_obj);
			});

			if (0 < variable_old_amounts.length) {
				// Update variable amounts.
				variable_old_amounts.attr('data-custom_amount', JSON.stringify(variable_amounts));
			} else {
				// Store all the custom amount.
				$('<span/>', {
					class: 'give-cs-variable-amount give-hidden',
					'data-custom_amount': JSON.stringify(variable_amounts),
				}).appendTo(form);
			}
		},

		/**
		 * Convert/Update Goal amount When switch currency.
		 *
		 * @param {object} form
		 * @param {string} newCurrency
		 * @param decimal_separator
		 */
		updateGoalAmount: function (form, newCurrency, decimal_separator) {
			const $goalType = Give.form.fn.getInfo('goal_format', form),
				$goalWrapper = Give_Currency_Switcher.isMultiStepForm
					? form.parent().find('.income-stats')
					: form.parent().find('.give-goal-progress');

			// If Goal amount is disabled or goal type isn't amount.
			if ('undefined' === $goalType || 'amount' !== $goalType) {
				return;
			}

			if (Give_Currency_Switcher.isMultiStepForm) {
				const exchangeRates = Give_Currency_Switcher.module.getExchangeRates(form),
					exchangeRate = exchangeRates[newCurrency] !== 0 ? exchangeRates[newCurrency] : 1,
					goalAmount = Give.fn.formatCurrency(
						form.attr('data-goal-amount') * exchangeRate,
						{
							symbol: Give.form.fn.getInfo('currency_symbol', form),
							position: Give.form.fn.getInfo('currency_position', form),
							precision: 0,
						},
						form
					),
					incomeAmount = Give.fn.formatCurrency(
						form.attr('data-income-amount') * exchangeRate,
						{
							symbol: Give.form.fn.getInfo('currency_symbol', form),
							position: Give.form.fn.getInfo('currency_position', form),
							precision: 0,
						},
						form
					);

				// Convert goal amount to selected currency.
				$goalWrapper.find('.goal .number').text(goalAmount);
				// Convert income to selected currency.
				$goalWrapper.find('.raised .number').text(incomeAmount);
			} else {
				goalAmounts = JSON.parse($goalWrapper.find('span.goal-text').attr('data-amounts'));
				incomeAmounts = JSON.parse($goalWrapper.find('span.income').attr('data-amounts'));

				// Convert goal amount to selected currency.
				$goalWrapper.find('span.goal-text').text(goalAmounts[newCurrency]);
				// Convert income to selected currency.
				$goalWrapper.find('span.income').text(incomeAmounts[newCurrency]);
			}
		},

		/**
		 * Convert Minimum amount
		 *
		 * @since 1.1
		 *
		 * @param form
		 * @param price_id
		 */
		updateMinimumAmount: function (form, price_id) {
			var selected_currency = Give_Currency_Switcher.module.getValueOf('give-cs-currency', form), // Get the selected currency.
				give_custom_amounts = Give_Currency_Switcher.module.getCustomAmounts(form, price_id), // Get all of the custom amounts.
				give_custom_price_amount = give_custom_amounts[selected_currency] || false; // Get the custom amount.

			// Convert the Minimum amount.
			var converted_give_minimum_amount = Give_Currency_Switcher.module.convertCurrency(
				Give_Currency_Switcher.module.getDefaultMinimumAmount(form),
				form,
				give_global_vars.currency
			);

			// If custom price amount is less the minimum amount.
			if (give_custom_price_amount && give_custom_price_amount < converted_give_minimum_amount) {
				converted_give_minimum_amount = give_custom_price_amount;
			}

			var format_args = {
				symbol: '',
				decimal: '.',
				thousand: '',
				precision: Give.form.fn.getInfo('number_decimals', form),
			};

			// Update donation minimum amount.
			Give_Currency_Switcher.module.setValueOf(
				'give-form-minimum',
				Give.fn.formatCurrency(converted_give_minimum_amount, format_args, form),
				form
			);

			// Get the maximum amount range value.
			if ((form_maximum_amount = Give_Currency_Switcher.module.getDefaultMinimumAmount(form, 'max'))) {
				// Convert the amount.
				form_maximum_amount = Give_Currency_Switcher.module.convertCurrency(
					form_maximum_amount,
					form,
					give_global_vars.currency
				);

				if (give_custom_price_amount && give_custom_price_amount > form_maximum_amount) {
					form_maximum_amount = give_custom_price_amount;
				}

				// Update donation maximum amount.
				Give_Currency_Switcher.module.setValueOf(
					'give-form-maximum',
					Give.fn.formatCurrency(form_maximum_amount, format_args, form),
					form
				);
			}
		},

		/**
		 * Payment Gateway changed.
		 *
		 * @since 1.0
		 *
		 * @param ev
		 * @param response HTML Content.
		 * @param formId   Form ID
		 */
		changed_payment_gateway: function (ev, response, formId) {
			// Declare required form details.
			var formObject = jQuery(document).find('form#' + formId),
				supportedGateways = Give_Currency_Switcher.module.getSupportedCurrencies(formObject),
				selectedCurrency = formObject.find('input[name="give-cs-form-currency"]').val(),
				currencyDropdown = formObject.find('.give-cs-select-currency'),
				$totalAmountField = formObject.find('span.give-final-total-amount'), // Final donation amount container selector.
				currentAmount = Give.form.fn.getAmount(formObject); // Selected donation amount value.;

			// Bailout, if currency switcher is not enabled.
			if (!Give_Currency_Switcher.module.isCurrencySwitcherEnable(formObject)) {
				return;
			}

			// Remove currency not supported old notices.
			formObject.find('#give_currency_not_support.give_notice').parent().remove();

			// Remove all of the currencies.
			formObject.find('.give-cs-select-currency').empty();

			if (1 === Object.keys(supportedGateways).length) {
				formObject.find('span.give-currency-symbol').addClass('give-cs-dropdown-hidden');
				formObject.find('select[name="give-cs-currency"]').addClass('give-cs-remove-cursor').hide();
				formObject.find('.give-cs-mini-dropdown-icon').addClass('give-cs-reset-width');
			} else {
				formObject.find('span.give-currency-symbol').removeClass('give-cs-dropdown-hidden');
				formObject.find('select[name="give-cs-currency"]').removeClass('give-cs-remove-cursor').show();
				formObject.find('.give-cs-mini-dropdown-icon').removeClass('give-cs-reset-width');
			}

			// Re-render the currency drop-down.
			Give_Currency_Switcher.init_switcher();

			Object.keys(supportedGateways).forEach(function (key) {
				if (-1 !== $.inArray(Give.form.fn.getGateway(formObject), supportedGateways[key].gateways)) {
					// Create currency option using jQuery DOM for security.
					var currency_option = jQuery('<option/>', {
						value: key,
						html: supportedGateways[key]['currency_label'],
					});

					// Append currency.
					formObject.find('.give-cs-select-currency').append(currency_option);
				}
			});

			// Show the currency switch drop-down.
			formObject.find('.give-currency-switcher-wrap').show();

			$totalAmountField.attr('data-total', currentAmount);
			$totalAmountField.text(
				Give.fn.formatCurrency(
					currentAmount,
					{
						symbol: Give.form.fn.getInfo('currency_symbol', formObject),
						position: Give.form.fn.getInfo('currency_position', formObject),
					},
					formObject
				)
			);

			if (!Give_Currency_Switcher.module.isGatewaySupportCurrency(formObject, selectedCurrency)) {
				Give_Currency_Switcher.module.showCurrencyNotSupportGatewayNotice(formObject, selectedCurrency);

				if (1 === currencyDropdown.length) {
					// Auto switch to available currency.
					Give_Currency_Switcher.autoSwitchCurrency(formObject, currencyDropdown.find('option').val());
				} else {
					// Reset Currency switcher.
					formObject.find('.give-donation-amount .give-text-input').trigger('blur');

					// Select switched currency.
					Give_Currency_Switcher.autoSwitchCurrency(formObject, selectedCurrency);

					// Disable Donation now button.
					formObject.find('#give-purchase-button').attr('disabled', true);

					return;
				}
			}

			if (0 < formObject.find('.give-cs-select-currency option[value="' + selectedCurrency + '"]').length) {
				// Select switched currency.
				Give_Currency_Switcher.autoSwitchCurrency(formObject, selectedCurrency);
			} else if (0 < formObject.find('.give-cs-select-currency').length) {
				$('<span/>', {
					class: 'give-cs-old-exchange-rates',
					'data-old_exchange_rates': JSON.stringify(
						Give_Currency_Switcher.module.getExchangeRates(formObject)
					),
				}).appendTo(formObject);

				// Change to base currency.
				formObject
					.find('.give-cs-select-currency')
					.val(Give_Currency_Switcher.module.getValueOf('give-cs-base-currency', formObject))
					.change();
			}
		},

		/**
		 * This function is used to auto switch currency based on the currency provided.
		 *
		 * @since 1.3.3
		 *
		 * @param {object} formObject Donation Form Object.
		 * @param {string} currency   Currency code.
		 */
		autoSwitchCurrency: function (formObject, currency) {
			// Auto switch to the currency provided.
			formObject.find('.give-cs-select-currency').val(currency).trigger('change');
		},

		/**
		 * Remove sanitized donation total
		 *
		 * @since 1.0
		 * @param e
		 */
		remove_sanitize_amount: function (e) {
			var $form = jQuery(this).closest('form'), // Get the current form.
				decimal_separator = Give.form.fn.getInfo('decimal_separator', $form),
				current_total = Give.fn.unFormatCurrency(
					$form.find('input[name="give-amount"]').val(),
					decimal_separator
				),
				old_total = Give.fn.unFormatCurrency(
					$form.find('.give-cs-amount-totals').attr('data-total'),
					decimal_separator
				);

			// If give amount changed then remove old sanitize and custom amount.
			if (current_total !== old_total) {
				// Get the currency sign from the selected donation level.
				$form.find('.give-cs-amount-totals').removeAttr('data-sanitize-amount data-custom_amount');
			}
		},

		/**
		 * It contains method to get and set value for input fields
		 * in-between the form.
		 *
		 * @since 1.0
		 */
		module: {
			/**
			 * Get value of any particular field.
			 *
			 * @since 1.0
			 *
			 * @param {string} key
			 * @param {jQuery} form jQuery object.
			 * @returns {*}
			 */
			getValueOf: function (key, form) {
				form = 0 < form.length ? form : {};
				key = 0 < key.length ? key : '';

				var result_var = false;

				switch (key) {
					case 'base_currency':
						result_var = form.find('*[name="give-cs-base-currency"]').val();
						break;

					case 'currency_rate':
						result_var = form.find('*[name="give-cs-exchange-rate"]').val();
						break;

					case 'switched_currency':
						result_var = form.find('*[name="give-cs-form-currency"]').val();
						break;

					case 'acronyms':
						var give_cs_json = jQuery.parseJSON(give_cs_json_obj);
						result_var = give_cs_json['form_' + Give.form.fn.getInfo('form-id', form)]['currency_acronym'];
						break;

					case 'give-cs-currency':
					case 'selected-currency':
						result_var = form.find('input[name="give-cs-form-currency"]').val();
						break;

					default:
						result_var = form.find('*[name="' + key + '"]').val();
						'undefined' === result_var ? form.attr('*[data-"' + key + '"]') : '';
				}
				return 'undefined' !== result_var ? result_var : '';
			},

			/**
			 * Set value of currency switcher.
			 *
			 * @since 1.0
			 *
			 * @param {string} key
			 * @param {string} value
			 * @param {jQuery} form
			 * @returns {boolean}
			 */
			setValueOf: function (key, value, form) {
				if (0 >= key.length) {
					return false;
				}

				form = 0 < form.length ? form : {};
				value = '' !== value.length ? value : '';

				// Get field.
				var field = form.find('input[name="' + key + '"]');

				// Set value.
				'undefined' !== field ? field.val(value) : '';
			},

			/**
			 * Get exchange rates.
			 *
			 * @since 1.0
			 *
			 * @param {jQuery} $form
			 */
			getExchangeRates: function ($form) {
				// Get the give form id.
				var give_form_id = $form ? Give.form.fn.getInfo('form-id', $form) : 0,
					give_cs_json = jQuery.parseJSON(give_cs_json_obj),
					exchange_rates = [];

				if (0 !== give_form_id) {
					exchange_rates = give_cs_json['form_' + give_form_id]['exchange_rates'];
				}

				$.each(exchange_rates, function (currency_key, data) {
					if (-1 !== $.inArray(Give.form.fn.getGateway($form), data.support_gateways)) {
						exchange_rates[currency_key] = data.rates;
					}
				});

				return exchange_rates;
			},

			/**
			 * Get the Number of the decimal.
			 *
			 * @param $form
			 * @param $currency_code
			 * @returns {Array|bool}
			 */
			getRatesDecimalNumber: function ($form, $currency_code) {
				if (!$form) {
					return false;
				}

				// Get the give form id.
				var give_form_id = Give.form.fn.getInfo('form-id', $form),
					give_cs_json = jQuery.parseJSON(give_cs_json_obj),
					decimal_numbers = give_cs_json['form_' + give_form_id]['decimal_number'];

				return $currency_code ? decimal_numbers[$currency_code] : decimal_numbers;
			},

			/**
			 * Get the custom amount.
			 *
			 * @since 1.0
			 *
			 * @param $form
			 * @param $price_id
			 * @returns {Array}
			 */
			getCustomAmounts: function ($form, $price_id) {
				// Get the give form id.
				var give_form_id = $form ? Give.form.fn.getInfo('form-id', $form) : 0,
					give_cs_json = jQuery.parseJSON(give_cs_json_obj),
					amounts = [];

				// Get the custom amounts.
				var custom_amounts = give_cs_json['form_' + give_form_id]['custom_amounts'];

				$.each(custom_amounts, function (currency_key, currency_data) {
					amounts[currency_key] = false;

					// If variable has no prices.
					if ('' !== $price_id && -1 !== $price_id && undefined !== $price_id) {
						if (undefined !== currency_data[$price_id] && false !== currency_data[$price_id]) {
							amounts[currency_key] = currency_data[$price_id]['raw_amount'];
						}
					} else {
						if (undefined !== currency_data['raw_amount']) {
							amounts[currency_key] = currency_data['raw_amount'];
						}
					}
				});
				return amounts;
			},

			/**
			 * Get the supported currency.
			 *
			 * @since 1.0
			 */
			getSupportedCurrencies: function ($form_object) {
				// Return, if currency switcher isn't activate.
				if (!Give_Currency_Switcher.module.isCurrencySwitcherEnable($form_object)) {
					return;
				}

				var form_id = $form_object ? Give.form.fn.getInfo('form-id', $form_object) : 0,
					give_cs_json = jQuery.parseJSON(give_cs_json_obj);

				// Return empty array when form id is not valid.
				if ('undefined' === typeof form_id) {
					return [];
				}

				// Bailout, if currency switcher is not enabled.
				if ('undefined' === typeof give_cs_json['form_' + form_id]['support_gateways']) {
					return;
				}

				var supported_gateways = [],
					supported_gateway_list = give_cs_json['form_' + form_id]['support_gateways'],
					form_gateway = Give.form.fn.getGateway($form_object);

				Object.keys(supported_gateway_list).forEach(function (key, value) {
					if (-1 !== $.inArray(form_gateway, supported_gateway_list[key].gateways)) {
						supported_gateways[key] = supported_gateway_list[key];
						supported_gateways[key].currencySetting = give_cs_json['supported_currency'][key];
					}
				});

				return supported_gateways;
			},

			/**
			 * Get the supported currency.
			 *
			 * @since 1.5.1
			 */
			getAllSupportedCurrencies: function () {
				return jQuery.parseJSON(give_cs_json_obj)['supported_currency'];
			},

			/**
			 * Get Donation amount.
			 *
			 * @since 1.0
			 *
			 * @param {jQuery} form Donation Form selector
			 * @param {string} decimal_separator
			 */
			getDonationAmount: function (form, decimal_separator) {
				form = 'undefined' !== form ? form : {};

				decimal_separator = decimal_separator || Give.form.fn.getInfo('decimal_separator', form);

				var amount_field = form.find('span.give-cs-amount-totals');

				var /**
					 * Sanitize amount: When customer put custom amount converted amount may have long decimal precision
					 * so for accurate conversion we will have to use full amount with original amount.
					 */
					sanitize_amount = amount_field.attr('data-sanitize-amount'),
					/**
					 * Custom Amount: If the donation form has Give's custom prices option enabled and if donor provide their
					 * custom value then it will take custom amount instead of donation actual amount.
					 */
					custom_amount = amount_field.attr('data-custom_amount'),
					donation_amount = form.find('input[name="give-amount"]').val(); // Donation amount text field.

				/**
				 * If currency has custom amount when we are storing the conversion
				 * of amount into "input[name="give-cs-custom-amount"]" field.
				 *
				 * For Eg: If custom amount is set for INR is ₹10 then we will store
				 * the conversion of it with base currency in it.
				 *
				 * @type {string}
				 */
				var donation_custom_amount =
					typeof custom_amount !== typeof undefined && false !== custom_amount
						? custom_amount
						: 0 < form.find('input[name="give-cs-custom-amount"]').length
						? form.find('input[name="give-cs-custom-amount"]').val()
						: '';

				// Return donation form amount based on various action.
				return 0 < donation_custom_amount
					? donation_custom_amount
					: 0 < sanitize_amount
					? sanitize_amount
					: Give.fn.unFormatCurrency(donation_amount, decimal_separator);
			},

			/**
			 * Get form's base amounts.
			 *
			 * @param $form
			 * @param $price_id
			 */
			getBaseAmounts: function ($form, $price_id) {
				var baseAmounts = JSON.parse(Give.form.fn.getInfo('give_cs_base_amounts', $form));

				if (-1 === $price_id) {
					$price_id = 0 !== baseAmounts.custom ? 'custom' : 0;
				}

				return baseAmounts[$price_id];
			},

			/**
			 * Convert currency to another currency.
			 *
			 * @since 1.0
			 *
			 * @param {float} amount
			 * @param {jQuery} form
			 * @param {string} from_currency
			 * @param {string} to_currency
			 * @param {boolean} isForm
			 * @param {string} priceID
			 */
			convertCurrency: function (amount, form, from_currency, to_currency, isForm, priceID) {
				// Get the selected currency if not passed.
				to_currency = to_currency || Give_Currency_Switcher.module.getValueOf('give-cs-currency', form);

				if (from_currency === to_currency) {
					return amount;
				}

				isForm = isForm || false;

				const oldCurrencyExchangeRates = form
					.find('span.give-cs-old-exchange-rates')
					.attr('data-old_exchange_rates');
				const getExchangeRates = Give_Currency_Switcher.module.getExchangeRates(form, true);
				const currency_rates =
					0 < form.find('span.give-cs-old-exchange-rates').length
						? null !== oldCurrencyExchangeRates
							? JSON.parse(oldCurrencyExchangeRates)
							: getExchangeRates
						: getExchangeRates;

				/**
				 * If this conversion is for donation form then get the amount in base amount always.
				 * Otherwise, convert the amount as per the currency.
				 */
				if (!isForm) {
					// If switched currency is not give base currency.
					if (give_global_vars.currency !== to_currency) {
						// If Switched currency is not form currency.
						if (give_global_vars.currency !== from_currency) {
							amount = amount / currency_rates[from_currency]; // Revert back to the original amount.
						}
					} else {
						// If donation has already changed to donor's preferred currency.
						amount = amount / currency_rates[from_currency];
					}
				} else {
					// If the calculation is about form calculation.
					priceID = 'undefined' === typeof priceID ? Give.form.fn.getPriceID(form) : priceID;
					amount = Give_Currency_Switcher.module.getBaseAmounts(form, priceID);
				}

				return (converted_amount =
					0 < currency_rates[to_currency] ? amount * currency_rates[to_currency] : amount);
			},

			/**
			 * Update Fee Recovery base_amount.
			 *
			 * @since 1.0
			 *
			 * @param {string} old_currency
			 * @param {string} new_currency
			 * @param {jQuery} form
			 * @param {string} decimal_separator
			 */
			updateFeeAmount: function (old_currency, new_currency, form, decimal_separator) {
				// Check if Fee Recovery is activated for this form.
				if (
					0 < form.find('input[name="give-fee-recovery-settings"]').length &&
					undefined !== typeof Give_Fee_Recovery
				) {
					// Get fee recovery settings.
					var fee_recovery_data = JSON.parse(form.find('input[name="give-fee-recovery-settings"]').val());

					if (false === fee_recovery_data.fee_recovery) {
						return;
					}

					// If the setting is configured for all gateways.
					if (
						'undefined' !== typeof fee_recovery_data.fee_data.all_gateways &&
						'' !== fee_recovery_data.fee_data.all_gateways.base_amount
					) {
						fee_recovery_data.fee_data.all_gateways.base_amount =
							Give_Currency_Switcher.module.convertCurrency(
								fee_recovery_data.fee_data.all_gateways.base_amount,
								form,
								old_currency,
								new_currency
							);
					} else {
						// If setting is configured for each gateway separately.
						$.each(fee_recovery_data.fee_data, function (gateway_key, data) {
							fee_recovery_data.fee_data[gateway_key].base_amount =
								Give_Currency_Switcher.module.convertCurrency(
									fee_recovery_data.fee_data[gateway_key].base_amount,
									form,
									old_currency,
									new_currency
								);
						});
					}

					// Update Fee Recovery data.
					form.find('input[name="give-fee-recovery-settings"]').val(JSON.stringify(fee_recovery_data));
					form.find('span.give-cs-old-exchange-rates').remove();

					// Update fee amount.
					Give_Fee_Recovery.give_fee_update(
						form,
						true,
						form.find('#give-amount').val(),
						Give.form.fn.getGateway(form)
					);
				}
			},

			/**
			 * Get default minimum amount, Amount in base currency.
			 *
			 * @since 1.0
			 *
			 * @param $form
			 * @param $type
			 */
			getDefaultMinimumAmount: function ($form, $type) {
				$type = $type || 'min';

				// Get the currency switcher object data.
				var form_data_obj =
					jQuery.parseJSON(give_cs_json_obj)['form_' + Give.form.fn.getInfo('form-id', $form)];

				return 'min' === $type ? form_data_obj['minimum_amount'] : form_data_obj['maximum_amount'];
			},

			/**
			 * Update currency switched message.
			 *
			 * @since 1.0
			 *
			 * @param form
			 * @param price_id
			 */
			updateCurrencyMessage: function (form, price_id) {
				if (!Give_Currency_Switcher.module.isCurrencySwitcherEnable(form)) {
					return;
				}

				// Get the custom amounts per currency.
				var custom_amounts = Give_Currency_Switcher.module.getCustomAmounts(form, price_id),
					message_container = form.find('.give-currency-switcher-msg'),
					switched_currency = Give.form.fn.getInfo('currency_code', form);

				if (null === switched_currency) {
					form.find('#give-purchase-button').attr('disabled', true);
					return false;
				}

				// Get the rate number of decimals.
				var rates_number_decimals = Give_Currency_Switcher.module.getRatesDecimalNumber(form);

				// If we have custom amount.
				if (custom_amounts[switched_currency]) {
					message_container.text(give_currency_switcher.cs_custom_price_message).show();
				} else {
					var current_rate = parseFloat(form.find('input[name="give-cs-exchange-rate"]').val());

					if (0 !== current_rate) {
						message_container.text(
							message_container
								.data('rawtext')
								.replace('{new_currency}', switched_currency)
								.replace(
									'{new_currency_rate}',
									Give.fn.formatCurrency(
										current_rate,
										{precision: rates_number_decimals[switched_currency]},
										form
									)
								)
								.replace('{base_currency}', give_global_vars.currency)
						);
					}
				}

				// If base currency and switched currency are same then hide this message.
				give_global_vars.currency === switched_currency || 0 === current_rate
					? form.find('.give-currency-switcher-msg-wrap').hide()
					: form.find('.give-currency-switcher-msg-wrap').show();
			},

			/**
			 * Check if currency switcher is enabled or not.
			 *
			 * @since 1.0
			 * @param $form
			 */
			isCurrencySwitcherEnable: function ($form) {
				if ('undefined' === typeof give_cs_json_obj || 'null' === give_cs_json_obj) {
					return false;
				}

				const csConfig = jQuery.parseJSON(give_cs_json_obj),
					formId = Give.form.fn.getInfo('form-id', $form),
					formDataKey = 'form_' + formId;

				if (!csConfig.hasOwnProperty(formDataKey)) {
					return false;
				}

				// Get the currency switcher object data.
				return jQuery.parseJSON(give_cs_json_obj)[formDataKey];
			},

			/**
			 * Get the supported currency.
			 *
			 * @since 1.3.1
			 */
			isGatewaySupportCurrency: function ($form_object, new_currency) {
				var form_id = $form_object ? Give.form.fn.getInfo('form-id', $form_object) : 0,
					give_cs_json = jQuery.parseJSON(give_cs_json_obj);

				if (0 === form_id) {
					return false;
				}

				var currency_code = new_currency;
				if ('undefined' === typeof new_currency) {
					currency_code = Give.form.fn.getInfo('currency_code', $form_object);
				}

				var supported_gateway_list = give_cs_json['form_' + form_id]['support_gateways'],
					form_gateway = Give.form.fn.getGateway($form_object),
					currency_based_gateways = supported_gateway_list[currency_code].gateways;

				return -1 !== $.inArray(form_gateway, currency_based_gateways);
			},

			/**
			 * Display Currency not Support Notice.
			 *
			 * @param $form_object
			 * @param new_currency
			 */
			showCurrencyNotSupportGatewayNotice: function ($form_object, new_currency) {
				// Remove currency not supported old notices.
				$form_object.find('#give_currency_not_support.give_notice').parent().remove();

				$form_object.find('#give-purchase-button').attr('disabled', true);
				$form_object.find('.give-currency-switcher-msg-wrap').hide();

				var currency_chosen = new_currency,
					form_currency = $form_object.data('currency_code');

				if ('undefined' === typeof currency_chosen) {
					currency_chosen = form_currency;
				}

				// Add notice if currency is not support by selected gateway.
				$('<div/>', {
					class: 'give_notices give_errors',
					id: 'give_error_error',
				})
					.append(
						$('<p/>', {
							class: 'give_error give_notice',
							id: 'give_currency_not_support',
							'data-dismissible': '1',
							'dismiss-type': 'manual',
						}).html(
							give_currency_switcher.currency_not_support_message
								.replace('{currency_code}', currency_chosen)
								.replace(
									'{payment_gateway}',
									$form_object
										.find('#give-gateway-option-' + Give.form.fn.getGateway($form_object))
										.text()
								) +
								'<img class="notice-dismiss give-notice-close" src="' +
								give_currency_switcher.notice_dismiss_image +
								'">'
						)
					)
					.prependTo($form_object);
			},
		},

		/**
		 * Prevent drop-down, if we don't have currency other than base currency.
		 *
		 * @since 1.0
		 * @param e
		 * @returns {boolean}
		 */
		chosenCurrency: function (e) {
			if (1 >= $(this).find('option').length) {
				e.preventDefault();
				return false;
			}
		},
	};

	$(function () {
		// Call initially.
		Give_Currency_Switcher.init();
	});
})(jQuery);
