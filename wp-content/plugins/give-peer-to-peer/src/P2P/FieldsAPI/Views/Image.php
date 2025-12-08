<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Image
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Image extends FieldViewContract {
	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {
		$document = new DOMDocument;
		// Container
		$container = $document->createElement( 'div' );
		$container->setAttribute( 'class', 'give-p2p-image-upload-field-container' );

		// Input container
		$inputContainer = $document->createElement( 'div' );
		$inputContainer->setAttribute( 'class', 'give-p2p-image-upload-field' );

		// Image input
		$imageInput = $document->createElement( 'input' );
		$imageInput->setAttribute( 'id', $field->getId() );
		$imageInput->setAttribute( 'class', 'give-image-field ' . $field->getClass() );
		$imageInput->setAttribute( 'name', $field->getName() );
		$imageInput->setAttribute( 'type', 'text' );
		$imageInput->setAttribute( 'placeholder', $field->getPlaceholder() );
		$imageInput->setAttribute( 'value', esc_attr( $field->getDefaultValue() ) );

		// Is required
		if ( $field->isRequired() ) {
			$imageInput->setAttribute( 'required', 'required' );
		}

		// Additional attributes
		foreach( $field->getAttributes() as $name => $value ) {
			$imageInput->setAttribute( $name, esc_attr( $value ) );
		}

		// Image Add/UpLoad button
		$addButton = $document->createElement( 'input' );
		$addButton->setAttribute( 'type', 'button' );
		$addButton->setAttribute( 'class', 'button give-p2p-button give-p2p-image-upload-btn' );
		$addButton->setAttribute( 'value', esc_html__( 'Add or Upload File', 'give-peer-to-peer' ) );

		// Image preview container
		$imagePreviewContainer = $document->createElement( 'div' );
		$imagePreviewContainer->setAttribute( 'class', 'give-p2p-image-preview-container' );

		// Preview image
		if ( filter_var( $field->getDefaultValue(), FILTER_VALIDATE_URL ) ) {
			$previewImage = $document->createElement( 'img' );
			$previewImage->setAttribute( 'src', $field->getDefaultValue() );

			$imagePreviewContainer->appendChild( $previewImage );
		}

		// Build
		$inputContainer->appendChild( $imageInput );
		$inputContainer->appendChild( $addButton );

		$container->appendChild( $inputContainer );
		$container->appendChild( $imagePreviewContainer );

		$document->appendChild( $container );

		return $document->saveHTML();
	}
}
