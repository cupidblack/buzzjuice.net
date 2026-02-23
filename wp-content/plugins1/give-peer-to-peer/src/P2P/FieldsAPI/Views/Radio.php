<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use Give\Framework\FieldsAPI\Radio as GiveRadio;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Radio
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Radio extends FieldViewContract {
	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {
		$document = new DOMDocument;
		/**
		 * @var GiveRadio $field
		 */
		foreach( $field->getOptions() as $option ) {
			// Container
			$container = $document->createElement( 'div' );
			$container->setAttribute( 'class', 'give-p2p-option-row' );

			$radio = $document->createElement( 'input' );
			$radio->setAttribute( 'id', sprintf( '%s-%s', $field->getId(), $option->getValue() ) );
			$radio->setAttribute( 'class', 'give-radio-field ' . $field->getClass() );
			$radio->setAttribute( 'name', $field->getName() );
			$radio->setAttribute( 'type', 'radio' );
			$radio->setAttribute( 'value', esc_attr( $option->getValue() ) );

			if ( $option->getValue() == $field->getDefaultValue() ) {
				$radio->setAttribute( 'checked', 'checked' );
			}

			if ( $field->isRequired() ) {
				$radio->setAttribute( 'required', 'required' );
			}

			foreach( $field->getAttributes() as $name => $value ) {
				$radio->setAttribute( $name, esc_attr( $value ) );
			}

			// Option label
			$label = $document->createElement( 'label', $option->getLabel() );
			$label->setAttribute( 'for', sprintf( '%s-%s', $field->getId(), $option->getValue() ) );

			// Build
			$container->appendChild( $radio );
			$container->appendChild( $label );

			$document->appendChild( $container );
		}

		return $document->saveHTML();
	}
}
