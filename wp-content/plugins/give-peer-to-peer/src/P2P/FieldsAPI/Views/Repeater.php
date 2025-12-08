<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Repeater as RepeaterField;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Repeater
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Repeater extends FieldViewContract {

	const PLACEHOLDER = '{PLACEHOLDER}';

	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {
		$document = new DOMDocument;

		/**
		 * @var RepeaterField $field
		 */
		$container = $document->createElement( 'div' );
		$container->setAttribute( 'class', 'give-p2p-repeater-container' );

		$container->appendChild(
			$document->createTextNode( self::PLACEHOLDER )
		);

		// Button container
		$buttonContainer = $document->createElement( 'div' );
		$buttonContainer->setAttribute( 'class', 'give-p2p-repeat-btn-container' );

		// Button
		$button = $document->createElement( 'button', $field->getRepeaterButtonText() );
		$button->setAttribute( 'class', 'button button-primary give-p2p-repeat-block-btn' );
		$button->setAttribute( 'data-block', $field->getId() );

		$buttonContainer->appendChild( $button );
		$container->appendChild( $buttonContainer );


		$document->appendChild( $container );

		$template = $document->saveHTML();

		$values = $field->getDefaultValue();

		if ( is_array( $values ) && ! empty( $values ) ) {
			$content = [];

			foreach ( $values as $value ) {
				$field->defaultValue( $value );
				$content[] = ( new RepeaterBlock( $field ) )->render();
			}

			return str_replace(
				self::PLACEHOLDER,
				implode( PHP_EOL, $content ),
				$template
			);
		}

		return str_replace(
			self::PLACEHOLDER,
			( new RepeaterBlock( $field ) )->render(),
			$template
		);
	}
}
