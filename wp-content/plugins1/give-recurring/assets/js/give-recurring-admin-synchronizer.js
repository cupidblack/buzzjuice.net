/* globals Give_Recurring_Vars, Give_Sync_Vars*/

const {__, sprintf, _x, _n, _nx} = wp.i18n;

/**
 * Give Recurring Admin Subscription Synchronizer
 */
jQuery(document).ready(function ($) {
    /**
     *
     * @type {{progressInterval: null, hasError: boolean, hasFinished: boolean, pollingPeriod: number, updatePeriod: number, lastData: null, lastUpdate: null, init: init, show_message: function, sync_subscription_details: sync_subscription_details, sync_subscription_transactions: sync_subscription_transactions}}
     */
    var give_synchronizer = {
        progressInterval: null,
        hasError: false,
        hasFinished: false,
        pollingPeriod: 1000,
        updatePeriod: 250,
        lastData: null,
        lastUpdate: null,

        /**
         * Initialize.
         */
        init: function () {
            var body = $('body');
            // First sync the subscription details.
            body.on('sync_subscription_clicked', this.sync_subscription_details);
            // Sync transactions after details.
            body.on('subscription_details_synced', this.sync_subscription_transactions);
        },

        /**
         * Show message.
         *
         * @param modal_id
         * @param message
         */
        show_message: function (modal_id, message) {
            var modal = $(modal_id);
            modal.find('.modal-body').append(message);
        },

        print_notice: function (modal_id, message, notice_type = 'normal') {
            switch (notice_type) {
                case 'title':
                    message = '<h3>' + message + '</h3>';
                    break;
                case 'subtitle':
                    message = '<h4>' + message + '</h4>';
                    break;
                default:
                    message =
                        '<div class="give-recurring-sync-notice give-recurring-sync-notice-' +
                        notice_type +
                        '">' +
                        message +
                        '</div>';
            }

            var modal = $(modal_id);
            modal.find('.modal-body').append(message);
        },

        show_detail: function (modal_id, item, current_value, gateway_value) {
            give_synchronizer.print_notice(modal_id, sprintf(__('Checking %s', 'give-recurring'), item), 'subtitle');

            give_synchronizer.print_notice(
                modal_id,
                sprintf(__('Current %s: %s', 'give-recurring'), item, current_value)
            );

            give_synchronizer.print_notice(
                modal_id,
                sprintf(__('Gateway %s: %s', 'give-recurring'), item, gateway_value)
            );

            if (current_value !== gateway_value) {
                give_synchronizer.print_notice(
                    modal_id,
                    sprintf(__('Mismatch Detected: %s', 'give-recurring'), item),
                    'error'
                );
                give_synchronizer.print_notice(modal_id, __('Fixing Mismatch...', 'give-recurring'));
                give_synchronizer.print_notice(
                    modal_id,
                    sprintf(__('Mismatch Fixed: %s', 'give-recurring'), item),
                    'success'
                );
            } else {
                give_synchronizer.print_notice(modal_id, sprintf(__('%s is sync', 'give-recurring'), item), 'success');
            }
        },

        show_missing_transaction: function (modal_id, transaction) {
            var message = sprintf(
                __(
                    'A donation made on %s is missing and has been added. id: %s, gatewayTransactionId: %s, amount: %s',
                    'give-recurring'
                ),
                transaction['createdAt'],
                transaction['id'],
                transaction['gatewayTransactionId'],
                transaction['amount']
            );

            give_synchronizer.print_notice(modal_id, message, 'item_missing');
        },

        show_present_transaction: function (modal_id, transaction) {
            var message = sprintf(
                __(
                    'The donation made on %s is already recorded. id: %s, gatewayTransactionId: %s, amount: %s',
                    'give-recurring'
                ),
                transaction['createdAt'],
                transaction['id'],
                transaction['gatewayTransactionId'],
                transaction['amount']
            );

            give_synchronizer.print_notice(modal_id, message, 'item_present');
        },

        /**
         * Sync subscription details.
         *
         * @param e
         * @returns {boolean}
         */
        sync_subscription_details: function (e) {
            give_synchronizer.disable_buttons();

            var data = {
                action: 'give_recurring_sync_subscription_details',
                subscription_id: Give_Sync_Vars.id,
                'give-form-id': Give_Sync_Vars.form_id,
                security: Give_Recurring_Vars.sync_subscription_details_nonce,
            };

            var modal_id = e.modal_id;

            // Reload the modal when closed.
            $(modal_id).on('hidden.bs.modal', function () {
                location.reload();
            });

            $.post(Give_Recurring_Vars.give_recurring_ajax_url, data, function (response) {
                if (!!response.data) {
                    if (!!response.data.notice) {
                        give_synchronizer.print_notice(modal_id, '* ' + response.data.notice, 'subtitle');
                    }

                    // DETAILS
                    give_synchronizer.print_notice(modal_id, Give_Recurring_Vars.sync_subscription_details, 'title');
                    give_synchronizer.show_detail(
                        modal_id,
                        __('Subscription Status', 'give-recurring'),
                        response.data.details['currentStatus'],
                        response.data.details['gatewayStatus']
                    );
                    give_synchronizer.show_detail(
                        modal_id,
                        __('Billing Period', 'give-recurring'),
                        response.data.details['currentPeriod'],
                        response.data.details['gatewayPeriod']
                    );
                    give_synchronizer.show_detail(
                        modal_id,
                        __('Date Created', 'give-recurring'),
                        response.data.details['currentCreatedAt'],
                        response.data.details['gatewayCreatedAt']
                    );

                    // TRANSACTIONS
                    give_synchronizer.print_notice(
                        modal_id,
                        Give_Recurring_Vars.sync_subscription_transactions,
                        'title'
                    );
                    give_synchronizer.print_notice(
                        modal_id,
                        __('Checking subscription payments', 'give-recurring'),
                        'subtitle'
                    );
                    for (var i = 0; i < response.data.missingTransactions.length; i++) {
                        give_synchronizer.show_missing_transaction(modal_id, response.data.missingTransactions[i]);
                    }
                    for (var i = 0; i < response.data.presentTransactions.length; i++) {
                        give_synchronizer.show_present_transaction(modal_id, response.data.presentTransactions[i]);
                    }

                    give_synchronizer.enable_buttons();
                } else {
                    give_synchronizer.show_message(
                        modal_id,
                        '<h3>' + Give_Recurring_Vars.sync_subscription_details + '</h3>'
                    );

                    // Show sync message.
                    give_synchronizer.show_message(modal_id, response.html);

                    // Don't proceed if there was an error.
                    if (response.error) {
                        return false;
                    }

                    var event = jQuery.Event('subscription_details_synced');
                    event.log_id = response.log_id;
                    event.subscription = {};
                    event.subscription.id = data.subscription_id;
                    event.modal_id = modal_id;

                    $('body').trigger(event);
                }
            });

            return false;
        },

        /**
         * Sync subscription transactions.
         *
         * @param e
         * @returns {boolean}
         */
        sync_subscription_transactions: function (e) {
            var data = {
                action: 'give_recurring_sync_subscription_transactions',
                subscription_id: Give_Sync_Vars.id,
                'give-form-id': Give_Sync_Vars.form_id,
                log_id: e.log_id,
                security: Give_Recurring_Vars.sync_subscription_transactions_nonce,
            };

            give_synchronizer.show_message(
                e.modal_id,
                '<h3>' + Give_Recurring_Vars.sync_subscription_transactions + '</h3>'
            );

            // Call Synchronizer via AJAX.
            $.post(Give_Recurring_Vars.give_recurring_ajax_url, data, function (response) {
                give_synchronizer.show_message(e.modal_id, response.html);
                give_synchronizer.enable_buttons();
            });

            return false;
        },

        /**
         * Enable buttons and loading animation.
         */
        enable_buttons: function () {
            $('.give-active-sync-message').fadeOut();
            $('button.give-resync-button').prop('disabled', false);
        },

        /**
         * Enable buttons and loading animation.
         */
        disable_buttons: function () {
            $('.give-active-sync-message').fadeIn();
            $('button.give-resync-button').prop('disabled', true);
        },
    };

    give_synchronizer.init();
});
