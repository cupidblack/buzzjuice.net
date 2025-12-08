<?php

namespace GiveP2P\P2P\Models\Traits;

use InvalidArgumentException;
use GiveP2P\P2P\FieldsAPI\FormField;

/**
 * Trait Properties
 * @package GiveP2P\P2P\Models\Traits
 *
 * @since 1.0.0
 */
trait Properties {
	/**
	 * Check if class property exist
	 *
	 * @param  string  $name
	 *
	 * @return bool
	 */
	public function has( $name ) {
		return property_exists( __CLASS__, $name );
	}

	/**
	 * Get property
	 *
	 * @param  string  $prop
	 * @param  mixed  $fallback
	 *
	 * @return mixed
	 * @throws InvalidArgumentException if property does not exist
	 *
	 */
	public function get( $prop, $fallback = '' ) {
		if ( ! $this->has( $prop ) ) {
			throw new InvalidArgumentException( "Property {$prop} does not exist" );
		}

		if ( empty( $this->{$prop} ) ) {
			return $fallback;
		}

		return $this->{$prop};
	}

	/**
	 * Set property
	 *
	 * @param  string  $prop
	 * @param  mixed  $value
	 */
	public function set( $prop, $value ) {
		if ( $this->has( $prop ) ) {
			$this->{$prop} = $value;
		}
	}

	/**
	 * @param  array  $data
	 */
	private function setPropertiesFromArray( $data ) {
		foreach ( $data as $property => $value ) {
			$this->set( $property, $value );
		}
	}


	/**
	 * @param  FormField[] $fields
	 */
	private function setPropertiesFromCollection( $fields ) {
		foreach ( $fields as $field ) {
			$this->set( $field->getName(), $field->getDefaultValue() );
		}
	}

	/**
	 * @return array
	 */
	abstract public function toArray();

	/**
	 * @return array
	 */
	public function getUpdatedProperties() {
		return array_filter( $this->toArray(), function ( $value ) {
			return ! is_null( $value );
		} );
	}

	/**
	 * @param string|array $props
	 *
	 * @return array
	 */
	public function getUpdatedPropertiesWithout( $props ) {

		$properties = $this->getUpdatedProperties();

		if ( is_array( $props ) ) {
			foreach( $props as $property ) {
				if ( isset( $properties[ $property ] ) ) {
					unset( $properties[ $property ] );
				}
			}
		} else {
			if ( isset( $properties[ $props ] ) ) {
				unset( $properties[ $props ] );
			}
		}

		return $properties;
	}
}
