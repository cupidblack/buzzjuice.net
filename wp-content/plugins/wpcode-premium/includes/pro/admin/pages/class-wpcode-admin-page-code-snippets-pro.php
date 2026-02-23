<?php

/**
 * Code snippets pro admin main list page.
 *
 * @package WPCode
 */

/**
 * Class for the Pro code snippets page.
 */
class WPCode_Admin_Page_Code_Snippets_Pro extends WPCode_Admin_Page_Code_Snippets {

	/**
	 * Page-specific hooks, init the custom WP_List_Table.
	 *
	 * @return void
	 */
	public function page_hooks() {
		parent::page_hooks();

		add_action( 'admin_init', array( $this, 'maybe_refresh_library_cache' ) );
	}

	/**
	 * Reorder the buttons and add version-specific info.
	 *
	 * @param array $type_buttons The type buttons.
	 *
	 * @return array
	 */
	protected function prepare_type_buttons( $type_buttons ) {
		foreach ( $type_buttons as $type_button_key => $type_button ) {
			if ( empty( $type_button['type'] ) ) {
				continue;
			}
			$capability = WPCode_Access::capability_for_code_type( $type_button['type'] );
			if ( ! current_user_can( $capability ) ) {
				unset( $type_buttons[ $type_button_key ] );
			}
		}

		return $type_buttons;
	}

	/**
	 * Remove the duplicated parameter from the URL.
	 *
	 * @param array $args The arguments that should be removed from the URL.
	 *
	 * @return array
	 */
	public function remove_query_arg_from_url( $args ) {
		$args   = parent::remove_query_arg_from_url( $args );
		$args[] = 'refresh_library_cache';
		$args[] = '_wpnonce';

		return $args;
	}

	/**
	 * Check if we need to refresh the library cache based on query parameter.
	 * This is a non-AJAX version of the wpcode_refresh_library_cache AJAX handler.
	 *
	 * @return void
	 */
	public function maybe_refresh_library_cache() {
		// Check if the query parameter exists.
		if ( ! isset( $_GET['refresh_library_cache'] ) ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode_admin' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
			return;
		}

		// Check if the library is connected.
		if ( ! wpcode()->library_auth->has_auth() ) {
			return;
		}

		// Refresh the library cache.
		wpcode()->my_library->refresh_library_cache();
	}
}
