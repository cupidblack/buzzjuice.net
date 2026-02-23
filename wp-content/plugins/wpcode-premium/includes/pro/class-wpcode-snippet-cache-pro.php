<?php
/**
 * Pro-specific snippet cache with file loading support.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Snippet_Cache_Pro.
 */
class WPCode_Snippet_Cache_Pro extends WPCode_Snippet_Cache {

	/**
	 * Get the snippets data from the cache.
	 * Override to add file-based snippet loading for PHP types.
	 *
	 * @return array
	 */
	public function get_cached_snippets() {
		if ( ! isset( $this->snippets ) ) {
			// Get snippets from database cache (for asset types).
			$db_snippets = $this->get_snippets_from_db_cache();

			// If file loading is enabled, merge with file-based snippets.
			if ( $this->should_use_file_cache() ) {
				$file_snippets = $this->get_snippets_from_file_index();
				// Merge file snippets with database snippets by location.
				$this->snippets = $this->merge_snippet_caches( $db_snippets, $file_snippets );
			} else {
				// Use database cache only.
				$this->snippets = $db_snippets;
			}
		}

		return $this->snippets;
	}

	/**
	 * Check if file-based cache should be used for saving metadata files.
	 *
	 * @return bool
	 */
	protected function should_use_file_cache() {
		// When building cache, we should NOT use file cache (even if enabled).
		// We need to load from database to build both caches properly.
		$is_building_cache = has_filter( 'wpcode_use_auto_insert_cache', '__return_false' );
		if ( $is_building_cache ) {
			return false;
		}

		// When testing mode is enabled, don't use file cache.
		// We need to load snippets from testing mode data instead.
		if ( wpcode_testing_mode_enabled() ) {
			return false;
		}

		// Check if the file handler class exists (premium feature).
		if ( ! class_exists( 'WPCode_Snippets_File_Handler' ) ) {
			return false;
		}

		// Check if the global setting for PHP file loading is enabled.
		return wpcode()->settings->get_option( 'php_load_as_file', false );
	}

	/**
	 * Get snippets from file index.
	 *
	 * @return array
	 */
	protected function get_snippets_from_file_index() {
		if ( ! class_exists( 'WPCode_Snippets_File_Handler' ) ) {
			return array();
		}

		$index_file = WPCode_Snippets_File_Handler::get_index_file_path();

		// If index file doesn't exist, return empty array.
		if ( ! file_exists( $index_file ) ) {
			return array();
		}

		// Include the index file to get $wpcode_snippets_index variable.
		$wpcode_snippets_index = '';
		include $index_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath

		// If variable is empty or not set, return empty array.
		if ( empty( $wpcode_snippets_index ) ) {
			return array();
		}

		// Decode JSON.
		$snippets_data = json_decode( $wpcode_snippets_index, true );

		// Validate decoded data.
		if ( ! is_array( $snippets_data ) || ! isset( $snippets_data['snippets'] ) || ! is_array( $snippets_data['snippets'] ) ) {
			return array();
		}

		// Group snippets by location.
		$snippets_by_location = array();

		foreach ( $snippets_data['snippets'] as $snippet_data ) {
			// Validate snippet data has required fields.
			if ( ! isset( $snippet_data['id'] ) || ! isset( $snippet_data['location'] ) ) {
				continue;
			}

			// Create snippet object from array data.
			$snippet = $this->load_snippet( $snippet_data );

			// Get location (can be array or string).
			$locations = is_array( $snippet_data['location'] ) ? $snippet_data['location'] : array( $snippet_data['location'] );

			// Add snippet to each location.
			foreach ( $locations as $location ) {
				if ( ! isset( $snippets_by_location[ $location ] ) ) {
					$snippets_by_location[ $location ] = array();
				}
				$snippets_by_location[ $location ][] = $snippet;
			}
		}

		// Sort snippets by priority within each location.
		foreach ( $snippets_by_location as $location => $snippets ) {
			usort( $snippets_by_location[ $location ], array( $this, 'priority_order' ) );
		}

		return $snippets_by_location;
	}

	/**
	 * Merge database cache and file index cache.
	 *
	 * @param array $db_snippets Snippets from database cache.
	 * @param array $file_snippets Snippets from file index.
	 *
	 * @return array Merged snippets by location.
	 */
	protected function merge_snippet_caches( $db_snippets, $file_snippets ) {
		$merged = $db_snippets;

		// Merge file snippets into database snippets by location.
		foreach ( $file_snippets as $location => $snippets ) {
			if ( ! isset( $merged[ $location ] ) ) {
				$merged[ $location ] = array();
			}

			// Add file snippets to the location.
			// Overwrite $merged data by $snippets, but only those having the same snippet ID
			foreach ( $snippets as $snippet ) {
				$snippet_id = is_object( $snippet ) && method_exists( $snippet, 'get_id' ) ? $snippet->get_id() : ( isset( $snippet->id ) ? $snippet->id : null );
				if ( null === $snippet_id ) {
					continue;
				}
				foreach ( $merged[ $location ] as $key => $merged_snippet ) {
					$merged_snippet_id = is_object( $merged_snippet ) && method_exists( $merged_snippet, 'get_id' ) ? $merged_snippet->get_id() : ( isset( $merged_snippet->id ) ? $merged_snippet->id : null );
					if ( $merged_snippet_id === $snippet_id ) {
						// Preserve the code property from the database snippet if file snippet doesn't have it.
						// File-based snippets don't have code in the index, but we need it for compatibility.
						if ( empty( $snippet->code ) && isset( $merged_snippet->code ) ) {
							$snippet->code = $merged_snippet->code;
						}
						$merged[ $location ][ $key ] = $snippet;
						// Once replaced, break inner loop.
						break;
					}
				}
			}

			// Sort by priority after merging.
			usort( $merged[ $location ], array( $this, 'priority_order' ) );
		}

		return $merged;
	}

	/**
	 * Save all the loaded snippets in a single option.
	 * Override to filter out PHP types when file loading is enabled.
	 *
	 * @return void
	 */
	public function cache_all_loaded_snippets() {
		if ( ! apply_filters( 'wpcode_cache_active_snippets', true ) ) {
			return;
		}
		$auto_inserts         = wpcode()->auto_insert->get_types();
		$snippets_by_location = array();
		foreach ( $auto_inserts as $auto_insert ) {
			// We don't want to use cached data when gathering stuff for cache.
			add_filter( 'wpcode_use_auto_insert_cache', '__return_false' );
			// Make sure snippets were not already loaded by earlier hooks.
			unset( $auto_insert->snippets );
			$snippets_by_location = array_merge( $auto_insert->get_snippets(), $snippets_by_location );
		}

		// Filter out PHP execution types if file loading is enabled.
		$php_types = array( 'php', 'html', 'universal' );

		if ( $this->should_use_file_cache() ) {
			// Remove PHP execution types from cache (they're loaded from files).
			foreach ( $snippets_by_location as $location => $snippets ) {
				$snippets_by_location[ $location ] = array_filter(
					$snippets,
					function( $snippet ) use ( $php_types ) {
						return ! in_array( $snippet->get_code_type(), $php_types, true );
					}
				);
			}
		}

		$data_for_cache = array();
		foreach ( $snippets_by_location as $location => $snippets ) {
			if ( empty( $snippets ) ) {
				continue;
			}
			$data_for_cache[ $location ] = $this->prepare_snippets_for_caching( $snippets );
		}

		// Save to database cache (only asset types when file loading is enabled).
		$this->update_option( $data_for_cache );
	}
}
