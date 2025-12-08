<?php

namespace GiveFunds\Factories;

use GiveFunds\Models\Fund;
use InvalidArgumentException;

/**
 * Class FundsFactory
 *
 * @package GiveFunds
 * @since 1.0.0
 */
class Funds {

	/**
	 * @since 1.0.0
	 *
	 * @param int    $id
	 * @param string $title
	 * @param string $description
	 * @param int    $author_id
	 * @param bool   $is_default
	 * @param array  $associated_forms
	 *
	 * @return Fund
	 */
	public function make( $id, $title, $description, $author_id, $is_default = false, $associated_forms = [] ) {
		// Prepare args
		$id         = (int) $id;
		$is_default = (bool) $is_default;
		$author_id  = (int) $author_id;

		/**
		 * Check title
		 */
		if ( empty( $title ) ) {
			throw new InvalidArgumentException( 'Property title is required' );
		}

		if ( strlen( $title ) > 255 ) {
			throw new InvalidArgumentException( 'Property title is to long' );
		}

		/**
		 * Check associated_forms
		 */
		if ( ! is_array( $associated_forms ) ) {
			throw new InvalidArgumentException( 'Property associated_forms is not an array' );
		}

		return new Fund( $id, $title, $description, $is_default, $author_id, $associated_forms );
	}
}
