<?php
/**
 * Trait for loading snippets from files.
 *
 * @package wpcode
 */

/**
 * Trait WPCode_Snippet_Execute_File_Loader.
 * Provides methods for loading snippets from files.
 */
trait WPCode_Snippet_Execute_File_Loader {

	/**
	 * Get the snippet file name.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	protected function get_snippet_file_name( $snippet ) {
		if ( class_exists( 'WPCode_Snippets_File_Handler' ) ) {
			return WPCode_Snippets_File_Handler::get_snippet_file_name( $snippet );
		}
		return '';
	}

	/**
	 * Get the allowed snippets directory path.
	 *
	 * @return string The snippets directory path.
	 */
	protected function get_snippets_directory() {
		return WP_CONTENT_DIR . '/wpcode/snippets/';
	}

	/**
	 * Validate that a file path is within the allowed snippets directory.
	 *
	 * @param string $filename The file path to validate.
	 *
	 * @return bool True if the file path is valid and within the allowed directory.
	 */
	protected function is_valid_snippet_path( $filename ) {
		// File must exist first.
		if ( ! file_exists( $filename ) ) {
			return false;
		}

		// Resolve to real path to prevent directory traversal.
		$real_path = realpath( $filename );
		if ( false === $real_path ) {
			return false;
		}

		// Get the allowed base directory.
		$snippets_dir = realpath( $this->get_snippets_directory() );
		if ( false === $snippets_dir ) {
			return false;
		}

		// Ensure the file is within the allowed directory.
		return 0 === strpos( $real_path, $snippets_dir . DIRECTORY_SEPARATOR );
	}

	/**
	 * Load and execute snippet from file.
	 *
	 * @param string         $filename The file path.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string The output from the file execution.
	 */
	protected function load_from_file( $filename, $snippet ) {
		// Validate the file path is within the allowed snippets directory.
		if ( ! $this->is_valid_snippet_path( $filename ) ) {
			return '';
		}

		// Use output buffering to capture any output from the file.
		ob_start();

		try {
			// Make shortcode attributes available as $attributes if they exist.
			// phpcs:ignore WordPress.CodeAnalysis.VariableAnalysis.UnusedVariable -- Used in included snippet file.
			$attributes = ! empty( $snippet->attributes ) ? $snippet->attributes : array();

			// Include the file (functions/classes are already wrapped with existence checks in the file).
			include $filename; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath

		} catch ( Throwable $e ) {
			// Log the error and skip snippet (no database fallback).
			wpcode()->error->add_error(
				array(
					// Translators: %s is the error message.
					'message' => sprintf( __( 'Error loading snippet file: %s', 'wpcode-premium' ), $e->getMessage() ),
					'snippet' => $snippet,
				)
			);

			ob_end_clean();
			return '';
		}

		return ob_get_clean();
	}
}
