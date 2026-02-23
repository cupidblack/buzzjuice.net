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
add_filter( 'wpcode_code_snippets_table_column_value', 'wpcode_maybe_add_cloud_sync_button', 10, 3 );

/**
 * Add a scheduled icon for snippets that are scheduled and active.
 *
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

/**
 * Add sync button for cloud snippets with updates.
 *
 * @param string         $value The value of the current column.
 * @param WPCode_Snippet $snippet The snippet we are showing the row for.
 * @param string         $column_name The name of the column we are showing.
 *
 * @return string
 */
function wpcode_maybe_add_cloud_sync_button( $value, $snippet, $column_name ) {
	if ( 'status' !== $column_name ) {
		return $value;
	}

	// Check for cloud snippets.
	$cloud_id = $snippet->get_cloud_id();
	if ( empty( $cloud_id ) ) {
		return $value;
	}

	// Check if the cloud snippet has updates.
	$update_info = wpcode()->my_library->check_snippet_update( $snippet->get_id(), $cloud_id );
	if ( $update_info ) {
		// If it's a cloud snippet with updates.
		$current_version = isset( $update_info['current_version'] ) ? $update_info['current_version'] : '';
		$latest_version  = isset( $update_info['latest_version'] ) ? $update_info['latest_version'] : '';

		$value .= ' <button class="wpcode-sync-button wpcode-sync-snippet" data-id="' . absint( $snippet->get_id() ) . '" data-cloud-id="' . esc_attr( $cloud_id ) . '" data-current-version="' . esc_attr( $current_version ) . '" data-latest-version="' . esc_attr( $latest_version ) . '">' . esc_html__( 'Update Available', 'wpcode-premium' );
		$value .= '</button>';
	}

	return $value;
}
