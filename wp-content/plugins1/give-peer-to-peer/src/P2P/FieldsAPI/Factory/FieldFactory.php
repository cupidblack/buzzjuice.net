<?php

namespace GiveP2P\P2P\FieldsAPI\Factory;

use Give\Framework\FieldsAPI\Contracts\Node;
use Give\Framework\FieldsAPI\Exceptions\TypeNotSupported;

/**
 * Class FieldFactory
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 */
class FieldFactory {

	/**
	 * @return Node
	 * @throws TypeNotSupported
	 */
	public function __call( $type, $args ) {
		return $this->make( $type, array_shift( $args ) );
	}

	/**
	 * @param  string  $type
	 * @param  string  $name
	 *
	 * @return Node
	 * @throws TypeNotSupported
	 */
	public function make( $type, $name ) {
		if (  class_exists( $className = 'GiveP2P\\P2P\\FieldsAPI\\' . ucfirst( $type ) ) ) {
			return new $className( $name );
		}

		throw new TypeNotSupported( $type );
	}
}
