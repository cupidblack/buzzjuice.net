<?php
/**
 * Class used to handle requests to the personal library.
 *
 * @package WPCode
 */

/**
 * Class WPCode My Library.
 */
class WPCode_My_Library extends WPCode_Library {

	/**
	 * Key for storing snippets in the cache.
	 *
	 * @var string
	 */
	protected $cache_key = 'my-snippets';

	/**
	 * Library endpoint for loading all data.
	 *
	 * @var string
	 */
	protected $all_snippets_endpoint = 'mysnippets';
	/**
	 * The default time to live for library items that are cached.
	 * 10 minutes for the "my-library" area.
	 *
	 * @var int
	 */
	protected $ttl = 600;
	/**
	 * Meta Key used for storing the library id.
	 *
	 * @var string
	 */
	protected $snippet_library_id_meta_key = '_wpcode_cloud_id';

	/**
	 * Key for transient used to store already installed snippets.
	 *
	 * @var string
	 */
	protected $used_snippets_transient_key = 'wpcode_used_my_cloud_snippets';

	/**
	 * Constructor for class.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->ajax_hooks();
		$this->push_hooks();

		// Add cloud snippets to the list of snippets with updates.
		add_filter( 'wpcode_snippets_with_updates', array( $this, 'add_cloud_snippets_with_updates' ) );
	}

	/**
	 * Separate method for ajax handlers that can optionally not be inherited
	 *
	 * @return void
	 */
	protected function ajax_hooks() {
		// Ajax handlers.
		add_action( 'wp_ajax_wpcode_save_to_cloud', array( $this, 'save_snippet_handler' ) );
		add_action( 'wp_ajax_wpcode_my_library_delete_snippet', array( $this, 'delete_snippet_handler' ) );
	}

	/**
	 * Adds hooks for receiving requests from the library.
	 *
	 * @return void
	 */
	protected function push_hooks() {
		add_action( 'plugins_loaded', array( $this, 'maybe_receive_request' ), 0 );
	}

	/**
	 * Check if the request is an AJAX request for updating snippets from the library.
	 *
	 * @return void
	 */
	public function maybe_receive_request() {
		if ( wp_doing_ajax() && isset( $_GET['wpcode_library'] ) && 'push' === $_GET['wpcode_library'] ) { // phpcs:ignore
			// Don't execute any snippet when we execute the logic for updating snippets from the library.
			add_filter( 'wpcode_do_auto_insert', '__return_false' );
			add_action( 'wp_ajax_nopriv_wpcode_update_from_library', array( $this, 'update_from_library' ) );
		}
	}

	/**
	 * Listener to trigger an update from the library based on the request.
	 *
	 * @return void
	 */
	public function update_from_library() {
		// Let's drop it if not a POST request.
		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request', 'wpcode-premium' ),
				)
			);
		}

		$snippet_id = isset( $_REQUEST['snippet_id'] ) ? sanitize_key( $_REQUEST['snippet_id'] ) : ''; // phpcs:ignore
		$auth_key   = isset( $_REQUEST['auth_key'] ) ? sanitize_key( $_REQUEST['auth_key'] ) : ''; // phpcs:ignore
		$unique_id  = isset( $_POST['t'] ) ? sanitize_key( $_POST['t'] ) : ''; // Unique id of this request.
		if ( empty( $snippet_id ) || empty( $auth_key ) || empty( $unique_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing authorization data', 'wpcode-premium' ),
				)
			);
		}

		$signed_data = wpcode()->library_auth->sign( $snippet_id . $unique_id );
		// Check if the request is legit.
		if ( empty( $signed_data ) || ! hash_equals( $signed_data, $auth_key ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid authorization data', 'wpcode-premium' ),
				)
			);
		}

		// Let's update the library now.
		$this->get_from_server();

		// Let's find the snippet on the site and update it or create a new snippet if it doesn't exist.
		$cloud_snippet = $this->grab_snippet_from_api( $snippet_id );

		if ( false === $cloud_snippet ) {
			wp_send_json_error(
				array(
					'message'    => __( 'Loading new snippets timed out, please try again.', 'wpcode-premium' ),
					'snippet_id' => $snippet_id,
				)
			);
		}

		$local_snippets = wpcode()->my_library->get_used_library_snippets();

		kses_remove_filters();

		if ( isset( $local_snippets[ $snippet_id ] ) ) {
			// Let's see if this unique_id has been used already using the remote_id_exists method in the revisions instance.
			if ( wpcode()->revisions->remote_id_exists( $local_snippets[ $snippet_id ], $unique_id ) ) {
				wp_send_json_success(
					array(
						'message' => __( 'Snippet already updated.', 'wpcode-premium' ),
					)
				);
			}
			$cloud_snippet['id'] = $local_snippets[ $snippet_id ];
			$snippet             = wpcode_get_snippet( $cloud_snippet );
			wpcode()->revisions->set_remote_id( $unique_id );
			// Save the snippet.
			$snippet->save();
		} else {
			wpcode()->revisions->set_remote_id( $unique_id );
			wpcode()->my_library->create_new_snippet( $snippet_id );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Snippet updated.', 'wpcode-premium' ),
			)
		);
	}

	/**
	 * Get the editor base URL. Used for pointing user to the page to edit their snippets.
	 *
	 * @return string
	 */
	public function get_editor_url() {
		return trailingslashit( wpcode()->library_auth->library_url ) . 'editor/';
	}

	/**
	 * Save a snippet to the user's cloud.
	 *
	 * @param int|WPCode_Snippet $snippet The snippet to save.
	 *
	 * @return bool
	 */
	public function save_snippet_to_cloud( $snippet ) {
		if ( ! $snippet instanceof WPCode_Snippet ) {
			$snippet = wpcode_get_snippet( $snippet );
		}

		$data = $snippet->get_data_for_caching();

		$data['cloud_id']         = $snippet->get_cloud_id();
		$data['tags']             = $snippet->get_tags();
		$data['note']             = $snippet->get_note();
		$data['custom_shortcode'] = $snippet->get_custom_shortcode();

		// Get current snippet_version.
		$current_snippet_version = get_post_meta( $snippet->get_id(), '_wpcode_snippet_version', true );

		// If no snippet_version exists, set default to 1.0.0.
		if ( empty( $current_snippet_version ) ) {
			$current_snippet_version = '1.0.0';
		}

		// Increment the snippet_version (1.0.0 -> 1.0.1).
		$version_parts = explode( '.', $current_snippet_version );
		if ( count( $version_parts ) === 3 ) {
			$version_parts[2]    = intval( $version_parts[2] ) + 1;
			$new_snippet_version = implode( '.', $version_parts );

			// Update the snippet version in the database.
			update_post_meta( $snippet->get_id(), '_wpcode_snippet_version', $new_snippet_version );
		}

		if ( 'blocks' === $snippet->get_code_type() ) {
			$data['code'] = wpcode()->snippet_block_editor->get_blocks_content( $snippet );
		}

		// Let's check if the cloud id is still relevant.
		if ( false === $this->grab_snippet_from_api( $data['cloud_id'] ) ) {
			// If we can't find the snippet don't send a cloud id so it gets saved as a new snippet.
			unset( $data['cloud_id'] );
		}

		$request = $this->make_request(
			'snippet/save',
			'POST',
			array(
				'snippet' => $data,
			)
		);

		$response = json_decode( $request, true );

		if ( ! empty( $response['status'] ) && ! empty( $response['data'] ) && ! empty( $response['data']['cloud_id'] ) ) {
			$snippet->set_cloud_id( $response['data']['cloud_id'] );
			$snippet->save();

			$this->delete_cache();
			$this->clear_used_snippets();
		}

		return isset( $response['status'] ) && 'success' === $response['status'];
	}

	/**
	 * Delete a snippet from the library site.
	 *
	 * @param string $cloud_id The snippet id (hash).
	 *
	 * @return bool
	 */
	public function delete_cloud_snippet( $cloud_id ) {

		$request = $this->make_request(
			'snippet/' . $cloud_id,
			'DELETE'
		);

		$response = json_decode( $request );

		return isset( $response->status ) && 'success' === $response->status;
	}

	/**
	 * Grab a snippet data from the API.
	 *
	 * @param int $library_id The id of the snippet in the Library api.
	 *
	 * @return array|array[]|false
	 */
	public function grab_snippet_from_api( $library_id ) {
		$snippets      = $this->get_data();
		$cloud_snippet = array();

		if ( ! empty( $snippets['snippets'] ) ) {
			foreach ( $snippets['snippets'] as $snippet ) {
				if ( $library_id === $snippet['cloud_id'] ) {
					$cloud_snippet = $snippet;
					break;
				}
			}
		}

		return empty( $cloud_snippet ) ? false : $cloud_snippet;
	}

	/**
	 * Grab the library id from the snippet by snippet id.
	 *
	 * @param int $snippet_id The snippet id.
	 *
	 * @return int
	 */
	public function get_snippet_library_id( $snippet_id ) {
		$snippet = wpcode_get_snippet( $snippet_id );

		return $snippet->get_cloud_id();
	}


	/**
	 * Ajax handler to save a snippet to the cloud.
	 *
	 * @return void
	 */
	public function save_snippet_handler() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { // phpcs:ignore
			// If they don't have this they shouldn't be able to load the snippet manager in the first place.
			wp_send_json_error(
				array(
					'title' => __( 'Not allowed', 'wpcode-premium' ),
					'text'  => __( 'You do not have permission to save snippets to the library.', 'wpcode-premium' ),
				)
			);
		}

		if ( ! wpcode()->library_auth->has_auth() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You need to be authenticated to save snippets to the library.', 'wpcode-premium' ),
				)
			);
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;

		if ( ! $snippet_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Snippet not found.', 'wpcode-premium' ),
				)
			);
		}

		if ( $this->save_snippet_to_cloud( $snippet_id ) ) {
			$snippet = wpcode_get_snippet( $snippet_id );
			wp_send_json_success(
				array(
					'title'    => __( 'Snippet saved to the library.', 'wpcode-premium' ),
					'message'  => '',
					'cloud_id' => $snippet->get_cloud_id(),
					'edit_url' => $this->get_editor_url() . $snippet->get_cloud_id(),
				)
			);
		}

		wp_send_json_error(
			array(
				'message' => __( 'Something went wrong. Please try again.', 'wpcode-premium' ),
			)
		);
	}

	/**
	 * Ajax handler for making a request to delete a snippet.
	 *
	 * @return void
	 */
	public function delete_snippet_handler() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) { // phpcs:ignore
			// If they don't have this they shouldn't be able to load the snippet manager in the first place.
			wp_send_json_error(
				array(
					'title' => __( 'Not allowed', 'wpcode-premium' ),
					'text'  => __( 'You do not have permission to delete snippets from the library.', 'wpcode-premium' ),
				)
			);
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : false;
		$cloud_id   = isset( $_POST['cloud_id'] ) ? sanitize_key( $_POST['cloud_id'] ) : false;

		if ( ! $cloud_id ) {
			wp_send_json_error(
				array(
					'title' => __( 'Invalid snippet ID', 'wpcode-premium' ),
					'text'  => __( 'Missing parameter in the request, please reload the page and try again.', 'wpcode-premium' ),
				)
			);
		}

		if ( $this->delete_cloud_snippet( $cloud_id ) ) {
			$this->delete_cache();
			if ( 0 !== $snippet_id ) {
				$snippet = new WPCode_Snippet( $snippet_id );
				$snippet->set_cloud_id( '' );
				$snippet->save();
			}
			wp_send_json_success();
		}

		wp_send_json_error(
			array(
				'title' => __( 'We encountered an error deleting the snippet', 'wpcode-premium' ),
				'text'  => __( 'The request to the Library site to delete the snippet has not been sucessful, please try again in a few minutes as this might be a temporary error.', 'wpcode-premium' ),
			)
		);
	}

	/**
	 * Get data from the options table.
	 *
	 * @param string $key The key to get the data for.
	 * @param int    $ttl The time to live for the cache similar to how we use it for the file cache for consistency.
	 *
	 * @return array|array[]|false|mixed
	 */
	public function get_from_cache( $key, $ttl = 0 ) {
		if ( empty( $ttl ) ) {
			$ttl = $this->ttl;
		}

		$key = $this->get_option_key( $key );

		$data = get_option( $key, false );

		if ( ! isset( $data['data'] ) || isset( $data['time'] ) && $data['time'] + $ttl < time() ) {
			return false;
		}

		if ( isset( $data['data']['error'] ) && isset( $data['data']['time'] ) ) {
			if ( $data['data']['time'] + 10 * MINUTE_IN_SECONDS < time() ) {
				return false;
			} else {
				return $this->get_empty_array();
			}
		}

		return $data['data'];
	}

	/**
	 * Delete the cache.
	 *
	 * @return void
	 */
	public function delete_cache() {
		$key = $this->get_option_key( $this->cache_key );

		delete_option( $key );
	}

	/**
	 * Refresh the library cache.
	 * This method handles the common logic for refreshing the library cache.
	 *
	 * @return bool True if the cache was refreshed, false otherwise.
	 */
	public function refresh_library_cache() {
		// Check rate limiting - only allow 1 refresh per minute.
		$last_update = get_transient( 'wpcode_library_cache_last_update' );
		if ( false !== $last_update ) {
			$time_diff = time() - $last_update;
			if ( $time_diff < 60 ) { // Less than 1 minute.
				return false;
			}
		}

		// Delete the cache.
		$this->delete_cache();

		// Set transient to track when the cache was last refreshed.
		set_transient( 'wpcode_library_cache_last_update', time(), HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Save to the db for this library type.
	 *
	 * @param string $key The key to save the data under.
	 * @param array  $data The data to save.
	 *
	 * @return void
	 */
	public function save_to_cache( $key, $data ) {
		$key = $this->get_option_key( $key );

		$save_data = array(
			'data' => $data,
			'time' => time(),
		);

		update_option( $key, $save_data, false );
	}

	/**
	 * Get the key to an option in the db using a prefix.
	 *
	 * @param string $key The key to grab data for.
	 *
	 * @return string
	 */
	public function get_option_key( $key ) {
		return 'wpcode_' . $key;
	}

	/**
	 * Get all the snippets that were created from the cloud library, by cloud ID.
	 * Results are cached in a transient.
	 *
	 * @return array
	 */
	public function get_used_library_snippets() {
		if ( isset( $this->library_snippets ) ) {
			return $this->library_snippets;
		}

		$snippets_from_library = get_transient( $this->used_snippets_transient_key );

		if ( false === $snippets_from_library ) {
			$snippets_from_library = array();

			$args     = array(
				'post_type'   => wpcode_get_post_type(),
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => $this->snippet_library_id_meta_key,
						'compare' => 'EXISTS',
					),
				),
				'fields'      => 'ids',
				'post_status' => 'any',
				'nopaging'    => true,
			);
			$snippets = get_posts( $args );

			foreach ( $snippets as $snippet_id ) {
				$snippets_from_library[ $this->get_snippet_library_id( $snippet_id ) ] = $snippet_id;
			}

			set_transient( $this->used_snippets_transient_key, $snippets_from_library );
		}

		$this->library_snippets = $snippets_from_library;

		return $this->library_snippets;
	}

	/**
	 * Check if a snippet has an update available by comparing with cached data.
	 *
	 * @param int    $snippet_id The snippet ID.
	 * @param string $cloud_id The cloud ID.
	 * @param bool   $is_cloud Whether this is a cloud snippet.
	 *
	 * @return bool|array False if no update, array with version info if update available.
	 */
	public function check_snippet_update( $snippet_id, $cloud_id, $is_cloud = true ) {
		// Get current version from post meta.
		$current_version = get_post_meta( $snippet_id, '_wpcode_snippet_version', true );

		// Get latest version from cache.
		// For cloud snippets, get from my_library cache.
		$cached_data   = $this->get_data();
		$cloud_snippet = null;

		if ( ! empty( $cached_data['snippets'] ) ) {
			foreach ( $cached_data['snippets'] as $snippet ) {
				if ( $cloud_id === $snippet['cloud_id'] ) {
					$cloud_snippet = $snippet;
					break;
				}
			}
		}

		if ( ! $cloud_snippet || empty( $cloud_snippet['version'] ) ) {
			return false;
		}

		$latest_version = $cloud_snippet['version'];

		// If either version is empty, set it to 1.0.0.
		if ( empty( $current_version ) ) {
			$current_version = '1.0.0';
		}

		if ( empty( $latest_version ) ) {
			$latest_version = '1.0.0';
		}

		// If latest version is greater than current version, update is available.
		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			return array(
				'current_version' => $current_version,
				'latest_version'  => $latest_version,
				'is_cloud'        => true,
			);
		}

		return false;
	}

	/**
	 * Get the list of snippets that have updates available.
	 * Checks on the fly using cached data.
	 *
	 * @return array
	 */
	public function get_snippets_with_updates() {
		// Get all snippets with cloud IDs.
		$cloud_snippets = $this->get_used_library_snippets();

		$snippets_with_updates = array();

		// Check cloud snippets.
		foreach ( $cloud_snippets as $cloud_id => $snippet_id ) {
			$update_info = $this->check_snippet_update( $snippet_id, $cloud_id );
			if ( $update_info ) {
				$snippets_with_updates[] = $snippet_id;
			}
		}

		return $snippets_with_updates;
	}

	/**
	 * Add cloud snippets to the list of snippets with updates.
	 *
	 * @param array $snippets_with_updates The list of snippets with updates.
	 *
	 * @return array
	 */
	public function add_cloud_snippets_with_updates( $snippets_with_updates ) {
		// Get cloud snippets with updates.
		$cloud_snippets_with_updates = $this->get_snippets_with_updates();

		// Merge with existing snippets with updates.
		return array_merge( $snippets_with_updates, $cloud_snippets_with_updates );
	}

	/**
	 * Update a snippet from the cloud library.
	 *
	 * @param int    $snippet_id The ID of the snippet to update.
	 * @param string $cloud_id The ID of the cloud snippet to fetch.
	 *
	 * @return array|false Array with success data or false on failure.
	 */
	public function update_snippet_from_library( $snippet_id, $cloud_id ) {
		// Get snippet data from cloud library.
		$cloud_snippet = $this->grab_snippet_from_api( $cloud_id );

		if ( ! $cloud_snippet ) {
			return false;
		}

		// Update snippet.
		$cloud_snippet['id'] = $snippet_id;
		$snippet             = wpcode_get_snippet( $cloud_snippet );
		$result              = $snippet->save();

		if ( ! $result ) {
			return false;
		}

		// Update local version metadata.
		if ( ! empty( $cloud_snippet['version'] ) ) {
			update_post_meta( $snippet_id, '_wpcode_snippet_version', $cloud_snippet['version'] );
		}

		return array(
			'success' => true,
			'version' => ! empty( $cloud_snippet['version'] ) ? $cloud_snippet['version'] : '',
		);
	}
}
