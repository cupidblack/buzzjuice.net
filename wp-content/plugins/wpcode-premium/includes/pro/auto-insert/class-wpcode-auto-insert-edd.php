<?php
/**
 * Easy Digital Downloads specific auto-insert locations.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Auto_Insert_EDD.
 */
class WPCode_Auto_Insert_EDD extends WPCode_Auto_Insert_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'edd';
	/**
	 * The category of this type.
	 *
	 * @var string
	 */
	public $category = 'ecommerce';

	/**
	 * Load the available options and labels.
	 *
	 * @return void
	 */
	public function init() {
		$this->locations = array(
			'edd_purchase_link_top'       => array(),
			'edd_purchase_link_end'       => array(),
			'edd_before_download_content' => array(),
			'edd_after_download_content'  => array(),
			'edd_before_cart'             => array(),
			'edd_after_cart'              => array(),
			'edd_before_checkout_cart'    => array(),
			'edd_after_checkout_cart'     => array(),
			'edd_before_purchase_form'    => array(),
			'edd_after_purchase_form'     => array(),
		);
	}

	/**
	 * Load the label for this type.
	 *
	 * @return void
	 */
	public function load_label() {
		$this->label = 'Easy Digital Downloads';
	}

	public function load_locations() {
		$this->locations = array(
			'edd_purchase_link_top'       => array(
				'label'       => __( 'Before the Purchase Button', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the EDD purchase button.', 'wpcode-premium' ),
			),
			'edd_purchase_link_end'       => array(
				'label'       => __( 'After the Purchase Button', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the EDD purchase button.', 'wpcode-premium' ),
			),
			'edd_before_download_content' => array(
				'label'       => __( 'Before the Single Download', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the single EDD download content.', 'wpcode-premium' ),
			),
			'edd_after_download_content'  => array(
				'label'       => __( 'After the Single Download', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the single EDD download content.', 'wpcode-premium' ),
			),
			'edd_before_cart'             => array(
				'label'       => __( 'Before the Cart', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the EDD cart.', 'wpcode-premium' ),
			),
			'edd_after_cart'              => array(
				'label'       => __( 'After the Cart', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the EDD cart.', 'wpcode-premium' ),
			),
			'edd_before_checkout_cart'    => array(
				'label'       => __( 'Before the Checkout Cart', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the EDD cart on the checkout page.', 'wpcode-premium' ),
			),
			'edd_after_checkout_cart'     => array(
				'label'       => __( 'After the Checkout Cart', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the EDD cart on the checkout page.', 'wpcode-premium' ),
			),
			'edd_before_purchase_form'    => array(
				'label'       => __( 'Before the Checkout Form', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the EDD checkout form on the checkout page.', 'wpcode-premium' ),
			),
			'edd_after_purchase_form'     => array(
				'label'       => __( 'After the Checkout Form', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the EDD checkout form on the checkout page', 'wpcode-premium' ),
			),
		);
	}

	/**
	 * Load the upgrade strings for this type.
	 *
	 * @return void
	 */
	public function load_upgrade_strings() {
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->label_pill     = 'PRO';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'Easy Digital Downloads Page Rules is a Pro Feature', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' );
				$this->upgrade_link   = add_query_arg(
					array(
						'page' => 'wpcode-settings',
					),
					admin_url( 'admin.php' )
				);
				$this->upgrade_button = __( 'Add License Key Now', 'wpcode-premium' );
			} elseif ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
				$this->label_pill     = 'Not Installed';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'Easy Digital Downloads is Not Installed', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please install and activate Easy Digital Downloads to use this feature.', 'wpcode-premium' );
				$this->upgrade_link   = admin_url( 'plugin-install.php?s=easy+digital+downloads&tab=search&type=term' );
				$this->upgrade_button = __( 'Install Easy Digital Downloads Now', 'wpcode-premium' );
			}
		}
	}

	/**
	 * WooCommerce-specific hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		// Could probably loop here, but it's easier to find hooked actions this way.
		add_action( 'edd_purchase_link_top', array( $this, 'edd_purchase_link_top' ) );
		add_action( 'edd_purchase_link_end', array( $this, 'edd_purchase_link_end' ) );
		add_action( 'edd_before_download_content', array( $this, 'edd_before_download_content' ) );
		add_action( 'edd_after_download_content', array( $this, 'edd_after_download_content' ) );
		add_action( 'edd_before_cart', array( $this, 'edd_before_cart' ) );
		add_action( 'edd_after_cart', array( $this, 'edd_after_cart' ) );
		add_action( 'edd_before_checkout_cart', array( $this, 'edd_before_checkout_cart' ) );
		add_action( 'edd_after_checkout_cart', array( $this, 'edd_after_checkout_cart' ) );
		add_action( 'edd_before_purchase_form', array( $this, 'edd_before_purchase_form' ) );
		add_action( 'edd_after_purchase_form', array( $this, 'edd_after_purchase_form' ) );
	}

	/**
	 * Output snippets before the purchase link.
	 *
	 * @return void
	 */
	public function edd_purchase_link_top() {
		$this->output_location( 'edd_purchase_link_top' );
	}

	/**
	 * Output snippets after the purchase link.
	 *
	 * @return void
	 */
	public function edd_purchase_link_end() {
		$this->output_location( 'edd_purchase_link_end' );
	}

	/**
	 * Output snippets before the single download content.
	 *
	 * @return void
	 */
	public function edd_before_download_content() {
		$this->output_location( 'edd_before_download_content' );
	}

	/**
	 * Output snippets after the single download content.
	 *
	 * @return void
	 */
	public function edd_after_download_content() {
		$this->output_location( 'edd_after_download_content' );
	}

	/**
	 * Output snippets before the EDD cart.
	 *
	 * @return void
	 */
	public function edd_before_cart() {
		$this->output_location( 'edd_before_cart' );
	}

	/**
	 * Output snippets after the EDD cart.
	 *
	 * @return void
	 */
	public function edd_after_cart() {
		$this->output_location( 'edd_after_cart' );
	}

	/**
	 * Output snippets before the EDD checkout cart.
	 *
	 * @return void
	 */
	public function edd_before_checkout_cart() {
		$this->output_location( 'edd_before_checkout_cart' );
	}

	/**
	 * Output snippets after the EDD checkout cart.
	 *
	 * @return void
	 */
	public function edd_after_checkout_cart() {
		$this->output_location( 'edd_after_checkout_cart' );
	}

	/**
	 * Output snippets before the EDD checkout form.
	 *
	 * @return void
	 */
	public function edd_before_purchase_form() {
		$this->output_location( 'edd_before_purchase_form' );
	}

	/**
	 * Output snippets after the EDD checkout form.
	 *
	 * @return void
	 */
	public function edd_after_purchase_form() {
		$this->output_location( 'edd_after_purchase_form' );
	}
}

new WPCode_Auto_Insert_EDD();
