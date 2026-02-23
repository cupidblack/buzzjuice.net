<?php

namespace GiveP2P\P2P\FieldsAPI\Contracts;

use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\FormField;

/**
 * Class ViewContract
 * @package GiveP2P\P2P\FieldsAPI\Contracts
 *
 * @since 1.0.0
 */
abstract class FieldViewContract {
	/**
	 * @var Field|FormField
	 */
	private $field;

	/**
	 * Wrapper constructor.
	 *
	 * @param  Field  $field
	 */
	public function __construct( Field $field ) {
		$this->field = $field;
	}

	/**
	 * @param  Field|FormField  $field
	 */
	abstract public function template( Field $field );

	/**
	 * @return string
	 */
	public function render() {
		return $this->template( $this->field );
	}
}
