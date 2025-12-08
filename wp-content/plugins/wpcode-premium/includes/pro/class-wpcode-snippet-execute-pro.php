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
