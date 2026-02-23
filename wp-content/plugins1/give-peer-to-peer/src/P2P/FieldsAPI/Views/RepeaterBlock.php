<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\FieldsAPI\Repeater as RepeaterField;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class RepeaterBlock
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class RepeaterBlock extends FieldViewContract {

	const PLACEHOLDER = '{PLACEHOLDER}';

	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {
		$document = new DOMDocument;
		/**
		 * @var RepeaterField $field
		 */

		// Repeater block container
		$repeaterBlock = $document->createElement( 'div' );
		$repeaterBlock->setAttribute( 'class', 'give-p2p-repeater-block' );

		// Repeater block title
		$repeaterBlockTitle = $document->createElement( 'div', $field->getRepeaterBlockTitle() );
		$repeaterBlockTitle->setAttribute( 'class', 'give-p2p-repeater-block-title' );

		// Remove icon container
		$removeIconContainer = $document->createElement( 'div' );
		$removeIconContainer->setAttribute( 'class', 'give-p2p-repeater-block-remove-icon' );

		// Icon
		$removeIcon = $document->createElement( 'span' );
		$removeIcon->setAttribute( 'class', 'dashicons dashicons-dismiss' );

		$removeIconContainer->appendChild( $removeIcon );
		$repeaterBlockTitle->appendChild( $removeIconContainer );

		$repeaterBlock->appendChild( $repeaterBlockTitle );

		$repeaterBlock->appendChild(
			$document->createTextNode( self::PLACEHOLDER )
		);

		$document->appendChild( $repeaterBlock );

		$content = [];

		// Options
		foreach ( $field->getOptions() as $option ) {
			/**
			 * @var FormField $formField
			 */
			$formField = $option->getValue();

			$formField->parent( $field );
			// Fill the values
			if ( is_array( $values = $field->getDefaultValue() ) ) {
				if ( isset( $values[ $formField->getCleanName() ] ) ) {
					$formField->defaultValue( $values[ $formField->getCleanName() ] );
				}
			}

			$content[] = ( new Wrapper( $formField ) )->render();
		}

		return str_replace(
			self::PLACEHOLDER,
			implode( PHP_EOL, $content ),
			$document->saveHTML()
		);

	}
}
