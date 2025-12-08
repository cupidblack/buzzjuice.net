<?php
/**
 * Register the WPCode snippet block for Gutenberg.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Snippet_Block
 */
class WPCode_Snippet_Block {

	/**
	 * WPCode_Snippet_Block constructor.
	 */
	public function __construct() {

		if ( ! $this->should_load() ) {
			return;
		}
		$this->hooks();
	}

	/**
	 * Check if the class should be loaded.
	 *
	 * @return bool
	 */
	public function should_load() {

		return function_exists( 'register_block_type' );
	}

	/**
	 * Hooks needed for the block.
	 */
	protected function hooks() {

		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Add filter to change the show_in_rest value for the register_post_type for wpcode.
		add_filter( 'register_post_type_args', array( $this, 'register_post_type_args' ), 10, 2 );

		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );

		add_filter( 'rest_wpcode_query', array( $this, 'exclude_snippets' ), 10, 2 );

	}

	/**
	 * Register the block for the editor.
	 *
	 * @return void
	 */
	public function register_block() {
		register_block_type(
			'wpcode/snippet',
			array(
				'attributes'      => array(
					'snippetId'  => array(
						'type' => 'string',
					),
					'attributes' => array(
						'type' => 'object',
					),
				),
				'style'           => 'wpcode-gutenberg-snippet',
				'editor_style'    => 'wpcode-blocks',
				'render_callback' => array( $this, 'get_snippet_preview' ),
				'category'        => 'widgets',
			)
		);
	}

	/**
	 * Server-side rendering of the block.
	 *
	 * @param array    $attr The attributes passed to the block.
	 * @param string   $content The content of the block.
	 * @param WP_Block $block The block object.
	 *
	 * @return string|void
	 */
	public function get_snippet_preview( $attr, $content, $block ) {
		if ( isset( $attr['snippetId'] ) ) {
			$snippet_id = absint( $attr['snippetId'] );
		} else {
			return;
		}

		$attributes = ! empty( $attr['attributes'] ) ? $attr['attributes'] : array();

		// Let's render the shortcode and implode the attributes.
		$shortcode = sprintf( '[wpcode id="%d" wpcode_source="block" %s]', $snippet_id, $this->implode_attributes( $attributes ) );

		$output = do_shortcode( $shortcode );

		// Let's check if the context is set to edit.
		if ( empty( $output ) && $this->is_gb_editor() ) {
			$output = sprintf(
				'<div class="wpcode-block-placeholder">%s</div>',
				esc_html__( 'The selected snippet has no output.', 'wpcode-code' )
			);

			$snippet = new WPCode_Snippet( $snippet_id );
			if ( ! $snippet->is_active() ) {
				$output = sprintf(
					'<div class="wpcode-block-placeholder">%1$s</div>',
					esc_html__( 'The selected snippet is inactive. It will only be executed once activated.', 'wpcode-premium' )
				);

				$output .= sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( admin_url( 'admin.php?page=wpcode-snippet-manager&snippet_id=' . $snippet_id ) ),
					esc_html__( 'Edit snippet', 'wpcode-premium' )
				);
			}
		}

		return $output;
	}

	/**
	 * Check if is Gutenberg REST API call since there's no official context provided.
	 *
	 * @return bool True if is Gutenberg REST API call.
	 */
	public function is_gb_editor() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'];
	}

	/**
	 * Takes an array of key-value pairs and implodes them into a string.
	 *
	 * @param array $attributes The attributes to implode.
	 *
	 * @return string
	 */
	public function implode_attributes( $attributes ) {
		$attributes_string = '';

		foreach ( $attributes as $key => $value ) {
			$attributes_string .= sprintf( '%s="%s" ', $key, $value );
		}

		return $attributes_string;
	}

	/**
	 * Enqueue the block editor assets.
	 */
	public function enqueue_block_editor_assets() {

		$block_asset_file = WPCODE_PLUGIN_PATH . 'build/block-snippet.asset.php';

		if ( ! file_exists( $block_asset_file ) ) {
			return;
		}

		$asset = require $block_asset_file;

		wp_enqueue_script(
			'wpcode-gutenberg-snippet',
			WPCODE_PLUGIN_URL . 'build/block-snippet.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'wpcode-gutenberg-snippet',
			'wpcode_gutenberg_snippet',
			array(
				'strings'           => array(
					'title'              => 'WPCode',
					'description'        => esc_html__( 'Choose a snippet and insert it in the current post.', 'wpcode-premium' ),
					'select_snippet'     => esc_html__( 'Select a snippet', 'wpcode-premium' ),
					'loading'            => esc_html__( 'Loading...', 'wpcode-premium' ),
					'choose_snippet'     => esc_html__( 'Choose snippet', 'wpcode-premium' ),
					'snippet_attributes' => esc_html__( 'Attributes', 'wpcode-premium' ),
					'snippet_settings'   => esc_html__( 'Snippet Settings', 'wpcode-premium' ),
					'inactive'           => esc_html__( 'Inactive', 'wpcode-premium' ),
					'no_permission'      => esc_html__( 'You do not have permissions to manage snippets.', 'wpcode-premium' ),
					'block_keywords'     => array(
						esc_html__( 'snippet', 'wpcode-premium' ),
						esc_html__( 'code', 'wpcode-premium' ),
						esc_html__( 'template', 'wpcode-premium' ),
						'html',
						'php',
					),
				),
				'can_edit_snippets' => current_user_can( 'wpcode_edit_snippets' ),
			)
		);

	}

	/**
	 * Filter the post type args for wpcode to allow it to be shown in the REST API for users with the right permissions.
	 *
	 * @param array  $args Register post type args.
	 * @param string $post_type The post type name.
	 *
	 * @return array
	 */
	public function register_post_type_args( $args, $post_type ) {
		if ( 'wpcode' !== $post_type ) {
			return $args;
		}

		$args['show_in_rest'] = current_user_can( 'wpcode_edit_snippets' );

		return $args;
	}

	/**
	 * Register the shortcode attributes values as a field in the REST API.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		register_rest_field(
			'wpcode',
			'shortcode_attributes',
			array(
				'get_callback'    => array( $this, 'get_shortcode_attributes' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Get the shortcode attributes for a snippet.
	 *
	 * @param array           $post The post object.
	 * @param string          $field_name The field name.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array
	 */
	public function get_shortcode_attributes( $post, $field_name, $request ) {
		if ( 'shortcode_attributes' === $field_name ) {
			$shortcode_attributes = get_post_meta( $post['id'], '_wpcode_shortcode_attributes', true );

			if ( empty( $shortcode_attributes ) || ! is_array( $shortcode_attributes ) ) {
				return array();
			}

			return $shortcode_attributes;
		}

		return array();
	}

	/**
	 * Filter out the rest response when loading snippets in the block.
	 * Any PHP snippet set to auto-insert site-wide like run everywhere, admin only, frontend. will be excluded from the response.
	 *
	 * @param array           $args Args for WP_Query.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array
	 */
	public function exclude_snippets( $args, $request ) {
		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		$exclude = $request->get_param( 'exclude' );

		// If we passed the exclude param that's the id of the block post we're editing so we need to grab the
		// corresponding snippet id to which the block post is attached and make sure you can't select that snippet
		// to avoid endless loops.
		if ( ! empty( $exclude ) && is_array( $exclude ) && ! empty( $exclude[0] ) ) {
			$exclude_snippet_id = absint( get_post_meta( $exclude[0], '_wpcode_snippet_id', true ) );
			if ( ! empty( $exclude_snippet_id ) ) {
				$args['post__not_in'] = array( $exclude_snippet_id );
			}
		}

		// Exclude PHP snippets set to run everywhere.
		$args['tax_query'][] = array(
			'taxonomy' => 'wpcode_location',
			'field'    => 'slug',
			'terms'    => apply_filters(
				'wpcode_snippet_block_exclude_locations',
				array(
					'everywhere',
					'frontend_only',
					'admin_only',
					'frontend_cl',
				)
			),
			'operator' => 'NOT IN',
		);

		// Exclude CSS snippets.
		$args['tax_query'][] = array(
			'taxonomy' => 'wpcode_type',
			'field'    => 'slug',
			'terms'    => apply_filters(
				'wpcode_snippet_block_exclude_code_types',
				array(
					'css',
				)
			),
			'operator' => 'NOT IN',
		);

		return $args;
	}
}

new WPCode_Snippet_Block();
