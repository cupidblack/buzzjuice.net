<?php

namespace GiveP2P\P2P\FieldsAPI\Exceptions;

use Give\Framework\Exceptions\Primitives\Exception;

/**
 * Class ViewNotSupported
 * @package GiveP2P\P2P\FieldsAPI\Exceptions
 *
 * @since 1.0.0
 */
class FieldTemplateNotSupported extends Exception {
	public function __construct( $type, $code = 0, $previous = null ) {
		parent::__construct( "P2P View $type is not supported", $code, $previous );
	}
}
