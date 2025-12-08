<?php
/**
 * Use a filter to add page-specific snippets to the location selected.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wpcode_get_snippets_for_location', 'wpcode_load_metabox_snippets', 10, 2 );
add_action( 'wp', 'wpcode_maybe_load_post_global_snippets' );

/**
 * Load metabox snippets on singular pages.
 *
 * @param array  $snippets The snippets loaded for the location.
 * @param string $location The location name.
 *
 * @return array
 */
function wpcode_load_metabox_snippets( $snippets, $location ) {
	// Check if query has run.
	if ( ! did_action( 'init' ) ) {
		return $snippets;
	}
	if ( ! is_singular() ) {
		return $snippets;
	}

	$post_custom_snippets = get_post_meta( get_the_ID(), '_wpcode_page_snippets', true );

	if ( empty( $post_custom_snippets ) || ! is_array( $post_custom_snippets ) ) {
		return $snippets;
	}

	$snippets_by_id = wpcode()->cache->get_cached_snippets_by_id();

	foreach ( $post_custom_snippets as $post_custom_snippet ) {
		// Only add the snippet for execution if it's the location we are looking for.
		$snippet_location = $post_custom_snippet['location'];
		if ( $snippet_location !== $location ) {
			continue;
		}

		// If the snippet is not cached let's load it and see if it's active.
		if ( ! array_key_exists( $post_custom_snippet['snippet_id'], $snippets_by_id ) ) {
			$page_snippet = new WPCode_Snippet( $post_custom_snippet['snippet_id'] );
			if ( isset( $post_custom_snippet['number'] ) ) {
				$page_snippet->insert_number = absint( $post_custom_snippet['number'] );
			}
			if ( $page_snippet->is_active() ) {
				$snippets[] = $page_snippet;
				continue;
			}
		}

		// Let's also make sure we don't load the same snippet twice.
		$already_loaded = false;
		foreach ( $snippets as $snippet ) {
			if ( $snippet->get_id() === $post_custom_snippet['snippet_id'] ) {
				$already_loaded = $snippet;
				break;
			}
		}

		if (
			false !== $already_loaded &&
			in_array( $location, wpcode_get_auto_insert_locations_with_number(), true ) &&
			isset( $post_custom_snippet['number'] ) &&
			$snippets_by_id[ $post_custom_snippet['snippet_id'] ]->get_auto_insert_number() !== absint( $post_custom_snippet['number'] )
		) {
			// If they have the same location but different numbers add it again.
			$another_snippet                = clone $snippets_by_id[ $post_custom_snippet['snippet_id'] ];
			$another_snippet->insert_number = absint( $post_custom_snippet['number'] );
			$snippets[]                     = $another_snippet;
		}

		// If the snippet is cached and active let's add it to the list.
		if ( ! $already_loaded && ! empty( $snippets_by_id[ $post_custom_snippet['snippet_id'] ] ) ) {
			// If the location needs a number, use the number set in the metabox.
			if ( in_array( $location, wpcode_get_auto_insert_locations_with_number(), true ) ) {
				$another_snippet                = clone $snippets_by_id[ $post_custom_snippet['snippet_id'] ];
				$another_snippet->insert_number = absint( $post_custom_snippet['number'] );
				$snippets[]                     = $another_snippet;
			} else {
				$snippets[] = $snippets_by_id[ $post_custom_snippet['snippet_id'] ];
			}
		}
	}

	return $snippets;
}

/**
 * Running PHP snippets on the init hook won't work for single posts so we need to load those later.
 * This will enable you to add filters specific to a page, for example, but nothing before the wp hook.
 *
 * @return void
 */
function wpcode_maybe_load_post_global_snippets() {
	$post_custom_snippets = get_post_meta( get_the_ID(), '_wpcode_page_snippets', true );

	if ( empty( $post_custom_snippets ) || ! is_array( $post_custom_snippets ) ) {
		return;
	}

	$global_locations = array(
		'everywhere',
		'frontend_only',
	);

	foreach ( $post_custom_snippets as $post_custom_snippet ) {
		if ( ! empty( $post_custom_snippet['location'] ) && in_array( $post_custom_snippet['location'], $global_locations, true ) ) {
			$snippet = new WPCode_Snippet( $post_custom_snippet['snippet_id'] );
			wpcode()->execute->get_snippet_output( $snippet );
		}
	}
}
