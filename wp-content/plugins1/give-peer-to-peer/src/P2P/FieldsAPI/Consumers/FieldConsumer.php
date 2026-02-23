<?php

namespace GiveP2P\P2P\FieldsAPI\Consumers;

use Give\Vendors\StellarWP\FieldConditions\Contracts\Condition;
use GiveP2P\P2P\FieldsAPI\Virtual;
use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\FieldsAPI\Views\Wrapper;

/**
 * Class FieldsConsumer
 * @package GiveP2P\P2P\FieldsAPI\Factory
 *
 * @since 1.0.0
 */
class FieldConsumer {

	/**
	 * @var FormField[]
	 */
	private $fields;

	/**
	 * Visibility handlers
	 *
	 * @var Condition[]
	 */
	private $handlers = [];

	/**
	 * FieldsConsumer constructor.
	 *
	 * @param  FormField[]  $fields
	 */
	private function __construct( array $fields ) {
		$this->fields = $fields;
	}

	/**
	 * @param $fields FormField[]
	 */
	public static function make( array $fields ): self {
		$consumer = new static( $fields );
		$consumer->setVisibilityHandlers();

		return $consumer;
	}


	/**
	 * Set visibility handlers
     *
     * @since 1.6.3 update to new conditions system
     * @since 1.0.0
	 */
	private function setVisibilityHandlers() {
		foreach ( $this->fields as $field ) {
			if ( $field->hasVisibilityConditions() ) {
                $this->handlers = array_map(static function(Condition $condition) {
                    return $condition->jsonSerialize()['field'];
                },$field->getVisibilityConditions());
			}
		}
	}

	/**
	 * Render fields
     *
     * @since 1.6.3 update to new conditions system
     * @since 1.0.0
	 */
	public function render() {
		foreach ( $this->fields as $field ) {
			if ( in_array( $field->getName(), $this->handlers ) ) {
				$field->visibilityHandler( true );
			}

			if ( $field->getType() != Virtual::TYPE ) {
				echo ( new Wrapper( $field ) )->render();
			}
		}
	}

}
