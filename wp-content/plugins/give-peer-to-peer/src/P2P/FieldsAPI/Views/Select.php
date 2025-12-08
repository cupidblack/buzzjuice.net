<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use Give\Framework\FieldsAPI\Select as GiveSelect;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Select
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Select extends FieldViewContract {
	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {

		$document = new DOMDocument;
		/**
		 * @var GiveSelect $field
		 */
		$select = $document->createElement( 'select' );
		$select->setAttribute( 'id', $field->getId() );
		$select->setAttribute( 'class', 'give-field ' . $field->getClass() );
		$select->setAttribute( 'name', $field->getName() );

		if ( $field->isRequired() ) {
			$select->setAttribute( 'required', 'required' );
		}

		foreach( $field->getAttributes() as $name => $value ) {
			$select->setAttribute( $name, esc_attr( $value ) );
		}

		// Options
		foreach( $field->getOptions() as $option ) {
			$optionElement = $document->createElement('option', htmlentities($option->getLabel()));
            $optionElement->setAttribute('value', $option->getValue());
			if ( $option->getValue() == $field->getDefaultValue() ) {
				$optionElement->setAttribute( 'selected', 'selected' );
			}
			$select->appendChild( $optionElement );
		}

		$document->appendChild( $select );

		return $document->saveHTML();
	}
}
