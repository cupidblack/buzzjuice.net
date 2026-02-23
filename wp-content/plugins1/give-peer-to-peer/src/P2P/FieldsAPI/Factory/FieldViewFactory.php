<?php

namespace GiveP2P\P2P\FieldsAPI\Factory;

use DOMDocument;
use GiveP2P\P2P\FieldsAPI\Types;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Exceptions\FieldTemplateNotSupported;


/**
 * Class View
 * @package GiveP2P\P2P\FieldsAPI\Factory
 *
 * @since 1.0.0
 */
class FieldViewFactory {

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	private function getFieldViewClassByType( $type ) {
		$className = in_array( $type, [ Types::TEXT, Types::DATE, Types::HIDDEN, Types::PHONE ] )
			? 'Text'
			: ucfirst( $type );

		return sprintf( 'GiveP2P\\P2P\\FieldsAPI\\Views\\%s', $className );
	}

	/**
	 * @param  Field  $field
	 *
	 * @return DOMDocument
	 * @throws FieldTemplateNotSupported
	 */
	public function make( Field $field ) {
		if ( ! class_exists( $class = $this->getFieldViewClassByType( $field->getType() ) ) ) {
			throw new FieldTemplateNotSupported( $field->getType() );
		}

		return new $class( $field );
	}
}
