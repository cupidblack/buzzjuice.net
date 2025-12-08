<?php
/**
 * Filter the snippets based on the schedule metabox selection.
 * This supersedes the conditional logic rules.
 *
 * @since 2.0.8
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wpcode_get_snippets_for_location', 'wpcode_maybe_exclude_snippet_by_schedule', 10 );

/**
 * Filter loaded snippets by the schedule parameters.
 *
 * @param WPCode_Snippet[] $snippets The snippets for the location that we are filtering.
 *
 * @return WPCode_Snippet[]
 */
function wpcode_maybe_exclude_snippet_by_schedule( $snippets ) {
	// If there's nothing to evaluate just return an empty array.
	if ( empty( $snippets ) ) {
		return array();
	}
	$filtered_snippets = array();
	$current_date_time = WPCode_Conditional_Schedule::current_datetime();
	$now               = strtotime( $current_date_time->format( 'Y-m-d H:i' ) );

	foreach ( $snippets as $snippet ) {
		$schedule = $snippet->get_schedule();

		if ( ! empty( $schedule['start'] ) ) {
			if ( $now < strtotime( $schedule['start'] ) ) {
				continue;
			}
		}
		if ( ! empty( $schedule['end'] ) ) {
			if ( $now > strtotime( $schedule['end'] ) ) {
				continue;
			}
		}

		$filtered_snippets[] = $snippet;
	}

	return $filtered_snippets;
}
