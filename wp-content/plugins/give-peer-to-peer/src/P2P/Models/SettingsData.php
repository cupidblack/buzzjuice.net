<?php

namespace GiveP2P\P2P\Models;

use ArrayAccess;
use GiveP2P\P2P\Admin\Contracts\SettingsData as SettingsDataInterface;

/**
 * Class SettingsData
 *
 * @package GiveP2P\P2P\Models
 *
 * @since 1.0.0
 */
class SettingsData implements SettingsDataInterface, ArrayAccess {
	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @param  array  $data
	 *
	 * @return static
	 */
	public static function fromRequest( $data ) {
		$object = new static();

		if ( is_array( $data ) ) {
			$object->setData( stripslashes_deep( $data ) );
		}

		return $object;
	}

	/**
	 * Set data
	 *
	 * Reformat $_POST or $_GET data
	 *
	 * @param  array $data
	 */
	private function setData( $data ) {
		$collection = [];

		foreach ( $data as $optionName => $optionValue ) {
			// Handle array fields
			if ( is_array( $optionValue ) ) {
				$options = [];
				foreach ( $optionValue as $name => $value ) {
					if ( is_array( $value ) ) {
						foreach ( $value as $i => $v ) {
							$options[ $i ][ $name ] = $v;
						}
					} else {
						$options[ $name ] = $value;
					}
				}
				$collection[ $optionName ] = $options;
			} else {
				$collection[ $optionName ] = $optionValue;
			}
		}

		$this->data = $collection;
	}

	/**
	 * @inheritDoc
	 */
	public function toArray() {
		return $this->data;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet( $offset ) {
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ) {
		if ( $this->offsetExists( $offset ) ) {
			unset( $this->data[ $offset ] );
		}
	}
}
