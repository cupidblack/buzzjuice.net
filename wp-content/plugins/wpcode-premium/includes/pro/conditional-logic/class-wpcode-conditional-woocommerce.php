<?php
/**
 * Class that handles conditional logic related to WooCommerce.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_WooCommerce class.
 */
class WPCode_Conditional_WooCommerce extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'woocommerce';

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
		$this->label = 'WooCommerce';
	}

	/**
	 * Hooks specific to this conditional type.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_wpcode_woocommerce_get_products', array( $this, 'ajax_get_products' ) );
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'wc_page' => array(
				'callback' => array( $this, 'get_page_type' ),
			),
			'wc_cart' => array(
				'callback' => array( $this, 'get_cart_contents' ),
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
			'wc_page' => array(
				'label'       => __( 'WooCommerce Page', 'wpcode-premium' ),
				'description' => __( 'Load the snippet on specific WooCommerce pages.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(
					array(
						'label' => __( 'Checkout Page', 'wpcode-premium' ),
						'value' => 'checkout',
					),
					array(
						'label' => __( 'Thank You Page', 'wpcode-premium' ),
						'value' => 'thank_you',
					),
					array(
						'label' => __( 'Cart Page', 'wpcode-premium' ),
						'value' => 'cart',
					),
					array(
						'label' => __( 'Single Product Page', 'wpcode-premium' ),
						'value' => 'product',
					),
					array(
						'label' => __( 'Shop Page', 'wpcode-premium' ),
						'value' => 'shop',
					),
					array(
						'label' => __( 'Product Category Page', 'wpcode-premium' ),
						'value' => 'product_cat',
					),
					array(
						'label' => __( 'Product Tag Page', 'wpcode-premium' ),
						'value' => 'product_tag',
					),
					array(
						'label' => __( 'My Account Page', 'wpcode-premium' ),
						'value' => 'account_page',
					),
				),
				'callback'    => array( $this, 'get_page_type' ),
			),
			'wc_cart' => array(
				'label'           => __( 'WooCommerce Cart', 'wpcode-premium' ),
				'description'     => __( 'Load the snippet based on the WooCommerce Cart Contents.', 'wpcode-premium' ),
				'type'            => 'ajax',
				'options'         => 'wpcode_woocommerce_get_products',
				'callback'        => array( $this, 'get_cart_contents' ),
				'labels_callback' => array( $this, 'get_products_labels' ),
				'operator_labels' => array(
					'='  => __( 'Contains', 'wpcode-premium' ),
					'!=' => __( 'Does Not Contain', 'wpcode-premium' ),
				),
				'placeholder'     => __( 'Select a product', 'wpcode-premium' ),
				'multiple'        => true,
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['wc_page']['upgrade']          = array(
					'title'  => __( 'WooCommerce Page Rules is a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
				$this->options['wc_cart']['upgrade']          = $this->options['wc_page']['upgrade'];
				$this->options['wc_cart']['upgrade']['title'] = __( 'WooCommerce Cart Rules is a Pro Feature', 'wpcode-premium' );
			} elseif ( ! class_exists( 'WooCommerce' ) ) {
				$this->options['wc_page']['upgrade'] = array(
					'title'  => __( 'WooCommerce Is Not Installed', 'wpcode-premium' ),
					'text'   => __( 'Please install and activate WooCommerce to use this feature.', 'wpcode-premium' ),
					'link'   => admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ),
					'button' => __( 'Install WooCommerce Now', 'wpcode-premium' ),
				);
				$this->options['wc_cart']['upgrade'] = $this->options['wc_page']['upgrade'];
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
		if ( ! class_exists( 'woocommerce' ) ) {
			return '';
		}
		if ( is_order_received_page() ) {
			return 'thank_you';
		}
		if ( is_checkout() ) {
			return 'checkout';
		}
		if ( is_shop() ) {
			return 'shop';
		}
		if ( is_product() ) {
			return 'product';
		}
		if ( is_product_category() ) {
			return 'product_cat';
		}
		if ( is_product_tag() ) {
			return 'product_tag';
		}
		if ( is_account_page() ) {
			return 'account_page';
		}

		return '';
	}

	/**
	 * Get WooCommerce products for the select field in the admin.
	 *
	 * @return void
	 */
	public function ajax_get_products() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_send_json_error();
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		// First get products by SKU.
		$sku_query = new WP_Query(
			array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'meta_query'     => array( // phpcs:ignore
					array(
						'key'     => '_sku',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			)
		);

		// Then get products by title or variation attributes.
		$title_query = new WP_Query(
			array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				's'              => $term,
				'meta_query'     => array( // phpcs:ignore
					'relation' => 'OR',
					array(
						'key'     => '_variation_description',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			)
		);

		// Merge results.
		$products = array_merge( $sku_query->posts, $title_query->posts );
		$products = array_unique( $products, SORT_REGULAR );
		$results  = array();

		foreach ( $products as $product ) {
			$wc_product = wc_get_product( $product );
			if ( ! $wc_product ) {
				continue;
			}

			$name = $wc_product->get_name();
			$sku  = $wc_product->get_sku();

			if ( $sku ) {
				$name .= ' (SKU: ' . $sku . ')';
			}

			if ( $wc_product->is_type( 'variation' ) ) {
				$parent = wc_get_product( $wc_product->get_parent_id() );
				if ( $parent ) {
					$name = $parent->get_name() . ' - ' . wc_get_formatted_variation( $wc_product, true );
				}
			}

			$results[] = array(
				'id'   => $wc_product->get_id(),
				'text' => $name,
			);
		}

		wp_send_json(
			array(
				'results' => $results,
			)
		);
	}

	/**
	 * Get the term labels for the taxonomy term value loading in the admin form.
	 *
	 * @param array $values The values that are selected.
	 *
	 * @return array
	 */
	public function get_products_labels( $values ) {
		$labels = array();
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $labels;
		}
		foreach ( $values as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( is_null( $product ) || is_wp_error( $product ) ) {
				continue;
			}
			$labels[] = array(
				'value' => $product_id,
				'label' => $product->get_name(),
			);
		}

		return $labels;
	}

	/**
	 * Get the WC cart contents to compare against.
	 *
	 * @return array
	 */
	public function get_cart_contents() {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}
		$cart_contents = WC()->cart->get_cart_contents();

		// Return an array of ids of the products in the cart.
		$cart_products = array();
		foreach ( $cart_contents as $cart_item ) {
			if ( ! empty( $cart_item['variation_id'] ) ) {
				$cart_products[] = $cart_item['variation_id'];
			}
			$cart_products[] = $cart_item['product_id'];
		}

		return array_unique( $cart_products );
	}
}

new WPCode_Conditional_WooCommerce();
