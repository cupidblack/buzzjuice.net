<?php

namespace GiveP2P\Routing\Actions;

use GiveP2P\Routing\NotFoundException;
use GiveP2P\Routing\RouteRegister;

/**
 * Resolves the route based on the current query and request, returning the output
 *
 * @since 1.0.0
 */
class ResolveRoute
{
	/**
	 * @var RouteRegister
	 */
	private $routeRegister;

	/**
	 * @since 1.0.0
	 */
	public function __construct(RouteRegister $routeRegister)
	{
		$this->routeRegister = $routeRegister;
	}

	/**
	 * @since 1.0.0
	 *
	 * @return string|void
	 * @throws NotFoundException
	 */
	public function __invoke()
	{
		global $wp_query;

		/*
		 * HTTP method override for clients that can't use PUT/PATCH/DELETE. First, we check
		 * $_GET['_method']. If that is not set, we check for the HTTP_X_HTTP_METHOD_OVERRIDE
		 * header.
		 *
		 * Note: The Customizer SSR uses a POST request, but overrides the method as a GET request.
		 * @link https://github.com/impress-org/give-peer-to-peer/issues/277
		 *
		 * Forked from wp-includes/rest-api/class-wp-rest-server.php
		 */
		if ( isset( $_REQUEST['_method'] ) ) {
			$requestMethod = strtoupper( $_REQUEST['_method'] );
		} elseif ( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ) {
			$requestMethod = strtoupper( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] );
		} else {
			$requestMethod = $_SERVER['REQUEST_METHOD'];
		}

		return $this->routeRegister->resolve($wp_query, $requestMethod);
	}
}
