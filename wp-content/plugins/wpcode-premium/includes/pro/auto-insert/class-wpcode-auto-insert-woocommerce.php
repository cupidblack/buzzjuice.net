<?php
/**
 * WooCommerce-specific auto-insert locations.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Auto_Insert_WooCommerce.
 */
class WPCode_Auto_Insert_WooCommerce extends WPCode_Auto_Insert_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'woocommerce';
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
			'wc_before_products_list'              => array(),
			'wc_after_products_list'               => array(),
			'wc_before_single_product'             => array(),
			'wc_after_single_product'              => array(),
			'wc_before_single_product_summary'     => array(),
			'wc_after_single_product_summary'      => array(),
			'woocommerce_before_cart'              => array(),
			'woocommerce_after_cart'               => array(),
			'woocommerce_before_checkout_form'     => array(),
			'woocommerce_after_checkout_form'      => array(),
			'woocommerce_checkout_order_review_19' => array(),
			'woocommerce_checkout_order_review_21' => array(),
			'woocommerce_before_thankyou'          => array(),
		);
	}

	/**
	 * Load the label for WooCommerce.
	 *
	 * @return void
	 */
	public function load_label() {
		$this->label     = 'WooCommerce';
	}

	/**
	 * Load the locations for WooCommerce.
	 *
	 * @return void
	 */
	public function load_locations() {
		$this->locations = array(
			'wc_before_products_list'              => array(
				'label'       => __( 'Before the List of Products', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the list of products on a WooCommerce page.', 'insert-headers-and-footers' ),
			),
			'wc_after_products_list'               => array(
				'label'       => __( 'After the List of Products', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the list of products on a WooCommerce page.', 'insert-headers-and-footers' ),
			),
			'wc_before_single_product'             => array(
				'label'       => __( 'Before the Single Product', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the content on the single WooCommerce product page.', 'insert-headers-and-footers' ),
			),
			'wc_after_single_product'              => array(
				'label'       => __( 'After the Single Product', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the content on the single WooCommerce product page.', 'insert-headers-and-footers' ),
			),
			'wc_before_single_product_summary'     => array(
				'label'       => __( 'Before the Single Product Summary', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the product summary on the single WooCommerce product page.', 'insert-headers-and-footers' ),
			),
			'wc_after_single_product_summary'      => array(
				'label'       => __( 'After the Single Product Summary', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the product summary on the single WooCommerce product page.', 'insert-headers-and-footers' ),
			),
			'woocommerce_before_cart'              => array(
				'label'       => __( 'Before the Cart', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the cart on WooCommerce pages.', 'insert-headers-and-footers' ),
			),
			'woocommerce_after_cart'               => array(
				'label'       => __( 'After the Cart', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the cart on WooCommerce pages.', 'insert-headers-and-footers' ),
			),
			'woocommerce_before_checkout_form'     => array(
				'label'       => __( 'Before the Checkout Form', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the checkout form on the WooCommerce checkout page.', 'insert-headers-and-footers' ),
			),
			'woocommerce_after_checkout_form'      => array(
				'label'       => __( 'After the Checkout Form', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the checkout form on the WooCommerce checkout page.', 'insert-headers-and-footers' ),
			),
			'woocommerce_checkout_order_review_19' => array(
				'label'       => __( 'Before Checkout Payment Button', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the checkout payment button on the WooCommerce checkout page.', 'insert-headers-and-footers' ),
			),
			'woocommerce_checkout_order_review_21' => array(
				'label'       => __( 'After Checkout Payment Button', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet after the checkout payment button on the WooCommerce checkout page.', 'insert-headers-and-footers' ),
			),
			'woocommerce_before_thankyou'          => array(
				'label'       => __( 'Before the Thank You Page', 'insert-headers-and-footers' ),
				'description' => __( 'Insert snippet before the thank you page content for WooCommerce.', 'insert-headers-and-footers' ),
			),
		);
	}

	/**
	 * Load the upgrade strings.
	 *
	 * @return void
	 */
	public function load_upgrade_strings() {
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->label_pill     = 'PRO';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'WooCommerce Locations are a PRO feature', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' );
				$this->upgrade_link   = add_query_arg(
					array(
						'page' => 'wpcode-settings',
					),
					admin_url( 'admin.php' )
				);
				$this->upgrade_button = __( 'Add License Key Now', 'wpcode-premium' );
			} elseif ( ! class_exists( 'WooCommerce' ) ) {
				$this->label_pill     = 'Not Installed';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'WooCommerce Is Not Installed', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please install and activate WooCommerce to use this feature.', 'wpcode-premium' );
				$this->upgrade_link   = admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' );
				$this->upgrade_button = __( 'Install WooCommerce Now', 'wpcode-premium' );
			}
		}
	}

	/**
	 * WooCommerce-specific hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'woocommerce_before_shop_loop', array( $this, 'insert_before_products_list' ) );
		add_action( 'woocommerce_after_shop_loop', array( $this, 'insert_after_products_list' ) );
		add_action( 'woocommerce_before_single_product', array( $this, 'insert_before_single_product' ) );
		add_action( 'woocommerce_after_single_product', array( $this, 'insert_after_single_product' ) );
		add_action(
			'woocommerce_before_single_product_summary',
			array(
				$this,
				'insert_before_single_product_summary',
			)
		);
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'insert_after_single_product_summary' ) );
		add_action( 'woocommerce_before_cart', array( $this, 'woocommerce_before_cart' ) );
		add_action( 'woocommerce_after_cart', array( $this, 'woocommerce_after_cart' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'woocommerce_before_checkout_form' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'woocommerce_after_checkout_form' ) );
		add_action( 'woocommerce_checkout_order_review', array( $this, 'woocommerce_checkout_order_review_19' ), 19 );
		add_action( 'woocommerce_checkout_order_review', array( $this, 'woocommerce_checkout_order_review_21' ), 21 );
		add_action( 'woocommerce_before_thankyou', array( $this, 'woocommerce_before_thankyou' ) );
	}

	/**
	 * Output snippets before the WooCommerce product list.
	 *
	 * @return void
	 */
	public function insert_before_products_list() {
		$this->output_location( 'wc_before_products_list' );
	}

	/**
	 * Output snippets after the WooCommerce product list.
	 *
	 * @return void
	 */
	public function insert_after_products_list() {
		$this->output_location( 'wc_after_products_list' );
	}

	/**
	 * Output snippets before the WooCommerce single product.
	 *
	 * @return void
	 */
	public function insert_before_single_product() {
		$this->output_location( 'wc_before_single_product' );
	}

	/**
	 * Output snippets after the WooCommerce single product.
	 *
	 * @return void
	 */
	public function insert_after_single_product() {
		$this->output_location( 'wc_after_single_product' );
	}

	/**
	 * Output snippets before the WooCommerce single product summary.
	 *
	 * @return void
	 */
	public function insert_before_single_product_summary() {
		$this->output_location( 'wc_before_single_product_summary' );
	}

	/**
	 * Output snippets after the WooCommerce single product summary.
	 *
	 * @return void
	 */
	public function insert_after_single_product_summary() {
		$this->output_location( 'wc_after_single_product_summary' );
	}

	/**
	 * Output snippets before the cart.
	 *
	 * @return void
	 */
	public function woocommerce_before_cart() {
		$this->output_location( 'woocommerce_before_cart' );
	}

	/**
	 * Output snippets after the cart.
	 *
	 * @return void
	 */
	public function woocommerce_after_cart() {
		$this->output_location( 'woocommerce_after_cart' );
	}

	/**
	 * Output snippets before the checkout form.
	 *
	 * @return void
	 */
	public function woocommerce_before_checkout_form() {
		$this->output_location( 'woocommerce_before_checkout_form' );
	}

	/**
	 * Output snippets after the checkout form.
	 *
	 * @return void
	 */
	public function woocommerce_after_checkout_form() {
		$this->output_location( 'woocommerce_after_checkout_form' );
	}

	/**
	 * Output snippets before the payment form on the checkout.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_order_review_19() {
		$this->output_location( 'woocommerce_checkout_order_review_19' );
	}

	/**
	 * Output snippets after the payment form on the checkout.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_order_review_21() {
		$this->output_location( 'woocommerce_checkout_order_review_21' );
	}

	/**
	 * Output snippets before the content of the thank you page.
	 *
	 * @return void
	 */
	public function woocommerce_before_thankyou() {
		$this->output_location( 'woocommerce_before_thankyou' );
	}
}

new WPCode_Auto_Insert_WooCommerce();
