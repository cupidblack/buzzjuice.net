<?php
if ( ! class_exists( 'MyCRED_WooCommerce_Plus' ) ) :
	final class MyCRED_WooCommerce_Plus {

		// Plugin Version
		public $version = '1.8';

		public $slug = 'mycred-woocommerce-plus';

		public $plugin = null;

		// Instnace
		protected static $_instance = null;

		// Current session
		public $session = null;

		public $domain     = 'mycredpartwoo';

		/**
		 * Setup Instance
		 *		 
		 * @since   1.0
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
		 *
		 * @since   1.0
		 * @version 1.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', esc_attr($this->version) );
		}

		/**
		 * Not allowed
		 *		 
		 * @since   1.0
		 * @version 1.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', esc_attr($this->version) );
		}

		/**
		 * Define
		 *		 
		 * @since   1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			} elseif ( ! $definable && defined( $name ) ) {
				_doing_it_wrong( 'MyCRED_WooCommerce_Plus->define()', 'Could not define: ' . esc_attr($name) . ' as it is already defined somewhere else!', esc_attr($this->version) );
			}
		}

		/**
		 * Construct
		 *
		 * @since   1.0
		 * @version 1.0
		 */
		public function __construct() {
		
			add_action( 'admin_notices', array( $this, 'meet_requirements' ) );
			
			if ( ! defined( 'myCRED_VERSION' ) ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_init' ) );

			$this->plugin = plugin_basename( __FILE__ );
			$this->define_constants();
			$this->includes();

			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 20 );
			add_filter( 'plugin_action_links', array( $this, 'disable_plugin_deactivation' ), 10, 4 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
			add_action( 'before_woocommerce_init', array( $this, 'gamification_hpos_compatibility' ) );
			
		}
		
		/**
		* Meet Requirements
		* Check if meet requirements or not
		*
		* @since 1.0
		* @version 1.0
		*/
		public function meet_requirements() {
			if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) { 
				?>
					<div class="notice notice-error is-dismissible">
						<p><?php echo wp_kses_post( 'In order to use myCred WooCommerce Plus, Install and activate <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>' ); ?></p>
					</div>
				<?php
			}
			if ( !is_plugin_active( 'mycred/mycred.php' ) ) { 
				?>
					<div class="notice notice-error is-dismissible">
						<p><?php echo wp_kses_post( 'In order to use myCred WooCommerce Plus, Install and activate <a href="https://wordpress.org/plugins/mycred/">myCRED</a>' ); ?></p>
					</div>
				<?php
			}
		}
		
		public function is_mycred_hook_page( $page ) {
		   return ( strpos( $page, 'mycred' ) !== false && strpos( $page, 'hook' ) !== false );
		}

		public function admin_init( $hook) {
			
			if ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'settings_wooplus' === $_GET['tab'] ) {
			  wp_enqueue_style( 'mycred-woo-admin', plugins_url( 'assets/css/admin-style.css', __FILE__ ), array(), $this->version );
			}
			
			if ( $this->is_mycred_hook_page( $hook ) ) {
			  wp_enqueue_script( 'mycred-hook-referral-js', plugins_url( 'assets/js/mycred-hook-referrals.js', __FILE__ ), array(), $this->version );
			}

			if ( is_plugin_active( 'mycred-partial-woo/mycred-partial-woo.php' ) ) {
				deactivate_plugins( 'mycred-partial-woo/mycred-partial-woo.php' );
			}

		}

		public function disable_plugin_deactivation( $actions, $plugin_file, $plugin_data, $context ) {

			if ( 
				array_key_exists( 'activate', $actions ) && 
				in_array( $plugin_file, array( 'mycred-partial-woo/mycred-partial-woo.php' ) ) 
			) {
				unset( $actions['activate'] );
			}

			return $actions;

		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 *
		 * @since   1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'MYCRED_WOOPLUS_VERSION', $this->version );
			$this->define( 'MYCRED_WOOPLUS_SLUG', $this->slug );
			$this->define( 'MYCRED_WOOPLUS_THIS', __FILE__ );
			$this->define( 'MYCRED_WOOPLUS_ROOT_DIR', plugin_dir_path( MYCRED_WOOPLUS_THIS ) );
			$this->define( 'MYCRED_WOOPLUS_INCLUDES_DIR', MYCRED_WOOPLUS_ROOT_DIR . 'includes/' );
			$this->define( 'MYCRED_WOOPLUS_BLOCKS_DIR', MYCRED_WOOPLUS_ROOT_DIR . 'includes/woo-block-compatibility/' );
			$this->define( 'MYCRED_WOOPLUS_TEMPLATES_DIR', MYCRED_WOOPLUS_ROOT_DIR . 'templates/' );
			$this->define( 'myCRED_WOOPLUS_SHORTCODES_DIR', MYCRED_WOOPLUS_INCLUDES_DIR . 'shortcodes/' );

		}

		/**
		 * Include Plugin Files
		 *
		 * @since   1.0
		 * @since 1.7.6 Global functions file and AutomateWoo added
		 * @version 1.0
		 */
		public function includes() {

			//Global functions
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-wooplus-functions.php';

			// add woocommerce tab settings
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-wooplus-settings.php';

			// add product ristrict code
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-ristrict-product.php';
			
			// add badge rank coupons code
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-badge-rank-coupons.php';

			// add points history code
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-points-history.php';

			// add partial payment code
			include_once MYCRED_WOOPLUS_ROOT_DIR . 'mycred-partial-woo.php';

			include_once MYCRED_WOOPLUS_ROOT_DIR . 'includes/mycred-woocommerce-show-products-by-ranks.php';
			
			// Reward points product and checkout and global reward option
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-reward-product.php';

			// wp-experts-product-refferal
			include_once myCRED_WOOPLUS_SHORTCODES_DIR . 'mycred-woocommerce-referrals-shortcode.php';
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-product-referral-hook.php';
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-rank-product-discount.php';
			// Add WooCommerce Subscription Gateway Support
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-woocommerce-subscription-gateway.php';

			// Add WooCommerce MY Account tab for badges,Ranks,Blance,Level
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'mycred-my-account.php';

			//AutomateWoo
			include_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/automatewoo.php';
			
			if(  function_exists('mycred_part_woo_settings')){
				//WooCommerce Blocks Compatibility
				include_once MYCRED_WOOPLUS_BLOCKS_DIR . 'mycred-woo-plus-block-compatibility.php';
			}
			

		}

		/**
		 * Enqueue Scripts
		 *
		 * @since   1.0
		 * @version 1.0
		 */
		public static function enqueue_styles() {

			wp_register_style(
				'woo-plus',
				plugins_url( 'assets/css/woo-plus.css', MYCRED_WOOPLUS_THIS ),
				array(),
				'1.7.2'
			);

			wp_enqueue_style( 'woo-plus' );

		}

		public static function enqueue_scripts() {

			wp_register_script(
				'partial_payment_setting',
				plugins_url( 'assets/js/partial_payment_setting.js', MYCRED_WOOPLUS_THIS ),
				'',
				MYCRED_WOOPLUS_VERSION
			);
			if ( isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['section'] ) && 'partial_payments' == $_GET['section'] ) {
				
				wp_enqueue_script( 'partial_payment_setting', '', '', MYCRED_WOOPLUS_VERSION );
			}
		}
		
		/**
		 * Woocommerce Compability
		 *
		 * @since   1.0.3.2
		 * @version 1.0
		 */
		public function gamification_hpos_compatibility() {

			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}

		}

	}
endif;

add_action( 'plugins_loaded', 'MyCRED_WooCommerce_Plus_RUN' );

function myCRED_WooCommerce_Plus_RUN() {
	return MyCRED_WooCommerce_Plus::instance();
}
