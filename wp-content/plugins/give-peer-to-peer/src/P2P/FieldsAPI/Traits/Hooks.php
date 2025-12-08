<?php

namespace GiveP2P\P2P\FieldsAPI\Traits;

/**
 * Trait RenderField
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 */
trait Hooks {
	/**
	 * @var null|string|callable
	 */
	protected $renderBeforeCallback = null;

	/**
	 * @var null|string|callable
	 */
	protected $renderAfterCallback = null;

	/**
	 * Add a callback to be called before the Form Field is rendered
	 *
	 * @param string|callable $callback
	 *
	 * @return $this
	 */
	public function renderBefore( $callback ) {
		$this->renderBeforeCallback = $callback;
		return $this;
	}

	/**
	 * @return callable|string|null
	 */
	public function getRenderBeforeCallback() {
		return $this->renderBeforeCallback;
	}

	/**
	 * Add a callback to be called after the Form Field is rendered
	 *
	 * @param string|callable $callback
	 *
	 * @return $this
	 */
	public function renderAfter( $callback ) {
		$this->renderAfterCallback = $callback;
		return $this;
	}

	/**
	 * Execute the renderAfter callback
	 */
	public function getRenderAfterCallback() {
		return $this->renderAfterCallback;
	}
}
