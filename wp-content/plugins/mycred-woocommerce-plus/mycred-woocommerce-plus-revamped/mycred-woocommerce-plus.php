<?php
if ( ! class_exists( 'myCred_WooCommerce_Plus' ) ) :
	final class myCred_WooCommerce_Plus {

		// Plugin Version
		public $version = '2.0';
		// Plugin Slug
		public $slug                = 'mycred-woocommerce-plus';

		public $domain = 'mycred-woocommerce-plus';

		// Instnace
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', esc_html( $this->version ) ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', esc_html( $this->version ) ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {

			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
			elseif ( ! $definable && defined( $name ) ) {
				_doing_it_wrong( 'myCred_WooCommerce_Plus->define()', esc_html( 'Could not define: ' . $name . ' as it is already defined somewhere else!' ), esc_html( $this->version ) );
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			add_action( 'upgrader_process_complete', array( $this, 'update_check'), 10, 2 );
			add_action( 'admin_init', array( $this, 'mycred_wooplus_backward_compatibility') );
			 add_action('admin_notices', array( $this, 'display_activation_notice') );
			$this->define_constants();
			$this->includes();	
			$this->init(); 		
			
		}

		public function display_activation_notice() {
            echo '<div class="notice notice-error is-dismissible">';
            echo wp_kses_post(
                __(
                    '<p><strong>📢🚨 Important Notice:</strong> <strong>myCred WooCommerce Plus</strong> will be <span style="color:red;">deprecated soon</span> and will only receive critical security fixes.<br>
                    This module is now included in <strong>myCred Toolkit Pro</strong>. Pro plan or add-on license holders already have access in their 
                    <a href="https://mycred.me/my-account/" target="_blank"><strong>myCred account</strong></a>.</p>', 
                    'mycred_gi'
                )
            );
            echo '<p><a href="' . esc_url('https://mycred.me/blog/what-is-mycred-toolkit-pro/') . '" target="_blank" class="button button-primary">Read More</a></p>';
            echo '</div>';
        }
		
		public function mycred_wooplus_backward_compatibility(){
			$backward_check = get_option('mycred_wooplus_backward_check');
			if (!$backward_check ) {
				apply_backward_compatibility();
				update_option('mycred_wooplus_backward_check', true);
			}
		}

		
		public function update_check($upgrader_object, $options) {
			if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
				$current_plugin_path_name = plugin_basename(__FILE__);
				if ( is_array( $options['plugins'] ) && in_array( $current_plugin_path_name, $options['plugins'] ) ) {
					apply_backward_compatibility();
				}
			}
		}
		
		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'MYCRED_WOOPLUS_VERSION',       $this->version );
			$this->define( 'MYCRED_WOOPLUS_SLUG',          $this->slug );
			$this->define( 'MYCRED_WOOPLUS_THIS',          __FILE__ );
			$this->define( 'MYCRED_WOOPLUS_ROOT_DIR',      plugin_dir_path( MYCRED_WOOPLUS_THIS ) );
			$this->define( 'MYCRED_WOOPLUS_INCLUDES_DIR',  MYCRED_WOOPLUS_ROOT_DIR . 'includes/' );
			$this->define( 'MYCRED_WOOPLUS_BLOCKS_DIR', MYCRED_WOOPLUS_ROOT_DIR . 'includes/woo-block-compatibility/' );
			$this->define( 'MYCRED_WOOPLUS_HOOKS_DIR',     MYCRED_WOOPLUS_INCLUDES_DIR . 'hooks/' );
			$this->define( 'MYCRED_WOOPLUS_ABSTRACT_DIR',  MYCRED_WOOPLUS_ROOT_DIR . 'abstract/' );
			$this->define( 'MYCRED_WOOPLUS_MODULES_DIR',   MYCRED_WOOPLUS_ROOT_DIR . 'modules/' );
			$this->define( 'MYCRED_WOOPLUS_TEMPLATES_DIR', MYCRED_WOOPLUS_ROOT_DIR . 'templates/' );
		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		private function includes() {
			
			if ( file_exists( MYCRED_WOOPLUS_INCLUDES_DIR . 'mwp-functions.php' ) ) {
				include_once( MYCRED_WOOPLUS_INCLUDES_DIR . 'mwp-functions.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_INCLUDES_DIR . 'trait-mwp-helper.php' ) ) {
				include_once( MYCRED_WOOPLUS_INCLUDES_DIR . 'trait-mwp-helper.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_ABSTRACT_DIR . 'class-mwp-abstract-module.php' ) ) {
				include_once( MYCRED_WOOPLUS_ABSTRACT_DIR . 'class-mwp-abstract-module.php' );
			}

			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/automatewoo.php';

			include_once MYCRED_WOOPLUS_BLOCKS_DIR . 'mycred-woo-plus-block-compatibility.php';
			
			if ( file_exists( MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-woocommerce-subscription-gateway.php' ) ) {
				include_once( MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-woocommerce-subscription-gateway.php' );
			}
		}

		/**
		 * Init
		 * @since 2.0
		 * @version 1.0
		 */
		private function init() {
			add_action( 'woocommerce_init', array( $this, 'woocommerce_init_callback' ) );
			add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
			add_action( 'mycred_admin_enqueue',    array( $this, 'register_admin_assets' ) );
			add_action( 'mycred_front_enqueue',    array( $this, 'register_front_assets' ) );
			add_action( 'mycred_load_modules', array( $this, 'load_modules' ) );



		}

		public function woocommerce_init_callback() {
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'gateway/mycred-addon-gateway.php';
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'gateway/mycred-multi-point-type.php';
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'gateway/includes/mycred-woo-plus-block-compatibility.php';

		}

		/**
		 * Load all modules
		 * @since 2.0
		 * @version 1.0
		 */
		public function load_modules() {
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-coupons.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-coupons.php' );
			}

			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-point-type.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-point-type.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-restrict-products.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-restrict-products.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-points-history.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-points-history.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-display-reward.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-display-reward.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-product-referral.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-product-referral.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-my-account.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-my-account.php' );
			}
			
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-partial-payments.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-partial-payments.php' );
			}

			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-hooks.php' ) ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-module-hooks.php' );
			}
			$old_user = get_option( 'mycred_partial_payments_woo' );
			if ( file_exists( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-upgrader.php' ) && $old_user ) {
				include_once( MYCRED_WOOPLUS_MODULES_DIR . 'class-mwp-upgrader.php' );
			}

		}

		public function register_admin_assets() {

			wp_register_style( 'mwp-admin-style', plugins_url( 'assets/css/admin-style.css', MYCRED_WOOPLUS_THIS ), array(), $this->version, 'all' );

		}

		public function register_front_assets() {
			wp_enqueue_script(
				'selected-gateway',
				plugins_url('includes/gateway/assets/js/selected-gateway.js', MYCRED_WOOPLUS_THIS),
				array('jquery'),
				$this->version, 
				true
			);
			wp_register_style( 'mwp-style', plugins_url( 'assets/css/style.css', MYCRED_WOOPLUS_THIS ), array(), $this->version, 'all' );

		}

		public function declare_compatibility() {
			
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {

				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
				
			}

		}
		
	}
endif;

function mycred_load_woocommerce_plus() {
	return myCred_WooCommerce_Plus::instance();
}
mycred_load_woocommerce_plus();