<?php
/**
 * Filter the snippets based on the device type metabox selection.
 * This supersedes the conditional logic rules.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wpcode_get_snippets_for_location', 'wpcode_maybe_exclude_snippet_by_device_type', 10 );

/**
 * @param WPCode_Snippet[] $snippets
 *
 * @return WPCode_Snippet[]
 */
function wpcode_maybe_exclude_snippet_by_device_type( $snippets ) {
	// If there's nothing to evaluate just return an empty array.
	if ( empty( $snippets ) ) {
		return array();
	}
	$filtered_snippets   = array();
	$current_device_type = wp_is_mobile() ? 'mobile' : 'desktop';

	foreach ( $snippets as $snippet ) {
		if ( 'any' === $snippet->get_device_type() ) {
			$filtered_snippets[] = $snippet;
			continue;
		}
		if ( $current_device_type === $snippet->get_device_type() ) {
			$filtered_snippets[] = $snippet;
		}
	}

	return $filtered_snippets;
}
