<?php

namespace GiveP2P\P2P\FieldsAPI\Traits;

/**
 * Trait Attributes
 * @package GiveP2P\P2P\FieldsAPI\Traits
 *
 * @since 1.0.0
 */
trait Attributes {

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setAttribute( $name, $value ) {
		array_push( $this->attributes, [ $name => $value ] );
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		$attributes = [];
		// Skip class and id attribute
		foreach ( $this->attributes as $key => $value ) {
			if ( in_array( $key, [ 'class', 'id' ] ) ) {
				continue;
			}
			$attributes[ $key ] = $value;
		}

		return $attributes;
	}

	/**
	 * Get field class attribute
	 *
	 * @return string
	 */
	public function getClass() {
		$classes = [];

		foreach ( $this->attributes as $key => $value ) {
			if ( $key === 'class' ) {
				$classes[] = $value;
			}
		}

		if ( $this->isVisibilityHandler() ) {
			$classes[] = 'give-p2p-visibility-handler';
		}

		if ( $this->isRequired() ) {
			$classes[] = 'required';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Get field ID attribute
	 *
	 * @return string
	 */
	public function getId() {
		return sprintf( 'give-p2p-%s', $this->getName() );
	}

	/**
	 * Set field default value
	 *
	 * @param mixed $defaultValue
	 * @return $this
	 */
	public function defaultValue( $defaultValue ) {
		$this->defaultValue = $defaultValue;
		return $this;
	}
}
