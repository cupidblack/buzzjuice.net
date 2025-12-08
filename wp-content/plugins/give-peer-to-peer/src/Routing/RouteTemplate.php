<?php

use GiveP2P\Routing\Actions\ResolveRoute;
use GiveP2P\Routing\NotFoundException;
use \GiveP2P\Routing\RouteRegister;

/**
 * GiveWP Routing Template
 * Handles routing the request to the correct content.
 *
 * @since 1.0.0
 */

global $wp_query;
try {
	$output = give(ResolveRoute::class)();
} catch ( NotFoundException $e ) {
	if( $notFoundTemplate = get_404_template() ) {
		return include $notFoundTemplate;
	}
	wp_die(
		$e->getMessage(),
		'',
		[
			'response' => 404,
		]
	);
} catch ( \Exception $e ) {
	wp_die(
		$e->getMessage(),
		'',
		[
			'response' => 404,
		]
	);
}

get_header();

echo $output;

get_footer();
