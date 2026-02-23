/**
 * Give Fee Recovery Frontend JS.
 */

import {give_fee_format_amount, give_fee_unformat_amount, give_fee_calculate} from './utilities';

var give_global_vars = window.give_global_vars;

jQuery.noConflict();

(function ($) {
    window.Give_Fee_Recovery = {
        init: function () {
            /**
             * In case there are multiple donation forms on the same page.
             * So we need to calculate the fee amount of form separately.
             * Here we're fetching all of the forms and calculating the the fee amount for specific donation form.
             */
            $('.give-form-wrap').each(function () {
                // Get the Donation form selector.
                var form = $(this).find('.give-form'),
                    give_fee_disable = form.find('.give-fee-disable').val(),
                    is_enable = !!parseInt(give_fee_disable),
                    gateway = form.find('input.give-gateway:radio:checked').val(),
                    give_total = form.find('input[name="give-amount"]').val();

                $(this).find('.give-fee-message').hide();
                if (is_enable) {
                    $(this).find('.give-fee-message').show();
                }

                // This scenario is for "Edit Amount" or "Update Payment Method" stability.
                if (typeof gateway === 'undefined') {
                    gateway = form.attr('data-gateway');
                }

                // Calculate Fee Recovery.
                if (is_enable) {
                    Give_Fee_Recovery.give_fee_update(form, true, give_total, gateway);
                }
            });
        },

        /**
         * Role of this method is to calculate the donation fee amount and update it wherever require.
         *
         * @param {jQuery} form The current Donation form.
         * @param {boolean} check_option Whether check if fee includes or not.
         * @param {float} give_total Give Total.
         * @param {string} gateway Donation amount.
         *
         * @since 1.8.0 add support for max fee coverage
         *
         * @returns {boolean}
         */
        give_fee_update: function (form, check_option, give_total, gateway) {
            var give_final_total = form.find('.give-final-total-amount'),
                give_fee_label_text = form.find('.give-fee-message-label-text'),
                fee_break_down_message = form.find('.fee-break-down-message'),
                break_down_message = fee_break_down_message.data('breakdowntext'),
                fee_mode_value = form.find('.give_fee_mode_checkbox').val(),
                form_decimal_separator = Give.form.fn.getInfo('decimal_separator', form),
                new_total = give_fee_unformat_amount(give_total, form_decimal_separator),
                data = JSON.parse(form.find('input[name="give-fee-recovery-settings"]').val());

            if (
                form.has('.give_fee_mode_checkbox').length >= 1 &&
                fee_mode_value !== 0 &&
                'undefined' !== fee_mode_value
            ) {
                check_option = form.find('.give_fee_mode_checkbox').is(':checked');
            }

            // Return, if Fee recovery option is disabled.
            if (form.find('.give-fee-message').length === 0) {
                return false;
            }

            give_final_total.show();
            fee_break_down_message.hide();

            var is_fee_recovery = data.fee_recovery,
                is_all_gateways = data.fee_data.all_gateways,
                base_amount = 0,
                percentage = 0,
                is_break_down = true,
                give_fee_status = true,
                give_fee_disable = false,
                maxAmount = false,
                fee = 0;

            // Check whether Fee Recovery Enable/disable.
            if (is_fee_recovery) {
                // Check whether Gateway support is All Gateways/Set per Gateway.
                if (is_all_gateways) {
                    base_amount = data.fee_data.all_gateways.base_amount;
                    percentage = data.fee_data.all_gateways.percentage;
                    is_break_down = data.fee_data.all_gateways.is_break_down;
                    give_fee_status = data.fee_data.all_gateways.give_fee_status;
                    give_fee_disable = data.fee_data.all_gateways.give_fee_disable;
                    maxAmount = data.fee_data.all_gateways.maxAmount;
                } else {
                    jQuery.each(data.fee_data, function (index, data_object) {
                        if (gateway === index) {
                            base_amount = data_object.base_amount;
                            percentage = data_object.percentage;
                            is_break_down = data_object.is_break_down;
                            give_fee_status = data_object.give_fee_status;
                            give_fee_disable = data_object.give_fee_disable;
                            maxAmount = data_object.maxAmount;
                        }
                    });
                }

                var give_fee_zero_based_currency = give_fee_recovery_object.give_fee_zero_based_currency,
                    zero_based_currencies = JSON.parse(give_fee_zero_based_currency),
                    currency_code = Give.form.fn.getInfo('currency_code', form),
                    precision = Give.form.fn.getInfo('number_decimals', form);

                // Set precision.
                if (1 >= parseInt(precision) && -1 === jQuery.inArray(currency_code, zero_based_currencies)) {
                    precision = 2;
                }

                // Calculate Fee amount.
                fee = give_fee_calculate(
                    percentage,
                    base_amount,
                    give_fee_unformat_amount(give_total, form_decimal_separator),
                    give_fee_disable
                );

                if (-1 !== jQuery.inArray(currency_code, zero_based_currencies)) {
                    precision = 0;
                    fee = Math.ceil(fee);
                }

                // Limit the fee amount is the maximum fee amount is reached.
                maxAmount = parseFloat(maxAmount);

                if (maxAmount > 0) {
                    fee = Math.min(fee, maxAmount);
                }

                // Add Fee with donation total if Donor Opt-in of Force-opt-in.
                if (check_option) {
                    new_total += give_fee_unformat_amount(Give.fn.formatCurrency(fee, {precision: precision}, form));
                }

                // Update Break-down message.
                var break_down_amount_replace = break_down_message.replace(
                        '{amount}',
                        give_fee_format_amount(give_fee_unformat_amount(give_total, form_decimal_separator), form)
                    ),
                    break_down_updated_message = break_down_amount_replace.replace(
                        '{fee_amount}',
                        give_fee_format_amount(fee, form)
                    );

                // Show Fee message based on Fee recovery enable/disable.
                if (!give_fee_disable) {
                    // Below code add to handle spacial case
                    // Check: https://github.com/impress-org/give-fee-recovery/pull/293
                    var isFormDisplayTypeSetToModel = false;
                    var formId = form[0].querySelector('input[name="give-form-id"]').value;
                    var formContainerSelector = '#give-form-' + formId + '-wrap';
                    var formContainer = Array.from(document.querySelectorAll(formContainerSelector))
                        .filter(function (container) {
                            return !container.querySelector('form.give-form');
                        })
                        .pop();

                    if (formContainer) {
                        isFormDisplayTypeSetToModel = formContainer.classList.contains('give-display-modal');
                    }

                    if (isFormDisplayTypeSetToModel) {
                        if (!form.parent().hasClass('mfp-content')) {
                            form.find('.give-fee-recovery-donors-choice').show();
                        }
                    } else {
                        form.find('.give-fee-recovery-donors-choice').show();
                    }

                    form.find('.fee-coverage-required').show();
                } else {
                    form.find('.give-fee-recovery-donors-choice').hide();
                    form.find('.fee-coverage-required').hide();
                }

                // Append give status based on condition.
                if (give_fee_status) {
                    form.find('input[name="give-fee-status"]').remove();
                    form.prepend('<input type="hidden" name="give-fee-status" value="enabled"/>');
                } else {
                    form.find('input[name="give-fee-status"]').remove();
                    form.prepend('<input type="hidden" name="give-fee-status" value="disabled"/>');
                }

                if (check_option && is_break_down) {
                    if ('undefined' !== typeof break_down_message) {
                        fee_break_down_message.show();
                        form.find('input[name="give-payment-mode"]').remove();
                        form.prepend('<input type="hidden" name="give-payment-mode" value="' + gateway + '"/>');
                        fee_break_down_message.text(break_down_updated_message);
                    }
                }

                var give_fee_message = form.find('.give-fee-message-label').data('feemessage'),
                    give_fee_updated_message = give_fee_message.replace(
                        '{fee_amount}',
                        give_fee_format_amount(fee, form)
                    );

                give_fee_label_text.text(give_fee_updated_message);

                if ('undefined' !== typeof give_global_vars) {
                    precision = give_global_vars.number_decimals;
                } else {
                    precision = give_vars.currency_decimals;
                }

                // Set precision.
                if (1 >= parseInt(precision)) {
                    precision = 2;
                }

                setTimeout(function () {
                    give_final_total
                        .text(give_fee_format_amount(new_total, form))
                        .attr('data-total', Give.fn.formatCurrency(new_total, {precision: precision}, form));
                }, 0);

                if (0 === give_fee_unformat_amount(Give.fn.formatCurrency(fee, {precision: precision}, form))) {
                    fee_break_down_message.hide();
                    give_fee_label_text.hide();
                    $('.give-fee-message-label').hide();
                } else {
                    give_fee_label_text.show();
                    $('.give-fee-message-label').show();
                }

                // Remove fee mode fields and prepend newest.
                form.find('input[name="give-fee-mode-enable"]').remove();
                form.prepend('<input type="hidden" name="give-fee-mode-enable" value="' + check_option + '"/>');

                // Remove Give fee amount and prepend newest.
                form.find('input[name="give-fee-amount"]').remove();
                form.prepend(
                    '<input type="hidden" name="give-fee-amount" value="' +
                        give_fee_unformat_amount(Give.fn.formatCurrency(fee, {precision: precision}, form)) +
                        '"/>'
                );
            } else {
                form.find('input[name="give-fee-status"]').remove();
                form.prepend('<input type="hidden" name="give-fee-status" value="disabled"/>');
            } // End if().
        },
    };

    /**
     * On page load.
     */
    $(function () {
        var $body = $('body');

        /**
         * Call Fee Recovery on check-box checked.
         */
        $body
            .on('change', '.give_fee_mode_checkbox', function () {
                // Update donation total when document is loaded.
                var form = $(this).closest('form.give-form'),
                    check_option = $(this).is(':checked'),
                    gateway = form.find('input.give-gateway:radio:checked').val(),
                    give_total = form.find('input[name="give-amount"]').val();

                // This scenario is for "Edit Amount" or "Update Payment Method" stability.
                if (typeof gateway === 'undefined') {
                    gateway = form.attr('data-gateway');
                }

                // Calculate and show fee and total amount.
                Give_Fee_Recovery.give_fee_update(form, check_option, give_total, gateway);
            })
            .change();

        /**
         * Call Fee Recovery when the donation amount has been update through button,
         * drop-down, or by entering custom amount.
         */
        $(document).on('give_donation_value_updated', function (e, parent_form, amount) {
            // If event is called when field blurred.
            if (!parent_form) {
                parent_form = $(this).closest('form.give-form'); // If parent form is empty, assign the current form object.
            }
            var gateway = parent_form.find('input.give-gateway:radio:checked').val(),
                give_total =
                    'undefined' === typeof amount ? parent_form.find('input[name="give-amount"]').val() : amount; // Check if amount from Multi donation blur text have value.

            // This scenario is for "Edit Amount" or "Update Payment Method" stability.
            if (typeof gateway === 'undefined') {
                gateway = form.attr('data-gateway');
            }

            Give_Fee_Recovery.give_fee_update(parent_form, true, give_total, gateway); // Calculate and show updated fee and total donation amount.
        });

        /**
         * This will be used to trigger when blur event on subscription amount field
         * will occur to calculate the fees as needed.
         */
        $(document).on('give_recurring_donation_amount_updated', function (e, parentForm, amount) {
            // If event is called when field blurred.
            if (!parentForm) {
                parentForm = $(this).closest('form.give-form'); // If parent form is empty, assign the current form object.
            }

            var gateway = parentForm.attr('data-gateway');

            Give_Fee_Recovery.give_fee_update(parentForm, true, amount, gateway); // Calculate and show updated fee and total donation amount.
        });

        /**
         * Re-calculate the fee and donation amount when gateway on the donation form has been changed.
         */
        $(document).on('give_gateway_loaded', function (e, response, form_id) {
            var form = $(e.currentTarget.activeElement).closest('form.give-form');
            if (0 === form.length) {
                form = $('#' + form_id);
            }

            var gateway = form.find('li.give-gateway-option-selected input[name="payment-mode"]').val(),
                give_total = form.find('input[name="give-amount"]').val();

            // This scenario is for "Edit Amount" or "Update Payment Method" stability.
            if (typeof gateway === 'undefined') {
                gateway = form.attr('data-gateway');
            }

            Give_Fee_Recovery.give_fee_update(form, true, give_total, gateway);
        });

        Give_Fee_Recovery.init();
    });
})(jQuery);
