<?php
/**
 * Class YITH_Role_Changer_Admin
 *
 * @package YITH\AutomaticRoleChanger\Classes
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'YITH_WCARC_VERSION' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_Role_Changer_Admin' ) ) {
	/**
	 * Class YITH_Role_Changer_Admin
	 */
	class YITH_Role_Changer_Admin {
		use YITH_WCARC_Extensible_Singleton_Trait;

		/**
		 * Panel Object.
		 *
		 * @var $panel
		 */
		protected $panel = null;

		/**
		 * Panel page.
		 *
		 * @var string
		 */
		protected $panel_page = 'yith_wcarc_panel';

		/**
		 * Show the premium landing page.
		 *
		 * @deprecated 2.0
		 * @var bool
		 */
		public $show_premium_landing = false;

		/**
		 * Construct
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCARC_PATH . '/' . basename( YITH_WCARC_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

			add_action( 'yith_wcarc_rules_tab', array( $this, 'print_rules_tab' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'pre_load_script_translations', array( $this, 'script_translations' ), 10, 4 );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'role_column_content' ), 100, 2 );
				add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( $this, 'add_role_granted_info_meta_box' ) );
			} else {
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'role_column_content' ), 100, 2 );
				add_action( 'add_meta_boxes_shop_order', array( $this, 'add_role_granted_info_meta_box' ) );
			}
		}

		/**
		 * Add a panel under YITH Plugins tab
		 */
		public function register_panel() {
			if ( ! empty( $this->panel ) ) {
				return;
			}

			$admin_tabs = apply_filters(
				'yith_wcarc_admin_tabs',
				array(
					'rules' => array(
						'title'       => __( 'Rules', 'yith-automatic-role-changer-for-woocommerce' ),
						'icon'        => '<svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"></path></svg>',
						'description' => __( 'Create and configure your own rules for automatic role switching.', 'yith-automatic-role-changer-for-woocommerce' ),
					),
				)
			);

			$args = array(
				'ui_version'       => 2,
				'create_menu_page' => true,
				'parent_slug'      => '',
				'plugin_slug'      => YITH_WCARC_SLUG,
				'page_title'       => 'YITH Automatic Role Changer for WooCommerce',
				'menu_title'       => 'Automatic Role Changer',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_WCARC_OPTIONS_PATH,
				'is_free'          => false,
				'is_premium'       => true,
				'is_extended'      => false,
				'help_tab'         => array(),
				'your_store_tools' => array(
					'items' => array(
						'role-based-prices'             => array(
							'name'           => 'Role Based Prices',
							'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-role-based-prices/',
							'icon_url'       => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-role-based-prices.svg',
							'description'    => __( 'Allows you to show different prices based on the role your users have, and offer dedicated discounts or increase the product prices.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'      => defined( 'YWCRBP_PREMIUM' ),
							'is_recommended' => true,
						),
						'subscription'                  => array(
							'name'        => 'Subscription',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-subscription/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-subscription.svg',
							'description' => __( 'Allows generating recurring payments for your products. Let your customers join a subscription plan with a payment cycle of your choice and many other options.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_YWSBS_PREMIUM' ),
						),
						'gift-card'                     => array(
							'name'        => 'Gift Cards',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2015/10/gift-card.svg',
							'description' => __( 'The essential tool for selling gift cards in your store, increasing your conversion rate, and attracting new customers.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_YWGC_PREMIUM' ),
						),
						'wishlist'                      => array(
							'name'        => 'Wishlist',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-wishlist/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-wishlist.svg',
							'description' => __( 'Let your customers save products to wishlists to share with family and friends, and create targeted email campaigns that encourage them to buy.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_WCWL_PREMIUM' ),
						),
						'dynamic-pricing-and-discounts' => array(
							'name'        => 'Dynamic Pricing & Discounts',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-dynamic-pricing-and-discounts/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-dynamic-pricing-and-discounts.svg',
							'description' => __( 'The best-selling plugin for creating promotions and upsell strategies in your store: 3x2, 2x1, BOGO, free products in the cart, quantity discounts, last-minute offers, and more.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_YWDPD_PREMIUM' ),
						),
						'ajax-search'                   => array(
							'name'        => 'Ajax Search',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-ajax-search/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-ajax-search.svg',
							'description' => __( 'Add an intelligent search engine to your store so that your customers can quickly find the products they are looking for.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_WCAS_PREMIUM' ),
						),
						'ajax-product-filter'           => array(
							'name'        => 'Ajax Product Filter',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-ajax-product-filter/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-ajax-product-filter.svg',
							'description' => __( 'Display a filter section on your store page and allow your customers to easily filter products by their requirements (price, size, color, model, etc.).', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_WCAN_PREMIUM' ),
						),
						'email-templates'               => array(
							'name'        => 'Email Templates',
							'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-email-templates/',
							'icon_url'    => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-email-templates.svg',
							// translators: Description of "YITH Email Templates" plugin in the "Your Store Tools" tab.
							'description' => __( 'Manage and customize the templates of the emails sent from your store for a more professional look.', 'yith-automatic-role-changer-for-woocommerce' ),
							'is_active'   => defined( 'YITH_WCET_PREMIUM' ),
						),
					),
				),
			);

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Adds action links to plugin admin page
		 *
		 * @param array    $row_meta_args Row meta args.
		 * @param string[] $plugin_meta   An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file   Path to the plugin file relative to the plugins directory.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $row_meta_args, $plugin_meta, $plugin_file ) {
			if ( YITH_WCARC_INIT === $plugin_file ) {
				$row_meta_args['slug']       = YITH_WCARC_SLUG;
				$row_meta_args['is_premium'] = true;
			}

			return $row_meta_args;
		}

		/**
		 * Add Action Links to Plugin Management.
		 *
		 * @param mixed $links Links to be added.
		 *
		 * @return mixed
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, true, YITH_WCARC_SLUG );

			return $links;
		}

		/**
		 * Create metabox of add_roles_granted.
		 *
		 * @param mixed $post Current post.
		 */
		public function add_role_granted_info_meta_box( $post ) {
			$order = $post instanceof WC_Order ? $post : wc_get_order( $post->ID );
			$rules = $order->get_meta( '_ywarc_rules_granted' );

			if ( $rules ) {
				$screen = OrderUtil::custom_orders_table_usage_is_enabled() ? 'woocommerce_page_wc-orders' : 'shop_order';
				add_meta_box(
					'ywarc-order-roles-granted',
					esc_html__( 'Automatic role changer', 'yith-automatic-role-changer-for-woocommerce' ),
					array( $this, 'ywarc_order_roles_granted_content' ),
					$screen,
					'side',
					'core',
					$rules
				);
			}
		}

		/**
		 * Print content about Roles Granted.
		 *
		 * @param mixed $post Current post ID.
		 * @param array $meta Contain the rules.
		 */
		public function ywarc_order_roles_granted_content( $post, $meta ) {
			if ( $post && $meta['args'] ) {
				$rules = $meta['args'];

				include trailingslashit( YITH_WCARC_PATH ) . 'views/order-gained-roles-meta-box.php';
			}
		}

		/**
		 * Print role column.
		 *
		 * @param mixed $column_name Current Column Name.
		 * @param mixed $post_id     Current Post ID.
		 *
		 * @return void
		 */
		public function role_column_content( $column_name, $post_id ) {
			$order = wc_get_order( $post_id );
			$rules = $order->get_meta( '_ywarc_rules_granted' );

			$column_to_use = version_compare( WC()->version, '3.3.0', '>=' ) ? 'order_number' : 'order_status';

			if ( $rules && $column_name === $column_to_use ) {

				// Count the total number of roles granted.
				$roles_count = 0;
				foreach ( $rules as $rule_id => $rule ) {
					if ( 'add' === $rule['rule_type'] && ! empty( $rule['role_selected'] && is_countable( $rule['role_selected'] ) ) ) {
						$roles_count = $roles_count + count( $rule['role_selected'] );
					} elseif ( 'replace' === $rule['rule_type'] && ! empty( $rule['replace_roles'] ) ) {
						++$roles_count;
					}
				}

				$details = sprintf(
				// translators: %s is replaced with number of roles.
					_n( '%d new role gained with this order', '%d new roles gained with this order', $roles_count, 'yith-automatic-role-changer-for-woocommerce' ),
					$roles_count
				);

				echo '<img class="ywarc_role_icon woocommerce-help-tip" data-tip="' . esc_attr( $details ) . '" src="' . YITH_WCARC_ASSETS_URL . '/images/badge.png"></span>';
			}
		}

		/**
		 * Print rules tab
		 */
		public function print_rules_tab() {
			echo "<div id='yith-automatic-role-changer-rules-panel'></div>";
		}

		/**
		 * Load admin JS & CSS.
		 */
		public function enqueue_scripts() {
			$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			$screen_id = ! ! $screen && is_a( $screen, 'WP_Screen' ) ? $screen->id : false;

			wp_register_style( 'ywarc-admin-orders', YITH_WCARC_ASSETS_URL . '/css/admin-orders.css', array(), YITH_WCARC_VERSION );

			if ( in_array( $screen_id, array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) ), true ) ) {
				wp_enqueue_style( 'ywarc-admin-orders' );
			}

			if ( isset( $_GET['page'] ) && 'yith_wcarc_panel' === $_GET['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification
				$script_asset = include YITH_WCARC_DIST_PATH . 'admin-rules-panel/index.asset.php';
				$dependencies = $script_asset['dependencies'] ?? array();
				$version      = $script_asset['version'] ?? '1.0.0';

				$script = YITH_WCARC_DIST_URL . 'admin-rules-panel/index.js';

				wp_enqueue_script( 'yith-wcarc-admin-rules-panel', $script, $dependencies, $version, true );
				wp_set_script_translations( 'yith-wcarc-admin-rules-panel', 'yith-automatic-role-changer-for-woocommerce', YITH_WCARC_LANGUAGES_PATH );

				$editable_roles = array_reverse( get_editable_roles() );
				$roles          = array();

				foreach ( $editable_roles as $role => $details ) {
					$roles[] = array(
						'value' => $role,
						'label' => translate_user_role( $details['name'] ),
					);
				}

				usort( $roles, function ( $a, $b ) {
					return strcmp( $a['label'], $b['label'] );
				} );

				wp_localize_script(
					'yith-wcarc-admin-rules-panel',
					'yith_wcarc_admin',
					array(
						'productsCount'          => array_sum( (array) wp_count_posts( 'product' ) ),
						'productTagsCount'       => wp_count_terms( 'product_tag' ),
						'productCategoriesCount' => wp_count_terms( 'product_cat' ),
						'currencySymbol'         => get_woocommerce_currency_symbol(),
						'roles'                  => $roles,
					)
				);
			}
		}

		/**
		 * Create the json translation through the PHP file.
		 * So, it's possible using normal translations (with PO files) also for JS translations
		 *
		 * @param string|null $json_translations Translations.
		 * @param string      $file              The file.
		 * @param string      $handle            The handle.
		 * @param string      $domain            The text-domain.
		 *
		 * @return string|null
		 */
		public function script_translations( $json_translations, $file, $handle, $domain ) {
			if ( 'yith-automatic-role-changer-for-woocommerce' === $domain ) {
				$path = trailingslashit( YITH_WCARC_LANGUAGES_PATH ) . 'js-i18n.php';
				if ( file_exists( $path ) ) {
					$translations = include $path;

					$json_translations = wp_json_encode(
						array(
							'domain'      => 'yith-automatic-role-changer-for-woocommerce',
							'locale_data' => array(
								'messages' =>
									array(
										'' => array(
											'domain'       => 'yith-automatic-role-changer-for-woocommerce',
											'lang'         => get_locale(),
											'plural-forms' => 'nplurals=2; plural=(n != 1);',
										),
									)
									+
									$translations,
							),
						)
					);

				}
			}

			return $json_translations;
		}

		/**
		 * Premium Tab Template
		 * Load the premium tab template on admin page
		 *
		 * @deprecated 2.0
		 */
		public function premium_tab() {

		}

		/**
		 * Include the rules-tab template
		 *
		 * @deprecated 2.0
		 */
		public function rules_tab() {

		}

		/**
		 * Include the load-rules template
		 *
		 * @deprecated 2.0
		 */
		public function load_rules() {
		}

		/**
		 * Add rule template.
		 *
		 * @deprecated 2.0
		 */
		public function add_rule() {

		}

		/**
		 * Triggers when saving a rule, and saves it into a metaoption
		 *
		 * @deprecated 2.0
		 */
		public function save_rule() {

		}

		/**
		 * Delete a rule from metaoption (Using POST)
		 *
		 * @deprecated 2.0
		 */
		public function delete_rule() {

		}

		/**
		 * Delete the rule metaoption
		 *
		 * @deprecated 2.0
		 */
		public function delete_all_rules() {

		}

		/**
		 * Search categories
		 *
		 * @deprecated 2.0
		 */
		public function category_search() {

		}

		/**
		 * Search tags
		 *
		 * @deprecated 2.0
		 */
		public function tag_search() {

		}
	}
}
