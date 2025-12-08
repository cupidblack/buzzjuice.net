<?php
/**
 * WPCode_Admin_Bar_Info_Pro class.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Admin_Bar_Info_Pro.
 *
 * @extends WPCode_Admin_Bar_Info
 */
class WPCode_Admin_Bar_Info_Pro extends WPCode_Admin_Bar_Info {

	/**
	 * Array of snippets loaded through the page scripts custom snippets interface.
	 *
	 * @var array
	 */
	public $page_snippets = array();

	/**
	 * Class-specific hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();

		// When clicking on a page scripts section let's show it and scroll it into view.
		add_action( 'admin_head', array( $this, 'maybe_uncollapse_metabox_for_current_screen' ) );
		// Hide the wpcode-show parameter from the URL.
		add_filter( 'removable_query_args', array( $this, 'remove_query_arg_from_url' ) );
		// Add a JS variable to tell the JS script to scroll to the metabox.
		add_filter( 'wpcode_admin_global_js_data', array( $this, 'maybe_add_scroll_variable' ) );
	}

	/**
	 * If you load the metabox with out specific parameter, uncollapse the metabox so we can scroll to it.
	 *
	 * @return void
	 */
	public function maybe_uncollapse_metabox_for_current_screen() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) ) {
			return;
		}
		if ( isset( $_GET['wpcode-show'] ) ) {
			// If the user clicked on our special URL in the admin bar, force the metabox open to show their selection.
			add_filter(
				'get_user_option_closedpostboxes_' . $screen->id,
				array(
					$this,
					'remove_metabox_from_user_closed',
				)
			);
		}
	}

	/**
	 * Force our scripts metabox open.
	 *
	 * @param array $closed The closed metaboxes.
	 *
	 * @return array
	 */
	public function remove_metabox_from_user_closed( $closed ) {
		// Make sure it's an array.
		if ( ! is_array( $closed ) ) {
			$closed = array();
		}
		foreach ( $closed as $index => $closed_id ) {
			if ( 'wpcode-metabox-snippets' === $closed_id ) {
				unset( $closed[ $index ] );
			}
		}

		return $closed;
	}

	/**
	 * Remove the wpcode-show parameter from the URL.
	 *
	 * @param array $args The arguments that should be removed from the URL.
	 *
	 * @return array
	 */
	public function remove_query_arg_from_url( $args ) {
		$args[] = 'wpcode-show';

		return $args;
	}

	/**
	 * Add variable to the localization array so that the page is scrolled to the metabox when needed.
	 *
	 * @param array $vars The localization array.
	 *
	 * @return array
	 */
	public function maybe_add_scroll_variable( $vars ) {
		$vars['scroll_to_metabox'] = isset( $_GET['wpcode-show'] ) ? true : false;

		return $vars;
	}

	/**
	 * Add the WPCode info to the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 */
	public function add_admin_bar_info( $wp_admin_bar ) {
		$page_scripts_areas = $this->get_page_scripts_data();

		parent::add_admin_bar_info( $wp_admin_bar );

		if ( ! empty( $page_scripts_areas ) ) {
			// Calculate total by adding up the "count" property of each item.
			$total = array_sum( wp_list_pluck( $page_scripts_areas, 'count' ) );

			if ( ! empty( $this->page_snippets ) ) {
				$total += count( $this->page_snippets );
			}

			$wp_admin_bar->add_menu(
				array(
					'id'     => 'wpcode-page-scripts',
					'parent' => 'wpcode-admin-bar-info',
					'title'  => sprintf(
					// translators: %d is the total number of global scripts.
						esc_html__( 'Page Scripts (%d)', 'wpcode-premium' ),
						$total
					),
					'meta'   => array(
						'class' => 'wpcode-admin-bar-info-submenu',
					),
				)
			);

			foreach ( $page_scripts_areas as $id => $page_scripts_area ) {
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'wpcode-page-' . $id,
						'parent' => 'wpcode-page-scripts',
						'title'  => esc_html( $page_scripts_area['label'] ),
						'meta'   => array(
							'class' => 'wpcode-admin-bar-info-submenu',
						),
						'href'   => $page_scripts_area['href'],
					)
				);
			}

			if ( ! empty( $this->page_snippets ) ) {
				$total = count( $this->page_snippets );
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'wpcode-page-snippets',
						'parent' => 'wpcode-page-scripts',
						'title'  => sprintf(
						// translators: %d is the total number of global scripts.
							esc_html__( 'Custom Code Snippets (%d)', 'wpcode-premium' ),
							$total
						),
						'meta'   => array(
							'class' => 'wpcode-admin-bar-info-submenu',
						),
						'href'   => add_query_arg( 'wpcode-show', 'code', get_edit_post_link( get_the_ID() ) ),
					)
				);
				$locations = array();

				foreach ( $this->page_snippets as $page_snippet ) {
					// Let's split them up by location before displaying them.
					$snippet_location                             = $page_snippet['location'];
					$location_label                               = wpcode()->auto_insert->get_location_label( $snippet_location );
					$locations[ $snippet_location ]['label']      = $location_label;
					$locations[ $snippet_location ]['snippets'][] = $page_snippet['snippet_id'];
				}

				foreach ( $locations as $location => $location_data ) {
					$wp_admin_bar->add_menu(
						array(
							'id'     => 'wpcode-page-snippets-location-' . $location,
							'parent' => 'wpcode-page-snippets',
							'title'  => esc_html( $location_data['label'] ),
							'meta'   => array(
								'class' => 'wpcode-admin-bar-info-submenu',
							),
						)
					);

					foreach ( $location_data['snippets'] as $snippet_id ) {
						$wp_admin_bar->add_menu(
							array(
								'id'     => 'wpcode-page-snippets-snippet-' . $snippet_id,
								'parent' => 'wpcode-page-snippets-location-' . $location,
								'title'  => esc_html( get_the_title( $snippet_id ) ),
								'meta'   => array(
									'class' => 'wpcode-admin-bar-info-submenu',
								),
								'href'   => admin_url( 'admin.php?page=wpcode-snippet-manager&snippet_id=' . $snippet_id ),
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Get data related to the page scripts.
	 *
	 * @return array|void
	 */
	public function get_page_scripts_data() {
		if ( ! is_singular() ) {
			return array();
		}
		$post_id   = get_the_ID();
		$edit_link = get_edit_post_link( $post_id );
		if ( null === $edit_link ) {
			return array();
		}
		$locations = array(
			'header' => __( 'Header', 'wpcode-premium' ),
			'body'   => __( 'Body', 'wpcode-premium' ),
			'footer' => __( 'Footer', 'wpcode-premium' ),
		);

		$data = array();

		foreach ( $locations as $location => $label ) {
			// Let's see if this page has any page scripts set.
			$location_scripts = wpcode()->page_scripts->get_scripts( $location, $post_id );
			if ( ! empty( $location_scripts['disable_global'] ) ) {
				$this->global_disabled[] = $location;
			}
			unset( $location_scripts['disable_global'] );
			$current_count = 0;
			foreach ( $location_scripts as $location_script ) {
				if ( ! empty( $location_script ) ) {
					$current_count = 1;
				}
			}
			$data[ $location ] = array(
				'label' => $label . ' (' . $current_count . ')',
				'href'  => add_query_arg( 'wpcode-show', $location, $edit_link ),
				'count' => $current_count,
			);

		}

		$post_custom_snippets = get_post_meta( get_the_ID(), '_wpcode_page_snippets', true );

		if ( ! empty( $post_custom_snippets ) && is_array( $post_custom_snippets ) ) {
			$this->page_snippets = $post_custom_snippets;
		}

		return $data;
	}

	/**
	 * Get the snippet edit link based on the snippet id. This allows us to link to the conversion pixels page if the snippet id starts with 'pixel_'.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function get_snippet_edit_link( $snippet ) {
		$snippet_id = $snippet->get_id();
		// If the snippet id is a string and it starts with 'pixel_' let's point to the appropriate Conversion Pixels page.
		if ( is_string( $snippet_id ) && 0 === strpos( $snippet_id, 'pixel_' ) ) {
			// The second part after 'pixel_' is the name of the view in the conversion pixels settings page.
			$view = str_replace( 'pixel_', '', $snippet_id );

			return admin_url( 'admin.php?page=wpcode-pixel&view=' . $view );
		}

		return parent::get_snippet_edit_link( $snippet );
	}
}