<?php
/**
 * This class handles apply the access management rules available in the pro plugin.
 *
 * @package wpcode
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle access management rule applying.
 */
class WPCode_Access_Logic {

	/**
	 * Add hooks.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'check_code_types' ), 5 );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 20, 4 );
	}

	/**
	 * Allow using a constant to completely disable PHP snippets & execution.
	 *
	 * @return void
	 */
	public function check_code_types() {
		$types = wpcode()->execute->get_types();

		// Filter out snippets you can't edit based on the settings.
		add_filter( 'wpcode_code_snippets_table_prepare_items_args', array( $this, 'exclude_code_types' ) );

		// Filter out code types in the admin dropdown.
		add_filter( 'wpcode_code_type_options', array( $this, 'exclude_options' ) );
		add_filter( 'wpcode_code_types_for_display', array( $this, 'exclude_options' ) );

		add_filter( 'wpcode_code_snippets_table_update_count_all', array( $this, 'maybe_update_counts' ), 10, 2 );

		if ( WPCode_Access::php_disabled() ) {
			if ( isset( $types['php'] ) ) {
				unset( wpcode()->execute->types['php'] );
			}
			if ( isset( $types['universal'] ) ) {
				unset( wpcode()->execute->types['universal'] );
			}

			// Add action to prevent access to a code snippet that is PHP or Universal.
			add_action( 'admin_init', array( $this, 'prevent_access' ) );
			// Don't show the library when adding a new snippet if PHP is disabled.
			add_filter( 'wpcode_add_snippet_show_library', '__return_false' );
			// Disable the library & generator pages completely.
			add_action( 'wpcode_before_admin_pages_loaded', array( $this, 'remove_admin_pages' ) );
			// Disable php-specific locations.
			$this->unregister_php_locations();
			// Prevent loading any type of snippet with php or universal code types.
			add_filter( 'wpcode_get_snippets_for_location', array( $this, 'prevent_loading' ), 9999, 2 );
		}

		if ( ! $this->user_can( 'wpcode_edit_php_snippets' ) ) {
			// Don't show the library when adding a new snippet if PHP is disabled.
			add_filter( 'wpcode_add_snippet_show_library', '__return_false' );
		}
	}

	/**
	 * Check if the current user has a custom capability based on its role.
	 *
	 * @param string $custom_capability The custom wpcode capability to check for.
	 *
	 * @return bool
	 */
	public function user_can( $custom_capability, $user_id = null ) {

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// If they can activate snippets they are admins and should have access to everything.
		if ( user_can( $user_id, 'wpcode_activate_snippets' ) ) {
			return true;
		}

		if ( ! isset( wpcode()->settings ) ) {
			return false;
		}

		if ( 0 === strpos( $custom_capability, 'wpcode_edit_' ) && 'wpcode_edit_php_snippets' !== $custom_capability && $this->user_can( 'wpcode_edit_php_snippets', $user_id ) ) {
			return true;
		}

		if ( 'wpcode_edit_text_snippets' === $custom_capability && $this->user_can( 'wpcode_edit_html_snippets', $user_id ) ) {
			return true;
		}

		$roles = wpcode()->settings->get_option( $custom_capability );
		if ( empty( $roles ) || ! is_array( $roles ) ) {
			return false;
		}
		foreach ( $roles as $role ) {
			if ( user_can( $user_id, $role ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Exclude PHP and Universal code type snippets by changing the query args in the list of snippets in the admin.
	 *
	 * @param array $query_args The query args for the snippets table.
	 *
	 * @return array
	 */
	public function exclude_code_types( $query_args ) {

		$code_types = array();
		if ( WPCode_Access::php_disabled() || ! $this->user_can( 'wpcode_edit_php_snippets' ) ) {
			$code_types = array( 'php', 'universal' );
		}
		if ( ! $this->user_can( 'wpcode_edit_html_snippets' ) ) {
			$code_types[] = 'html';
			$code_types[] = 'js';
			$code_types[] = 'css';
			$code_types[] = 'scss';
		}

		// If no code types are excluded, return the original query args.
		if ( empty( $code_types ) ) {
			return $query_args;
		}

		if ( ! isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		$query_args['tax_query'][] = array(
			'taxonomy' => 'wpcode_type',
			'terms'    => $code_types,
			'field'    => 'slug',
			'operator' => 'NOT IN',
		);

		return $query_args;
	}

	/**
	 * Filter out the locations based on the current user's capabilities in the admin.
	 *
	 * @param array $options The options for the code type dropdown.
	 *
	 * @return array
	 */
	public function exclude_options( $options ) {
		foreach ( $options as $code_type => $label ) {
			$capability_needed = WPCode_Access::capability_for_code_type( $code_type );
			if ( ! $this->user_can( $capability_needed ) ) {
				unset( $options[ $code_type ] );
			}
		}


		return $options;
	}

	/**
	 * Prevent access to a code snippet that is PHP or Universal.
	 *
	 * @return void
	 */
	public function prevent_access() {
		if ( ! isset( $_GET['page'] ) || 'wpcode-snippet-manager' !== $_GET['page'] ) {
			return;
		}

		$code_types = array( 'php', 'universal' );

		$snippet_id = isset( $_GET['snippet_id'] ) ? absint( $_GET['snippet_id'] ) : 0;
		if ( ! $snippet_id ) {
			return;
		}

		$snippet = new WPCode_Snippet( $snippet_id );
		if ( empty( $snippet->post_data ) ) {
			// We couldn't get the data or it's the wrong post type.
			return;
		}

		$snippet_code_type = $snippet->get_code_type();
		if ( ! in_array( $snippet_code_type, $code_types, true ) ) {
			return;
		}

		// If it's in the code types not allowed, die with a message.
		wp_die( esc_html__( 'PHP Snippets have been disabled on this website.', 'wpcode-premium' ) );
	}

	/**
	 * Remove the library and generator pages from the admin.
	 *
	 * @param array $pages The pages to load.
	 *
	 * @return void
	 */
	public function remove_admin_pages( $pages ) {
		if ( isset( $pages['library'] ) ) {
			unset( wpcode()->admin_page_loader->pages['library'] );
		}
		if ( isset( $pages['generator'] ) ) {
			unset( wpcode()->admin_page_loader->pages['generator'] );
		}
	}

	/**
	 * Remove the php-specific locations from the auto-insert options.
	 *
	 * @return void
	 */
	public function unregister_php_locations() {
		$types           = wpcode()->auto_insert->types;
		$type_categories = wpcode()->auto_insert->type_categories;

		foreach ( $types as $type_key => $type ) {
			if ( 'php' === $type->code_type ) {
				unset( wpcode()->auto_insert->types[ $type_key ] );
			}
		}
		foreach ( $type_categories as $type_category_key => $type_category ) {
			foreach ( $type_category['types'] as $type_key => $type ) {
				if ( 'php' === $type->code_type ) {
					unset( wpcode()->auto_insert->type_categories[ $type_category_key ]['types'][ $type_key ] );
				}
			}
		}
	}

	/**
	 * Prevent the snippets from auto-loading if they have PHP or Universal code types.
	 *
	 * @param WPCode_Snippet[] $snippets The snippets loaded for the location.
	 *
	 * @return array
	 */
	public function prevent_loading( $snippets ) {
		foreach ( $snippets as $snippet_key => $snippet ) {
			if ( in_array( $snippet->get_code_type(), array( 'php', 'universal' ), true ) ) {
				unset( $snippets[ $snippet_key ] );
			}
		}

		return $snippets;
	}

	/**
	 * Map meta capabilities based on the settings.
	 *
	 * @param string[] $caps Primitive capabilities required of the user.
	 * @param string   $cap Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args Adds context to the capability check, typically
	 *                          starting with an object ID.
	 *
	 * @return array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		if ( 'wpcode_edit_snippets' === $cap ) {
			// If this is called very early by the testing mode let's return early to avoid fatal errors.
			if ( is_null( wpcode()->settings ) ) {
				return $caps;
			}
			// Let's allow this user to see the snippets menus if they can edit snippets.
			$roles       = array();
			$capabilites = wpcode_custom_capabilities_keys();
			// Filter just the capabilities that start with wpcode_edit.
			$capabilites = array_filter(
				$capabilites,
				function ( $capability ) {
					return 0 === strpos( $capability, 'wpcode_edit' );
				}
			);
			foreach ( $capabilites as $capability ) {
				$capability_roles = wpcode()->settings->get_option( $capability, array() );
				if ( ! empty( $capability_roles ) && is_array( $capability_roles ) ) {
					$roles = array_merge( $roles, $capability_roles );
				}
				if ( $this->user_has_role_in_roles( $roles, $user_id ) ) {
					return array();
				}
			}
		}

		if ( 'edit_post' === $cap ) {
			// Let's see if this is for a snippet.
			if ( ! empty( $args[0] ) && 'wpcode' === get_post_type( $args[0] ) ) {
				// Let's check the code type.
				$snippet                  = new WPCode_Snippet( $args[0] );
				$code_type                = $snippet->get_code_type();
				$custom_capability_needed = WPCode_Access::capability_for_code_type( $code_type );
				if ( $this->user_can( $custom_capability_needed, $user_id ) ) {
					return array();
				}
			}
		}

		$blocks_capabilities = array(
			'edit_wpcodeblock',
			'read_wpcodeblock',
			'delete_wpcodeblock',
			'edit_wpcodeblocks',
			'edit_others_wpcodeblocks',
			'delete_wpcodeblocks',
			'publish_wpcodeblocks',
			'read_private_wpcodeblocks',
		);

		if ( in_array( $cap, $blocks_capabilities, true ) ) {
			$custom_capability_needed = WPCode_Access::capability_for_code_type( 'blocks' );
			if ( ! empty( $custom_capability_needed ) ) {
				$capability_roles = wpcode()->settings->get_option( $custom_capability_needed, array() );
				if ( $this->user_has_role_in_roles( $capability_roles, $user_id ) ) {
					return array();
				}
			}

			return array( 'wpcode_edit_snippets' );
		}

		if ( 'unfiltered_html' === $cap ) {
			if ( $this->user_can( 'wpcode_edit_php_snippets', $user_id ) || $this->user_can( 'wpcode_edit_html_snippets', $user_id ) ) {
				return array();
			}
			// Let's see if the wpcode-editor is sent as an arg.
			if ( ! empty( $args[0] ) && 'wpcode-editor' === $args[0] ) {
				// Let's override this if the current user has been enabled to edit HTML or PHP snippets.
				if ( $this->user_can( 'wpcode_edit_html_snippets', $user_id ) ) {
					return array();
				}
			}
		}

		if ( 'wpcode_activate_snippets' === $cap ) {
			// Let's see if a snippet is passed and then based on the code type check if the user can activate it.
			if ( ! empty( $args[0] ) && is_a( $args[0], 'WPCode_Snippet' ) ) {
				$snippet                  = $args[0];
				$code_type                = $snippet->get_code_type();
				$custom_capability_needed = WPCode_Access::capability_for_code_type( $code_type );
				if ( ! empty( $custom_capability_needed ) ) {
					$capability_roles = wpcode()->settings->get_option( $custom_capability_needed, array() );
					if ( $this->user_has_role_in_roles( $capability_roles, $user_id ) ) {
						return array();
					}
				}
			}
		}

		$custom_caps = wpcode_custom_capabilities_keys();

		if ( in_array( $cap, $custom_caps, true ) && $this->user_can( $cap, $user_id ) ) {
			return array();
		}

		return $caps;
	}

	/**
	 * Check if a user has a role in a set of roles.
	 *
	 * @param array $roles Roles to check in.
	 * @param int   $user_id The user ID to check.
	 *
	 * @return bool
	 */
	protected function user_has_role_in_roles( $roles, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}
		foreach ( $roles as $role ) {
			if ( user_can( $user_id, $role ) ) {
				return true;
			}
		}

		return false;
	}

	public function maybe_update_counts( $counts, $args ) {
		if ( ! empty( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) {
			$use_custom_counts = false;
			// Let's see if the query excludes some code types.
			foreach ( $args['tax_query'] as $tax_query ) {
				if ( ! empty( $tax_query['taxonomy'] ) && 'wpcode_type' === $tax_query['taxonomy'] && isset( $tax_query['operator'] ) && 'NOT IN' === $tax_query['operator'] ) {
					$use_custom_counts = true;
					break;
				}
			}

			if ( $use_custom_counts ) {
				// We need to use the args to run queries and get the counts for each status, all, publish, draft, trash.
				$custom_counts    = array();
				$publish_args     = $args;
				$publish_args     = array_merge(
					$publish_args,
					array(
						'post_status' => 'publish',
						'fields'      => 'ids',
					)
				);
				$publish_query    = new WP_Query( $publish_args );
				$counts['active'] = $publish_query->found_posts;

				$inactive_args      = $args;
				$inactive_args      = array_merge(
					$inactive_args,
					array(
						'post_status' => 'draft',
						'fields'      => 'ids',
					)
				);
				$inactive_query     = new WP_Query( $inactive_args );
				$counts['inactive'] = $inactive_query->found_posts;
				$counts['all']      = $counts['active'] + $counts['inactive'];

				$trash_args      = $args;
				$trash_args      = array_merge(
					$trash_args,
					array(
						'post_status' => 'trash',
						'fields'      => 'ids',
					)
				);
				$trash_query     = new WP_Query( $trash_args );
				$counts['trash'] = $trash_query->found_posts;
			}
		}

		return $counts;
	}

}

new WPCode_Access_Logic();
