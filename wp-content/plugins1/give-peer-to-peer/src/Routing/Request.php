<?php

namespace GiveP2P\Routing;

class Request {

	protected $data = [];

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function input( $name, $filter = FILTER_SANITIZE_STRING ) {
		return filter_var( $this->data[ $name ], $filter );
	}
}
