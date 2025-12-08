<?php

namespace GiveP2P\P2P\QueryBuilder\Traits;

trait Limit {

	/**
	 * @var int
	 */
	public $limit;

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit( $limit ) {
		$this->limit = $limit;
		return $this;
	}

	public function getLimitSQL() {
		return $this->limit
			   ? [ "LIMIT {$this->limit}" ]
			   : [];
	}
}
