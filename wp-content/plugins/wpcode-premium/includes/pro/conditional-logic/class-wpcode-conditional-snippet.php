<?php
/**
 * Class that handles conditional logic for snippets type
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_Snippet class.
 */
class WPCode_Conditional_Snippet extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'snippet';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'advanced';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = __( 'Snippet', 'wpcode-premium' );
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'snippet_loaded' => array(
				'callback' => array( $this, 'get_loaded_snippets' ),
			),
		);
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		$this->options = array(
			'snippet_loaded' => array(
				'label'           => __( 'WPCode Snippet', 'wpcode-premium' ),
				'description'     => __( 'Load this snippet based on another snippet being loaded.', 'wpcode-premium' ),
				'type'            => 'ajax',
				'options'         => 'wpcode_search_snippets',
				'callback'        => array( $this, 'get_loaded_snippets' ),
				'labels_callback' => array( $this, 'get_snippets_labels' ),
				'operator_labels' => array(
					'='  => __( 'Is loaded', 'wpcode-premium' ),
					'!=' => __( 'Is not loaded', 'wpcode-premium' ),
				),
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['snippet_loaded']['upgrade'] = array(
					'title'  => __( 'Snippet Loaded Rules are a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
			}
		}
	}

	/**
	 * Get all the snippets loaded on the page.
	 *
	 * @param WPCode_Snippet $loaded_snippet The snippet we are evaluating the rules for.
	 *
	 * @return array
	 */
	public function get_loaded_snippets( $loaded_snippet ) {
		// Get all snippets from the cache.
		$snippets        = wpcode()->cache->get_cached_snippets();
		$snippets_loaded = array();
		foreach ( $snippets as $location => $snippets_for_location ) {
			foreach ( $snippets_for_location as $snippet ) {
				/**
				 * Added for convenience.
				 *
				 * @var $snippet WPCode_Snippet
				 */
				if ( $loaded_snippet->get_id() === $snippet->get_id() ) {
					continue;
				}
				if ( wpcode()->conditional_logic->are_snippet_rules_met( $snippet ) ) {
					$snippets_loaded[] = $snippet->get_id();
				}
			}
		}

		return $snippets_loaded;
	}

	/**
	 * Get the label to display selected snippet in the admin.
	 *
	 * @param int|string $values The snippet ids.
	 *
	 * @return array
	 */
	public function get_snippets_labels( $snippet_id ) {
		$labels = array(
			array(
				'value' => $snippet_id,
				'label' => get_the_title( $snippet_id ),
			),
		);

		return $labels;
	}
}

new WPCode_Conditional_Snippet();
