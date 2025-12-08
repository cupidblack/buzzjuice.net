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

	public function maybe_receive_request() {
		if ( wp_doing_ajax() && isset( $_GET['wpcode_library'] ) && 'push' === $_GET['wpcode_library'] ) {
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

		$snippet_id = isset( $_REQUEST['snippet_id'] ) ? sanitize_key( $_REQUEST['snippet_id'] ) : '';
		$auth_key   = isset( $_REQUEST['auth_key'] ) ? sanitize_key( $_REQUEST['auth_key'] ) : '';
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

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
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

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
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
}
