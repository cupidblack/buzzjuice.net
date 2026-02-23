<?php

namespace GiveP2P\P2P\QueryBuilder\Traits;

use GiveP2P\P2P\QueryBuilder\QueryBuilder;

/**
 * @since 1.3.0 Overloaded where() method to support logical groupings in where clauses.
 */
trait Where {

	/**
	 * @var string
	 */
	public $wheres = [];

	/**
	 * @param string $column
	 * @param string $comparator
	 * @param string $value
	 * @param string $logicalOperator
	 *
	 * @return $this
	 */
	private function setWhere( $column, $comparator, $value, $logicalOperator ) {
		$this->wheres[] = [ $column, $comparator, $value, $logicalOperator ];
		return $this;
	}

	/**
	 * @param string $column
	 * @param string $comparator
	 * @param string $value
	 * @return $this
	 */
	public function where( ...$args ) {
        if( 1 === func_num_args() ) {
            $this->wheres[]= func_get_arg(0);
            return $this;
        }

        list( $column, $comparator, $value ) = $args;
		return $this->setWhere( $column, $comparator, $value, 'AND' );
	}

	/**
	 * @param string $column
	 * @param string $comparator
	 * @param string $value
	 *
	 * @return $this
	 */
	public function orWhere( $column, $comparator, $value ) {
		return $this->setWhere( $column, $comparator, $value, 'OR' );
	}

	public function getWhereSQL() {
		$sql = array_map(function( $where ) {
            if( is_callable( $where ) ) {
                $builder = $where( $this->cloneWithTableAliases() );
                return str_replace( 'WHERE 1 AND', '',"AND ( {$builder->getSQL()} )");
            }
			list( $tableColumn, $comparator, $value, $operator ) = $where;
			list( $table, $column ) = explode('.', $tableColumn );
			return "{$operator} {$this->alias($table)}.$column $comparator '$value'";
		}, $this->wheres);
		return array_merge([ 'WHERE 1' ], $sql );
	}
}
