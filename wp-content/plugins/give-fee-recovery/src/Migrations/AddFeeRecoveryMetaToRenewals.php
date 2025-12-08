<?php

namespace GiveFeeRecovery\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;
use Give\Framework\QueryBuilder\QueryBuilder;
use Give_Updates;

/**
 * This migration updates all renewals that have fee recovery enabled with missing fee recovery meta values
 *
 * @since 1.9.8
 */
class AddFeeRecoveryMetaToRenewals extends Migration
{
    public static function id(): string
    {
        return 'add_fee_recovery_meta_to_renewals';
    }

    /**
     * @return string
     */
    public static function title(): string
    {
        return esc_html__('Update missing fee recovery meta values for renewals', 'give');
    }

    /**
     * Register background update.
     *
     * @param Give_Updates $give_updates
     *
     */
    public function register(Give_Updates $give_updates)
    {
        $give_updates->register(
            [
                'id' => self::id(),
                'version' => '1.9.8',
                'callback' => [$this, 'run'],
            ]
        );

        // Auto complete migration on website which do not have renewals.
        if (
            !$this->queryRenewals()->count()
            && !give_has_upgrade_completed(self::id())
        ) {
            give_set_upgrade_complete(self::id());
        }
    }

    /**
     * @throws DatabaseMigrationException
     */
    public function run()
    {
        $giveUpdates = Give_Updates::get_instance();

        $renewals = $this->queryRenewals()
            ->select(
                ['ID', 'donationId'],
                ['post_status', 'status'],
                ['post_parent', 'donationParentId']
            )
            ->attachMeta(
                'give_donationmeta',
                'id',
                'donation_id',
                ['_give_payment_form_id', 'formId'],
                ['_give_payment_total', 'total'],
                ['_give_fee_donation_amount', 'feeDonationAmount'],
                ['_give_fee_amount', 'feeAmount']
            )
            ->limit(25)
            ->offset(($giveUpdates->step - 1) * 25)
            ->getAll();

        $renewalsCount = count($renewals);

        if ($renewalsCount === 0) {
            give_set_upgrade_complete(self::id());
            return;
        }

        try {
            $giveUpdates->set_percentage($renewalsCount, $giveUpdates->step * 25);

            foreach ($renewals as $donation) {
                if (
                    is_null($donation->feeDonationAmount)
                    && $this->isFeeRecoveryEnabledOnForm($donation->formId)
                ) {
                    $this->insertDataForRenewalDonation(
                        $donation->donationId,
                        $donation->donationParentId,
                        $donation->total
                    );
                }
            }
        } catch (DatabaseMigrationException $exception) {
            $giveUpdates->__pause_db_update(true);
            update_option('give_upgrade_error', 1, false);

            throw new DatabaseMigrationException(
                'An error occurred updating missing fee recovery meta to renewals',
                0,
                $exception
            );
        }
    }

    /**
     * @since 1.9.8
     * @inerhitDoc
     */
    public static function timestamp()
    {
        return strtotime('2022-04-27');
    }

    /**
     * @since 1.9.8
     *
     * @param $payment_id
     * @param $parent_id
     * @param $donation_amount
     *
     * @return void
     */
    public function insertDataForRenewalDonation($payment_id, $parent_id, $donation_amount)
    {
        $fee_amount = give_get_meta($parent_id, '_give_fee_amount', true);
        $fee_amount = !empty($fee_amount) ? $fee_amount : 0;

        $fee_status = give_get_meta($parent_id, '_give_fee_status', true);
        $fee_status = !empty($fee_status) ? $fee_status : 'disabled';

        // Store Donation amount for recurring payment.
        give_update_payment_meta(
            $payment_id,
            '_give_fee_donation_amount',
            give_sanitize_amount_for_db($donation_amount - $fee_amount)
        );

        // Store total fee amount for recurring payment.
        give_update_payment_meta($payment_id, '_give_fee_amount', $fee_amount);

        // Update Give Fee Status for recurring payment.
        give_update_payment_meta($payment_id, '_give_fee_status', $fee_status);
    }

    /**
     * @since 1.9.8
     *
     * @param $formId
     *
     * @return bool
     */
    public function isFeeRecoveryEnabledOnForm($formId): bool
    {
        $is_fee_recovery = give_get_meta($formId, '_form_give_fee_recovery', true);
        $is_fee_recovery = !empty($is_fee_recovery) ? $is_fee_recovery : 'global';

        return give_is_setting_enabled($is_fee_recovery, 'global') && give_is_setting_enabled(
                give_get_option('give_fee_recovery')
            );
    }

    /**
     * @since 1.9.8
     * @return QueryBuilder
     */
    private function queryRenewals(): QueryBuilder
    {
        return DB::table('posts')
            ->where('post_type', 'give_payment')
            ->where('post_status', 'give_subscription');
    }
}
