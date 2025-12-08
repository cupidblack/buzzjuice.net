<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Textarea
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Textarea extends FieldViewContract {
	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {
		$document = new DOMDocument;

		$textarea = $document->createElement( 'textarea',  esc_attr( $field->getDefaultValue() ) );
		$textarea->setAttribute( 'id', $field->getId() );
		$textarea->setAttribute( 'class', 'give-field ' . $field->getClass() );
		$textarea->setAttribute( 'name', $field->getName() );
		$textarea->setAttribute( 'placeholder', $field->getPlaceholder() );

		if ( $field->isRequired() ) {
			$textarea->setAttribute( 'required', 'required' );
		}

		foreach( $field->getAttributes() as $name => $value ) {
			$textarea->setAttribute( $name, esc_attr( $value ) );
		}

		$document->appendChild( $textarea );

		return $document->saveHTML();
	}
}
