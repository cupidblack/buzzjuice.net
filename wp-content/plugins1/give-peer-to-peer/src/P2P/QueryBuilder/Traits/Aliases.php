<?php

namespace GiveP2P\P2P\QueryBuilder\Traits;

trait Aliases {

	/**
	 * @var array
	 */
	protected $aliases = [];

	public function tables( $aliases = [] ) {
		$this->aliases = $aliases;
	}

	public function alias( $table ) {
		if( isset( $this->aliases[ $table ] ) ) {
			return $this->aliases[ $table ];
		}
		return $table;
	}
}
