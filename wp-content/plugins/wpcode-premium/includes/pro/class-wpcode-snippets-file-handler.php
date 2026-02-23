<?php
/**
 * This class handles generating and loading actual files for JS, CSS, PHP, HTML, and Universal snippets.
 * This is an option in the Snippet settings.
 *
 * @package wpcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPCode_Snippets_File_Handler {

	/**
	 * The types of snippets we handle (asset types).
	 *
	 * @var array
	 */
	protected $asset_types = array( 'js', 'css', 'scss' );

	/**
	 * The types of snippets we handle (PHP execution types).
	 *
	 * @var array
	 */
	protected $php_types = array( 'php', 'html', 'universal' );

	/**
	 * Flag to prevent duplicate index rebuilds in the same request.
	 *
	 * @var bool
	 */
	protected static $index_rebuild_scheduled = false;

	/**
	 * WPCode_Snippets_File_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wpcode_snippet_after_update', array( $this, 'maybe_update_snippet_file' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_js', array( $this, 'maybe_enqueue_instead' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_css', array( $this, 'maybe_enqueue_instead' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'maybe_delete_snippet_file' ), 10, 1 );

		// Rebuild index file when snippets are updated/deleted.
		add_action( 'wpcode_snippet_after_update', array( $this, 'maybe_rebuild_index_file' ), 20, 2 );
		add_action( 'before_delete_post', array( $this, 'maybe_rebuild_index_file_after_delete' ), 20, 1 );

		// Also rebuild index when post status changes (activation/deactivation).
		add_action( 'transition_post_status', array( $this, 'maybe_rebuild_index_on_status_change' ), 20, 3 );
	}

	/**
	 * Maybe update the snippet file.
	 *
	 * @param int            $snippet_id The snippet id.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_update_snippet_file( $snippet_id, $snippet ) {
		$code_type = $snippet->get_code_type();
		if ( ! in_array( $code_type, $this->asset_types, true ) && ! in_array( $code_type, $this->php_types, true ) ) {
			return;
		}

		// For asset types, ensure load_as_file property is set from meta if not already set on the object.
		// For PHP types, the get_load_as_file() method will check the global setting, so we don't need to set it here.
		if ( in_array( $code_type, $this->asset_types, true ) && ! isset( $snippet->load_as_file ) ) {
			$snippet->load_as_file = boolval( get_post_meta( $snippet_id, '_wpcode_load_as_file', true ) );
		}

		// Clear post cache once to ensure fresh data from database for all types.
		// This is important because is_active() depends on post_data->post_status.
		clean_post_cache( $snippet_id );

		// For PHP types, check status and delete file immediately if deactivated.
		// Do this BEFORE reloading to ensure we catch the status change.
		if ( in_array( $code_type, $this->php_types, true ) ) {
			// Get post status using WordPress API after clearing cache.
			$post_status = get_post_status( $snippet_id );
			$is_active   = 'publish' === $post_status;

			// If deactivated, delete the file immediately and return early.
			// Don't call update_snippet_file() as it would recreate the file.
			if ( ! $is_active ) {
				$filename = self::get_snippet_file_name( $snippet );
				if ( file_exists( $filename ) ) {
					self::safe_unlink( $filename );
				}
				// Return early - don't update/create the file if snippet is deactivated.
				return;
			}
		}

		// Reload snippet to ensure we have fresh post status data after save.
		$snippet = new WPCode_Snippet( $snippet_id );

		$this->update_snippet_file( $snippet );
	}

	/**
	 * Maybe rebuild the index file after snippet update.
	 *
	 * @param int            $snippet_id The snippet id.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_rebuild_index_file( $snippet_id, $snippet ) {
		// Validate snippet ID matches the snippet object.
		if ( ! $snippet || $snippet->get_id() !== $snippet_id ) {
			return;
		}

		// Only rebuild index for PHP execution types when file loading is enabled.
		$code_type = $snippet->get_code_type();
		if ( ! in_array( $code_type, $this->php_types, true ) ) {
			return;
		}

		if ( ! $this->should_use_file_cache() ) {
			return;
		}

		// Always rebuild index when PHP snippets are updated (to reflect active/inactive status changes).
		// Rebuild immediately - the snippet object already has the updated data.
		self::build_index_file( true );
	}

	/**
	 * Maybe rebuild the index file after snippet deletion.
	 *
	 * @param int $post_id The post ID being deleted.
	 *
	 * @return void
	 */
	public function maybe_rebuild_index_file_after_delete( $post_id ) {
		// Check if this is a WPCode snippet.
		if ( 'wpcode' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! $this->should_use_file_cache() ) {
			return;
		}

		// Try to get snippet before it's deleted to check code type.
		$snippet   = new WPCode_Snippet( $post_id );
		$code_type = $snippet->get_code_type();

		// Only rebuild index for PHP execution types.
		if ( ! in_array( $code_type, $this->php_types, true ) ) {
			return;
		}

		// Always rebuild index when PHP snippets are deleted (to remove from index).
		// Rebuild immediately - the post is deleted but we can still query for remaining active snippets.
		self::build_index_file( true );
	}

	/**
	 * Maybe rebuild index file when post status changes (activation/deactivation).
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The old post status.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function maybe_rebuild_index_on_status_change( $new_status, $old_status, $post ) {
		// Only handle wpcode post type.
		if ( 'wpcode' !== $post->post_type ) {
			return;
		}

		// Only rebuild if status actually changed between publish and draft (activation/deactivation).
		if ( $new_status === $old_status ) {
			return;
		}

		// Only rebuild if transitioning to/from publish status.
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		if ( ! $this->should_use_file_cache() ) {
			return;
		}

		$snippet   = new WPCode_Snippet( $post->ID );
		$code_type = $snippet->get_code_type();

		// Only rebuild index for PHP execution types.
		if ( ! in_array( $code_type, $this->php_types, true ) ) {
			return;
		}

		// If deactivating (publish -> draft), also delete the snippet file immediately.
		// This ensures the file is deleted even if wpcode_snippet_after_update doesn't fire.
		// Use the post ID directly to get the filename, as the snippet object might have stale data.
		if ( 'publish' === $old_status && 'draft' === $new_status ) {
			$filename = self::get_snippet_file_name( $snippet );
			if ( file_exists( $filename ) ) {
				self::safe_unlink( $filename );
			}
		}

		// Rebuild index after status change.
		// Use shutdown hook to ensure database is fully updated before querying.
		// Use a flag to prevent duplicate rebuilds if multiple hooks fire.
		if ( ! self::$index_rebuild_scheduled ) {
			self::$index_rebuild_scheduled = true;
			// Use static callback since this is a static method context.
			add_action( 'shutdown', array( __CLASS__, 'rebuild_index_on_shutdown' ), 999 );
		}
	}

	/**
	 * Rebuild index file on shutdown (called after status change).
	 * Static method so it can be called from shutdown hook.
	 *
	 * @return void
	 */
	public static function rebuild_index_on_shutdown() {
		self::$index_rebuild_scheduled = false; // Reset flag.
		self::build_index_file( true );
	}

	/**
	 * Check if file-based cache should be used.
	 *
	 * @return bool
	 */
	protected function should_use_file_cache() {
		// Check if the global setting for PHP file loading is enabled.
		return wpcode()->settings->get_option( 'php_load_as_file', false );
	}

	/**
	 * Update the snippet file.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	protected function update_snippet_file( $snippet ) {
		$code_type = $snippet->get_code_type();

		if ( 'scss' === $code_type ) {
			$code = $snippet->get_compiled_code();
		} else {
			$code = $snippet->get_code();
		}
		$code = wp_unslash( $code );

		// Check if this is a PHP execution type that needs special handling.
		if ( in_array( $code_type, $this->php_types, true ) ) {
			$this->update_php_snippet_file( $snippet, $code );
			return;
		}

		// Handle asset types (JS, CSS, SCSS).
		if ( $snippet->maybe_compress_output() ) {
			$compress_method = 'compress_' . $code_type;
			if ( method_exists( 'WPCode_Snippets_Compressed', $compress_method ) ) {
				$code = WPCode_Snippets_Compressed::$compress_method( $code );
			}
		}

		$filename = self::get_snippet_file_name( $snippet );

		// Check post status directly from database to avoid cached data issues.
		// is_active() may cache the result, so we check post_status directly.
		$post_status = get_post_status( $snippet->get_id() );
		$is_active   = 'publish' === $post_status;

		// For asset types, check per-snippet meta. PHP types use global setting (checked elsewhere).
		$should_load_as_file = $snippet->get_load_as_file();

		// If the file exists and (the code is empty or the snippet is not active or the snippet should not be loaded as a file), delete the file.
		if ( file_exists( $filename ) && ( empty( $code ) || ! $is_active || ! $should_load_as_file ) ) {
			$this->safe_unlink( $filename );

			return;
		}

		// If the code is not empty, create or update the file.
		if ( ! empty( $code ) ) {
			self::safe_file_put_contents( $filename, $code );
		}
	}

	/**
	 * Update a PHP snippet file (PHP, HTML, or Universal).
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 * @param string         $code The snippet code.
	 * @param bool           $force Force creation even if load_as_file is false (for bulk operations).
	 *
	 * @return void
	 */
	protected function update_php_snippet_file( $snippet, $code, $force = false ) {
		$filename = self::get_snippet_file_name( $snippet );

		// Check post status directly to ensure we have the latest status after save.
		// This is more reliable than is_active() which might have cached data.
		$post_status = get_post_status( $snippet->get_id() );
		$is_active   = 'publish' === $post_status;

		// If the snippet is not active, delete the file if it exists and return early.
		// Don't create/update the file for deactivated snippets.
		if ( ! $is_active ) {
			if ( file_exists( $filename ) ) {
				$this->safe_unlink( $filename );
			}
			// Index file will be rebuilt by maybe_rebuild_index_file() hook.
			return;
		}

		// For PHP types, check global setting instead of per-snippet meta.
		$php_load_as_file = wpcode()->settings->get_option( 'php_load_as_file', false );

		// If the file exists and (the code is empty or the snippet should not be loaded as a file), delete the file.
		if ( file_exists( $filename ) && ( empty( $code ) || ( ! $force && ! $php_load_as_file ) ) ) {
			$this->safe_unlink( $filename );
			// Index file will be rebuilt by maybe_rebuild_index_file() hook.
			return;
		}

		// If the code is not empty and (snippet should load as file OR forced), create or update the file.
		if ( ! empty( $code ) && ( $force || $php_load_as_file ) ) {
			$code_type    = $snippet->get_code_type();
			$file_content = $this->prepare_php_file_content( $snippet, $code, $code_type );
			$result       = self::safe_file_put_contents( $filename, $file_content );
			// Clear opcache for the snippet file to ensure changes take effect immediately.
			if ( false !== $result && function_exists( 'opcache_invalidate' ) && file_exists( $filename ) ) {
				opcache_invalidate( $filename, true );
			}
			// Index file will be rebuilt by maybe_rebuild_index_file() hook.
		}
	}

	/**
	 * Prepare the content for a PHP file (PHP, HTML, or Universal snippet).
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 * @param string         $code The snippet code.
	 * @param string         $code_type The code type.
	 *
	 * @return string The prepared file content.
	 */
	protected function prepare_php_file_content( $snippet, $code, $code_type ) {
		// Security comment and PHP opening tag.
		$file_content  = "<?php\n";
		$file_content .= "/**\n";
		$file_content .= " * WPCode Snippet File\n";
		$file_content .= " * Snippet ID: {$snippet->get_id()}\n";
		$file_content .= " * Type: {$code_type}\n";
		$file_content .= " * DO NOT EDIT THIS FILE DIRECTLY - Use WPCode admin to edit this snippet.\n";
		$file_content .= " * This file is auto-generated and may be overwritten.\n";
		$file_content .= " */\n\n";

		if ( 'php' === $code_type ) {
			// PHP snippets: code is already PHP, no additional wrapping needed.
			$file_content .= $code;
		} elseif ( 'html' === $code_type ) {
			// HTML snippets: output as HTML.
			$file_content .= "?>\n";
			$file_content .= $code;
			$file_content .= "\n<?php\n";
		} elseif ( 'universal' === $code_type ) {
			// Universal snippets: mix of HTML and PHP, use closing/opening PHP tags.
			$file_content .= "?>\n";
			$file_content .= $code;
			$file_content .= "\n<?php\n";
		}

		return $file_content;
	}

	/**
	 * Check if a snippet file has been altered (modified outside of WPCode).
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return bool True if file has been altered, false otherwise.
	 */
	public static function is_file_altered( $snippet ) {
		// Only check PHP execution types.
		$code_type = $snippet->get_code_type();
		$php_types = array( 'php', 'html', 'universal' );
		if ( ! in_array( $code_type, $php_types, true ) ) {
			return false;
		}

		// Only check if file loading is enabled (PHP types use global setting).
		$php_load_as_file = wpcode()->settings->get_option( 'php_load_as_file', false );
		if ( ! $php_load_as_file ) {
			return false;
		}

		// Only check if snippet is active. Deactivated snippets don't need file alteration warnings.
		if ( ! $snippet->is_active() ) {
			return false;
		}

		$filename = self::get_snippet_file_name( $snippet );
		if ( ! file_exists( $filename ) ) {
			return true; // File doesn't exist, so it's altered.
		}

		// Read file content and extract the actual code (remove header comments).
		$file_content = file_get_contents( $filename );
		if ( false === $file_content ) {
			return false;
		}

		// Remove PHP opening tag and header comments.
		$file_content = preg_replace( '/^<\?php\s*\n\/\*\*.*?\*\/\s*\n/s', '', $file_content );

		// For HTML and Universal snippets, remove the PHP closing/opening tags around the content.
		if ( in_array( $code_type, array( 'html', 'universal' ), true ) ) {
			// Remove PHP closing tag from start and opening tag from end.
			$file_content = preg_replace( '/^\?>\s*\n/', '', $file_content );
			$file_content = preg_replace( '/\s*\n<\?php\s*\n?$/', '', $file_content );
		} else {
			// For PHP snippets, just remove closing PHP tag if present.
			$file_content = preg_replace( '/\?>\s*$/', '', $file_content );
		}

		$file_code = trim( $file_content );

		// Get database code and wrap it (same way it's wrapped when writing to file).
		$db_code = trim( $snippet->get_code() );

		// Compare file code with database code (normalize whitespace for comparison).
		// Note: when file loading is enabled, PHP snippets are excluded from the cache, but they remain in the database `post_content`.
		$file_code_normalized = preg_replace( '/\s+/', ' ', $file_code );
		$db_code_normalized   = preg_replace( '/\s+/', ' ', $db_code );

		return $file_code_normalized !== $db_code_normalized;
	}

	/**
	 * Get the snippet file name.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public static function get_snippet_file_name( $snippet ) {
		// Let's generate a unique filename by hashing the id.
		$file_name  = md5( $snippet->get_id() );
		$file_name .= '.' . self::get_snippet_extension( $snippet );

		$code_type = $snippet->get_code_type();
		$php_types = array( 'php', 'html', 'universal' );

		// Use different directories for asset types vs PHP execution types.
		// PHP files MUST be in wp-content directory (not uploads) as many hosts disable PHP execution in uploads.
		if ( in_array( $code_type, $php_types, true ) ) {
			// In multisite, use site-specific directories to prevent cross-site file deletion.
			if ( is_multisite() ) {
				$blog_id = get_current_blog_id();
				// Ensure blog_id is valid (should be > 0 in multisite).
				if ( $blog_id > 0 ) {
					$dir = WP_CONTENT_DIR . '/wpcode/snippets/site-' . absint( $blog_id ) . '/';
				} else {
					// Fallback to root directory if blog_id is invalid.
					$dir = WP_CONTENT_DIR . '/wpcode/snippets/';
				}
			} else {
				$dir = WP_CONTENT_DIR . '/wpcode/snippets/';
			}
		} else {
			// Asset types can use uploads directory as they don't execute PHP.
			$upload_dir = wp_upload_dir();
			$dir        = $upload_dir['basedir'] . '/wpcode/assets/';
		}

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir . $file_name;
	}

	/**
	 * Maybe enqueue the snippet instead of outputting it.
	 *
	 * @param string         $code The snippet code.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function maybe_enqueue_instead( $code, $snippet ) {
		// Only handle asset types (JS, CSS, SCSS) - not PHP execution types.
		$code_type   = $snippet->get_code_type();
		$asset_types = array( 'js', 'css', 'scss' );
		if ( ! in_array( $code_type, $asset_types, true ) ) {
			return $code;
		}

		// If the snippet is not set to be loaded as a file, return the code.
		// For PHP types, check global setting. Asset types use per-snippet meta.
		$php_types           = array( 'php', 'html', 'universal' );
		$should_load_as_file = false;
		if ( in_array( $code_type, $php_types, true ) ) {
			$should_load_as_file = wpcode()->settings->get_option( 'php_load_as_file', false );
		} else {
			$should_load_as_file = $snippet->get_load_as_file();
		}

		if ( ! $should_load_as_file ) {
			return $code;
		}
		// Let's check if the file for this snippet exists.
		$filename = self::get_snippet_file_name( $snippet );

		if ( file_exists( $filename ) ) {
			$this->enqueue_snippet( $snippet );

			return '';
		}

		// File doesn't exist but snippet is set to load as file - fall back to inline code.
		return $code;
	}

	/**
	 * Get the snippet extension.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public static function get_snippet_extension( $snippet ) {
		$code_type = $snippet->get_code_type();

		if ( 'scss' === $code_type ) {
			return 'css';
		}

		// PHP, HTML, and Universal snippets all use .php extension.
		if ( in_array( $code_type, array( 'php', 'html', 'universal' ), true ) ) {
			return 'php';
		}

		return $code_type;
	}

	/**
	 * Get the file URL for the snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public static function get_file_url( $snippet ) {
		// Let's generate a unique filename by hashing the id.
		$file_name = md5( $snippet->get_id() );

		$file_name .= '.' . self::get_snippet_extension( $snippet );

		$upload_dir = wp_upload_dir();

		$dir = $upload_dir['baseurl'] . '/wpcode/assets/';

		return $dir . $file_name;
	}

	/**
	 * Enqueue the snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function enqueue_snippet( $snippet ) {

		$url     = self::get_file_url( $snippet );
		$version = strtotime( $snippet->modified );

		if ( 'js' === $snippet->get_code_type() ) {
			wp_enqueue_script( 'wpcode-snippet-' . $snippet->get_id(), $url, array(), $version, true );
		} elseif ( 'css' === $snippet->get_code_type() ) {
			wp_enqueue_style( 'wpcode-snippet-' . $snippet->get_id(), $url, array(), $version );
		}
	}

	/**
	 * Safely write content to a file with error logging.
	 *
	 * @param string $filename The file path to write to.
	 * @param string $content The content to write.
	 *
	 * @return int|false Number of bytes written on success, false on failure.
	 */
	protected static function safe_file_put_contents( $filename, $content ) {
		// Ensure directory exists before writing.
		$dir = dirname( $filename );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$result = file_put_contents( $filename, $content );

		if ( false === $result ) {
			$error         = error_get_last();
			$error_message = isset( $error['message'] ) ? $error['message'] : 'Unknown error';

			// Use WPCode logger if available, otherwise use error_log.
			if ( function_exists( 'wpcode' ) && is_callable( array( wpcode(), 'logger' ) ) ) {
				wpcode()->logger->handle(
					time(),
					sprintf( 'WPCode: Failed to write snippet file "%s". Error: %s', $filename, $error_message ),
					'error'
				);
			} else {
				error_log( sprintf( 'WPCode: Failed to write snippet file "%s". Error: %s', $filename, $error_message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		return $result;
	}

	/**
	 * Safely delete a file with error logging.
	 *
	 * @param string $filename The file path to delete.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected static function safe_unlink( $filename ) {
		if ( ! file_exists( $filename ) ) {
			return true; // File doesn't exist, consider it successful.
		}

		$result = @unlink( $filename );

		if ( ! $result ) {
			$error         = error_get_last();
			$error_message = isset( $error['message'] ) ? $error['message'] : 'Unknown error';

			// Use WPCode logger if available, otherwise use error_log.
			if ( function_exists( 'wpcode' ) && is_callable( array( wpcode(), 'logger' ) ) ) {
				wpcode()->logger->handle(
					time(),
					sprintf( 'WPCode: Failed to delete snippet file "%s". Error: %s', $filename, $error_message ),
					'error'
				);
			} else {
				error_log( sprintf( 'WPCode: Failed to delete snippet file "%s". Error: %s', $filename, $error_message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		return $result;
	}

	/**
	 * Maybe delete snippet file when snippet is deleted.
	 *
	 * @param int $post_id The post ID being deleted.
	 *
	 * @return void
	 */
	public function maybe_delete_snippet_file( $post_id ) {
		// Check if this is a WPCode snippet.
		if ( 'wpcode' !== get_post_type( $post_id ) ) {
			return;
		}

		$snippet  = new WPCode_Snippet( $post_id );
		$filename = self::get_snippet_file_name( $snippet );

		if ( file_exists( $filename ) ) {
			$this->safe_unlink( $filename );
		}

		// Index file will be rebuilt by maybe_rebuild_index_file_after_delete() hook.
	}

	/**
	 * Bulk generate files for all active PHP snippets.
	 * Called when global setting is enabled.
	 *
	 * @return void
	 */
	public static function bulk_generate_php_files() {
		$php_types = array( 'php', 'html', 'universal' );
		$handler   = new self();

		// Get all snippets that are PHP execution types.
		$args  = array(
			'post_type'      => 'wpcode',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			// Still build index file even if no snippets (creates empty index).
			// Use force=true since setting might not be saved yet.
			self::build_index_file( true );
			return;
		}

		foreach ( $query->posts as $post ) {
			$snippet = new WPCode_Snippet( $post->ID );
			// Only process active PHP execution types.
			if ( ! $snippet->is_active() || ! in_array( $snippet->get_code_type(), $php_types, true ) ) {
				continue;
			}

			$code = $snippet->get_code();
			if ( ! empty( $code ) ) {
				// Force file creation since we're enabling the global setting.
				$handler->update_php_snippet_file( $snippet, wp_unslash( $code ), true );
			}
		}

		// Build index file after all files are created (force since setting might not be saved yet).
		self::build_index_file( true );
	}

	/**
	 * Bulk delete all PHP snippet files for the current site.
	 * Called when global setting is disabled.
	 * In multisite, only deletes files for the current site (uses site-specific directory from get_index_file_path()).
	 *
	 * @return void
	 */
	public static function bulk_delete_php_files() {
		// get_index_file_path() already handles multisite by returning site-specific path.
		$index_file = self::get_index_file_path();
		$dir        = dirname( $index_file );

		if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
			return;
		}

		// Get all PHP files in the site-specific snippets directory (excluding index.php).
		$files = glob( $dir . '/*.php' );
		if ( $files ) {
			foreach ( $files as $file ) {
				// Don't delete the index file here (we'll delete it separately).
				if ( basename( $file ) === 'index.php' ) {
					continue;
				}
				if ( is_file( $file ) ) {
					self::safe_unlink( $file );
				}
			}
		}

		// Delete index file.
		if ( file_exists( $index_file ) ) {
			self::safe_unlink( $index_file );
		}

		// Also delete metadata JSON files (for backward compatibility).
		$json_files = glob( $dir . '/*.json' );
		if ( $json_files ) {
			foreach ( $json_files as $json_file ) {
				if ( is_file( $json_file ) ) {
					self::safe_unlink( $json_file );
				}
			}
		}

		// In multisite, try to remove the site-specific directory if it's empty.
		if ( is_multisite() && is_dir( $dir ) ) {
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Get the index file path for the current site.
	 *
	 * @return string
	 */
	public static function get_index_file_path() {
		// In multisite, use site-specific directories.
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			if ( $blog_id > 0 ) {
				$dir = WP_CONTENT_DIR . '/wpcode/snippets/site-' . absint( $blog_id ) . '/';
			} else {
				$dir = WP_CONTENT_DIR . '/wpcode/snippets/';
			}
		} else {
			$dir = WP_CONTENT_DIR . '/wpcode/snippets/';
		}

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir . 'index.php';
	}

	/**
	 * Build the index file containing metadata for all active PHP snippets.
	 *
	 * @param bool $force Force build even if setting check fails (for bulk operations).
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function build_index_file( $force = false ) {
		$php_types  = array( 'php', 'html', 'universal' );
		$index_file = self::get_index_file_path();

		// Ensure directory exists before proceeding.
		$dir = dirname( $index_file );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		// When forced, skip all checks and build the index file.
		// Otherwise, check if file loading is enabled.
		if ( ! $force ) {
			// Ensure wpcode() is available for settings check.
			if ( ! function_exists( 'wpcode' ) || ! is_callable( array( wpcode(), 'settings' ) ) ) {
				return false;
			}

			$file_loading_enabled = wpcode()->settings->get_option( 'php_load_as_file', false );
			if ( ! $file_loading_enabled ) {
				// If setting is disabled, create empty index file.
				$file_content  = "<?php\n";
				$file_content .= "/**\n";
				$file_content .= " * WPCode Snippets Index File\n";
				$file_content .= " * This file contains metadata for all active PHP snippets loaded from files.\n";
				$file_content .= " * DO NOT EDIT THIS FILE DIRECTLY - It is auto-generated.\n";
				$file_content .= " */\n\n";
				$file_content .= '$wpcode_snippets_index = ' . var_export( wp_json_encode( array( 'snippets' => array() ) ), true ) . ";\n";
				$result        = self::safe_file_put_contents( $index_file, $file_content );
				if ( false !== $result && function_exists( 'opcache_invalidate' ) && file_exists( $index_file ) ) {
					opcache_invalidate( $index_file, true );
				}
				return false !== $result;
			}
		}

		// Get all active PHP execution type snippets.
		// Use get_posts with cache_results=false to ensure fresh data.
		$posts = get_posts(
			array(
				'post_type'        => 'wpcode',
				'posts_per_page'   => -1,
				'post_status'      => 'publish',
				'suppress_filters' => false,
				'cache_results'    => false, // Don't use cache to ensure fresh data.
				'no_found_rows'    => true, // Skip counting for performance.
			)
		);

		$snippets_data = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				// Pass post object directly to constructor for better data loading.
				$snippet   = new WPCode_Snippet( $post );
				$code_type = $snippet->get_code_type();

				// Ensure snippet has valid ID and post_data first.
				if ( ! $snippet->get_id() || ! $snippet->get_post_data() ) {
					continue;
				}

				// Only include active PHP execution types.
				// Check post_status directly as well since is_active() depends on post_data.
				if ( 'publish' !== $post->post_status || ! $snippet->is_active() ) {
					continue;
				}

				// Ensure code_type is valid and is a PHP execution type.
				if ( empty( $code_type ) || ! in_array( $code_type, $php_types, true ) ) {
					continue;
				}

				// Since file loading is enabled globally, all active PHP types should be included.
				// Get snippet metadata for caching.
				$metadata = $snippet->get_data_for_caching();

				// Validate metadata was retrieved and has required fields.
				if ( empty( $metadata ) || ! isset( $metadata['id'] ) || ! isset( $metadata['code_type'] ) ) {
					continue;
				}

				// Add file path to metadata.
				$metadata['file_path'] = self::get_snippet_file_name( $snippet );

				// Don't store code in index (it's in the snippet file).
				unset( $metadata['code'] );

				// Add active status and load_as_file flag.
				$metadata['active']       = true;
				$metadata['post_status']  = 'publish';
				$metadata['load_as_file'] = true;

				$snippets_data[] = $metadata;
			}
		}

		// Create JSON string with snippets data (even if empty array).
		$json_data = wp_json_encode( array( 'snippets' => $snippets_data ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		// If JSON encoding failed, still create an empty index file to ensure file exists.
		if ( false === $json_data ) {
			$json_data = wp_json_encode( array( 'snippets' => array() ) );
		}

		// Create PHP file content with JSON in a variable (as string).
		$file_content  = "<?php\n";
		$file_content .= "/**\n";
		$file_content .= " * WPCode Snippets Index File\n";
		$file_content .= " * This file contains metadata for all active PHP snippets loaded from files.\n";
		$file_content .= " * DO NOT EDIT THIS FILE DIRECTLY - It is auto-generated.\n";
		$file_content .= " */\n\n";
		// Store JSON as a string in PHP variable (easier to decode later).
		$file_content .= '$wpcode_snippets_index = ' . var_export( $json_data, true ) . ";\n";

		// Write to file (directory already checked/created earlier).
		$result = self::safe_file_put_contents( $index_file, $file_content );

		// Clear any opcode cache to ensure the new file is loaded.
		if ( false !== $result && function_exists( 'opcache_invalidate' ) && file_exists( $index_file ) ) {
			opcache_invalidate( $index_file, true );
		}

		return false !== $result;
	}
}

new WPCode_Snippets_File_Handler();
