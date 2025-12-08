<?php

namespace GiveP2P\Addon\Helpers;

/**
 * Helper class used to handle redirections
 *
 * @package GiveP2P\Addon\Helpers
 * @since 1.0.0
 */
class Redirect {

	/**
	 * Redirect to
	 *
	 * @param string $location
	 */
	public static function to( $location ) {
		if ( ! headers_sent() ) {
			return wp_safe_redirect( $location );
		}

		$location = wp_sanitize_redirect( $location );

		printf( '<script>window.location="%s";</script>', $location );
		exit;
	}
}
