<?php
/**
 * Class used to execute snippets in the Pro plugin.
 *
 * @package WPCode
 */

/**
 * WPCode_Snippet_Execute_Pro class.
 */
class WPCode_Snippet_Execute_Pro extends WPCode_Snippet_Execute {

	use WPCode_Snippet_Execute_File_Loader;

	/**
	 * Load the classes and options available for executing code.
	 *
	 * @return void
	 */
	public function load_types() {
		parent::load_types();
		// Include other Pro execution classes if needed.
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/execute/class-wpcode-snippet-execute-blocks.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/execute/class-wpcode-snippet-execute-scss.php';
	}

	/**
	 * Gets passed a snippet WP_Post or id and returns the processed output.
	 * Overrides parent to add file loading support for PHP execution types.
	 *
	 * @param int|WP_Post|WPCode_Snippet $snippet The snippet id or post object.
	 *
	 * @return string
	 */
	public function get_snippet_output( $snippet ) {
		// If we're in headers & footers mode prevent execution of any type of snippet.
		if ( WPCode()->settings->get_option( 'headers_footers_mode' ) ) {
			return '';
		}
		if ( ! $snippet instanceof WPCode_Snippet ) {
			$snippet = new WPCode_Snippet( $snippet );
		}

		// Check if snippet should be loaded from file (for PHP execution types only).
		// During activation checks, always use database code (the new code being saved).
		// Don't load from file to avoid function redeclaration errors.
		$is_activation = $this->is_doing_activation();
		$code_type     = $snippet->get_code_type();
		$php_types     = array( 'php', 'html', 'universal' );

		// For PHP types, check global setting instead of per-snippet meta.
		// Asset types use get_load_as_file() which checks per-snippet meta.
		$php_load_as_file = false;
		if ( in_array( $code_type, $php_types, true ) ) {
			// PHP types use global setting.
			$php_load_as_file = wpcode()->settings->get_option( 'php_load_as_file', false );
		} else {
			// Fall back to parent method.
			return parent::get_snippet_output( $snippet );
		}

		// Don't attempt file loading for dynamically created snippets (like pixel snippets).
		// These snippets are generated on-the-fly, have string IDs or post_data = null, and don't have files.
		$snippet_id = $snippet->get_id();
		$post_data  = $snippet->get_post_data();
		// Only attempt file loading for real database snippets (numeric ID > 0 and post_data exists).
		$is_database_snippet = is_numeric( $snippet_id ) && $snippet_id > 0 && null !== $post_data;

		// Use filter to allow preventing file loading during activation (similar to cache system pattern).
		// This allows other code to prevent file loading in specific contexts.
		$should_load_from_file = apply_filters(
			'wpcode_should_load_snippet_from_file',
			! $is_activation && $php_load_as_file && in_array( $code_type, $php_types, true ) && $is_database_snippet,
			$snippet,
			$is_activation
		);

		// Check if snippet should be loaded from file (but not during activation).
		if ( $should_load_from_file ) {
			$filename = $this->get_snippet_file_name( $snippet );
			if ( file_exists( $filename ) ) {
				return $this->load_from_file( $filename, $snippet );
			}
			// File doesn't exist - skip snippet (no database fallback).
			return '';
		}

		// Fall back to parent method (database loading).
		return parent::get_snippet_output( $snippet );
	}

	/**
	 * Pro types are always for licensed users.
	 *
	 * @param string $key The type key.
	 *
	 * @return false
	 */
	public function is_type_pro( $key ) {
		if ( empty( wpcode()->license->get() ) ) {
			return parent::is_type_pro( $key );
		}

		return false;
	}

	/**
	 * Load the snippet types on demand.
	 *
	 * @return void
	 */
	public function load_snippet_types_on_demand() {
		parent::load_snippet_types_on_demand();

		$this->types['blocks'] = array(
			'class'        => 'WPCode_Snippet_Execute_Blocks',
			'label'        => __( 'Blocks Snippet', 'wpcode-premium' ),
			'description'  => __( 'Use the Block Editor to create components that you can insert anywhere in your site.', 'wpcode-premium' ),
			'is_pro'       => true,
			'filter_label' => 'Blocks',
		);
		$this->types['scss']   = array(
			'class'        => 'WPCode_Snippet_Execute_SCSS',
			'label'        => __( 'SCSS Snippet', 'wpcode-premium' ),
			'description'  => __( 'Write SCSS styles directly in WPCode and easily customize how your website looks.', 'wpcode-premium' ),
			'is_pro'       => true,
			'filter_label' => 'SCSS',
		);
	}
}
