<?php
/**
 * Class that handles conditional logic related to Easy Digital Downloads.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_EDD class.
 */
class WPCode_Conditional_EDD extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'edd';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'ecommerce';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = 'Easy Digital Downloads';
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'edd_page' => array(
				'callback' => array( $this, 'get_page_type' ),
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
			'edd_page' => array(
				'label'       => __( 'EDD Page', 'wpcode-premium' ),
				'description' => __( 'Load the snippet on specific Easy Digital Downloads pages.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(
					array(
						'label' => __( 'Checkout Page', 'wpcode-premium' ),
						'value' => 'checkout',
					),
					array(
						'label' => __( 'Confirmation Page', 'wpcode-premium' ),
						'value' => 'success_page',
					),
					array(
						'label' => __( 'Single Download Page', 'wpcode-premium' ),
						'value' => 'download_page',
					),
					array(
						'label' => __( 'Download Category Page', 'wpcode-premium' ),
						'value' => 'download_category',
					),
					array(
						'label' => __( 'Download Tag Page', 'wpcode-premium' ),
						'value' => 'download_tag',
					),
				),
				'callback'    => array( $this, 'get_page_type' ),
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['edd_page']['upgrade'] = array(
					'title'  => __( 'Easy Digital Downloads Page Rules is a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
			} elseif ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
				$this->options['edd_page']['upgrade'] = array(
					'title'  => __( 'Easy Digital Downloads is Not Installed', 'wpcode-premium' ),
					'text'   => __( 'Please install and activate Easy Digital Downloads to use this feature.', 'wpcode-premium' ),
					'link'   => admin_url( 'plugin-install.php?s=easy+digital+downloads&tab=search&type=term' ),
					'button' => __( 'Install Easy Digital Downloads Now', 'wpcode-premium' ),
				);
				$this->set_label();// Reset label.
				$this->label = $this->label . __( ' (Not Installed)', 'wpcode-premium' );
			}
		}
	}

	/**
	 * Get the WooCommerce page type.
	 *
	 * @return string
	 */
	public function get_page_type() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return '';
		}
		if ( edd_is_checkout() ) {
			return 'checkout';
		}
		if ( edd_is_success_page() ) {
			return 'success_page';
		}
		if ( is_singular( 'download' ) ) {
			return 'download_page';
		}
		if ( is_tax( 'download_category' ) ) {
			return 'download_category';
		}
		if ( is_tax( 'download_tag' ) ) {
			return 'download_tag';
		}

		return '';
	}
}

new WPCode_Conditional_EDD();
