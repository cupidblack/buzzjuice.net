<?php

namespace GiveP2P\P2P\FieldsAPI\Traits;

use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\FieldsAPI\Types;

/**
 * Trait Repeater
 * @package GiveP2P\P2P\FieldsAPI\Traits
 *
 * @since 1.0.0
 */
trait Repeater {

	/**
	 * @var FormField
	 */
	protected $parent;

	/**
	 * @var string
	 */
	protected $repeaterBlockTitle;

	/**
	 * @var string
	 */
	protected $repeaterButtonText;

	public function isRepeaterField() {
		return $this->getType() === Types::REPEATER;
	}


	/**
	 * Set field parent
	 *
	 * @param  FormField  $parent
	 *
	 * @return $this
	 */
	public function parent( FormField $parent ) {
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return FormField
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param  string  $title
	 *
	 * @return $this
	 */
	public function repeaterBlockTitle( $title ) {
		$this->repeaterBlockTitle = $title;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getRepeaterBlockTitle() {
		return $this->repeaterBlockTitle;
	}

	/**
	 * @param  string  $text
	 *
	 * @return $this
	 */
	public function repeaterButtonText( $text ) {
		$this->repeaterButtonText = $text;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRepeaterButtonText() {
		return $this->repeaterButtonText;
	}

	/**
	 * Override the getName method
     *
     * @since 1.3.4 update return signature to match overrode method
	 */
	public function getName(): string {
		if ( $parent = $this->getParent() ) {
			return sprintf( '%s[%s][]', $parent->getName(), $this->name );
		}

		return $this->name;
	}

	/**
	 * Repeater field options have name combined with the parent field name,
	 * so we need a way to get the clean name.
	 *
	 * @return string
	 */
	public function getCleanName() {
		return $this->name;
	}
}
