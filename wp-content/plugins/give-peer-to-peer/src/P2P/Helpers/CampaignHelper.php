<?php

namespace GiveP2P\P2P\Helpers;

use GiveP2P\Addon\Helpers\Notices;
use GiveP2P\P2P\FieldsAPI\FormField;

/**
 * Class CampaignHelper
 * @package GiveP2P\P2P\Helpers
 *
 * @since 1.0.0
 */
class CampaignHelper {

	/**
	 * @param  FormField[]  $fields
	 * @return bool
	 */
	public function validateFields( $fields ) {
		foreach ( $fields as $field ) {
			// Check if is required
			if ( $field->isRequired() ) {
				if ( empty( trim( $field->getDefaultValue() ) ) ) {
					Notices::add( 'error', $field->getRequiredError()['error_message'] );
					return false;
				}
			}

			// Validate field input
			if ( ! $field->isValid() ) {
				Notices::add( 'error', $field->getValidationError()['error_message'] );
				return false;
			}

			// Validate repeater options
			if ( $field->isRepeaterField() ) {
				// Check repeater field options
				$fieldsValid = $field->validateRepeaterFields(
					function( FormField $formField ) {
						Notices::add( 'error', $formField->getValidationError()['error_message'] );
					}
				);

				if ( ! $fieldsValid ) {
					return false;
				}
			}
		}

		return true;
	}
}
