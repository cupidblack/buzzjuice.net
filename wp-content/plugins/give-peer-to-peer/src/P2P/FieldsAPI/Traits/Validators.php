<?php

namespace GiveP2P\P2P\FieldsAPI\Traits;

use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\FieldsAPI\Repeater;
/**
 * Trait FieldValidator
 * @package GiveP2P\P2P\FieldsAPI\FormField
 *
 * @since 1.0.0
 */
trait Validators {
	/**
	 * @var array
	 */
	protected $validators = [];

	/**
	 * Add field validator
	 * Validator function should return bool value
	 *
	 * @param  callable|string  $validator
	 *
	 * @return $this
	 */
	public function addValidator( $validator ) {
		array_push( $this->validators, $validator );

		return $this;
	}

	/**
	 * @return array
	 */
	public function getValidators() {
		return $this->validators;
	}

	/**
	 * Validate field value
	 *
	 * @return bool
	 */
	public function isValid() {
		foreach ( $this->getValidators() as $validator ) {
			if ( is_callable( $validator ) ) {
				$valid = call_user_func( $validator, $this->getDefaultValue() );

				if ( ! $valid ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate repeater fields
	 *
	 * In case of an error, the $errorCallback will be executed and the FormField instance which didn't pass the validators will be provided as a parameter
	 *
	 * @param  callable  $errorCallback
	 *
	 * @return bool
	 */
	public function validateRepeaterFields( $errorCallback ) {
		/**
		 * @var FormField|Repeater $this
		 * @var FormField $formField
		 */
		$data = $this->getDefaultValue();

		if ( is_array( $data ) && ! empty( $data ) ) {
			// Iterate trough data
			foreach ( $data as $values ) {
				// Check all repeater fields
				foreach ( $this->getOptions() as $option ) {
					$formField = $option->getValue();
					// Set current field value
					if ( is_array( $values ) && isset( $values[ $formField->getName() ] ) ) {
						$formField->defaultValue( $values[ $formField->getName() ] );
					}

					// Check if is required
					if ( $formField->isRequired() ) {
						if ( empty( trim( $formField->getDefaultValue() ) ) ) {
							call_user_func( $errorCallback, $formField );
							return false;
						}
					}

					// Validate field input
					if ( ! $formField->isValid() ) {
						call_user_func( $errorCallback, $formField );
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getValidationError() {
		$fieldName = empty( $this->getLabel() )
			? $this->getName()
			: $this->getLabel();

		return [
			'error_id'      => $this->getName(),
			'error_message' => sprintf( __( 'Please enter a valid value for %s', 'give-peer-to-peer' ), $fieldName ),
		];
	}
}
