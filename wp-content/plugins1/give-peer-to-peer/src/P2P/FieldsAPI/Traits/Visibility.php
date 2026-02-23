<?php

namespace GiveP2P\P2P\FieldsAPI\Traits;

/**
 * Trait Visibility
 * @package GiveP2P\P2P\FieldsAPI\Traits
 *
 * @since 1.6.3 update be compatible with new visibility system in GiveWP
 * @since 1.0.0
 */
trait Visibility {
	/**
	 * @var bool
	 */
	protected $isVisibilityHandler = false;

	/**
	 * Set field visibility handler option
     *
     * @since 1.6.3 add types
     * @since 1.0.0
	 */
	public function visibilityHandler( bool $bool ) {
		$this->isVisibilityHandler = $bool;
	}

	/**
	 * Check if field is visibility handler
     *
     * @since 1.6.3 add types
     * @since 1.0.0
	 */
	public function isVisibilityHandler(): bool
    {
		return $this->isVisibilityHandler;
	}
}
