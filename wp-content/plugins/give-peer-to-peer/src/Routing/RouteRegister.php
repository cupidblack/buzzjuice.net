<?php

namespace GiveP2P\Routing;

/**
 * Centralized storage for registered routes.
 * @since 1.0.0
 */
class RouteRegister {

	/**
	 * @var array
	 * @since 1.0.0
	 */
	public $routes = [];

	/**
	 * @since 1.0.0
	 * @param Route $route
	 */
	public function register( Route $route ) {
		$this->routes[] = $route;
	}

	/**
	 * For a given query, determine which route callback to resolve.
	 * @param $wp_query
	 */
	public function resolve( $wp_query, $requestType ) {
		foreach ( $this->routes as $route ) {
			if ( $route->canHandleRequest( $wp_query, $requestType ) ) {
				return $this->resolveRoute( $route, $wp_query );
			}
		}
		throw new NotFoundException( 'Route not found' );
	}

	protected function resolveRoute( $route, $wp_query ) {
		$vars = array_filter(
			$wp_query->query,
			function( $key ) use ( $route ) {
				return in_array( $key, $route->variables, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		array_push( $vars, new Request( $_REQUEST ) );

		if ( is_array( $route->callback ) ) {
			$route->callback[0] = give( $route->callback[0] );
		}

        /**
         * @since 1.2.0 Pass only the values of the $vars array to avoid PHP8 expecting named parameters.
         */
		$return = call_user_func_array( $route->callback, array_values( $vars ) );

		if ( $return instanceof ViewContract ) {
			return $return->render();
		} elseif ( is_string( $return ) ) {
			return $return;
		}
	}

	/**
	 * Reduce the collective route variables into a flat array.
	 * @return mixed
	 */
	public function reduceVariables() {
		return array_reduce(
			$this->routes,
			function( $variables, $route ) {
				return array_merge( $variables, $route->variables );
			},
			[]
		);
	}

	/**
	 * Execute a given callback for each variable of each route.
	 * @param callable $callback
	 */
	public function walkVariables( callable $callback ) {
		$variables = $this->reduceVariables();
		array_walk( $variables, $callback );
	}

	/**
	 * Execute a given callback for each route.
	 * @param callable $callback
	 */
	public function walkRoutes( callable $callback ) {
		array_walk( $this->routes, $callback );
	}
}
