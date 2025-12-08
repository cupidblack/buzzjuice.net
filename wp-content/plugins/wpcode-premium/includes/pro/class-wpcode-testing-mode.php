<?php
/**
 * This class holds all the functionality for the testing mode.
 * The testing mode enables admins to make changes to snippets without them being reflected to all users.
 *
 * @package WPcode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Testing_Mode
 */
class WPCode_Testing_Mode {

	/**
	 * The option name for the testing mode.
	 *
	 * @var string
	 */
	protected $option_name = 'wpcode_testing_mode';

	/**
	 * The data from the db once loaded.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The instance of the class.
	 *
	 * @var WPCode_Testing_Mode
	 */
	private static $instance;

	/**
	 * The page scripts meta keys.
	 *
	 * @var string[]
	 */
	public $page_scripts_keys = array(
		'_wpcode_header_scripts',
		'_wpcode_footer_scripts',
		'_wpcode_body_scripts',
		'_wpcode_page_snippets',
	);

	/**
	 * WPCode_Testing_Mode constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_toggle_testing_mode' ) );
		add_filter( 'pre_option_wpcode_snippets', array( $this, 'maybe_filter_snippets' ) );
		add_filter( 'wpcode_load_snippet', array( $this, 'load_snippet_from_testing_data' ) );
		add_filter( 'wpcode_admin_js_data', array( $this, 'add_enable_link_to_script' ) );
		add_action( 'wp_ajax_wpcode_update_snippet_status', array( $this, 'maybe_handle_update_status' ), 5 );
		add_action( 'wpcode_after_snippet_duplicated', array( $this, 'track_duplicated_snippet' ), 15 );
		add_filter( 'pre_option_wpcode_custom_shortcodes', array( $this, 'add_custom_shortcodes' ) );
		// Bulk actions handling.
		add_filter( 'wpcode_snippets_bulk_actions', array( $this, 'bulk_actions' ) );
		// Let's capture the save action in testing mode.
		add_filter( 'wpcode_pre_save_snippet', array( $this, 'pre_save_snippet' ), 10, 2 );
		// Handling block editor snippets.
		add_filter( 'wpcode_block_editor_redirect_url', array( $this, 'block_editor_redirect_url' ), 10, 3 );
		add_filter( 'wpcode_snippet_output_blocks', array( $this, 'block_snippet_output' ), 10, 2 );
		// Handle force deactivation in testing mode.
		add_filter( 'wpcode_force_deactivate_snippet', array( $this, 'handle_force_deactivate' ), 10, 2 );
		// Ajax endpoint for loading the changes made in testing mode.
		add_action( 'wp_ajax_wpcode_testing_mode_get_changes', array( $this, 'load_testing_mode_changes' ) );

		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
		add_filter( 'body_class', array( $this, 'add_frontend_body_class' ) );

		add_action( 'wpcode_admin_bar_info_top', array( $this, 'add_testing_mode_info' ) );

		add_action( 'admin_init', array( $this, 'maybe_capture_header_footer_changes' ), 9 );

		add_filter( 'pre_option_ihaf_insert_header', array( $this, 'maybe_filter_header_footer' ), 10, 2 );
		add_filter( 'pre_option_ihaf_insert_footer', array( $this, 'maybe_filter_header_footer' ), 10, 2 );
		add_filter( 'pre_option_ihaf_insert_body', array( $this, 'maybe_filter_header_footer' ), 10, 2 );

		// Let's short-circuit the update post meta for the Page Scripts meta if testing mode is enabled.
		add_filter( 'update_post_metadata', array( $this, 'maybe_capture_page_scripts' ), 10, 5 );
		add_filter( 'get_post_metadata', array( $this, 'maybe_replace_page_scripts' ), 10, 5 );

		// Let's add an indicator to the Page Scripts metabox that the testing mode is enabled.
		add_action( 'wpcode_metabox_admin_tabs', array( $this, 'add_testing_mode_indicator' ) );

		add_action( 'wpcode_admin_notices', array( $this, 'display_testing_mode_notice' ) );

		add_action( 'admin_init', array( $this, 'maybe_publish_snippet_changes' ) );
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return WPCode_Testing_Mode
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WPCode_Testing_Mode();
		}

		return self::$instance;
	}

	/**
	 * Prepare data and enable testing mode.
	 *
	 * @return void
	 */
	public function maybe_toggle_testing_mode() {
		// Check if the user has the capability to enable testing mode.
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			return;
		}

		// Check if the nonce is valid.
		if ( ! isset( $_GET['wpcode_testing_mode_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['wpcode_testing_mode_nonce'] ), 'wpcode_testing_mode_nonce' ) ) {
			return;
		}

		$redirect_url = remove_query_arg(
			array(
				'wpcode_testing_mode_nonce',
				'wpcode_testing_mode',
			)
		);

		// Check if the testing mode is already enabled.
		if ( $this->testing_mode_enabled() ) {
			// Let's see if they want to save the changes or not.
			if ( isset( $_GET['save_changes'] ) && 'true' === $_GET['save_changes'] ) {
				// Remove filters now to make sure regular saving works now.
				remove_filter( 'wpcode_load_snippet', array( $this, 'load_snippet_from_testing_data' ) );
				remove_filter( 'wpcode_pre_save_snippet', array( $this, 'pre_save_snippet' ) );
				// Let's loop through all the snippets and update them.
				$data = $this->get_data();
				foreach ( $data['snippets'] as $location => $snippets ) {
					foreach ( $snippets as $key => $snippet ) {
						if ( ! array_key_exists( 'updated', $snippet ) ) {
							continue;
						}
						// Let's update the snippet.
						$snippet = new WPCode_Snippet( $snippet );
						$snippet->save();
					}
				}
				if ( ! empty( $data['block_ids'] ) ) {
					foreach ( $data['block_ids'] as $snippet_id => $block_post_id ) {
						$current_block_post_id = get_post_meta( $snippet_id, '_wpcode_block_editor_id', true );
						if ( ! empty( $current_block_post_id ) ) {
							// Let's update the current block post with the content from the $block_post_id in the testing mode and then delete the testing mode post.
							$block_post = get_post( $block_post_id );
							if ( ! is_null( $block_post ) ) {
								wp_update_post(
									array(
										'ID'           => $current_block_post_id,
										'post_content' => $block_post->post_content,
									)
								);
								wp_delete_post( $block_post_id, true );
							}
						}
					}
				}

				// Let's update the global header & footer.
				if ( ! empty( $data['global'] ) ) {
					$this->remove_global_filters();
					foreach ( $data['global'] as $key => $value ) {
						update_option( 'ihaf_insert_' . $key, $value );
					}
				}

				// Let's save the page scripts.
				if ( ! empty( $data['page_scripts'] ) ) {
					$this->data['enabled'] = false;
					$this->remove_page_scripts_filters();
					foreach ( $data['page_scripts'] as $post_id => $meta ) {
						do_action( 'wpcode_before_update_page_scripts', $post_id );
						foreach ( $meta as $key => $value ) {
							update_post_meta( $post_id, $key, $value );
						}
						do_action( 'wpcode_after_update_page_scripts', $post_id, $meta );
					}
				}
			} else {
				// Let's see if we have any new snippets we need to discard.
				$data = $this->get_data();
				foreach ( $data['new_snippets'] as $snippet_id ) {
					wp_delete_post( intval( $snippet_id ), true );
				}
				foreach ( $data['block_ids'] as $post_id ) {
					wp_delete_post( intval( $post_id ), true );
				}
			}

			update_option( $this->option_name, array( 'enabled' => false ) );
		} else {
			$testing_mode_data = array(
				'enabled'  => true,
				'snippets' => get_option( 'wpcode_snippets', array() ),
			);

			// Let's prepare the data for the testing mode.
			update_option( $this->option_name, $testing_mode_data );

			$redirect_url = add_query_arg( 'wpcode_testing_mode', 'true', $redirect_url );
		}

		// Redirect to the same page.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get the testing mode data.
	 *
	 * @return array
	 */
	public function get_data() {
		if ( ! isset( $this->data ) ) {
			$data       = (array) get_option( $this->option_name, array() );
			$this->data = wp_parse_args(
				$data,
				array(
					'enabled'      => false,
					'snippets'     => array(),
					'page_scripts' => array(),
					'global'       => array(),
					'new_snippets' => array(),
					'block_ids'    => array(),
				)
			);
		}

		return $this->data;
	}

	/**
	 * Check if the testing mode is enabled.
	 *
	 * @return bool
	 */
	public function testing_mode_enabled() {
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			return false;
		}
		$data = $this->get_data();

		return $data['enabled'];
	}

	/**
	 * If testing mode is enabled check which snippets we should load from the testing mode option.
	 *
	 * @param false $value The value to return.
	 *
	 * @return array|false
	 */
	public function maybe_filter_snippets( $value ) {
		if ( ! $this->testing_mode_enabled() ) {
			return $value;
		}

		// Let's short-circuit the option used for snippet cache with our own values from the testing mode option.
		$data = $this->get_data();

		// Let's loop through all snippets and process them.
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $snippet ) {
				if ( empty( $data['snippets'][ $location ][ $key ]['post_data'] ) ) {
					$data['snippets'][ $location ][ $key ]['post_data'] = get_post( $snippet['id'] );
				} else {
					$data['snippets'][ $location ][ $key ]['post_data'] = (object) $snippet['post_data'];
				}
				if ( is_null( $data['snippets'][ $location ][ $key ]['post_data'] ) ) {
					continue;
				}
				// If post status is not publish let's not load this snippet but keep it in our data to track changes.
				if ( 'publish' !== $data['snippets'][ $location ][ $key ]['post_data']->post_status || ! $data['snippets'][ $location ][ $key ]['auto_insert'] ) {
					unset( $data['snippets'][ $location ][ $key ] );
					continue;
				}

				// Unslash the code for proper output.
				$data['snippets'][ $location ][ $key ]['code'] = wp_unslash( $snippet['code'] );
			}
		}

		return $data['snippets'];
	}

	/**
	 * Handle adding a new snippet while in testing mode.
	 *
	 * @param WPCode_Snippet $snippet The snippet instance.
	 *
	 * @return void
	 */
	public function add_new_snippet( $snippet ) {
		// Let's keep track of this new snippet id in our testing data.
		// We save it as usual and track the id so that we can delete it if the user discards the changes.
		$id = $snippet->save();
		if ( $id ) {
			$this->track_new_snippet_id( $id );
		}
	}

	/**
	 * Track the id of a new snippet in the testing mode data.
	 *
	 * @param int $id The id of the new snippet.
	 *
	 * @return void
	 */
	public function track_new_snippet_id( $id ) {
		$data = $this->get_data();
		if ( ! isset( $data['new_snippets'] ) ) {
			$data['new_snippets'] = array();
		}
		$data['new_snippets'][]     = $id;
		$this->data['new_snippets'] = $data['new_snippets'];
		update_option( $this->option_name, $this->data );
	}

	/**
	 * Update a snippet in the testing mode data.
	 *
	 * @param WPCode_Snippet $snippet The snippet we are updating in the testing data.
	 *
	 * @return void
	 */
	public function update_snippet( $snippet ) {
		$data = $this->get_data();
		// Let's loop through all the snippets to see if we find our snippet and update it + move it to the right location.
		// If we can't find it we need to add it to the relevant location.
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $data_snippet ) {
				if ( $data_snippet['id'] === $snippet->get_id() ) {
					unset( $data['snippets'][ $location ][ $key ] );
				}
			}
		}
		$snippet_location = $snippet->auto_insert ? $snippet->get_location() : 'shortcode';
		if ( ! isset( $data['snippets'][ $snippet_location ] ) ) {
			$data['snippets'][ $snippet_location ] = array();
		}

		$data['snippets'][ $snippet_location ][] = $this->get_data_for_snippet( $snippet );

		update_option( $this->option_name, $data );
	}

	/**
	 * Grab the data for a snippet from the test mode data by id.
	 *
	 * @param int $id The id of the snippet.
	 *
	 * @return WPCode_Snippet|false
	 */
	public function get_snippet_by_id( $id ) {
		// Let's see if the snippet is in the data we have.
		$data = $this->get_data();
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $snippet ) {
				if ( $snippet['id'] === $id ) {
					$snippet['code'] = wp_unslash( $snippet['code'] );
					if ( empty( $snippet['post_data'] ) ) {
						$snippet['post_data'] = get_post( $id );
					}

					return new WPCode_Snippet( $snippet );
				}
			}
		}

		// If we reached this far the snippet is not in the data we have so let's load it regularly.
		$snippet_post = get_post( $id );
		if ( ! is_null( $snippet_post ) && 'wpcode' === $snippet_post->post_type ) {
			return new WPCode_Snippet( $snippet_post );
		}

		return false;
	}

	/**
	 * Check if the snippet has been updated in testing mode.
	 *
	 * @param int $snippet_id The snippet id.
	 *
	 * @return bool
	 */
	public function is_snippet_changed( $snippet_id ) {
		$data = $this->get_data();
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $snippet ) {
				if ( $snippet['id'] === $snippet_id ) {
					if ( isset( $snippet['updated'] ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the data to store in the test mode option for a specific snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return array
	 */
	public function get_data_for_snippet( $snippet ) {
		$data              = $snippet->get_data_for_caching();
		$data['post_data'] = get_post( $snippet->get_id() );
		if ( $snippet->active ) {
			$data['post_data']->post_status = 'publish';
		} else {
			$data['post_data']->post_status = 'draft';
		}

		$data['updated']          = time();
		$data['custom_shortcode'] = $snippet->get_custom_shortcode();
		$data['active']           = $snippet->active;

		return $data;
	}

	/**
	 * When using the wpcode_load_snippet filter, check if we have the snippet data in our testing mode option.
	 * If we do, let's use that.
	 *
	 * @param int|WP_Post|array $snippet_id The snippet id, or post object or array of properties.
	 *
	 * @return int|mixed
	 * @see WPCode_Snippet::__construct()
	 *
	 */
	public function load_snippet_from_testing_data( $snippet_id ) {
		if ( ! $this->testing_mode_enabled() ) {
			return $snippet_id;
		}
		if ( ! is_array( $snippet_id ) ) {
			if ( $snippet_id instanceof WP_Post ) {
				$snippet_id = $snippet_id->ID;
			}
			$data = $this->get_data();
			foreach ( $data['snippets'] as $location => $snippets ) {
				foreach ( $snippets as $key => $snippet ) {
					if ( $snippet['id'] === $snippet_id ) {
						$snippet['code'] = wp_unslash( $snippet['code'] );
						if ( empty( $snippet['post_data'] ) ) {
							$snippet['post_data'] = get_post( $snippet_id );
						}

						return $snippet;
					}
				}
			}
		}

		return $snippet_id;
	}

	/**
	 * Add the links
	 *
	 * @param array $data The data to pass to the script.
	 *
	 * @return array
	 */
	public function add_enable_link_to_script( $data ) {

		$url              = add_query_arg( 'wpcode_testing_mode_nonce', wp_create_nonce( 'wpcode_testing_mode_nonce' ) );
		$save_changes_url = add_query_arg( 'save_changes', 'true', $url );

		$publish_changes_url = '';
		if ( isset( $_GET['snippet_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$publish_changes_url = wp_nonce_url(
				add_query_arg(
					array(
						'snippet_id' => absint( $_GET['snippet_id'] ),
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						'action'     => 'wpcode_testing_mode_publish_changes',
					)
				),
				'wpcode_testing_mode_publish_changes'
			);
			$publish_changes_url = str_replace( '&amp;', '&', $publish_changes_url );
		}

		$data['testing_mode'] = array(
			'toggle_link'           => $url,
			'save_changes_url'      => $save_changes_url,
			'disable_confirm_title' => __( 'Disable testing mode?', 'wpcode-premium' ),
			'disable_confirm_text'  => __( 'Are you sure you want to disable testing mode? You can either publish the changes you made in testing mode to all site visitors or discard all changes.', 'wpcode-premium' ),
			'updated_snippets'      => __( 'Updated Snippets:', 'wpcode-premium' ),
			'no_changes'            => __( 'No unsaved changes currently in testing mode.', 'wpcode-premium' ),
			'save_changes'          => __( 'Save & Publish Changes', 'wpcode-premium' ),
			'cancel'                => __( 'Cancel', 'wpcode-premium' ),
			'discard_changes'       => __( 'Discard all changes', 'wpcode-premium' ),
			'enabling'              => __( 'Enabling Testing Mode', 'wpcode-premium' ),
			'disabling'             => __( 'Disabling Testing Mode', 'wpcode-premium' ),
			'info_title'            => __( 'Testing Mode Enabled', 'wpcode-premium' ),
			'info_text'             => __( 'Testing Mode is activated. Changes to WPCode Snippets, Header & Footer or Page Scripts are only visible to users with the capability to manage WPCode Snippets. To exit Testing Mode, use the toggle located at the top right. Before deactivation, you will have the option to publish or discard the changes.', 'wpcode-premium' ),
			'info_ok'               => __( 'OK', 'wpcode-premium' ),
			'enabled'               => $this->testing_mode_enabled(),
			'checking_changes'      => __( 'Loading testing mode changes...', 'wpcode-premium' ),
			'disable_testing_mode'  => __( 'Disable Testing Mode', 'wpcode-premium' ),
			'updated_global'        => __( 'Global Header & Footer changes:', 'wpcode-premium' ),
			'updated_page_scripts'  => __( 'Page Scripts changes:', 'wpcode-premium' ),
			'publish_changes_title' => __( 'Are you sure?', 'wpcode-premium' ),
			'publish_changes_text'  => __( 'By clicking "Confirm" all the changes made in testing mode to this snippet will be published for all users. Other changes made in testing mode will not be affected.', 'wpcode-premium' ),
			'publish_changes_url'   => $publish_changes_url,
		);

		return $data;
	}

	/**
	 * When updating the status of a snippet in testing mode, handle that separately.
	 *
	 * @return void
	 */
	public function maybe_handle_update_status() {
		if ( ! current_user_can( 'wpcode_activate_snippets' ) || ! $this->testing_mode_enabled() ) {
			return;
		}
		check_ajax_referer( 'wpcode_admin' );

		if ( empty( $_POST['snippet_id'] ) ) {
			return;
		}
		$snippet_id = absint( $_POST['snippet_id'] );
		$active     = isset( $_POST['active'] ) && 'true' === $_POST['active'];

		$snippet         = $this->get_snippet_by_id( $snippet_id );
		$snippet->active = $active;

		$this->update_snippet( $snippet );
		exit;
	}

	/**
	 * Track the id of a duplicated snippet in the testing mode data.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function track_duplicated_snippet( $snippet ) {
		if ( ! wpcode_testing_mode_enabled() ) {
			return;
		}
		$this->track_new_snippet_id( $snippet->get_id() );
	}

	/**
	 * Add the custom shortcodes in the testing mode data to the list of shortcodes.
	 *
	 * @param mixed $value The value to return.
	 *
	 * @return mixed
	 */
	public function add_custom_shortcodes( $value ) {
		if ( ! wpcode_testing_mode_enabled() ) {
			return $value;
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$data = $this->get_data();
		// Loop through all snippets and if we have the custom_shortcode attribute for any of them make sure it's present in the returned value.
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $snippet ) {
				if ( ! empty( $snippet['custom_shortcode'] ) ) {
					$value[ $snippet['custom_shortcode'] ] = $snippet['id'];
				}
			}
		}

		return $value;
	}

	/**
	 * Hide the "trash" bulk action in testing mode.
	 *
	 * @param array $actions The bulk actions.
	 *
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		if ( ! $this->testing_mode_enabled() ) {
			return $actions;
		}

		if ( isset( $actions['trash'] ) ) {
			unset( $actions['trash'] );
		}

		return $actions;
	}

	/**
	 * When saving a snippet in testing mode, let's update the testing mode data.
	 *
	 * @param false          $action Returning anything other than false will short-circuit the snippet saving.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return false|int
	 */
	public function pre_save_snippet( $action, $snippet ) {
		if ( ! $this->testing_mode_enabled() || ! isset( $snippet->id ) || 0 === $snippet->id ) {
			return $action;
		}

		$this->update_snippet( $snippet );

		return $snippet->get_id();
	}

	/**
	 * Handle the redirect url for block editor snippets.
	 *
	 * @param string $url The URL where we'll redirect to edit the "Block Editor" post attached to the snippet.
	 * @param int    $snippet_id The id of the snippet we are editing.
	 * @param int    $block_editor_id The id of the post we are using for the block editor.
	 *
	 * @return string
	 */
	public function block_editor_redirect_url( $url, $snippet_id, $block_editor_id ) {
		if ( ! $this->testing_mode_enabled() ) {
			return $url;
		}

		$data = $this->get_data();
		if ( isset( $data['block_ids'][ $snippet_id ] ) ) {
			return get_edit_post_link( (int) $data['block_ids'][ $snippet_id ], 'edit' );
		}

		// Let's create a post for the block editor which is a clone of the $block_editor_id post and store it in the testing mode data for later retrieval.
		$block_editor_post = get_post( (int) $block_editor_id );
		if ( ! is_null( $block_editor_post ) ) {
			$block_editor_post->post_status = 'publish';
			$block_editor_post->post_title  = $block_editor_post->post_title . ' (Testing Mode)';
			$block_editor_post->post_name   = $block_editor_post->post_name . '-testing-mode';
			unset( $block_editor_post->ID );
			$new_id = wp_insert_post( (array) $block_editor_post );
			update_post_meta( $new_id, '_wpcode_snippet_id', $snippet_id );
			$data['block_ids'][ $snippet_id ] = $new_id;
			update_option( $this->option_name, $data );

			return get_edit_post_link( $new_id, 'edit' );
		}
	}


	/**
	 * Filter the output in testing mode for block editor snippets.
	 *
	 * @param string         $content The content for output.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function block_snippet_output( $content, $snippet ) {
		if ( $this->testing_mode_enabled() ) {
			$data = $this->get_data();
			if ( isset( $data['block_ids'][ $snippet->get_id() ] ) ) {
				$block_editor_post = get_post( (int) $data['block_ids'][ $snippet->get_id() ] );
				if ( ! is_null( $block_editor_post ) ) {
					return $block_editor_post->post_content;
				}
			}
		}

		return $content;
	}

	/**
	 * When a snippet throws an error for which we need to force deactivate it, let's handle that in testing mode.
	 *
	 * @param bool           $force_deactivated If the snippet was already deactivated. Returning anything other than false will stop the default deactivation.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return bool
	 */
	public function handle_force_deactivate( $force_deactivated, $snippet ) {
		if ( $this->testing_mode_enabled() ) {
			$data = $this->get_data();
			// Let's disable the snippet only in testing mode.
			if ( isset( $data['snippets'][ $snippet->get_location() ] ) ) {
				foreach ( $data['snippets'][ $snippet->get_location() ] as $key => $data_snippet ) {
					if ( $data_snippet['id'] === $snippet->get_id() ) {
						unset( $data['snippets'][ $snippet->get_location() ][ $key ] );
						$snippet->active                                = false;
						$data['snippets'][ $snippet->get_location() ][] = $this->get_data_for_snippet( $snippet );
						update_option( $this->option_name, $data );
						$force_deactivated = true;
						break;
					}
				}
			}
		}

		return $force_deactivated;
	}

	/**
	 * Ajax endpoint for loading the changes made in testing mode.
	 *
	 * @return void
	 */
	public function load_testing_mode_changes() {
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_send_json_error();
		}
		check_ajax_referer( 'wpcode_admin' );

		$data = $this->get_data();

		// Let's loop through all the snippets and see which ones have the updated property.
		$snippets = array();
		foreach ( $data['snippets'] as $location => $location_snippets ) {
			foreach ( $location_snippets as $key => $snippet ) {
				if ( ! array_key_exists( 'updated', $snippet ) ) {
					continue;
				}
				$snippets[] = array(
					'id'        => $snippet['id'],
					'edit_link' => add_query_arg( 'snippet_id', $snippet['id'], admin_url( 'admin.php?page=wpcode-snippet-manager' ) ),
					'title'     => $snippet['title'],
				);
			}
		}

		$global = array();
		// Let's see if any global data was updated.
		if ( ! empty( $data['global'] ) ) {
			$this->remove_global_filters();

			$header_footer_url = admin_url( 'admin.php?page=wpcode-headers-footers' );
			$labels            = array(
				'header' => __( 'Header', 'wpcode-premium' ),
				'footer' => __( 'Footer', 'wpcode-premium' ),
				'body'   => __( 'Body', 'wpcode-premium' ),
			);

			// Let's go through all the global data and see if we have any changes.
			foreach ( $data['global'] as $key => $value ) {
				$original_value = get_option( 'ihaf_insert_' . $key );
				if ( $original_value !== $value ) {
					$global[] = array(
						'edit_link' => $header_footer_url . '#wpcode-global-body',
						'title'     => $labels[ $key ],
					);
				}
			}
		}

		$page_scripts = array();
		if ( ! empty( $data['page_scripts'] ) ) {
			$this->remove_page_scripts_filters();

			$labels = array(
				'_wpcode_header_scripts' => __( 'Header', 'wpcode-premium' ),
				'_wpcode_footer_scripts' => __( 'Footer', 'wpcode-premium' ),
				'_wpcode_body_scripts'   => __( 'Body', 'wpcode-premium' ),
				'_wpcode_page_snippets'  => __( 'Page Snippets', 'wpcode-premium' ),
			);

			foreach ( $data['page_scripts'] as $post_id => $meta ) {
				foreach ( $meta as $key => $value ) {
					$original_value = get_post_meta( $post_id, $key, true );
					if ( $original_value !== $value ) {
						$title = get_the_title( $post_id );
						if ( empty( $title ) ) {
							$title = $post_id;
						}
						$page_scripts[] = array(
							'edit_link' => get_edit_post_link( $post_id, 'edit' ),
							'title'     => '"' . $title . '" - ' . $labels[ $key ],
						);
					}
				}
			}
		}

		wp_send_json_success(
			array(
				'snippets'     => $snippets,
				'global'       => $global,
				'page_scripts' => $page_scripts,
			)
		);

	}

	/**
	 * Add a body class when testing mode is enabled.
	 *
	 * @param string $classes The body classes.
	 *
	 * @return string
	 */
	public function add_body_class( $classes ) {
		if ( $this->testing_mode_enabled() ) {
			$classes .= ' wpcode-testing-mode';
		}

		return $classes;
	}

	/**
	 * Add a body class when testing mode is enabled.
	 *
	 * @param string[] $classes The body classes.
	 *
	 * @return string[]
	 */
	public function add_frontend_body_class( $classes ) {
		if ( $this->testing_mode_enabled() ) {
			$classes[] = 'wpcode-testing-mode';
		}

		return $classes;
	}

	/**
	 * Add the testing mode info to the WPCode admin bar menu.
	 *
	 * @param WP_Admin_Bar $admin_bar The admin bar object.
	 *
	 * @return void
	 */
	public function add_testing_mode_info( $admin_bar ) {
		if ( ! $this->testing_mode_enabled() ) {
			return;
		}
		$admin_bar->add_menu(
			array(
				'id'     => 'wpcode-test-mode-description',
				'parent' => 'wpcode-admin-bar-info',
				'title'  => esc_html__( 'Testing Mode Enabled', 'insert-headers-and-footers' ),
				'meta'   => array(
					'class' => 'wpcode-admin-bar-info-submenu wpcode-admin-bar-description',
				),
				'href'   => add_query_arg( 'wpcode_testing_mode', 'true', admin_url( 'admin.php?page=wpcode' ) ),
			)
		);
	}

	public function update_global_data( $values ) {
		$data = $this->get_data();
		if ( ! isset( $data['global'] ) ) {
			$data['global'] = array();
		}
		// $values is an array with 3 possible keys, header, footer, body, let's replace the data we have if any.
		foreach ( $values as $key => $value ) {
			$data['global'][ $key ] = $value;
		}

		update_option( $this->option_name, $data );
	}

	/**
	 * Maybe capture the header and footer changes.
	 *
	 * @return void
	 */
	public function maybe_capture_header_footer_changes() {
		if ( ! $this->testing_mode_enabled() ) {
			return;
		}
		// Let's see if we have a request for saving the header & footer data.
		if ( ! isset( $_POST['insert-headers-and-footers_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['insert-headers-and-footers_nonce'] ), 'insert-headers-and-footers' ) ) {
			return;
		}
		if ( ! current_user_can( 'unfiltered_html', 'wpcode-editor' ) ) {
			// Let's run a capabilities check just in case.
			return;
		}

		if ( isset( $_REQUEST['ihaf_insert_header'] ) && isset( $_REQUEST['ihaf_insert_footer'] ) ) {
			$updated_values = array(
				'header' => $_REQUEST['ihaf_insert_header'],
				'footer' => $_REQUEST['ihaf_insert_footer'],
			);
			if ( isset( $_REQUEST['ihaf_insert_body'] ) ) {
				$updated_values['body'] = $_REQUEST['ihaf_insert_body'];
			}
			$this->update_global_data( $updated_values );

			// If we got this far let's redirect back to the header and footer page.
			wp_safe_redirect( admin_url( 'admin.php?page=wpcode-headers-footers' ) );
			exit;
		}
	}

	/**
	 * Maybe filter the header and footer options.
	 *
	 * @param mixed  $value The value of the option.
	 * @param string $option The option name.
	 *
	 * @return mixed
	 */
	public function maybe_filter_header_footer( $value, $option ) {
		if ( ! $this->testing_mode_enabled() ) {
			return $value;
		}
		// Let's extract the name of our option from the option name.
		$option_key = str_replace( 'ihaf_insert_', '', $option );
		$data       = $this->get_data();
		if ( isset( $data['global'][ $option_key ] ) ) {
			return $data['global'][ $option_key ];
		}

		return $value;
	}

	/**
	 * Remove the global filters for the header and footer.
	 *
	 * @return void
	 */
	public function remove_global_filters() {
		// Let's remove the filter for the header and footer so that we can get the original values.
		remove_filter( 'pre_option_ihaf_insert_header', array( $this, 'maybe_filter_header_footer' ) );
		remove_filter( 'pre_option_ihaf_insert_footer', array( $this, 'maybe_filter_header_footer' ) );
		remove_filter( 'pre_option_ihaf_insert_body', array( $this, 'maybe_filter_header_footer' ) );
	}

	/**
	 * Remove the page scripts filters so we can process changes.
	 *
	 * @return void
	 */
	public function remove_page_scripts_filters() {
		remove_filter( 'update_post_metadata', array( $this, 'maybe_capture_page_scripts' ) );
		remove_filter( 'get_post_metadata', array( $this, 'maybe_replace_page_scripts' ) );
	}

	/**
	 * @param null|bool $check Whether to allow updating metadata for the given type.
	 * @param int       $post_id ID of the post.
	 * @param string    $meta_key Metadata key.
	 * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed     $prev_value Optional. Previous value to check before updating.
	 * If specified, only update existing metadata entries with
	 * this value. Otherwise, update all entries.
	 *
	 * @return null|bool
	 */
	public function maybe_capture_page_scripts( $check, $post_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! in_array( $meta_key, $this->page_scripts_keys, true ) ) {
			return $check;
		}
		if ( ! $this->testing_mode_enabled() ) {
			return $check;
		}
		if ( $prev_value === $meta_value ) {
			return false;
		}
		$data = $this->get_data();
		if ( ! isset( $data['page_scripts'] ) ) {
			$data['page_scripts'] = array();
		}
		if ( ! isset( $data['page_scripts'][ $post_id ] ) ) {
			$data['page_scripts'][ $post_id ] = array();
		}
		$data['page_scripts'][ $post_id ][ $meta_key ] = $meta_value;
		update_option( $this->option_name, $data );
		unset( $this->data );

		return true;
	}

	/**
	 * @param mixed  $value The value to return, either a single metadata value or an array
	 * of values depending on the value of `$single`. Default null.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single Whether to return only the first value of the specified `$meta_key`.
	 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 * or any other object type with an associated meta table.
	 *
	 * @return mixed
	 */
	public function maybe_replace_page_scripts( $value, $object_id, $meta_key, $single, $meta_type = '' ) {
		if ( ! in_array( $meta_key, $this->page_scripts_keys, true ) ) {
			return $value;
		}
		if ( ! $this->testing_mode_enabled() ) {
			return $value;
		}
		$data = $this->get_data();
		if ( ! isset( $data['page_scripts'] ) ) {
			return $value;
		}
		if ( ! isset( $data['page_scripts'][ $object_id ] ) ) {
			return $value;
		}
		if ( ! isset( $data['page_scripts'][ $object_id ][ $meta_key ] ) ) {
			return $value;
		}

		return array( $data['page_scripts'][ $object_id ][ $meta_key ] );

	}

	/**
	 * Highlight that we are in testing mode in the page scripts metabox.
	 *
	 * @return void
	 */
	public function add_testing_mode_indicator() {
		if ( ! $this->testing_mode_enabled() ) {
			return;
		}
		$url = add_query_arg( 'wpcode_testing_mode', 'true', admin_url( 'admin.php?page=wpcode' ) );

		?>
		<li class="wpcode-page-scripts-testing-mode-indicator">
			<a href="<?php echo esc_url( $url ); ?>" target="_blank"><strong><?php esc_html_e( 'Testing Mode Enabled', 'wpcode-premium' ); ?></strong></a>
		</li>
		<?php
	}

	/**
	 * Display a notice on the conversion pixels page that changes are published immediately even in testing mode.
	 *
	 * @return void
	 */
	public function display_testing_mode_notice() {
		if ( ! $this->testing_mode_enabled() ) {
			return;
		}
		// If we're not on the conversion pixels page skip.
		if ( ! isset( $_GET['page'] ) || 'wpcode-pixel' !== $_GET['page'] ) {
			return;
		}
		$addon_data = wpcode()->addons->get_addon( 'wpcode-pixel' );
		// Show only if the addon is active.
		if ( empty( $addon_data->isActive ) ) {
			return;
		}

		?>
		<div class="wpcode-alert wpcode-alert-warning">
			<p><?php esc_html_e( 'Please note that even though Testing Mode is enabled, changes made to conversion pixels will be published immediately.', 'wpcode-premium' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Callback that handles publishing individual snippet changes from the testing mode data to production.
	 *
	 * @return void
	 */
	public function maybe_publish_snippet_changes() {
		// Let's check the nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode_testing_mode_publish_changes' ) ) {
			return;
		}
		if ( ! $this->testing_mode_enabled() || ! isset( $_GET['action'] ) || 'wpcode_testing_mode_publish_changes' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			return;
		}
		// Let's check the snippet id.
		if ( ! isset( $_GET['snippet_id'] ) || ! current_user_can( 'wpcode_edit_snippets' ) ) {
			return;
		}
		$snippet_id = absint( $_GET['snippet_id'] );
		// Let's load the snippet data from the data object and if we save successfully unset it from the testing mode data.
		$data = $this->get_data();
		foreach ( $data['snippets'] as $location => $snippets ) {
			foreach ( $snippets as $key => $snippet ) {
				if ( $snippet['id'] === $snippet_id ) {
					// Remove filters now to make sure regular saving works now.
					remove_filter( 'wpcode_load_snippet', array( $this, 'load_snippet_from_testing_data' ) );
					remove_filter( 'wpcode_pre_save_snippet', array( $this, 'pre_save_snippet' ) );

					$snippet_object = new WPCode_Snippet( $snippet );
					$snippet_object->save();
					if ( isset( $data['snippets'][ $location ][ $key ]['updated'] ) ) {
						unset( $data['snippets'][ $location ][ $key ]['updated'] );
					}
					update_option( $this->option_name, $data );
					wp_safe_redirect(
						add_query_arg(
							array(
								'snippet_id' => $snippet_id,
								'message'    => 1,
							),
							admin_url( 'admin.php?page=wpcode-snippet-manager' )
						)
					);
					exit;
				}
			}
		}
	}
}

WPCode_Testing_Mode::get_instance();
