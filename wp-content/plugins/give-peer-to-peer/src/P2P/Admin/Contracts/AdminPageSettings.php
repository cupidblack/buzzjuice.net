<?php

namespace GiveP2P\P2P\Admin\Contracts;

use RuntimeException;
use InvalidArgumentException;
use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\FieldsAPI\Repeater;

/**
 * Interface AdminPageSettings
 * @package GiveP2P\P2P\Admin\Contracts
 *
 * @since 1.0.0
 */
abstract class AdminPageSettings {
	/**
	 * @return FormField[]
	 */
	public function getFields() {
		throw new RuntimeException(
			sprintf( 'This method must be overridden to return an array of %s objects', FormField::class )
		);
	}

	/**
	 * @param  SettingsData  $dataObject
	 *
	 * @return FormField[]
	 */
	public function getFieldsWithData( $dataObject ) {
		if ( ! is_subclass_of( $dataObject, SettingsData::class ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Provided data object must implement the %s interface', SettingsData::class )
			);
		}

		$data = $dataObject->toArray();

		// Bailout
		if ( empty( $data ) ) {
			return $this->getFields();
		}

		$fields = [];

		foreach ( $this->getFields() as $field ) {
			// Field exists in data?
			if ( array_key_exists( $field->getName(), $data ) ) {
				// Repeater field?
				if ( $field->getType() === Repeater::TYPE ) {
					if ( is_array( $data[ $field->getName() ] ) ) {
						$optionData = [];
						foreach ( $data[ $field->getName() ] as $i => $options ) {
							foreach ( $options as $optionName => $optionValue ) {
								$optionData[ $i ][ $optionName ] = $optionValue;
							}
						}
						$field->defaultValue( $optionData );
					}
				} else {
					$field->defaultValue( $data[ $field->getName() ] );
				}
			}

			$fields[] = $field;
		}

		return $fields;
	}

}
