<?php
/**
 * Show pro-only values in the snippets admin table.
 *
 * @since 2.0.8
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wpcode_code_snippets_table_column_value', 'wpcode_maybe_show_snippet_scheduled_icon', 10, 3 );

/**
 * @param string         $value The value of the current column.
 * @param WPCode_Snippet $snippet The snippet we are showing the row for.
 * @param string         $column_name The name of the column we are showing.
 *
 * @return string
 */
function wpcode_maybe_show_snippet_scheduled_icon( $value, $snippet, $column_name ) {
	if ( 'status' !== $column_name ) {
		return $value;
	}

	if ( $snippet->is_scheduled() && $snippet->is_active() ) {
		$value .= '<span class="wpcode-table-status-icon wpcode-scheduled-icon" title="' . esc_attr__( 'Scheduled', 'wpcode-premium' ) . '">' . get_wpcode_icon( 'scheduled', 48, 48 ) . '</span>';
	}

	return $value;
}
