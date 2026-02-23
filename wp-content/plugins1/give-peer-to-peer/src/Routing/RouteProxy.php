<?php

namespace GiveP2P\Routing;

/**
 * Connects the registered routes with the WordPress rewrite API.
 *
 * @since 1.0.0
 */
class RouteProxy {

	/**
	 * @var RouteRegister
	 * @since 1.0.0
	 */
	protected $register;

	/**
	 * @since 1.0.0
	 * @param RouteRegister $register
	 */
	public function __construct( RouteRegister $register ) {
		$this->register = $register;
	}

	/**
	 * Merge the route variables with Rewrite API query variables.
	 *  Registering variables is required in order to be processed by `$wp_query`.
	 * @since 1.0.0
	 * @param $vars
	 * @return array
	 */
	public function registerVariables( $vars ) {
		$vars[] = 'give_route'; // Using this later on to check if the request should be routed.
		return array_merge( $vars, $this->register->reduceVariables() );
	}

	/**
	 * Adds a rewrite tag for each custom route variable
	 * @since 1.0.0
	 */
	public function registerRewriteTags() {
		$this->register->walkVariables(
			function( $variable ) {
				add_rewrite_tag( "%$variable%", '([^&]*)' );
			}
		);
	}

	/**
	 * Add rewrite rules, connecting a route pattern to a query.
	 * @since 1.0.0
	 */
	public function registerRewriteRules() {
		$this->register->walkRoutes(
			function( $route ) {
				$query = array_reduce(
					$route->variables,
					[ $this, 'reduceQueryMatches' ],
					[]
				);

				add_rewrite_rule(
					"^{$route->pattern}/?$", // example: `^campaigns/([^/]*)/?`
					add_query_arg( $query, "index.php?give_route=$route->path" ), // example: `index.php?campaign=$matches[1]`
					'top'
				);
			}
		);
	}

	/**
	 * Convert route variables to query variables to be matched from the request URL.
	 * @since 1.0.0
	 * @param $query
	 * @param $variable
	 * @return $query
	 */
	public function reduceQueryMatches( $query, $variable ) {
		$key                = count( $query ) + 1;
		$query[ $variable ] = "\$matches[$key]";
		return $query;
	}

	/**
	 * If a request is routable (as marked by `give_route`)
	 *  then include a custom template where we can resolve a controller.
	 * @since 1.0.0
	 * @param $template
	 * @return mixed|string
	 */
	public function includeTemplate( $template ) {
		global $wp_query;

		if ( isset( $wp_query->query['give_route'] ) ) {
			return plugin_dir_path( __FILE__ ) . 'RouteTemplate.php';
		}

		return $template;
	}
}
