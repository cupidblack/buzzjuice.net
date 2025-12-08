<?php

namespace GiveP2P\P2P\QueryBuilder;

use GiveP2P\P2P\QueryBuilder\Traits\Aliases;
use GiveP2P\P2P\QueryBuilder\Traits\From;
use GiveP2P\P2P\QueryBuilder\Traits\GroupBy;
use GiveP2P\P2P\QueryBuilder\Traits\Join;
use GiveP2P\P2P\QueryBuilder\Traits\Limit;
use GiveP2P\P2P\QueryBuilder\Traits\OrderBy;
use GiveP2P\P2P\QueryBuilder\Traits\Select;
use GiveP2P\P2P\QueryBuilder\Traits\Where;

class QueryBuilder {

	use Aliases;
	use Select;
	use From;
	use Join;
	use Where;
	use OrderBy;
	use GroupBy;
	use Limit;

    /**
     * Clones the query builder while maintaining the
     * table aliases mapping for child queries.
     *
     * @since 1.3.0
     *
     * @return static
     */
    public function cloneWithTableAliases() {
        $static = new static();
        $static->tables( $this->aliases );
        return $static;
    }

	/**
	 * @return string
	 */
	public function getSQL() {

		$sql = array_merge(
			$this->getSelectSQL(),
			$this->getFromSQL(),
			$this->getJoinSQL(),
			$this->getWhereSQL(),
			$this->getGroupBySQL(),
			$this->getOrderBySQL(),
			$this->getLimitSQL()
		);

		return implode(' ', $sql);
	}
}
