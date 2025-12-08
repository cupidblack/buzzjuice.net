<?php

namespace GiveP2P\Routing;

/**
 * Represents the relationship between a pattern and its variables.
 *
 * @since 1.0.0
 */
class Route {

	public $path;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	public $pattern;

	/**
	 * @var string[]
	 * @since 1.0.0
	 */
	public $variables;

	/**
	 * @var callable
	 * @since 1.0.0
	 */
	public $callback;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	public $requestType;

	/**
	 * @since 1.0.0
	 * @param string $path
	 * @param callable $callback
	 * @param string $requestType
	 */
	public function __construct( $path, $callback, $requestType = 'GET' ) {
		$this->path        = $path;
		$this->callback    = $callback;
		$this->requestType = $requestType;
		$this->pattern     = $this->parsePattern( $path );
		$this->variables   = $this->prefixVariables(
			$this->parseVariables( $path )
		);
	}

	/**
	 * A static factory method for registering a GET request route.
	 * @since 1.0.0
	 * @param string $pattern
	 * @param callable $callback
	 */
	public static function get( $pattern, $callback ) {
		give( RouteRegister::class )->register(
			new static( $pattern, $callback, 'GET' )
		);
	}
	public static function post( $pattern, $callback ) {
		give( RouteRegister::class )->register(
			new static( $pattern, $callback, 'POST' )
		);
	}

	/**
	 * Determine if the route can handle the given request based on the $wp_query object.
	 * @since 1.0.0
	 * @param $wp_query
	 * @return bool
	 */
	public function canHandleRequest( $wp_query, $requestType ) {
		return $requestType === $this->requestType
			&& $wp_query->query['give_route'] === $this->path;
	}

	/**
	 * Converts route parameters to regex patterns for the Rewrite API.
	 *  Example: `campaigns/{campaign}` becomes `campaigns/([\-\w+]*)`.
	 * @since 1.0.0
	 * @param string $pattern
	 * @return string
	 */
	protected function parsePattern( $pattern ) {
		return preg_replace( '/{\w*}/', '([\-\w+]*)', $pattern );
	}

	/**
	 * Extracts route parameters into an array (sans wrapping curly braces).
	 * @since 1.0.0
	 * @param string $pattern
	 * @return string[]
	 */
	protected function parseVariables( $pattern ) {
		$matches = [];
		preg_match_all( '/{\w*}/', $pattern, $matches );
		return array_map(
			function( $match ) {
				return str_replace( [ '{', '}' ], '', $match );
			},
			$matches[0]
		);
	}

	/**
	 * Adds a `give_*` prefix query variables to avoid collision.
	 * @since 1.0.0
	 * @param $variables
	 * @return string[]
	 */
	protected function prefixVariables( $variables ) {
		return array_map(
			function( $variable ) {
				return "give_$variable";
			},
			$variables
		);
	}
}
