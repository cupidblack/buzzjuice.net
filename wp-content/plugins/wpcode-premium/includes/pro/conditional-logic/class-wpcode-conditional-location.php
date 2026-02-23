<?php
/**
 * Class that handles conditional logic based on location.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The WPCode_Conditional_Location class.
 */
class WPCode_Conditional_Location_Pro extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'location';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'who';

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		// Location addon defines its own callbacks when loaded.
		$this->options = array(
			'country'   => array(),
			'continent' => array(),
		);
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		$this->options = array(
			'country'   => array(
				'label'       => __( 'Country', 'wpcode-location' ),
				'description' => __( 'Limit loading the snippet based on the visitor\'s country.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(),
				'multiple'    => true,
			),
			'continent' => array(
				'label'       => __( 'Continent', 'wpcode-premium' ),
				'description' => __( 'Target entire continents with ease.', 'wpcode-premium' ),
				'type'        => 'select',
				'multiple'    => true,
				'options'     => array(),
			),
		);

		if ( is_admin() ) {
			$title  = __( 'Location Rules are a Pro Feature', 'wpcode-premium' );
			$text   = __( 'The WPCode Location addon is not available on your current plan. Upgrade to Pro or higher level today and get access to the WPCode Location addon.', 'wpcode-premium' );
			$link   = wpcode_utm_url( 'https://library.wpcode.com/account/downloads/', 'conditional-logic', 'location' );
			$button = __( 'Upgrade Now', 'wpcode-premium' );

			if ( wpcode()->license->license_can( 'pro' ) ) {
				// We need to install or activate the addon.
				$title  = __( 'The WPCode Location Addon is not Active', 'wpcode-premium' );
				$text   = __( 'The WPCode Location addon that is included in your plan is required in order to use this feature.', 'wpcode-premium' );
				$link   = 'wpcode-location';
				$button = __( 'Activate Addon', 'wpcode-premium' );
			}

			foreach ( $this->options as $key => $options ) {
				$this->options[ $key ]['upgrade'] = array(
					'title'  => $title,
					'text'   => $text,
					'link'   => $link,
					'button' => $button,
				);
			}
		}
	}

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = __( 'Location', 'wpcode-premium' ) . ' ' . _x( '(Addon)', 'The addon is not active.', 'wpcode-premium' );
	}
}

new WPCode_Conditional_Location_Pro();
