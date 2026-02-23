<?php
/**
 * Admin ajax handlers for the Pro version of the plugin.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_wpcode_load_snippets', 'wpcode_ajax_snippets_list' );
add_action( 'wp_ajax_wpcode_load_selected_snippet', 'wpcode_ajax_selected_snippet' );
add_action( 'wp_ajax_wpcode_load_revisions_compare', 'wpcode_load_revisions_compare' );
add_action( 'wp_ajax_wpcode_verify_license', 'wpcode_verify_license' );
add_action( 'wp_ajax_wpcode_deactivate_license', 'wpcode_deactivate_license' );

add_action( 'wp_ajax_wpcode_install_addon', 'wpcode_install_addon' );

add_action( 'wp_ajax_wpcode_search_snippets', 'wpcode_ajax_search_snippets' );
add_action( 'wp_ajax_wpcode_search_posts', 'wpcode_ajax_search_posts' );
add_action( 'wp_ajax_wpcode_search_users', 'wpcode_ajax_search_users' );

add_action( 'wp_ajax_wpcode_refresh_library_cache', 'wpcode_refresh_library_cache' );

// Override the sync snippet function to handle cloud snippets with higher priority.
add_action( 'wp_ajax_wpcode_sync_snippet', 'wpcode_sync_snippet_pro', 5 );
add_action( 'wp_ajax_wpcode_refresh_license', 'wpcode_refresh_license' );


/**
 * AJAX handler for syncing a snippet from the cloud library - Pro version.
 * This function overrides the one in the main plugin to handle cloud snippets.
 * If the snippet is not a cloud snippet, it returns early to let the lite version handle it.
 *
 * @return void
 */
function wpcode_sync_snippet_pro() {

	// Check nonce.
	if ( ! check_ajax_referer( 'wpcode_admin', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'insert-headers-and-footers' ) ) );
	}

	// Check permissions.
	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error( array( 'message' => __( 'You do not have permission to edit snippets.', 'insert-headers-and-footers' ) ) );
	}

	$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;
	$cloud_id   = isset( $_POST['cloud_id'] ) ? sanitize_text_field( wp_unslash( $_POST['cloud_id'] ) ) : false;

	// If this is not a cloud snippet, return early to let the lite version handle it.
	if ( empty( $cloud_id ) ) {
		return;
	}

	if ( ! $snippet_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid snippet ID.', 'insert-headers-and-footers' ) ) );
	}

	// Check if library is connected.
	if ( ! wpcode()->library_auth->has_auth() ) {
		wp_send_json_error( array( 'message' => __( 'You need to connect to the WPCode Library to sync cloud snippets.', 'insert-headers-and-footers' ) ) );
	}

	// Update snippet from cloud library.
	$result = wpcode()->my_library->update_snippet_from_library( $snippet_id, $cloud_id );

	if ( ! $result ) {
		wp_send_json_error( array( 'message' => __( 'Failed to update snippet from cloud library.', 'insert-headers-and-footers' ) ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Snippet successfully updated from cloud library.', 'insert-headers-and-footers' ),
			'version' => ! empty( $result['version'] ) ? $result['version'] : '',
		)
	);
}

/**
 * Ajax handler to load a list of snippets.
 *
 * @return void
 */
function wpcode_ajax_snippets_list() {

	check_ajax_referer( 'wpcode_admin_global' );

	if ( ! current_user_can( 'wpcode_activate_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$selected = isset( $_POST['selected'] ) ? array_map( 'absint', $_POST['selected'] ) : array();

	if ( 0 !== $post_id && empty( $selected ) ) {
		$selected_snippets = get_post_meta( $post_id, '_wpcode_page_snippets', true );
		if ( empty( $selected_snippet ) ) {
			$selected_snippets = array();
		}
		foreach ( $selected_snippets as $selected_snippet ) {
			if ( isset( $selected_snippet['snippet_id'] ) ) {
				$selected[] = absint( $selected_snippet['snippet_id'] );
			}
		}
	}

	$query_args = array(
		'post_type'      => 'wpcode',
		'posts_per_page' => 10,
		'paged'          => $page,
		'post_status'    => 'any',
	);

	if ( ! empty( $search ) ) {
		$query_args['s'] = trim( $search );
	}

	$snippets = get_posts( $query_args );

	$response = array(
		'snippets' => array(),
	);
	foreach ( $snippets as $snippet ) {
		$snippet_obj            = new WPCode_Snippet( $snippet );
		$response['snippets'][] = wpcode_get_snippet_item_picker( $snippet_obj, in_array( $snippet_obj->get_id(), $selected, true ) );
	}

	wp_send_json_success( $response );
}

/**
 * Ajax handler for loading the markup of a selected snippet in the Metabox.
 *
 * @return void
 */
function wpcode_ajax_selected_snippet() {
	check_ajax_referer( 'wpcode_admin_global' );

	if ( ! current_user_can( 'wpcode_activate_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;
	$snippet    = new WPCode_Snippet( $snippet_id );

	if ( 0 === $snippet->get_id() ) {
		wp_send_json_error();
	}

	wp_send_json_success(
		array(
			'snippet' => wpcode_get_snippet_item_selected( $snippet, $snippet->get_location(), $snippet->get_auto_insert_number() ),
		)
	);
}

/**
 * Get the HTML markup for a snippet.
 *
 * @param int|WPCode_Snippet $snippet The snippet to get the markup for.
 * @param bool               $checked Is the checkbox checked (snippet selected).
 *
 * @return string
 */
function wpcode_get_snippet_item_picker( $snippet, $checked = false ) {
	if ( ! $snippet instanceof WPCode_Snippet ) {
		$snippet = new WPCode_Snippet( $snippet );
	}

	$markup = '<div class="wpcode-snippet-picker-item">';

	$markup .= sprintf(
		'<label for="wpcode-snippet-checkbox-%2$s">%1$s</label>',
		esc_html( $snippet->get_title() ),
		absint( $snippet->get_id() )
	);
	$markup .= wpcode_get_checkbox_toggle( $checked, 'wpcode-snippet-checkbox-' . $snippet->get_id(), '', absint( $snippet->get_id() ) );
	$markup .= '</div>';

	return $markup;
}

/**
 * Get the markup for a selected snippet with option to override the location.
 *
 * @param int|WPCode_Snippet $snippet The snippet id or snippet object.
 * @param string             $location The location to override the global une.
 * @param int                $insert_number The insert number for locations that need one.
 *
 * @return string
 */
function wpcode_get_snippet_item_selected( $snippet, $location, $insert_number ) {
	if ( ! $snippet instanceof WPCode_Snippet ) {
		$snippet = new WPCode_Snippet( $snippet );
	}

	$name        = "wpcode_auto_insert_override[{$snippet->get_id()}]";
	$name_number = "wpcode_auto_insert_number_override[{$snippet->get_id()}]";

	$markup = sprintf(
		'<div class="wpcode-list-item wpcode-selected-snippet-item wpcode-list-item-has-pill" id="%1$s" data-id="%2$s">',
		'wpcode-selected-snippet-' . $snippet->get_id(),
		$snippet->get_id()
	);
	if ( ! $snippet->is_active() ) {
		$markup .= sprintf(
			'<span class="wpcode-list-item-pill wpcode-list-item-pill-gray">%s</span>',
			__( 'Inactive', 'wpcode-premium' )
		);
	}
	$markup .= sprintf(
		'<div class="wpcode-list-item-top-actions"><button type="button" class="wpcode-button-just-icon wpcode-remove-snippet-item">%s</button></div>',
		get_wpcode_icon( 'trash' )
	);
	$markup .= sprintf(
		'<h3 title="%1$s">%1$s</h3>',
		$snippet->get_title()
	);
	if ( '' !== $snippet->get_location() ) {
		$markup .= '<h4>';
		// Translators: The name of the global auto-insert location for a snippet loaded in the edit-post metabox.
		$markup .= sprintf( esc_html__( 'Global location: %s', 'wpcode-premium' ), wpcode()->auto_insert->get_location_label( $snippet->get_location() ) );
		if ( in_array( $snippet->get_location(), wpcode_get_auto_insert_locations_with_number(), true ) && $snippet->get_auto_insert_number() ) {
			$markup .= ' #' . $snippet->get_auto_insert_number();
		}
		$markup .= '</h4>';
	} else {
		$markup .= '<h4>' . __( 'Auto-insert disabled', 'wpcode-premium' ) . '</h4>';
	}
	$markup .= '<div class="wpcode-list-item-location-details">';
	$markup .= '<div class="wpcode-auto-insert-location-wrap">';
	$markup .= sprintf(
		'<label for="%1$s">%2$s</label>',
		$name,
		__( 'Page location:', 'wpcode-premium' )
	);
	$markup .= wpcode_get_auto_insert_location_picker( $location, $snippet->get_code_type(), $name );

	$markup .= '</div>';// End .wpcode-auto-insert-location-wrap.

	$insert_number_wrap_hidden = ! in_array( $location, wpcode_get_auto_insert_locations_with_number(), true ) ? ' wpcode-hidden' : '';

	$markup .= sprintf(
		'<div class="wpcode-auto-insert-number-wrap%1$s">',
		$insert_number_wrap_hidden
	);
	$markup .= sprintf(
		'<label for="%1$s">%2$s</label>',
		$name_number,
		__( 'Insert number:', 'wpcode-premium' )
	);
	$markup .= '<input type="number" name="' . esc_attr( $name_number ) . '" value="' . absint( $insert_number ) . '" />';
	$markup .= '</div>'; // .wpcode-auto-insert-number-wrap
	$markup .= '</div>'; // .wpcode-list-item-location-details

	$markup .= '</div>';

	return $markup;
}

/**
 * Load revisions compare markup for 2 revisions by their ID.
 *
 * @return void
 */
function wpcode_load_revisions_compare() {
	check_ajax_referer( 'wpcode_admin' );

	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	if ( ! isset( $_POST['ids'] ) || ! isset( $_POST['snippet_id'] ) ) {
		wp_send_json_error();
	}

	$ids        = array_map( 'absint', wp_unslash( $_POST['ids'] ) );
	$snippet_id = absint( $_POST['snippet_id'] );

	$all_revisions = array_reverse( wpcode()->revisions->get_snippet_revisions( $snippet_id ) );
	$disabled_2    = false;

	foreach ( $all_revisions as $key => $revision ) {
		if ( absint( $revision->revision_id ) === $ids[0] ) {
			$revision_1 = $revision;
		}
		if ( absint( $revision->revision_id ) === $ids[1] ) {
			if ( count( $all_revisions ) - 1 === $key ) {
				$disabled_2 = true;
			}
			$revision_2 = $revision;
		}
	}

	if ( empty( $revision_1 ) || empty( $revision_2 ) ) {
		wp_send_json_error();

		return;
	}

	$type = 'header_footer';
	if ( 0 !== $snippet_id ) {
		$post_type = get_post_type( $snippet_id );
		if ( 'wpcode' === $post_type ) {
			$type = 'snippet';
		} else {
			$type = 'page_scripts';
		}
	}

	$revision_1_data = json_decode( $revision_1->revision_data, true );
	$revision_2_data = json_decode( $revision_2->revision_data, true );

	ob_start();

	WPCode_Admin_Page_Revisions::get_revision_compare_header( $revision_1, false, __( 'From:', 'wpcode-premium' ) );
	WPCode_Admin_Page_Revisions::get_revision_compare_header( $revision_2, $disabled_2, __( 'To:', 'wpcode-premium' ) );
	WPCode_Admin_Page_Revisions::get_revision_compare_content( $revision_1_data, $revision_2_data, $type );

	$markup = ob_get_clean();

	wp_send_json_success(
		array(
			'markup' => $markup,
		)
	);
}


/**
 * Verify license via Ajax.
 *
 * @since 2.1.0
 */
function wpcode_verify_license() {

	// Run a security check.
	check_ajax_referer( 'wpcode_admin' );

	// Check for permissions.
	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	// Check for license key.
	if ( empty( $_POST['license'] ) ) {
		wp_send_json_error( esc_html__( 'Please enter a license key.', 'wpcode-premium' ) );
	}
	$multisite = isset( $_POST['multisite'] ) && boolval( $_POST['multisite'] );

	wpcode()->license->verify_key( sanitize_text_field( wp_unslash( $_POST['license'] ) ), true, $multisite );
}

/**
 * Deactivate license via Ajax.
 *
 * @since 2.1.0
 */
function wpcode_deactivate_license() {

	$true = true;

	// Run a security check.
	check_ajax_referer( 'wpcode_admin' );

	// Check for permissions.
	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$force = isset( $_POST['force'] ) && 'true' === $_POST['force'];

	$multisite = isset( $_POST['multisite'] ) && boolval( $_POST['multisite'] );

	wpcode()->license->deactivate_key( true, $force, $multisite );
}

/**
 * Ajax endpoint for installing an addon.
 *
 * @return void
 */
function wpcode_install_addon() {
	check_ajax_referer( 'wpcode_admin' );

	if ( ! wpcode()->addons->can_install() ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to install addons.', 'wpcode-premium' ),
			)
		);
	}

	$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

	if ( empty( $slug ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'No addon slug provided.', 'wpcode-premium' ),
			)
		);
	}

	$multisite = isset( $_POST['multisite'] ) && boolval( $_POST['multisite'] );

	$addon = wpcode()->addons->install_addon( $slug, $multisite );

	if ( false === $addon ) {
		wp_send_json_error(
			array(
				'message' => __( 'Could not install addon.', 'wpcode-premium' ),
			)
		);
	}

	wp_send_json_success(
		array(
			'message' => __( 'Addon installed successfully.', 'wpcode-premium' ),
			'slug'    => $slug,
		)
	);
}

/**
 * Ajax endpoint for searching through snippets.
 *
 * @return void
 */
function wpcode_ajax_search_snippets() {
	check_ajax_referer( 'wpcode_admin' );

	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	$snippets = get_posts(
		array(
			's'              => $term,
			'post_type'      => 'wpcode',
			'posts_per_page' => 10,
			'post_status'    => 'any',
		)
	);

	$results = array();

	foreach ( $snippets as $snippet ) {
		$results[] = array(
			'id'   => $snippet->ID,
			'text' => $snippet->post_title,
		);
	}

	wp_send_json(
		array(
			'results' => $results,
		)
	);
}

/**
 * Ajax endpoint for searching through posts.
 *
 * @return void
 */
function wpcode_ajax_search_posts() {
	check_ajax_referer( 'wpcode_admin' );

	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	$public_post_types = get_post_types(
		array(
			'public' => true,
		)
	);

	$posts = get_posts(
		array(
			's'              => $term,
			'post_type'      => $public_post_types,
			'posts_per_page' => 10,
			'post_status'    => 'any',
			'orderby'        => 'relevance',
		)
	);

	$results = array();

	foreach ( $posts as $post ) {
		$results[] = array(
			'id'   => $post->ID,
			'text' => $post->post_title,
		);
	}

	wp_send_json(
		array(
			'results' => $results,
		)
	);
}

/**
 * Ajax callback for searching through users.
 *
 * @return void
 */
function wpcode_ajax_search_users() {
	check_ajax_referer( 'wpcode_admin' );

	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	$users = get_users(
		array(
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			'number'         => 10,
		)
	);

	$results = array();

	foreach ( $users as $user ) {
		$results[] = array(
			'id'   => $user->ID,
			'text' => $user->display_name,
		);
	}

	wp_send_json(
		array(
			'results' => $results,
		)
	);
}

/**
 * AJAX handler for refreshing the library cache.
 * This is a global version of the maybe_refresh_library_cache method in class-wpcode-admin-page-code-snippets-pro.php.
 *
 * @return void
 */
function wpcode_refresh_library_cache() {
	// Check nonce.
	if ( ! check_ajax_referer( 'wpcode_admin', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'insert-headers-and-footers' ) ) );
	}

	// Check permissions.
	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error( array( 'message' => __( 'You do not have permission to check for updates.', 'insert-headers-and-footers' ) ) );
	}

	// Check if the library is connected.
	if ( ! wpcode()->library_auth->has_auth() ) {
		wp_send_json_error( array( 'message' => __( 'You need to connect to the WPCode Library to check for updates to your private library.', 'insert-headers-and-footers' ) ) );
	}

	// Attempt to refresh the library cache.
	$result = wpcode()->my_library->refresh_library_cache();

	// If the cache was not refreshed due to rate limiting.
	if ( ! $result ) {
		wp_send_json_error(
			array(
				'message'          => __( 'Please wait before checking again. You can check for private library updates once per minute.', 'insert-headers-and-footers' ),
				'recently_updated' => true,
			)
		);
	}

	wp_send_json_success( array( 'message' => __( 'Library cache refreshed successfully.', 'insert-headers-and-footers' ) ) );
}
/**
 * AJAX handler for refreshing the license status.
 *
 * @return void
 */
function wpcode_refresh_license() {

	// Run a security check.
	check_ajax_referer( 'wpcode_admin' );

	// Check for permissions.
	if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { //phpcs:ignore
		wp_send_json_error();
	}

	// Get the multisite flag.
	$multisite = isset( $_POST['multisite'] ) && boolval( $_POST['multisite'] );

	// Get the license object.
	$license = wpcode()->license;

	// Get the license key.
	$license_key = $license->get( $multisite );

	// Check for license key.
	if ( empty( $license_key ) ) {
		wp_send_json_error( esc_html__( 'Please enter a license key.', 'wpcode-premium' ) );
	}

	// Validate the license key with force = true to refresh from server.
	$result = $license->validate_key( $license_key, true, false, false, $multisite );

	if ( $result ) {
		// Get the updated license type.
		$license_type = $license->type( $multisite );

		wp_send_json_success(
			array(
				'msg'  => esc_html__( 'License key refreshed successfully!', 'wpcode-premium' ),
				'type' => $license_type,
			)
		);
	} elseif ( $license->is_expired() ) {
		wp_send_json_error( esc_html__( 'Your license key has expired. Please renew your license.', 'wpcode-premium' ) );
	} elseif ( $license->is_disabled() ) {
		wp_send_json_error( esc_html__( 'Your license key has been disabled.', 'wpcode-premium' ) );
	} elseif ( $license->is_invalid() ) {
		wp_send_json_error( esc_html__( 'Your license key is invalid.', 'wpcode-premium' ) );
	} else {
		wp_send_json_error( esc_html__( 'There was an error refreshing your license. Please try again later.', 'wpcode-premium' ) );
	}
}
