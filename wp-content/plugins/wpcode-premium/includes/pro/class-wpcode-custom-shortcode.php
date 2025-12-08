<?php
/**
 * This class handles loading custom shortcodes for snippets.
 *
 * @package WPCode
 */

/**
 * WPCode_Custom_Shortcode class.
 */
class WPCode_Custom_Shortcode {

	/**
	 * The custom shortcodes loaded from the db.
	 *
	 * @var array
	 */
	public $custom_shortcodes;

	/**
	 * Add hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_shortcodes' ) );
		add_action( 'update_postmeta', array( $this, 'maybe_clear_cache_on_update' ), 10, 4 );
		add_action( 'add_post_meta', array( $this, 'maybe_clear_cache_on_add' ), 10, 3 );
		add_filter( 'wpcode_shortcode_preview', array( $this, 'maybe_show_custom_shortcode_preview' ), 10, 2 );
	}

	/**
	 * Loop through all the WPCode custom shortcodes set in the snippets and register them.
	 *
	 * @return void
	 */
	public function add_shortcodes() {

		$shortcodes = $this->get_custom_shortcodes();

		foreach ( $shortcodes as $tag => $snippet_id ) {
			add_shortcode( $tag, array( $this, 'execute_custom_shortcode' ) );
		}

	}

	/**
	 * Generic handler for the custom shortcodes.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content The shortcode content.
	 * @param string $tag The shortcode tag.
	 *
	 * @return string
	 */
	public function execute_custom_shortcode( $atts, $content, $tag ) {
		$shortcodes = $this->get_custom_shortcodes();

		if ( ! array_key_exists( $tag, $shortcodes ) ) {
			return '';
		}

		$snippet = new WPCode_Snippet( absint( $shortcodes[ $tag ] ) );

		if ( ! $snippet->is_active() ) {
			return '';
		}

		// Let's check that conditional logic rules are met.
		if ( $snippet->conditional_rules_enabled() && ! wpcode()->conditional_logic->are_snippet_rules_met( $snippet ) && apply_filters( 'wpcode_shortcode_use_conditional_logic', true ) ) {
			return '';
		}

		/**
		 * Allow filtering custom shortcodes as a location.
		 * Pass the shortcode tag for more options.
		 */
		$shortcode_location = apply_filters( 'wpcode_get_snippets_for_location', array( $snippet ), 'shortcode-' . $tag );

		if ( empty( $shortcode_location ) ) {
			return '';
		}

		do_action( 'wpcode_shortcode_before_output', $snippet, $atts, $content, $tag );

		return wpcode()->execute->get_snippet_output( $snippet );
	}

	/**
	 * Get custom shortcodes and store them in the class instance.
	 * Will look in the cache first and if that's empty load the query.
	 *
	 * @return array
	 */
	public function get_custom_shortcodes() {
		if ( ! isset( $this->custom_shortcodes ) ) {
			$this->custom_shortcodes = get_option( 'wpcode_custom_shortcodes', false );
			if ( ! $this->custom_shortcodes ) {
				$this->custom_shortcodes = $this->gather_shortcodes();
			}
		}

		return $this->custom_shortcodes;
	}

	/**
	 * Grab all the shortcodes from the db and store them in a separate option for performance.
	 *
	 * @return array
	 */
	protected function gather_shortcodes() {

		$shortcodes = array();

		$snippets_with_shortcodes = get_posts(
			array(
				'post_type'      => 'wpcode',
				'post_status'    => 'any',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_wpcode_custom_shortcode',
						'compare' => 'EXISTS',
					),
				),
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			)
		);

		foreach ( $snippets_with_shortcodes as $snippet_with_shortcode ) {
			$snippet                                        = new WPCode_Snippet( $snippet_with_shortcode );
			$shortcodes[ $snippet->get_custom_shortcode() ] = $snippet->get_id();
		}

		update_option( 'wpcode_custom_shortcodes', $shortcodes );

		return $shortcodes;
	}

	/**
	 * Delete the cache, used when a custom shortcode is updated/added.
	 *
	 * @return void
	 */
	public function clear_shortcode_cache() {
		update_option( 'wpcode_custom_shortcodes', false );
	}

	/**
	 * Listen for updates to the meta used to store the custom shortcode and only clear the cache
	 * if the custom shortcode name has actually been changed.
	 *
	 * @param int    $meta_id ID of metadata entry to update.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key Metadata key.
	 * @param mixed  $meta_value Metadata value. This will be a PHP-serialized string representation of the value
	 *                           if the value is an array, an object, or itself a PHP-serialized string.
	 *
	 * @return void
	 */
	public function maybe_clear_cache_on_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_wpcode_custom_shortcode' !== $meta_key ) {
			return;
		}

		$prev_value = get_post_meta( $object_id, $meta_key, true );
		if ( $prev_value !== $meta_value ) {
			$this->clear_shortcode_cache();
		}
	}

	/**
	 * When a new custom shortcode is created, force-update the cache.
	 *
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key Metadata key.
	 * @param mixed  $_meta_value Metadata value.
	 *
	 * @return void
	 */
	public function maybe_clear_cache_on_add( $object_id, $meta_key, $_meta_value ) {
		if ( '_wpcode_custom_shortcode' !== $meta_key ) {
			return;
		}

		$this->clear_shortcode_cache();
	}

	/**
	 * Change the shortcode preview if we have a custom shortcode available.
	 *
	 * @param string         $shortcode The shortcode.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function maybe_show_custom_shortcode_preview( $shortcode, $snippet ) {
		if ( ! empty( $snippet->get_custom_shortcode() ) ) {
			$shortcode = '[' . $snippet->get_custom_shortcode() . ']';
		}

		return $shortcode;
	}
}
