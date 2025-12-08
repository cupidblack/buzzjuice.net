<?php

namespace GiveFunds\Admin\ListTable\Columns;

use Give\Donations\Models\Donation;
use Give\Framework\ListTable\ModelColumn;
use GiveFunds\Repositories\Revenue as RevenueRepository;
use GiveFunds\Repositories\Funds as FundsRepository;

/**
 * @since 1.2.0
 *
 * @extends ModelColumn<Donation>
 */
class FundColumn extends ModelColumn
{

    /**
     * @since 1.2.0
     *
     * @inheritDoc
     */
    public static function getId(): string
    {
        return 'fund';
    }

    /**
     * @since 1.2.0
     *
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return __('Fund', 'give-funds');
    }

    /**
     * @since 1.2.0
     *
     * @inheritDoc
     *
     * @param Donation $model
     */
    public function getCellValue($model): string
    {
        $fundId = give(RevenueRepository::class)->getDonationFundId( $model->id );
        $fund   = give(FundsRepository::class)->getFund( $fundId );

        if ( ! $fundId || ! $fund ) {
            return __( 'Unassigned', 'give-funds' );
        }

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            admin_url( "edit.php?post_type=give_forms&page=give-fund-overview&id={$fundId}" ),
            $fund->getTitle()
        );
    }
}
