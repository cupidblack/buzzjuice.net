<?php

namespace GiveP2P\P2P;

use DOMDocument;
use GiveP2P\Routing\ViewContract;

class View implements ViewContract {

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @param $id
	 * @param $attributes
	 */
	public function __construct( $id, $attributes ) {
		$this->id         = $id;
		$this->attributes = $attributes;
	}

	/**
	 * @return string
	 */
	public function render() {

		$document = new DOMDocument;

		$container = $document->createElement( 'div' );
		$container->setAttribute( 'id', $this->id );
		foreach ( $this->attributes as $attribute => $props ) {
			if ( ! is_string( $props ) ) {
				$props = json_encode( $props );
			}
			$container->setAttribute( "data-{$attribute}", $props );
		}

		$document->appendChild( $container );

		return $document->saveHTML();
	}
}
