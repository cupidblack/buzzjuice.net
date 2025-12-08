<?php
/**
 * This class holds the logic that allows users to edit snippets with the block editor.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Snippet_Block_Editor
 */
class WPCode_Snippet_Block_Editor {

	/**
	 * The post type key.
	 *
	 * @var string
	 */
	public static $post_type_key = 'wpcode-blocks';

	/**
	 * We use this to store the blocks code when importing a snippet so that we create the block editor post.
	 *
	 * @var string
	 */
	private $imported_blocks_code = '';

	/**
	 * WPCode_Snippet_Block_Editor constructor.
	 */
	public function __construct() {
		// Register the post type for the block editor.
		add_action( 'init', array( $this, 'register_post_type' ) );
		// Redirect if accessing the post type in the admin directly.
		add_action( 'current_screen', array( $this, 'maybe_redirect' ) );
		// Handle automatically creating a post for the block editor when clicking from the snippet editor.
		add_action( 'admin_init', array( $this, 'handle_snippet_edit_with_block_editor_redirect' ) );
		// Load JS for the block editor.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		// Load HTML templates for the block editor.
		add_action( 'admin_footer', array( $this, 'editor_templates' ) );
		// Update the active menu item in the admin.
		add_filter( 'parent_file', array( $this, 'change_current_submenu' ) );
		add_filter( 'submenu_file', array( $this, 'change_current_submenu' ) );
		// Change admin title.
		add_filter( 'admin_title', array( $this, 'change_admin_title' ), 10, 2 );
		// Delete associated block editor post when the snippet is deleted.
		add_action( 'before_delete_post', array( $this, 'delete_associated_block_editor_post' ), 10, 2 );
		// Delete associated block editor post when the snippet is saved with a different code type.
		add_action( 'wpcode_snippet_after_update', array( $this, 'delete_post_if_code_type_changed' ), 10, 2 );

		// Add block data to the snippet export.
		add_filter( 'wpcode_export_snippet_data', array( $this, 'add_block_data_to_export' ), 10, 2 );
		// Process block data when importing a snippet.
		add_filter( 'wpcode_import_snippet_data', array( $this, 'handle_block_data_import' ) );
		// Process block data when loading a blocks snippet from the library.
		add_filter( 'wpcode_library_import_snippet_data', array( $this, 'handle_block_data_import' ) );
		// Create a block editor post after import if the data is present.
		add_action( 'wpcode_snippet_after_update', array( $this, 'maybe_create_block_editor_post' ), 10, 2 );

		// Execute blocks snippets content.
		add_filter( 'wpcode_snippet_output_blocks', 'do_blocks', 15 );
		add_filter( 'wpcode_snippet_output_blocks', 'do_shortcode', 20 );
		add_filter( 'wpcode_snippet_output_blocks', array( $this, 'auto_embed' ), 20 );

		// Add filter late to try to force the block editor for our post type.
		add_filter( 'use_block_editor_for_post', array( $this, 'force_block_editor' ), 9999, 2 );

		add_action( 'wpcode_before_snippet_duplicated', array( $this, 'duplicate_block_editor_post' ), 10 );
		add_action( 'wpcode_after_snippet_duplicated', array( $this, 'assign_duplicated_post' ), 10 );
	}

	/**
	 * Execute blocks snippets content.
	 *
	 * @param string $content The content to execute.
	 *
	 * @return string
	 */
	public function auto_embed( $content ) {
		global $wp_embed;
		return $wp_embed->autoembed( $content );
	}

	/**
	 * Register the post type for the block editor.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::$post_type_key,
			array(
				'public'            => false,
				'show_ui'           => true,
				'show_in_nav_menus' => false,
				'show_in_menu'      => false,
				'show_in_admin_bar' => false,
				'show_in_rest'      => true,
				'capability_type'   => 'wpcodeblock',
				'map_meta_cap'      => false,
				'supports'          => array(
					'editor',
				),
			)
		);
	}

	/**
	 * Handle redirects in relation to the post type.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		$current_screen = get_current_screen();

		// Redirect to the WPCode admin page to avoid confusion if the user tries to access the post type directly.
		if ( ! empty( $current_screen->id ) && 'edit-wpcode-blocks' === $current_screen->id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wpcode' ) );
			exit;
		}
	}

	/**
	 * Get a link to edit a snippet with the block editor.
	 *
	 * @param int $snippet_id The snippet ID.
	 *
	 * @return string The admin URL to edit the snippet with the block editor.
	 */
	public function get_edit_with_block_editor_link( $snippet_id ) {
		return add_query_arg(
			array(
				'action'     => 'wpcode_edit_with_block_editor',
				'snippet_id' => $snippet_id,
				'_wpnonce'   => wp_create_nonce( 'wpcode_redirect_to_block_editor' ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Listen for requests to edit a snippet with the block editor. Based on the snippet id, redirect
	 * to the block editor post type post associated with the snippet or create a new one and redirect to that
	 * while saving the block editor post id for future use.
	 *
	 * @return void
	 */
	public function handle_snippet_edit_with_block_editor_redirect() {
		if ( empty( $_GET['action'] ) || 'wpcode_edit_with_block_editor' !== $_GET['action'] ) {
			return;
		}

		// Check that we have the snippet id.
		if ( empty( $_GET['snippet_id'] ) ) {
			wp_die( esc_html__( 'Invalid snippet ID.', 'wpcode' ) );
		}

		// Check nonce.
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode_redirect_to_block_editor' ) ) {
			wp_die( esc_html__( 'This link has expired, please try again.', 'wpcode' ) );
		}
		$snippet_id = absint( $_GET['snippet_id'] );

		$block_editor_id = get_post_meta( $snippet_id, '_wpcode_block_editor_id', true );
		if ( $block_editor_id ) {
			wp_update_post(
				array(
					'ID'         => $block_editor_id,
					'post_title' => get_the_title( $snippet_id ),
				)
			);
			$url = get_edit_post_link( $block_editor_id, 'raw' );
		}

		// If we don't have an url yet, create a new post and redirect to it.
		if ( empty( $url ) ) {
			$block_editor_id = $this->create_post_for_snippet( $snippet_id );
			$url             = get_edit_post_link( $block_editor_id, 'raw' );
		}

		if ( ! use_block_editor_for_post( $block_editor_id ) ) {
			$message = esc_html__( 'The block editor is not available on your website, please disable any snippet or plugin preventing the Block Editor from being loaded and try again.', 'wpcode-premium' );

			// Add go back button.
			$message .= '<p><a href="' . esc_url( wp_get_referer() ) . '" class="button button-primary">' . esc_html__( 'Return to Snippet', 'wpcode-premium' ) . '</a></p>';
			wp_die(
				$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped.
				esc_html__( 'Block Editor Not Available', 'wpcode-premium' )
			);
		}

		$url = apply_filters( 'wpcode_block_editor_redirect_url', $url, $snippet_id, $block_editor_id );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Create a new post for the block editor and associate it with a snippet.
	 *
	 * @param int    $snippet_id The snippet ID.
	 * @param string $content Optional content to use for the post.
	 *
	 * @return int|WP_Error
	 */
	public function create_post_for_snippet( $snippet_id, $content = '' ) {
		$create_post_args = array(
			'post_type'   => self::$post_type_key,
			'post_status' => 'publish',
			'post_title'  => get_the_title( $snippet_id ),
		);
		if ( ! empty( $content ) ) {
			$create_post_args['post_content'] = $content;
		}
		$block_editor_id = wp_insert_post( $create_post_args );

		update_post_meta( $snippet_id, '_wpcode_block_editor_id', $block_editor_id );
		update_post_meta( $block_editor_id, '_wpcode_snippet_id', $snippet_id );

		return $block_editor_id;
	}

	/**
	 * Check if we are currently on the wpcode block editor post type.
	 *
	 * @return bool
	 */
	public function is_block_editor() {
		$current_screen = get_current_screen();
		if ( empty( $current_screen ) || self::$post_type_key !== $current_screen->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue the block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		if ( ! $this->is_block_editor() ) {
			return;
		}

		$admin_asset_file = WPCODE_PLUGIN_PATH . 'build/editor.asset.php';

		if ( ! file_exists( $admin_asset_file ) ) {
			return;
		}

		$asset = require $admin_asset_file;

		wp_enqueue_style(
			'wpcode-block-editor-styles',
			WPCODE_PLUGIN_URL . 'build/editor.css',
			null,
			$asset['version']
		);

		wp_enqueue_script(
			'wpcode-block-editor',
			WPCODE_PLUGIN_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Localize the blockeditor script to add our pinned notice message regarding the current snippet being edited.
		$snippet_id = get_post_meta( get_the_ID(), '_wpcode_snippet_id', true );
		$snippet    = get_post( $snippet_id );
		$return_url = add_query_arg(
			array(
				'page'       => 'wpcode-snippet-manager',
				'snippet_id' => absint( $snippet_id ),
			),
			admin_url( 'admin.php' )
		);
		$notice     = sprintf(
		/* translators: %s: Snippet name. */
			__( 'You are currently editing the %1$s snippet. Once done, %2$sclick here%3$s to return to your code snippet and finish setting up your settings.', 'wpcode' ),
			'<strong>' . esc_html( $snippet->post_title ) . '</strong>',
			'<a href="' . esc_url( $return_url ) . '">',
			'</a>'
		);
		wp_localize_script(
			'wpcode-block-editor',
			'wpcodeBlockEditor',
			array(
				'noticeText' => $notice,
			)
		);
	}

	/**
	 * Output js templates used on the block editor pages.
	 *
	 * @return void
	 */
	public function editor_templates() {
		if ( ! $this->is_block_editor() ) {
			return;
		}
		$snippet_id       = get_post_meta( get_the_ID(), '_wpcode_snippet_id', true );
		$snippet_edit_url = add_query_arg(
			array(
				'page'       => 'wpcode-snippet-manager',
				'snippet_id' => absint( $snippet_id ),
			),
			admin_url( 'admin.php' )
		);
		?>
		<script id="wpcode-gutenberg-button-return" type="text/html">
			<div id="wpcode-snippet-actions">
				<a id="wpcode-return-to-snippet-button" href="<?php echo esc_url( $snippet_edit_url ); ?>" class="button button-large">
					<?php echo esc_html__( 'Return to WPCode Snippet', 'insert-headers-and-footers' ); ?>
				</a>
			</div>
		</script>
		<?php
	}

	/**
	 * If we are editing a snippet with the block editor, change the current submenu to the wpcode menu.
	 *
	 * @param string $menu_item The menu item.
	 *
	 * @return string
	 */
	public function change_current_submenu( $menu_item ) {
		if ( ! $this->is_block_editor() ) {
			return $menu_item;
		}

		return 'wpcode';
	}

	/**
	 * Get the content of the blocks for a snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function get_blocks_content( $snippet ) {
		$associated_post = absint( get_post_meta( $snippet->get_id(), '_wpcode_block_editor_id', true ) );
		if ( empty( $associated_post ) ) {
			return '';
		}

		return get_post_field( 'post_content', $associated_post );
	}

	/**
	 * Change the admin title for the block editor to match the WPCode snippet editing title.
	 *
	 * @param string $admin_title The full admin title.
	 * @param string $title The page title.
	 *
	 * @return string
	 */
	public function change_admin_title( $admin_title, $title ) {
		if ( ! $this->is_block_editor() ) {
			return $admin_title;
		}

		return str_replace( $title, __( 'Edit snippet', 'insert-headers-and-footers' ), $admin_title );
	}

	/**
	 * Get the snippet ID from the post ID.
	 *
	 * @param int $post_id The id of the post used for the block editor.
	 *
	 * @return int
	 */
	public function get_snippet_id_from_post_id( $post_id ) {
		return absint( get_post_meta( $post_id, '_wpcode_snippet_id', true ) );
	}

	/**
	 * When a snippet is deleted, make sure we clean up the current post too.
	 *
	 * @param int     $post_id The id of the post being deleted.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function delete_associated_block_editor_post( $post_id, $post = null ) {
		if ( empty( $post ) ) {
			$post = get_post( $post_id );
		}
		// Let's make sure we run this just for our snippets post type.
		if ( empty( $post->post_type ) || 'wpcode' !== $post->post_type ) {
			return;
		}

		$associated_post = absint( get_post_meta( $post_id, '_wpcode_block_editor_id', true ) );

		if ( empty( $associated_post ) ) {
			return;
		}

		wp_delete_post( $associated_post, true );
	}

	/**
	 * When a snippet is updated, make sure we clean up the current post too if the code type changed.
	 *
	 * @param int            $snippet_id The id of the snippet being updated.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function delete_post_if_code_type_changed( $snippet_id, $snippet ) {
		if ( 'blocks' === $snippet->get_code_type() ) {
			return;
		}

		$associated_post = absint( get_post_meta( $snippet_id, '_wpcode_block_editor_id', true ) );

		if ( empty( $associated_post ) ) {
			return;
		}

		wp_delete_post( $associated_post, true );
	}

	/**
	 * Add blocks data to the snippet export if the code type matches.
	 *
	 * @param array          $snippet_data The snippet data for the export.
	 * @param WPCode_Snippet $snippet The snippet being exported.
	 *
	 * @return array
	 */
	public function add_block_data_to_export( $snippet_data, $snippet ) {
		if ( 'blocks' === $snippet->get_code_type() ) {
			$snippet_data['code'] = wpcode()->snippet_block_editor->get_blocks_content( $snippet );
		}

		return $snippet_data;
	}

	/**
	 * Remove blocks data from the snippet import if the code type matches and save it for later.
	 *
	 * @param array $snippet The snippet data.
	 *
	 * @return array
	 */
	public function handle_block_data_import( $snippet ) {

		if ( 'blocks' === $snippet['code_type'] ) {
			$this->imported_blocks_code = $snippet['code'];
			$snippet['code']            = '';
		}

		return $snippet;
	}

	/**
	 * When a snippet is saved after the import, if we have blocks data, create a post for it.
	 *
	 * @param int            $snippet_id The snippet id.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_create_block_editor_post( $snippet_id, $snippet ) {
		if ( 'blocks' !== $snippet->get_code_type() ) {
			return;
		}

		if ( ! empty( $this->imported_blocks_code ) ) {
			$this->create_post_for_snippet( $snippet_id, $this->imported_blocks_code );
			$this->imported_blocks_code = '';
		}
	}

	/**
	 * Force the block editor enabled for the post type used for WPCode Block snippets.
	 *
	 * @param bool    $use_block_editor Whether to use the block editor.
	 * @param WP_Post $post The post object.
	 *
	 * @return bool
	 */
	public function force_block_editor( $use_block_editor, $post ) {
		// Only force for our post type.
		if ( self::$post_type_key === $post->post_type ) {
			return true;
		}

		return $use_block_editor;
	}

	/**
	 * When a snippet is duplicated, make sure we duplicate the associated post too.
	 *
	 * @param WPCode_Snippet $snippet The snippet that is about to be duplicated.
	 *
	 * @return void
	 */
	public function duplicate_block_editor_post( $snippet ) {
		$associated_post = absint( get_post_meta( $snippet->get_id(), '_wpcode_block_editor_id', true ) );

		if ( empty( $associated_post ) ) {
			return;
		}

		$associated_post = get_post( $associated_post );
		// Duplicate the associated post and set it in the new object.
		$duplicated_post_id = wp_insert_post(
			array(
				'post_title'   => $associated_post->post_title,
				'post_content' => $associated_post->post_content,
				'post_type'    => self::$post_type_key,
				'post_status'  => 'publish',
			)
		);
		if ( ! is_wp_error( $duplicated_post_id ) ) {
			$this->duplicated_post_id = $duplicated_post_id;
		}
	}

	/**
	 * If we just created new post when a snippet is duplicated let's make sure it gets correctly associated to the new snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet that is about to be duplicated.
	 *
	 * @return void
	 */
	public function assign_duplicated_post( $snippet ) {
		if ( ! empty( $this->duplicated_post_id ) ) {
			$post_id = absint( $this->duplicated_post_id );
			update_post_meta( $snippet->get_id(), '_wpcode_block_editor_id', $post_id );
			update_post_meta( $post_id, '_wpcode_snippet_id', $snippet->get_id() );
			unset( $this->duplicated_post_id );
		}
	}
}