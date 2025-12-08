<?php
/**
 * This class handles the logic for snippet revisions.
 * Since version 2.1.6 we also support revisions for Header and Footer code.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Revisions.
 */
class WPCode_Revisions {
	/**
	 * All snippet data before the update.
	 *
	 * @var array
	 */
	protected $previous_snippet_data;

	/**
	 * A record of the Header & Footer data for a revision.
	 *
	 * @var array
	 */
	protected $header_footer_data;

	/**
	 * A record of the previous Header & Footer data for comparison
	 *
	 * @var array
	 */
	protected $previous_header_footer_data;

	/**
	 * Keep a record of the previous page scripts data, so we can compare for a revision.
	 *
	 * @var array
	 */
	protected $previous_page_scripts_data;

	/**
	 * The remote id, if any, for the revision we are about to create.
	 *
	 * @var string
	 */
	protected $remote_id;

	/**
	 * Constructor for initiating the revisions actions.
	 */
	public function __construct() {
		add_action( 'pre_post_update', array( $this, 'maybe_capture_snippet_data' ) );
		add_action( 'wpcode_snippet_after_update', array( $this, 'maybe_create_revision' ), 10, 2 );

		add_action( 'post_updated', array( $this, 'maybe_create_blocks_revision' ), 10, 3 );

		add_filter( 'pre_update_option_ihaf_insert_header', array( $this, 'maybe_create_hf_revision' ), 10, 3 );
		add_filter( 'pre_update_option_ihaf_insert_footer', array( $this, 'maybe_create_hf_revision' ), 10, 3 );
		add_filter( 'pre_update_option_ihaf_insert_body', array( $this, 'maybe_create_hf_revision' ), 10, 3 );

		add_action( 'wpcode_before_update_page_scripts', array( $this, 'capture_page_scripts_data' ) );
		add_action( 'wpcode_after_update_page_scripts', array( $this, 'maybe_create_page_scripts_revision' ), 10, 2 );
	}

	/**
	 * Capture the data of the snippet before it is updated to compare in order
	 * to decide if a revision should be created.
	 *
	 * @param int $post_ID The id of the post.
	 *
	 * @return void
	 */
	public function maybe_capture_snippet_data( $post_ID ) {
		if ( 'wpcode' !== get_post_type( $post_ID ) ) {
			return;
		}

		$this->capture_snippet_data( $post_ID );
	}

	/**
	 * Create a new revision from the differences between the old version of the snippet and the new values.
	 *
	 * @param int            $snippet_id The id of the snippet.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_create_revision( $snippet_id, $snippet ) {

		$data_to_compare = $this->get_all_snippet_revision_data( $snippet, true );
		$updated_data    = array();

		// Check if something changed in the revision data.
		foreach ( $data_to_compare as $key => $value ) {
			if ( isset( $this->previous_snippet_data[ $key ] ) && $value === $this->previous_snippet_data[ $key ] ) {
				continue;
			}
			$updated_data[ $key ] = $value;
		}

		// If we have updated data, save the whole snippet data so we can easily compare between revisions.
		if ( ! empty( $updated_data ) ) {
			$this->create_revision( $snippet_id, $data_to_compare );
		}
	}

	/**
	 * Get all snippet data for storing as a revision.
	 *
	 * @param WPCode_Snippet $snippet The snippet to grab data for.
	 * @param bool           $unslash Whether to unslash the code.
	 *
	 * @return array
	 */
	public function get_all_snippet_revision_data( $snippet, $unslash = false ) {
		$all_data = $snippet->get_data_for_caching();

		if ( isset( $snippet->tags ) ) {
			// Let's grab the actual slug here to avoid confusion.
			unset( $snippet->tags );
		}
		$all_data['note']             = $snippet->get_note();
		$all_data['tags']             = $snippet->get_tags();
		$all_data['custom_shortcode'] = $snippet->get_custom_shortcode();
		$all_data['device_type']      = $snippet->get_device_type();
		$all_data['schedule']         = $snippet->get_schedule();

		if ( $unslash ) {
			$all_data['code'] = wp_unslash( $all_data['code'] );
		}

		return $all_data;
	}

	/**
	 * Before a snippet is updated we capture all the data to compare with the updated values.
	 *
	 * @param int $snippet_id The snippet id.
	 *
	 * @return void
	 */
	public function capture_snippet_data( $snippet_id ) {

		$snippet = new WPCode_Snippet( $snippet_id );

		$this->previous_snippet_data = $this->get_all_snippet_revision_data( $snippet );
	}

	/**
	 * Create a revision from the data passed.
	 *
	 * @param int   $snippet_id The snippet to associate the revision with.
	 * @param array $updated_data The new revision data.
	 *
	 * @return void
	 */
	public function create_revision( $snippet_id, $updated_data ) {
		if ( empty( $updated_data ) ) {
			return;
		}
		global $wpdb;

		$remote_id = '';
		if ( isset( $this->remote_id ) ) {
			$remote_id = $this->remote_id;
			$this->clear_remote_id();
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'wpcode_revisions',
			array(
				'snippet_id'    => $snippet_id,
				'revision_data' => wp_json_encode( $updated_data ),
				'author_id'     => get_current_user_id(),
				'created'       => current_time( 'mysql', true ),
				'remote_id'     => $remote_id,
			),
			array(
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);

		$cache_key = 'wpcode_revisions_for_snippet_' . $snippet_id;
		wp_cache_delete( $cache_key, 'wpcode' );
	}

	/**
	 * Get a list of available revisions.
	 *
	 * @param int $snippet_id The snippet id.
	 *
	 * @return array
	 */
	public function get_snippet_revisions( $snippet_id ) {
		global $wpdb;

		$cache_key = 'wpcode_revisions_for_snippet_' . $snippet_id;
		$results   = wp_cache_get( 'wpcode_revisions_for_snippet_' . $snippet_id, 'wpcode' );

		if ( false === $results ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wpcode_revisions WHERE snippet_id=%d ORDER BY created DESC",
					$snippet_id
				)
			);

			wp_cache_set( $cache_key, $results, 'wpcode' );
		}

		if ( ! empty( $wpdb->last_error ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Get the snippet ID from a revision ID.
	 *
	 * @param int $revision_id The revision id.
	 *
	 * @return int|false
	 */
	public function get_revision_snippet_id( $revision_id ) {

		_deprecated_function( __METHOD__, '2.1.6', 'WPCode_Revisions::get_revision_parent_id' );

		return $this->get_revision_parent_id( $revision_id );
	}

	/**
	 * Get the parent id of for a revision.
	 *
	 * @param int $revision_id The revision id.
	 *
	 * @return int|false
	 */
	public function get_revision_parent_id( $revision_id ) {
		global $wpdb;

		$cache_key  = 'wpcode_snippet_id_for_revision_' . $revision_id;
		$snippet_id = wp_cache_get( $cache_key, 'wpcode' );

		if ( false === $snippet_id ) {
			$snippet_id = $wpdb->get_var(// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT snippet_id FROM {$wpdb->prefix}wpcode_revisions WHERE revision_id=%d",
					$revision_id
				)
			);
			if ( is_null( $snippet_id ) ) {
				return false;
			}
			wp_cache_set( $cache_key, 'wpcode' );
		}

		return absint( $snippet_id );
	}

	/**
	 * Listen to changes to the options used for Header & Footer and capture data to create a revision.
	 *
	 * @param mixed  $new_value The new value of the option.
	 * @param mixed  $old_value The previous value of the option.
	 * @param string $option_name The option name.
	 *
	 * @return mixed
	 */
	public function maybe_create_hf_revision( $new_value, $old_value, $option_name ) {
		// Store the old value.
		$this->header_footer_data[ $option_name ]          = $new_value;
		$this->previous_header_footer_data[ $option_name ] = $old_value;
		if ( 'ihaf_insert_body' === $option_name ) {
			// Let's check if header_footer_data and previous_header_footer_data have any differences.
			$diff = array_diff_assoc( $this->header_footer_data, $this->previous_header_footer_data );
			if ( ! empty( $diff ) ) {
				$this->create_hf_revision();
			}
		}

		return $new_value;
	}

	/**
	 * Load a revision's data by id.
	 *
	 * @param int $revision_id The revision id.
	 *
	 * @return array
	 */
	public function get_revision_data( $revision_id ) {
		$snippet_data = $this->get_revision( $revision_id );

		if ( empty( $snippet_data ) ) {
			return array();
		}

		return json_decode( $snippet_data->revision_data, true );
	}

	/**
	 * Load a revision's data by id.
	 *
	 * @param int $revision_id The revision id.
	 *
	 * @return array|StdClass
	 */
	public function get_revision( $revision_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpcode_revisions WHERE revision_id=%d",
			$revision_id
		);
		// This is called only when restoring a revision.
		$snippet_data = $wpdb->get_row( $query );// phpcs:ignore

		if ( empty( $snippet_data->revision_data ) ) {
			return array();
		}

		return $snippet_data;
	}

	/**
	 * Listen to changes in the post used for the block editor and add a revision to the
	 * associated snippet, if needed.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post_after Post object following the update.
	 * @param WP_Post $post_before Post object before the update.
	 *
	 * @return void
	 */
	public function maybe_create_blocks_revision( $post_id, $post_after, $post_before ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$block_editor = wpcode()->snippet_block_editor;
		if ( $block_editor::$post_type_key !== $post_after->post_type ) {
			return;
		}
		// If the content is the same, we don't need to create a revision.
		if ( $post_before->post_content === $post_after->post_content ) {
			return;
		}
		// Let's grab the snippet id for this post.
		$snippet_id = $block_editor->get_snippet_id_from_post_id( $post_id );

		if ( empty( $snippet_id ) ) {
			return;
		}
		$snippet       = new WPCode_Snippet( $snippet_id );
		$revision_data = $this->get_all_snippet_revision_data( $snippet );

		// We save the post content as the code for the revision.
		$revision_data['code'] = $post_after->post_content;
		$this->create_revision( $snippet_id, $revision_data );
	}

	/**
	 * Create a revision for the Header & Footer data.
	 *
	 * @return void
	 */
	public function create_hf_revision() {

		// Let's check if this is the first revision ever for Header & Footer.
		$revisions = $this->get_snippet_revisions( 0 );

		$this->create_revision( 0, $this->header_footer_data );
		// If this is the first revision, first create a revision with the existing data before we added this feature.
		if ( empty( $revisions ) && ! empty( array_filter( $this->previous_header_footer_data ) ) ) {
			$this->create_revision( 0, $this->previous_header_footer_data );
		}
	}

	/**
	 * Capture the page scripts data before it is updated to compare for a new revision later.
	 *
	 * @param int $post_id The post id.
	 *
	 * @return void
	 */
	public function capture_page_scripts_data( $post_id ) {
		// Don't create a revision in testing mode.
		if ( wpcode_testing_mode_enabled() ) {
			return;
		}
		$meta_keys = array(
			'_wpcode_header_scripts',
			'_wpcode_footer_scripts',
			'_wpcode_body_scripts',
			'_wpcode_page_snippets',
		);

		foreach ( $meta_keys as $meta_key ) {
			$this->previous_page_scripts_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
		}
	}

	/**
	 * Create a revision for the page scripts if needed.
	 *
	 * @param int   $post_id The post id.
	 * @param array $updated The updated data.
	 *
	 * @return void
	 */
	public function maybe_create_page_scripts_revision( $post_id, $updated ) {
		if ( empty( $this->previous_page_scripts_data ) ) {
			return;
		}
		$updated_data = array();
		$revisions    = $this->get_snippet_revisions( $post_id );

		// Check if something changed in the revision data.
		foreach ( $updated as $key => $value ) {
			if ( isset( $this->previous_page_scripts_data[ $key ] ) && $value === $this->previous_page_scripts_data[ $key ] ) {
				continue;
			}
			$updated_data[ $key ] = $value;
		}

		// If we have updated data, save the whole snippet data, so we can easily compare between revisions.
		if ( ! empty( $updated_data ) ) {
			$this->create_revision( $post_id, $updated );
		}
		// If $revisions is empty and all the values in the previous_page_scripts_data array are not empty we need to create a revision.
		if ( empty( $revisions ) && ! empty( array_filter( $this->previous_page_scripts_data ) ) ) {
			$this->create_revision( $post_id, $this->previous_page_scripts_data );
		}
	}

	/**
	 * Set the remote id for the revision.
	 *
	 * @param string $remote_id The remote id.
	 *
	 * @return void
	 */
	public function set_remote_id( $remote_id ) {
		$this->remote_id = $remote_id;
	}

	/**
	 * Clear the remote id for the revision.
	 *
	 * @return void
	 */
	public function clear_remote_id() {
		$this->remote_id = null;
	}

	/**
	 * Check if the remote id for a snippet exists.
	 *
	 * @param int    $snippet_id The snippet id.
	 * @param string $remote_id The remote id.
	 *
	 * @return bool
	 */
	public function remote_id_exists( $snippet_id, $remote_id ) {
		// Query the revisions table by snippet id and remote id.
		global $wpdb;

		$query    = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpcode_revisions WHERE snippet_id=%d AND remote_id=%s",
			$snippet_id,
			$remote_id
		);
		$revision = $wpdb->get_row( $query );// phpcs:ignore

		if ( empty( $revision ) ) {
			return false;
		}

		return true;
	}
}
