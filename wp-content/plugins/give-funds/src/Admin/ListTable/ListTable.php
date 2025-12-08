<?php

namespace GiveFunds\Admin\ListTable;

use Give\Donations\ListTable\DonationsListTable;
use GiveFunds\Admin\ListTable\Columns\FundColumn;

/**
 * @since 1.2.0
 */
class ListTable
{

    /**
     * @since 1.2.0
     *
     * @param DonationsListTable $listTable
     *
     * @return DonationsListTable
     */
    public static function registerDonationsListTableColumns(DonationsListTable $listTable): DonationsListTable
    {
        $listTable
            ->addColumnBefore('status', new FundColumn())
            ->setColumnVisibility(FundColumn::getId(), true);

        return $listTable;
    }
}
