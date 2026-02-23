<?php
/**
 * Class that handles conditional logic related to users.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_User class.
 */
class WPCode_Conditional_User_Pro extends WPCode_Conditional_User {

	use WPCode_Conditional_Meta;

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'user';

	/**
	 * The category of this type.
	 *
	 * @var string
	 */
	public $category = 'who';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = __( 'User', 'insert-headers-and-footers' );
	}

	/**
	 * Evaluate a rule row with meta option.
	 *
	 * @param array          $rule_row An array of rules with keys option, meta_key/user_meta_key, relation, and value.
	 * @param WPCode_Snippet $snippet The snippet we are evaluating the rules for.
	 *
	 * @return bool
	 */
	public function evaluate_rule_row( $rule_row, $snippet ) {
		if ( 'user_meta' === $rule_row['option'] ) {
			$meta_key = $rule_row['user_meta_key'];
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

		// Add callback for user meta in Pro version.
		$this->options['user_meta']['callback'] = array( $this, 'get_user_meta' );
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		parent::load_type_options();

		$this->options['user_meta'] = array(
			'label'       => __( 'User Meta', 'wpcode-premium' ),
			'description' => __( 'Target users based on user meta values.', 'wpcode-premium' ),
			'type'        => 'text',
		);
	}
}

new WPCode_Conditional_User_Pro();
