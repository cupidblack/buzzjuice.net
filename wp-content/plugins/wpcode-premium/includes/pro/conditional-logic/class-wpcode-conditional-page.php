<?php
/**
 * Class that handles conditional logic related to pages.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_Page class.
 */
class WPCode_Conditional_Page_Pro extends WPCode_Conditional_Page {

	use WPCode_Conditional_Meta;

	/**
	 * Evaluate a rule row with meta option.
	 *
	 * @param array          $rule_row An array of rules with keys option, meta_key/user_meta_key, relation, and value.
	 * @param WPCode_Snippet $snippet The snippet we are evaluating the rules for.
	 *
	 * @return bool
	 */
	public function evaluate_rule_row( $rule_row, $snippet ) {
		if ( 'post_meta' === $rule_row['option'] ) {
			$meta_key = $rule_row['meta_key'];
			return $this->evaluate_meta_rule( $rule_row['option'], $meta_key, $rule_row['relation'], $rule_row['value'] );
		}
		return $this->evaluate_rule( $rule_row['option'], $rule_row['relation'], $rule_row['value'], $snippet );
	}
	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		parent::load_evaluation_options();

		// Add callbacks for Pro features.
		$this->options['post_id']['callback']       = array( $this, 'get_post_id' );
		$this->options['page_template']['callback'] = array( $this, 'get_page_template' );
		$this->options['post_author']['callback']   = array( $this, 'get_post_author' );
		$this->options['post_meta']['callback']     = array( $this, 'get_post_meta' );
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		parent::load_type_options();

		$this->options['post_id'] = array(
			'label'           => __( 'Post/Page', 'wpcode-premium' ),
			'description'     => __( 'Pick specific posts or pages to load the snippet on.', 'wpcode-premium' ),
			'type'            => 'ajax',
			'options'         => 'wpcode_search_posts',
			'callback'        => array( $this, 'get_post_id' ),
			'labels_callback' => array( $this, 'get_posts_labels' ),
			'multiple'        => true,
			'operator_labels' => array(
				'='  => __( 'Is one of', 'wpcode-premium' ),
				'!=' => __( 'Is not one of', 'wpcode-premium' ),
			),
		);

		$page_templates        = wp_get_theme()->get_page_templates();
		$page_template_options = array();
		foreach ( $page_templates as $file => $name ) {
			$page_template_options[] = array(
				'value' => $file,
				'label' => $name,
			);
		}

		$this->options['page_template'] = array(
			'label'           => __( 'Page Template', 'wpcode-premium' ),
			'description'     => __( 'Load the snippet only on pages with a specific template.', 'insert-headers-and-footers' ),
			'type'            => 'select',
			'options'         => $page_template_options,
			'callback'        => array( $this, 'get_page_template' ),
			'multiple'        => true,
			'operator_labels' => array(
				'='  => __( 'Is one of', 'wpcode-premium' ),
				'!=' => __( 'Is not one of', 'wpcode-premium' ),
			),
		);

		$this->options['post_author'] = array(
			'label'           => __( 'Author', 'wpcode-premium' ),
			'description'     => __( 'Load the snippet only on pages with a specific author.', 'wpcode-premium' ),
			'type'            => 'ajax',
			'options'         => 'wpcode_search_users',
			'callback'        => array( $this, 'get_post_author' ),
			'labels_callback' => array( $this, 'get_users_labels' ),
			'multiple'        => true,
			'operator_labels' => array(
				'='  => __( 'Is one of', 'wpcode-premium' ),
				'!=' => __( 'Is not one of', 'wpcode-premium' ),
			),
		);

		$this->options['post_meta'] = array(
			'label'       => __( 'Post Meta', 'wpcode-premium' ),
			'description' => __( 'Load the snippet only on pages with a specific post meta value.', 'wpcode-premium' ),
			'type'        => 'text',
		);

		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['post_id']['upgrade']       = array(
					'title'  => __( 'Post specific rules are a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
				$this->options['page_template']['upgrade'] = array(
					'title'  => __( 'Page Template rules are a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
				$this->options['post_author']['upgrade']   = array(
					'title'  => __( 'Post Author rules are a Pro Feature', 'wpcode-premium' ),
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
	 * Get the post id.
	 *
	 * @return false|int
	 */
	public function get_post_id() {
		return get_the_ID();
	}

	/**
	 * Get the titles of the selected posts for this condition.
	 *
	 * @param [int] $values The post ids.
	 *
	 * @return array
	 */
	public function get_posts_labels( $values ) {
		$labels = array();
		foreach ( $values as $post_id ) {
			$labels[] = array(
				'value' => $post_id,
				'label' => get_the_title( $post_id ),
			);
		}

		return $labels;
	}

	/**
	 * Get the labels for the users.
	 *
	 * @param [int] $values The user ids.
	 *
	 * @return array
	 */
	public function get_users_labels( $values ) {
		$labels = array();
		foreach ( $values as $user_id ) {
			$user     = get_user_by( 'ID', $user_id );
			$labels[] = array(
				'value' => $user_id,
				'label' => $user->display_name,
			);
		}

		return $labels;
	}

	/**
	 * Get the page template.
	 *
	 * @return string
	 */
	public function get_page_template() {
		return get_page_template_slug();
	}

	/**
	 * Get the post author.
	 *
	 * @return string
	 */
	public function get_post_author() {
		global $wp_query;
		if ( is_null( $wp_query ) ) {
			return '';
		}
		$queried_object = get_queried_object();

		return isset( $queried_object->post_author ) ? $queried_object->post_author : '';
	}
}

new WPCode_Conditional_Page_Pro();
